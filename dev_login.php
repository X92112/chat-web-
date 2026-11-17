<?php
// Quick session switch helper for browser testing.
// LOCAL ONLY: refuses any request that did not come straight from this computer.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

$remote   = $_SERVER['REMOTE_ADDR'] ?? '';
$host     = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$is_loopback = in_array($remote, ['127.0.0.1', '::1'], true);
$is_local_host = in_array($host, ['localhost', '127.0.0.1'], true);
// Cloudflare Tunnel connects from 127.0.0.1 too, so also reject anything that was proxied.
$was_proxied = !empty($_SERVER['HTTP_CF_CONNECTING_IP'])
            || !empty($_SERVER['HTTP_CF_RAY'])
            || !empty($_SERVER['HTTP_X_FORWARDED_FOR']);

if (!$is_loopback || !$is_local_host || $was_proxied) {
    http_response_code(404);
    exit('Not found.');
}

$u = trim($_GET['u'] ?? 'Sokha');
$db = get_db();
$stmt = $db->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
$stmt->execute(['u' => $u]);
$user = $stmt->fetch();

if ($user) {
    login_user($user);
}

// Only allow relative in-app redirects
$redirect = $_GET['redirect'] ?? '/';
if (!is_string($redirect) || $redirect === '' || $redirect[0] !== '/' || str_starts_with($redirect, '//')) {
    $redirect = '/';
}
header("Location: " . BASE_URL . $redirect);
exit;
