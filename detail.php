<?php
require __DIR__ . '/auth.php';

$lang = 'fr';
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

$productStmt = $pdo->prepare(
    "SELECT id, name, slug, summary, baseline, brand, category, tag, price, currency, type, weight, autonomy, charge, main_image, hero_image, bullets, tags
       FROM products
      WHERE slug = :slug
      LIMIT 1"
);
$productStmt->execute(['slug' => $slug]);
$product = $productStmt->fetch();

if (!$product) {
    http_response_code(404);
    echo '<h1>Produit introuvable</h1>';
    exit;
}

$currentUser = current_user($pdo);

$imagesStmt = $pdo->prepare("SELECT url FROM product_images WHERE product_id = :pid ORDER BY sort_order, id");
$imagesStmt->execute(['pid' => $product['id']]);
$galleryImages = $imagesStmt->fetchAll(PDO::FETCH_COLUMN);

$downloadsStmt = $pdo->prepare("SELECT label, href FROM product_downloads WHERE product_id = :pid ORDER BY sort_order, id");
$downloadsStmt->execute(['pid' => $product['id']]);
$downloads = $downloadsStmt->fetchAll();

$useCasesStmt = $pdo->prepare("SELECT title, kpi, description FROM product_use_cases WHERE product_id = :pid ORDER BY sort_order, id");
$useCasesStmt->execute(['pid' => $product['id']]);
$useCases = $useCasesStmt->fetchAll();

$specsStmt = $pdo->prepare("SELECT label, value FROM product_specs WHERE product_id = :pid ORDER BY sort_order, id");
$specsStmt->execute(['pid' => $product['id']]);
$specRows = $specsStmt->fetchAll();

$alternativesStmt = $pdo->prepare("SELECT alt_name, alt_slug, tag, summary, price, image FROM product_alternatives WHERE product_id = :pid ORDER BY sort_order, id");
$alternativesStmt->execute(['pid' => $product['id']]);
$alternatives = $alternativesStmt->fetchAll();

$mainImage = $product['hero_image'] ?: ($product['main_image'] ?: 'assets/img/hero-exosquelette.jpg');
$summary = $product['summary'] ?: ($product['baseline'] ?: '');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($product['name']) ?> – Exoleton</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= htmlspecialchars($summary) ?>">
  <link rel="canonical" href="https://www.exoleton.com/produits/<?= urlencode($product['slug']) ?>">
  <link rel="icon" type="image/x-icon" href="/favicon.ico?v=1">
  <link rel="shortcut icon" href="/favicon.ico?v=1">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/ico.png">
  <link rel="icon" type="image/png" sizes="192x192" href="/assets/img/ico.png">
  <link rel="apple-touch-icon" href="/assets/img/ico.png">
  <meta property="og:title" content="<?= htmlspecialchars($product['name']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($summary) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($mainImage) ?>">
  <meta property="og:type" content="product">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <div class="nav-search-bar" role="group" aria-label="Search">
              <div class="nav-search-select-wrap">
                <label class="visually-hidden" for="navSearchCategory">Category</label>
                <select id="navSearchCategory" name="cat" class="form-select nav-search-select">
                  <option value="">Toutes les catégories</option>
                </select>
                <span class="nav-search-caret" aria-hidden="true">▾</span>
              </div>
              <div class="nav-search-input">
                <label class="visually-hidden" for="navSearchQuery">Search</label>
                <input id="navSearchQuery" name="q" type="search" class="form-control" placeholder="Rechercher un modèle…">
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

  <main class="mt-5 pt-4">
    <section class="product-hero mb-4">
      <img class="hero" src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="object-fit:cover;width:100%;height:360px;">
      <div class="overlay"></div>
      <div class="container position-relative" style="margin-top:-240px;">
        <div class="card shadow-lg border-0">
          <div class="card-body p-4 p-lg-5">
            <span class="badge bg-primary-subtle text-primary mb-2"><?= htmlspecialchars($product['category'] ?? 'Catalogue') ?></span>
            <h1 class="display-6 fw-bold mb-2"><?= htmlspecialchars($product['name']) ?></h1>
            <?php if (!empty($product['brand'])): ?><p class="text-muted mb-2">Marque : <?= htmlspecialchars($product['brand']) ?></p><?php endif; ?>
            <p class="lead mb-3"><?= htmlspecialchars($summary ?: 'Aperçu à venir.') ?></p>
            <?php if ($product['price'] !== null): ?>
              <div class="d-flex align-items-center gap-3">
                <strong class="h4 mb-0">
                  Prix indicatif : <?= number_format((float)$product['price'], 0, ',', ' ') ?> <?= htmlspecialchars($product['currency'] ?? 'EUR') ?>
                </strong>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <div class="container">
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-0">
              <h2 class="h5 mb-0">Description</h2>
            </div>
            <div class="card-body">
              <?= $summary ? '<p class="mb-0">' . htmlspecialchars($summary) . '</p>' : '<p class="text-muted mb-0">Aucune description disponible.</p>'; ?>
            </div>
          </div>

          <div class="card shadow-sm mb-4" id="attributes">
            <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between">
              <h2 class="h5 mb-0">Caractéristiques</h2>
            </div>
            <div class="card-body">
              <?php if (empty($specRows)): ?>
                <p class="text-muted mb-0">Aucune caractéristique renseignée pour le moment.</p>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <tbody>
                      <?php foreach ($specRows as $attr): ?>
                        <tr>
                          <th style="width:40%;"><?= htmlspecialchars($attr['label']) ?></th>
                          <td><?= htmlspecialchars($attr['value']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-0">
              <h2 class="h5 mb-0">Cas d'usage</h2>
            </div>
            <div class="card-body">
              <?php if (empty($useCases)): ?>
                <p class="text-muted mb-0">Pas de cas d'usage renseigné.</p>
              <?php else: ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($useCases as $case): ?>
                    <div class="list-group-item">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <div class="fw-semibold"><?= htmlspecialchars($case['title']) ?></div>
                          <div class="text-muted small"><?= htmlspecialchars($case['description']) ?></div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($case['kpi']) ?></span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card shadow-sm mb-4" id="variants">
            <div class="card-header bg-white border-0">
              <h2 class="h6 mb-0">Tarifs & documents</h2>
            </div>
            <div class="card-body">
              <p class="mb-3">
                <strong>Prix :</strong>
                <?= $product['price'] !== null ? number_format((float)$product['price'], 0, ',', ' ') . ' ' . htmlspecialchars($product['currency'] ?? 'EUR') : 'Sur demande'; ?>
              </p>
              <?php if (!empty($downloads)): ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($downloads as $download): ?>
                    <a class="list-group-item list-group-item-action" href="<?= htmlspecialchars($download['href']) ?>" target="_blank">📄 <?= htmlspecialchars($download['label']) ?></a>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p class="text-muted mb-0">Aucun document téléchargeable pour le moment.</p>
              <?php endif; ?>
            </div>
          </div>

          <div class="card shadow-sm">
            <div class="card-header bg-white border-0">
              <h2 class="h6 mb-0">Galerie</h2>
            </div>
            <div class="card-body">
              <?php if (empty($galleryImages)): ?>
                <p class="text-muted mb-0">Pas d'image disponible.</p>
              <?php else: ?>
                <div class="row g-2">
                  <?php foreach ($galleryImages as $img): ?>
                    <div class="col-6">
                      <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid rounded">
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
