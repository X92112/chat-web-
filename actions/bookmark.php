<?php
// Action: Toggle Bookmark / Save Post
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
    echo json_encode(['success' => false, 'error' => 'Please log in to save posts.']);
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
$uid = (int)$current_user['id'];

$chk = $db->prepare("SELECT 1 FROM bookmarks WHERE user_id = :uid AND post_id = :pid LIMIT 1");
$chk->execute(['uid' => $uid, 'pid' => $post_id]);
$exists = (bool)$chk->fetchColumn();

if ($exists) {
    $del = $db->prepare("DELETE FROM bookmarks WHERE user_id = :uid AND post_id = :pid");
    $del->execute(['uid' => $uid, 'pid' => $post_id]);
    $bookmarked = false;
} else {
    $ins = $db->prepare("INSERT INTO bookmarks (user_id, post_id, created_at) VALUES (:uid, :pid, NOW())");
    $ins->execute(['uid' => $uid, 'pid' => $post_id]);
    $bookmarked = true;
}

echo json_encode([
    'success'    => true,
    'bookmarked' => $bookmarked,
    'post_id'    => $post_id
]);
