<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Media.php';

class MediaApiController {
    private PDO $pdo;
    private Media $mediaModel;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->mediaModel = new Media($pdo);
    }
    
    /**
     * Upload de médias pour un produit (AJAX)
     */
    public function upload(int $productId): void {
        header('Content-Type: application/json');
        
        try {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token invalide');
            }
            
            $files = $_FILES['media'] ?? [];
            if (empty($files['name'][0])) {
                throw new Exception('Aucun fichier reçu');
            }
            
            $currentCounts = $this->mediaModel->countByProduct($productId);
            $uploaded = [];
            
            foreach ($files['name'] as $i => $name) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $isVideo = in_array($extension, ALLOWED_VIDEO_TYPES);
                
                // Vérifier les limites
                if ($isVideo && $currentCounts['videos'] >= MAX_VIDEOS_PER_PRODUCT) {
                    continue;
                }
                if (!$isVideo && $currentCounts['images'] >= MAX_IMAGES_PER_PRODUCT) {
                    continue;
                }
                
                // Calculer le sort_order
                $sortOrder = ($currentCounts['images'] + $currentCounts['videos'] + $i + 1) * 10;
                
                $media = $this->mediaModel->upload($file, $productId, $sortOrder);
                $uploaded[] = $media;
                
                if ($isVideo) {
                    $currentCounts['videos']++;
                } else {
                    $currentCounts['images']++;
                }
            }
            
            echo json_encode(['success' => true, 'medias' => $uploaded]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Supprimer un média
     */
    public function delete(int $mediaId): void {
        header('Content-Type: application/json');
        
        try {
            $headers = getallheaders();
            $csrfToken = $headers['X-CSRF-Token'] ?? '';
            if (!verify_csrf_token($csrfToken)) {
                throw new Exception('Token invalide');
            }
            
            $this->mediaModel->delete($mediaId);
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Définir comme média principal
     */
    public function setMain(int $mediaId): void {
        header('Content-Type: application/json');
        
        try {
            $headers = getallheaders();
            $csrfToken = $headers['X-CSRF-Token'] ?? '';
            if (!verify_csrf_token($csrfToken)) {
                throw new Exception('Token invalide');
            }
            
            // Récupérer le product_id du média
            $stmt = $this->pdo->prepare("SELECT product_id FROM media WHERE id = ?");
            $stmt->execute([$mediaId]);
            $media = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$media) {
                throw new Exception('Média non trouvé');
            }
            
            $this->mediaModel->setMain($mediaId, $media['product_id']);
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Réordonner les médias
     */
    public function reorder(int $productId): void {
        header('Content-Type: application/json');
        
        try {
            $headers = getallheaders();
            $csrfToken = $headers['X-CSRF-Token'] ?? '';
            if (!verify_csrf_token($csrfToken)) {
                throw new Exception('Token invalide');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $order = $input['order'] ?? [];
            
            if (empty($order)) {
                throw new Exception('Ordre non spécifié');
            }
            
            $stmt = $this->pdo->prepare("UPDATE media SET sort_order = ? WHERE id = ? AND product_id = ?");
            foreach ($order as $index => $mediaId) {
                $sortOrder = ($index + 1) * 10;
                $stmt->execute([$sortOrder, $mediaId, $productId]);
            }
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}