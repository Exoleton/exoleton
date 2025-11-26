<?php
require __DIR__ . '/auth.php';

function price_html($p, $cur = 'EUR')
{
  if (!$p || $p <= 0) return 'Sur demande';
  return number_format($p, 0, ',', ' ') . ' ' . ($cur === 'EUR' ? '€' : $cur);
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$sanitizedQuery = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
$sanitizedCategory = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
$like = '%' . $query . '%';

$categoryOptions = [
  '' => 'Toutes les catégories',
  'Industriel' => 'Industriel',
  'Médical' => 'Médical',
  'Particulier / Quotidien' => 'Particulier / Quotidien',
  'Collectivités / Soins' => 'Collectivités / Soins',
  'Guides & ressources' => 'Guides & ressources',
];
$navCategoryOptions = $categoryOptions;

$isGuideCategory = in_array($category, ['Guide', 'Guides', 'Guides & ressources'], true);

$products = [];
if (!$isGuideCategory) {
  $productQuery = "SELECT slug, name, tag, category, summary, price, currency, main_image, tags FROM products";
  $productWhere = [];
  $productParams = [];

  if ($query !== '') {
    $productWhere[] = "(name LIKE :q OR summary LIKE :q OR tags LIKE :q)";
    $productParams['q'] = $like;
  }

  if ($category !== '') {
    $productWhere[] = "(tag = :category OR category = :category)";
    $productParams['category'] = $category;
  }

  if (!empty($productWhere)) {
    $productQuery .= ' WHERE ' . implode(' AND ', $productWhere);
  }

  $productQuery .= ' ORDER BY name';
  $prodStmt = $pdo->prepare($productQuery);
  $prodStmt->execute($productParams);
  $products = $prodStmt->fetchAll();
}

$guideQuery = "SELECT title, summary, image, category, tags FROM guides";
$guideWhere = [];
$guideParams = [];

if ($query !== '') {
  $guideWhere[] = "(title LIKE :q OR summary LIKE :q OR tags LIKE :q)";
  $guideParams['q'] = $like;
}

if ($category !== '') {
  $guideWhere[] = "(category = :guideCategory OR :guideCategory IN ('Guide', 'Guides & ressources'))";
  $guideParams['guideCategory'] = $category === 'Guides & ressources' ? 'Guide' : $category;
}

if (!empty($guideWhere)) {
  $guideQuery .= ' WHERE ' . implode(' AND ', $guideWhere);
}

$guideQuery .= ' ORDER BY published_at DESC, id DESC';
$guideStmt = $pdo->prepare($guideQuery);
$guideStmt->execute($guideParams);
$guides = $guideStmt->fetchAll();

$results = [];
foreach ($products as $product) {
  $results[] = [
    'title' => $product['name'],
    'category' => $product['tag'] ?: $product['category'],
    'summary' => $product['summary'],
    'price' => $product['price'],
    'currency' => $product['currency'],
    'image' => $product['main_image'],
    'tags' => array_filter(array_map('trim', explode(',', (string)$product['tags']))),
    'link' => 'detail.php?slug=' . urlencode($product['slug']),
  ];
}

$currentUser = current_user($pdo);

foreach ($guides as $guide) {
  $results[] = [
    'title' => $guide['title'],
    'category' => $guide['category'] ?: 'Guide',
    'summary' => $guide['summary'],
    'price' => null,
    'currency' => null,
    'image' => $guide['image'],
    'tags' => array_filter(array_map('trim', explode(',', (string)$guide['tags']))),
    'link' => '#',
  ];
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title data-i18n="search.metaTitle">Résultats de recherche – Exoleton</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Résultats de recherche pour les exosquelettes et technologies d’assistance sur Exoleton." data-i18n-description="search.metaDescription">

  <link rel="canonical" href="https://exoleton.com/recherche">

  <!-- Favicons -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico?v=1">
  <link rel="shortcut icon" href="/favicon.ico?v=1">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/ico.png">
  <link rel="icon" type="image/png" sizes="192x192" href="/assets/img/ico.png">
  <link rel="apple-touch-icon" href="/assets/img/ico.png">

  <!-- Open Graph -->
  <meta property="og:title" content="Résultats de recherche – Exoleton" data-i18n-property="og:title:search.metaTitle">
  <meta property="og:description" content="Découvrez les produits, guides et ressources correspondant à votre recherche." data-i18n-property="og:description:search.ogDescription">
  <meta property="og:image" content="assets/img/hero-exosquelette.jpg">
  <meta property="og:type" content="website">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

  <header class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="assets/img/logo.png" alt="Exoleton" width="272" height="1000" class="me-2">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Basculer la navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <nav id="mainNav" class="collapse navbar-collapse">
        <div class="d-lg-flex align-items-lg-center w-100 gap-3">
          <ul class="navbar-nav align-items-lg-center mb-2 mb-lg-0 me-lg-3">
            <li class="nav-item"><a class="nav-link fw-semibold" href="index.php" data-i18n="nav.home">Accueil</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php#guides" data-i18n="nav.guides">Guides</a></li>
          </ul>
          <form class="nav-search flex-grow-1 my-3 my-lg-0" method="get" action="recherche.php" role="search">
            <label class="visually-hidden" for="navSearchQuery">Rechercher</label>
            <div class="input-group nav-search-combobox">
              <label class="visually-hidden" for="navSearchCategory">Catégorie</label>
              <select id="navSearchCategory" name="cat" class="form-select bg-light-subtle border-end-0">
                <?php foreach ($navCategoryOptions as $value => $label): ?>
                  <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $category === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
              <span class="input-group-text bg-transparent border-start-0 border-end-0 px-3"><span class="bi bi-search"></span></span>
              <input id="navSearchQuery" name="q" type="search" class="form-control border-start-0 border-end-0" placeholder="Exosquelette industriel, aide à la marche…" value="<?php echo $sanitizedQuery; ?>" data-i18n-placeholder="search.placeholder">
              <button class="btn btn-primary" type="submit" data-i18n="search.cta">Rechercher</button>
            </div>
          </form>

          <ul class="navbar-nav ms-lg-auto mb-2 mb-lg-0 align-items-lg-center">
            <li class="nav-item ms-lg-3">
              <a class="nav-link" href="index.php#cta" data-i18n="nav.cta">Découvrir les solutions</a>
            </li>
            <li class="nav-item ms-lg-3">
              <label class="visually-hidden" for="languageSwitcherSearch" data-i18n="lang.label">Langue</label>
              <select id="languageSwitcherSearch" class="form-select form-select-sm" data-language-switcher></select>
            </li>
            <?php if ($currentUser): ?>
              <li class="nav-item dropdown ms-lg-3">
                <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Bonjour <?= htmlspecialchars($currentUser['name']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                  <li><a class="dropdown-item" href="account.php">Mon compte</a></li>
                  <?php if (($currentUser['role'] ?? 'customer') === 'admin'): ?>
                    <li><a class="dropdown-item" href="admin.php">Administration</a></li>
                  <?php endif; ?>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger" href="logout.php">Se déconnecter</a></li>
                </ul>
              </li>
            <?php else: ?>
              <li class="nav-item ms-lg-3">
                <a class="btn btn-outline-primary" href="login.php">Connexion</a>
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
          <div class="col-lg-7">
            <p class="text-primary fw-semibold mb-2" data-i18n="search.title">Résultats de recherche</p>
            <h1 class="h2 mb-3"><span data-i18n="search.heading">Ce que nous avons trouvé</span><?php if ($sanitizedQuery): ?> pour «&nbsp;<?php echo $sanitizedQuery; ?>&nbsp;»<?php endif; ?></h1>
            <p class="text-muted mb-0" data-i18n="search.subtitle">Affinez votre recherche, parcourez les modèles et guides correspondants ou contactez-nous pour être accompagné.</p>
          </div>
          <div class="col-lg-5">
            <form class="search-box p-3 p-lg-4 shadow-sm rounded-4 bg-white" method="get" action="recherche.php">
              <label for="searchQuery" class="form-label text-muted small text-uppercase" data-i18n="search.label">Rechercher</label>
              <div class="input-group input-group-lg mb-3 search-combobox">
                <label class="visually-hidden" for="searchCategory">Catégorie</label>
                <select id="searchCategory" name="cat" class="form-select bg-light-subtle border-end-0">
                  <?php foreach ($categoryOptions as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $category === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
                <span class="input-group-text bg-transparent border-start-0 border-end-0 px-3"><span class="bi bi-search"></span></span>
                <input id="searchQuery" name="q" type="search" class="form-control border-start-0 border-end-0" placeholder="Exosquelette industriel, aide à la marche…" value="<?php echo $sanitizedQuery; ?>" data-i18n-placeholder="search.placeholder">
                <button type="submit" class="btn btn-primary px-4">Rechercher</button>
              </div>
              <div class="d-flex flex-wrap gap-2">
                <span class="badge rounded-pill text-bg-light" data-i18n="nav.industrial">Industriel</span>
                <span class="badge rounded-pill text-bg-light" data-i18n="nav.medical">Médical</span>
                <span class="badge rounded-pill text-bg-light" data-i18n="search.rehab">Rééducation</span>
                <span class="badge rounded-pill text-bg-light" data-i18n="search.budget">Budget & financement</span>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5">
      <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
          <div>
            <p class="text-muted mb-1 small text-uppercase" data-i18n="search.results">Résultats</p>
            <h2 class="h4 mb-0"><?php echo count($results); ?> élément<?php echo count($results) > 1 ? 's' : ''; ?> trouvé<?php echo count($results) > 1 ? 's' : ''; ?></h2>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted small" data-i18n="search.sort">Trier par</span>
            <select class="form-select" style="min-width: 210px;">
              <option data-i18n="search.sortRelevance">Pertinence</option>
              <option data-i18n="search.sortLow">Prix croissant</option>
              <option data-i18n="search.sortHigh">Prix décroissant</option>
            </select>
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
                    <img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php endif; ?>
                  <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <span class="badge <?php echo $item['category'] === 'Industriel' ? 'bg-success' : ($item['category'] === 'Médical' ? 'bg-info' : 'bg-secondary'); ?>">
                        <?php echo htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                      <?php if ($item['price'] !== null): ?><span class="price fw-semibold"><?php echo htmlspecialchars(price_html((int)$item['price'], $item['currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                    </div>
                    <h3 class="h5 card-title mb-2"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="text-muted small mb-3 flex-grow-1"><?php echo htmlspecialchars($item['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                      <?php foreach ($item['tags'] as $tag): ?>
                        <span class="badge rounded-pill text-bg-light"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                      <?php endforeach; ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-auto">
                      <a href="<?php echo htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary btn-sm" data-i18n="selection.details">Voir les détails</a>
                      <a href="index.php#cta" class="btn btn-link text-decoration-none" data-i18n="search.contact">Être recontacté</a>
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
            <img src="assets/img/logo.png" alt="Exoleton" width="136" height="50" class="me-2">
          </div>
          <p class="text-white-50" data-i18n="footer.mission">Site d’exosquelettes et technologies d’assistance. Notre mission : rendre la mobilité augmentée accessible à tous.</p>
        </div>
        <div class="col-6 col-md-2">
          <h3 class="h6" data-i18n="footer.navigation">Navigation</h3>
          <ul class="list-unstyled">
            <li><a class="footer-link" href="index.php" data-i18n="nav.home">Accueil</a></li>
            <li><a class="footer-link" href="index.php#guides" data-i18n="nav.guides">Guides</a></li>
            <li><a class="footer-link" href="recherche.php" data-i18n="nav.search">Recherche</a></li>
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

  <div id="cookieBanner" class="cookie-banner shadow-lg" role="dialog" aria-live="polite" aria-label="Bannière de consentement aux cookies" hidden>
    <div class="cookie-banner__content">
      <h2 class="h5 mb-2" data-i18n="cookie.bannerTitle">Nous utilisons des cookies</h2>
      <p class="mb-0 small text-muted" data-i18n="cookie.bannerText">
        Certains cookies sont essentiels au bon fonctionnement du site. Nous utilisons également des cookies optionnels pour mesurer l’audience et améliorer votre expérience.
      </p>
    </div>
    <div class="cookie-banner__actions">
      <button type="button" class="btn btn-primary" id="cookieAcceptAll" data-i18n="cookie.accept">Tout accepter</button>
      <button type="button" class="btn btn-outline-secondary" id="cookieRejectAll" data-i18n="cookie.reject">Tout refuser</button>
      <button type="button" class="btn btn-link text-decoration-none" id="cookieCustomize" data-bs-toggle="modal" data-bs-target="#cookieSettingsModal">
        <span data-i18n="cookie.customize">Personnaliser</span>
      </button>
    </div>
  </div>

  <div class="modal fade" id="cookieSettingsModal" tabindex="-1" aria-labelledby="cookieSettingsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title h5 mb-0" id="cookieSettingsTitle" data-i18n="cookie.modalTitle">Préférences de cookies</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer" data-i18n-aria-label="cookie.close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted" data-i18n="cookie.modalIntro">Modifiez ci-dessous vos préférences. Les cookies nécessaires sont toujours actifs afin de garantir la sécurité et le fonctionnement du site.</p>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="cookieNecessary" checked disabled>
            <label class="form-check-label" for="cookieNecessary">
              <span data-i18n="cookie.necessary">Cookies nécessaires</span>
              <span class="d-block text-muted small" data-i18n="cookie.necessaryDesc">Indispensables pour la sécurité, l’accessibilité et la mémorisation de vos choix.</span>
            </label>
          </div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="cookieAnalytics">
            <label class="form-check-label" for="cookieAnalytics">
              <span data-i18n="cookie.analytics">Cookies de mesure d’audience</span>
              <span class="d-block text-muted small" data-i18n="cookie.analyticsDesc">Nous aident à comprendre comment le site est utilisé pour l’améliorer.</span>
            </label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="cookieMarketing">
            <label class="form-check-label" for="cookieMarketing">
              <span data-i18n="cookie.marketing">Cookies marketing</span>
              <span class="d-block text-muted small" data-i18n="cookie.marketingDesc">Permettent de personnaliser la communication et les offres.</span>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-i18n="cookie.cancel">Annuler</button>
          <button type="button" class="btn btn-primary" id="cookieSavePreferences" data-i18n="cookie.save">Enregistrer</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/main.js"></script>
  <script src="assets/js/i18n.js"></script>
</body>
</html>
