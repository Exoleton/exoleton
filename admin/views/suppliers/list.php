<?php
declare(strict_types=1);
$pageTitle = 'Gestion des Fournisseurs';
$currentPage = 'suppliers';

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Total: <?= $total ?> fournisseur(s)</p>
    <a href="<?= admin_url('suppliers/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nouveau fournisseur
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Contact</th>
                    <th>Produits</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Aucun fournisseur trouvé.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td><?= (int)$s['id'] ?></td>
                            <td>
                                <strong><?= sanitize($s['name']) ?></strong>
                                <?php if ($s['is_featured']): ?>
                                    <i class="bi bi-star-fill text-warning ms-1"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['contact_email']): ?>
                                    <div class="small"><i class="bi bi-envelope"></i> <?= sanitize($s['contact_email']) ?></div>
                                <?php endif; ?>
                                <?php if ($s['contact_phone']): ?>
                                    <div class="small"><i class="bi bi-telephone"></i> <?= sanitize($s['contact_phone']) ?></div>
                                <?php endif; ?>
                                <?php if ($s['website']): ?>
                                    <div class="small">
                                        <i class="bi bi-globe"></i>
                                        <a href="<?= sanitize($s['website']) ?>" target="_blank" class="text-decoration-none">
                                            Site web
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$s['products_count'] ?></td>
                            <td>
                                <span class="badge bg-<?= $s['is_active'] ? 'success' : 'danger' ?>">
                                    <?= $s['is_active'] ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= admin_url('suppliers/edit/' . $s['id']) ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                
                                <form method="POST" action="<?= admin_url('suppliers/delete/' . $s['id']) ?>" 
                                      class="d-inline" onsubmit="return confirm('Supprimer ce fournisseur ?');">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
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
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
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