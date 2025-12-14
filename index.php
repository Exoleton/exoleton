<?php
require __DIR__ . '/auth.php';

/**
 * IMPORTANT
 * - Ce fichier est appelé par router.php (ex: /fr/ -> router.php -> index.php)
 * - Donc $lang doit venir du routeur. Fallback fr si accès direct.
 */

/* =========================
   FIX 1) Compat PHP 7 + helpers
   ========================= */
if (!function_exists('str_contains')) {
  function str_contains($haystack, $needle) {
    return $needle !== '' && strpos($haystack, $needle) !== false;
  }
}
if (!function_exists('mb_strtolower')) {
  function mb_strtolower($s) { return strtolower($s); }
}

/**
 * FIX 2) Normalisation langue + alias jp/kr -> ja/ko (cohérent avec htaccess)
 * Ton .htaccess route ja/ko, donc on accepte aussi jp/kr si jamais.
 */
const SUPPORTED_LANGS = ['fr','en','de','it','es','pt','nl','pl','ja','zh','ko','ru'];
function normalize_lang($lang) {
  $lang = is_string($lang) ? strtolower(trim($lang)) : 'fr';
  if ($lang === 'jp') $lang = 'ja';
  if ($lang === 'kr') $lang = 'ko';
  return $lang;
}

// $lang doit venir du router. fallback GET si accès direct, sinon fr.
$lang = $lang ?? ($_GET['lang'] ?? 'fr');
$lang = normalize_lang($lang);
if (!in_array($lang, SUPPORTED_LANGS, true)) $lang = 'fr';

$base = '/' . $lang;

/**
 * FIX 3) Forcer les URLs d'images en absolu (/uploads/..., /assets/...)
 * évite le bug: /fr/uploads/... et /fr/assets/... => 404
 */
function abs_url(?string $url): ?string {
  if (!$url) return null;
  $url = trim($url);
  if ($url === '') return null;

  // http(s):// ou //cdn...
  if (preg_match('~^(https?:)?//~i', $url)) return $url;

  // déjà absolu
  if ($url[0] === '/') return $url;

  // relatif => on force racine
  return '/' . $url;
}

function price_html($p, $cur = 'EUR')
{
  if ($p === null) return 'Sur demande';
  $p = (float)$p;
  if ($p <= 0) return 'Sur demande';
  return number_format($p, 0, ',', ' ') . ' ' . ($cur === 'EUR' ? '€' : $cur);
}

/** PRODUITS */
$sqlProducts = "
SELECT
  p.id,
  pi.slug,
  pi.title AS name,
  ci.name AS category,
  SUBSTRING(REPLACE(REPLACE(pi.description, '\r',' '), '\n',' '), 1, 160) AS summary,
  pv.price,
  'EUR' AS currency,
  pm.url AS main_image,

  t.value_select AS type,

  w.value_decimal AS weight,
  a.value_decimal AS autonomy,
  ch.value_decimal AS charge
FROM products p
JOIN products_i18n pi ON pi.product_id = p.id AND pi.lang = :lang
JOIN categories_i18n ci ON ci.category_id = p.category_id AND ci.lang = :lang

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

LEFT JOIN attributes at ON at.code='assistance_type'
LEFT JOIN product_attribute_values t ON t.product_id=p.id AND t.attribute_id=at.id

LEFT JOIN attributes aw ON aw.code='weight_kg'
LEFT JOIN product_attribute_values w ON w.product_id=p.id AND w.attribute_id=aw.id

LEFT JOIN attributes aa ON aa.code='autonomy_h'
LEFT JOIN product_attribute_values a ON a.product_id=p.id AND a.attribute_id=aa.id

LEFT JOIN attributes ach ON ach.code='max_user_weight_kg'
LEFT JOIN product_attribute_values ch ON ch.product_id=p.id AND ch.attribute_id=ach.id

WHERE p.is_active = 1
ORDER BY p.id DESC
";
$stmt = $pdo->prepare($sqlProducts);
$stmt->execute([':lang' => $lang]);
$products = $stmt->fetchAll();

/* =========================
   FIX 4) Normaliser les URLs d’images produits
   ========================= */
foreach ($products as &$p) {
  $p['main_image'] = abs_url($p['main_image'] ?? null);
}
unset($p);

/** GUIDES (pas encore i18n, OK pour l’instant) */
$guidesStmt = $pdo->query("SELECT title, summary, image FROM guides ORDER BY published_at DESC, id DESC LIMIT 3");
$guides = $guidesStmt->fetchAll();

/* =========================
   FIX 5) Normaliser les URLs d’images guides
   ========================= */
foreach ($guides as &$g) {
  $g['image'] = abs_url($g['image'] ?? null);
}
unset($g);

/** MISE EN AVANT (featured_items) */
$sqlFeatured = "
SELECT fi.priority, pi.slug, pi.title AS product_name
FROM featured_items fi
JOIN products p ON p.id = fi.product_id
JOIN products_i18n pi ON pi.product_id = p.id AND pi.lang = :lang
WHERE fi.is_active = 1
  AND fi.start_at <= NOW()
  AND fi.end_at >= NOW()
ORDER BY fi.priority DESC, fi.start_at DESC, fi.id DESC
LIMIT 3
";
$stmt = $pdo->prepare($sqlFeatured);
$stmt->execute([':lang' => $lang]);
$announcements = $stmt->fetchAll();

$currentUser = current_user($pdo);

$navCategoryOptions = [
  '' => 'All categories',
  'industriels_professionnels' => 'Industrial',
  'medical' => 'Medical',
  'personnel_sport' => 'Personal / Sport',
  'collectivites' => 'Communities',
  'guides' => 'Guides & resources',
];

function category_badge(string $category): string {
  $c = mb_strtolower($category);
  if (str_contains($c, 'industri')) return 'success';
  if (str_contains($c, 'médical') || str_contains($c, 'medical')) return 'info';
  if (str_contains($c, 'collectiv')) return 'primary';
  if (str_contains($c, 'personnel') || str_contains($c, 'sport')) return 'secondary';
  return 'secondary';
}

// Canonical SEO (home)
$canonical = 'https://exoleton.com' . $base . '/';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <title data-i18n="meta.title">Exoleton – Site d’exosquelettes et technologies d’assistance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Découvrez, comparez et accédez aux meilleures solutions d’exosquelettes et technologies d’assistance pour professionnels, collectivités et particuliers." data-i18n-description="meta.description">

  <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">

  <!-- FIX 6) Exposer la langue au JS (utile pour i18n.js) -->
  <meta name="x-lang" content="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="x-base" content="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>">

  <!-- Favicons (ABSOLU) -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico?v=1">
  <link rel="shortcut icon" href="/favicon.ico?v=1">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/ico.png">
  <link rel="icon" type="image/png" sizes="192x192" href="/assets/img/ico.png">
  <link rel="apple-touch-icon" href="/assets/img/ico.png">

  <!-- Open Graph (ABSOLU conseillé) -->
  <meta property="og:title" content="Exoleton – La mobilité augmentée, accessible à tous" data-i18n-property="og:title:meta.ogTitle">
  <meta property="og:description" content="Site de référence pour exosquelettes et assistances physiques." data-i18n-property="og:description:meta.ogDescription">
  <meta property="og:image" content="https://exoleton.com/assets/img/hero-exosquelette.jpg">
  <meta property="og:type" content="website">

  <!-- Bootstrap 5 (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Custom CSS (ABSOLU) -->
  <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>

  <!-- HEADER / NAV -->
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
            <li class="nav-item"><a class="nav-link" href="#guides" data-i18n="nav.guides">Guides</a></li>
          </ul>

          <form class="nav-search flex-grow-1 my-3 my-lg-0" method="get" action="<?= $base ?>/recherche" role="search">
            <div class="nav-search-bar" role="group" aria-label="Search">
              <div class="nav-search-select-wrap">
                <label class="visually-hidden" for="navSearchCategory">Category</label>
                <select id="navSearchCategory" name="cat" class="form-select nav-search-select">
                  <?php foreach ($navCategoryOptions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
                <span class="nav-search-caret" aria-hidden="true">▾</span>
              </div>
              <div class="nav-search-input">
                <label class="visually-hidden" for="navSearchQuery">Search</label>
                <input id="navSearchQuery" name="q" type="search" class="form-control" placeholder="Industrial exoskeleton, walking aid…" data-i18n-placeholder="search.placeholder">
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
              <label class="visually-hidden" for="languageSwitcher" data-i18n="lang.label">Langue</label>
              <select id="languageSwitcher" class="form-select form-select-sm" data-language-switcher></select>
            </li>

            <?php if ($currentUser): ?>
              <li class="nav-item dropdown ms-lg-3">
                <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Bonjour <?= htmlspecialchars($currentUser['name']); ?>
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

  <!-- HERO -->
  <section class="hero position-relative text-white">
    <img class="hero-bg" src="/assets/img/hero-exosquelette.png" alt="Exosquelette en action">
    <div class="hero-overlay"></div>
    <div class="container position-relative py-5">
      <div class="row align-items-center" style="min-height: 50vh;">
        <div class="col-lg-7">
          <h1 class="display-5 fw-bold mb-3" data-i18n="hero.title">La mobilité augmentée, accessible à tous.</h1>
          <p class="lead mb-4" data-i18n="hero.lead">
            Exoleton est le site de référence pour les exosquelettes et technologies d’assistance.
            Découvrez, comparez et accédez aux solutions adaptées à vos besoins professionnels et personnels.
          </p>
          <div class="d-flex gap-3">
            <a href="#produits" class="btn btn-primary btn-lg" data-i18n="hero.primary">Explorer les produits</a>
            <a href="#guides" class="btn btn-outline-light btn-lg" data-i18n="hero.secondary">Lire les guides</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- AVANTAGES -->
  <section class="py-5 bg-light border-top">
    <div class="container">
      <div class="row text-center g-4">
        <div class="col-md-4">
          <div class="icon-badge mx-auto mb-3">✓</div>
          <h3 class="h5" data-i18n="advantages.title1">Large choix de modèles</h3>
          <p class="text-muted mb-0" data-i18n="advantages.desc1">Une sélection couvrant les usages industriels, médicaux et du quotidien.</p>
        </div>
        <div class="col-md-4">
          <div class="icon-badge mx-auto mb-3">★</div>
          <h3 class="h5" data-i18n="advantages.title2">Partenaires de confiance</h3>
          <p class="text-muted mb-0" data-i18n="advantages.desc2">Fabricants reconnus, produits certifiés et processus d’achat encadré.</p>
        </div>
        <div class="col-md-4">
          <div class="icon-badge mx-auto mb-3">ℹ︎</div>
          <h3 class="h5" data-i18n="advantages.title3">Conseils d’experts</h3>
          <p class="text-muted mb-0" data-i18n="advantages.desc3">Guides, cas d’usage et comparatifs pour choisir en toute sérénité.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- PRODUITS MIS EN AVANT -->
  <section id="produits" class="py-5">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="h3 mb-0" data-i18n="selection.title">Sélection du moment</h2>
        <a href="#comparateur" class="link-primary" data-i18n="selection.link">Comparer les modèles →</a>
      </div>

      <?php if (!empty($announcements)): ?>
        <div class="row g-3 mb-3">
          <?php foreach ($announcements as $announcement): ?>
            <div class="col-md-4">
              <div class="alert alert-primary h-100 shadow-sm mb-0">
                <div class="d-flex align-items-start justify-content-between">
                  <div>
                    <h3 class="h6 mb-1">
                      <?= htmlspecialchars($announcement['product_name'] ?? 'Produit mis en avant', ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    <p class="mb-2 small text-muted">Produit mis en avant</p>
                  </div>
                  <span class="badge bg-primary-subtle text-primary">Mise en avant</span>
                </div>

                <?php if (!empty($announcement['slug'])): ?>
                  <a class="btn btn-sm btn-outline-primary"
                     href="<?= $base ?>/produit/<?= urlencode($announcement['slug']) ?>">
                    Voir le produit
                  </a>
                <?php else: ?>
                  <span class="text-muted small">Lien indisponible</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="row g-4">
        <?php foreach ($products as $product): ?>
          <div class="col-md-4">
            <article class="card product-card h-100">
              <?php if (!empty($product['main_image'])): ?>
                <img src="<?= htmlspecialchars($product['main_image'], ENT_QUOTES, 'UTF-8') ?>"
                     class="card-img-top"
                     alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
              <?php endif; ?>
              <div class="card-body">
                <span class="badge bg-<?= category_badge($product['category'] ?? '') ?> mb-2">
                  <?= htmlspecialchars($product['category'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                </span>

                <h3 class="h5 card-title mb-1"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="text-muted small mb-3"><?= htmlspecialchars($product['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="d-flex align-items-center justify-content-between">
                  <strong class="price"><?= price_html((int)$product['price'], $product['currency']) ?></strong>
                  <a href="<?= $base ?>/produit/<?= urlencode($product['slug']) ?>"
                     class="btn btn-outline-primary btn-sm" data-i18n="selection.details">Voir les détails</a>
                </div>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>

    </div>
  </section>

  <!-- COMPARATEUR (apercu) -->
  <section id="comparateur" class="py-5 bg-light border-top">
    <div class="container">
      <div class="row align-items-center g-4">
        <div class="col-lg-6">
          <h2 class="h3 mb-3" data-i18n="comparator.title">Comparer en un coup d’œil</h2>
          <p class="text-muted" data-i18n="comparator.desc">Poids, autonomie, type d’assistance, charge supportée, certifications… Notre comparateur vous aide à sélectionner le bon modèle pour votre activité.</p>
          <a class="btn btn-primary" href="#cta" data-i18n="comparator.cta">Accéder au comparateur</a>
        </div>
        <div class="col-lg-6">
          <div class="table-responsive rounded-3 shadow-sm bg-white">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th data-i18n="comparator.table.model">Modèle</th>
                  <th data-i18n="comparator.table.type">Type</th>
                  <th data-i18n="comparator.table.weight">Poids</th>
                  <th data-i18n="comparator.table.autonomy">Autonomie</th>
                  <th data-i18n="comparator.table.charge">Charge</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($products as $product): ?>
                  <tr>
                    <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($product['type'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($product['weight'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($product['autonomy'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($product['charge'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <small class="text-muted d-block mt-2" data-i18n="comparator.note">*Données indicatives, variables selon configuration.</small>
        </div>
      </div>
    </div>
  </section>

  <!-- GUIDES -->
  <section id="guides" class="py-5">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="h3 mb-0" data-i18n="guides.title">Guides & cas d’usage</h2>
        <a href="#" class="link-primary" data-i18n="guides.link">Voir tous les articles →</a>
      </div>
      <div class="row g-4">
        <?php foreach ($guides as $guide): ?>
          <div class="col-md-4">
            <article class="card h-100 shadow-sm">
              <?php if (!empty($guide['image'])): ?>
                <img src="<?= htmlspecialchars($guide['image'], ENT_QUOTES, 'UTF-8') ?>"
                     class="card-img-top"
                     alt="<?= htmlspecialchars($guide['title'], ENT_QUOTES, 'UTF-8') ?>">
              <?php endif; ?>
              <div class="card-body">
                <h3 class="h5"><?= htmlspecialchars($guide['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="text-muted"><?= htmlspecialchars($guide['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                <a class="stretched-link" href="#"></a>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section id="cta" class="py-5 bg-primary text-white">
    <div class="container">
      <div class="row align-items-center g-3">
        <div class="col-lg-8">
          <h2 class="h4 mb-1" data-i18n="cta.title">Un besoin précis ? Parlons-en.</h2>
          <p class="mb-0 opacity-75" data-i18n="cta.desc">Nos équipes vous orientent vers les bons modèles et vous accompagnent dans votre projet.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="#contact" class="btn btn-outline-light btn-lg" data-i18n="cta.button">Être recontacté</a>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
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
            <li><a class="footer-link" href="#guides" data-i18n="nav.guides">Guides</a></li>
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
            <li>
              <button type="button" class="footer-link btn btn-link p-0 text-start" data-bs-toggle="modal" data-bs-target="#cookieSettingsModal">
                <span data-i18n="footer.cookies">Gérer les cookies</span>
              </button>
            </li>
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

  <!-- Cookie / Modal (inchangé) -->
  <!-- ... garde ton code existant ici ... -->

  <!-- Scripts (ABSOLU) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/i18n.js?v=3"></script>
</body>
</html>
