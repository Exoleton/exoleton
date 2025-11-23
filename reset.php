<?php
require __DIR__ . '/auth.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$messages = [];

if ($token === '') {
    $errors[] = 'Jeton de réinitialisation manquant.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token !== '') {
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, name, email, role, reset_expires FROM users WHERE reset_token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = 'Jeton invalide ou déjà utilisé.';
        } elseif ($user['reset_expires'] && new DateTime($user['reset_expires']) < new DateTime()) {
            $errors[] = 'Le lien de réinitialisation a expiré.';
        } else {
            $hash = hash_password_secure($password);
            $upd = $pdo->prepare('UPDATE users SET password_hash = :hash, reset_token = NULL, reset_expires = NULL WHERE id = :id');
            $upd->execute(['hash' => $hash, 'id' => $user['id']]);

            login_user($user);
            $messages[] = 'Mot de passe mis à jour avec succès. Vous êtes connecté.';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Réinitialiser le mot de passe – Exoleton</title>
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
    </div>
  </header>

  <main class="container" style="padding-top: 7rem; padding-bottom: 4rem;">
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4 p-lg-5">
            <h1 class="h4 mb-3">Réinitialiser votre mot de passe</h1>
            <p class="text-muted">Saisissez un nouveau mot de passe pour sécuriser votre compte.</p>

            <?php if ($errors): ?>
              <div class="alert alert-danger" role="alert">
                <ul class="mb-0 ps-3">
                  <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <?php if ($messages): ?>
              <div class="alert alert-success" role="alert">
                <ul class="mb-0 ps-3">
                  <?php foreach ($messages as $message): ?>
                    <li><?= htmlspecialchars($message); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <?php if ($token !== '' && !$messages): ?>
              <form method="post" class="vstack gap-3">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">
                <div>
                  <label for="newPassword" class="form-label">Nouveau mot de passe</label>
                  <input type="password" class="form-control" id="newPassword" name="password" required minlength="8" autocomplete="new-password">
                  <div class="form-text">Les mots de passe sont stockés en <?= htmlspecialchars(preferred_password_algorithm()['label']); ?>.</div>
                </div>
                <div class="text-end">
                  <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </div>
              </form>
            <?php elseif ($messages): ?>
              <a class="btn btn-primary" href="account.php">Aller à mon compte</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
