<?php
require __DIR__ . '/auth.php';

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

$user = require_auth($pdo);
$currentUser = $user;
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <title data-i18n="account.metaTitle">Mon compte – Exoleton</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="bg-light">
  <header class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="<?= $base ?>/">
        <img src="/assets/img/logo.png" alt="Exoleton" width="272" height="1000" class="me-2">
      </a>

      <nav class="ms-auto">
        <ul class="navbar-nav align-items-lg-center flex-row gap-3">
          <li class="nav-item">
            <label class="visually-hidden" for="languageSwitcherAccount" data-i18n="lang.label">Langue</label>
            <select id="languageSwitcherAccount" class="form-select form-select-sm" data-language-switcher></select>
          </li>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <span data-i18n="account.hello">Bonjour</span> <?= htmlspecialchars($user['name']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
              <li><a class="dropdown-item" href="<?= $base ?>/account" data-i18n="account.menu.account">Mon compte</a></li>
              <?php if (($user['role'] ?? 'customer') === 'admin'): ?>
                <li><a class="dropdown-item" href="<?= $base ?>/admin" data-i18n="account.menu.admin">Administration</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= $base ?>/logout" data-i18n="account.menu.logout">Se déconnecter</a></li>
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
            <h1 class="h4 mb-3" data-i18n="account.title">Mon compte</h1>
            <p class="text-muted" data-i18n="account.subtitle">Consultez vos informations et accédez aux outils adaptés à votre profil.</p>

            <dl class="row mb-4">
              <dt class="col-sm-4" data-i18n="account.fullName">Nom complet</dt>
              <dd class="col-sm-8"><?= htmlspecialchars($user['name']); ?></dd>

              <dt class="col-sm-4" data-i18n="account.email">Email</dt>
              <dd class="col-sm-8"><?= htmlspecialchars($user['email']); ?></dd>

              <dt class="col-sm-4" data-i18n="account.role">Rôle</dt>
              <dd class="col-sm-8"><?= htmlspecialchars($user['role']); ?></dd>
            </dl>

            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-outline-primary" href="<?= $base ?>/" data-i18n="account.backHome">Retour à l'accueil</a>
              <?php if (($user['role'] ?? 'customer') === 'admin'): ?>
                <a class="btn btn-warning" href="<?= $base ?>/admin" data-i18n="account.goAdmin">Accéder à l'administration</a>
              <?php endif; ?>
              <a class="btn btn-outline-danger" href="<?= $base ?>/logout" data-i18n="account.logout">Se déconnecter</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/i18n.js"></script>
</body>
</html>
