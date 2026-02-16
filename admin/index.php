<?php
declare(strict_types=1);

// DÉBOGAGE - À RETIRER APRÈS
error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    // 1. D'abord l'auth racine qui charge db.php et définit $pdo + require_auth()
    require_once __DIR__ . '/../auth.php';
    
    // 2. Ensuite config qui utilise $pdo et la session déjà démarrée
    require_once __DIR__ . '/config.php';
    
} catch (Throwable $e) {
    die('ERREUR: ' . $e->getMessage() . '<br>Fichier: ' . $e->getFile() . '<br>Ligne: ' . $e->getLine());
}

/**
 * Vérifie l'accès admin (utilise require_auth() existant)
 */
function require_admin_access(): array {
    global $pdo;  // Récupère $pdo défini par auth.php racine
    
    // Utilise require_auth() du système existant
    $user = require_auth($pdo);
    
    // Vérifier le rôle admin
    if (($user['role'] ?? 'customer') !== 'admin') {
        set_flash('error', "Accès réservé aux administrateurs.");
        header('Location: /' . CURRENT_LANG . '/account');
        exit;
    }
    
    return $user;
}

// CSRF
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

// Vérification de l'admin
$currentUser = require_admin_access();

// Charger les contrôleurs
require_once __DIR__ . '/controllers/SupplierController.php';
require_once __DIR__ . '/controllers/ProductController.php';

// Récupération du chemin admin depuis .htaccess (paramètre admin_path)
$adminPath = $_GET['admin_path'] ?? '';
$segments = array_filter(explode('/', $adminPath), 'strlen');
$segments = array_values($segments);

$method = $_SERVER['REQUEST_METHOD'];

// === ROUTES API MÉDIAS (prioritaires) ===
// Reconstruire le chemin complet pour les regex
$fullPath = implode('/', $segments);

// API: Upload médias pour un produit  →  api/products/123/media
if (preg_match('#^api/products/(\d+)/media$#', $fullPath, $matches) && $method === 'POST') {
    require_once __DIR__ . '/controllers/MediaApiController.php';
    $controller = new MediaApiController($pdo);
    $controller->upload((int)$matches[1]);
    exit;
}

// API: Réordonner les médias  →  api/products/123/media/reorder
if (preg_match('#^api/products/(\d+)/media/reorder$#', $fullPath, $matches) && $method === 'POST') {
    require_once __DIR__ . '/controllers/MediaApiController.php';
    $controller = new MediaApiController($pdo);
    $controller->reorder((int)$matches[1]);
    exit;
}

// API: Définir comme média principal  →  api/media/123/set-main
if (preg_match('#^api/media/(\d+)/set-main$#', $fullPath, $matches) && $method === 'POST') {
    require_once __DIR__ . '/controllers/MediaApiController.php';
    $controller = new MediaApiController($pdo);
    $controller->setMain((int)$matches[1]);
    exit;
}

// API: Supprimer un média  →  api/media/123
if (preg_match('#^api/media/(\d+)$#', $fullPath, $matches) && $method === 'DELETE') {
    require_once __DIR__ . '/controllers/MediaApiController.php';
    $controller = new MediaApiController($pdo);
    $controller->delete((int)$matches[1]);
    exit;
}

// Routes standards (hors API)
$controller = $segments[0] ?? 'dashboard';
$action = $segments[1] ?? 'index';
$id = isset($segments[2]) ? (int)$segments[2] : null;

try {
    switch ($controller) {
        case 'api':
            // Si on arrive ici, c'est une route API non reconnue
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'API endpoint not found']);
            exit;
            
        case 'dashboard':
        case '':
            $pageTitle = 'Tableau de bord';
            $currentPage = 'dashboard';
            ob_start();
            ?>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card text-white bg-primary h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-uppercase mb-2">Produits</h6>
                                    <h2 class="mb-0">
                                        <?php
                                        $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                                        echo (int)$count;
                                        ?>
                                    </h2>
                                </div>
                                <i class="bi bi-box-seam fs-1 opacity-50"></i>
                            </div>
                            <a href="<?= admin_url('products') ?>" class="btn btn-light btn-sm mt-3">Gérer les produits</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card text-white bg-success h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-uppercase mb-2">Fournisseurs</h6>
                                    <h2 class="mb-0">
                                        <?php
                                        $count = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
                                        echo (int)$count;
                                        ?>
                                    </h2>
                                </div>
                                <i class="bi bi-building fs-1 opacity-50"></i>
                            </div>
                            <a href="<?= admin_url('suppliers') ?>" class="btn btn-light btn-sm mt-3">Gérer les fournisseurs</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card text-white bg-info h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-uppercase mb-2">Catégories</h6>
                                    <h2 class="mb-0">
                                        <?php
                                        $count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
                                        echo (int)$count;
                                        ?>
                                    </h2>
                                </div>
                                <i class="bi bi-folder fs-1 opacity-50"></i>
                            </div>
                            <span class="small mt-3 d-block">Gestion à venir</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Produits récents</h5>
                </div>
                <div class="card-body">
                    <?php
                    $recentProducts = $pdo->prepare("
                        SELECT p.id, pi.title, p.created_at 
                        FROM products p
                        LEFT JOIN products_i18n pi ON pi.product_id = p.id AND pi.lang = ?
                        ORDER BY p.created_at DESC 
                        LIMIT 5
                    ");
                    $recentProducts->execute([CURRENT_LANG]);
                    $recent = $recentProducts->fetchAll();
                    ?>
                    
                    <?php if (empty($recent)): ?>
                        <p class="text-muted mb-0">Aucun produit.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recent as $p): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= sanitize($p['title'] ?: 'Produit #' . $p['id']) ?></strong>
                                        <small class="text-muted d-block">Créé le <?= format_datetime($p['created_at']) ?></small>
                                    </div>
                                    <a href="<?= admin_url('products/edit/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary">Éditer</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            $content = ob_get_clean();
            require __DIR__ . '/views/partials/header.php';
            echo $content;
            require __DIR__ . '/views/partials/footer.php';
            break;
            
        case 'suppliers':
            $ctrl = new SupplierController($pdo, CURRENT_LANG);
            switch ($action) {
                case 'create':
                    $ctrl->form();
                    break;
                case 'edit':
                    if (!$id) throw new Exception("ID requis.");
                    $ctrl->form($id);
                    break;
                case 'delete':
                    if (!$id) throw new Exception("ID requis.");
                    $ctrl->delete($id);
                    break;
                default:
                    $ctrl->index();
            }
            break;
            
        case 'products':
            $ctrl = new ProductController($pdo, CURRENT_LANG);
            switch ($action) {
                case 'create':
                    $ctrl->create();
                    break;
                case 'edit':
                    if (!$id) throw new Exception("ID requis.");
                    $ctrl->edit($id);
                    break;
                case 'delete':
                    if (!$id) throw new Exception("ID requis.");
                    $ctrl->delete($id);
                    break;
                default:
                    $ctrl->index();
            }
            break;
            
        default:
            http_response_code(404);
            $pageTitle = 'Page non trouvée';
            $currentPage = '';
            ob_start();
            echo '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Page non trouvée.</div>';
            $content = ob_get_clean();
            require __DIR__ . '/views/partials/header.php';
            echo $content;
            require __DIR__ . '/views/partials/footer.php';
    }
    
} catch (Exception $e) {
    http_response_code(500);
    $pageTitle = 'Erreur';
    $currentPage = '';
    ob_start();
    echo '<div class="alert alert-danger"><strong>Erreur :</strong> ' . sanitize($e->getMessage()) . '</div>';
    $content = ob_get_clean();
    require __DIR__ . '/views/partials/header.php';
    echo $content;
    require __DIR__ . '/views/partials/footer.php';
}