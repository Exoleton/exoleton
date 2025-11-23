<?php
require __DIR__ . '/auth.php';

$user = require_admin($pdo);
$currentUser = $user;
$statusMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  try {
    switch ($action) {
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

$suppliersStmt = $pdo->query('SELECT s.*, COUNT(sp.id) AS product_links FROM suppliers s LEFT JOIN supplier_products sp ON sp.supplier_id = s.id GROUP BY s.id ORDER BY s.name');
$suppliers = $suppliersStmt->fetchAll();

$supplierProductsStmt = $pdo->query('SELECT sp.id, sp.supplier_id, sp.product_id, sp.supplier_sku, sp.buy_price, sp.lead_time_days, s.name AS supplier_name, p.name AS product_name FROM supplier_products sp INNER JOIN suppliers s ON s.id = sp.supplier_id INNER JOIN products p ON p.id = sp.product_id ORDER BY s.name, p.name');
$supplierProducts = $supplierProductsStmt->fetchAll();

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
            <p class="text-muted">Vous êtes connecté avec les droits administrateur. Ajoutez ici vos outils de gestion (catalogue, utilisateurs, contenu…).</p>

            <div class="alert alert-info">Cet espace est prêt à accueillir les modules back-office (gestion des produits, des guides, des utilisateurs…).</div>

            <?php if ($statusMessage): ?>
              <div class="alert alert-success"><?= htmlspecialchars($statusMessage) ?></div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-outline-primary" href="index.php">Retour au site</a>
              <a class="btn btn-outline-secondary" href="account.php">Mon compte</a>
            </div>

            <hr class="my-4">

            <div class="row g-4">
              <div class="col-lg-6">
                <div class="card h-100 shadow-sm border-0">
                  <div class="card-body">
                    <h2 class="h5">Fournisseurs (dropshipping)</h2>
                    <p class="text-muted small">Ajoutez les fournisseurs et activez les partenariats en dropshipping.</p>

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
                      <div>
                        <button class="btn btn-primary" type="submit">Ajouter le fournisseur</button>
                      </div>
                    </form>

                    <?php if (!empty($suppliers)): ?>
                      <div class="table-responsive mt-4">
                        <table class="table table-sm align-middle">
                          <thead>
                            <tr>
                              <th>Nom</th>
                              <th>Contact</th>
                              <th class="text-end">Produits liés</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($suppliers as $supplier): ?>
                              <tr>
                                <td><?= htmlspecialchars($supplier['name']) ?><?= $supplier['dropshipping_enabled'] ? '' : ' <span class="badge bg-secondary">Off</span>' ?></td>
                                <td class="text-muted small">
                                  <?= htmlspecialchars($supplier['contact_name'] ?: '—') ?><br>
                                  <?= htmlspecialchars($supplier['email'] ?: ''); ?>
                                </td>
                                <td class="text-end"><span class="badge bg-light text-dark"><?= (int)$supplier['product_links'] ?></span></td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="col-lg-6">
                <div class="card h-100 shadow-sm border-0">
                  <div class="card-body">
                    <h2 class="h5">Produits liés aux fournisseurs</h2>
                    <p class="text-muted small">Associez un produit catalogue à un fournisseur et précisez les conditions d’achat.</p>

                    <form method="post" class="row g-3 align-items-end">
                      <input type="hidden" name="action" value="add_supplier_link">
                      <div class="col-md-6">
                        <label class="form-label">Fournisseur</label>
                        <select name="supplier_id" class="form-select" required>
                          <option value="">Sélectionner…</option>
                          <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= (int)$supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-6">
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
                        <button class="btn btn-primary" type="submit">Associer</button>
                      </div>
                    </form>

                    <?php if (!empty($supplierProducts)): ?>
                      <div class="table-responsive mt-4">
                        <table class="table table-sm align-middle">
                          <thead>
                            <tr>
                              <th>Fournisseur</th>
                              <th>Produit</th>
                              <th>SKU</th>
                              <th class="text-end">Prix d'achat</th>
                              <th class="text-end">Délai</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($supplierProducts as $link): ?>
                              <tr>
                                <td><?= htmlspecialchars($link['supplier_name']) ?></td>
                                <td><?= htmlspecialchars($link['product_name']) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($link['supplier_sku'] ?: '—') ?></td>
                                <td class="text-end"><?= $link['buy_price'] ? number_format((int)$link['buy_price'], 0, ',', ' ') . ' €' : '—' ?></td>
                                <td class="text-end"><?= $link['lead_time_days'] !== null ? (int)$link['lead_time_days'] . ' j' : '—' ?></td>
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

            <hr class="my-4">

            <div class="card shadow-sm border-0">
              <div class="card-body">
                <h2 class="h5">Annonces "Sélection du moment"</h2>
                <p class="text-muted small">Définissez les messages et produits à mettre en avant sur la page d’accueil.</p>

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
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
