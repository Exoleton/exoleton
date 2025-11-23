<?php
require __DIR__ . '/auth.php';

$currentUser = current_user($pdo);
if ($currentUser) {
    header('Location: account.php');
    exit;
}

$errors = [];
$messages = [];
$activeView = $_POST['view'] ?? $_GET['view'] ?? 'login';

function sanitize_field(string $value): string
{
    return trim(filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $activeView = 'login';
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $errors[] = 'Merci de renseigner votre email et votre mot de passe.';
        } else {
            $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $algo = preferred_password_algorithm();
                if (password_needs_rehash($user['password_hash'], $algo['algo'])) {
                    $newHash = hash_password_secure($password);
                    $updateStmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
                    $updateStmt->execute(['hash' => $newHash, 'id' => $user['id']]);
                    $user['password_hash'] = $newHash;
                }

                login_user($user);
                header('Location: account.php');
                exit;
            } else {
                $errors[] = 'Identifiants incorrects.';
            }
        }
    } elseif ($action === 'register') {
        $activeView = 'register';
        $name = sanitize_field($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            $errors[] = 'Merci de remplir tous les champs pour créer votre compte.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $existing = $stmt->fetch();

            if ($existing) {
                $errors[] = 'Un compte existe déjà avec cet email.';
            } else {
                $hash = hash_password_secure($password);
                $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)');
                $insert->execute([
                    'name' => $name,
                    'email' => $email,
                    'hash' => $hash,
                    'role' => 'customer'
                ]);

                $userId = $pdo->lastInsertId();
                login_user(['id' => $userId, 'name' => $name, 'email' => $email, 'role' => 'customer']);
                $messages[] = 'Votre compte a été créé avec succès.';
                header('Location: account.php');
                exit;
            }
        }
    } elseif ($action === 'forgot') {
        $activeView = 'forgot';
        $email = strtolower(trim($_POST['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Merci de renseigner une adresse email valide pour réinitialiser le mot de passe.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
                $upd = $pdo->prepare('UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id');
                $upd->execute(['token' => $token, 'expires' => $expires, 'id' => $user['id']]);
                $resetLink = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'exoleton.local') . '/reset.php?token=' . urlencode($token);
                $messages[] = 'Un lien de réinitialisation a été généré. Copiez-le pour réinitialiser : ' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
            }

            $messages[] = 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation (validité 1 heure).';
        }
    }
}

$algoInfo = preferred_password_algorithm();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Connexion / Inscription – Exoleton</title>
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
      <div class="col-lg-8 col-xl-7">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4 p-lg-5">
            <h1 class="h3 mb-4">Accéder à votre espace</h1>
            <p class="text-muted">Connexion, création de compte client ou demande de réinitialisation du mot de passe.</p>

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
                    <li><?= $message; ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <ul class="nav nav-pills mb-4" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link<?= $activeView === 'login' ? ' active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#pane-login" type="button" role="tab">Connexion</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link<?= $activeView === 'register' ? ' active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#pane-register" type="button" role="tab">Créer un compte</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link<?= $activeView === 'forgot' ? ' active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#pane-forgot" type="button" role="tab">Mot de passe oublié</button>
              </li>
            </ul>

            <div class="tab-content">
              <div class="tab-pane fade<?= $activeView === 'login' ? ' show active' : ''; ?>" id="pane-login" role="tabpanel">
                <form method="post" class="vstack gap-3">
                  <input type="hidden" name="action" value="login">
                  <input type="hidden" name="view" value="login">
                  <div>
                    <label for="loginEmail" class="form-label">Email</label>
                    <input type="email" class="form-control" id="loginEmail" name="email" required autocomplete="email">
                  </div>
                  <div>
                    <label for="loginPassword" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="loginPassword" name="password" required autocomplete="current-password">
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <a href="#" onclick="document.querySelector('[data-bs-target=\\'#pane-forgot\\']').click(); return false;">Mot de passe oublié ?</a>
                    <button type="submit" class="btn btn-primary">Se connecter</button>
                  </div>
                </form>
              </div>

              <div class="tab-pane fade<?= $activeView === 'register' ? ' show active' : ''; ?>" id="pane-register" role="tabpanel">
                <form method="post" class="vstack gap-3">
                  <input type="hidden" name="action" value="register">
                  <input type="hidden" name="view" value="register">
                  <div>
                    <label for="registerName" class="form-label">Nom complet</label>
                    <input type="text" class="form-control" id="registerName" name="name" required>
                  </div>
                  <div>
                    <label for="registerEmail" class="form-label">Email</label>
                    <input type="email" class="form-control" id="registerEmail" name="email" required autocomplete="email">
                  </div>
                  <div>
                    <label for="registerPassword" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="registerPassword" name="password" required autocomplete="new-password" minlength="8">
                    <div class="form-text">Stockage sécurisé via <?= htmlspecialchars($algoInfo['label']); ?>.</div>
                  </div>
                  <div class="text-end">
                    <button type="submit" class="btn btn-primary">Créer le compte</button>
                  </div>
                </form>
              </div>

              <div class="tab-pane fade<?= $activeView === 'forgot' ? ' show active' : ''; ?>" id="pane-forgot" role="tabpanel">
                <form method="post" class="vstack gap-3">
                  <input type="hidden" name="action" value="forgot">
                  <input type="hidden" name="view" value="forgot">
                  <div>
                    <label for="forgotEmail" class="form-label">Email</label>
                    <input type="email" class="form-control" id="forgotEmail" name="email" required autocomplete="email">
                    <div class="form-text">Nous générerons un lien de réinitialisation valable 1 heure.</div>
                  </div>
                  <div class="text-end">
                    <button type="submit" class="btn btn-primary">Envoyer le lien</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // maintenir l'onglet actif après soumission
    const activeTab = document.querySelector('.nav-link.active');
    if (activeTab) {
      const tab = new bootstrap.Tab(activeTab);
      tab.show();
    }
  </script>
</body>
</html>
