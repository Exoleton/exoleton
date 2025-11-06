<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Exoleton – Site d’exosquelettes et technologies d’assistance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Découvrez, comparez et accédez aux meilleures solutions d’exosquelettes et technologies d’assistance pour professionnels, collectivités et particuliers.">

  <!-- Canonical (ok de laisser, n'affecte pas le chargement local) -->
  <link rel="canonical" href="https://exoleton.com/">

  <!-- Favicons (CHEMINS RELATIFS) -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico?v=1">
	<link rel="shortcut icon" href="/favicon.ico?v=1">
	<link rel="icon" type="image/png" sizes="32x32" href="/assets/img/ico.png">
	<link rel="icon" type="image/png" sizes="192x192" href="/assets/img/ico.png">
	<link rel="apple-touch-icon" href="/assets/img/ico.png">


  <!-- Open Graph (CHEMIN RELATIF) -->
  <meta property="og:title" content="Exoleton – La mobilité augmentée, accessible à tous">
  <meta property="og:description" content="Site de référence pour exosquelettes et assistances physiques.">
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
          <li class="nav-item"><a class="nav-link" href="index.php">Accueil</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#produits" id="produitsMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Produits</a>
            <ul class="dropdown-menu" aria-labelledby="produitsMenu">
              <li><a class="dropdown-item" href="#produits">Industriel</a></li>
              <li><a class="dropdown-item" href="#produits">Médical / Rééducation</a></li>
              <li><a class="dropdown-item" href="#produits">Particulier / Quotidien</a></li>
              <li><a class="dropdown-item" href="#produits">Collectivités / Soins</a></li>
            </ul>
          </li>
          <li class="nav-item"><a class="nav-link" href="#comparateur">Comparateur</a></li>
          <li class="nav-item"><a class="nav-link" href="#guides">Guides</a></li>
          <li class="nav-item ms-lg-3">
            <a class="btn btn-primary" href="#cta">Découvrir les solutions</a>
          </li>
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
          <h1 class="display-5 fw-bold mb-3">La mobilité augmentée, accessible à tous.</h1>
          <p class="lead mb-4">
            Exoleton est le site de référence pour les exosquelettes et technologies d’assistance.
            Découvrez, comparez et accédez aux solutions adaptées à vos besoins professionnels et personnels.
          </p>
          <div class="d-flex gap-3">
            <a href="#produits" class="btn btn-primary btn-lg">Explorer les produits</a>
            <a href="#guides" class="btn btn-outline-light btn-lg">Lire les guides</a>
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
          <h3 class="h5">Large choix de modèles</h3>
          <p class="text-muted mb-0">Une sélection couvrant les usages industriels, médicaux et du quotidien.</p>
        </div>
        <div class="col-md-4">
          <div class="icon-badge mx-auto mb-3">★</div>
          <h3 class="h5">Partenaires de confiance</h3>
          <p class="text-muted mb-0">Fabricants reconnus, produits certifiés et processus d’achat encadré.</p>
        </div>
        <div class="col-md-4">
          <div class="icon-badge mx-auto mb-3">ℹ︎</div>
          <h3 class="h5">Conseils d’experts</h3>
          <p class="text-muted mb-0">Guides, cas d’usage et comparatifs pour choisir en toute sérénité.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- PRODUITS MIS EN AVANT -->
  <section id="produits" class="py-5">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="h3 mb-0">Sélection du moment</h2>
        <a href="#comparateur" class="link-primary">Comparer les modèles →</a>
      </div>

      <div class="row g-4">
        <!-- Carte produit 1 -->
        <div class="col-md-4">
          <article class="card product-card h-100">
            <img src="assets/img/produit-1.jpg" class="card-img-top" alt="ExoLift – exosquelette industriel">
            <div class="card-body">
              <span class="badge bg-success mb-2">Industriel</span>
              <h3 class="h5 card-title mb-1">ExoLift</h3>
              <p class="text-muted small mb-3">Assistance au levage jusqu’à 30 kg · Batterie échangeable</p>
              <div class="d-flex align-items-center justify-content-between">
                <strong class="price">4 500 €</strong>
                <a href="#" class="btn btn-outline-primary btn-sm">Voir les détails</a>
              </div>
            </div>
          </article>
        </div>

        <!-- Carte produit 2 -->
        <div class="col-md-4">
          <article class="card product-card h-100">
            <img src="assets/img/produit-2.jpg" class="card-img-top" alt="Atalante X – rééducation">
            <div class="card-body">
              <span class="badge bg-info mb-2">Médical</span>
              <h3 class="h5 card-title mb-1">Atalante X</h3>
              <p class="text-muted small mb-3">Rééducation de la marche · Usage en établissement</p>
              <div class="d-flex align-items-center justify-content-between">
                <strong class="price">Sur demande</strong>
                <a href="#" class="btn btn-outline-primary btn-sm">Voir les détails</a>
              </div>
            </div>
          </article>
        </div>

        <!-- Carte produit 3 -->
        <div class="col-md-4">
          <article class="card product-card h-100">
            <img src="assets/img/produit-3.jpg" class="card-img-top" alt="AssistArm – assistance quotidienne">
            <div class="card-body">
              <span class="badge bg-secondary mb-2">Particulier</span>
              <h3 class="h5 card-title mb-1">AssistArm</h3>
              <p class="text-muted small mb-3">Soulagement des efforts répétés · Ultra-léger</p>
              <div class="d-flex align-items-center justify-content-between">
                <strong class="price">2 800 €</strong>
                <a href="#" class="btn btn-outline-primary btn-sm">Voir les détails</a>
              </div>
            </div>
          </article>
        </div>
      </div>

    </div>
  </section>

  <!-- COMPARATEUR (apercu) -->
  <section id="comparateur" class="py-5 bg-light border-top">
    <div class="container">
      <div class="row align-items-center g-4">
        <div class="col-lg-6">
          <h2 class="h3 mb-3">Comparer en un coup d’œil</h2>
          <p class="text-muted">Poids, autonomie, type d’assistance, charge supportée, certifications… Notre comparateur vous aide à sélectionner le bon modèle pour votre activité.</p>
          <a class="btn btn-primary" href="#cta">Accéder au comparateur</a>
        </div>
        <div class="col-lg-6">
          <div class="table-responsive rounded-3 shadow-sm bg-white">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Modèle</th>
                  <th>Type</th>
                  <th>Poids</th>
                  <th>Autonomie</th>
                  <th>Charge</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>ExoLift</td>
                  <td>Actif</td>
                  <td>7,8 kg</td>
                  <td>6 h</td>
                  <td>30 kg</td>
                </tr>
                <tr>
                  <td>Atalante X</td>
                  <td>Actif</td>
                  <td>≈30 kg</td>
                  <td>—</td>
                  <td>—</td>
                </tr>
                <tr>
                  <td>AssistArm</td>
                  <td>Passif</td>
                  <td>2,1 kg</td>
                  <td>∞</td>
                  <td>—</td>
                </tr>
              </tbody>
            </table>
          </div>
          <small class="text-muted d-block mt-2">*Données indicatives, variables selon configuration.</small>
        </div>
      </div>
    </div>
  </section>

  <!-- GUIDES -->
  <section id="guides" class="py-5">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="h3 mb-0">Guides & cas d’usage</h2>
        <a href="#" class="link-primary">Voir tous les articles →</a>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <article class="card h-100 shadow-sm">
            <div class="card-body">
              <h3 class="h5">Choisir un exosquelette pour la logistique</h3>
              <p class="text-muted">Critères essentiels, ROI, prévention des TMS, sécurité et formation.</p>
              <a class="stretched-link" href="#"></a>
            </div>
          </article>
        </div>
        <div class="col-md-4">
          <article class="card h-100 shadow-sm">
            <div class="card-body">
              <h3 class="h5">Aide à la marche : quelles solutions ?</h3>
              <p class="text-muted">Panorama des dispositifs disponibles et indications d’usage.</p>
              <a class="stretched-link" href="#"></a>
            </div>
          </article>
        </div>
        <div class="col-md-4">
          <article class="card h-100 shadow-sm">
            <div class="card-body">
              <h3 class="h5">Financements & subventions</h3>
              <p class="text-muted">Pistes pour entreprises, hôpitaux, collectivités et particuliers.</p>
              <a class="stretched-link" href="#"></a>
            </div>
          </article>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section id="cta" class="py-5 bg-primary text-white">
    <div class="container">
      <div class="row align-items-center g-3">
        <div class="col-lg-8">
          <h2 class="h4 mb-1">Un besoin précis ? Parlons-en.</h2>
          <p class="mb-0 opacity-75">Nos équipes vous orientent vers les bons modèles et vous accompagnent dans votre projet.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="#contact" class="btn btn-outline-light btn-lg">Être recontacté</a>
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
          <p class="text-white-50">Site d’exosquelettes et technologies d’assistance. Notre mission : rendre la mobilité augmentée accessible à tous.</p>
        </div>
        <div class="col-6 col-md-2">
          <h3 class="h6">Navigation</h3>
          <ul class="list-unstyled">
            <li><a class="footer-link" href="index.php">Accueil</a></li>
            <li><a class="footer-link" href="#produits">Produits</a></li>
            <li><a class="footer-link" href="#comparateur">Comparateur</a></li>
            <li><a class="footer-link" href="#guides">Guides</a></li>
          </ul>
        </div>
        <div class="col-6 col-md-3">
          <h3 class="h6">Ressources</h3>
          <ul class="list-unstyled">
            <li><a class="footer-link" href="#">FAQ</a></li>
            <li><a class="footer-link" href="#">Support</a></li>
            <li><a class="footer-link" href="#">Mentions légales</a></li>
            <li><a class="footer-link" href="#">Politique de confidentialité</a></li>
            <li>
              <button type="button" class="footer-link btn btn-link p-0 text-start" data-bs-toggle="modal" data-bs-target="#cookieSettingsModal">
                Gérer les cookies
              </button>
            </li>
          </ul>
        </div>
        <div class="col-md-3">
          <h3 class="h6">Newsletter</h3>
          <form class="d-flex gap-2" action="#" method="post" onsubmit="return false;">
            <input type="email" class="form-control" placeholder="Votre email" aria-label="Votre email">
            <button class="btn btn-success">S’inscrire</button>
          </form>
        </div>
      </div>
      <hr class="border-secondary my-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center pb-4">
        <small class="text-white-50">© <span id="year"></span> Exoleton. Tous droits réservés.</small>
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
      <h2 class="h5 mb-2">Nous utilisons des cookies</h2>
      <p class="mb-0 small text-muted">
        Certains cookies sont essentiels au bon fonctionnement du site. Nous utilisons également des cookies optionnels pour mesurer l’audience et améliorer votre expérience.
      </p>
    </div>
    <div class="cookie-banner__actions">
      <button type="button" class="btn btn-primary" id="cookieAcceptAll">Tout accepter</button>
      <button type="button" class="btn btn-outline-secondary" id="cookieRejectAll">Tout refuser</button>
      <button type="button" class="btn btn-link text-decoration-none" id="cookieCustomize" data-bs-toggle="modal" data-bs-target="#cookieSettingsModal">
        Personnaliser
      </button>
    </div>
  </div>

  <div class="modal fade" id="cookieSettingsModal" tabindex="-1" aria-labelledby="cookieSettingsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title h5 mb-0" id="cookieSettingsTitle">Préférences de cookies</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted">Modifiez ci-dessous vos préférences. Les cookies nécessaires sont toujours actifs afin de garantir la sécurité et le fonctionnement du site.</p>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="cookieNecessary" checked disabled>
            <label class="form-check-label" for="cookieNecessary">
              Cookies nécessaires
              <span class="d-block text-muted small">Indispensables pour la sécurité, l’accessibilité et la mémorisation de vos choix.</span>
            </label>
          </div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="cookieAnalytics">
            <label class="form-check-label" for="cookieAnalytics">
              Cookies de mesure d’audience
              <span class="d-block text-muted small">Nous aident à comprendre comment le site est utilisé pour l’améliorer.</span>
            </label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="cookieMarketing">
            <label class="form-check-label" for="cookieMarketing">
              Cookies marketing
              <span class="d-block text-muted small">Permettent de personnaliser la communication et les offres.</span>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="button" class="btn btn-primary" id="cookieSavePreferences">Enregistrer</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>
