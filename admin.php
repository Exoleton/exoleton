<?php
require __DIR__ . '/auth.php';

$user = require_admin($pdo);
$currentUser = $user;
$statusMessage = null;

function sanitize_field(?string $value): string
{
    return trim((string)$value);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create_product':
                $stmt = $pdo->prepare('INSERT INTO products (slug, name, category, tag, price, currency, summary, baseline, brand, availability, type, weight, autonomy, charge, main_image, hero_image, bullets, tags, featured_order) VALUES (:slug, :name, :category, :tag, :price, :currency, :summary, :baseline, :brand, :availability, :type, :weight, :autonomy, :charge, :main_image, :hero_image, :bullets, :tags, :featured_order)');
                $stmt->execute([
                    'slug' => sanitize_field($_POST['slug'] ?? ''),
                    'name' => sanitize_field($_POST['name'] ?? ''),
                    'category' => sanitize_field($_POST['category'] ?? ''),
                    'tag' => sanitize_field($_POST['tag'] ?? ''),
                    'price' => $_POST['price'] === '' ? null : (int)$_POST['price'],
                    'currency' => sanitize_field($_POST['currency'] ?? 'EUR') ?: 'EUR',
                    'summary' => sanitize_field($_POST['summary'] ?? ''),
                    'baseline' => sanitize_field($_POST['baseline'] ?? ''),
                    'brand' => sanitize_field($_POST['brand'] ?? ''),
                    'availability' => sanitize_field($_POST['availability'] ?? 'https://schema.org/InStock') ?: 'https://schema.org/InStock',
                    'type' => sanitize_field($_POST['type'] ?? ''),
                    'weight' => sanitize_field($_POST['weight'] ?? ''),
                    'autonomy' => sanitize_field($_POST['autonomy'] ?? ''),
                    'charge' => sanitize_field($_POST['charge'] ?? ''),
                    'main_image' => sanitize_field($_POST['main_image'] ?? ''),
                    'hero_image' => sanitize_field($_POST['hero_image'] ?? ''),
                    'bullets' => sanitize_field($_POST['bullets'] ?? ''),
                    'tags' => sanitize_field($_POST['tags'] ?? ''),
                    'featured_order' => (int)($_POST['featured_order'] ?? 0),
                ]);
                $statusMessage = 'Produit créé avec succès.';
                break;

            case 'update_product':
                $productId = (int)($_POST['product_id'] ?? 0);
                $stmt = $pdo->prepare('UPDATE products SET slug = :slug, name = :name, category = :category, tag = :tag, price = :price, currency = :currency, summary = :summary, baseline = :baseline, brand = :brand, availability = :availability, type = :type, weight = :weight, autonomy = :autonomy, charge = :charge, main_image = :main_image, hero_image = :hero_image, bullets = :bullets, tags = :tags, featured_order = :featured_order WHERE id = :id');
                $stmt->execute([
                    'slug' => sanitize_field($_POST['slug'] ?? ''),
                    'name' => sanitize_field($_POST['name'] ?? ''),
                    'category' => sanitize_field($_POST['category'] ?? ''),
                    'tag' => sanitize_field($_POST['tag'] ?? ''),
                    'price' => $_POST['price'] === '' ? null : (int)$_POST['price'],
                    'currency' => sanitize_field($_POST['currency'] ?? 'EUR') ?: 'EUR',
                    'summary' => sanitize_field($_POST['summary'] ?? ''),
                    'baseline' => sanitize_field($_POST['baseline'] ?? ''),
                    'brand' => sanitize_field($_POST['brand'] ?? ''),
                    'availability' => sanitize_field($_POST['availability'] ?? 'https://schema.org/InStock') ?: 'https://schema.org/InStock',
                    'type' => sanitize_field($_POST['type'] ?? ''),
                    'weight' => sanitize_field($_POST['weight'] ?? ''),
                    'autonomy' => sanitize_field($_POST['autonomy'] ?? ''),
                    'charge' => sanitize_field($_POST['charge'] ?? ''),
                    'main_image' => sanitize_field($_POST['main_image'] ?? ''),
                    'hero_image' => sanitize_field($_POST['hero_image'] ?? ''),
                    'bullets' => sanitize_field($_POST['bullets'] ?? ''),
                    'tags' => sanitize_field($_POST['tags'] ?? ''),
                    'featured_order' => (int)($_POST['featured_order'] ?? 0),
                    'id' => $productId,
                ]);
                $statusMessage = 'Produit mis à jour.';
                break;

            case 'delete_product':
                $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
                $stmt->execute([(int)($_POST['product_id'] ?? 0)]);
                $statusMessage = 'Produit supprimé.';
                break;

            case 'create_supplier':
                $stmt = $pdo->prepare('INSERT INTO suppliers (name, contact_name, email, phone, dropshipping_enabled, is_featured, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    sanitize_field($_POST['name'] ?? ''),
                    sanitize_field($_POST['contact_name'] ?? ''),
                    sanitize_field($_POST['email'] ?? ''),
                    sanitize_field($_POST['phone'] ?? ''),
                    isset($_POST['dropshipping_enabled']) ? 1 : 0,
                    isset($_POST['is_featured']) ? 1 : 0,
                    sanitize_field($_POST['notes'] ?? ''),
                ]);
                $statusMessage = 'Fournisseur créé.';
                break;

            case 'add_featured':
                $stmt = $pdo->prepare('INSERT INTO featured_announcements (title, message, link_url, product_id, priority, start_at, end_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute([
                    sanitize_field($_POST['title'] ?? ''),
                    sanitize_field($_POST['message'] ?? ''),
                    sanitize_field($_POST['link_url'] ?? ''),
                    $_POST['product_id'] !== '' ? (int)$_POST['product_id'] : null,
                    (int)($_POST['priority'] ?? 0),
                    $_POST['start_at'] ?: null,
                    $_POST['end_at'] ?: null,
                ]);
                $statusMessage = 'Annonce ajoutée.';
                break;

            case 'toggle_featured':
                $stmt = $pdo->prepare('UPDATE featured_announcements SET is_active = ? WHERE id = ?');
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
    "SELECT id, slug, name, category, tag, price, currency, brand, availability, type, weight, autonomy, charge, main_image, hero_image, summary, baseline, bullets, tags, featured_order
       FROM products
      ORDER BY featured_order ASC, id ASC"
)->fetchAll();

$suppliers = $pdo->query('SELECT id, name, contact_name, email, phone, dropshipping_enabled, is_featured, notes FROM suppliers ORDER BY name')->fetchAll();
$featuredItems = $pdo->query(
    "SELECT fa.id, fa.title, fa.priority, fa.start_at, fa.end_at, fa.is_active, fa.link_url, p.name AS product_title
       FROM featured_announcements fa
       LEFT JOIN products p ON p.id = fa.product_id
      ORDER BY fa.priority DESC, fa.start_at DESC, fa.id DESC"
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
                    <tr><th>Nom</th><th>Catégorie</th><th>Marque</th><th>Prix</th><th></th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($products as $prod): ?>
                      <tr>
                        <td><?= htmlspecialchars($prod['name']) ?></td>
                        <td><?= htmlspecialchars($prod['category'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($prod['brand'] ?? '—') ?></td>
                        <td><?= $prod['price'] !== null ? number_format((float)$prod['price'], 0, ',', ' ') . ' ' . htmlspecialchars($prod['currency'] ?? 'EUR') : 'Sur demande' ?></td>
                        <td>
                          <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit-<?= $prod['id'] ?>">Éditer</button>
                        </td>
                      </tr>
                      <tr class="collapse" id="edit-<?= $prod['id'] ?>">
                        <td colspan="5">
                          <form method="post" class="row g-2">
                            <input type="hidden" name="action" value="update_product">
                            <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
                            <div class="col-md-4">
                              <label class="form-label">Nom</label>
                              <input class="form-control" name="name" value="<?= htmlspecialchars($prod['name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Slug</label>
                              <input class="form-control" name="slug" value="<?= htmlspecialchars($prod['slug']) ?>" required>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Catégorie</label>
                              <input class="form-control" name="category" value="<?= htmlspecialchars($prod['category']) ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Tag court</label>
                              <input class="form-control" name="tag" value="<?= htmlspecialchars($prod['tag']) ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Prix (nombre entier)</label>
                              <input type="number" step="1" min="0" class="form-control" name="price" value="<?= htmlspecialchars((string)$prod['price']) ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Devise</label>
                              <input class="form-control" name="currency" value="<?= htmlspecialchars($prod['currency']) ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Marque</label>
                              <input class="form-control" name="brand" value="<?= htmlspecialchars($prod['brand']) ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Poids</label>
                              <input class="form-control" name="weight" value="<?= htmlspecialchars($prod['weight'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Autonomie</label>
                              <input class="form-control" name="autonomy" value="<?= htmlspecialchars($prod['autonomy'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Charge assistée</label>
                              <input class="form-control" name="charge" value="<?= htmlspecialchars($prod['charge'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Type</label>
                              <input class="form-control" name="type" value="<?= htmlspecialchars($prod['type'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Disponibilité</label>
                              <input class="form-control" name="availability" value="<?= htmlspecialchars($prod['availability'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Image principale</label>
                              <input class="form-control" name="main_image" value="<?= htmlspecialchars($prod['main_image'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Visuel héros</label>
                              <input class="form-control" name="hero_image" value="<?= htmlspecialchars($prod['hero_image'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Résumé</label>
                              <textarea class="form-control" name="summary" rows="2"><?= htmlspecialchars($prod['summary'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Baseline</label>
                              <textarea class="form-control" name="baseline" rows="2"><?= htmlspecialchars($prod['baseline'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Puces (séparées par |)</label>
                              <textarea class="form-control" name="bullets" rows="2"><?= htmlspecialchars($prod['bullets'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Tags (texte libre)</label>
                              <textarea class="form-control" name="tags" rows="2"><?= htmlspecialchars($prod['tags'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Ordre de mise en avant</label>
                              <input type="number" class="form-control" name="featured_order" value="<?= (int)$prod['featured_order'] ?>">
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
              <div class="col-12">
                <label class="form-label">Titre</label>
                <input class="form-control" name="title" required>
              </div>
              <div class="col-12">
                <label class="form-label">Message</label>
                <textarea class="form-control" name="message" rows="2"></textarea>
              </div>
              <div class="col-12">
                <label class="form-label">Lien externe (optionnel)</label>
                <input class="form-control" name="link_url" placeholder="https://...">
              </div>
              <div class="col-md-4">
                <label class="form-label">Produit (optionnel)</label>
                <select class="form-select" name="product_id">
                  <option value="">-- aucun lien produit --</option>
                  <?php foreach ($products as $prod): ?>
                    <option value="<?= (int)$prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?></option>
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
                      <strong><?= htmlspecialchars($item['title']) ?></strong><br>
                      <?php if ($item['product_title']): ?><small class="text-muted">Produit : <?= htmlspecialchars($item['product_title']) ?></small><br><?php endif; ?>
                      <small class="text-muted">Du <?= htmlspecialchars($item['start_at'] ?? 'N/A') ?> au <?= htmlspecialchars($item['end_at'] ?? 'N/A') ?></small>
                      <?php if ($item['link_url']): ?><div><a href="<?= htmlspecialchars($item['link_url']) ?>" target="_blank">Lien</a></div><?php endif; ?>
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
                <label class="form-label">Nom</label>
                <input class="form-control" name="name" required>
              </div>
              <div class="col-12">
                <label class="form-label">Slug</label>
                <input class="form-control" name="slug" required>
              </div>
              <div class="col-12">
                <label class="form-label">Catégorie</label>
                <input class="form-control" name="category">
              </div>
              <div class="col-6">
                <label class="form-label">Tag court</label>
                <input class="form-control" name="tag">
              </div>
              <div class="col-6">
                <label class="form-label">Devise</label>
                <input class="form-control" name="currency" value="EUR">
              </div>
              <div class="col-12">
                <label class="form-label">Prix (nombre entier)</label>
                <input type="number" step="1" min="0" class="form-control" name="price">
              </div>
              <div class="col-12">
                <label class="form-label">Marque</label>
                <input class="form-control" name="brand">
              </div>
              <div class="col-12">
                <label class="form-label">Résumé</label>
                <textarea class="form-control" rows="2" name="summary"></textarea>
              </div>
              <div class="col-12">
                <label class="form-label">Baseline</label>
                <textarea class="form-control" rows="2" name="baseline"></textarea>
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
                <label class="form-label">Contact</label>
                <input class="form-control" name="contact_name">
              </div>
              <div class="col-12">
                <label class="form-label">Email</label>
                <input class="form-control" name="email">
              </div>
              <div class="col-12">
                <label class="form-label">Téléphone</label>
                <input class="form-control" name="phone">
              </div>
              <div class="col-6 form-check ms-2">
                <input class="form-check-input" type="checkbox" name="dropshipping_enabled" value="1" checked>
                <label class="form-check-label">Dropshipping</label>
              </div>
              <div class="col-6 form-check ms-2">
                <input class="form-check-input" type="checkbox" name="is_featured" value="1">
                <label class="form-check-label">Mis en avant</label>
              </div>
              <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea class="form-control" rows="2" name="notes"></textarea>
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
                    <?php if ($supplier['contact_name']): ?><div class="text-muted">Contact : <?= htmlspecialchars($supplier['contact_name']) ?></div><?php endif; ?>
                    <?php if ($supplier['email']): ?><div class="text-muted">Email : <?= htmlspecialchars($supplier['email']) ?></div><?php endif; ?>
                    <?php if ($supplier['phone']): ?><div class="text-muted">Téléphone : <?= htmlspecialchars($supplier['phone']) ?></div><?php endif; ?>
                    <div class="text-muted small">Dropshipping : <?= $supplier['dropshipping_enabled'] ? 'Oui' : 'Non' ?> · Mis en avant : <?= $supplier['is_featured'] ? 'Oui' : 'Non' ?></div>
                    <?php if ($supplier['notes']): ?><div class="mt-1">Notes : <?= htmlspecialchars($supplier['notes']) ?></div><?php endif; ?>
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
