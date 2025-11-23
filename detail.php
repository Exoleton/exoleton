<?php
// ===============================================
// detail.php — Page détail produit Exoleton
// ===============================================
require __DIR__ . '/db.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : 'exolift';

$productStmt = $pdo->prepare('SELECT * FROM products WHERE slug = :slug LIMIT 1');
$productStmt->execute(['slug' => $slug]);
$product = $productStmt->fetch();

if (!$product) {
  http_response_code(404);
  echo '<h1>Produit introuvable</h1>';
  exit;
}

function price_html($p, $cur = 'EUR'){
  if(!$p || $p <= 0) return 'Sur demande';
  return number_format($p, 0, ',', ' ') . ' ' . ($cur === 'EUR' ? '€' : $cur);
}

$imagesStmt = $pdo->prepare('SELECT url FROM product_images WHERE product_id = :pid ORDER BY sort_order');
$imagesStmt->execute(['pid' => $product['id']]);
$galleryImages = $imagesStmt->fetchAll(PDO::FETCH_COLUMN) ?: [$product['hero_image']];

$downloadsStmt = $pdo->prepare('SELECT label, href FROM product_downloads WHERE product_id = :pid ORDER BY sort_order');
$downloadsStmt->execute(['pid' => $product['id']]);
$downloads = $downloadsStmt->fetchAll();

$useCasesStmt = $pdo->prepare('SELECT title, kpi, description FROM product_use_cases WHERE product_id = :pid ORDER BY sort_order');
$useCasesStmt->execute(['pid' => $product['id']]);
$use_cases = $useCasesStmt->fetchAll();

$specsStmt = $pdo->prepare('SELECT label, value FROM product_specs WHERE product_id = :pid ORDER BY sort_order');
$specsStmt->execute(['pid' => $product['id']]);
$specs = $specsStmt->fetchAll();

$alternativesStmt = $pdo->prepare('SELECT alt_name, alt_slug, tag, summary, price, image FROM product_alternatives WHERE product_id = :pid ORDER BY sort_order');
$alternativesStmt->execute(['pid' => $product['id']]);
$alternatives = $alternativesStmt->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($product['name']) ?> – Exoleton</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= htmlspecialchars($product['baseline']) ?>">

  <link rel="canonical" href="https://www.exoleton.com/produits/<?= urlencode($product['slug']) ?>">

  <!-- Favicons -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico?v=1">
  <link rel="shortcut icon" href="/favicon.ico?v=1">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/ico.png">
  <link rel="icon" type="image/png" sizes="192x192" href="/assets/img/ico.png">
  <link rel="apple-touch-icon" href="/assets/img/ico.png">

  <!-- Open Graph -->
  <meta property="og:title" content="<?= htmlspecialchars($product['name']) ?> – <?= htmlspecialchars($product['category']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($product['baseline']) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($product['hero_image']) ?>">
  <meta property="og:type" content="product">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Styles -->
  <link rel="stylesheet" href="assets/css/main.css">

  <style>
    /* Ajustements locaux rapides (tu peux les déplacer dans main.css) */
    .product-hero{position:relative;background:#0b0f14;color:#fff;overflow:hidden}
    .product-hero .overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(0,0,0,.55),rgba(0,0,0,.2))}
    .product-hero img.hero{object-fit:cover;width:100%;height:420px;opacity:.55}
    @media (min-width:992px){.product-hero img.hero{height:520px}}
    .thumbs img{width:100%;border-radius:.5rem;cursor:pointer;border:2px solid transparent}
    .thumbs img.active{border-color:#0d6efd}
    .product-sticky{position:fixed;left:0;right:0;bottom:0;z-index:1030;background:#111;color:#fff;padding:.75rem 1rem;border-top:1px solid rgba(255,255,255,.1);display:none}
    .product-sticky .price{font-weight:700}
    @media (max-width:991.98px){.product-sticky{display:block}}
    .icon-badge{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#e9f2ff;color:#0d6efd;font-weight:700}
    .specs th{width:42%}
  </style>
</head>
<body>

  <!-- HEADER / NAV (copié d’index.php) -->
  <header class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="assets/img/logo.png" alt="Exoleton" width="272" height="1000" class="me-2">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Basculer la navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <nav id="mainNav" class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
          <li class="nav-item"><a class="nav-link" href="index.php">Accueil</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle active" href="#produits" id="produitsMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Produits</a>
            <ul class="dropdown-menu" aria-labelledby="produitsMenu">
              <li><a class="dropdown-item" href="index.php#produits">Industriel</a></li>
              <li><a class="dropdown-item" href="index.php#produits">Médical / Rééducation</a></li>
              <li><a class="dropdown-item" href="index.php#produits">Particulier / Quotidien</a></li>
              <li><a class="dropdown-item" href="index.php#produits">Collectivités / Soins</a></li>
            </ul>
          </li>
          <li class="nav-item"><a class="nav-link" href="index.php#comparateur">Comparateur</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#guides">Guides</a></li>
          <li class="nav-item ms-lg-3">
            <a class="btn btn-primary" href="#demo">Demander une démo</a>
          </li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="mt-5 pt-4">

    <!-- Fil d’Ariane -->
    <nav class="container" aria-label="breadcrumb">
      <ol class="breadcrumb small mb-2">
        <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
        <li class="breadcrumb-item"><a href="index.php#produits">Produits</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
      </ol>
    </nav>

    <!-- HERO produit 
    <section class="product-hero mb-4">
      <img class="hero" src="<?= htmlspecialchars($product['hero_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
      <div class="overlay"></div>
      <div class="container position-relative">
        <div class="row align-items-end" style="min-height: 320px;">
          <div class="col-lg-7 py-4">
            <?php $badgeClass = $product['tag']==='Industriel'?'bg-success':($product['tag']==='Médical'?'bg-info':'bg-secondary'); ?>
            <span class="badge <?= $badgeClass ?> mb-2"><?= htmlspecialchars($product['tag']) ?></span>
            <h1 class="display-6 fw-bold mb-2"><?= htmlspecialchars($product['name']) ?></h1>
            <p class="lead mb-3"><?= htmlspecialchars($product['baseline']) ?></p>
            <?php $bullets = array_filter(array_map('trim', explode('|', (string)$product['bullets']))); ?>
            <?php if (!empty($bullets)): ?>
              <ul class="list-inline mb-4">
                <?php foreach($bullets as $b): ?>
                  <li class="list-inline-item me-3"><span class="icon-badge me-2">✓</span><?= htmlspecialchars($b) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-2">
              <a href="#demo" class="btn btn-primary btn-lg">Demander une démo</a>
              <a href="#devis" class="btn btn-outline-light btn-lg">Devis rapide</a>
              <a href="#specs" class="btn btn-link text-white text-decoration-none">Caractéristiques ↓</a>
            </div>
          </div>
        </div>
      </div>
    </section>-->

    <!-- Bloc résumé + galerie -->
    <section class="container mb-5">
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="ratio ratio-4x3 mb-3">
            <img id="mainImage" src="<?= htmlspecialchars($galleryImages[0] ?? $product['hero_image']) ?>" alt="Vue principale" class="w-100 h-100 rounded-3" style="object-fit:cover">
          </div>
          <div class="row g-2 thumbs" role="listbox" aria-label="Galerie produit">
            <?php foreach($galleryImages as $idx => $g): ?>
              <div class="col-4">
                <img src="<?= htmlspecialchars($g) ?>" alt="Miniature <?= $idx+1 ?>" class="thumb rounded-3 <?= $idx===0?'active':'' ?>" data-full="<?= htmlspecialchars($g) ?>">
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h2 class="h4 mb-3">À propos</h2>
              <p class="text-muted"><?= htmlspecialchars($product['summary']) ?></p>
              <div class="d-flex align-items-center justify-content-between my-3">
                <div>
                  <div class="small text-muted">Tarif indicatif</div>
                  <div class="h4 mb-0"><?= price_html($product['price'], $product['currency']) ?></div>
                </div>
                <div class="d-flex gap-2">
                  <a href="#demo" class="btn btn-primary">Demande de démo</a>
                  <a href="#devis" class="btn btn-outline-primary">Devis</a>
                </div>
              </div>
              <hr>
              <ul class="list-unstyled mb-0">
                <li class="mb-2">• Essai sur site possible (selon zone)</li>
                <li class="mb-2">• Formation opérateur incluse</li>
                <li class="mb-2">• Financements et aides possibles</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Use cases -->
    <section class="py-5 bg-light border-top">
      <div class="container">
        <h2 class="h4 mb-4">Cas d’usage</h2>
        <div class="row g-4">
                  <?php foreach($use_cases as $uc): ?>
                    <div class="col-md-4">
                      <div class="card h-100 shadow-sm">
                        <div class="card-body">
                          <div class="d-flex align-items-center justify-content-between">
                            <h3 class="h6 mb-1"><?= htmlspecialchars($uc['title']) ?></h3>
                            <span class="badge bg-primary"><?= htmlspecialchars($uc['kpi']) ?></span>
                          </div>
                          <p class="text-muted mb-0"><?= htmlspecialchars($uc['description']) ?></p>
                        </div>
                      </div>
                    </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Specs + Téléchargements -->
    <section id="specs" class="py-5">
      <div class="container">
        <div class="row g-4">
          <div class="col-lg-7">
            <h2 class="h4 mb-3">Caractéristiques techniques</h2>
            <div class="table-responsive rounded-3 shadow-sm bg-white">
              <table class="table align-middle mb-0 specs">
                <tbody>
                  <?php foreach($specs as $spec): ?>
                    <tr>
                      <th class="bg-light"><?= htmlspecialchars($spec['label']) ?></th>
                      <td><?= htmlspecialchars($spec['value']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <small class="text-muted d-block mt-2">*Données indicatives, variables selon configuration.</small>
          </div>
          <div class="col-lg-5">
            <h2 class="h4 mb-3">Téléchargements</h2>
            <ul class="list-group">
              <?php foreach($downloads as $d): ?>
                <li class="list-group-item d-flex align-items-center justify-content-between">
                  <span><?= htmlspecialchars($d['label']) ?></span>
                  <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($d['href']) ?>" target="_blank" rel="noopener">PDF</a>
                </li>
              <?php endforeach; ?>
            </ul>
            <div class="alert alert-info mt-3 mb-0 small">
              Besoin d’un document spécifique (fiche technique, essais, certificats) ? <a href="#contact" class="alert-link">Contacte-nous</a>.
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Démo + Devis + Mini ROI -->
    <section id="demo" class="py-5 bg-primary text-white">
      <div class="container">
        <div class="row g-4">
          <div class="col-lg-6">
            <h2 class="h4 mb-3">Demander une démo</h2>
            <form class="row g-3" action="#" method="post" onsubmit="return false;">
              <div class="col-md-6">
                <label class="form-label">Nom</label>
                <input type="text" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Secteur</label>
                <select class="form-select" required>
                  <option value="">Sélectionner</option>
                  <option>Logistique</option><option>Industrie</option><option>BTP</option><option>Santé</option><option>Autre</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Code postal</label>
                <input type="text" class="form-control" pattern="[0-9]{5}" placeholder="75001" required>
              </div>
              <div class="col-12">
                <label class="form-label">Votre besoin</label>
                <textarea class="form-control" rows="3" placeholder="Contexte, postes concernés, objectifs…"></textarea>
              </div>
              <div class="col-12">
                <button class="btn btn-light btn-lg">Envoyer la demande</button>
              </div>
            </form>
          </div>

          <div id="devis" class="col-lg-6">
            <h2 class="h4 mb-3">Devis rapide</h2>
            <form class="row g-3" action="#" method="post" onsubmit="return false;">
              <div class="col-md-6">
                <label class="form-label">Quantité</label>
                <input type="number" class="form-control" id="qte" value="1" min="1">
              </div>
              <div class="col-md-6">
                <label class="form-label">Options</label>
                <select class="form-select" id="opt">
                  <option value="0">Aucune</option>
                  <option value="450">Batterie supplémentaire (+450€)</option>
                  <option value="190">Harnais XL (+190€)</option>
                </select>
              </div>
              <div class="col-12">
                <div class="p-3 bg-white text-dark rounded-3 d-flex align-items-center justify-content-between">
                  <div>
                    <div class="small text-muted">Estimation</div>
                    <div class="h5 mb-0" id="estimation"><?= price_html($product['price']) ?></div>
                  </div>
                  <button class="btn btn-primary">Recevoir le devis</button>
                </div>
              </div>
            </form>

            <div class="mt-4 p-3 bg-white text-dark rounded-3">
              <h3 class="h6">ROI rapide (indicatif)</h3>
              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label small">Opérateurs</label>
                  <input type="number" class="form-control form-control-sm" id="op" value="5" min="1">
                </div>
                <div class="col-6">
                  <label class="form-label small">AT/an évités</label>
                  <input type="number" class="form-control form-control-sm" id="at" value="1" min="0">
                </div>
                <div class="col-12">
                  <label class="form-label small">Levages/jour</label>
                  <input type="number" class="form-control form-control-sm" id="lev" value="200" min="0">
                </div>
              </div>
              <div class="d-flex align-items-center justify-content-between mt-3">
                <small class="text-muted">Hypothèses internes simples</small>
                <div class="fw-bold" id="roiTxt">Payback estimé : —</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="py-5">
      <div class="container">
        <h2 class="h4 mb-4">FAQ</h2>
        <div class="accordion" id="faq">
          <div class="accordion-item">
            <h2 class="accordion-header" id="q1">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#a1">Faut-il une formation ?</button>
            </h2>
            <div id="a1" class="accordion-collapse collapse show" data-bs-parent="#faq">
              <div class="accordion-body">Oui, une prise en main opérateur est prévue. Durée typique : 1–2 h.</div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header" id="q2">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2">Hygiène et partage entre opérateurs ?</button>
            </h2>
            <div id="a2" class="accordion-collapse collapse" data-bs-parent="#faq">
              <div class="accordion-body">Harnais lavable et consommables remplaçables. Tailles S–L.</div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header" id="q3">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a3">Maintenance ?</button>
            </h2>
            <div id="a3" class="accordion-collapse collapse" data-bs-parent="#faq">
              <div class="accordion-body">Contrôles périodiques simples. Batterie échangeable.</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Alternatives -->
    <section class="py-5 bg-light border-top">
      <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-4">
          <h2 class="h4 mb-0">Alternatives proches</h2>
          <a href="index.php#comparateur" class="link-primary">Comparer →</a>
        </div>
        <div class="row g-4">
          <?php foreach($alternatives as $alt): ?>
            <div class="col-md-6">
              <article class="card h-100">
                <div class="row g-0 h-100">
                  <div class="col-4">
                    <img src="<?= htmlspecialchars($alt['image']) ?>" alt="<?= htmlspecialchars($alt['alt_name']) ?>" class="w-100 h-100" style="object-fit:cover">
                  </div>
                  <div class="col-8">
                    <div class="card-body">
                      <?php $altBadge = $alt['tag']==='Industriel'?'bg-success':($alt['tag']==='Médical'?'bg-info':'bg-secondary'); ?>
                      <span class="badge <?= $altBadge ?> mb-2"><?= htmlspecialchars($alt['tag']) ?></span>
                      <h3 class="h6 mb-1"><?= htmlspecialchars($alt['alt_name']) ?></h3>
                      <p class="small text-muted mb-2"><?= htmlspecialchars($alt['summary']) ?></p>
                      <div class="d-flex align-items-center justify-content-between">
                        <strong class="price"><?= price_html($alt['price']) ?></strong>
                        <a href="detail.php?slug=<?= urlencode($alt['alt_slug'] ?? $product['slug']) ?>" class="btn btn-outline-primary btn-sm">Voir</a>
                      </div>
                    </div>
                  </div>
                </div>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

  </main>

  <!-- Barre sticky (mobile) -->
  <div class="product-sticky">
    <div class="container d-flex align-items-center justify-content-between">
      <div class="me-2">
        <div class="small text-white-50"><?= htmlspecialchars($product['name']) ?></div>
        <div class="price"><?= price_html($product['price'], $product['currency']) ?></div>
      </div>
      <div class="d-flex gap-2">
        <a href="#demo" class="btn btn-light btn-sm">Démo</a>
        <a href="#devis" class="btn btn-outline-light btn-sm">Devis</a>
      </div>
    </div>
  </div>

  <!-- FOOTER (identique index.php) -->
  <footer class="pt-5 bg-dark text-white mt-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="d-flex align-items-center mb-3">
            <img src="assets/img/logo.png" alt="Exoleton" width="136" height="50" class="me-2">
          </div>
          <p class="text-white-50">Site d’exosquelettes et technologies d’assistance. Notre mission : rendre la mobilité augmentée accessible à tous.</p>
        </div>
        <div class="col-6 col-md-2">
          <h3 class="h6">Navigation</h3>
          <ul class="list-unstyled">
            <li><a class="footer-link" href="index.php">Accueil</a></li>
            <li><a class="footer-link" href="index.php#produits">Produits</a></li>
            <li><a class="footer-link" href="index.php#comparateur">Comparateur</a></li>
            <li><a class="footer-link" href="index.php#guides">Guides</a></li>
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

  <!-- Cookie modal (optionnel, comme index.php) -->
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

  <!-- JSON-LD Product -->
  <script type="application/ld+json">
  {
    "@context":"https://schema.org",
    "@type":"Product",
    "name":"<?= htmlspecialchars($product['name']) ?>",
    "image": <?= json_encode($galleryImages) ?>,
    "description":"<?= htmlspecialchars($product['baseline']) ?>",
    "category":"<?= htmlspecialchars($product['category']) ?>",
    "brand":{"@type":"Brand","name":"<?= htmlspecialchars($product['brand']) ?>"},
    "offers":{
      "@type":"Offer",
      "priceCurrency":"<?= htmlspecialchars($product['currency']) ?>",
      "price":"<?= $product['price'] ? $product['price'] : 0 ?>",
      "availability":"<?= htmlspecialchars($product['availability']) ?>",
      "url":"https://www.exoleton.com/produits/<?= urlencode($product['slug']) ?>"
    }
  }
  </script>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/main.js"></script>
  <script>
    // Galerie
    document.querySelectorAll('.thumbs .thumb').forEach(function(el){
      el.addEventListener('click', function(){
        document.getElementById('mainImage').src = this.dataset.full;
        document.querySelectorAll('.thumb').forEach(t=>t.classList.remove('active'));
        this.classList.add('active');
      });
    });

    // Estimation devis
    const basePrice = <?= (int)$product['price'] ?>;
    function updateEstimate(){
      const q = parseInt(document.getElementById('qte').value||'1',10);
      const opt = parseInt(document.getElementById('opt').value||'0',10);
      const total = Math.max(0, basePrice)*q + opt*q;
      const fmt = new Intl.NumberFormat('fr-FR').format(total);
      document.getElementById('estimation').textContent = (basePrice>0) ? (fmt + ' €') : 'Sur demande';
    }
    ['qte','opt'].forEach(id=>document.getElementById(id)?.addEventListener('input', updateEstimate));
    updateEstimate();

    // Mini ROI très simplifié (purement indicatif)
    function updateROI(){
      const op = parseInt(document.getElementById('op').value||'0',10);
      const at = parseInt(document.getElementById('at').value||'0',10);
      const lev = parseInt(document.getElementById('lev').value||'0',10);
      // Hypothèses simplifiées: 1 AT évité = 3 000€, gain productivité ~ 0.5% par 100 levages/op
      const gainAT = at * 3000;
      const gainProd = op * (lev/100) * 0.005 * (<?= (int)$product['price'] ?: 4000 ?>); // proxy
      const cout = Math.max(0, basePrice)*Math.max(1, parseInt(document.getElementById('qte').value||'1',10));
      const payback = (gainAT + gainProd) > 0 ? (cout / (gainAT + gainProd)) : Infinity;
      document.getElementById('roiTxt').textContent = isFinite(payback) ? ('Payback estimé : ' + payback.toFixed(1) + ' an(s)') : 'Payback estimé : —';
    }
    ['op','at','lev','qte','opt'].forEach(id=>document.getElementById(id)?.addEventListener('input', updateROI));
    updateROI();

    // Année footer
    const y = document.getElementById('year'); if(y) y.textContent = new Date().getFullYear();
  </script>
</body>
</html>
