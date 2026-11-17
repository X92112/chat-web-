<?php
// Action: Delete Post or Comment
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
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

check_csrf_or_fail(true);

$type = $_POST['type'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0 || !in_array($type, ['post', 'comment'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid delete request.']);
    exit;
}

$db = get_db();

if ($type === 'post') {
    // Check post ownership
    $stmt = $db->prepare("SELECT id, user_id, filename, thumb FROM posts WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $post = $stmt->fetch();

    if (!$post) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Post not found.']);
        exit;
    }

    if ((int)$post['user_id'] !== (int)$current_user['id'] && empty($current_user['is_admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You can only delete your own posts.']);
        exit;
    }

    // Delete image files
    $files = [$post['filename'], $post['thumb']];
    foreach ($files as $file) {
        if ($file) {
            $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    // Remove DB rows
    $db->prepare("DELETE FROM likes WHERE post_id = :id")->execute(['id' => $id]);
    $db->prepare("DELETE FROM bookmarks WHERE post_id = :id")->execute(['id' => $id]);
    $db->prepare("DELETE FROM comments WHERE post_id = :id")->execute(['id' => $id]);
    $db->prepare("DELETE FROM notifications WHERE post_id = :id")->execute(['id' => $id]);
    $db->prepare("DELETE FROM posts WHERE id = :id")->execute(['id' => $id]);

    echo json_encode(['success' => true, 'post_id' => $id]);
    exit;
}

if ($type === 'comment') {
    // Check comment ownership or post ownership
    $stmt = $db->prepare("
        SELECT c.id, c.user_id, c.post_id, p.user_id AS post_author_id
        FROM comments c
        JOIN posts p ON c.post_id = p.id
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $id]);
    $comment = $stmt->fetch();

    if (!$comment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Comment not found.']);
        exit;
    }

    $is_commenter = (int)$comment['user_id'] === (int)$current_user['id'];
    $is_post_owner = (int)$comment['post_author_id'] === (int)$current_user['id'];
    $is_admin = !empty($current_user['is_admin']);

    if (!$is_commenter && !$is_post_owner && !$is_admin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You cannot delete this comment.']);
        exit;
    }

    $post_id = (int)$comment['post_id'];
    $db->prepare("DELETE FROM comments WHERE id = :id")->execute(['id' => $id]);

    $cnt_stmt = $db->prepare("SELECT COUNT(*) FROM comments WHERE post_id = :pid");
    $cnt_stmt->execute(['pid' => $post_id]);
    $comment_count = (int)$cnt_stmt->fetchColumn();

    echo json_encode([
        'success'       => true,
        'comment_id'    => $id,
        'post_id'       => $post_id,
        'comment_count' => $comment_count
    ]);
    exit;
}
