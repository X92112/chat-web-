<?php
// Snapwall / Nexly Fallback Polling & Heartbeat Endpoint
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Unauthenticated']);
    exit;
}

$my_id = (int)$user['id'];
$user_channel = 'user:' . $my_id;
$db = get_db();

// Touch last_seen at most once every 20 seconds to prevent heavy write locks on users table
if (!isset($_SESSION['last_seen_touch']) || (time() - $_SESSION['last_seen_touch']) > 20) {
    $_SESSION['last_seen_touch'] = time();
    $touch_stmt = $db->prepare("UPDATE users SET last_seen = NOW() WHERE id = :id");
    $touch_stmt->execute(['id' => $my_id]);
}
session_write_close();

// If heartbeat only
if (isset($_GET['heartbeat'])) {
    $online_users = get_online_users(15);
    echo json_encode([
        'success'      => true,
        'online_count' => count($online_users),
        'online_users' => $online_users
    ]);
    exit;
}

$last_id = (int)($_GET['last_id'] ?? 0);

// If no last_id specified, start from current latest event to prevent replaying old events
if ($last_id <= 0) {
    $max_stmt = $db->query("SELECT COALESCE(MAX(id), 0) FROM events");
    $last_id = (int)$max_stmt->fetchColumn();
}

// Query channels visible to this user
$stmt = $db->prepare("
    SELECT id, channel, user_id, type, payload, created_at
    FROM events
    WHERE channel IN ('lobby', 'global', :user_channel)
      AND id > :last_id
    ORDER BY id ASC
    LIMIT 50
");
$stmt->execute([
    'user_channel' => $user_channel,
    'last_id'      => $last_id
]);
$raw_events = $stmt->fetchAll();

$formatted_events = [];
foreach ($raw_events as $event) {
    $formatted_events[] = [
        'id'      => (int)$event['id'],
        'channel' => $event['channel'],
        'type'    => $event['type'],
        'payload' => json_decode($event['payload'], true) ?: []
    ];
}

echo json_encode([
    'success' => true,
    'events'  => $formatted_events
]);
