<?php
require __DIR__ . '/auth.php';

function price_html($p, $cur = 'EUR')
{
  if (!$p || $p <= 0) return 'Sur demande';
  return number_format($p, 0, ',', ' ') . ' ' . ($cur === 'EUR' ? '€' : $cur);
}

$productsStmt = $pdo->query("SELECT id, slug, name, category, tag, price, currency, summary, main_image, type, weight, autonomy, charge FROM products ORDER BY featured_order, name");
$products = $productsStmt->fetchAll();

$guidesStmt = $pdo->query("SELECT title, summary, image FROM guides ORDER BY published_at DESC, id DESC LIMIT 3");
$guides = $guidesStmt->fetchAll();

$announcementsStmt = $pdo->query("SELECT fa.title, fa.message, fa.link_url, fa.priority, p.slug, p.name AS product_name FROM featured_announcements fa LEFT JOIN products p ON p.id = fa.product_id WHERE fa.is_active = 1 AND (fa.start_at IS NULL OR fa.start_at <= NOW()) AND (fa.end_at IS NULL OR fa.end_at >= NOW()) ORDER BY fa.priority DESC, fa.start_at DESC, fa.id DESC LIMIT 3");
$announcements = $announcementsStmt->fetchAll();

$currentUser = current_user($pdo);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title data-i18n="meta.title">Exoleton – Site d’exosquelettes et technologies d’assistance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Découvrez, comparez et accédez aux meilleures solutions d’exosquelettes et technologies d’assistance pour professionnels, collectivités et particuliers." data-i18n-description="meta.description">

  <!-- Canonical (ok de laisser, n'affecte pas le chargement local) -->
  <link rel="canonical" href="https://exoleton.com/">

  <!-- Favicons (CHEMINS RELATIFS) -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico?v=1">
	<link rel="shortcut icon" href="/favicon.ico?v=1">
	<link rel="icon" type="image/png" sizes="32x32" href="/assets/img/ico.png">
	<link rel="icon" type="image/png" sizes="192x192" href="/assets/img/ico.png">
	<link rel="apple-touch-icon" href="/assets/img/ico.png">


  <!-- Open Graph (CHEMIN RELATIF) -->
  <meta property="og:title" content="Exoleton – La mobilité augmentée, accessible à tous" data-i18n-property="og:title:meta.ogTitle">
  <meta property="og:description" content="Site de référence pour exosquelettes et assistances physiques." data-i18n-property="og:description:meta.ogDescription">
  <meta property="og:image" content="assets/img/hero-exosquelette.jpg">
  <meta property="og:type" content="website">

  <!-- Bootstrap 5 (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Feuille de style custom (CHEMIN RELATIF) -->
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

  <!-- HEADER / NAV -->
  <header class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="assets/img/logo.png" alt="Exoleton" width="272" height="1000" class="me-2">
        <!--<span class="fw-semibold">Movalya</span>-->
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Basculer la navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <nav id="mainNav" class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
          <li class="nav-item"><a class="nav-link" href="index.php" data-i18n="nav.home">Accueil</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#produits" id="produitsMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-i18n="nav.products">Produits</a>
            <ul class="dropdown-menu" aria-labelledby="produitsMenu">
              <li><a class="dropdown-item" href="#produits" data-i18n="nav.industrial">Industriel</a></li>
              <li><a class="dropdown-item" href="#produits" data-i18n="nav.medical">Médical / Rééducation</a></li>
              <li><a class="dropdown-item" href="#produits" data-i18n="nav.consumer">Particulier / Quotidien</a></li>
              <li><a class="dropdown-item" href="#produits" data-i18n="nav.care">Collectivités / Soins</a></li>
            </ul>
          </li>
          <li class="nav-item"><a class="nav-link" href="#comparateur" data-i18n="nav.comparator">Comparateur</a></li>
          <li class="nav-item"><a class="nav-link" href="#guides" data-i18n="nav.guides">Guides</a></li>
          <li class="nav-item">
            <a class="nav-link nav-search-highlight d-flex align-items-center gap-1" href="recherche.php">
              <span data-i18n="nav.search">Recherche</span>
              <span class="search-icon" aria-hidden="true">🔍</span>
            </a>
          </li>
          <li class="nav-item ms-lg-3">
            <a class="nav-link" href="#cta" data-i18n="nav.cta">Découvrir les solutions</a>
          </li>
          <li class="nav-item ms-lg-3">
            <label class="visually-hidden" for="languageSwitcher" data-i18n="lang.label">Langue</label>
            <select id="languageSwitcher" class="form-select form-select-sm" data-language-switcher>
            </select>
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
      </nav>
    </div>
  </header>

  <!-- HERO -->
  <section class="hero position-relative text-white">
    <img class="hero-bg" src="assets/img/hero-exosquelette.png" alt="Exosquelette en action">
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
                    <h3 class="h6 mb-1"><?= htmlspecialchars($announcement['title']) ?></h3>
                    <p class="mb-2 small text-muted"><?= htmlspecialchars($announcement['message'] ?? '') ?></p>
                  </div>
                  <span class="badge bg-primary-subtle text-primary">Mise en avant</span>
                </div>
                <?php if (!empty($announcement['link_url'])): ?>
                  <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($announcement['link_url']) ?>" target="_blank" rel="noopener">Découvrir</a>
                <?php elseif (!empty($announcement['slug'])): ?>
                  <a class="btn btn-sm btn-outline-primary" href="detail.php?slug=<?= urlencode($announcement['slug']) ?>">Voir le produit</a>
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
                <img src="<?= htmlspecialchars($product['main_image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
              <?php endif; ?>
              <div class="card-body">
                <span class="badge bg-<?= $product['tag']==='Industriel'?'success':($product['tag']==='Médical'?'info':'secondary') ?> mb-2"><?= htmlspecialchars($product['tag']) ?></span>
                <h3 class="h5 card-title mb-1"><?= htmlspecialchars($product['name']) ?></h3>
                <p class="text-muted small mb-3"><?= htmlspecialchars($product['summary']) ?></p>
                <div class="d-flex align-items-center justify-content-between">
                  <strong class="price"><?= price_html((int)$product['price'], $product['currency']) ?></strong>
                  <a href="detail.php?slug=<?= urlencode($product['slug']) ?>" class="btn btn-outline-primary btn-sm" data-i18n="selection.details">Voir les détails</a>
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
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['type'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($product['weight'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($product['autonomy'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($product['charge'] ?: '—') ?></td>
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
                <img src="<?= htmlspecialchars($guide['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($guide['title']) ?>">
              <?php endif; ?>
              <div class="card-body">
                <h3 class="h5"><?= htmlspecialchars($guide['title']) ?></h3>
                <p class="text-muted"><?= htmlspecialchars($guide['summary']) ?></p>
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
            <img src="assets/img/logo.png" alt="Exoleton" width="136" height="50" class="me-2">
            <!--<strong>Movalya</strong>-->
          </div>
          <p class="text-white-50" data-i18n="footer.mission">Site d’exosquelettes et technologies d’assistance. Notre mission : rendre la mobilité augmentée accessible à tous.</p>
        </div>
        <div class="col-6 col-md-2">
          <h3 class="h6" data-i18n="footer.navigation">Navigation</h3>
          <ul class="list-unstyled">
            <li><a class="footer-link" href="index.php" data-i18n="nav.home">Accueil</a></li>
            <li><a class="footer-link" href="#produits" data-i18n="nav.products">Produits</a></li>
            <li><a class="footer-link" href="#comparateur" data-i18n="nav.comparator">Comparateur</a></li>
            <li><a class="footer-link" href="#guides" data-i18n="nav.guides">Guides</a></li>
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

  <!-- COOKIE CONSENT -->
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

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/main.js"></script>
  <script src="assets/js/i18n.js"></script>
</body>
</html>
