<?php
require __DIR__ . '/auth.php';

$user = require_admin($pdo);
$currentUser = $user;
$statusMessage = null;
$lang = 'fr';

$categories = $pdo->query("SELECT c.id, COALESCE(ci.name, c.code) AS name FROM categories c LEFT JOIN categories_i18n ci ON ci.category_id = c.id AND ci.lang = '$lang' ORDER BY c.sort_order, c.id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create_product':
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO products (category_id, supplier_id, sku, brand, is_active) VALUES (?, NULL, ?, ?, 1)');
                $stmt->execute([
                    (int)($_POST['category_id'] ?? 0),
                    trim($_POST['sku'] ?? ''),
                    trim($_POST['brand'] ?? ''),
                ]);
                $productId = (int)$pdo->lastInsertId();

                $stmt = $pdo->prepare('INSERT INTO products_i18n (product_id, lang, title, slug, description, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $productId,
                    $lang,
                    trim($_POST['title'] ?? ''),
                    trim($_POST['slug'] ?? ''),
                    trim($_POST['description'] ?? ''),
                    trim($_POST['meta_title'] ?? ''),
                    trim($_POST['meta_description'] ?? ''),
                ]);

                if ($_POST['price'] !== '') {
                    $vStmt = $pdo->prepare('INSERT INTO product_variants (product_id, sku, price, vat, stock_qty, stock_reserved, is_active) VALUES (?, ?, ?, 20.00, 0, 0, 1)');
                    $vStmt->execute([$productId, trim($_POST['variant_sku'] ?? $_POST['sku'] ?? ''), (float)$_POST['price']]);
                }

                $pdo->commit();
                $statusMessage = 'Produit créé avec succès.';
                break;

            case 'update_product':
                $productId = (int)($_POST['product_id'] ?? 0);
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE products SET category_id = ?, sku = ?, brand = ?, is_active = ? WHERE id = ?')->execute([
                    (int)($_POST['category_id'] ?? 0),
                    trim($_POST['sku'] ?? ''),
                    trim($_POST['brand'] ?? ''),
                    isset($_POST['is_active']) ? 1 : 0,
                    $productId,
                ]);
                $pdo->prepare('UPDATE products_i18n SET title = ?, slug = ?, description = ?, meta_title = ?, meta_description = ? WHERE product_id = ? AND lang = ?')->execute([
                    trim($_POST['title'] ?? ''),
                    trim($_POST['slug'] ?? ''),
                    trim($_POST['description'] ?? ''),
                    trim($_POST['meta_title'] ?? ''),
                    trim($_POST['meta_description'] ?? ''),
                    $productId,
                    $lang,
                ]);

                if ($_POST['price'] !== '') {
                    $variant = $pdo->prepare('SELECT id FROM product_variants WHERE product_id = ? ORDER BY id LIMIT 1');
                    $variant->execute([$productId]);
                    $existing = $variant->fetchColumn();
                    if ($existing) {
                        $pdo->prepare('UPDATE product_variants SET sku = ?, price = ?, is_active = ? WHERE id = ?')->execute([
                            trim($_POST['variant_sku'] ?? $_POST['sku'] ?? ''),
                            (float)$_POST['price'],
                            isset($_POST['is_active']) ? 1 : 0,
                            $existing,
                        ]);
                    } else {
                        $pdo->prepare('INSERT INTO product_variants (product_id, sku, price, vat, stock_qty, stock_reserved, is_active) VALUES (?, ?, ?, 20.00, 0, 0, 1)')->execute([
                            $productId,
                            trim($_POST['variant_sku'] ?? $_POST['sku'] ?? ''),
                            (float)$_POST['price'],
                        ]);
                    }
                }
                $pdo->commit();
                $statusMessage = 'Produit mis à jour.';
                break;

            case 'delete_product':
                $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
                $stmt->execute([(int)($_POST['product_id'] ?? 0)]);
                $statusMessage = 'Produit supprimé.';
                break;

            case 'create_supplier':
                $stmt = $pdo->prepare('INSERT INTO suppliers (name, website, contact_email, contact_phone, is_active) VALUES (?, ?, ?, ?, 1)');
                $stmt->execute([
                    trim($_POST['name'] ?? ''),
                    trim($_POST['website'] ?? ''),
                    trim($_POST['contact_email'] ?? ''),
                    trim($_POST['contact_phone'] ?? ''),
                ]);
                $statusMessage = 'Fournisseur créé.';
                break;

            case 'add_featured':
                $stmt = $pdo->prepare('INSERT INTO featured_items (product_id, start_at, end_at, priority, is_active) VALUES (?, ?, ?, ?, 1)');
                $stmt->execute([
                    (int)($_POST['product_id'] ?? 0),
                    $_POST['start_at'] ?: date('Y-m-d H:i:s'),
                    $_POST['end_at'] ?: date('Y-m-d H:i:s', strtotime('+30 days')),
                    (int)($_POST['priority'] ?? 0),
                ]);
                $statusMessage = 'Mise en avant ajoutée.';
                break;

            case 'toggle_featured':
                $stmt = $pdo->prepare('UPDATE featured_items SET is_active = ? WHERE id = ?');
                $stmt->execute([(int)($_POST['target_state'] ?? 0), (int)($_POST['featured_id'] ?? 0)]);
                $statusMessage = 'Statut mis à jour.';
                break;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $statusMessage = 'Erreur : ' . $e->getMessage();
    }
}

$products = $pdo->query(
    "SELECT p.id, p.sku, p.brand, p.category_id, p.is_active, pi.title, pi.slug, COALESCE(ci.name, c.code) AS category,
            (SELECT MIN(price) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS min_price
       FROM products p
       INNER JOIN products_i18n pi ON pi.product_id = p.id AND pi.lang = '$lang'
       LEFT JOIN categories c ON c.id = p.category_id
       LEFT JOIN categories_i18n ci ON ci.category_id = c.id AND ci.lang = '$lang'
      ORDER BY pi.title"
)->fetchAll();

$suppliers = $pdo->query('SELECT id, name, website, contact_email, contact_phone, is_active FROM suppliers ORDER BY name')->fetchAll();
$featuredItems = $pdo->query(
    "SELECT fi.id, fi.priority, fi.start_at, fi.end_at, fi.is_active, pi.title AS product_title
       FROM featured_items fi
       INNER JOIN products_i18n pi ON pi.product_id = fi.product_id AND pi.lang = '$lang'
      ORDER BY fi.priority DESC, fi.start_at DESC"
)->fetchAll();
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
              <li><a class="dropdown-item" href="logout.php">Se déconnecter</a></li>
            </ul>
          </li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" style="padding-top: 7rem; padding-bottom: 4rem;">
    <h1 class="h4 mb-3">Administration</h1>
    <p class="text-muted">Gestion simplifiée des produits, fournisseurs et mises en avant après migration de la base.</p>

    <?php if ($statusMessage): ?>
      <div class="alert alert-info"><?= htmlspecialchars($statusMessage) ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Catalogue</h2>
          </div>
          <div class="card-body">
            <?php if (empty($products)): ?>
              <p class="text-muted mb-0">Aucun produit pour l'instant.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table align-middle">
                  <thead class="table-light">
                    <tr><th>Nom</th><th>Catégorie</th><th>Marque</th><th>Prix min</th><th>Statut</th><th></th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($products as $prod): ?>
                      <tr>
                        <td><?= htmlspecialchars($prod['title']) ?></td>
                        <td><?= htmlspecialchars($prod['category'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($prod['brand'] ?? '—') ?></td>
                        <td><?= $prod['min_price'] !== null ? number_format((float)$prod['min_price'], 2, ',', ' ') . ' €' : '—' ?></td>
                        <td><?= $prod['is_active'] ? 'Actif' : 'Inactif' ?></td>
                        <td>
                          <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit-<?= $prod['id'] ?>">Éditer</button>
                        </td>
                      </tr>
                      <tr class="collapse" id="edit-<?= $prod['id'] ?>">
                        <td colspan="6">
                          <form method="post" class="row g-2">
                            <input type="hidden" name="action" value="update_product">
                            <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
                            <div class="col-md-4">
                              <label class="form-label">Titre</label>
                              <input class="form-control" name="title" value="<?= htmlspecialchars($prod['title']) ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Slug</label>
                              <input class="form-control" name="slug" value="<?= htmlspecialchars($prod['slug']) ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Catégorie</label>
                              <select name="category_id" class="form-select">
                                <?php foreach ($categories as $cat): ?>
                                  <option value="<?= (int)$cat['id'] ?>" <?= ($prod['category_id'] ?? null) == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Marque</label>
                              <input class="form-control" name="brand" value="<?= htmlspecialchars($prod['brand']) ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">SKU</label>
                              <input class="form-control" name="sku" value="<?= htmlspecialchars($prod['sku']) ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Prix</label>
                              <input type="number" step="0.01" min="0" class="form-control" name="price" value="<?= htmlspecialchars((string)$prod['min_price']) ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">SKU variante</label>
                              <input class="form-control" name="variant_sku" value="<?= htmlspecialchars($prod['sku']) ?>">
                            </div>
                            <div class="col-md-12">
                              <label class="form-label">Description</label>
                              <textarea class="form-control" name="description" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Meta title</label>
                              <input class="form-control" name="meta_title">
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Meta description</label>
                              <input class="form-control" name="meta_description">
                            </div>
                            <div class="col-md-6 form-check ms-2">
                              <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= $prod['is_active'] ? 'checked' : '' ?>>
                              <label class="form-check-label">Actif</label>
                            </div>
                            <div class="col-12 d-flex gap-2">
                              <button class="btn btn-primary" type="submit">Mettre à jour</button>
                            </div>
                          </form>
                          <form method="post" class="mt-2">
                            <input type="hidden" name="action" value="delete_product">
                            <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm" type="submit" onclick="return confirm('Supprimer ce produit ?');">Supprimer</button>
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

        <div class="card shadow-sm mb-4">
          <div class="card-header bg-white border-0">
            <h2 class="h6 mb-0">Mises en avant</h2>
          </div>
          <div class="card-body">
            <form method="post" class="row g-2 mb-3">
              <input type="hidden" name="action" value="add_featured">
              <div class="col-md-4">
                <label class="form-label">Produit</label>
                <select class="form-select" name="product_id">
                  <?php foreach ($products as $prod): ?>
                    <option value="<?= (int)$prod['id'] ?>"><?= htmlspecialchars($prod['title']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label">Priorité</label>
                <input type="number" class="form-control" name="priority" value="0">
              </div>
              <div class="col-md-3">
                <label class="form-label">Début</label>
                <input type="datetime-local" class="form-control" name="start_at">
              </div>
              <div class="col-md-3">
                <label class="form-label">Fin</label>
                <input type="datetime-local" class="form-control" name="end_at">
              </div>
              <div class="col-12">
                <button class="btn btn-primary" type="submit">Ajouter</button>
              </div>
            </form>

            <?php if (empty($featuredItems)): ?>
              <p class="text-muted mb-0">Aucune mise en avant active.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($featuredItems as $item): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <strong><?= htmlspecialchars($item['product_title']) ?></strong><br>
                      <small class="text-muted">Du <?= htmlspecialchars($item['start_at']) ?> au <?= htmlspecialchars($item['end_at']) ?></small>
                    </div>
                    <form method="post" class="d-flex align-items-center gap-2 mb-0">
                      <input type="hidden" name="action" value="toggle_featured">
                      <input type="hidden" name="featured_id" value="<?= (int)$item['id'] ?>">
                      <input type="hidden" name="target_state" value="<?= $item['is_active'] ? 0 : 1 ?>">
                      <button class="btn btn-sm <?= $item['is_active'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>" type="submit">
                        <?= $item['is_active'] ? 'Désactiver' : 'Activer' ?>
                      </button>
                    </form>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-white border-0">
            <h2 class="h6 mb-0">Ajouter un produit</h2>
          </div>
          <div class="card-body">
            <form method="post" class="row g-2">
              <input type="hidden" name="action" value="create_product">
              <div class="col-12">
                <label class="form-label">Titre</label>
                <input class="form-control" name="title" required>
              </div>
              <div class="col-12">
                <label class="form-label">Slug</label>
                <input class="form-control" name="slug" required>
              </div>
              <div class="col-12">
                <label class="form-label">Catégorie</label>
                <select class="form-select" name="category_id">
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">Marque</label>
                <input class="form-control" name="brand">
              </div>
              <div class="col-6">
                <label class="form-label">SKU</label>
                <input class="form-control" name="sku">
              </div>
              <div class="col-12">
                <label class="form-label">Prix (TTC)</label>
                <input type="number" step="0.01" min="0" class="form-control" name="price">
              </div>
              <div class="col-12">
                <label class="form-label">SKU variante</label>
                <input class="form-control" name="variant_sku">
              </div>
              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" rows="3" name="description"></textarea>
              </div>
              <div class="col-12">
                <label class="form-label">Meta description</label>
                <input class="form-control" name="meta_description">
              </div>
              <div class="col-12">
                <button class="btn btn-primary w-100" type="submit">Créer</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm mb-4">
          <div class="card-header bg-white border-0">
            <h2 class="h6 mb-0">Ajouter un fournisseur</h2>
          </div>
          <div class="card-body">
            <form method="post" class="row g-2">
              <input type="hidden" name="action" value="create_supplier">
              <div class="col-12">
                <label class="form-label">Nom</label>
                <input class="form-control" name="name" required>
              </div>
              <div class="col-12">
                <label class="form-label">Site web</label>
                <input class="form-control" name="website">
              </div>
              <div class="col-12">
                <label class="form-label">Email</label>
                <input class="form-control" name="contact_email">
              </div>
              <div class="col-12">
                <label class="form-label">Téléphone</label>
                <input class="form-control" name="contact_phone">
              </div>
              <div class="col-12">
                <button class="btn btn-outline-primary w-100" type="submit">Ajouter</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-header bg-white border-0">
            <h2 class="h6 mb-0">Fournisseurs</h2>
          </div>
          <div class="card-body">
            <?php if (empty($suppliers)): ?>
              <p class="text-muted mb-0">Aucun fournisseur enregistré.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($suppliers as $supplier): ?>
                  <li class="list-group-item">
                    <div class="fw-semibold"><?= htmlspecialchars($supplier['name']) ?></div>
                    <?php if ($supplier['website']): ?><div><a href="<?= htmlspecialchars($supplier['website']) ?>" target="_blank">Site</a></div><?php endif; ?>
                    <?php if ($supplier['contact_email']): ?><div class="text-muted">Email : <?= htmlspecialchars($supplier['contact_email']) ?></div><?php endif; ?>
                    <?php if ($supplier['contact_phone']): ?><div class="text-muted">Téléphone : <?= htmlspecialchars($supplier['contact_phone']) ?></div><?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
