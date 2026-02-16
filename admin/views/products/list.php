<?php
declare(strict_types=1);
$pageTitle = 'Gestion des Produits';
$currentPage = 'products';

ob_start();
?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Recherche</label>
                <input type="text" name="search" class="form-control" 
                       value="<?= sanitize($_GET['search'] ?? '') ?>"
                       placeholder="Nom, slug, SKU...">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Catégorie</label>
                <select name="category" class="form-select">
                    <option value="">Toutes</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" 
                            <?= ($_GET['category'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= sanitize($c['translated_name'] ?: $c['code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Fournisseur</label>
                <select name="supplier" class="form-select">
                    <option value="">Tous</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" 
                            <?= ($_GET['supplier'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= sanitize($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Statut</label>
                <select name="active" class="form-select">
                    <option value="">Tous</option>
                    <option value="1" <?= ($_GET['active'] ?? '') === '1' ? 'selected' : '' ?>>Actifs</option>
                    <option value="0" <?= ($_GET['active'] ?? '') === '0' ? 'selected' : '' ?>>Inactifs</option>
                </select>
            </div>
            
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="<?= admin_url('products') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">
            <?= $total ?> produit(s) trouvé(s)
            <?php if ($totalPages > 1): ?>
                · Page <?= $page ?>/<?= $totalPages ?>
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= admin_url('products/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nouveau produit
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Fournisseur</th>
                    <th>Prix</th>
                    <th>Variantes</th>
                    <th>Statut</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Aucun produit trouvé.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= (int)$p['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($p['media'])): ?>
                                        <img src="<?= sanitize($p['media'][0]['url']) ?>" 
                                             alt="" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded" style="width: 40px; height: 40px;"></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-medium"><?= sanitize($p['title'] ?: 'Sans titre') ?></div>
                                        <small class="text-muted"><?= sanitize($p['slug'] ?: '') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    <?= sanitize($p['category_name'] ?: '—') ?>
                                </span>
                            </td>
                            <td><?= sanitize($p['supplier_name'] ?: '—') ?></td>
                            <td>
                                <?php if ($p['min_price']): ?>
                                    <span class="fw-medium"><?= format_price((float)$p['min_price']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Sur demande</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= (int)$p['variants_count'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $p['is_active'] ? 'success' : 'danger' ?>">
                                    <?= $p['is_active'] ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= admin_url('products/edit/' . $p['id']) ?>" 
                                       class="btn btn-outline-primary" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    
                                    <form method="POST" 
                                          action="<?= admin_url('products/delete/' . $p['id']) ?>" 
                                          class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        <button type="submit" class="btn btn-outline-danger" 
                                                title="Supprimer"
                                                onclick="return confirm('Supprimer ce produit ?');">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <div class="card-footer d-flex justify-content-center">
            <nav>
                <ul class="pagination mb-0">
                    <?php 
                    $queryParams = $_GET;
                    unset($queryParams['page']);
                    $baseUrl = '?' . http_build_query($queryParams);
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= $baseUrl ?>&page=<?= $page - 1 ?>">Précédent</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $baseUrl ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= $baseUrl ?>&page=<?= $page + 1 ?>">Suivant</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../partials/header.php';
echo $content;
require __DIR__ . '/../partials/footer.php';