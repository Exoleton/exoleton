<?php
declare(strict_types=1);
$csrfToken = generate_csrf_token();  // Nouveau nom
?>
<!DOCTYPE html>
<html lang="<?= CURRENT_LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Administration' ?> – Exoleton</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #2563eb;
        }
        
        body {
            min-height: 100vh;
            background-color: #f8fafc;
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: #1e293b;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid #334155;
        }
        
        .sidebar-brand a {
            color: white;
            text-decoration: none;
            display: block;
        }
        
        .nav-link {
            color: #94a3b8;
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            transition: all 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: #334155;
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 0.5rem;
        }
        
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }
        
        .top-bar {
            background: white;
            padding: 1rem 2rem;
            margin: -2rem -2rem 2rem -2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
        }
        
        .badge-status {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            border-radius: 9999px;
        }
        
        .badge-active {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <nav class="sidebar">
            <div class="sidebar-brand">
                <a href="/<?= CURRENT_LANG ?>/">
                    <strong style="font-size: 1.25rem;">EXOLETON</strong>
                    <div class="small text-muted" style="font-size: 0.75rem;">Administration</div>
                </a>
            </div>
            
            <ul class="nav flex-column" style="list-style: none; padding: 0; margin: 0;">
                <li>
                    <a class="nav-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>" 
                       href="<?= admin_url() ?>">
                        <i class="bi bi-speedometer2"></i> Tableau de bord
                    </a>
                </li>
                
                <li class="mt-3 px-3">
                    <small class="text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.05em;">Catalogue</small>
                </li>
                
                <li>
                    <a class="nav-link <?= ($currentPage ?? '') === 'products' ? 'active' : '' ?>" 
                       href="<?= admin_url('products') ?>">
                        <i class="bi bi-box-seam"></i> Produits
                    </a>
                </li>
                
                <li>
                    <a class="nav-link <?= ($currentPage ?? '') === 'suppliers' ? 'active' : '' ?>" 
                       href="<?= admin_url('suppliers') ?>">
                        <i class="bi bi-building"></i> Fournisseurs
                    </a>
                </li>
                
                <li class="mt-3 px-3">
                    <small class="text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.05em;">Compte</small>
                </li>
                
                <li>
                    <a class="nav-link" href="/<?= CURRENT_LANG ?>/account">
                        <i class="bi bi-person"></i> Mon compte
                    </a>
                </li>
                
                <li>
                    <a class="nav-link text-danger" href="/<?= CURRENT_LANG ?>/logout">
                        <i class="bi bi-box-arrow-right"></i> Déconnexion
                    </a>
                </li>
            </ul>
        </nav>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-secondary d-md-none" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 class="h4 mb-0"><?= $pageTitle ?? 'Administration' ?></h1>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <select class="form-select form-select-sm" style="width: auto;" 
                            onchange="window.location.href='/' + this.value + '/admin/'">
                        <?php foreach (SUPPORTED_LANGS as $l): ?>
                            <option value="<?= $l ?>" <?= CURRENT_LANG === $l ? 'selected' : '' ?>>
                                <?= strtoupper($l) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?= sanitize($currentUser['name'] ?? 'Admin') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/<?= CURRENT_LANG ?>/account">Mon compte</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/<?= CURRENT_LANG ?>/logout">Déconnexion</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <?php $flash = get_flash(); ?>
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
                    <?= sanitize($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>