<?php
// Action: Toggle Like on Post
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
    echo json_encode(['success' => false, 'error' => 'Please log in to like photos.']);
    exit;
}

check_csrf_or_fail(true);

$post_id = (int)($_POST['post_id'] ?? 0);
if ($post_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid post ID.']);
    exit;
}

$db = get_db();

// Check post exists and get author
$post_stmt = $db->prepare("SELECT user_id FROM posts WHERE id = :id LIMIT 1");
$post_stmt->execute(['id' => $post_id]);
$post = $post_stmt->fetch();
if (!$post) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Post not found.']);
    exit;
}
$author_id = (int)$post['user_id'];

// Check existing like
$check_stmt = $db->prepare("SELECT 1 FROM likes WHERE user_id = :uid AND post_id = :pid");
$check_stmt->execute(['uid' => $current_user['id'], 'pid' => $post_id]);
$existing = $check_stmt->fetch();

if ($existing) {
    // Unlike
    $del = $db->prepare("DELETE FROM likes WHERE user_id = :uid AND post_id = :pid");
    $del->execute(['uid' => $current_user['id'], 'pid' => $post_id]);
    $liked = false;
    $action = 'unlike';
} else {
    // Like
    $ins = $db->prepare("INSERT INTO likes (user_id, post_id, created_at) VALUES (:uid, :pid, NOW())");
    $ins->execute(['uid' => $current_user['id'], 'pid' => $post_id]);
    $liked = true;
    $action = 'like';

    // Notification for post author
    create_notification($author_id, $current_user['id'], 'like', $post_id);
}

// Get updated count
$cnt_stmt = $db->prepare("SELECT COUNT(*) FROM likes WHERE post_id = :pid");
$cnt_stmt->execute(['pid' => $post_id]);
$like_count = (int)$cnt_stmt->fetchColumn();

// Broadcast event for instant sync
dispatch_event('global', 'like_update', [
    'post_id'    => $post_id,
    'like_count' => $like_count,
    'user_id'    => (int)$current_user['id'],
    'action'     => $action
]);

echo json_encode([
    'success'    => true,
    'liked'      => $liked,
    'like_count' => $like_count
]);
