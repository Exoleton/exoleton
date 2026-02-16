<?php
declare(strict_types=1);

class Supplier {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Récupère tous les fournisseurs avec pagination
     */
    public function getAll(int $page = 1, int $perPage = ITEMS_PER_PAGE): array {
    $offset = ($page - 1) * $perPage;
    
    $stmt = $this->pdo->prepare("
        SELECT s.*, 
               COUNT(DISTINCT p.id) as products_count
        FROM suppliers s
        LEFT JOIN products p ON p.supplier_id = s.id
        GROUP BY s.id
        ORDER BY s.name ASC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    /**
     * Compte total des fournisseurs
     */
    public function count(): int {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
    }
    
    /**
     * Récupère un fournisseur par ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Crée un nouveau fournisseur
     */
    public function create(array $data): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO suppliers (name, website, contact_email, contact_phone, is_active, is_featured)
            VALUES (:name, :website, :email, :phone, :active, :featured)
        ");
        
        $stmt->execute([
            ':name' => $data['name'],
            ':website' => $data['website'] ?: null,
            ':email' => $data['contact_email'] ?: null,
            ':phone' => $data['contact_phone'] ?: null,
            ':active' => $data['is_active'] ? 1 : 0,
            ':featured' => $data['is_featured'] ? 1 : 0
        ]);
        
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Met à jour un fournisseur
     */
    public function update(int $id, array $data): bool {
        $stmt = $this->pdo->prepare("
            UPDATE suppliers 
            SET name = :name,
                website = :website,
                contact_email = :email,
                contact_phone = :phone,
                is_active = :active,
                is_featured = :featured,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':website' => $data['website'] ?: null,
            ':email' => $data['contact_email'] ?: null,
            ':phone' => $data['contact_phone'] ?: null,
            ':active' => $data['is_active'] ? 1 : 0,
            ':featured' => $data['is_featured'] ? 1 : 0
        ]);
    }
    
    /**
     * Supprime un fournisseur (vérifie d'abord s'il a des produits)
     */
    public function delete(int $id): bool {
        // Vérifier s'il y a des produits associés
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Impossible de supprimer : ce fournisseur a des produits associés.");
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Toggle featured status
     */
    public function toggleFeatured(int $id): bool {
        $stmt = $this->pdo->prepare("
            UPDATE suppliers 
            SET is_featured = NOT is_featured 
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }
    
    /**
     * Liste pour dropdown (tous les actifs)
     */
    public function getForDropdown(): array {
        $stmt = $this->pdo->query("
            SELECT id, name 
            FROM suppliers 
            WHERE is_active = 1 
            ORDER BY name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}