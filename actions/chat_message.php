<?php
// Action: Send Live Chat Message (Lobby or DM) with Text, Picture, or Voice Note
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
    echo json_encode(['success' => false, 'error' => 'Please log in to chat.']);
    exit;
}

check_csrf_or_fail(true);

// Rate limiting: Max 10 messages per 10 seconds per user
if (!check_rate_limit('chat_send', 10, 10)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Slow down! You are sending messages too fast.']);
    exit;
}

// Release session lock early so subsequent requests (polling, signaling) aren't blocked
session_write_close();

$channel = trim($_POST['channel'] ?? 'lobby');
$to_id = (int)($_POST['to_id'] ?? 0);
$body = trim($_POST['body'] ?? '');
$media_type = trim($_POST['media_type'] ?? 'text');
$media_url = null;
$media_thumb = null;
$media_duration = max(0, (int)($_POST['media_duration'] ?? 0));

// Process media attachments if uploaded
try {
    if (!empty($_FILES['chat_image']['tmp_name'])) {
        $saved = process_photo_upload($_FILES['chat_image']);
        $media_type = 'image';
        $media_url = $saved['filename'];
        $media_thumb = $saved['thumb'];
    } elseif (!empty($_FILES['voice_audio']['tmp_name'])) {
        $saved = save_uploaded_audio($_FILES['voice_audio']);
        $media_type = 'audio';
        $media_url = $saved['filename'];
    }
} catch (Exception $ex) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $ex->getMessage()]);
    exit;
}

// Validation: must have either text or a media attachment
if ($body === '' && !$media_url) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please enter a message, record voice, or attach a photo.']);
    exit;
}

// Max 500 characters
if (mb_strlen($body) > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message exceeds maximum length of 500 characters.']);
    exit;
}

$db = get_db();

if ($channel === 'lobby') {
    // 1. PUBLIC LOBBY MESSAGE
    $stmt = $db->prepare("
        INSERT INTO lobby_messages (user_id, body, media_type, media_url, media_thumb, media_duration, created_at)
        VALUES (:user_id, :body, :media_type, :media_url, :media_thumb, :media_duration, NOW())
    ");
    $stmt->execute([
        'user_id'        => $current_user['id'],
        'body'           => ($body !== '') ? $body : null,
        'media_type'     => $media_type,
        'media_url'      => $media_url,
        'media_thumb'    => $media_thumb,
        'media_duration' => $media_duration
    ]);
    $msg_id = (int)$db->lastInsertId();

    $payload = [
        'id'             => $msg_id,
        'user_id'        => (int)$current_user['id'],
        'username'       => $current_user['username'],
        'is_verified'    => !empty($current_user['is_verified']),
        'is_admin'       => !empty($current_user['is_admin']),
        'avatar_color'   => get_avatar_color($current_user['username']),
        'initials'       => get_initials($current_user['username']),
        'body'           => $body,
        'media_type'     => $media_type,
        'media_url'      => $media_url,
        'media_thumb'    => $media_thumb,
        'media_duration' => $media_duration,
        'created_at'     => 'just now'
    ];

    // Broadcast to Lobby channel
    dispatch_event('lobby', 'lobby_message', $payload, $current_user['id']);

    echo json_encode([
        'success' => true,
        'channel' => 'lobby',
        'message' => $payload
    ]);
    exit;
} else {
    // 2. DIRECT 1-TO-1 MESSAGE
    if ($to_id <= 0 || $to_id === (int)$current_user['id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid message recipient.']);
        exit;
    }

    $chk = $db->prepare("SELECT id, username FROM users WHERE id = :id AND is_banned = 0 LIMIT 1");
    $chk->execute(['id' => $to_id]);
    $recipient = $chk->fetch();
    if (!$recipient) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Recipient not found.']);
        exit;
    }

    $stmt = $db->prepare("
        INSERT INTO messages (from_id, to_id, body, media_type, media_url, media_thumb, media_duration, is_seen, created_at)
        VALUES (:from_id, :to_id, :body, :media_type, :media_url, :media_thumb, :media_duration, 0, NOW())
    ");
    $stmt->execute([
        'from_id'        => $current_user['id'],
        'to_id'          => $to_id,
        'body'           => ($body !== '') ? $body : null,
        'media_type'     => $media_type,
        'media_url'      => $media_url,
        'media_thumb'    => $media_thumb,
        'media_duration' => $media_duration
    ]);
    $msg_id = (int)$db->lastInsertId();

    $payload = [
        'id'             => $msg_id,
        'from_id'        => (int)$current_user['id'],
        'to_id'          => $to_id,
        'from_username'  => $current_user['username'],
        'from_verified'  => !empty($current_user['is_verified']),
        'body'           => $body,
        'media_type'     => $media_type,
        'media_url'      => $media_url,
        'media_thumb'    => $media_thumb,
        'media_duration' => $media_duration,
        'is_seen'        => 0,
        'created_at'     => 'just now'
    ];

    // Dispatch targeted event to recipient channel
    dispatch_event('user:' . $to_id, 'dm_message', $payload, $current_user['id']);

    echo json_encode([
        'success' => true,
        'channel' => 'dm',
        'message' => $payload
    ]);
    exit;
}
