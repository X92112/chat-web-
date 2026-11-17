<?php
// Action: Add Comment
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
    echo json_encode(['success' => false, 'error' => 'Please log in to comment.']);
    exit;
}

check_csrf_or_fail(true);

// Rate limiting
if (!check_rate_limit('comment', RATE_LIMIT_COMMENT_COUNT, RATE_LIMIT_COMMENT_WINDOW)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Commenting too fast! Please slow down.']);
    exit;
}

$post_id = (int)($_POST['post_id'] ?? 0);
$body = trim($_POST['body'] ?? '');

if ($post_id <= 0 || $body === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Comment body cannot be blank.']);
    exit;
}

if (mb_strlen($body) > 500) {
    $body = mb_substr($body, 0, 500);
}

$db = get_db();

// Verify post exists and find author
$post_stmt = $db->prepare("SELECT user_id FROM posts WHERE id = :id LIMIT 1");
$post_stmt->execute(['id' => $post_id]);
$post = $post_stmt->fetch();
if (!$post) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Post not found.']);
    exit;
}
$author_id = (int)$post['user_id'];

// Insert comment
$ins = $db->prepare("
    INSERT INTO comments (post_id, user_id, body, created_at)
    VALUES (:post_id, :user_id, :body, NOW())
");
$ins->execute([
    'post_id' => $post_id,
    'user_id' => $current_user['id'],
    'body'    => $body
]);

$comment_id = (int)$db->lastInsertId();

// Notify post author
create_notification($author_id, $current_user['id'], 'comment', $post_id);

// Get total count
$cnt_stmt = $db->prepare("SELECT COUNT(*) FROM comments WHERE post_id = :pid");
$cnt_stmt->execute(['pid' => $post_id]);
$comment_count = (int)$cnt_stmt->fetchColumn();

$comment_data = [
    'id'          => $comment_id,
    'post_id'     => $post_id,
    'user_id'     => (int)$current_user['id'],
    'username'    => $current_user['username'],
    'is_verified' => !empty($current_user['is_verified']),
    'body'        => $body,
    'created_at'  => 'just now'
];

// Broadcast new_comment event for instant update
dispatch_event('global', 'new_comment', [
    'post_id'       => $post_id,
    'comment'       => $comment_data,
    'comment_count' => $comment_count
]);

echo json_encode([
    'success'       => true,
    'comment'       => $comment_data,
    'comment_count' => $comment_count
]);
