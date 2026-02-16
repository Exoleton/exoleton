<?php
declare(strict_types=1);
$pageTitle = ($isEdit ?? false) ? 'Modifier le Fournisseur' : 'Nouveau Fournisseur';
$currentPage = 'suppliers';

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?= ($isEdit ?? false) ? 'Modifier : ' . sanitize($supplier['name']) : 'Nouveau fournisseur' ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= sanitize($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?= sanitize($supplier['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Site web</label>
                            <input type="url" name="website" class="form-control" 
                                   value="<?= sanitize($supplier['website'] ?? '') ?>"
                                   placeholder="https://...">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="contact_email" class="form-control" 
                                   value="<?= sanitize($supplier['contact_email'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="contact_phone" class="form-control" 
                                   value="<?= sanitize($supplier['contact_phone'] ?? '') ?>">
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="is_active" 
                                       value="1" <?= ($supplier['is_active'] ?? true) ? 'checked' : '' ?>>
                                <label class="form-check-label">Actif</label>
                            </div>
                            
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="is_featured" 
                                       value="1" <?= ($supplier['is_featured'] ?? false) ? 'checked' : '' ?>>
                                <label class="form-check-label">Mis en avant <i class="bi bi-star-fill text-warning"></i></label>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= admin_url('suppliers') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> <?= ($isEdit ?? false) ? 'Mettre à jour' : 'Créer' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../partials/header.php';
echo $content;
require __DIR__ . '/../partials/footer.php';