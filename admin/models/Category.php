<?php
declare(strict_types=1);

class Category {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Récupère les catégories avec traduction
     */
    public function getAll(string $lang): array {
        $stmt = $this->pdo->prepare("
            SELECT c.*, ci.name as translated_name
            FROM categories c
            LEFT JOIN categories_i18n ci 
                ON ci.category_id = c.id AND ci.lang = :lang
            ORDER BY c.sort_order ASC, ci.name ASC
        ");
        $stmt->execute([':lang' => $lang]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère les catégories actives uniquement
     */
    public function getActive(string $lang): array {
        $stmt = $this->pdo->prepare("
            SELECT c.*, ci.name as translated_name
            FROM categories c
            LEFT JOIN categories_i18n ci 
                ON ci.category_id = c.id AND ci.lang = :lang
            WHERE c.is_active = 1
            ORDER BY c.sort_order ASC, ci.name ASC
        ");
        $stmt->execute([':lang' => $lang]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère les attributs d'une catégorie
     */
    public function getAttributes(int $categoryId, string $lang): array {
        $stmt = $this->pdo->prepare("
            SELECT a.*, ai.name as translated_name, ca.is_required
            FROM category_attributes ca
            JOIN attributes a ON a.id = ca.attribute_id
            LEFT JOIN attributes_i18n ai 
                ON ai.attribute_id = a.id AND ai.lang = :lang
            WHERE ca.category_id = :cat_id
            ORDER BY ca.sort_order ASC
        ");
        $stmt->execute([':cat_id' => $categoryId, ':lang' => $lang]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}