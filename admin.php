<?php
require __DIR__ . '/auth.php';

$user = require_admin($pdo);
$currentUser = $user;
$statusMessage = null;

// Ajout rétrocompatible des colonnes récentes
try {
  $pdo->query('SELECT is_featured FROM suppliers LIMIT 1');
} catch (Exception $e) {
  $pdo->exec('ALTER TABLE suppliers ADD COLUMN is_featured TINYINT(1) DEFAULT 0');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  try {
    switch ($action) {
      case 'add_product':
        $stmt = $pdo->prepare('INSERT INTO products (slug, name, category, tag, price, currency, summary, type, featured_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
          trim($_POST['slug'] ?? ''),
          trim($_POST['name'] ?? ''),
          trim($_POST['category'] ?? ''),
          trim($_POST['tag'] ?? ''),
          $_POST['price'] !== '' ? (int)$_POST['price'] : null,
          'EUR',
          trim($_POST['summary'] ?? ''),
          trim($_POST['type'] ?? ''),
          isset($_POST['is_featured']) ? max(1, (int)($_POST['featured_order'] ?? 1)) : 0,
        ]);
        $statusMessage = 'Produit ajouté avec succès.';
        break;

      case 'update_product':
        $productId = (int)($_POST['product_id'] ?? 0);
        $featuredOrder = isset($_POST['is_featured']) ? max(1, (int)($_POST['featured_order'] ?? 1)) : 0;
        $stmt = $pdo->prepare('UPDATE products SET slug = ?, name = ?, category = ?, tag = ?, price = ?, summary = ?, type = ?, featured_order = ? WHERE id = ?');
        $stmt->execute([
          trim($_POST['slug'] ?? ''),
          trim($_POST['name'] ?? ''),
          trim($_POST['category'] ?? ''),
          trim($_POST['tag'] ?? ''),
          $_POST['price'] !== '' ? (int)$_POST['price'] : null,
          trim($_POST['summary'] ?? ''),
          trim($_POST['type'] ?? ''),
          $featuredOrder,
          $productId,
        ]);
        $statusMessage = 'Produit mis à jour.';
        break;

      case 'delete_product':
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ? LIMIT 1');
        $stmt->execute([(int)($_POST['product_id'] ?? 0)]);
        $statusMessage = 'Produit supprimé.';
        break;

      case 'toggle_product_featured':
        $stmt = $pdo->prepare('UPDATE products SET featured_order = ? WHERE id = ?');
        $stmt->execute([
          (int)($_POST['target_state'] ?? 0) ? 1 : 0,
          (int)($_POST['product_id'] ?? 0),
        ]);
        $statusMessage = 'Mise en avant du produit mise à jour.';
        break;

      case 'add_supplier':
        $stmt = $pdo->prepare('INSERT INTO suppliers (name, contact_name, email, phone, dropshipping_enabled, notes) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
          trim($_POST['name'] ?? ''),
          trim($_POST['contact_name'] ?? ''),
          trim($_POST['email'] ?? ''),
          trim($_POST['phone'] ?? ''),
          isset($_POST['dropshipping_enabled']) ? 1 : 0,
          trim($_POST['notes'] ?? ''),
        ]);
        $statusMessage = 'Fournisseur ajouté avec succès.';
        break;

      case 'toggle_supplier_featured':
        $stmt = $pdo->prepare('UPDATE suppliers SET is_featured = ? WHERE id = ?');
        $stmt->execute([
          (int)($_POST['target_state'] ?? 0),
          (int)($_POST['supplier_id'] ?? 0),
        ]);
        $statusMessage = 'Statut de mise en avant du fournisseur mis à jour.';
        break;

      case 'add_supplier_link':
        $stmt = $pdo->prepare('INSERT INTO supplier_products (supplier_id, product_id, supplier_sku, buy_price, lead_time_days, is_active) VALUES (?, ?, ?, ?, ?, 1)');
        $stmt->execute([
          (int)($_POST['supplier_id'] ?? 0),
          (int)($_POST['product_id'] ?? 0),
          trim($_POST['supplier_sku'] ?? ''),
          $_POST['buy_price'] !== '' ? (int)$_POST['buy_price'] : null,
          $_POST['lead_time_days'] !== '' ? (int)$_POST['lead_time_days'] : null,
        ]);
        $statusMessage = 'Produit associé au fournisseur.';
        break;

      case 'add_announcement':
        $stmt = $pdo->prepare('INSERT INTO featured_announcements (title, message, link_url, product_id, priority, start_at, end_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
          trim($_POST['title'] ?? ''),
          trim($_POST['message'] ?? ''),
          trim($_POST['link_url'] ?? ''),
          $_POST['product_id'] !== '' ? (int)$_POST['product_id'] : null,
          $_POST['priority'] !== '' ? (int)$_POST['priority'] : 0,
          $_POST['start_at'] !== '' ? $_POST['start_at'] : null,
          $_POST['end_at'] !== '' ? $_POST['end_at'] : null,
          isset($_POST['is_active']) ? 1 : 0,
        ]);
        $statusMessage = 'Annonce ajoutée dans la sélection du moment.';
        break;

      case 'toggle_announcement':
        $stmt = $pdo->prepare('UPDATE featured_announcements SET is_active = ? WHERE id = ?');
        $stmt->execute([
          (int)($_POST['target_state'] ?? 0),
          (int)($_POST['announcement_id'] ?? 0),
        ]);
        $statusMessage = 'Statut de l’annonce mis à jour.';
        break;
    }
  } catch (Exception $e) {
    $statusMessage = 'Erreur : ' . $e->getMessage();
  }
}

$allProductsStmt = $pdo->query('SELECT id, name FROM products ORDER BY name');
$allProducts = $allProductsStmt->fetchAll();

$productsTableStmt = $pdo->query('SELECT id, slug, name, category, tag, price, currency, summary, type, featured_order FROM products ORDER BY name');
$productsTable = $productsTableStmt->fetchAll();

$suppliersStmt = $pdo->query('SELECT s.*, COUNT(sp.id) AS product_links FROM suppliers s LEFT JOIN supplier_products sp ON sp.supplier_id = s.id GROUP BY s.id ORDER BY s.name');
$suppliers = $suppliersStmt->fetchAll();

$supplierProductsStmt = $pdo->query('SELECT sp.id, sp.supplier_id, sp.product_id, sp.supplier_sku, sp.buy_price, sp.lead_time_days, s.name AS supplier_name, p.name AS product_name FROM supplier_products sp INNER JOIN suppliers s ON s.id = sp.supplier_id INNER JOIN products p ON p.id = sp.product_id ORDER BY s.name, p.name');
$supplierProducts = $supplierProductsStmt->fetchAll();

$featuredProductsStmt = $pdo->query('SELECT id, name, slug, category, featured_order FROM products WHERE featured_order > 0 ORDER BY featured_order, name');
$featuredProducts = $featuredProductsStmt->fetchAll();

$featuredSuppliersStmt = $pdo->query('SELECT id, name, contact_name, is_featured FROM suppliers WHERE is_featured = 1 ORDER BY name');
$featuredSuppliers = $featuredSuppliersStmt->fetchAll();

$supplierProductsBySupplier = [];
foreach ($supplierProducts as $link) {
  $supplierProductsBySupplier[$link['supplier_id']][] = $link;
}

$announcementsStmt = $pdo->query('SELECT fa.*, p.name AS product_name FROM featured_announcements fa LEFT JOIN products p ON p.id = fa.product_id ORDER BY fa.priority DESC, fa.start_at DESC, fa.id DESC');
$announcements = $announcementsStmt->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Administration – Exoleton</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEkyG1Yq0C2J5W72st8trL2Q4C2nDvSV2+ULA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="bg-light">
  <header class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="assets/img/logo.png" alt="Exoleton" width="272" height="1000" class="me-2">
      </a>
      <nav class="ms-auto">
        <ul class="navbar-nav align-items-lg-center flex-row gap-3">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Bonjour <?= htmlspecialchars($user['name']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
              <li><a class="dropdown-item" href="account.php">Mon compte</a></li>
              <li><a class="dropdown-item" href="admin.php">Administration</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php">Se déconnecter</a></li>
            </ul>
          </li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" style="padding-top: 7rem; padding-bottom: 4rem;">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4 p-lg-5">
            <h1 class="h4 mb-3">Espace d'administration</h1>
            <p class="text-muted">Pilotage des fournisseurs, du catalogue produits et des mises en avant.</p>

            <?php if ($statusMessage): ?>
              <div class="alert alert-success"><?= htmlspecialchars($statusMessage) ?></div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-outline-primary" href="index.php">Retour au site</a>
              <a class="btn btn-outline-secondary" href="account.php">Mon compte</a>
            </div>

            <hr class="my-4">

            <div class="row g-4">
              <div class="col-lg-4 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                  <div class="card-body">
                    <h2 class="h6 mb-3">Menu d’administration</h2>
                    <div class="nav flex-column nav-pills gap-2" id="adminMenu" role="tablist">
                      <button class="nav-link d-flex align-items-center active" id="tab-overview-tab" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button" role="tab" aria-controls="tab-overview" aria-selected="true">
                        <span>Vue d’ensemble</span>
                        <span class="badge bg-primary-subtle text-primary ms-auto">Stats</span>
                      </button>
                      <button class="nav-link d-flex align-items-center" id="tab-suppliers-tab" data-bs-toggle="pill" data-bs-target="#tab-suppliers" type="button" role="tab" aria-controls="tab-suppliers" aria-selected="false">
                        <span>Fournisseurs</span>
                        <span class="badge bg-primary-subtle text-primary ms-auto"><?= count($suppliers) ?></span>
                      </button>
                      <button class="nav-link d-flex align-items-center" id="tab-products-tab" data-bs-toggle="pill" data-bs-target="#tab-products" type="button" role="tab" aria-controls="tab-products" aria-selected="false">
                        <span>Catalogue produits</span>
                        <span class="badge bg-primary-subtle text-primary ms-auto"><?= count($productsTable) ?></span>
                      </button>
                      <button class="nav-link d-flex align-items-center" id="tab-highlights-tab" data-bs-toggle="pill" data-bs-target="#tab-highlights" type="button" role="tab" aria-controls="tab-highlights" aria-selected="false">
                        <span>Mises en avant</span>
                        <span class="badge bg-primary-subtle text-primary ms-auto"><?= count($featuredProducts) + count($featuredSuppliers) ?></span>
                      </button>
                    </div>
                    <hr>
                    <p class="small text-muted mb-1">Astuce</p>
                    <p class="small text-muted mb-0">Chaque menu dispose de son espace dédié, prêt pour ajouter de nouvelles sections à l’avenir.</p>
                  </div>
                </div>
              </div>

              <div class="col-lg-8 col-xl-9">
                <div class="tab-content" id="adminMenuContent">
                  <div class="tab-pane fade show active" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview-tab">
                    <div class="row g-4 mb-4">
                      <div class="col-md-6 col-xl-3">
                        <div class="card shadow-sm border-0 h-100">
                          <div class="card-body">
                            <p class="text-muted small mb-1">Catalogue produits</p>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                              <span class="h4 mb-0"><?= count($productsTable) ?></span>
                              <span class="badge bg-primary-subtle text-primary">Actifs</span>
                            </div>
                            <p class="small text-muted mb-0">Produits prêts pour la mise en avant ou l’association fournisseur.</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6 col-xl-3">
                        <div class="card shadow-sm border-0 h-100">
                          <div class="card-body">
                            <p class="text-muted small mb-1">Fournisseurs</p>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                              <span class="h4 mb-0"><?= count($suppliers) ?></span>
                              <span class="badge bg-success-subtle text-success">Partenaires</span>
                            </div>
                            <p class="small text-muted mb-0">Contacts disponibles pour relier des produits et activer le dropshipping.</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6 col-xl-3">
                        <div class="card shadow-sm border-0 h-100">
                          <div class="card-body">
                            <p class="text-muted small mb-1">Mises en avant</p>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                              <span class="h4 mb-0"><?= count($featuredProducts) + count($featuredSuppliers) ?></span>
                              <span class="badge bg-warning-subtle text-warning">Prioritaires</span>
                            </div>
                            <p class="small text-muted mb-0">Produits et fournisseurs affichés en priorité sur le site.</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6 col-xl-3">
                        <div class="card shadow-sm border-0 h-100">
                          <div class="card-body">
                            <p class="text-muted small mb-1">Annonces</p>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                              <span class="h4 mb-0"><?= count($announcements) ?></span>
                              <span class="badge bg-info-subtle text-info">Actives</span>
                            </div>
                            <p class="small text-muted mb-0">Messages en page d’accueil pour guider les visiteurs.</p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="card shadow-sm border-0">
                      <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                          <div>
                            <h2 class="h5 mb-1">Actions rapides</h2>
                            <p class="text-muted small mb-0">Accédez directement aux formulaires d’ajout dans la colonne centrale.</p>
                          </div>
                          <span class="badge bg-light text-dark">Tout est regroupé ici</span>
                        </div>
                        <div class="row g-3">
                          <div class="col-md-4">
                            <div class="card h-100 border-0 bg-primary-subtle text-primary">
                              <div class="card-body d-flex flex-column">
                                <div class="fw-semibold mb-1">Ajouter un produit</div>
                                <p class="small mb-3">Créez une fiche détaillée et préparez-la pour la mise en avant.</p>
                                <button class="btn btn-primary mt-auto" data-bs-toggle="pill" data-bs-target="#tab-products" type="button">Ouvrir le catalogue</button>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-4">
                            <div class="card h-100 border-0 bg-success-subtle text-success">
                              <div class="card-body d-flex flex-column">
                                <div class="fw-semibold mb-1">Ajouter un fournisseur</div>
                                <p class="small mb-3">Ajoutez un partenaire et connectez-le à vos références.</p>
                                <button class="btn btn-success mt-auto" data-bs-toggle="pill" data-bs-target="#tab-suppliers" type="button">Gérer les fournisseurs</button>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-4">
                            <div class="card h-100 border-0 bg-warning-subtle text-warning">
                              <div class="card-body d-flex flex-column">
                                <div class="fw-semibold mb-1">Mettre en avant</div>
                                <p class="small mb-3">Choisissez les annonces et sélections visibles sur la page d’accueil.</p>
                                <button class="btn btn-warning text-dark mt-auto" data-bs-toggle="pill" data-bs-target="#tab-highlights" type="button">Ouvrir les mises en avant</button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="tab-pane fade" id="tab-suppliers" role="tabpanel" aria-labelledby="tab-suppliers-tab">
                    <div class="row g-4">
                      <div class="col-xl-7">
                        <div class="card shadow-sm border-0 h-100">
                          <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                              <div>
                                <h2 class="h5 mb-0">Fournisseurs</h2>
                                <p class="text-muted small mb-0">Liste détaillée avec produits connectés et statut d’activation.</p>
                              </div>
                              <span class="badge bg-primary-subtle text-primary"><?= count($suppliers) ?> fournisseurs</span>
                            </div>

                            <?php if (!empty($suppliers)): ?>
                              <div class="accordion" id="supplierAccordion">
                                <?php foreach ($suppliers as $supplier): ?>
                                  <?php $links = $supplierProductsBySupplier[$supplier['id']] ?? []; ?>
                                  <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-<?= (int)$supplier['id'] ?>">
                                      <button class="accordion-button collapsed d-flex flex-wrap gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= (int)$supplier['id'] ?>" aria-expanded="false" aria-controls="collapse-<?= (int)$supplier['id'] ?>">
                                        <span class="fw-semibold me-2"><?= htmlspecialchars($supplier['name']) ?></span>
                                        <?php if ($supplier['is_featured'] ?? false): ?>
                                          <span class="badge bg-warning text-dark">Mise en avant</span>
                                        <?php endif; ?>
                                        <?php if (!$supplier['dropshipping_enabled']): ?>
                                          <span class="badge bg-secondary">Dropshipping inactif</span>
                                        <?php endif; ?>
                                        <span class="badge bg-light text-dark ms-auto"><?= count($links) ?> produit(s)</span>
                                      </button>
                                    </h2>
                                    <div id="collapse-<?= (int)$supplier['id'] ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= (int)$supplier['id'] ?>" data-bs-parent="#supplierAccordion">
                                      <div class="accordion-body">
                                        <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
                                          <div>
                                            <div class="fw-semibold">Contact</div>
                                            <div class="text-muted small"><?= htmlspecialchars($supplier['contact_name'] ?: '—') ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($supplier['email'] ?: '') ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($supplier['phone'] ?: '') ?></div>
                                          </div>
                                          <div class="ms-auto">
                                            <form method="post" class="d-inline">
                                              <input type="hidden" name="action" value="toggle_supplier_featured">
                                              <input type="hidden" name="supplier_id" value="<?= (int)$supplier['id'] ?>">
                                              <input type="hidden" name="target_state" value="<?= $supplier['is_featured'] ? 0 : 1 ?>">
                                              <button class="btn btn-sm <?= $supplier['is_featured'] ? 'btn-warning' : 'btn-outline-warning' ?>" type="submit"><?= $supplier['is_featured'] ? 'Retirer de la mise en avant' : 'Mettre en avant' ?></button>
                                            </form>
                                          </div>
                                        </div>
                                        <?php if (!empty($supplier['notes'])): ?>
                                          <p class="small bg-light p-2 rounded">Notes internes : <?= nl2br(htmlspecialchars($supplier['notes'])) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($links)): ?>
                                          <div class="table-responsive">
                                            <table class="table table-sm align-middle">
                                              <thead>
                                                <tr>
                                                  <th>Produit</th>
                                                  <th>SKU</th>
                                                  <th class="text-end">Prix d'achat</th>
                                                  <th class="text-end">Délai</th>
                                                </tr>
                                              </thead>
                                              <tbody>
                                                <?php foreach ($links as $link): ?>
                                                  <tr>
                                                    <td><?= htmlspecialchars($link['product_name']) ?></td>
                                                    <td class="text-muted small"><?= htmlspecialchars($link['supplier_sku'] ?: '—') ?></td>
                                                    <td class="text-end"><?= $link['buy_price'] ? number_format((int)$link['buy_price'], 0, ',', ' ') . ' €' : '—' ?></td>
                                                    <td class="text-end"><?= $link['lead_time_days'] !== null ? (int)$link['lead_time_days'] . ' j' : '—' ?></td>
                                                  </tr>
                                                <?php endforeach; ?>
                                              </tbody>
                                            </table>
                                          </div>
                                        <?php else: ?>
                                          <p class="text-muted small mb-0">Aucun produit lié pour l’instant.</p>
                                        <?php endif; ?>
                                      </div>
                                    </div>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            <?php else: ?>
                              <div class="alert alert-light border">Aucun fournisseur enregistré. Ajoutez-en un via le formulaire ci-contre.</div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>

                      <div class="col-xl-5">
                        <button type="button" class="card shadow-sm border-0 mb-3 text-start w-100" data-bs-toggle="modal" data-bs-target="#modalAddSupplier">
                          <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                              <h2 class="h6 mb-1">Ajouter un fournisseur</h2>
                              <p class="text-muted small mb-0">Création rapide d’une fiche fournisseur avec contact et statut dropshipping.</p>
                            </div>
                            <span class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" aria-hidden="true">
                              <i class="fa-solid fa-user-plus"></i>
                              <span class="fw-semibold">Modal</span>
                            </span>
                          </div>
                        </button>

                        <button type="button" class="card shadow-sm border-0 text-start w-100" data-bs-toggle="modal" data-bs-target="#modalLinkSupplier">
                          <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                              <h2 class="h6 mb-1">Lier un produit à un fournisseur</h2>
                              <p class="text-muted small mb-0">Associer un produit existant à un fournisseur.</p>
                            </div>
                            <span class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2" aria-hidden="true">
                              <i class="fa-solid fa-link"></i>
                              <span class="fw-semibold">Modal</span>
                            </span>
                          </div>
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="tab-pane fade" id="tab-products" role="tabpanel" aria-labelledby="tab-products-tab">
                    <div class="card shadow-sm border-0 mb-4">
                      <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                          <div>
                            <h2 class="h5 mb-0">Catalogue produits (CRUD)</h2>
                            <p class="text-muted small mb-0">Créer, modifier ou supprimer les produits et gérer leur mise en avant.</p>
                          </div>
                          <span class="badge bg-primary-subtle text-primary"><?= count($productsTable) ?> produits</span>
                        </div>

                        <div class="row g-4">
                          <div class="col-lg-5">
                            <div class="border rounded p-3 bg-light h-100">
                              <h3 class="h6">Ajouter un produit</h3>
                              <form method="post" class="vstack gap-3">
                                <input type="hidden" name="action" value="add_product">
                                <div class="row g-3">
                                  <div class="col-sm-6">
                                    <label class="form-label">Nom</label>
                                    <input type="text" name="name" class="form-control" required>
                                  </div>
                                  <div class="col-sm-6">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug" class="form-control" placeholder="exolift-2" required>
                                  </div>
                                  <div class="col-sm-6">
                                    <label class="form-label">Catégorie</label>
                                    <input type="text" name="category" class="form-control" placeholder="Industriel" required>
                                  </div>
                                  <div class="col-sm-6">
                                    <label class="form-label">Tag</label>
                                    <input type="text" name="tag" class="form-control" placeholder="Logistique" required>
                                  </div>
                                  <div class="col-sm-6">
                                    <label class="form-label">Type</label>
                                    <input type="text" name="type" class="form-control" placeholder="Actif / Passif">
                                  </div>
                                  <div class="col-sm-6">
                                    <label class="form-label">Prix (€)</label>
                                    <input type="number" name="price" class="form-control" min="0" step="1">
                                  </div>
                                  <div class="col-12">
                                    <label class="form-label">Résumé</label>
                                    <textarea name="summary" class="form-control" rows="2" placeholder="Pitch court"></textarea>
                                  </div>
                                  <div class="col-12 form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="product_featured" name="is_featured">
                                    <label class="form-check-label" for="product_featured">Mettre en avant</label>
                                  </div>
                                  <div class="col-12">
                                    <label class="form-label">Ordre de mise en avant</label>
                                    <input type="number" name="featured_order" class="form-control" min="1" value="1">
                                  </div>
                                </div>
                                <button class="btn btn-success" type="submit">Créer le produit</button>
                              </form>
                            </div>
                          </div>

                          <div class="col-lg-7">
                            <div class="list-group">
                              <?php foreach ($productsTable as $product): ?>
                                <div class="list-group-item">
                                  <form method="post" class="row g-2 align-items-end">
                                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                    <div class="col-sm-6 col-md-4">
                                      <label class="form-label small">Nom</label>
                                      <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($product['name']) ?>" required>
                                    </div>
                                    <div class="col-sm-6 col-md-4">
                                      <label class="form-label small">Slug</label>
                                      <input type="text" name="slug" class="form-control form-control-sm" value="<?= htmlspecialchars($product['slug']) ?>" required>
                                    </div>
                                    <div class="col-sm-6 col-md-4">
                                      <label class="form-label small">Catégorie</label>
                                      <input type="text" name="category" class="form-control form-control-sm" value="<?= htmlspecialchars($product['category']) ?>" required>
                                    </div>
                                    <div class="col-sm-6 col-md-4">
                                      <label class="form-label small">Tag</label>
                                      <input type="text" name="tag" class="form-control form-control-sm" value="<?= htmlspecialchars($product['tag']) ?>" required>
                                    </div>
                                    <div class="col-sm-6 col-md-4">
                                      <label class="form-label small">Type</label>
                                      <input type="text" name="type" class="form-control form-control-sm" value="<?= htmlspecialchars($product['type']) ?>">
                                    </div>
                                    <div class="col-sm-6 col-md-4">
                                      <label class="form-label small">Prix (€)</label>
                                      <input type="number" name="price" class="form-control form-control-sm" min="0" step="1" value="<?= htmlspecialchars((string)$product['price']) ?>">
                                    </div>
                                    <div class="col-12">
                                      <label class="form-label small">Résumé</label>
                                      <input type="text" name="summary" class="form-control form-control-sm" value="<?= htmlspecialchars($product['summary']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                      <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" value="1" id="featured-<?= (int)$product['id'] ?>" name="is_featured" <?= $product['featured_order'] > 0 ? 'checked' : '' ?>>
                                        <label class="form-check-label small" for="featured-<?= (int)$product['id'] ?>">Mettre en avant</label>
                                      </div>
                                    </div>
                                    <div class="col-md-4">
                                      <label class="form-label small">Ordre</label>
                                      <input type="number" name="featured_order" class="form-control form-control-sm" min="1" value="<?= (int)max(1, (int)$product['featured_order']) ?>">
                                    </div>
                                    <div class="col-md-4 text-end">
                                      <button class="btn btn-sm btn-primary" type="submit" name="action" value="update_product">Sauvegarder</button>
                                      <button class="btn btn-sm btn-outline-danger" type="submit" name="action" value="delete_product" onclick="return confirm('Supprimer ce produit ?');">Supprimer</button>
                                    </div>
                                  </form>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="tab-pane fade" id="tab-highlights" role="tabpanel" aria-labelledby="tab-highlights-tab">
                    <div class="card shadow-sm border-0">
                      <div class="card-body">
                        <div class="row g-4">
                          <div class="col-lg-5">
                            <h2 class="h5">Mises en avant</h2>
                            <p class="text-muted small">Gestion des produits et fournisseurs mis en avant sur le site.</p>

                            <h3 class="h6 mt-3">Produits mis en avant</h3>
                            <?php if (!empty($featuredProducts)): ?>
                              <ul class="list-group list-group-flush mb-3">
                                <?php foreach ($featuredProducts as $product): ?>
                                  <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                      <span class="fw-semibold"><?= htmlspecialchars($product['name']) ?></span>
                                      <span class="text-muted small">(<?= htmlspecialchars($product['category']) ?>)</span>
                                    </span>
                                    <form method="post" class="d-inline">
                                      <input type="hidden" name="action" value="toggle_product_featured">
                                      <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                      <input type="hidden" name="target_state" value="0">
                                      <button class="btn btn-sm btn-outline-secondary" type="submit">Retirer</button>
                                    </form>
                                  </li>
                                <?php endforeach; ?>
                              </ul>
                            <?php else: ?>
                              <p class="text-muted small">Aucun produit mis en avant.</p>
                            <?php endif; ?>

                            <h3 class="h6 mt-3">Fournisseurs mis en avant</h3>
                            <?php if (!empty($featuredSuppliers)): ?>
                              <ul class="list-group list-group-flush mb-3">
                                <?php foreach ($featuredSuppliers as $supplier): ?>
                                  <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold"><?= htmlspecialchars($supplier['name']) ?></span>
                                    <span class="text-muted small ms-2">— Contact : <span class="fw-normal"><?= htmlspecialchars($supplier['contact_name'] ?: '') ?></span></span>
                                    <form method="post" class="d-inline">
                                      <input type="hidden" name="action" value="toggle_supplier_featured">
                                      <input type="hidden" name="supplier_id" value="<?= (int)$supplier['id'] ?>">
                                      <input type="hidden" name="target_state" value="0">
                                      <button class="btn btn-sm btn-outline-secondary" type="submit">Retirer</button>
                                    </form>
                                  </li>
                                <?php endforeach; ?>
                              </ul>
                            <?php else: ?>
                              <p class="text-muted small">Aucun fournisseur mis en avant.</p>
                            <?php endif; ?>
                          </div>

                          <div class="col-lg-7">
                            <div class="d-flex align-items-center justify-content-between">
                              <div>
                                <h2 class="h5 mb-1">Annonces "Sélection du moment"</h2>
                                <p class="text-muted small mb-0">Définissez les messages et produits à mettre en avant sur la page d’accueil.</p>
                              </div>
                              <button class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" type="button" data-bs-toggle="modal" data-bs-target="#modalAnnouncements" aria-label="Ouvrir la gestion des annonces">
                                <i class="fa-solid fa-bullhorn"></i>
                                <span class="fw-semibold">Ouvrir le modal</span>
                              </button>
                            </div>
                            <p class="text-muted small mt-3 mb-0">Cliquez sur l’icône pour accéder aux formulaires de création et au tableau des annonces.</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Modals -->
  <div class="modal fade" id="modalAddSupplier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Ajouter un fournisseur</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <form method="post" class="vstack gap-3">
            <input type="hidden" name="action" value="add_supplier">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nom du fournisseur</label>
                <input type="text" name="name" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Contact</label>
                <input type="text" name="contact_name" class="form-control" placeholder="Nom du contact">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="contact@exemple.com">
              </div>
              <div class="col-md-6">
                <label class="form-label">Téléphone</label>
                <input type="text" name="phone" class="form-control" placeholder="+33 ...">
              </div>
              <div class="col-12">
                <label class="form-label">Notes internes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Conditions, frais, zones livrées…"></textarea>
              </div>
              <div class="col-12 form-check">
                <input class="form-check-input" type="checkbox" value="1" id="dropshipping_enabled" name="dropshipping_enabled" checked>
                <label class="form-check-label" for="dropshipping_enabled">Dropshipping activé</label>
              </div>
            </div>
            <div class="text-end">
              <button class="btn btn-primary" type="submit">Ajouter le fournisseur</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalLinkSupplier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Lier un produit à un fournisseur</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <form method="post" class="row g-3 align-items-end">
            <input type="hidden" name="action" value="add_supplier_link">
            <div class="col-12">
              <label class="form-label">Fournisseur</label>
              <select name="supplier_id" class="form-select" required>
                <option value="">Sélectionner…</option>
                <?php foreach ($suppliers as $supplier): ?>
                  <option value="<?= (int)$supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Produit</label>
              <select name="product_id" class="form-select" required>
                <option value="">Sélectionner…</option>
                <?php foreach ($allProducts as $product): ?>
                  <option value="<?= (int)$product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">SKU fournisseur</label>
              <input type="text" name="supplier_sku" class="form-control" placeholder="Référence interne">
            </div>
            <div class="col-md-4">
              <label class="form-label">Prix d'achat (€)</label>
              <input type="number" name="buy_price" class="form-control" min="0" step="1" placeholder="HT">
            </div>
            <div class="col-md-4">
              <label class="form-label">Délai (jours)</label>
              <input type="number" name="lead_time_days" class="form-control" min="0" step="1" placeholder="0">
            </div>
            <div class="col-12">
              <button class="btn btn-primary w-100" type="submit">Associer</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalAnnouncements" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Gestion des annonces</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <form method="post" class="row g-3 align-items-end">
            <input type="hidden" name="action" value="add_announcement">
            <div class="col-md-4">
              <label class="form-label">Titre</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Message court</label>
              <input type="text" name="message" class="form-control" placeholder="Ce qui rend l’offre unique">
            </div>
            <div class="col-md-4">
              <label class="form-label">URL personnalisée</label>
              <input type="url" name="link_url" class="form-control" placeholder="https://… (facultatif)">
            </div>
            <div class="col-md-3">
              <label class="form-label">Produit lié</label>
              <select name="product_id" class="form-select">
                <option value="">Aucun</option>
                <?php foreach ($allProducts as $product): ?>
                  <option value="<?= (int)$product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Priorité</label>
              <input type="number" name="priority" class="form-control" value="0" step="1">
            </div>
            <div class="col-md-3">
              <label class="form-label">Début</label>
              <input type="datetime-local" name="start_at" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">Fin</label>
              <input type="datetime-local" name="end_at" class="form-control">
            </div>
            <div class="col-12 form-check">
              <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" checked>
              <label class="form-check-label" for="is_active">Activer immédiatement</label>
            </div>
            <div class="col-12">
              <button class="btn btn-primary" type="submit">Ajouter l’annonce</button>
            </div>
          </form>

          <?php if (!empty($announcements)): ?>
            <div class="table-responsive mt-4">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>Titre</th>
                    <th>Produit/URL</th>
                    <th>Période</th>
                    <th>Priorité</th>
                    <th class="text-end">Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($announcements as $announcement): ?>
                    <tr>
                      <td>
                        <strong><?= htmlspecialchars($announcement['title']) ?></strong><br>
                        <span class="text-muted small"><?= htmlspecialchars($announcement['message'] ?: '—') ?></span>
                      </td>
                      <td class="small">
                        <?php if (!empty($announcement['product_name'])): ?>
                          Produit : <?= htmlspecialchars($announcement['product_name']) ?><br>
                        <?php endif; ?>
                        <?= htmlspecialchars($announcement['link_url'] ?: '—') ?>
                      </td>
                      <td class="small text-muted">
                        <?= $announcement['start_at'] ? htmlspecialchars($announcement['start_at']) : '—' ?>
                        →
                        <?= $announcement['end_at'] ? htmlspecialchars($announcement['end_at']) : '—' ?>
                      </td>
                      <td><?= (int)$announcement['priority'] ?></td>
                      <td class="text-end">
                        <form method="post" class="d-inline">
                          <input type="hidden" name="action" value="toggle_announcement">
                          <input type="hidden" name="announcement_id" value="<?= (int)$announcement['id'] ?>">
                          <input type="hidden" name="target_state" value="<?= $announcement['is_active'] ? 0 : 1 ?>">
                          <button class="btn btn-sm <?= $announcement['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>" type="submit">
                            <?= $announcement['is_active'] ? 'Actif' : 'Inactif' ?>
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
