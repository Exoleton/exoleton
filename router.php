<?php
require __DIR__ . '/auth.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$uri = trim($uri, '/');
$parts = $uri === '' ? [] : explode('/', $uri);

// 1) Langue (1er segment)
$lang = $parts[0] ?? 'fr';

// 2) Langues actives en DB (cache simple)
static $activeLangs = null;
if ($activeLangs === null) {
  $activeLangs = $pdo->query("SELECT code FROM languages WHERE is_active=1")->fetchAll(PDO::FETCH_COLUMN);
}

if (!in_array($lang, $activeLangs, true)) {
  // si pas de langue dans l'URL -> redirige fr
  // ex: / -> /fr/
  if ($uri === '' || !preg_match('~^[a-z]{2}(/|$)~', $uri)) {
    header("Location: /fr/", true, 301);
    exit;
  }
  http_response_code(404);
  echo "Langue non supportée";
  exit;
}

// 3) Chemin après la langue
$pathAfterLang = array_slice($parts, 1);
$route = $pathAfterLang[0] ?? ''; // '' = home

// 4) Routage
switch ($route) {
  case '':
    // /fr/ ou /en/
    require __DIR__ . '/index.php';
    break;

  case 'produit':
    // /fr/produit/exolift-pro
    $slug = $pathAfterLang[1] ?? '';
    if ($slug === '') { http_response_code(404); echo "Slug manquant"; exit; }
    $_GET['slug'] = $slug;
    require __DIR__ . '/detail.php';
    break;

  case 'recherche':
    // /fr/recherche?q=...
    require __DIR__ . '/recherche.php';
    break;

  default:
    http_response_code(404);
    echo "Page introuvable";
    break;
}
