<?php
require __DIR__ . '/auth.php';

/* =========================
   Helpers compat + langue
   ========================= */
if (!function_exists('str_contains')) {
  function str_contains($haystack, $needle) {
    return $needle !== '' && strpos($haystack, $needle) !== false;
  }
}
if (!function_exists('mb_strtolower')) {
  function mb_strtolower($s) { return strtolower($s); }
}

const SUPPORTED_LANGS = ['fr','en','de','it','es','pt','nl','pl','ja','zh','ko','ru'];

function normalize_lang($lang) {
  $lang = is_string($lang) ? strtolower(trim($lang)) : 'fr';
  if ($lang === 'jp') $lang = 'ja';
  if ($lang === 'kr') $lang = 'ko';
  return $lang;
}

/**
 * IMPORTANT:
 * - Si tu passes par router.php: $lang existe déjà.
 * - Si accès direct: fallback GET/lang ou 'fr'
 */
$lang = $lang ?? ($_GET['lang'] ?? 'fr');
$lang = normalize_lang($lang);
if (!in_array($lang, SUPPORTED_LANGS, true)) $lang = 'fr';

$base = '/' . $lang;

function abs_url(?string $url): ?string {
  if (!$url) return null;
  $url = trim($url);
  if ($url === '') return null;
  if (preg_match('~^(https?:)?//~i', $url)) return $url;
  if ($url[0] === '/') return $url;
  return '/' . $url;
}

function price_html($p, $cur = 'EUR')
{
  if ($p === null) return 'Sur demande';
  $p = (float)$p;
  if ($p <= 0) return 'Sur demande';
  return number_format($p, 0, ',', ' ') . ' ' . ($cur === 'EUR' ? '€' : $cur);
}

function category_badge(string $categoryName): string {
  $c = mb_strtolower($categoryName);
  if (str_contains($c, 'industri')) return 'success';
  if (str_contains($c, 'médical') || str_contains($c, 'medical')) return 'info';
  if (str_contains($c, 'collectiv') || str_contains($c, 'commun')) return 'primary';
  if (str_contains($c, 'personnel') || str_contains($c, 'sport') || str_contains($c, 'personal')) return 'secondary';
  return 'secondary';
}

/* =========================
   Inputs
   ========================= */
$query    = isset($_GET['q'])   ? trim((string)$_GET['q'])   : '';
$catCode  = isset($_GET['cat']) ? trim((string)$_GET['cat']) : '';

$sanitizedQuery = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
$like = '%' . $query . '%';

$isGuidesOnly = ($catCode === 'guides');

/* =========================
   Catégories (DB) + option guides
   categories: id, code, is_active, sort_order...
   categories_i18n: id, category_id, lang, name
   ========================= */
$catStmt = $pdo->prepare("
  SELECT c.code, ci.name
  FROM categories c
  JOIN categories_i18n ci ON ci.category_id = c.id AND ci.lang = :lang
  WHERE c.is_active = 1
  ORDER BY COALESCE(c.sort_order, 999999) ASC, ci.name ASC
");
$catStmt->execute([':lang' => $lang]);
$navCategories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Produits (DB actuelle)
   ========================= */
$products = [];
if (!$isGuidesOnly) {
  $sql = "
  SELECT
    p.id,
    pi.slug,
    pi.title AS name,
    ci.name AS category_name,
    SUBSTRING(REPLACE(REPLACE(pi.description, '\r',' '), '\n',' '), 1, 180) AS summary,
    pv.price,
    'EUR' AS currency,
    pm.url AS main_image
  FROM products p
  JOIN products_i18n pi ON pi.product_id = p.id AND pi.lang = :lang
  JOIN categories c ON c.id = p.category_id
  JOIN categories_i18n ci ON ci.category_id = c.id AND ci.lang = :lang

  LEFT JOIN (
    SELECT product_id, MIN(price) AS price
    FROM product_variants
    WHERE is_active = 1
    GROUP BY product_id
  ) pv ON pv.product_id = p.id

  LEFT JOIN (
    SELECT m1.product_id, m1.url
    FROM media m1
    JOIN (
      SELECT product_id, MIN(sort_order) AS min_sort
      FROM media
      WHERE type='image'
      GROUP BY product_id
    ) mm ON mm.product_id = m1.product_id AND mm.min_sort = m1.sort_order
    WHERE m1.type='image'
  ) pm ON pm.product_id = p.id

  WHERE p.is_active = 1
  ";

  $params = [':lang' => $lang];

  if ($query !== '') {
    $sql .= " AND (pi.title LIKE :q OR pi.description LIKE :q)";
    $params[':q'] = $like;
  }

  if ($catCode !== '' && $catCode !== 'guides') {
    $sql .= " AND c.code = :cat";
    $params[':cat'] = $catCode;
  }

  $sql .= " ORDER BY pi.title ASC";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($products as &$p) {
    $p['main_image'] = abs_url($p['main_image'] ?? null);
  }
  unset($p);
}

/* =========================
   Guides (pas i18n pour l’instant)
   ========================= */
$guides = [];
$guideSql = "SELECT title, summary, image, category, tags FROM guides";
$gWhere = [];
$gParams = [];

if ($query !== '') {
  $gWhere[] = "(title LIKE :q OR summary LIKE :q OR tags LIKE :q)";
  $gParams[':q'] = $like;
}

if ($isGuidesOnly) {
  // pas besoin d'autre filtre
} else {
  // si cat sélectionnée (une vraie catégorie produits), on ne filtre pas les guides
  // tu peux changer ce comportement si tu veux
}

if (!empty($gWhere)) {
  $guideSql .= " WHERE " . implode(" AND ", $gWhere);
}
$guideSql .= " ORDER BY published_at DESC, id DESC";

$gStmt = $pdo->prepare($guideSql);
$gStmt->execute($gParams);
$guides = $gStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($guides as &$g) {
  $g['image'] = abs_url($g['image'] ?? null);
}
unset($g);

/* =========================
   Fusion résultats (produits + guides)
   ========================= */
$results = [];

foreach ($products as $p) {
  $results[] = [
    'type'     => 'product',
    'title'    => $p['name'],
    'category' => $p['category_name'],
    'summary'  => $p['summary'],
    'price'    => $p['price'],
    'currency' => $p['currency'],
    'image'    => $p['main_image'],
    'link'     => $base . '/produit/' . urlencode($p['slug']),
  ];
}

foreach ($guides as $g) {
  $results[] = [
    'type'     => 'guide',
    'title'    => $g['title'],
    'category' => $g['category'] ?: 'Guide',
    'summary'  => $g['summary'],
    'price'    => null,
    'currency' => null,
    'image'    => $g['image'],
    'link'     => '#',
  ];
}

$currentUser = current_user($pdo);

// Canonical (recherche)
$canonical = 'https://exoleton.com' . $base . '/recherche';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <title data-i18n="search.metaTitle">Résultats de recherche – Exoleton</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Résultats de recherche pour les exosquelettes et technologies d’assistance sur Exoleton." data-i18n-description="search.metaDescription">

  <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">

  <!-- Exposer langue/base au JS -->
  <meta name="x-lang" content="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="x-base" content="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>">

  <!-- Favicons -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico?v=1">
  <link rel="shortcut icon" href="/favicon.ico?v=1">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/ico.png">
  <link rel="icon" type="image/png" sizes="192x192" href="/assets/img/ico.png">
  <link rel="apple-touch-icon" href="/assets/img/ico.png">

  <!-- Open Graph -->
  <meta property="og:title" content="Résultats de recherche – Exoleton" data-i18n-property="og:title:search.metaTitle">
  <meta property="og:description" content="Découvrez les produits, guides et ressources correspondant à votre recherche." data-i18n-property="og:description:search.ogDescription">
  <meta property="og:image" content="https://exoleton.com/assets/img/hero-exosquelette.jpg">
  <meta property="og:type" content="website">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>

<header class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="<?= $base ?>/">
      <img src="/assets/img/logo.png" alt="Exoleton" width="272" height="1000" class="me-2">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Basculer la navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <nav id="mainNav" class="collapse navbar-collapse">
      <div class="d-lg-flex align-items-lg-center w-100 gap-3">
        <ul class="navbar-nav align-items-lg-center mb-2 mb-lg-0 me-lg-3">
          <li class="nav-item"><a class="nav-link fw-semibold" href="<?= $base ?>/" data-i18n="nav.home">Accueil</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>/#guides" data-i18n="nav.guides">Guides</a></li>
        </ul>

        <form class="nav-search flex-grow-1 my-3 my-lg-0" method="get" action="<?= $base ?>/recherche" role="search">
          <div class="nav-search-bar" role="group" aria-label="Search">
            <div class="nav-search-select-wrap">
              <label class="visually-hidden" for="navSearchCategory">Category</label>
              <select id="navSearchCategory" name="cat" class="form-select nav-search-select">
                <option value="" <?= $catCode === '' ? 'selected' : '' ?> data-i18n="search.allCategories">All categories</option>

                <?php foreach ($navCategories as $c): ?>
                  <option value="<?= htmlspecialchars($c['code'], ENT_QUOTES, 'UTF-8') ?>" <?= $catCode === $c['code'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>

                <option value="guides" <?= $catCode === 'guides' ? 'selected' : '' ?> data-i18n="search.guides">Guides & resources</option>
              </select>
              <span class="nav-search-caret" aria-hidden="true">▾</span>
            </div>

            <div class="nav-search-input">
              <label class="visually-hidden" for="navSearchQuery">Search</label>
              <input id="navSearchQuery" name="q" type="search" class="form-control"
                     placeholder="Industrial exoskeleton, walking aid…"
                     value="<?= $sanitizedQuery ?>"
                     data-i18n-placeholder="search.placeholder">
            </div>

            <button class="nav-search-btn" type="submit" aria-label="Search">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true" focusable="false">
                <path fill="currentColor" d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.71.71l.27.28v.79l4.25 4.25a1 1 0 0 0 1.42-1.42L15.5 14Zm-6 0a5 5 0 1 1 0-10a5 5 0 0 1 0 10Z"/>
              </svg>
              <span class="visually-hidden" data-i18n="search.cta">Search</span>
            </button>
          </div>
        </form>

        <ul class="navbar-nav ms-lg-auto mb-2 mb-lg-0 align-items-lg-center">
          <li class="nav-item ms-lg-3">
            <a class="nav-link" href="<?= $base ?>/#cta" data-i18n="nav.cta">Découvrir les solutions</a>
          </li>

          <li class="nav-item ms-lg-3">
            <label class="visually-hidden" for="languageSwitcherSearch" data-i18n="lang.label">Langue</label>
            <select id="languageSwitcherSearch" class="form-select form-select-sm" data-language-switcher></select>
          </li>

          <?php if ($currentUser): ?>
            <li class="nav-item dropdown ms-lg-3">
              <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Bonjour <?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                <li><a class="dropdown-item" href="<?= $base ?>/account">Mon compte</a></li>
                <?php if (($currentUser['role'] ?? 'customer') === 'admin'): ?>
                  <li><a class="dropdown-item" href="<?= $base ?>/admin">Administration</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= $base ?>/logout">Se déconnecter</a></li>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item ms-lg-3">
              <a class="btn btn-outline-primary" href="<?= $base ?>/login">Connexion</a>
            </li>
          <?php endif; ?>
        </ul>

      </div>
    </nav>
  </div>
</header>

<main class="search-page">
  <section class="search-hero py-5 bg-light border-bottom">
    <div class="container py-4">
	<div class="row g-4 align-items-center">
  <div class="col-12">
    <p class="text-primary fw-semibold mb-2" data-i18n="search.title">Résultats de recherche</p>
    <h1 class="h2 mb-3">
      <span data-i18n="search.heading">Ce que nous avons trouvé</span>
      <?php if ($sanitizedQuery): ?> pour «&nbsp;<?= $sanitizedQuery; ?>&nbsp;»<?php endif; ?>
    </h1>
    <p class="text-muted mb-0" data-i18n="search.subtitle">
      Affinez votre recherche, parcourez les modèles et guides correspondants ou contactez-nous pour être accompagné.
    </p>
  </div>
</div>

      </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
          <p class="text-muted mb-1 small text-uppercase" data-i18n="search.results">Résultats</p>
          <h2 class="h4 mb-0">
            <?= count($results) ?> élément<?= count($results) > 1 ? 's' : '' ?> trouvé<?= count($results) > 1 ? 's' : '' ?>
          </h2>
        </div>
      </div>

      <?php if (empty($results)): ?>
        <div class="alert alert-info d-flex align-items-center" role="status">
          <div class="flex-shrink-0 me-3"><span class="bi bi-info-circle"></span></div>
          <div data-i18n="search.noResults">
            <strong>Aucun résultat</strong> pour votre recherche. Essayez d’autres mots-clés ou contactez nos équipes pour un accompagnement personnalisé.
          </div>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($results as $item): ?>
            <div class="col-md-6 col-lg-4">
              <article class="card h-100 product-card">
                <?php if (!empty($item['image'])): ?>
                  <img src="<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>"
                       class="card-img-top"
                       alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>

                <div class="card-body d-flex flex-column">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-<?= category_badge($item['category'] ?? '') ?>">
                      <?= htmlspecialchars($item['category'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                    </span>

                    <?php if ($item['price'] !== null): ?>
                      <span class="price fw-semibold">
                        <?= htmlspecialchars(price_html($item['price'], $item['currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                    <?php endif; ?>
                  </div>

                  <h3 class="h5 card-title mb-2"><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                  <p class="text-muted small mb-3 flex-grow-1"><?= htmlspecialchars($item['summary'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>

                  <div class="d-flex align-items-center justify-content-between mt-auto">
                    <a href="<?= htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8'); ?>"
                       class="btn btn-outline-primary btn-sm"
                       data-i18n="selection.details">Voir les détails</a>

                    <a href="<?= $base ?>/#cta" class="btn btn-link text-decoration-none" data-i18n="search.contact">Être recontacté</a>
                  </div>
                </div>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<footer class="pt-5 bg-dark text-white">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="d-flex align-items-center mb-3">
          <img src="/assets/img/logo.png" alt="Exoleton" width="136" height="50" class="me-2">
        </div>
        <p class="text-white-50" data-i18n="footer.mission">Site d’exosquelettes et technologies d’assistance. Notre mission : rendre la mobilité augmentée accessible à tous.</p>
      </div>
      <div class="col-6 col-md-2">
        <h3 class="h6" data-i18n="footer.navigation">Navigation</h3>
        <ul class="list-unstyled">
          <li><a class="footer-link" href="<?= $base ?>/" data-i18n="nav.home">Accueil</a></li>
          <li><a class="footer-link" href="<?= $base ?>/#guides" data-i18n="nav.guides">Guides</a></li>
          <li><a class="footer-link" href="<?= $base ?>/recherche" data-i18n="nav.search">Recherche</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-3">
        <h3 class="h6" data-i18n="footer.resources">Ressources</h3>
        <ul class="list-unstyled">
          <li><a class="footer-link" href="#" data-i18n="footer.faq">FAQ</a></li>
          <li><a class="footer-link" href="#" data-i18n="footer.support">Support</a></li>
          <li><a class="footer-link" href="#" data-i18n="footer.legal">Mentions légales</a></li>
          <li><a class="footer-link" href="#" data-i18n="footer.privacy">Politique de confidentialité</a></li>
        </ul>
      </div>
      <div class="col-md-3">
        <h3 class="h6" data-i18n="footer.newsletter">Newsletter</h3>
        <form class="d-flex gap-2" action="#" method="post" onsubmit="return false;">
          <input type="email" class="form-control" placeholder="Votre email" aria-label="Votre email" data-i18n-placeholder="footer.email">
          <button class="btn btn-success" data-i18n="footer.subscribe">S’inscrire</button>
        </form>
      </div>
    </div>
    <hr class="border-secondary my-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center pb-4">
      <small class="text-white-50" data-i18n="footer.rights" data-i18n-html="true">© <span id="year"></span> Exoleton. Tous droits réservés.</small>
      <div class="d-flex gap-3 mt-3 mt-md-0">
        <a class="footer-link" href="#" aria-label="Twitter">Twitter</a>
        <a class="footer-link" href="#" aria-label="LinkedIn">LinkedIn</a>
        <a class="footer-link" href="#" aria-label="YouTube">YouTube</a>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/main.js"></script>
<script src="/assets/js/i18n.js?v=1"></script>
</body>
</html>
