<?php
// Connexion PDO partagée
// Permet de surcharger les valeurs par défaut via les variables d'environnement
// courantes (DATABASE_URL ou DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET).

$dsn = null;
$user = null;
$pass = null;
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$defaultUser = getenv('DB_USER') ?: 'root';
$defaultPass = getenv('DB_PASS') ?: 'KQchRF5NEjd7';

// Priorité au format DATABASE_URL (ex: mysql://user:pass@host:port/dbname)
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if ($parts !== false && isset($parts['scheme'], $parts['host'], $parts['path'])) {
        $user = $parts['user'] ?? null;
        $pass = $parts['pass'] ?? null;
        $host = $parts['host'];
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $db = ltrim($parts['path'], '/');

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $queryParams);
            if (!empty($queryParams['charset'])) {
                $charset = $queryParams['charset'];
            }
        }

        $dsn = sprintf(
            '%s:host=%s%s;dbname=%s;charset=%s',
            $parts['scheme'],
            $host,
            $port ? ";port={$port}" : '',
            $db,
            $charset
        );
    }
}

// Sinon on retombe sur les variables d'environnement individuelles ou les valeurs par défaut locales
if ($dsn === null) {
    $host = getenv('DB_HOST') ?: 'localhost';
    $db   = getenv('DB_NAME') ?: 'exoleton';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
}

// Fallbacks pour l'identification si non fournis dans l'URL ou les variables explicites
$user = $user ?? $defaultUser;
$pass = $pass ?? $defaultPass;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'DB connection failed']);
    error_log('[DB] Connection failed: ' . $e->getMessage());
    exit;
}
