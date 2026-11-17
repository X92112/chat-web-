<?php
// Snapwall / Nexly Authentication & CSRF
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Check if a user is currently logged in
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user record
 */
function current_user(bool $force_refresh = false): ?array {
    static $cached_user = null;
    if ($cached_user !== null && !$force_refresh) {
        return $cached_user;
    }
    if (!is_logged_in()) {
        return null;
    }
    $db = get_db();
    $stmt = $db->prepare("
        SELECT id, username, email, bio, avatar, banner, display_name, status_mood, location, website,
               last_seen, is_verified, is_admin, is_banned, must_change_password, created_at 
        FROM users 
        WHERE id = :id 
        LIMIT 1
    ");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }

    // Ban check
    if (!empty($user['is_banned'])) {
        logout_user();
        http_response_code(403);
        die("Your account has been suspended by an administrator.");
    }

    $cached_user = $user;
    return $cached_user;
}

/**
 * Require authentication; redirects to login if unauthenticated
 */
function require_login(): array {
    $user = current_user();
    if (!$user) {
        $current_url = $_SERVER['REQUEST_URI'] ?? BASE_URL;
        header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode($current_url));
        exit;
    }
    return $user;
}

/**
 * Require administrator access
 */
function require_admin(): array {
    $user = require_login();
    if (empty($user['is_admin'])) {
        http_response_code(403);
        die("Access denied: Administrator privileges required.");
    }
    return $user;
}

/**
 * Generate or retrieve CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from POST or X-CSRF-Token header
 */
function verify_csrf(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check CSRF and terminate with 403 JSON or error if invalid
 */
function check_csrf_or_fail(bool $is_json = false): void {
    if (!verify_csrf()) {
        http_response_code(403);
        if ($is_json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Invalid or expired CSRF token. Please refresh the page.']);
        } else {
            die("Security check failed: Invalid CSRF token.");
        }
        exit;
    }
}

/**
 * Log in a user
 */
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    csrf_token(); // ensure token is set
    
    // Update last_seen
    $db = get_db();
    $stmt = $db->prepare("UPDATE users SET last_seen = NOW() WHERE id = :id");
    $stmt->execute(['id' => $user['id']]);
}

/**
 * Log out the current user
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}
