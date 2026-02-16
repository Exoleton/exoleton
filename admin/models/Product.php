<?php
declare(strict_types=1);

class Product {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Récupère tous les produits avec pagination et filtres
     */
    public function getAll(string $lang, array $filters = [], int $page = 1, int $perPage = ITEMS_PER_PAGE): array {
        $where = ["1=1"];
        $params = [':lang' => $lang];
        
        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = :cat_id";
            $params[':cat_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['supplier_id'])) {
            $where[] = "p.supplier_id = :sup_id";
            $params[':sup_id'] = $filters['supplier_id'];
        }
        
        if (isset($filters['is_active'])) {
            $where[] = "p.is_active = :active";
            $params[':active'] = $filters['is_active'] ? 1 : 0;
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(pi.title LIKE :search OR pi.slug LIKE :search OR p.sku LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        $sql = "
            SELECT 
                p.*,
                pi.title, pi.slug, pi.description,
                ci.name as category_name,
                s.name as supplier_name,
                MIN(pv.price) as min_price,
                COUNT(DISTINCT pv.id) as variants_count
            FROM products p
            LEFT JOIN products_i18n pi ON pi.product_id = p.id AND pi.lang = :lang
            LEFT JOIN categories_i18n ci ON ci.category_id = p.category_id AND ci.lang = :lang
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            LEFT JOIN product_variants pv ON pv.product_id = p.id AND pv.is_active = 1
            WHERE {$whereClause}
            GROUP BY p.id
            ORDER BY p.id DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compte les produits avec filtres
     */
    public function count(array $filters = []): int {
        $where = ["1=1"];
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $where[] = "category_id = :cat_id";
            $params[':cat_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['supplier_id'])) {
            $where[] = "supplier_id = :sup_id";
            $params[':sup_id'] = $filters['supplier_id'];
        }
        
        if (isset($filters['is_active'])) {
            $where[] = "is_active = :active";
            $params[':active'] = $filters['is_active'] ? 1 : 0;
        }
        
        $sql = "SELECT COUNT(*) FROM products WHERE " . implode(' AND ', $where);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Récupère un produit complet avec toutes ses relations
     */
    public function getById(int $id, string $lang): ?array {
        // Produit de base
        $stmt = $this->pdo->prepare("
            SELECT p.*, pi.title, pi.slug, pi.description, pi.meta_title, pi.meta_description
            FROM products p
            LEFT JOIN products_i18n pi ON pi.product_id = p.id AND pi.lang = :lang
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id, ':lang' => $lang]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) return null;
        
        // Toutes les traductions - CORRECTION ICI
        $stmt = $this->pdo->prepare("
            SELECT lang, title, slug, description, meta_title, meta_description
            FROM products_i18n 
            WHERE product_id = ?
        ");
        $stmt->execute([$id]);
        $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Réorganiser par langue
        $product['translations'] = [];
        foreach ($translations as $trans) {
            $langKey = $trans['lang'];
            $product['translations'][$langKey] = [
                'title' => $trans['title'],
                'slug' => $trans['slug'],
                'description' => $trans['description'],
                'meta_title' => $trans['meta_title'],
                'meta_description' => $trans['meta_description']
            ];
        }
        
        // Variantes
        $stmt = $this->pdo->prepare("
            SELECT * FROM product_variants 
            WHERE product_id = ? 
            ORDER BY price ASC
        ");
        $stmt->execute([$id]);
        $product['variants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Attributs du produit - indexés par attribute_id
        $stmt = $this->pdo->prepare("
            SELECT pav.*, a.code, a.data_type, a.unit
            FROM product_attribute_values pav
            JOIN attributes a ON a.id = pav.attribute_id
            WHERE pav.product_id = ?
        ");
        $stmt->execute([$id]);
        $attrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $product['attributes'] = [];
        foreach ($attrs as $attr) {
            $product['attributes'][$attr['attribute_id']] = $attr['value_select'] 
                ?? $attr['value_text'] 
                ?? $attr['value_decimal'] 
                ?? $attr['value_int'] 
                ?? $attr['value_bool'];
        }
        
        // Médias
        $stmt = $this->pdo->prepare("
            SELECT * FROM media 
            WHERE product_id = ? 
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$id]);
        $product['media'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $product;
    }
    
    /**
     * Crée un nouveau produit avec toutes ses relations
     */
    public function create(array $data, string $defaultLang): int {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Créer le produit de base
            $stmt = $this->pdo->prepare("
                INSERT INTO products (category_id, supplier_id, sku, brand, is_active)
                VALUES (:cat_id, :sup_id, :sku, :brand, :active)
            ");
            $stmt->execute([
                ':cat_id' => $data['category_id'],
                ':sup_id' => $data['supplier_id'] ?: null,
                ':sku' => $data['sku'] ?: null,
                ':brand' => $data['brand'] ?: null,
                ':active' => $data['is_active'] ? 1 : 0
            ]);
            
            $productId = (int) $this->pdo->lastInsertId();
            
            // 2. Créer les traductions pour toutes les langues fournies
            foreach ($data['translations'] as $lang => $trans) {
                if (empty($trans['title'])) continue;
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO products_i18n 
                    (product_id, lang, title, slug, description, meta_title, meta_description)
                    VALUES (:pid, :lang, :title, :slug, :desc, :meta_title, :meta_desc)
                ");
                $stmt->execute([
                    ':pid' => $productId,
                    ':lang' => $lang,
                    ':title' => $trans['title'],
                    ':slug' => $trans['slug'] ?: slugify($trans['title']),
                    ':desc' => $trans['description'] ?: null,
                    ':meta_title' => $trans['meta_title'] ?: null,
                    ':meta_desc' => $trans['meta_description'] ?: null
                ]);
            }
            
            // 3. Créer une variante par défaut si prix fourni
            if (!empty($data['base_price'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO product_variants 
                    (product_id, sku, price, vat, stock_qty, is_active)
                    VALUES (:pid, :sku, :price, :vat, :stock, 1)
                ");
                $stmt->execute([
                    ':pid' => $productId,
                    ':sku' => ($data['sku'] ?: 'VAR-') . '-001',
                    ':price' => $data['base_price'],
                    ':vat' => $data['vat'] ?? 20.00,
                    ':stock' => $data['stock_qty'] ?? 0
                ]);
            }
            
            $this->pdo->commit();
            return $productId;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Met à jour un produit complet
     */
    public function update(int $id, array $data, string $currentLang): bool {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Mettre à jour le produit de base
            $stmt = $this->pdo->prepare("
                UPDATE products 
                SET category_id = :cat_id,
                    supplier_id = :sup_id,
                    sku = :sku,
                    brand = :brand,
                    is_active = :active
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':cat_id' => $data['category_id'],
                ':sup_id' => $data['supplier_id'] ?: null,
                ':sku' => $data['sku'] ?: null,
                ':brand' => $data['brand'] ?: null,
                ':active' => $data['is_active'] ? 1 : 0
            ]);
            
            // 2. Upsert les traductions
            foreach ($data['translations'] as $lang => $trans) {
                if (empty($trans['title'])) continue;
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO products_i18n 
                    (product_id, lang, title, slug, description, meta_title, meta_description)
                    VALUES (:pid, :lang, :title, :slug, :desc, :meta_title, :meta_desc)
                    ON DUPLICATE KEY UPDATE
                        title = VALUES(title),
                        slug = VALUES(slug),
                        description = VALUES(description),
                        meta_title = VALUES(meta_title),
                        meta_description = VALUES(meta_description)
                ");
                $stmt->execute([
                    ':pid' => $id,
                    ':lang' => $lang,
                    ':title' => $trans['title'],
                    ':slug' => $trans['slug'] ?: slugify($trans['title']),
                    ':desc' => $trans['description'] ?: null,
                    ':meta_title' => $trans['meta_title'] ?: null,
                    ':meta_desc' => $trans['meta_description'] ?: null
                ]);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
	/**
	 * Supprime un produit et toutes ses relations
	 */
	public function delete(int $id): bool {
		try {
			$this->pdo->beginTransaction();
			
			// Supprimer les fichiers médias physiques d'abord
			$stmt = $this->pdo->prepare("SELECT id FROM media WHERE product_id = ?");
			$stmt->execute([$id]);
			$mediaIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
			
			if (!empty($mediaIds)) {
				require_once __DIR__ . '/Media.php';
				$mediaModel = new Media($this->pdo);
				foreach ($mediaIds as $mediaId) {
					$mediaModel->delete((int)$mediaId);  // <-- CAST EN INT ICI
				}
			}
			
			// Supprimer dans l'ordre pour respecter les FK
			$tables = [
				'product_attribute_values',
				'product_variants',
				'products_i18n',
				'media',
				'featured_items',
				'product_tags'
			];
			
			foreach ($tables as $table) {
				$this->pdo->prepare("DELETE FROM {$table} WHERE product_id = ?")->execute([$id]);
			}
			
			$this->pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
			
			$this->pdo->commit();
			return true;
			
		} catch (Exception $e) {
			$this->pdo->rollBack();
			throw $e;
		}
	}
		
	/**
     * Sauvegarde les attributs d'un produit
     */
    public function saveAttributes(int $productId, array $attributes): void {
        $stmt = $this->pdo->prepare("DELETE FROM product_attribute_values WHERE product_id = ?");
        $stmt->execute([$productId]);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO product_attribute_values 
            (product_id, attribute_id, value_int, value_decimal, value_bool, value_text, value_select)
            VALUES (:pid, :aid, :vint, :vdec, :vbool, :vtext, :vsel)
        ");
        
        foreach ($attributes as $attrId => $value) {
            if ($value === '' || $value === null) continue;
            
            // Déterminer le type et la colonne appropriée
            $typeStmt = $this->pdo->prepare("SELECT data_type FROM attributes WHERE id = ?");
            $typeStmt->execute([$attrId]);
            $dataType = $typeStmt->fetchColumn();
            
            $params = [
                ':pid' => $productId,
                ':aid' => $attrId,
                ':vint' => null,
                ':vdec' => null,
                ':vbool' => null,
                ':vtext' => null,
                ':vsel' => null
            ];
            
            switch ($dataType) {
                case 'int':
                    $params[':vint'] = (int) $value;
                    break;
                case 'decimal':
                    $params[':vdec'] = (float) $value;
                    break;
                case 'bool':
                    $params[':vbool'] = (bool) $value ? 1 : 0;
                    break;
                case 'text':
                    $params[':vtext'] = $value;
                    break;
                case 'select':
                    $params[':vsel'] = $value;
                    break;
            }
            
            $stmt->execute($params);
        }
    }
}