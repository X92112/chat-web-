<?php
// Action: Toggle Follow / Unfollow
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
    echo json_encode(['success' => false, 'error' => 'Please log in to follow users.']);
    exit;
}

check_csrf_or_fail(true);

$target_id = (int)($_POST['target_id'] ?? 0);
if ($target_id <= 0 || $target_id === (int)$current_user['id']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid target user.']);
    exit;
}

$db = get_db();

// Verify target user exists
$u_stmt = $db->prepare("SELECT id, username FROM users WHERE id = :id LIMIT 1");
$u_stmt->execute(['id' => $target_id]);
$target_user = $u_stmt->fetch();
if (!$target_user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found.']);
    exit;
}

// Check existing follow
$chk = $db->prepare("SELECT 1 FROM follows WHERE follower_id = :fid AND following_id = :tid LIMIT 1");
$chk->execute(['fid' => $current_user['id'], 'tid' => $target_id]);
$exists = $chk->fetch();

if ($exists) {
    // Unfollow
    $del = $db->prepare("DELETE FROM follows WHERE follower_id = :fid AND following_id = :tid");
    $del->execute(['fid' => $current_user['id'], 'tid' => $target_id]);
    $is_following = false;
} else {
    // Follow
    $ins = $db->prepare("INSERT INTO follows (follower_id, following_id, created_at) VALUES (:fid, :tid, NOW())");
    $ins->execute(['fid' => $current_user['id'], 'tid' => $target_id]);
    $is_following = true;

    // Send notification
    create_notification($target_id, $current_user['id'], 'follow');
}

// Get updated follower count
$cnt_stmt = $db->prepare("SELECT COUNT(*) FROM follows WHERE following_id = :tid");
$cnt_stmt->execute(['tid' => $target_id]);
$follower_count = (int)$cnt_stmt->fetchColumn();

echo json_encode([
    'success'        => true,
    'is_following'   => $is_following,
    'follower_count' => $follower_count
]);
