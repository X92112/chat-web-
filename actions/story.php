<?php
// Action: Create or Delete a 24h Visual Story
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
    echo json_encode(['success' => false, 'error' => 'Please log in to post a story.']);
    exit;
}

check_csrf_or_fail(true);

$db = get_db();
$uid = (int)$current_user['id'];
$action = $_POST['action'] ?? 'create';

if ($action === 'delete') {
    $story_id = (int)($_POST['story_id'] ?? 0);
    $stmt = $db->prepare("SELECT id, user_id, filename, thumb FROM stories WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $story_id]);
    $story = $stmt->fetch();
    if (!$story) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Story not found.']);
        exit;
    }
    if ((int)$story['user_id'] !== $uid && empty($current_user['is_admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Cannot delete this story.']);
        exit;
    }
    foreach ([$story['filename'] ?? null, $story['thumb'] ?? null] as $f) {
        if ($f) {
            $p = UPLOAD_DIR . DIRECTORY_SEPARATOR . $f;
            if (is_file($p)) @unlink($p);
        }
    }
    $del = $db->prepare("DELETE FROM stories WHERE id = :id");
    $del->execute(['id' => $story_id]);
    echo json_encode(['success' => true, 'deleted_id' => $story_id]);
    exit;
}

if (!check_rate_limit('story_upload', RATE_LIMIT_UPLOAD_COUNT, RATE_LIMIT_UPLOAD_WINDOW)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many uploads! Take a breather and try again in a minute.']);
    exit;
}

if (empty($_FILES['photo'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please select a photo for your story.']);
    exit;
}

try {
    $saved = process_photo_upload($_FILES['photo']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

$caption = trim($_POST['caption'] ?? '');
if (mb_strlen($caption) > 150) {
    $caption = mb_substr($caption, 0, 150);
}

$ins = $db->prepare("
    INSERT INTO stories (user_id, filename, thumb, caption, created_at)
    VALUES (:uid, :filename, :thumb, :caption, NOW())
");
$ins->execute([
    'uid'      => $uid,
    'filename' => $saved['filename'],
    'thumb'    => $saved['thumb'],
    'caption'  => $caption !== '' ? $caption : null
]);

$story_id = (int)$db->lastInsertId();

echo json_encode([
    'success' => true,
    'story'   => [
        'id'          => $story_id,
        'user_id'     => $uid,
        'username'    => $current_user['username'],
        'is_verified' => (bool)$current_user['is_verified'],
        'img_url'     => UPLOAD_URL . '/' . rawurlencode($saved['filename']),
        'thumb_url'   => UPLOAD_URL . '/' . rawurlencode($saved['thumb'] ?: $saved['filename']),
        'caption'     => $caption,
        'time_ago'    => 'just now'
    ]
]);
