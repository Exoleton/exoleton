<?php
$lang = $_GET['lang'] ?? 'fr';
$path = $_GET['path'] ?? '';

$langs = ['fr','en','de','it','es','pt','nl','pl','ja','zh','ko','ru'];
if (!in_array($lang, $langs, true)) $lang = 'fr';

$path = trim($path, '/');

if ($path === '' || $path === 'index.php') {
  require __DIR__ . '/index.php';
  exit;
}

if ($path === 'recherche') {
  require __DIR__ . '/recherche.php';
  exit;
}

if (preg_match('#^produit/([^/]+)$#', $path, $m)) {
  $_GET['slug'] = $m[1];
  require __DIR__ . '/detail.php';
  exit;
}

if ($path === 'login') { require __DIR__ . '/login.php'; exit; }
if ($path === 'logout') { require __DIR__ . '/logout.php'; exit; }
if ($path === 'account') { require __DIR__ . '/account.php'; exit; }
if ($path === 'admin') { require __DIR__ . '/admin.php'; exit; }

http_response_code(404);
require __DIR__ . '/404.php';
