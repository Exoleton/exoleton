<?php
require __DIR__ . '/auth.php';

/**
 * login.php (routing i18n)
 * - Supporte les URLs du style /fr/login, /en/login, etc.
 * - Corrige toutes les redirections & liens pour respecter le préfixe langue.
 */

/* =========================
   Helpers langue / base
   ========================= */
const SUPPORTED_LANGS = ['fr','en','de','it','es','pt','nl','pl','ja','zh','ko','ru'];

function normalize_lang($lang) {
    $lang = is_string($lang) ? strtolower(trim($lang)) : 'fr';
    if ($lang === 'jp') $lang = 'ja';
    if ($lang === 'kr') $lang = 'ko';
    return $lang;
}

function get_path_lang(): ?string {
    $path = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($path, PHP_URL_PATH) ?: '/';
    $seg = array_values(array_filter(explode('/', $path), 'strlen'));
    if (!$seg) return null;
    $lang = normalize_lang($seg[0]);
    return in_array($lang, SUPPORTED_LANGS, true) ? $lang : null;
}

$lang = get_path_lang() ?? 'fr';
$base = '/' . $lang;

function redirect_to(string $url) {
    header('Location: ' . $url, true, 302);
    exit;
}

$currentUser = current_user($pdo);
if ($currentUser) {
    redirect_to($base . '/account');
}

/* =========================
   Form logic
   ========================= */
$errors = [];
$messages = [];
$activeView = $_POST['view'] ?? $_GET['view'] ?? 'login';

function sanitize_field(string $value): string {
    return trim(filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

function current_request_scheme(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : 'http';
    }
    $https = $_SERVER['HTTPS'] ?? '';
    if ($https && strtolower($https) !== 'off') return 'https';
    return (!empty($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https') ? 'https' : 'http';
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

                // Optionnel: si tu passes ?next=/fr/produit/... on redirige là
                $next = $_POST['next'] ?? ($_GET['next'] ?? '');
                if (is_string($next) && $next !== '' && str_starts_with($next, $base . '/')) {
                    redirect_to($next);
                }

                redirect_to($base . '/account');
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

                redirect_to($base . '/account');
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

                $scheme = current_request_scheme();
                $host = $_SERVER['HTTP_HOST'] ?? 'exoleton.local';
                // IMPORTANT: lien reset avec base langue
                $resetLink = sprintf('%s://%s%s/reset?token=%s', $scheme, $host, $base, urlencode($token));
                $messages[] = 'Un lien de réinitialisation a été généré. Copiez-le pour réinitialiser : ' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
            }

            $messages[] = 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation (validité 1 heure).';
        }
    }
}

$algoInfo = preferred_password_algorithm();
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <title data-i18n="auth.metaTitle">Connexion / Inscription – Exoleton</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Connexion, création de compte client ou demande de réinitialisation du mot de passe." data-i18n-description="auth.metaDescription">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

  <header class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container d-flex align-items-center justify-content-between">
      <a class="navbar-brand d-flex align-items-center" href="<?= $base ?>/">
        <img src="/assets/img/logo.png" alt="Exoleton" width="272" height="1000" class="me-2">
      </a>
      <div class="d-flex align-items-center gap-3">
        <a class="btn btn-outline-primary d-none d-md-inline-flex" href="<?= $base ?>/" data-i18n="nav.home">Accueil</a>
        <label class="visually-hidden" for="languageSwitcherLogin" data-i18n="lang.label">Langue</label>
        <select id="languageSwitcherLogin" class="form-select form-select-sm" data-language-switcher></select>
      </div>
    </div>
  </header>

  <main class="container flex-grow-1" style="padding-top: 7rem; padding-bottom: 4rem;">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-xl-7">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4 p-lg-5">
            <h1 class="h3 mb-4" data-i18n="auth.heading">Accéder à votre espace</h1>
            <p class="text-muted" data-i18n="auth.subtitle">Connexion, création de compte client ou demande de réinitialisation du mot de passe.</p>

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
                <button class="nav-link<?= $activeView === 'login' ? ' active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#pane-login" type="button" role="tab" data-i18n="auth.tabLogin">Connexion</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link<?= $activeView === 'register' ? ' active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#pane-register" type="button" role="tab" data-i18n="auth.tabRegister">Créer un compte</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link<?= $activeView === 'forgot' ? ' active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#pane-forgot" type="button" role="tab" data-i18n="auth.tabForgot">Mot de passe oublié</button>
              </li>
            </ul>

            <div class="tab-content">
              <div class="tab-pane fade<?= $activeView === 'login' ? ' show active' : ''; ?>" id="pane-login" role="tabpanel">
                <form method="post" class="vstack gap-3">
                  <input type="hidden" name="action" value="login">
                  <input type="hidden" name="view" value="login">
                  <?php
                    // si tu veux revenir sur une page précise après login:
                    // ex: /fr/login?next=/fr/produit/slug
                    $next = $_GET['next'] ?? '';
                    if (is_string($next) && $next !== '' && str_starts_with($next, $base . '/')) {
                      echo '<input type="hidden" name="next" value="'.htmlspecialchars($next, ENT_QUOTES, 'UTF-8').'">';
                    }
                  ?>
                  <div>
                    <label for="loginEmail" class="form-label" data-i18n="auth.login.emailLabel">Email</label>
                    <input type="email" class="form-control" id="loginEmail" name="email" required autocomplete="email">
                  </div>
                  <div>
                    <label for="loginPassword" class="form-label" data-i18n="auth.login.passwordLabel">Mot de passe</label>
                    <input type="password" class="form-control" id="loginPassword" name="password" required autocomplete="current-password">
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <a href="#" onclick="document.querySelector('[data-bs-target=\\'#pane-forgot\\']').click(); return false;" data-i18n="auth.login.forgotLink">Mot de passe oublié ?</a>
                    <button type="submit" class="btn btn-primary" data-i18n="auth.login.submit">Se connecter</button>
                  </div>
                </form>
              </div>

              <div class="tab-pane fade<?= $activeView === 'register' ? ' show active' : ''; ?>" id="pane-register" role="tabpanel">
                <form method="post" class="vstack gap-3">
                  <input type="hidden" name="action" value="register">
                  <input type="hidden" name="view" value="register">
                  <div>
                    <label for="registerName" class="form-label" data-i18n="auth.register.nameLabel">Nom complet</label>
                    <input type="text" class="form-control" id="registerName" name="name" required>
                  </div>
                  <div>
                    <label for="registerEmail" class="form-label" data-i18n="auth.register.emailLabel">Email</label>
                    <input type="email" class="form-control" id="registerEmail" name="email" required autocomplete="email">
                  </div>
                  <div>
                    <label for="registerPassword" class="form-label" data-i18n="auth.register.passwordLabel">Mot de passe</label>
                    <input type="password" class="form-control" id="registerPassword" name="password" required autocomplete="new-password" minlength="8">
                    <div class="form-text" data-i18n="auth.passwordHint">Vos mots de passe sont conservés de manière sécurisée.</div>
                  </div>
                  <div class="text-end">
                    <button type="submit" class="btn btn-primary" data-i18n="auth.register.submit">Créer le compte</button>
                  </div>
                </form>
              </div>

              <div class="tab-pane fade<?= $activeView === 'forgot' ? ' show active' : ''; ?>" id="pane-forgot" role="tabpanel">
                <form method="post" class="vstack gap-3">
                  <input type="hidden" name="action" value="forgot">
                  <input type="hidden" name="view" value="forgot">
                  <div>
                    <label for="forgotEmail" class="form-label" data-i18n="auth.forgot.emailLabel">Email</label>
                    <input type="email" class="form-control" id="forgotEmail" name="email" required autocomplete="email">
                    <div class="form-text" data-i18n="auth.forgot.hint">Nous générerons un lien de réinitialisation valable 1 heure.</div>
                  </div>
                  <div class="text-end">
                    <button type="submit" class="btn btn-primary" data-i18n="auth.forgot.submit">Envoyer le lien</button>
                  </div>
                </form>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="pt-5 bg-dark text-white mt-auto">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="d-flex align-items-center mb-3">
            <img src="/assets/img/logo.png" alt="Exoleton" width="136" height="50" class="me-2">
          </div>
          <p class="text-white-50" data-i18n="footer.mission">Site d’exosquelettes et technologies d’assistance. Notre mission : rendre la mobilité augmentée accessible à tous.</p>
        </div>
        <div class="col-6 col-md-2">
          <h3 class="h6" data-i18n="footer.navigation">Navigation</h3>
          <ul class="list-unstyled">
            <li><a class="footer-link" href="<?= $base ?>/" data-i18n="nav.home">Accueil</a></li>
            <li><a class="footer-link" href="<?= $base ?>/#guides" data-i18n="nav.guides">Guides</a></li>
            <li><a class="footer-link" href="<?= $base ?>/recherche" data-i18n="nav.search">Recherche</a></li>
          </ul>
        </div>
        <div class="col-6 col-md-3">
          <h3 class="h6" data-i18n="footer.resources">Ressources</h3>
          <ul class="list-unstyled">
            <li><a class="footer-link" href="#" data-i18n="footer.faq">FAQ</a></li>
            <li><a class="footer-link" href="#" data-i18n="footer.support">Support</a></li>
            <li><a class="footer-link" href="#" data-i18n="footer.legal">Mentions légales</a></li>
            <li><a class="footer-link" href="#" data-i18n="footer.privacy">Politique de confidentialité</a></li>
          </ul>
        </div>
        <div class="col-md-3">
          <h3 class="h6" data-i18n="footer.newsletter">Newsletter</h3>
          <form class="d-flex gap-2" action="#" method="post" onsubmit="return false;">
            <input type="email" class="form-control" placeholder="Votre email" aria-label="Votre email" data-i18n-placeholder="footer.email">
            <button class="btn btn-success" data-i18n="footer.subscribe">S’inscrire</button>
          </form>
        </div>
      </div>
      <hr class="border-secondary my-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center pb-4">
        <small class="text-white-50" data-i18n="footer.rights" data-i18n-html="true">© <span id="year"></span> Exoleton. Tous droits réservés.</small>
        <div class="d-flex gap-3 mt-3 mt-md-0">
          <a class="footer-link" href="#" aria-label="Twitter">Twitter</a>
          <a class="footer-link" href="#" aria-label="LinkedIn">LinkedIn</a>
          <a class="footer-link" href="#" aria-label="YouTube">YouTube</a>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/i18n.js"></script>
  <script>
    const activeTab = document.querySelector('.nav-link.active');
    if (activeTab) new bootstrap.Tab(activeTab).show();
  </script>
</body>
</html>
