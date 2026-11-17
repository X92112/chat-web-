<?php
// Action: Post a new photo
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
    echo json_encode(['success' => false, 'error' => 'Please log in to post a photo.']);
    exit;
}

check_csrf_or_fail(true);

// Rate limiting check
if (!check_rate_limit('upload', RATE_LIMIT_UPLOAD_COUNT, RATE_LIMIT_UPLOAD_WINDOW)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Rate limit exceeded: you are posting too fast. Take a breath!']);
    exit;
}

if (!isset($_FILES['photo'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No photo provided.']);
    exit;
}

$caption = trim($_POST['caption'] ?? '');
if (mb_strlen($caption) > 150) {
    $caption = mb_substr($caption, 0, 150);
}

$tags = trim($_POST['tags'] ?? '');
if (mb_strlen($tags) > 255) {
    $tags = mb_substr($tags, 0, 255);
}

try {
    $upload = process_photo_upload($_FILES['photo']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

$db = get_db();
$stmt = $db->prepare("
    INSERT INTO posts (user_id, filename, thumb, caption, tags, created_at)
    VALUES (:user_id, :filename, :thumb, :caption, :tags, NOW())
");
$stmt->execute([
    'user_id'  => $current_user['id'],
    'filename' => $upload['filename'],
    'thumb'    => $upload['thumb'],
    'caption'  => $caption ?: null,
    'tags'     => $tags ?: null
]);

$post_id = (int)$db->lastInsertId();

// Render post HTML for instant prepend
$post = [
    'id'            => $post_id,
    'user_id'       => $current_user['id'],
    'username'      => $current_user['username'],
    'display_name'  => $current_user['display_name'] ?? '',
    'is_verified'   => !empty($current_user['is_verified']) ? 1 : 0,
    'filename'      => $upload['filename'],
    'thumb'         => $upload['thumb'],
    'caption'       => $caption,
    'tags'          => $tags,
    'created_at'    => date('Y-m-d H:i:s'),
    'like_count'    => 0,
    'comment_count' => 0,
    'is_liked'      => false,
    'is_bookmarked' => false
];

ob_start();
include __DIR__ . '/../includes/post_card.php';
$post_html = ob_get_clean();

// Broadcast event to followers
dispatch_event('global', 'new_post', [
    'post_id'         => $post_id,
    'author_id'       => (int)$current_user['id'],
    'author_username' => $current_user['username'],
    'caption'         => $caption,
    'thumb'           => $upload['thumb'],
    'filename'        => $upload['filename'],
    'created_at'      => 'just now',
    'post_html'       => $post_html
]);

echo json_encode([
    'success'   => true,
    'post_id'   => $post_id,
    'post_html' => $post_html
]);
