<?php
// Connexion PDO partagée avec repli SQLite lorsque MySQL n'est pas disponible
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'exoleton';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'KQchRF5NEjd7';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Tentative MySQL
$pdo = null;
$mysqlDsn = getenv('DB_DSN') ?: "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($mysqlDsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Repli SQLite local (permet de faire tourner le site sans serveur MySQL)
    $storageDir = __DIR__ . '/storage';
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0775, true);
    }

    $sqlitePath = $storageDir . '/exoleton.sqlite';
    $pdo = new PDO('sqlite:' . $sqlitePath, null, null, $options);
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Création du schéma et des données minimales si nécessaire
    initialize_sqlite_if_needed($pdo);
}

/**
 * Création du schéma/données SQLite minimal pour faire fonctionner le site vitrine.
 */
function initialize_sqlite_if_needed(PDO $pdo): void
{
    // Vérifie si la table products existe déjà
    $exists = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'products'")->fetchColumn();
    if ($exists) {
        return;
    }

    // Tables principales
    $pdo->exec('CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        category TEXT NOT NULL,
        tag TEXT NOT NULL,
        price INTEGER NULL,
        currency TEXT NOT NULL DEFAULT "EUR",
        summary TEXT,
        baseline TEXT,
        brand TEXT DEFAULT "Exoleton",
        availability TEXT DEFAULT "https://schema.org/InStock",
        type TEXT,
        weight TEXT,
        autonomy TEXT,
        charge TEXT,
        main_image TEXT,
        hero_image TEXT,
        bullets TEXT,
        tags TEXT,
        featured_order INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        category_slug TEXT
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS guides (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        summary TEXT,
        image TEXT,
        category TEXT DEFAULT "Guide",
        tags TEXT,
        published_at TEXT DEFAULT CURRENT_TIMESTAMP,
        category_slug TEXT
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS featured_announcements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        message TEXT,
        link_url TEXT,
        product_id INTEGER,
        priority INTEGER DEFAULT 0,
        start_at TEXT,
        end_at TEXT,
        is_active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS product_images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        url TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0,
        FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS product_downloads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        label TEXT NOT NULL,
        href TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0,
        FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS product_use_cases (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        kpi TEXT NOT NULL,
        description TEXT,
        sort_order INTEGER DEFAULT 0,
        FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS product_specs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        label TEXT NOT NULL,
        value TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0,
        FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS product_alternatives (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        alt_name TEXT NOT NULL,
        alt_slug TEXT,
        tag TEXT NOT NULL,
        summary TEXT NOT NULL,
        price INTEGER,
        image TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0,
        FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT NOT NULL UNIQUE,
        default_label TEXT NOT NULL,
        translations TEXT,
        scope TEXT NOT NULL DEFAULT "product",
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT DEFAULT "customer",
        reset_token TEXT,
        reset_expires TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    // Données d'exemple (produits)
    $pdo->exec("INSERT INTO products (id, slug, name, category, tag, price, currency, summary, baseline, brand, availability, type, weight, autonomy, charge, main_image, hero_image, bullets, tags, featured_order, created_at, category_slug) VALUES
        (1, 'exolift', 'ExoLift', 'Exosquelette industriel', 'Industriel', 4500, 'EUR', 'Assistance au levage jusqu’à 30 kg · Batterie échangeable', 'Réduit la contrainte lombaire et améliore la cadence sans fatigue.', 'Exoleton', 'https://schema.org/InStock', 'Actif', '7,8 kg', '6 h', '30 kg', 'assets/img/produit-1.jpg', 'assets/img/produits/exolift-1.jpg', '30 kg assistés|7,8 kg|Autonomie 6 h', 'levage, industriel, batterie 6 h', 1, CURRENT_TIMESTAMP, NULL),
        (2, 'atalante-x', 'Atalante X', 'Exosquelette médical', 'Médical', NULL, 'EUR', 'Rééducation de la marche · Usage en établissement', 'Solution de marche assistée pour centres de rééducation.', 'Exoleton', 'https://schema.org/InStock', 'Actif', '≈30 kg', '—', '—', 'assets/img/produit-2.jpg', 'assets/img/produits/atalante.jpg', 'Rééducation de la marche|Usage en établissement|Support clinique', 'rééducation, marche, usage clinique', 2, CURRENT_TIMESTAMP, NULL),
        (3, 'assistarm', 'AssistArm', 'Exosquelette particulier', 'Particulier', 2800, 'EUR', 'Soulagement des efforts répétés · Ultra-léger', 'Soulage les efforts répétés du haut du corps pour le quotidien.', 'Exoleton', 'https://schema.org/InStock', 'Passif', '2,1 kg', '∞', '—', 'assets/img/produit-3.jpg', 'assets/img/produits/assistarm.jpg', 'Passif, ultra-léger|Soulage les efforts répétés|Idéal pour le quotidien', 'quotidien, léger, passif', 3, CURRENT_TIMESTAMP, NULL)");

    // Guides
    $pdo->exec("INSERT INTO guides (id, title, summary, image, category, tags, published_at, category_slug) VALUES
        (1, 'Choisir un exosquelette pour la logistique', 'Critères essentiels, ROI, prévention des TMS, sécurité et formation.', 'assets/img/hero-exosquelette.jpg', 'Guide', 'logistique, industriel', CURRENT_TIMESTAMP, NULL),
        (2, 'Aide à la marche : quelles solutions ?', 'Panorama des dispositifs disponibles et indications d’usage.', 'assets/img/hero-exosquelette.jpg', 'Guide', 'rééducation, marche', CURRENT_TIMESTAMP, NULL),
        (3, 'Financements & subventions', 'Pistes pour entreprises, hôpitaux, collectivités et particuliers.', 'assets/img/hero-exosquelette.jpg', 'Guide', 'budget, aides', CURRENT_TIMESTAMP, NULL),
        (4, 'Guide pratique : financer son exosquelette', 'Panorama des aides, subventions et démarches pour obtenir un financement.', 'assets/img/hero-exosquelette.jpg', 'Guide', 'financement, budget', CURRENT_TIMESTAMP, NULL)");

    // Annonces
    $pdo->exec("INSERT INTO featured_announcements (title, message, link_url, product_id, priority, start_at, end_at, is_active) VALUES
        ('Nouveau : démonstrations ExoLift', 'Planifiez une démo sur site avec nos ergonomes partenaires.', 'detail.php?slug=exolift', 1, 1, NULL, NULL, 1)");

    // Médias produits
    $pdo->exec("INSERT INTO product_images (product_id, url, sort_order) VALUES
        (1, 'assets/img/produits/exolift-1.jpg', 1),
        (1, 'assets/img/produits/exolift-2.jpg', 2),
        (1, 'assets/img/produits/exolift-3.jpg', 3),
        (2, 'assets/img/produits/atalante.jpg', 1),
        (3, 'assets/img/produits/assistarm.jpg', 1)");

    $pdo->exec("INSERT INTO product_downloads (product_id, label, href, sort_order) VALUES
        (1, 'Fiche produit (PDF)', 'assets/docs/exolift-fiche.pdf', 1),
        (1, 'Manuel d’utilisation (PDF)', 'assets/docs/exolift-manuel.pdf', 2),
        (1, 'Fiche sécurité (PDF)', 'assets/docs/exolift-securite.pdf', 3)");

    $pdo->exec("INSERT INTO product_use_cases (product_id, title, kpi, description, sort_order) VALUES
        (1, 'Logistique', '–28% TMS dos', 'Aide au soulèvement et à la manutention répétée.', 1),
        (1, 'Industrie', '+18% cadence', 'Maintien de la performance en fin de poste.', 2),
        (1, 'BTP', '–35% fatigue perçue', 'Postures contraignantes mieux supportées.', 3)");

    $pdo->exec("INSERT INTO product_specs (product_id, label, value, sort_order) VALUES
        (1, 'Type', 'Actif (électrique)', 1),
        (1, 'Zones assistées', 'Dos / Membres supérieurs', 2),
        (1, 'Charge assistée', 'Jusqu’à 30 kg', 3),
        (1, 'Poids', '7,8 kg', 4),
        (1, 'Autonomie', '≈ 6 h (batterie échangeable)', 5),
        (1, 'Niveaux d’assistance', '3', 6),
        (1, 'Taille opérateur', '160–195 cm (S–L)', 7),
        (1, 'Niveau sonore', '≤ 45 dB', 8),
        (1, 'Indice de protection', 'IP54', 9),
        (1, 'Conformité', 'CE, Directive Machines', 10),
        (1, 'Entretien', 'Module batterie remplaçable, harnais lavable', 11),
        (1, 'Garantie', '24 mois', 12)");

    $pdo->exec("INSERT INTO product_alternatives (product_id, alt_name, alt_slug, tag, summary, price, image, sort_order) VALUES
        (1, 'AssistArm', 'assistarm', 'Particulier', 'Passif, ultra-léger', 2800, 'assets/img/produits/assistarm.jpg', 1),
        (1, 'Atalante X', 'atalante-x', 'Médical', 'Rééducation marche', NULL, 'assets/img/produits/atalante.jpg', 2)");

    // Utilisateurs (mot de passe déjà hashé)
    $userSeed = <<<'SQL'
INSERT INTO users (id, name, email, password_hash, role, created_at) VALUES
        (1, 'franck BODO', 'contact@exoleton.com', '$argon2id$v=19$m=65536,t=4,p=1$V2ZEWXJ2bE5ZWS8wVjUvTg$zHD7pXnaFtyGg9hYiluZwhkHguJlhBGQW02lGr1Ctz0', 'admin', CURRENT_TIMESTAMP),
        (2, 'Toto', 'lapinkrr@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$QXZhNWNhSGNHQS9WUndpbQ$uvqsGmz+1ZQIHvxadxIgA5FWd6Ao1uKaPT2SMeww2Ps', 'customer', CURRENT_TIMESTAMP);
SQL;
    $pdo->exec($userSeed);
}
