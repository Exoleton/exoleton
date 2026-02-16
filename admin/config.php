<?php
declare(strict_types=1);

// Configuration existante...
define('SUPPORTED_LANGS', ['fr','en','de','it','es','pt','nl','pl','ja','zh','ko','ru']);
define('DEFAULT_LANG', 'fr');
define('ITEMS_PER_PAGE', 20);

// === CONSTANTES MÉDIAS ===
define('MAX_IMAGES_PER_PRODUCT', 10);
define('MAX_VIDEOS_PER_PRODUCT', 3);
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024);      // 5 Mo
define('MAX_VIDEO_SIZE', 130 * 1024 * 1024);     // 50 Mo
define('THUMB_SIZE', 400);                       // 400x400 px
define('THUMB_QUALITY', 85);                     // Qualité JPEG
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'mov']);
define('MEDIA_BASE_PATH', '/assets/produits/img');

// Détection du chemin de base
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/admin/index.php';
$adminDir = dirname($scriptName);
define('ADMIN_BASE_PATH', $adminDir === '/' || $adminDir === '\\' ? '' : $adminDir);

// Connexion PDO - auth.php à la racine
require_once __DIR__ . '/../auth.php';

// Gestion de la langue
function normalize_lang(?string $lang): string {
    if (!$lang) return DEFAULT_LANG;
    $lang = strtolower(trim($lang));
    $map = ['jp' => 'ja', 'kr' => 'ko'];
    return $map[$lang] ?? $lang;
}

function get_current_lang(): string {
    if (!empty($_GET['lang'])) {
        $lang = normalize_lang($_GET['lang']);
        if (in_array($lang, SUPPORTED_LANGS, true)) {
            $_SESSION['admin_lang'] = $lang;
            return $lang;
        }
    }
    if (!empty($_SESSION['admin_lang'])) {
        return $_SESSION['admin_lang'];
    }
    return DEFAULT_LANG;
}

$currentLang = get_current_lang();
define('CURRENT_LANG', $currentLang);

// Fonctions utilitaires
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

function format_price(?float $price): string {
    if ($price === null) return 'Sur demande';
    return number_format($price, 2, ',', ' ') . ' €';
}

function format_datetime(?string $datetime): string {
    if (!$datetime) return '—';
    try {
        $date = new DateTime($datetime);
        return $date->format('d/m/Y H:i');
    } catch (Exception $e) {
        return '—';
    }
}

// Messages flash
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// URL helper
function admin_url(string $path = ''): string {
    $path = trim($path, '/');
    return '/' . CURRENT_LANG . '/admin/' . $path;
}

// Helper médias
function media_url(string $path = ''): string {
    return MEDIA_BASE_PATH . '/' . trim($path, '/');
}