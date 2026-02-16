<?php
declare(strict_types=1);
$pageTitle = 'Modifier : ' . sanitize($product['title'] ?: 'Produit #' . $product['id']);
$currentPage = 'products';

// Charger les médias existants
require_once __DIR__ . '/../../models/Media.php';
$mediaModel = new Media($pdo ?? $GLOBALS['pdo']);
$medias = $mediaModel->getByProduct((int)$product['id']);
$mediaCounts = $mediaModel->countByProduct((int)$product['id']);

ob_start();
?>

<div class="row">
    <div class="col-lg-8">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="form_action" value="save_product">
            
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button">
                        <i class="bi bi-info-circle"></i> Général
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-variants" type="button">
                        <i class="bi bi-layers"></i> Variantes
                        <span class="badge bg-secondary"><?= count($product['variants']) ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-medias" type="button">
                        <i class="bi bi-images"></i> Médias
                        <span class="badge bg-secondary"><?= count($medias) ?></span>
                    </button>
                </li>
                <?php if (!empty($categoryAttributes)): ?>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-attributes" type="button">
                        <i class="bi bi-tags"></i> Attributs
                    </button>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="tab-content">
                <!-- Tab Général -->
                <div class="tab-pane fade show active" id="tab-general">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Informations de base</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Catégorie *</label>
                                    <select name="category_id" class="form-select" required>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= (int)$c['id'] ?>" 
                                                <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>>
                                                <?= sanitize($c['translated_name'] ?: $c['code']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Fournisseur</label>
                                    <select name="supplier_id" class="form-select">
                                        <option value="">Aucun</option>
                                        <?php foreach ($suppliers as $s): ?>
                                            <option value="<?= (int)$s['id'] ?>" 
                                                <?= ($product['supplier_id'] ?? null) == $s['id'] ? 'selected' : '' ?>>
                                                <?= sanitize($s['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">SKU</label>
                                    <input type="text" name="sku" class="form-control" 
                                           value="<?= sanitize($product['sku'] ?: '') ?>">
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Marque</label>
                                    <input type="text" name="brand" class="form-control" 
                                           value="<?= sanitize($product['brand'] ?: '') ?>">
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                                            <?= $product['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label">Produit actif</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Traductions -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Traductions</h5>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-3">
                                <?php foreach (SUPPORTED_LANGS as $i => $l): 
                                    $trans = $product['translations'][$l] ?? [];
                                ?>
                                    <li class="nav-item">
                                        <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" 
                                                data-bs-toggle="tab" data-bs-target="#edit-lang-<?= $l ?>" type="button">
                                            <?= strtoupper($l) ?>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <div class="tab-content">
                                <?php foreach (SUPPORTED_LANGS as $i => $l): 
                                    $trans = $product['translations'][$l] ?? [];
                                ?>
                                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="edit-lang-<?= $l ?>">
                                        <div class="row g-3">
                                            <div class="col-md-8">
                                                <label class="form-label">Titre</label>
                                                <input type="text" name="title_<?= $l ?>" class="form-control" 
                                                       value="<?= sanitize($trans['title'] ?? '') ?>">
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <label class="form-label">Slug</label>
                                                <input type="text" name="slug_<?= $l ?>" class="form-control" 
                                                       value="<?= sanitize($trans['slug'] ?? '') ?>">
                                            </div>
                                            
                                            <div class="col-12">
                                                <label class="form-label">Description</label>
                                                <textarea name="description_<?= $l ?>" class="form-control" rows="4"><?= sanitize($trans['description'] ?? '') ?></textarea>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label class="form-label">Meta titre</label>
                                                <input type="text" name="meta_title_<?= $l ?>" class="form-control" 
                                                       value="<?= sanitize($trans['meta_title'] ?? '') ?>">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label class="form-label">Meta description</label>
                                                <input type="text" name="meta_description_<?= $l ?>" class="form-control" 
                                                       value="<?= sanitize($trans['meta_description'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Variantes -->
                <div class="tab-pane fade" id="tab-variants">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Variantes existantes</h5>
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addVariantModal">
                                <i class="bi bi-plus"></i> Ajouter
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>SKU</th>
                                        <th>Prix HT</th>
                                        <th>Stock</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($product['variants'] as $v): ?>
                                        <tr>
                                            <td><?= (int)$v['id'] ?></td>
                                            <td><?= sanitize($v['sku'] ?: '—') ?></td>
                                            <td><?= format_price((float)$v['price']) ?></td>
                                            <td><?= (int)$v['stock_qty'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= $v['is_active'] ? 'success' : 'danger' ?>">
                                                    <?= $v['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (count($product['variants']) > 1): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette variante ?');">
                                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                    <input type="hidden" name="form_action" value="delete_variant">
                                                    <input type="hidden" name="variant_id" value="<?= $v['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Médias -->
                <div class="tab-pane fade" id="tab-medias">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Galerie médias</h5>
                        </div>
                        <div class="card-body">
                            <!-- Stats -->
                            <div class="d-flex gap-3 mb-3">
                                <span class="badge bg-primary">
                                    <?= (int)($mediaCounts['images'] ?? 0) ?> / <?= MAX_IMAGES_PER_PRODUCT ?> images
                                </span>
                                <span class="badge bg-danger">
                                    <?= (int)($mediaCounts['videos'] ?? 0) ?> / <?= MAX_VIDEOS_PER_PRODUCT ?> vidéos
                                </span>
                            </div>
                            
                            <!-- Upload zone -->
                            <div id="dropZoneEdit" class="drop-zone mb-4">
                                <div class="drop-zone-content">
                                    <i class="bi bi-cloud-arrow-up display-4 text-primary"></i>
                                    <p class="mb-2">Ajouter des médias</p>
                                    <p class="text-muted small">Glissez-déposez ou cliquez pour parcourir</p>
                                    <input type="file" id="mediaInputEdit" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" style="display: none;">
                                </div>
                            </div>
                            
                            <!-- Gallery -->
                            <div id="mediaGallery" class="row g-3 sortable">
                                <?php foreach ($medias as $media): ?>
                                    <div class="col-6 col-md-4 col-lg-3 media-item" data-id="<?= $media['id'] ?>">
                                        <div class="media-card <?= !empty($media['is_main']) ? 'is-main' : '' ?>">
                                            <?php if ($media['type'] === 'video'): ?>
                                                <video src="<?= sanitize($media['url']) ?>" muted preload="metadata"></video>
                                                <span class="media-type-badge video">VIDÉO</span>
                                            <?php else: ?>
                                                <img src="<?= sanitize($media['thumb_url'] ?? $media['url']) ?>" alt="" loading="lazy">
                                                <span class="media-type-badge image">IMAGE</span>
                                            <?php endif; ?>
                                            
                                            <div class="media-overlay">
                                                <button type="button" class="btn btn-sm btn-success <?= !empty($media['is_main']) ? 'd-none' : '' ?>" 
                                                        onclick="setMainMedia(<?= $media['id'] ?>)" title="Définir comme principal">
                                                    <i class="bi bi-star-fill"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="deleteMedia(<?= $media['id'] ?>)" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            
                                            <?php if (!empty($media['is_main'])): ?>
                                                <span class="main-label">PRINCIPAL</span>
                                            <?php endif; ?>
                                            
                                            <div class="drag-handle">
                                                <i class="bi bi-grip-vertical"></i>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (empty($medias)): ?>
                                <div class="text-center text-muted py-5" id="noMediaMessage">
                                    <i class="bi bi-images display-4"></i>
                                    <p>Aucun média pour ce produit</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Attributs -->
                <?php if (!empty($categoryAttributes)): ?>
                <div class="tab-pane fade" id="tab-attributes">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Attributs spécifiques</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php foreach ($categoryAttributes as $attr): 
                                    $attrId = $attr['id'];
                                    $value = $product['attributes'][$attrId] ?? '';
                                ?>
                                    <div class="col-md-6">
                                        <label class="form-label"><?= sanitize($attr['translated_name'] ?? $attr['name'] ?? 'Attribut #' . $attr['id']) ?></label>
                                        <?php if ($attr['data_type'] === 'select'): ?>
                                            <input type="text" name="attributes[<?= $attr['id'] ?>]" 
                                                   class="form-control" value="<?= sanitize($value) ?>">
                                        <?php elseif ($attr['data_type'] === 'bool'): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="attributes[<?= $attr['id'] ?>]" value="1"
                                                       <?= $value ? 'checked' : '' ?>>
                                            </div>
                                        <?php elseif ($attr['data_type'] === 'int'): ?>
                                            <input type="number" name="attributes[<?= $attr['id'] ?>]" 
                                                   class="form-control" value="<?= (int)$value ?>">
                                        <?php elseif ($attr['data_type'] === 'decimal'): ?>
                                            <input type="number" step="0.01" name="attributes[<?= $attr['id'] ?>]" 
                                                   class="form-control" value="<?= (float)$value ?>">
                                        <?php else: ?>
                                            <input type="text" name="attributes[<?= $attr['id'] ?>]" 
                                                   class="form-control" value="<?= sanitize($value) ?>">
                                        <?php endif; ?>
                                        <?php if (!empty($attr['unit'])): ?>
                                            <small class="text-muted"><?= sanitize($attr['unit']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mt-4">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= sanitize($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="<?= admin_url('products') ?>" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-lg"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informations</h5>
            </div>
            <div class="card-body">
                <p><strong>ID:</strong> <?= (int)$product['id'] ?></p>
                <p><strong>Créé le:</strong> <?= format_datetime($product['created_at']) ?></p>
                <p><strong>Modifié le:</strong> <?= format_datetime($product['updated_at']) ?></p>
                
                <hr>
                
                <form method="POST" action="<?= admin_url('products/delete/' . $product['id']) ?>"
                      onsubmit="return confirm('Supprimer ce produit ? Cette action est irréversible.');">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash"></i> Supprimer le produit
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Preview produit -->
        <?php if (!empty($medias)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Aperçu principal</h5>
            </div>
            <div class="card-body p-0">
                <?php 
                $mainMedia = array_filter($medias, fn($m) => !empty($m['is_main']));
                $mainMedia = $mainMedia ? array_values($mainMedia)[0] : $medias[0];
                ?>
                <?php if ($mainMedia['type'] === 'video'): ?>
                    <video src="<?= sanitize($mainMedia['url']) ?>" class="w-100" controls></video>
                <?php else: ?>
                    <img src="<?= sanitize($mainMedia['url']) ?>" class="w-100" alt="">
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Ajout Variante -->
<div class="modal fade" id="addVariantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="form_action" value="add_variant">
                
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle variante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">SKU</label>
                        <input type="text" name="variant_sku" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prix HT (€) *</label>
                        <input type="number" name="variant_price" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">TVA (%)</label>
                        <input type="number" name="variant_vat" class="form-control" value="20.00" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stock</label>
                        <input type="number" name="variant_stock" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="variant_active" value="1" checked>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Styles Médias */
.drop-zone {
    border: 3px dashed #dee2e6;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    background: #f8f9fa;
    cursor: pointer;
}

.drop-zone:hover, .drop-zone.dragover {
    border-color: #0d6efd;
    background: #e7f1ff;
}

.media-card {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    cursor: move;
    background: #000;
}

.media-card img, .media-card video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-card.is-main {
    box-shadow: 0 0 0 3px #198754;
}

.media-type-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: bold;
    color: white;
    z-index: 2;
}

.media-type-badge.image { background: #0d6efd; }
.media-type-badge.video { background: #dc3545; }

.media-overlay {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    gap: 5px;
    opacity: 0;
    transition: opacity 0.2s;
    z-index: 2;
}

.media-card:hover .media-overlay {
    opacity: 1;
}

.main-label {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    background: #198754;
    color: white;
    padding: 2px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    z-index: 2;
}

.drag-handle {
    position: absolute;
    bottom: 8px;
    right: 8px;
    color: white;
    background: rgba(0,0,0,0.5);
    width: 28px;
    height: 28px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: move;
    z-index: 2;
}

.sortable-ghost {
    opacity: 0.4;
}

.sortable-drag {
    cursor: grabbing;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
const productId = <?= (int)$product['id'] ?>;
const csrfToken = '<?= generate_csrf_token() ?>';
const currentLang = '<?= CURRENT_LANG ?>';

// Initialiser SortableJS pour le réordonnancement
const gallery = document.getElementById('mediaGallery');
if (gallery && gallery.children.length > 0) {
    new Sortable(gallery, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            const ids = Array.from(gallery.querySelectorAll('.media-item')).map(el => el.dataset.id);
            updateOrder(ids);
        }
    });
}

// Upload de médias
const dropZone = document.getElementById('dropZoneEdit');
const mediaInput = document.getElementById('mediaInputEdit');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
    }, false);
});

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
});

dropZone.addEventListener('click', () => mediaInput.click());
dropZone.addEventListener('drop', (e) => handleFiles(e.dataTransfer.files), false);
mediaInput.addEventListener('change', (e) => handleFiles(e.target.files), false);

function handleFiles(files) {
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    
    Array.from(files).forEach(file => {
        formData.append('media[]', file);
    });
    
    // Afficher un indicateur de chargement
    dropZone.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div><p class="mt-2">Upload en cours...</p>';
    
    fetch(`/${currentLang}/admin/api/products/${productId}/media`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Erreur lors de l\'upload');
            location.reload();
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erreur réseau');
        location.reload();
    });
}

// Définir comme principal
function setMainMedia(mediaId) {
    fetch(`/${currentLang}/admin/api/media/${mediaId}/set-main`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Retirer is-main de tous
            document.querySelectorAll('.media-card').forEach(card => {
                card.classList.remove('is-main');
                const btn = card.querySelector('.btn-success');
                if (btn) btn.classList.remove('d-none');
                const label = card.querySelector('.main-label');
                if (label) label.remove();
            });
            
            // Ajouter au nouveau
            const item = document.querySelector(`[data-id="${mediaId}"]`);
            const card = item.querySelector('.media-card');
            card.classList.add('is-main');
            const btn = card.querySelector('.btn-success');
            if (btn) btn.classList.add('d-none');
            
            const label = document.createElement('span');
            label.className = 'main-label';
            label.textContent = 'PRINCIPAL';
            card.appendChild(label);
        } else {
            alert(data.error || 'Erreur');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erreur réseau');
    });
}

// Supprimer un média
function deleteMedia(mediaId) {
    if (!confirm('Supprimer ce média ?')) return;
    
    fetch(`/${currentLang}/admin/api/media/${mediaId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-Token': csrfToken
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`[data-id="${mediaId}"]`);
            if (item) item.remove();
            
            // Si plus de médias, recharger pour afficher le message "Aucun média"
            if (document.querySelectorAll('.media-item').length === 0) {
                location.reload();
            }
        } else {
            alert(data.error || 'Erreur lors de la suppression');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erreur réseau');
    });
}

// Mettre à jour l'ordre
function updateOrder(ids) {
    fetch(`/${currentLang}/admin/api/products/${productId}/media/reorder`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ order: ids })
    })
    .catch(err => console.error('Erreur réordonnancement:', err));
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../partials/header.php';
echo $content;
require __DIR__ . '/../partials/footer.php';