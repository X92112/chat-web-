<?php
// Action: Update User Profile (Avatar, Cover Banner, Display Name, Bio, Mood, Location, Website)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
           || (isset($_POST['ajax']) && $_POST['ajax'] === '1');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$current_user = current_user();
if (!$current_user) {
    http_response_code(401);
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Please log in first.']);
    } else {
        header("Location: " . BASE_URL . "/login.php");
    }
    exit;
}

check_csrf_or_fail($is_ajax);

$db = get_db();
$uid = (int)$current_user['id'];

$display_name = trim($_POST['display_name'] ?? ($current_user['display_name'] ?? ''));
$bio          = trim($_POST['bio'] ?? ($current_user['bio'] ?? ''));
$status_mood  = trim($_POST['status_mood'] ?? ($current_user['status_mood'] ?? ''));
$location     = trim($_POST['location'] ?? ($current_user['location'] ?? ''));
$website      = trim($_POST['website'] ?? ($current_user['website'] ?? ''));

if (mb_strlen($display_name) > 60) $display_name = mb_substr($display_name, 0, 60);
if (mb_strlen($bio) > 300)          $bio = mb_substr($bio, 0, 300);
if (mb_strlen($status_mood) > 80)   $status_mood = mb_substr($status_mood, 0, 80);
if (mb_strlen($location) > 80)      $location = mb_substr($location, 0, 80);
if (mb_strlen($website) > 180)      $website = mb_substr($website, 0, 180);

// Normalize website URL if non-empty
if ($website !== '' && !preg_match('#^https?://#i', $website)) {
    $website = 'https://' . $website;
}

$avatar_filename = $current_user['avatar'] ?? null;
$banner_filename = $current_user['banner'] ?? null;

try {
    // Remove avatar if requested
    if (!empty($_POST['remove_avatar'])) {
        $avatar_filename = null;
    }

    // Remove banner if requested
    if (!empty($_POST['remove_banner'])) {
        $banner_filename = null;
    }

    // Process uploaded Avatar photo
    if (!empty($_FILES['avatar_photo']['tmp_name']) && $_FILES['avatar_photo']['error'] === UPLOAD_ERR_OK) {
        $saved = process_photo_upload($_FILES['avatar_photo']);
        $avatar_filename = $saved['thumb'] ?: $saved['filename'];
    }

    // Process uploaded Cover Banner photo
    if (!empty($_FILES['banner_photo']['tmp_name']) && $_FILES['banner_photo']['error'] === UPLOAD_ERR_OK) {
        $saved_banner = process_photo_upload($_FILES['banner_photo']);
        $banner_filename = $saved_banner['filename']; // keep crisp full width for banner
    }
} catch (Exception $e) {
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    } else {
        $_SESSION['flash_error'] = $e->getMessage();
        header("Location: " . BASE_URL . "/profile.php?u=" . urlencode($current_user['username']));
        exit;
    }
}

$stmt = $db->prepare("
    UPDATE users
    SET display_name = :display_name,
        bio          = :bio,
        status_mood  = :status_mood,
        location     = :location,
        website      = :website,
        avatar       = :avatar,
        banner       = :banner
    WHERE id = :id
");
$stmt->execute([
    'display_name' => $display_name !== '' ? $display_name : null,
    'bio'          => $bio !== '' ? $bio : null,
    'status_mood'  => $status_mood !== '' ? $status_mood : null,
    'location'     => $location !== '' ? $location : null,
    'website'      => $website !== '' ? $website : null,
    'avatar'       => $avatar_filename,
    'banner'       => $banner_filename,
    'id'           => $uid
]);

set_user_avatar_cache($current_user['username'], $avatar_filename);
current_user(true);

if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'    => true,
        'message'    => 'Profile updated!',
        'avatar_url' => $avatar_filename ? (UPLOAD_URL . '/' . rawurlencode($avatar_filename)) : null,
        'banner_url' => $banner_filename ? (UPLOAD_URL . '/' . rawurlencode($banner_filename)) : null,
        'profile'    => [
            'display_name' => $display_name,
            'bio'          => $bio,
            'status_mood'  => $status_mood,
            'location'     => $location,
            'website'      => $website
        ]
    ]);
    exit;
}

$redirect_to = $_POST['redirect_to'] ?? (BASE_URL . '/profile.php?u=' . urlencode($current_user['username']) . '&updated=1');
header("Location: " . $redirect_to);
exit;
