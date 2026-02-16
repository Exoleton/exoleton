<?php
declare(strict_types=1);
require_once __DIR__ . '/../models/Media.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Supplier.php';

class ProductController {
    private PDO $pdo;
    private Product $productModel;
    private Category $categoryModel;
    private Supplier $supplierModel;
    private string $lang;
    
    public function __construct(PDO $pdo, string $lang) {
        $this->pdo = $pdo;
        $this->productModel = new Product($pdo);
        $this->categoryModel = new Category($pdo);
        $this->supplierModel = new Supplier($pdo);
        $this->lang = $lang;
    }
    
    /**
     * Liste des produits avec filtres et pagination
     */
    public function index(): void {
        $filters = [
            'category_id' => $_GET['category'] ?? null,
            'supplier_id' => $_GET['supplier'] ?? null,
            'is_active' => isset($_GET['active']) && $_GET['active'] !== '' ? ($_GET['active'] === '1') : null,
            'search' => $_GET['search'] ?? null
        ];
        
        // Nettoyer les filtres vides
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = ITEMS_PER_PAGE;
        
        $products = $this->productModel->getAll($this->lang, $filters, $page, $perPage);
        $total = $this->productModel->count($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        
        // Données pour les filtres
        $categories = $this->categoryModel->getActive($this->lang);
        $suppliers = $this->supplierModel->getForDropdown();
        
        require __DIR__ . '/../views/products/list.php';
    }
    
	/**
	 * Formulaire de création de produit
	 */
	public function create(): void {
		$categories = $this->categoryModel->getActive($this->lang);
		$suppliers = $this->supplierModel->getForDropdown();
		
		$errors = [];
		$product = null;
		
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
				$errors[] = "Token de sécurité invalide.";
			} else {
				// Vérifier les médias
				$mediaFiles = $_FILES['media'] ?? [];
				$hasMedia = !empty($mediaFiles['name'][0]);
				
				if (!$hasMedia) {
					$errors[] = "Au moins une photo ou vidéo est obligatoire.";
				}
				
				$data = $this->extractFormData($_POST);
				
				// Validation
				if (empty($data['translations'][$this->lang]['title'])) {
					$errors[] = "Le titre est obligatoire.";
				}
				if (empty($data['category_id'])) {
					$errors[] = "La catégorie est obligatoire.";
				}
				
				if (empty($errors)) {
					try {
						// La transaction est gérée DANS Product::create()
						// 1. Créer le produit (avec sa transaction interne)
						$productId = $this->productModel->create($data, $this->lang);
						
						// 2. Upload les médias (hors transaction, car fichiers physiques)
						if ($hasMedia) {
							$mediaModel = new Media($this->pdo);
							$counts = ['images' => 0, 'videos' => 0];
							
							foreach ($mediaFiles['name'] as $i => $name) {
								if ($mediaFiles['error'][$i] !== UPLOAD_ERR_OK) continue;
								
								$file = [
									'name' => $mediaFiles['name'][$i],
									'type' => $mediaFiles['type'][$i],
									'tmp_name' => $mediaFiles['tmp_name'][$i],
									'error' => $mediaFiles['error'][$i],
									'size' => $mediaFiles['size'][$i]
								];
								
								// Vérifier les limites
								$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
								$isVideo = in_array($extension, ALLOWED_VIDEO_TYPES);
								
								if ($isVideo && $counts['videos'] >= MAX_VIDEOS_PER_PRODUCT) {
									continue;
								}
								if (!$isVideo && $counts['images'] >= MAX_IMAGES_PER_PRODUCT) {
									continue;
								}
								
								// Upload - isMain = true pour le premier média
								$sortOrder = ($i + 1) * 10;
								$isMain = ($i === 0); // Premier média = principal
								$media = $mediaModel->upload($file, $productId, $sortOrder, $isMain);
								
								if ($media['type'] === 'video') {
									$counts['videos']++;
								} else {
									$counts['images']++;
								}
							}
						}
						
						set_flash('success', "Produit créé avec succès.");
						header('Location: ' . admin_url('products/edit/' . $productId));
						exit;
						
					} catch (Exception $e) {
						$errors[] = $e->getMessage();
					}
				}
			}
		}
		
		require __DIR__ . '/../views/products/form.php';
	}
		
	/**
     * Édition complète d'un produit
     */
    public function edit(int $id): void {
        $product = $this->productModel->getById($id, $this->lang);
        
        if (!$product) {
            set_flash('error', "Produit non trouvé.");
            header('Location: ' . admin_url('products'));
            exit;
        }
        
        $categories = $this->categoryModel->getActive($this->lang);
        $suppliers = $this->supplierModel->getForDropdown();
        $categoryAttributes = $this->categoryModel->getAttributes((int)$product['category_id'], $this->lang);
        
        $errors = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $errors[] = "Token de sécurité invalide.";
            } else {
                $action = $_POST['form_action'] ?? 'save_product';
                
                try {
                    switch ($action) {
                        case 'save_product':
                            $data = $this->extractFormData($_POST);
                            $this->productModel->update($id, $data, $this->lang);
                            
                            // Sauvegarder les attributs
                            if (isset($_POST['attributes'])) {
                                $this->productModel->saveAttributes($id, $_POST['attributes']);
                            }
                            
                            set_flash('success', "Produit mis à jour avec succès.");
                            break;
                            
                        case 'add_variant':
                            $this->addVariant($id, $_POST);
                            set_flash('success', "Variante ajoutée avec succès.");
                            break;
                            
                        case 'delete_variant':
                            $this->deleteVariant((int)($_POST['variant_id'] ?? 0));
                            set_flash('success', "Variante supprimée avec succès.");
                            break;
                    }
                    
                    // Recharger la page pour voir les changements
                    header('Location: ' . admin_url('products/edit/' . $id));
                    exit;
                    
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
        
        require __DIR__ . '/../views/products/edit.php';
    }
    
    /**
     * Suppression d'un produit
     */
    public function delete(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . admin_url('products'));
            exit;
        }
        
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('error', "Token de sécurité invalide.");
            header('Location: ' . admin_url('products'));
            exit;
        }
        
        try {
            $this->productModel->delete($id);
            set_flash('success', "Produit supprimé avec succès.");
        } catch (Exception $e) {
            set_flash('error', $e->getMessage());
        }
        
        header('Location: ' . admin_url('products'));
        exit;
    }
    
    /**
     * Extraction et validation des données du formulaire
     */
    private function extractFormData(array $post): array {
        $data = [
            'category_id' => (int)($post['category_id'] ?? 0),
            'supplier_id' => !empty($post['supplier_id']) ? (int)$post['supplier_id'] : null,
            'sku' => trim($post['sku'] ?? ''),
            'brand' => trim($post['brand'] ?? ''),
            'is_active' => isset($post['is_active']),
            'base_price' => !empty($post['base_price']) ? (float)$post['base_price'] : null,
            'vat' => (float)($post['vat'] ?? 20.00),
            'stock_qty' => (int)($post['stock_qty'] ?? 0),
            'translations' => []
        ];
        
        // Extraire les traductions pour toutes les langues supportées
        foreach (SUPPORTED_LANGS as $lang) {
            $title = trim($post["title_{$lang}"] ?? '');
            if (!empty($title)) {
                $data['translations'][$lang] = [
                    'title' => $title,
                    'slug' => !empty($post["slug_{$lang}"]) ? trim($post["slug_{$lang}"]) : slugify($title),
                    'description' => trim($post["description_{$lang}"] ?? ''),
                    'meta_title' => trim($post["meta_title_{$lang}"] ?? ''),
                    'meta_description' => trim($post["meta_description_{$lang}"] ?? '')
                ];
            }
        }
        
        return $data;
    }
    
    /**
     * Ajoute une variante au produit
     */
    private function addVariant(int $productId, array $data): void {
        if (empty($data['variant_price'])) {
            throw new Exception("Le prix est obligatoire pour créer une variante.");
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO product_variants 
            (product_id, sku, price, vat, stock_qty, is_active)
            VALUES (:pid, :sku, :price, :vat, :stock, :active)
        ");
        
        $stmt->execute([
            ':pid' => $productId,
            ':sku' => !empty($data['variant_sku']) ? $data['variant_sku'] : null,
            ':price' => (float)$data['variant_price'],
            ':vat' => (float)($data['variant_vat'] ?? 20.00),
            ':stock' => (int)($data['variant_stock'] ?? 0),
            ':active' => isset($data['variant_active']) ? 1 : 0
        ]);
    }
    
    /**
     * Supprime une variante
     */
    private function deleteVariant(int $variantId): void {
        if ($variantId <= 0) {
            throw new Exception("ID de variante invalide.");
        }
        
        // Vérifier qu'il reste au moins une variante après suppression
        $stmt = $this->pdo->prepare("
            SELECT product_id, COUNT(*) as total 
            FROM product_variants 
            WHERE product_id = (SELECT product_id FROM product_variants WHERE id = ?)
        ");
        $stmt->execute([$variantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['total'] <= 1) {
            throw new Exception("Impossible de supprimer la dernière variante d'un produit.");
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM product_variants WHERE id = ?");
        $stmt->execute([$variantId]);
    }
}