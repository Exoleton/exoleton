<?php
require __DIR__ . '/auth.php';

$user = require_admin($pdo);
$currentUser = $user;
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Administration – Exoleton</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="bg-light">
  <header class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="assets/img/logo.png" alt="Exoleton" width="272" height="1000" class="me-2">
      </a>
      <nav class="ms-auto">
        <ul class="navbar-nav align-items-lg-center flex-row gap-3">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Bonjour <?= htmlspecialchars($user['name']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
              <li><a class="dropdown-item" href="account.php">Mon compte</a></li>
              <li><a class="dropdown-item" href="admin.php">Administration</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php">Se déconnecter</a></li>
            </ul>
          </li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" style="padding-top: 7rem; padding-bottom: 4rem;">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4 p-lg-5">
            <h1 class="h4 mb-3">Espace d'administration</h1>
            <p class="text-muted">Vous êtes connecté avec les droits administrateur. Ajoutez ici vos outils de gestion (catalogue, utilisateurs, contenu…).</p>

            <div class="alert alert-info">Cet espace est prêt à accueillir les modules back-office (gestion des produits, des guides, des utilisateurs…).</div>

            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-outline-primary" href="index.php">Retour au site</a>
              <a class="btn btn-outline-secondary" href="account.php">Mon compte</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
