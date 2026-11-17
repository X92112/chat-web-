<?php
// Action: Send Direct Message
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$current_user = current_user();
if (!$current_user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to send messages.']);
    exit;
}

check_csrf_or_fail(true);

$to_id = (int)($_POST['to_id'] ?? 0);
$body = trim($_POST['body'] ?? '');

if ($to_id <= 0 || $to_id === (int)$current_user['id']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid recipient.']);
    exit;
}

if ($body === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty.']);
    exit;
}

if (mb_strlen($body) > 1000) {
    $body = mb_substr($body, 0, 1000);
}

$db = get_db();

// Verify recipient exists
$chk = $db->prepare("SELECT id, username FROM users WHERE id = :id LIMIT 1");
$chk->execute(['id' => $to_id]);
$recipient = $chk->fetch();
if (!$recipient) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User does not exist.']);
    exit;
}

// Insert message
$stmt = $db->prepare("
    INSERT INTO messages (from_id, to_id, body, created_at)
    VALUES (:from_id, :to_id, :body, NOW())
");
$stmt->execute([
    'from_id' => $current_user['id'],
    'to_id'   => $to_id,
    'body'    => $body
]);

$msg_id = (int)$db->lastInsertId();

$msg_payload = [
    'id'            => $msg_id,
    'from_id'       => (int)$current_user['id'],
    'to_id'         => $to_id,
    'from_username' => $current_user['username'],
    'body'          => $body,
    'created_at'    => 'just now'
];

// Dispatch targeted event to recipient
dispatch_event('user:' . $to_id, 'dm_message', $msg_payload, $to_id);

echo json_encode([
    'success' => true,
    'message' => $msg_payload
]);
