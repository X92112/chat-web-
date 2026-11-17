<?php
// Snapwall / Nexly Helper Functions
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

const RESERVED_USERNAMES = ['rt', 'admin', 'ceo', 'snapwall', 'support', 'official'];

/**
 * Check if a username is in the reserved list
 */
function is_reserved_username(string $username): bool {
    return in_array(strtolower(trim($username)), RESERVED_USERNAMES, true);
}

/**
 * HTML escape helper
 */
function e(?string $str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Render Ink Verified Badge (inline SVG - 14px circle in #1C1B18 with white check)
 */
function verified_badge(bool|int $is_verified): string {
    if (!$is_verified) {
        return '';
    }
    return '<span class="verified-badge" title="Verified"><svg viewBox="0 0 14 14" width="14" height="14" style="vertical-align:-1.5px;display:inline-block;margin-left:3px;" aria-label="Verified"><circle cx="7" cy="7" r="7" fill="#1C1B18"/><path d="M4 7.2l2 2 4-4" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg></span>';
}

/**
 * Render diagonal arrow icon box
 */
function bsx_arrow_icon(): string {
    return '<span class="bsx-icon-sq" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.9993 10V4H5.99935M4.16602 11.8333L11.3327 4.66667" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
}

/**
 * 5 discrete deterministic tint pairs for user initials avatar:
 * #E8D9C4 / #4A3A22, #D5E3DA / #1F4733, #D9DEEB / #2A3558, #EBD3D3 / #5A2424, #E4DCEB / #43305A
 */
function get_avatar_tint(string $username): array {
    $tints = [
        ['bg' => '#E8D9C4', 'color' => '#4A3A22'],
        ['bg' => '#D5E3DA', 'color' => '#1F4733'],
        ['bg' => '#D9DEEB', 'color' => '#2A3558'],
        ['bg' => '#EBD3D3', 'color' => '#5A2424'],
        ['bg' => '#E4DCEB', 'color' => '#43305A'],
    ];
    $hash = abs(crc32(strtolower(trim($username))));
    return $tints[$hash % count($tints)];
}

function get_avatar_color(string $username): string {
    $tint = get_avatar_tint($username);
    return $tint['bg'];
}

/**
 * 2-letter uppercase initials from username
 */
function get_initials(string $username): string {
    $clean = trim($username);
    if ($clean === '') {
        return '??';
    }
    if (strlen($clean) === 1) {
        return strtoupper($clean);
    }
    if (preg_match('/[ _-]/', $clean)) {
        $parts = preg_split('/[ _-]+/', $clean);
        $res = substr($parts[0], 0, 1) . substr($parts[1] ?? '', 0, 1);
        return strtoupper($res);
    }
    return strtoupper(substr($clean, 0, 2));
}

/**
 * Static cache for username -> avatar filename
 */
function &avatar_cache_store(): array {
    static $map = null;
    if ($map === null) {
        $map = [];
        try {
            $db = get_db();
            $stmt = $db->query("SELECT LOWER(username) AS uname, avatar FROM users WHERE avatar IS NOT NULL AND avatar != ''");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $map[$row['uname']] = $row['avatar'];
            }
        } catch (Exception $e) {
            // Ignore if column not yet migrated
        }
    }
    return $map;
}

function set_user_avatar_cache(string $username, ?string $avatar): void {
    $map = &avatar_cache_store();
    $key = strtolower(trim($username));
    if ($avatar) {
        $map[$key] = $avatar;
    } else {
        unset($map[$key]);
    }
}

function get_user_avatar_file(string $username): ?string {
    $map = &avatar_cache_store();
    return $map[strtolower(trim($username))] ?? null;
}

/**
 * Render avatar circle markup (supports uploaded profile photo or tinted initials fallback with 5 discrete pairs)
 */
function render_avatar(string $username, int $size = 40, string $extra_class = '', ?string $avatar_override = null): string {
    $tint = get_avatar_tint($username);
    $initials = get_initials($username);
    $fontSize = max(11, (int)($size * 0.38));
    $avatar_file = $avatar_override ?? get_user_avatar_file($username);

    if ($avatar_file) {
        $img_url = UPLOAD_URL . '/' . rawurlencode($avatar_file);
        return '<span class="avatar-circle has-photo ' . e($extra_class) . '" style="width:' . $size . 'px;height:' . $size . 'px;background-color:' . $tint['bg'] . ';color:' . $tint['color'] . ';font-size:' . $fontSize . 'px;font-family:var(--font-brand, \'Space Grotesk\', sans-serif);font-weight:600;" title="' . e($username) . '">'
             . '<img src="' . e($img_url) . '" alt="' . e($username) . '" class="avatar-photo-img" loading="lazy">'
             . '</span>';
    }

    return '<span class="avatar-circle ' . e($extra_class) . '" style="width:' . $size . 'px;height:' . $size . 'px;background-color:' . $tint['bg'] . ';color:' . $tint['color'] . ';font-size:' . $fontSize . 'px;font-family:var(--font-brand, \'Space Grotesk\', sans-serif);font-weight:600;" title="' . e($username) . '">' . e($initials) . '</span>';
}

/**
 * Casual, human time formatting
 */
function time_ago(?string $datetime): string {
    if (!$datetime) {
        return 'just now';
    }
    $ts = strtotime($datetime);
    if (!$ts) {
        return 'just now';
    }
    $diff = time() - $ts;
    if ($diff < 45) {
        return 'just now';
    }
    if ($diff < 3600) {
        $m = max(1, (int)($diff / 60));
        return $m . 'm ago';
    }
    if ($diff < 86400) {
        $h = (int)($diff / 3600);
        return $h . 'h ago';
    }
    if ($diff < 172800) {
        return 'yesterday';
    }
    if ($diff < 604800) {
        $d = (int)($diff / 86400);
        return $d . 'd ago';
    }
    return date('M j', $ts);
}

/**
 * Clean and format tag list
 */
function parse_tags(?string $raw): array {
    if (!$raw) {
        return [];
    }
    $raw = str_replace('#', ' ', $raw);
    $items = preg_split('/[,;\s]+/', $raw);
    $tags = [];
    foreach ($items as $item) {
        $cleaned = strtolower(trim($item));
        if ($cleaned !== '' && strlen($cleaned) <= 30 && !in_array($cleaned, $tags, true)) {
            $tags[] = $cleaned;
        }
    }
    return $tags;
}

/**
 * Rate limit checker
 */
function check_rate_limit(string $action, int $max_allowed, int $window_seconds): bool {
    if (!isset($_SESSION['rate_limits'][$action])) {
        $_SESSION['rate_limits'][$action] = [];
    }
    $now = microtime(true);
    $_SESSION['rate_limits'][$action] = array_filter(
        $_SESSION['rate_limits'][$action],
        fn($ts) => ($now - $ts) < $window_seconds
    );
    if (count($_SESSION['rate_limits'][$action]) >= $max_allowed) {
        return false;
    }
    $_SESSION['rate_limits'][$action][] = $now;
    return true;
}

/**
 * Process photo upload and create 600px GD thumbnail
 */
function process_photo_upload(array $file): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid upload parameter.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File is larger than the server allows.',
            UPLOAD_ERR_FORM_SIZE  => 'File is larger than the form allows (max 5 MB).',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No photo was selected.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload directory on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write photo to disk.',
            UPLOAD_ERR_EXTENSION  => 'A server extension stopped the upload.'
        ];
        throw new Exception($errors[$file['error']] ?? 'Upload error code ' . $file['error']);
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('Photo exceeds max size of 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!array_key_exists($mime, ALLOWED_MIMES)) {
        throw new Exception('Only JPG, PNG or WebP images are allowed (detected: ' . $mime . ').');
    }

    $ext = ALLOWED_MIMES[$mime];
    $random_name = bin2hex(random_bytes(16));
    $orig_filename = $random_name . '.' . $ext;
    $thumb_filename = 'thumb_' . $random_name . '.' . $ext;

    $orig_path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $orig_filename;
    $thumb_path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $thumb_filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $orig_path)) {
        throw new Exception('Could not save uploaded photo to /uploads directory.');
    }

    create_gd_thumbnail($orig_path, $thumb_path, $mime, 600);

    return [
        'filename' => $orig_filename,
        'thumb'    => $thumb_filename
    ];
}

/**
 * Alias for process_photo_upload
 */
function save_uploaded_photo(array $file): array {
    return process_photo_upload($file);
}

/**
 * Create a resized thumbnail with GD (max dimension up to $max_dim)
 */
function create_gd_thumbnail(string $source_path, string $dest_path, string $mime, int $max_dim = 600): void {
    if ($mime === 'image/gif') {
        copy($source_path, $dest_path);
        return;
    }

    $src_img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($source_path),
        'image/png'  => @imagecreatefrompng($source_path),
        'image/webp' => @imagecreatefromwebp($source_path),
        default      => null
    };

    if (!$src_img) {
        copy($source_path, $dest_path);
        return;
    }

    $orig_w = imagesx($src_img);
    $orig_h = imagesy($src_img);

    if ($orig_w <= 0 || $orig_h <= 0) {
        imagedestroy($src_img);
        copy($source_path, $dest_path);
        return;
    }

    $scale = min($max_dim / $orig_w, $max_dim / $orig_h, 1.0);
    $new_w = max(1, (int)round($orig_w * $scale));
    $new_h = max(1, (int)round($orig_h * $scale));

    $thumb_img = imagecreatetruecolor($new_w, $new_h);

    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($thumb_img, false);
        imagesavealpha($thumb_img, true);
        $transparent = imagecolorallocatealpha($thumb_img, 255, 255, 255, 127);
        imagefilledrectangle($thumb_img, 0, 0, $new_w, $new_h, $transparent);
    }

    imagecopyresampled($thumb_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);

    match ($mime) {
        'image/jpeg' => imagejpeg($thumb_img, $dest_path, 88),
        'image/png'  => imagepng($thumb_img, $dest_path, 8),
        'image/webp' => imagewebp($thumb_img, $dest_path, 88),
        default      => imagejpeg($thumb_img, $dest_path, 85)
    };

    imagedestroy($src_img);
    imagedestroy($thumb_img);
}

/**
 * Dispatch an event to the events table
 * Supports both:
 * dispatch_event($channel, $type, $payload, $user_id)
 * and legacy dispatch_event($type, $payload, $user_id)
 */
function dispatch_event($arg1, $arg2, $arg3 = null, ?int $user_id = null): int {
    if (is_array($arg2)) {
        // Legacy: type, payload, user_id
        $channel = ($arg3 !== null) ? ('user:' . (int)$arg3) : 'global';
        $type = (string)$arg1;
        $payload = $arg2;
        $uid = $arg3 ? (int)$arg3 : null;
    } else {
        $channel = (string)$arg1;
        $type = (string)$arg2;
        $payload = is_array($arg3) ? $arg3 : [];
        $uid = $user_id;
    }

    $db = get_db();
    $stmt = $db->prepare("
        INSERT INTO events (channel, user_id, type, payload, created_at)
        VALUES (:channel, :user_id, :type, :payload, NOW())
    ");
    $stmt->execute([
        'channel' => $channel,
        'user_id' => $uid,
        'type'    => $type,
        'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    ]);
    return (int)$db->lastInsertId();
}

/**
 * Delete events older than 1 hour with occasional cleanup
 */
function cleanup_old_events(): void {
    if (mt_rand(1, 40) === 1) {
        try {
            $db = get_db();
            $db->query("DELETE FROM events WHERE created_at < (NOW() - INTERVAL 1 HOUR)");
        } catch (Exception $e) {
            // Ignore background cleanup errors
        }
    }
}

/**
 * Create a user notification and dispatch live notification event
 */
function create_notification(int $user_id, int $actor_id, string $type, ?int $post_id = null): bool {
    if ($user_id === $actor_id) {
        return false;
    }
    $db = get_db();
    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, actor_id, type, post_id, is_read, created_at)
        VALUES (:user_id, :actor_id, :type, :post_id, 0, NOW())
    ");
    $stmt->execute([
        'user_id'  => $user_id,
        'actor_id' => $actor_id,
        'type'     => $type,
        'post_id'  => $post_id
    ]);

    $actor_stmt = $db->prepare("SELECT username, is_verified FROM users WHERE id = :id LIMIT 1");
    $actor_stmt->execute(['id' => $actor_id]);
    $actor = $actor_stmt->fetch();
    $actor_name = $actor['username'] ?? 'someone';
    $actor_verified = (bool)($actor['is_verified'] ?? 0);

    $unread_count = get_unread_notification_count($user_id);

    // Dispatch event to targeted user channel
    dispatch_event('user:' . $user_id, 'notification', [
        'type'           => $type,
        'actor_id'       => $actor_id,
        'actor_name'     => $actor_name,
        'actor_verified' => $actor_verified,
        'post_id'        => $post_id,
        'unread_count'   => $unread_count,
        'message'        => match ($type) {
            'like'    => $actor_name . ' liked your photo',
            'comment' => $actor_name . ' commented on your photo',
            'follow'  => $actor_name . ' started following you',
            default   => $actor_name . ' interacted with you'
        }
    ], $user_id);

    return true;
}

/**
 * Get count of unread notifications for a user
 */
function get_unread_notification_count(int $user_id): int {
    $db = get_db();
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = :id AND is_read = 0");
    $stmt->execute(['id' => $user_id]);
    $row = $stmt->fetch();
    return (int)($row['cnt'] ?? 0);
}

/**
 * Get count of unread direct messages for a user
 */
function get_unread_dm_count(int $user_id, ?int $from_id = null): int {
    $db = get_db();
    if ($from_id !== null) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE to_id = :to_id AND from_id = :from_id AND is_seen = 0");
        $stmt->execute(['to_id' => $user_id, 'from_id' => $from_id]);
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE to_id = :to_id AND is_seen = 0");
        $stmt->execute(['to_id' => $user_id]);
    }
    return (int)$stmt->fetchColumn();
}

/**
 * Check if follower_id is following following_id
 */
function is_following(int $follower_id, int $following_id): bool {
    if ($follower_id === $following_id) {
        return false;
    }
    $db = get_db();
    $stmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = :follower AND following_id = :following LIMIT 1");
    $stmt->execute(['follower' => $follower_id, 'following' => $following_id]);
    return (bool)$stmt->fetch();
}

/**
 * Get IDs of users followed by $user_id
 */
function get_following_ids(int $user_id): array {
    $db = get_db();
    $stmt = $db->prepare("SELECT following_id FROM follows WHERE follower_id = :id");
    $stmt->execute(['id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

/**
 * Get list of currently online users (seen in last 60 seconds)
 */
function get_online_users(int $limit = 12): array {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT id, username, display_name, bio, status_mood, avatar, is_verified, last_seen 
        FROM users 
        WHERE last_seen >= (NOW() - INTERVAL 60 SECOND) 
          AND is_banned = 0
        ORDER BY last_seen DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

/**
 * Get suggested users to follow (not already followed, not self)
 */
function get_people_to_follow(int $current_user_id, int $limit = 5): array {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.display_name, u.bio, u.status_mood, u.avatar, u.is_verified,
               (SELECT COUNT(*) FROM posts WHERE user_id = u.id) AS post_count
        FROM users u
        WHERE u.id != :self_id
          AND u.is_banned = 0
          AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = :self_id2)
        ORDER BY post_count DESC, u.id DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':self_id', $current_user_id, PDO::PARAM_INT);
    $stmt->bindValue(':self_id2', $current_user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

/**
 * Get top trending tags across posts
 */
function get_trending_tags(int $limit = 8): array {
    try {
        $db = get_db();
        $stmt = $db->query("SELECT tags FROM posts WHERE tags IS NOT NULL AND tags != '' ORDER BY created_at DESC LIMIT 200");
        $counts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tags = parse_tags($row['tags']);
            foreach ($tags as $t) {
                $counts[$t] = ($counts[$t] ?? 0) + 1;
            }
        }
        arsort($counts);
        $result = [];
        foreach (array_slice($counts, 0, $limit, true) as $tag => $cnt) {
            $result[] = ['tag' => $tag, 'count' => $cnt];
        }
        return $result;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get active 24h stories with author info
 */
function get_active_stories(int $limit = 20): array {
    try {
        $db = get_db();
        $stmt = $db->prepare("
            SELECT s.id, s.user_id, s.filename, s.thumb, s.caption, s.created_at,
                   u.username, u.display_name, u.is_verified, u.avatar
            FROM stories s
            JOIN users u ON s.user_id = u.id
            WHERE s.created_at >= (NOW() - INTERVAL 24 HOUR)
              AND u.is_banned = 0
            ORDER BY s.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Check if a post is bookmarked by a user
 */
function is_post_bookmarked(int $user_id, int $post_id): bool {
    if ($user_id <= 0 || $post_id <= 0) {
        return false;
    }
    try {
        $db = get_db();
        $stmt = $db->prepare("SELECT 1 FROM bookmarks WHERE user_id = :uid AND post_id = :pid LIMIT 1");
        $stmt->execute(['uid' => $user_id, 'pid' => $post_id]);
        return (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Validate and save an uploaded voice note or audio file
 */
function save_uploaded_audio(array $file): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid audio upload parameters.');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'Audio exceeds server maximum size.',
            UPLOAD_ERR_FORM_SIZE  => 'Audio exceeds form maximum size.',
            UPLOAD_ERR_PARTIAL    => 'Audio was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No audio file was received.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write audio to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
        ];
        throw new Exception($errors[$file['error']] ?? 'Upload error code ' . $file['error']);
    }

    // Max audio size: 10 MB
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('Audio exceeds maximum size of 10 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed_audio_mimes = [
        'audio/webm'               => 'webm',
        'video/webm'               => 'webm',
        'audio/ogg'                => 'ogg',
        'application/ogg'          => 'ogg',
        'audio/x-wav'              => 'wav',
        'audio/wav'                => 'wav',
        'audio/mpeg'               => 'mp3',
        'audio/mp3'                => 'mp3',
        'audio/mp4'                => 'm4a',
        'audio/x-m4a'              => 'm4a',
        'audio/aac'                => 'aac',
        'application/octet-stream' => null
    ];

    $ext = $allowed_audio_mimes[$mime] ?? null;
    if (!$ext) {
        $client_ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (in_array($client_ext, ['webm', 'ogg', 'wav', 'mp3', 'm4a', 'aac'])) {
            $ext = $client_ext;
        } else {
            throw new Exception('Invalid audio format. Allowed: webm, ogg, wav, mp3, m4a (detected: ' . $mime . ').');
        }
    }

    $random_name = 'voice_' . bin2hex(random_bytes(16));
    $audio_filename = $random_name . '.' . $ext;
    $audio_path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $audio_filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $audio_path)) {
        throw new Exception('Could not save voice audio to /uploads directory.');
    }

    return [
        'filename' => $audio_filename,
        'mime'     => $mime
    ];
}

/**
 * Render Chat Message Content (Picture, Voice player, Caption/Text)
 */
function render_chat_media(array $msg): string {
    $html = '';
    $media_type = $msg['media_type'] ?? 'text';
    $media_url = $msg['media_url'] ?? '';
    $media_thumb = $msg['media_thumb'] ?? '';
    $media_duration = (int)($msg['media_duration'] ?? 0);

    if ($media_type === 'image' && !empty($media_url)) {
        $display_src = BASE_URL . '/uploads/' . e($media_thumb ?: $media_url);
        $full_src = BASE_URL . '/uploads/' . e($media_url);
        $html .= '<div class="chat-media-image">';
        $html .= '<a href="' . $full_src . '" target="_blank" onclick="openChatLightbox(event, \'' . $full_src . '\')">';
        $html .= '<img src="' . $display_src . '" alt="Chat picture" class="chat-bubble-img" loading="lazy">';
        $html .= '</a>';
        $html .= '</div>';
    } elseif ($media_type === 'audio' && !empty($media_url)) {
        $audio_src = BASE_URL . '/uploads/' . e($media_url);
        $dur_str = $media_duration > 0 ? gmdate($media_duration >= 3600 ? "H:i:s" : "i:s", $media_duration) : '0:00';
        $html .= '<div class="chat-voice-player" onclick="handleVoicePlayerClick(event, this)">';
        $html .= '<button type="button" class="voice-play-btn" title="Play voice note" aria-label="Play voice note">';
        $html .= '<svg class="icon-play" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><polygon points="6 3 20 12 6 21 6 3"/></svg>';
        $html .= '<svg class="icon-pause" style="display:none;" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>';
        $html .= '</button>';
        $html .= '<div class="voice-track-info">';
        $html .= '<div class="voice-wave-bars" aria-hidden="true">';
        for ($w = 0; $w < 18; $w++) {
            $html .= '<span></span>';
        }
        $html .= '</div>';
        $html .= '<div class="voice-progress-track"><div class="voice-progress-fill" style="width:0%;"></div></div>';
        $html .= '<div class="voice-time-display"><span class="voice-elapsed">0:00</span> / <span class="voice-total">' . e($dur_str) . '</span> <span class="voice-codec-tag">48kHz</span></div>';
        $html .= '</div>';
        $html .= '<audio preload="metadata" src="' . $audio_src . '" style="display:none;"></audio>';
        $html .= '</div>';
    } elseif ($media_type === 'call') {
        $body = trim(preg_replace('/[\x{1F300}-\x{1FAD6}\x{2000}-\x{3300}]/u', '', $msg['body'] ?? ''));
        $is_missed = stripos($body, 'missed') !== false || stripos($body, 'declined') !== false;
        $is_video = stripos($body, 'video') !== false;

        $label = $body;
        $dur_text = '';
        if (str_contains($body, ' · ')) {
            $parts = explode(' · ', $body, 2);
            $label = trim($parts[0]);
            $dur_text = trim($parts[1]);
        } elseif ($media_duration > 0) {
            $dur_text = gmdate($media_duration >= 3600 ? "H:i:s" : "i:s", $media_duration);
        }

        if ($is_missed) {
            $icon_svg = '<svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="2" y1="2" x2="18" y2="18"/><path d="M8.5 13.5a16.2 16.2 0 0 1-3.2-3.2 16.5 16.5 0 0 1-2.4-7 1.7 1.7 0 0 1 1.7-1.8h2.5a1.7 1.7 0 0 1 1.7 1.5c.2.8.4 1.6.6 2.4a1.7 1.7 0 0 1-.4 1.8l-1.1 1.1"/></svg>';
        } elseif ($is_video) {
            $icon_svg = '<svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="12" height="12" rx="2"/><polygon points="14 8 18 5 18 15 14 12"/></svg>';
        } else {
            $icon_svg = '<svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 13.8v2.5a1.7 1.7 0 0 1-1.8 1.7 16.5 16.5 0 0 1-7.2-2.6 16.2 16.2 0 0 1-5-5A16.5 16.5 0 0 1 .4 3.2 1.7 1.7 0 0 1 2.1 1.4h2.5a1.7 1.7 0 0 1 1.7 1.5 10.7 10.7 0 0 0 .6 2.4 1.7 1.7 0 0 1-.4 1.8L5.4 8.2a13.2 13.2 0 0 0 5 5l1.1-1.1a1.7 1.7 0 0 1 1.8-.4 10.7 10.7 0 0 0 2.4.6 1.7 1.7 0 0 1 1.3 1.5z"/></svg>';
        }

        $html .= '<div class="call-event-pill">';
        $html .= '<span class="call-pill-icon-holder">' . $icon_svg . '</span>';
        $html .= '<span class="call-pill-text">';
        $html .= '<strong>' . e($label ?: ($is_video ? 'Video call ended' : 'Voice call ended')) . '</strong>';
        if ($dur_text !== '') {
            $html .= '<span class="call-pill-dur"> · ' . e($dur_text) . '</span>';
        }
        $html .= '</span>';
        $html .= '</div>';
    }

    if (!empty($msg['body']) && $media_type !== 'call') {
        $margin = ($media_type !== 'text') ? 'style="margin-top:6px;"' : '';
        $html .= '<div class="chat-msg-text" ' . $margin . '>' . nl2br(e($msg['body'])) . '</div>';
    }

    return $html;
}


