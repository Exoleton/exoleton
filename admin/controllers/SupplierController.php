<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Supplier.php';

class SupplierController {
    private PDO $pdo;
    private Supplier $model;
    private string $lang;
    
    public function __construct(PDO $pdo, string $lang) {
        $this->pdo = $pdo;
        $this->model = new Supplier($pdo);
        $this->lang = $lang;
    }
    
    /**
     * Liste des fournisseurs
     */
    public function index(): void {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = ITEMS_PER_PAGE;
        
        $suppliers = $this->model->getAll($page, $perPage);
        $total = $this->model->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        
        require __DIR__ . '/../views/suppliers/list.php';
    }
    
    /**
     * Formulaire création/édition
     */
    public function form(?int $id = null): void {
        $supplier = $id ? $this->model->getById($id) : null;
        $isEdit = (bool) $supplier;
        
        $errors = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $errors[] = "Token de sécurité invalide.";
            } else {
                $data = [
                    'name' => trim($_POST['name'] ?? ''),
                    'website' => trim($_POST['website'] ?? ''),
                    'contact_email' => trim($_POST['contact_email'] ?? ''),
                    'contact_phone' => trim($_POST['contact_phone'] ?? ''),
                    'is_active' => isset($_POST['is_active']),
                    'is_featured' => isset($_POST['is_featured'])
                ];
                
                if (empty($data['name'])) {
                    $errors[] = "Le nom du fournisseur est obligatoire.";
                }
                
                if (empty($errors)) {
                    try {
                        if ($isEdit) {
                            $this->model->update($id, $data);
                            set_flash('success', "Fournisseur mis à jour avec succès.");
                        } else {
                            $id = $this->model->create($data);
                            set_flash('success', "Fournisseur créé avec succès.");
                        }
                        header('Location: ' . admin_url('suppliers'));
                        exit;
                        
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                }
            }
            
            // Repopulate form
            $supplier = array_merge($supplier ?? [], $data);
        }
        
        require __DIR__ . '/../views/suppliers/form.php';
    }
    
    /**
     * Suppression
     */
    public function delete(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . admin_url('suppliers'));
            exit;
        }
        
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('error', "Token de sécurité invalide.");
            header('Location: ' . admin_url('suppliers'));
            exit;
        }
        
        try {
            $this->model->delete($id);
            set_flash('success', "Fournisseur supprimé avec succès.");
        } catch (Exception $e) {
            set_flash('error', $e->getMessage());
        }
        
        header('Location: ' . admin_url('suppliers'));
        exit;
    }
}