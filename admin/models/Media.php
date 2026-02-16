<?php
declare(strict_types=1);

class Media {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Upload et traitement d'un média
     */
    public function upload(array $file, int $productId, int $sortOrder, bool $isMain = false): array {
        // Validation
        $this->validateFile($file);
        
        // Créer le dossier produit
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . MEDIA_BASE_PATH . '/' . $productId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Générer nom unique
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $productId . '_' . time() . '_' . bin2hex(random_bytes(4));
        $basename = $filename . '.' . $extension;
        $filepath = $uploadDir . '/' . $basename;
        
        // Déterminer le type
        $type = $this->isVideo($extension) ? 'video' : 'image';
        
        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception("Erreur lors de l'upload du fichier.");
        }
        
        // Traitement image : redimension + miniature
        $thumbUrl = null;
        if ($type === 'image') {
            $this->processImage($filepath, $uploadDir, $filename, $extension);
            $thumbUrl = media_url($productId . '/' . $filename . '_thumb.' . $extension);
        }
        
        // Enregistrer en DB
        $url = media_url($productId . '/' . $basename);
        $mediaId = $this->saveToDatabase($productId, $type, $url, $sortOrder, $isMain);
        
        return [
            'id' => $mediaId,
            'type' => $type,
            'url' => $url,
            'thumb_url' => $thumbUrl,
            'filename' => $basename,
            'is_main' => $isMain
        ];
    }
    
    /**
     * Validation du fichier
     */
    private function validateFile(array $file): void {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur upload: code " . $file['error']);
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Vérifier type
        $isImage = in_array($extension, ALLOWED_IMAGE_TYPES);
        $isVideo = in_array($extension, ALLOWED_VIDEO_TYPES);
        
        if (!$isImage && !$isVideo) {
            throw new Exception("Type de fichier non autorisé: .$extension");
        }
        
        // Vérifier taille
        if ($isImage && $file['size'] > MAX_IMAGE_SIZE) {
            throw new Exception("Image trop lourde (max " . (MAX_IMAGE_SIZE / 1024 / 1024) . " Mo)");
        }
        if ($isVideo && $file['size'] > MAX_VIDEO_SIZE) {
            throw new Exception("Vidéo trop lourde (max " . (MAX_VIDEO_SIZE / 1024 / 1024) . " Mo)");
        }
        
        // Vérifier MIME type réel
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $validMimes = [
            'image/jpeg', 'image/png', 'image/webp',
            'video/mp4', 'video/webm', 'video/quicktime'
        ];
        
        if (!in_array($mime, $validMimes)) {
            throw new Exception("Type MIME non valide: $mime");
        }
    }
    
    /**
     * Traitement image avec GD
     */
    private function processImage(string $filepath, string $uploadDir, string $filename, string $extension): void {
        // Charger l'image
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $src = imagecreatefromjpeg($filepath);
                break;
            case 'png':
                $src = imagecreatefrompng($filepath);
                break;
            case 'webp':
                $src = imagecreatefromwebp($filepath);
                break;
            default:
                throw new Exception("Format image non supporté");
        }
        
        if (!$src) {
            throw new Exception("Impossible de charger l'image");
        }
        
        $origWidth = imagesx($src);
        $origHeight = imagesy($src);
        
        // Redimensionner si trop grand (max 2048)
        $maxSize = 2048;
        if ($origWidth > $maxSize || $origHeight > $maxSize) {
            $ratio = min($maxSize / $origWidth, $maxSize / $origHeight);
            $newWidth = (int)($origWidth * $ratio);
            $newHeight = (int)($origHeight * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Préservation transparence PNG
            if ($extension === 'png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            
            imagecopyresampled($resized, $src, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($src);
            $src = $resized;
            
            // Sauvegarder l'image redimensionnée
            $this->saveImage($src, $filepath, $extension);
        }
        
        // Créer miniature carrée 400x400
        $thumbSize = THUMB_SIZE;
        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
        
        // Calculer le crop centré
        $srcSize = min(imagesx($src), imagesy($src));
        $srcX = (imagesx($src) - $srcSize) / 2;
        $srcY = (imagesy($src) - $srcSize) / 2;
        
        // Préservation transparence
        if ($extension === 'png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        } else {
            $white = imagecolorallocate($thumb, 255, 255, 255);
            imagefill($thumb, 0, 0, $white);
        }
        
        imagecopyresampled($thumb, $src, 0, 0, $srcX, $srcY, $thumbSize, $thumbSize, $srcSize, $srcSize);
        
        $thumbPath = $uploadDir . '/' . $filename . '_thumb.' . $extension;
        $this->saveImage($thumb, $thumbPath, $extension);
        
        imagedestroy($src);
        imagedestroy($thumb);
    }
    
    /**
     * Sauvegarder image selon le format
     */
    private function saveImage($image, string $path, string $extension): void {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($image, $path, THUMB_QUALITY);
                break;
            case 'png':
                imagepng($image, $path, 8);
                break;
            case 'webp':
                imagewebp($image, $path, THUMB_QUALITY);
                break;
        }
    }
    
    /**
     * Enregistrer en base de données
     */
    private function saveToDatabase(int $productId, string $type, string $url, int $sortOrder, bool $isMain = false): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO media (product_id, type, url, sort_order, is_main, created_at)
            VALUES (:pid, :type, :url, :sort, :main, NOW())
        ");
        
        $stmt->execute([
            ':pid' => $productId,
            ':type' => $type,
            ':url' => $url,
            ':sort' => $sortOrder,
            ':main' => $isMain ? 1 : 0
        ]);
        
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Vérifier si c'est une vidéo
     */
    private function isVideo(string $extension): bool {
        return in_array($extension, ALLOWED_VIDEO_TYPES);
    }
    
/**
 * Récupérer les médias d'un produit
 */
public function getByProduct(int $productId): array {
    $stmt = $this->pdo->prepare("
        SELECT 
            id,
            product_id,
            variant_id,
            type,
            url,
            sort_order,
            is_main,
            created_at,
            CASE 
                WHEN type = 'image' THEN REPLACE(url, CONCAT('.', SUBSTRING_INDEX(url, '.', -1)), CONCAT('_thumb.', SUBSTRING_INDEX(url, '.', -1)))
                ELSE NULL 
            END as thumb_url
        FROM media 
        WHERE product_id = ? 
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute([$productId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    /**
     * Définir l'image principale
     */
    public function setMain(int $mediaId, int $productId): void {
        // Retirer le flag des autres
        $this->pdo->prepare("
            UPDATE media SET is_main = 0 WHERE product_id = ?
        ")->execute([$productId]);
        
        // Définir le nouveau main
        $this->pdo->prepare("
            UPDATE media SET is_main = 1 WHERE id = ?
        ")->execute([$mediaId]);
    }
    
    /**
     * Supprimer un média
     */
    public function delete(int $mediaId): void {
        $stmt = $this->pdo->prepare("SELECT * FROM media WHERE id = ?");
        $stmt->execute([$mediaId]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$media) return;
        
        // Supprimer les fichiers physiques
        $filepath = $_SERVER['DOCUMENT_ROOT'] . $media['url'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Supprimer la miniature si image
        if ($media['type'] === 'image') {
            $pathinfo = pathinfo($filepath);
            $thumbPath = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_thumb.' . $pathinfo['extension'];
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
        }
        
		// Si on supprime le main, définir le suivant comme main
		if (!empty($media['is_main'])) {
			$next = $this->pdo->prepare("
				SELECT id FROM media 
				WHERE product_id = ? AND id != ? 
				ORDER BY sort_order ASC, id ASC 
				LIMIT 1
			");
			$next->execute([$media['product_id'], $mediaId]);
			if ($nextMedia = $next->fetch(PDO::FETCH_ASSOC)) {
				$this->setMain((int)$nextMedia['id'], (int)$media['product_id']);  // <-- CAST EN INT
			}
		}
        
        // Supprimer en DB
        $this->pdo->prepare("DELETE FROM media WHERE id = ?")->execute([$mediaId]);
    }
    
    /**
     * Compter les médias d'un produit
     */
    public function countByProduct(int $productId): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN type = 'image' THEN 1 ELSE 0 END), 0) as images,
                COALESCE(SUM(CASE WHEN type = 'video' THEN 1 ELSE 0 END), 0) as videos
            FROM media 
            WHERE product_id = ?
        ");
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'images' => (int)$result['images'],
            'videos' => (int)$result['videos']
        ];
    }
    
    /**
     * Récupérer un média par ID
     */
    public function getById(int $mediaId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM media WHERE id = ?");
        $stmt->execute([$mediaId]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);
        return $media ?: null;
    }
}