<?php
require __DIR__ . '/auth.php';

/** =========================
 * Lang depuis l'URL /{lang}/admin
 * ========================= */
const SUPPORTED_LANGS = ['fr','en','de','it','es','pt','nl','pl','ja','zh','ko','ru'];

function normalize_lang($lang) {
  $lang = is_string($lang) ? strtolower(trim($lang)) : 'fr';
  if ($lang === 'jp') $lang = 'ja';
  if ($lang === 'kr') $lang = 'ko';
  return $lang;
}
function get_path_lang(): ?string {
  $path = $_SERVER['REQUEST_URI'] ?? '/';
  $path = parse_url($path, PHP_URL_PATH) ?: '/';
  $seg = array_values(array_filter(explode('/', $path), 'strlen'));
  if (!$seg) return null;
  $lang = normalize_lang($seg[0]);
  return in_array($lang, SUPPORTED_LANGS, true) ? $lang : null;
}

$lang = get_path_lang() ?? 'fr';
$base = '/' . $lang;

$user = require_admin($pdo);
$currentUser = $user;

$statusMessage = null;
$errorMessage  = null;

function sanitize_field(?string $v): string { return trim((string)$v); }

/** =========================
 * Chargements de base (catégories + produits)
 * ========================= */
function fetch_categories(PDO $pdo, string $lang): array {
  $stmt = $pdo->prepare("
    SELECT c.id, c.code, c.sort_order, c.is_active, ci.name
    FROM categories c
    LEFT JOIN categories_i18n ci
      ON ci.category_id = c.id AND ci.lang = :lang
    ORDER BY c.sort_order ASC, COALESCE(ci.name, c.code) ASC
  ");
  $stmt->execute([':lang' => $lang]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_products(PDO $pdo, string $lang): array {
  $stmt = $pdo->prepare("
    SELECT
      p.id,
      p.is_active,
      p.category_id,

      pi.slug,
      pi.title,
      SUBSTRING(REPLACE(REPLACE(pi.description, '\\r',' '), '\\n',' '), 1, 120) AS short_desc,

      ci.name AS category_name,

      pv.price
    FROM products p
    LEFT JOIN products_i18n pi
      ON pi.product_id = p.id AND pi.lang = :lang
    LEFT JOIN categories_i18n ci
      ON ci.category_id = p.category_id AND ci.lang = :lang
    LEFT JOIN (
      SELECT product_id, MIN(price) AS price
      FROM product_variants
      WHERE is_active = 1
      GROUP BY product_id
    ) pv ON pv.product_id = p.id
    ORDER BY p.id DESC
  ");
  $stmt->execute([':lang' => $lang]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_featured(PDO $pdo, string $lang): array {
  // On affiche le nom produit via i18n
  $stmt = $pdo->prepare("
    SELECT
      fi.id,
      fi.product_id,
      fi.priority,
      fi.start_at,
      fi.end_at,
      fi.is_active,
      pi.title AS product_title
    FROM featured_items fi
    LEFT JOIN products_i18n pi
      ON pi.product_id = fi.product_id AND pi.lang = :lang
    ORDER BY fi.priority DESC, fi.start_at DESC, fi.id DESC
  ");
  $stmt->execute([':lang' => $lang]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** =========================
 * POST actions
 * ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'create_product') {
      $title = sanitize_field($_POST['title'] ?? '');
      $slug  = sanitize_field($_POST['slug'] ?? '');
      $category_id = (int)($_POST['category_id'] ?? 0);
      $is_active   = isset($_POST['is_active']) ? 1 : 0;

      if ($title === '' || $slug === '' || $category_id <= 0) {
        throw new Exception("Champs requis: titre, slug, catégorie.");
      }

      $pdo->beginTransaction();

      // 1) base product
      $stmt = $pdo->prepare("INSERT INTO products (category_id, is_active) VALUES (:cat, :active)");
      $stmt->execute([':cat' => $category_id, ':active' => $is_active]);
      $productId = (int)$pdo->lastInsertId();

      // 2) i18n for current lang
      $stmt = $pdo->prepare("
        INSERT INTO products_i18n (product_id, lang, slug, title, description)
        VALUES (:pid, :lang, :slug, :title, :desc)
      ");
      $stmt->execute([
        ':pid' => $productId,
        ':lang' => $lang,
        ':slug' => $slug,
        ':title' => $title,
        ':desc' => sanitize_field($_POST['description'] ?? ''),
      ]);

      $pdo->commit();
      $statusMessage = "Produit créé (ID $productId).";
    }

    elseif ($action === 'update_product') {
      $productId = (int)($_POST['product_id'] ?? 0);
      if ($productId <= 0) throw new Exception("ID produit invalide.");

      $title = sanitize_field($_POST['title'] ?? '');
      $slug  = sanitize_field($_POST['slug'] ?? '');
      $category_id = (int)($_POST['category_id'] ?? 0);
      $is_active   = isset($_POST['is_active']) ? 1 : 0;

      if ($title === '' || $slug === '' || $category_id <= 0) {
        throw new Exception("Champs requis: titre, slug, catégorie.");
      }

      $pdo->beginTransaction();

      // base
      $stmt = $pdo->prepare("
        UPDATE products
        SET category_id = :cat, is_active = :active
        WHERE id = :id
      ");
      $stmt->execute([':cat' => $category_id, ':active' => $is_active, ':id' => $productId]);

      // i18n upsert (MySQL: INSERT ... ON DUPLICATE KEY si unique(product_id,lang))
      // Si tu n'as pas d'UNIQUE(product_id,lang), remplace par SELECT+UPDATE/INSERT.
      $stmt = $pdo->prepare("
        INSERT INTO products_i18n (product_id, lang, slug, title, description)
        VALUES (:pid, :lang, :slug, :title, :desc)
        ON DUPLICATE KEY UPDATE
          slug = VALUES(slug),
          title = VALUES(title),
          description = VALUES(description)
      ");
      $stmt->execute([
        ':pid' => $productId,
        ':lang' => $lang,
        ':slug' => $slug,
        ':title' => $title,
        ':desc' => sanitize_field($_POST['description'] ?? ''),
      ]);

      $pdo->commit();
      $statusMessage = "Produit mis à jour.";
    }

    elseif ($action === 'delete_product') {
      $productId = (int)($_POST['product_id'] ?? 0);
      if ($productId <= 0) throw new Exception("ID produit invalide.");

      $pdo->beginTransaction();
      $pdo->prepare("DELETE FROM products_i18n WHERE product_id = ?")->execute([$productId]);
      $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$productId]);
      $pdo->prepare("DELETE FROM featured_items WHERE product_id = ?")->execute([$productId]);
      $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
      $pdo->commit();

      $statusMessage = "Produit supprimé.";
    }

    elseif ($action === 'add_featured') {
      $productId = (int)($_POST['product_id'] ?? 0);
      if ($productId <= 0) throw new Exception("Choisis un produit.");

      $stmt = $pdo->prepare("
        INSERT INTO featured_items (product_id, priority, start_at, end_at, is_active)
        VALUES (:pid, :prio, :start, :end, :active)
      ");
      $stmt->execute([
        ':pid' => $productId,
        ':prio' => (int)($_POST['priority'] ?? 0),
        ':start' => ($_POST['start_at'] ?? '') ?: null,
        ':end' => ($_POST['end_at'] ?? '') ?: null,
        ':active' => isset($_POST['is_active']) ? 1 : 0,
      ]);
      $statusMessage = "Mise en avant ajoutée.";
    }

    elseif ($action === 'toggle_featured') {
      $fid = (int)($_POST['featured_id'] ?? 0);
      $state = (int)($_POST['target_state'] ?? 0);
      $stmt = $pdo->prepare("UPDATE featured_items SET is_active = ? WHERE id = ?");
      $stmt->execute([$state, $fid]);
      $statusMessage = "Statut mise en avant mis à jour.";
    }

    elseif ($action === 'delete_featured') {
      $fid = (int)($_POST['featured_id'] ?? 0);
      $stmt = $pdo->prepare("DELETE FROM featured_items WHERE id = ?");
      $stmt->execute([$fid]);
      $statusMessage = "Mise en avant supprimée.";
    }

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $errorMessage = $e->getMessage();
  }
}

try {
  $categories = fetch_categories($pdo, $lang);
  $products   = fetch_products($pdo, $lang);
  $featured   = fetch_featured($pdo, $lang);
} catch (Throwable $e) {
  $errorMessage = $errorMessage ?: $e->getMessage();
  $categories = $categories ?? [];
  $products = $products ?? [];
  $featured = $featured ?? [];
}
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <title>Administration – Exoleton</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="bg-light">
<header class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="<?= $base ?>/">
      <img src="/assets/img/logo.png" alt="Exoleton" width="272" height="1000" class="me-2">
    </a>

    <nav class="ms-auto">
      <ul class="navbar-nav align-items-lg-center flex-row gap-3">
        <li class="nav-item">
          <label class="visually-hidden" for="languageSwitcherAdmin">Langue</label>
          <select id="languageSwitcherAdmin" class="form-select form-select-sm" data-language-switcher></select>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Bonjour <?= htmlspecialchars($user['name']); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
            <li><a class="dropdown-item" href="<?= $base ?>/account">Mon compte</a></li>
            <li><a class="dropdown-item text-danger" href="<?= $base ?>/logout">Se déconnecter</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </div>
</header>

<main class="container" style="padding-top: 7rem; padding-bottom: 4rem;">
  <h1 class="h4 mb-3">Administration</h1>

  <?php if ($errorMessage): ?>
    <div class="alert alert-danger">
      <strong>Erreur:</strong> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php elseif ($statusMessage): ?>
    <div class="alert alert-info"><?= htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- PRODUITS -->
    <div class="col-lg-8">
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
          <h2 class="h6 mb-0">Produits (lang: <?= htmlspecialchars($lang) ?>)</h2>
        </div>
        <div class="card-body">

          <?php if (empty($products)): ?>
            <p class="text-muted mb-0">Aucun produit.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table align-middle">
                <thead class="table-light">
                  <tr>
                    <th>ID</th>
                    <th>Titre</th>
                    <th>Catégorie</th>
                    <th>Prix min</th>
                    <th>Actif</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($products as $p): ?>
                    <tr>
                      <td><?= (int)$p['id'] ?></td>
                      <td><?= htmlspecialchars($p['title'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars($p['category_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= $p['price'] !== null ? number_format((float)$p['price'], 0, ',', ' ') . ' €' : 'Sur demande' ?></td>
                      <td><?= (int)$p['is_active'] === 1 ? 'Oui' : 'Non' ?></td>
                      <td>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit-<?= (int)$p['id'] ?>">Éditer</button>
                      </td>
                    </tr>

                    <tr class="collapse" id="edit-<?= (int)$p['id'] ?>">
                      <td colspan="6">
                        <form method="post" class="row g-2">
                          <input type="hidden" name="action" value="update_product">
                          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

                          <div class="col-md-4">
                            <label class="form-label">Titre</label>
                            <input class="form-control" name="title" value="<?= htmlspecialchars($p['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                          </div>

                          <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input class="form-control" name="slug" value="<?= htmlspecialchars($p['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                          </div>

                          <div class="col-md-4">
                            <label class="form-label">Catégorie</label>
                            <select class="form-select" name="category_id" required>
                              <option value="">-- choisir --</option>
                              <?php foreach ($categories as $c): ?>
                                <?php if ((int)$c['is_active'] !== 1) continue; ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)$p['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                  <?= htmlspecialchars($c['name'] ?: $c['code'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>

                          <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($p['short_desc'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">Ici tu édites la description pour la langue courante (<?= htmlspecialchars($lang) ?>).</div>
                          </div>

                          <div class="col-12 form-check ms-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= (int)$p['is_active'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label">Produit actif</label>
                          </div>

                          <div class="col-12 d-flex gap-2">
                            <button class="btn btn-primary" type="submit">Enregistrer</button>
                          </div>
                        </form>

                        <form method="post" class="mt-2">
                          <input type="hidden" name="action" value="delete_product">
                          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
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

      <!-- FEATURED -->
      <div class="card shadow-sm">
        <div class="card-header bg-white border-0">
          <h2 class="h6 mb-0">Mises en avant (featured_items)</h2>
        </div>
        <div class="card-body">

          <form method="post" class="row g-2 mb-3">
            <input type="hidden" name="action" value="add_featured">

            <div class="col-md-6">
              <label class="form-label">Produit</label>
              <select class="form-select" name="product_id" required>
                <option value="">-- choisir --</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?= (int)$p['id'] ?>">
                    #<?= (int)$p['id'] ?> — <?= htmlspecialchars($p['title'] ?? $p['slug'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Priorité</label>
              <input type="number" class="form-control" name="priority" value="0">
            </div>

            <div class="col-md-2">
              <label class="form-label">Début</label>
              <input type="datetime-local" class="form-control" name="start_at">
            </div>

            <div class="col-md-2">
              <label class="form-label">Fin</label>
              <input type="datetime-local" class="form-control" name="end_at">
            </div>

            <div class="col-12 form-check ms-2">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
              <label class="form-check-label">Active</label>
            </div>

            <div class="col-12">
              <button class="btn btn-primary" type="submit">Ajouter</button>
            </div>
          </form>

          <?php if (empty($featured)): ?>
            <p class="text-muted mb-0">Aucune mise en avant.</p>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($featured as $f): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <strong>#<?= (int)$f['id'] ?></strong>
                    — <?= htmlspecialchars($f['product_title'] ?? ('Produit #' . (int)$f['product_id']), ENT_QUOTES, 'UTF-8') ?><br>
                    <small class="text-muted">
                      Priorité: <?= (int)$f['priority'] ?> ·
                      Du <?= htmlspecialchars($f['start_at'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                      au <?= htmlspecialchars($f['end_at'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                      · Actif: <?= (int)$f['is_active'] === 1 ? 'Oui' : 'Non' ?>
                    </small>
                  </div>

                  <div class="d-flex gap-2">
                    <form method="post" class="mb-0">
                      <input type="hidden" name="action" value="toggle_featured">
                      <input type="hidden" name="featured_id" value="<?= (int)$f['id'] ?>">
                      <input type="hidden" name="target_state" value="<?= (int)$f['is_active'] === 1 ? 0 : 1 ?>">
                      <button class="btn btn-sm <?= (int)$f['is_active'] === 1 ? 'btn-outline-secondary' : 'btn-outline-success' ?>" type="submit">
                        <?= (int)$f['is_active'] === 1 ? 'Désactiver' : 'Activer' ?>
                      </button>
                    </form>

                    <form method="post" class="mb-0">
                      <input type="hidden" name="action" value="delete_featured">
                      <input type="hidden" name="featured_id" value="<?= (int)$f['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Supprimer cette mise en avant ?');">
                        Supprimer
                      </button>
                    </form>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <!-- CREATE PRODUCT -->
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header bg-white border-0">
          <h2 class="h6 mb-0">Créer un produit (<?= htmlspecialchars($lang) ?>)</h2>
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
              <select class="form-select" name="category_id" required>
                <option value="">-- choisir --</option>
                <?php foreach ($categories as $c): ?>
                  <?php if ((int)$c['is_active'] !== 1) continue; ?>
                  <option value="<?= (int)$c['id'] ?>">
                    <?= htmlspecialchars($c['name'] ?: $c['code'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="3"></textarea>
            </div>

            <div class="col-12 form-check ms-2">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
              <label class="form-check-label">Produit actif</label>
            </div>

            <div class="col-12">
              <button class="btn btn-primary w-100" type="submit">Créer</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/i18n.js"></script>
</body>
</html>
