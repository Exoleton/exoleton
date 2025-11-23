<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function preferred_password_algorithm(): array
{
    if (defined('PASSWORD_ARGON2ID')) {
        return ['algo' => PASSWORD_ARGON2ID, 'label' => 'argon2id'];
    }

    if (defined('PASSWORD_ARGON2I')) {
        return ['algo' => PASSWORD_ARGON2I, 'label' => 'argon2i'];
    }

    return ['algo' => PASSWORD_DEFAULT, 'label' => 'bcrypt'];
}

function hash_password_secure(string $password): string
{
    $algo = preferred_password_algorithm();
    $hash = password_hash($password, $algo['algo']);

    if ($hash === false) {
        throw new RuntimeException('Impossible de générer le mot de passe sécurisé.');
    }

    return $hash;
}

function current_user(PDO $pdo): ?array
{
    static $cachedUser = null;

    if (!empty($_SESSION['user_id'])) {
        if ($cachedUser && $cachedUser['id'] === $_SESSION['user_id']) {
            return $cachedUser;
        }

        $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            $cachedUser = $user;
            return $user;
        }

        unset($_SESSION['user_id']);
    }

    return null;
}

function login_user(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_auth(PDO $pdo): array
{
    $user = current_user($pdo);
    if (!$user) {
        header('Location: login.php');
        exit;
    }

    return $user;
}

function require_admin(PDO $pdo): array
{
    $user = require_auth($pdo);
    if (($user['role'] ?? 'customer') !== 'admin') {
        http_response_code(403);
        echo '<h1>Accès refusé</h1>';
        exit;
    }

    return $user;
}
?>
