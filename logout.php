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

logout_user();
header('Location: ' . $base . '/', true, 302);
exit;
