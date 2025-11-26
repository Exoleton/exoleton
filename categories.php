<?php
// Fonctions utilitaires pour les catégories multilingues

const EXOLETON_LANGUAGES = ['fr','en','de','it','es','pt','nl','pl','jp','zh','kr','ru'];

function bootstrap_categories_schema(PDO $pdo): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT NOT NULL UNIQUE,
                default_label TEXT NOT NULL,
                translations TEXT NULL,
                scope TEXT NOT NULL DEFAULT 'product',
                sort_order INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )"
        );

        add_column_if_missing($pdo, 'products', 'category_slug', 'TEXT');
        add_column_if_missing($pdo, 'guides', 'category_slug', 'TEXT');
    } else {
        // Table des catégories (multilingue)
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS categories (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(150) NOT NULL UNIQUE,
                default_label VARCHAR(190) NOT NULL,
                translations JSON NULL,
                scope ENUM('product','guide','all') NOT NULL DEFAULT 'product',
                sort_order INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
        );

        // Colonnes de rattachement côté produits & guides (slug pour compatibilité)
        add_column_if_missing($pdo, 'products', 'category_slug', "VARCHAR(150) NULL AFTER category");
        add_column_if_missing($pdo, 'guides', 'category_slug', "VARCHAR(150) NULL AFTER category");
    }

    // Graines par défaut si la table est vide
    $existing = $pdo->query('SELECT COUNT(*) AS c FROM categories')->fetchColumn();
    if ((int)$existing === 0) {
        $defaults = default_category_seed();
        $stmt = $pdo->prepare('INSERT INTO categories (slug, default_label, translations, scope, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)');
        foreach ($defaults as $cat) {
            $stmt->execute([
                $cat['slug'],
                $cat['label'],
                json_encode($cat['translations'], JSON_UNESCAPED_UNICODE),
                $cat['scope'],
                $cat['order'],
            ]);
        }
    }

    // Mise à jour des catégories pour les contenus existants
    synchronize_category_slugs($pdo);
}

function default_category_seed(): array
{
    $map = [
        'industriel' => [
            'label' => 'Industriel',
            'scope' => 'product',
            'order' => 1,
            'translations' => [
                'fr' => 'Industriel',
                'en' => 'Industrial',
                'de' => 'Industriell',
                'it' => 'Industriale',
                'es' => 'Industrial',
                'pt' => 'Industrial',
                'nl' => 'Industrieel',
                'pl' => 'Przemysłowy',
                'jp' => '産業用',
                'zh' => '工业',
                'kr' => '산업용',
                'ru' => 'Промышленный',
            ],
        ],
        'medical' => [
            'label' => 'Médical',
            'scope' => 'product',
            'order' => 2,
            'translations' => [
                'fr' => 'Médical',
                'en' => 'Medical',
                'de' => 'Medizinisch',
                'it' => 'Medico',
                'es' => 'Médico',
                'pt' => 'Médico',
                'nl' => 'Medisch',
                'pl' => 'Medyczny',
                'jp' => '医療用',
                'zh' => '医疗',
                'kr' => '의료용',
                'ru' => 'Медицинский',
            ],
        ],
        'particulier' => [
            'label' => 'Particulier / Quotidien',
            'scope' => 'product',
            'order' => 3,
            'translations' => [
                'fr' => 'Particulier / Quotidien',
                'en' => 'Personal / Daily',
                'de' => 'Privat / Alltag',
                'it' => 'Personale / Quotidiano',
                'es' => 'Particular / Diario',
                'pt' => 'Particular / Diário',
                'nl' => 'Particulier / Dagelijks',
                'pl' => 'Osobisty / Codzienny',
                'jp' => '個人/日常',
                'zh' => '个人/日用',
                'kr' => '개인/일상',
                'ru' => 'Персональный / Повседневный',
            ],
        ],
        'collectivites' => [
            'label' => 'Collectivités / Soins',
            'scope' => 'product',
            'order' => 4,
            'translations' => [
                'fr' => 'Collectivités / Soins',
                'en' => 'Communities / Care',
                'de' => 'Gemeinschaften / Pflege',
                'it' => 'Collettività / Cura',
                'es' => 'Colectividades / Cuidados',
                'pt' => 'Coletividades / Cuidados',
                'nl' => 'Gemeenschappen / Zorg',
                'pl' => 'Społeczności / Opieka',
                'jp' => '団体/ケア',
                'zh' => '集体/护理',
                'kr' => '단체/케어',
                'ru' => 'Сообщества / Забота',
            ],
        ],
        'guides' => [
            'label' => 'Guides & ressources',
            'scope' => 'guide',
            'order' => 5,
            'translations' => [
                'fr' => 'Guides & ressources',
                'en' => 'Guides & resources',
                'de' => 'Leitfäden & Ressourcen',
                'it' => 'Guide e risorse',
                'es' => 'Guías y recursos',
                'pt' => 'Guias e recursos',
                'nl' => 'Gidsen en resources',
                'pl' => 'Przewodniki i zasoby',
                'jp' => 'ガイド＆リソース',
                'zh' => '指南与资源',
                'kr' => '가이드 및 리소스',
                'ru' => 'Гайды и ресурсы',
            ],
        ],
    ];

    return array_map(
        fn($slug, $meta) => [
            'slug' => $slug,
            'label' => $meta['label'],
            'scope' => $meta['scope'],
            'order' => $meta['order'],
            'translations' => $meta['translations'],
        ],
        array_keys($map),
        $map
    );
}

function synchronize_category_slugs(PDO $pdo): void
{
    $map = [
        'industriel' => ['Industriel', 'Exosquelette industriel'],
        'medical' => ['Médical', 'Exosquelette médical'],
        'particulier' => ['Particulier', 'Particulier / Quotidien', 'Exosquelette particulier'],
        'collectivites' => ['Collectivités / Soins'],
        'guides' => ['Guide', 'Guides & ressources'],
    ];

    foreach ($map as $slug => $labels) {
        $placeholders = implode(',', array_fill(0, count($labels), '?'));
        $productStmt = $pdo->prepare(
            "UPDATE products SET category_slug = ? WHERE (category_slug IS NULL OR category_slug = '') AND (tag IN ($placeholders) OR category IN ($placeholders))"
        );
        $params = array_merge([$slug], $labels, $labels);
        $productStmt->execute($params);

        $guideStmt = $pdo->prepare(
            "UPDATE guides SET category_slug = ? WHERE (category_slug IS NULL OR category_slug = '') AND category IN ($placeholders)"
        );
        $guideStmt->execute(array_merge([$slug], $labels));
    }
}

function add_column_if_missing(PDO $pdo, string $table, string $column, string $definition): void
{
    if (column_exists($pdo, $table, $column)) {
        return;
    }

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    } else {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $stmt = $pdo->prepare("PRAGMA table_info({$table})");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        foreach ($columns as $col) {
            if (strcasecmp($col['name'], $column) === 0) {
                return true;
            }
        }
        return false;
    }

    $stmt = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}

function get_active_categories(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT slug, default_label, translations, scope, sort_order FROM categories WHERE is_active = 1 ORDER BY sort_order, default_label');
    $rows = $stmt->fetchAll();

    return array_map('normalize_category_row', $rows);
}

function get_all_categories(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, slug, default_label, translations, scope, sort_order, is_active FROM categories ORDER BY sort_order, default_label');
    $rows = $stmt->fetchAll();

    return array_map('normalize_category_row', $rows);
}

function find_category_by_slug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ? normalize_category_row($row) : null;
}

function normalize_category_row(array $row): array
{
    $translations = [];
    if (!empty($row['translations'])) {
        $decoded = json_decode($row['translations'], true);
        if (is_array($decoded)) {
            $translations = $decoded;
        }
    }

    if (!empty($row['default_label']) && empty($translations['fr'])) {
        $translations['fr'] = $row['default_label'];
    }

    return [
        'id' => $row['id'] ?? null,
        'slug' => $row['slug'],
        'default_label' => $row['default_label'],
        'translations' => $translations,
        'scope' => $row['scope'] ?? 'product',
        'sort_order' => (int)($row['sort_order'] ?? 0),
        'is_active' => (int)($row['is_active'] ?? 1),
    ];
}

function category_label_for_lang(array $category, string $lang = 'fr'): string
{
    return $category['translations'][$lang] ?? $category['translations']['fr'] ?? $category['default_label'];
}

function category_options_with_all(array $categories): array
{
    $allTranslations = [
        'fr' => 'Toutes les catégories',
        'en' => 'All categories',
        'de' => 'Alle Kategorien',
        'it' => 'Tutte le categorie',
        'es' => 'Todas las categorías',
        'pt' => 'Todas as categorias',
        'nl' => 'Alle categorieën',
        'pl' => 'Wszystkie kategorie',
        'jp' => 'すべてのカテゴリ',
        'zh' => '所有类别',
        'kr' => '모든 카테고리',
        'ru' => 'Все категории',
    ];

    $options = [[
        'value' => '',
        'label' => $allTranslations['fr'],
        'translations' => $allTranslations,
        'scope' => 'all',
    ]];

    foreach ($categories as $category) {
        $options[] = [
            'value' => $category['slug'],
            'label' => category_label_for_lang($category, 'fr'),
            'translations' => $category['translations'],
            'scope' => $category['scope'],
        ];
    }

    return $options;
}

function filter_categories_by_scope(array $categories, array $allowedScopes): array
{
    return array_values(array_filter($categories, fn($c) => in_array($c['scope'], $allowedScopes, true)));
}

function render_category_options(array $options, string $selected = ''): void
{
    foreach ($options as $option) {
        $value = htmlspecialchars((string)$option['value'], ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars((string)$option['label'], ENT_QUOTES, 'UTF-8');
        $scope = htmlspecialchars((string)$option['scope'], ENT_QUOTES, 'UTF-8');
        $translationsJson = htmlspecialchars(json_encode($option['translations'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        $defaultLabel = htmlspecialchars((string)($option['translations']['fr'] ?? $option['label']), ENT_QUOTES, 'UTF-8');
        $isSelected = ($selected === (string)$option['value']) ? ' selected' : '';

        echo "<option value=\"{$value}\" data-category-translations=\"{$translationsJson}\" data-category-scope=\"{$scope}\" data-default-label=\"{$defaultLabel}\"{$isSelected}>{$label}</option>\n";
    }
}

