<?php
declare(strict_types=1);
$pageTitle = 'Nouveau Produit';
$currentPage = 'products';

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <form method="POST" enctype="multipart/form-data" id="productForm">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <!-- Informations de base -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations de base</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Catégorie *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Choisir...</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>">
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
                                    <option value="<?= (int)$s['id'] ?>"><?= sanitize($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">SKU (référence)</label>
                            <input type="text" name="sku" class="form-control" placeholder="EXO-XXX-001">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Marque</label>
                            <input type="text" name="brand" class="form-control">
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                <label class="form-check-label">Produit actif</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Médias - Section Upload -->
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-images"></i> Médias * <small class="opacity-75">(Photos et Vidéos)</small></h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Minimum requis :</strong> 1 photo ou vidéo.<br>
                        <small>
                            Images : max <?= MAX_IMAGES_PER_PRODUCT ?> fichiers (JPG, PNG, WebP, max 5Mo)<br>
                            Vidéos : max <?= MAX_VIDEOS_PER_PRODUCT ?> fichiers (MP4, WebM, MOV, max 50Mo)
                        </small>
                    </div>
                    
                    <!-- Zone de drop -->
                    <div id="dropZone" class="drop-zone mb-3">
                        <div class="drop-zone-content">
                            <i class="bi bi-cloud-arrow-up display-4 text-primary"></i>
                            <p class="mb-2">Glissez-déposez vos fichiers ici</p>
                            <p class="text-muted small mb-3">ou</p>
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('mediaInput').click()">
                                <i class="bi bi-folder-open"></i> Parcourir
                            </button>
                            <!-- Input caché pour la sélection de fichiers -->
                            <input type="file" id="mediaInput" name="media[]" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" style="display: none;">
                        </div>
                    </div>
                    
                    <!-- Preview container -->
                    <div id="mediaPreview" class="row g-3"></div>
                    
                    <!-- Container pour les fichiers sélectionnés (sera rempli par JS) -->
                    <div id="mediaInputsContainer" style="display: none;"></div>
                </div>
            </div>
            
            <!-- Traductions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-globe"></i> Traductions</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <?php foreach (SUPPORTED_LANGS as $i => $l): ?>
                            <li class="nav-item">
                                <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" 
                                        data-bs-toggle="tab" data-bs-target="#lang-<?= $l ?>" 
                                        type="button">
                                    <?= strtoupper($l) ?>
                                    <?php if ($l === CURRENT_LANG): ?>
                                        <span class="badge bg-primary ms-1">défaut</span>
                                    <?php endif; ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="tab-content">
                        <?php foreach (SUPPORTED_LANGS as $i => $l): ?>
                            <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="lang-<?= $l ?>">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Titre <?= $l === CURRENT_LANG ? '*' : '' ?></label>
                                        <input type="text" name="title_<?= $l ?>" class="form-control" 
                                               <?= $l === CURRENT_LANG ? 'required' : '' ?>>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Slug</label>
                                        <input type="text" name="slug_<?= $l ?>" class="form-control">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea name="description_<?= $l ?>" class="form-control" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Meta titre</label>
                                        <input type="text" name="meta_title_<?= $l ?>" class="form-control">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Meta description</label>
                                        <input type="text" name="meta_description_<?= $l ?>" class="form-control">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Prix et stock initial -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-currency-euro"></i> Prix et stock (première variante)</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Prix de base (€)</label>
                            <input type="number" name="base_price" class="form-control" step="0.01" min="0">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">TVA (%)</label>
                            <input type="number" name="vat" class="form-control" value="20.00" step="0.01">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Stock initial</label>
                            <input type="number" name="stock_qty" class="form-control" value="0" min="0">
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= sanitize($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between">
                <a href="<?= admin_url('products') ?>" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-left"></i> Annuler
                </a>
                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                    <i class="bi bi-check-lg"></i> Créer le produit
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.drop-zone {
    border: 3px dashed #dee2e6;
    border-radius: 12px;
    padding: 3rem;
    text-align: center;
    transition: all 0.3s ease;
    background: #f8f9fa;
    cursor: pointer;
}

.drop-zone:hover, .drop-zone.dragover {
    border-color: #0d6efd;
    background: #e7f1ff;
}

.drop-zone.dragover {
    transform: scale(1.02);
}

.media-preview-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    aspect-ratio: 1;
}

.media-preview-item img, .media-preview-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-preview-item .badge {
    position: absolute;
    top: 8px;
    left: 8px;
    z-index: 2;
}

.media-preview-item .remove-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    z-index: 2;
}

.media-preview-item .remove-btn:hover {
    background: #dc3545;
    transform: scale(1.1);
}

.media-preview-item .main-badge {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(25, 135, 84, 0.9);
    color: white;
    padding: 2px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    display: none;
    z-index: 2;
}

.media-preview-item.is-main .main-badge {
    display: block;
}

.media-preview-item.is-main {
    box-shadow: 0 0 0 3px #198754;
}
</style>

<script>
// Gestionnaire de médias
const dropZone = document.getElementById('dropZone');
const mediaInput = document.getElementById('mediaInput');
const previewContainer = document.getElementById('mediaPreview');
const submitBtn = document.getElementById('submitBtn');
let filesQueue = [];

// Empêcher le comportement par défaut du navigateur sur toute la page
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    document.body.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

// Highlight drop zone uniquement quand on drag sur la zone
['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    }, false);
});

// Clic sur la zone = clic sur l'input
dropZone.addEventListener('click', (e) => {
    if (e.target !== mediaInput) {
        mediaInput.click();
    }
});

// Gestion des fichiers sélectionnés
mediaInput.addEventListener('change', handleFiles, false);
dropZone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = Array.from(dt.files);
    addFiles(files);
}, false);

function handleFiles(e) {
    const files = Array.from(e.target.files);
    addFiles(files);
    // Reset l'input pour permettre de sélectionner les mêmes fichiers encore
    e.target.value = '';
}

function addFiles(files) {
    const maxImages = <?= MAX_IMAGES_PER_PRODUCT ?>;
    const maxVideos = <?= MAX_VIDEOS_PER_PRODUCT ?>;
    
    let imageCount = filesQueue.filter(f => f.type.startsWith('image/')).length;
    let videoCount = filesQueue.filter(f => f.type.startsWith('video/')).length;
    
    files.forEach(file => {
        const isImage = file.type.startsWith('image/');
        const isVideo = file.type.startsWith('video/');
        
        if (!isImage && !isVideo) {
            alert(`Type de fichier non supporté: ${file.name}`);
            return;
        }
        
        if (isImage && imageCount >= maxImages) {
            alert(`Maximum ${maxImages} images atteint`);
            return;
        }
        if (isVideo && videoCount >= maxVideos) {
            alert(`Maximum ${maxVideos} vidéos atteint`);
            return;
        }
        
        // Vérifier taille
        const maxSize = isImage ? <?= MAX_IMAGE_SIZE ?> : <?= MAX_VIDEO_SIZE ?>;
        if (file.size > maxSize) {
            const maxMo = maxSize / 1024 / 1024;
            alert(`Fichier trop lourd (${(file.size/1024/1024).toFixed(1)} Mo > ${maxMo} Mo): ${file.name}`);
            return;
        }
        
        filesQueue.push(file);
        if (isImage) imageCount++;
        if (isVideo) videoCount++;
        
        createPreview(file, filesQueue.length - 1);
    });
    
    updateMediaInput();
}

function createPreview(file, index) {
    const div = document.createElement('div');
    div.className = 'col-6 col-md-4 col-lg-3';
    div.id = `preview-${index}`;
    div.innerHTML = `
        <div class="media-preview-item ${index === 0 ? 'is-main' : ''}" data-index="${index}">
            <span class="badge bg-${file.type.startsWith('video/') ? 'danger' : 'primary'}">
                ${file.type.startsWith('video/') ? 'VIDÉO' : 'IMAGE'}
            </span>
            <button type="button" class="remove-btn" onclick="removeFile(${index})" title="Retirer">
                <i class="bi bi-x-lg"></i>
            </button>
            <span class="main-badge">PRINCIPAL</span>
            <div class="preview-content"></div>
        </div>
    `;
    
    const content = div.querySelector('.preview-content');
    
    if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = file.name;
        content.appendChild(img);
    } else {
        const video = document.createElement('video');
        video.src = URL.createObjectURL(file);
        video.muted = true;
        video.preload = 'metadata';
        content.appendChild(video);
    }
    
    previewContainer.appendChild(div);
}

function removeFile(index) {
    // Supprimer le fichier du tableau
    filesQueue.splice(index, 1);
    
    // Recréer toutes les previews avec les nouveaux index
    renderAllPreviews();
    updateMediaInput();
}

function renderAllPreviews() {
    previewContainer.innerHTML = '';
    filesQueue.forEach((file, i) => createPreview(file, i));
}

function updateMediaInput() {
    // Mettre à jour les classes is-main sur les previews
    document.querySelectorAll('.media-preview-item').forEach((el, i) => {
        el.classList.toggle('is-main', i === 0);
    });
    
    // Le input file original contient déjà les fichiers via DataTransfer
    // Mais comme on ne peut pas modifier un FileList, on utilise une astuce :
    // On crée un nouveau FormData lors du submit
}

// Validation formulaire
document.getElementById('productForm').addEventListener('submit', function(e) {
    if (filesQueue.length === 0) {
        e.preventDefault();
        alert('Veuillez ajouter au moins une photo ou vidéo.');
        return false;
    }
    
    // Créer un nouveau DataTransfer pour mettre à jour l'input
    const dt = new DataTransfer();
    filesQueue.forEach(file => dt.items.add(file));
    mediaInput.files = dt.files;
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../partials/header.php';
echo $content;
require __DIR__ . '/../partials/footer.php';