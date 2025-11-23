<?php
require __DIR__ . '/auth.php';

$user = require_auth($pdo);
$currentUser = $user;
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Mon compte – Exoleton</title>
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
              <?php if (($user['role'] ?? 'customer') === 'admin'): ?>
                <li><a class="dropdown-item" href="admin.php">Administration</a></li>
              <?php endif; ?>
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
      <div class="col-lg-8">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4 p-lg-5">
            <h1 class="h4 mb-3">Mon compte</h1>
            <p class="text-muted">Consultez vos informations et accédez aux outils adaptés à votre profil.</p>

            <dl class="row mb-4">
              <dt class="col-sm-4">Nom complet</dt>
              <dd class="col-sm-8"><?= htmlspecialchars($user['name']); ?></dd>

              <dt class="col-sm-4">Email</dt>
              <dd class="col-sm-8"><?= htmlspecialchars($user['email']); ?></dd>

              <dt class="col-sm-4">Rôle</dt>
              <dd class="col-sm-8"><?= htmlspecialchars($user['role']); ?></dd>
            </dl>

            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-outline-primary" href="index.php">Retour à l'accueil</a>
              <?php if (($user['role'] ?? 'customer') === 'admin'): ?>
                <a class="btn btn-warning" href="admin.php">Accéder à l'administration</a>
              <?php endif; ?>
              <a class="btn btn-outline-danger" href="logout.php">Se déconnecter</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
