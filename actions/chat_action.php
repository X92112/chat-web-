<?php
// Action: Chat Actions (Typing, Mark Seen, Delete Message, Load Older)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$current_user = current_user();
if (!$current_user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated']);
    exit;
}

$action = $_REQUEST['action'] ?? '';
$db = get_db();
$my_id = (int)$current_user['id'];
$is_admin = !empty($current_user['is_admin']);

// Release session lock early to allow concurrent requests without blocking
session_write_close();

// 1. TYPING INDICATOR
if ($action === 'typing') {
    $channel = trim($_POST['channel'] ?? 'lobby');
    $to_id = (int)($_POST['to_id'] ?? 0);

    if ($channel === 'lobby') {
        dispatch_event('lobby', 'lobby_typing', [
            'user_id'  => $my_id,
            'username' => $current_user['username']
        ], $my_id);
    } elseif ($channel === 'dm' && $to_id > 0) {
        dispatch_event('user:' . $to_id, 'dm_typing', [
            'from_id'       => $my_id,
            'from_username' => $current_user['username']
        ], $my_id);
    }
    echo json_encode(['success' => true]);
    exit;
}

// 2. MARK SEEN (in DM)
if ($action === 'mark_seen') {
    check_csrf_or_fail(true);
    $from_id = (int)($_POST['from_id'] ?? 0);
    if ($from_id > 0) {
        $stmt = $db->prepare("
            UPDATE messages 
            SET is_seen = 1, seen_at = NOW() 
            WHERE to_id = :my_id AND from_id = :from_id AND is_seen = 0
        ");
        $stmt->execute(['my_id' => $my_id, 'from_id' => $from_id]);
        $rows = $stmt->rowCount();

        if ($rows > 0) {
            // Notify the sender that their messages were seen
            dispatch_event('user:' . $from_id, 'dm_seen', [
                'reader_id'   => $my_id,
                'reader_name' => $current_user['username'],
                'seen_at'     => date('H:i')
            ], $my_id);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// 3. DELETE MESSAGE (Own message or Admin)
if ($action === 'delete_message') {
    check_csrf_or_fail(true);
    $type = trim($_POST['type'] ?? 'lobby');
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid message ID']);
        exit;
    }

    if ($type === 'lobby') {
        $stmt = $db->prepare("SELECT id, user_id, media_url, media_thumb FROM lobby_messages WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $msg = $stmt->fetch();
        if (!$msg) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Message not found']);
            exit;
        }

        if ((int)$msg['user_id'] !== $my_id && !$is_admin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You cannot delete this message']);
            exit;
        }

        if (!empty($msg['media_url'])) {
            @unlink(UPLOAD_DIR . DIRECTORY_SEPARATOR . $msg['media_url']);
        }
        if (!empty($msg['media_thumb'])) {
            @unlink(UPLOAD_DIR . DIRECTORY_SEPARATOR . $msg['media_thumb']);
        }

        $db->prepare("DELETE FROM lobby_messages WHERE id = :id")->execute(['id' => $id]);
        dispatch_event('lobby', 'lobby_delete', ['id' => $id]);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    } elseif ($type === 'dm') {
        $stmt = $db->prepare("SELECT id, from_id, to_id, media_url, media_thumb FROM messages WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $msg = $stmt->fetch();
        if (!$msg) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Message not found']);
            exit;
        }

        if ((int)$msg['from_id'] !== $my_id && !$is_admin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You cannot delete this message']);
            exit;
        }

        if (!empty($msg['media_url'])) {
            @unlink(UPLOAD_DIR . DIRECTORY_SEPARATOR . $msg['media_url']);
        }
        if (!empty($msg['media_thumb'])) {
            @unlink(UPLOAD_DIR . DIRECTORY_SEPARATOR . $msg['media_thumb']);
        }

        $other_id = ((int)$msg['from_id'] === $my_id) ? (int)$msg['to_id'] : (int)$msg['from_id'];
        $db->prepare("DELETE FROM messages WHERE id = :id")->execute(['id' => $id]);

        dispatch_event('user:' . $other_id, 'dm_delete', ['id' => $id]);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }
}

// 4. LOAD OLDER LOBBY MESSAGES
if ($action === 'load_older_lobby') {
    $before_id = (int)($_GET['before_id'] ?? 0);
    if ($before_id <= 0) {
        echo json_encode(['success' => true, 'messages' => []]);
        exit;
    }

    $stmt = $db->prepare("
        SELECT lm.id, lm.user_id, lm.body, lm.media_type, lm.media_url, lm.media_thumb, lm.media_duration, lm.created_at,
               u.username, u.is_verified, u.is_admin
        FROM lobby_messages lm
        JOIN users u ON lm.user_id = u.id
        WHERE lm.id < :before_id
        ORDER BY lm.id DESC
        LIMIT 30
    ");
    $stmt->execute(['before_id' => $before_id]);
    $older = array_reverse($stmt->fetchAll());

    $formatted = [];
    foreach ($older as $m) {
        $formatted[] = [
            'id'             => (int)$m['id'],
            'user_id'        => (int)$m['user_id'],
            'username'       => $m['username'],
            'is_verified'    => !empty($m['is_verified']),
            'is_admin'       => !empty($m['is_admin']),
            'avatar_color'   => get_avatar_color($m['username']),
            'initials'       => get_initials($m['username']),
            'body'           => $m['body'] ?? '',
            'media_type'     => $m['media_type'] ?? 'text',
            'media_url'      => $m['media_url'] ?? null,
            'media_thumb'    => $m['media_thumb'] ?? null,
            'media_duration' => (int)($m['media_duration'] ?? 0),
            'created_at'     => time_ago($m['created_at'])
        ];
    }

    echo json_encode([
        'success'  => true,
        'messages' => $formatted
    ]);
    exit;
}

// 5. LOAD OLDER DM MESSAGES
if ($action === 'load_older_dm') {
    $before_id = (int)($_GET['before_id'] ?? 0);
    $partner_id = (int)($_GET['partner_id'] ?? 0);
    if ($before_id <= 0 || $partner_id <= 0) {
        echo json_encode(['success' => true, 'messages' => []]);
        exit;
    }

    $stmt = $db->prepare("
        SELECT id, from_id, to_id, body, media_type, media_url, media_thumb, media_duration, is_seen, seen_at, created_at
        FROM messages
        WHERE ((from_id = :my_id1 AND to_id = :p_id1) OR (from_id = :p_id2 AND to_id = :my_id2))
          AND id < :before_id
        ORDER BY id DESC
        LIMIT 40
    ");
    $stmt->execute([
        'my_id1'    => $my_id, 'p_id1' => $partner_id,
        'p_id2'     => $partner_id, 'my_id2' => $my_id,
        'before_id' => $before_id
    ]);
    $older = array_reverse($stmt->fetchAll());

    $formatted = [];
    foreach ($older as $m) {
        $formatted[] = [
            'id'             => (int)$m['id'],
            'from_id'        => (int)$m['from_id'],
            'to_id'          => (int)$m['to_id'],
            'body'           => $m['body'] ?? '',
            'media_type'     => $m['media_type'] ?? 'text',
            'media_url'      => $m['media_url'] ?? null,
            'media_thumb'    => $m['media_thumb'] ?? null,
            'media_duration' => (int)($m['media_duration'] ?? 0),
            'is_seen'        => (int)$m['is_seen'],
            'created_at'     => time_ago($m['created_at'])
        ];
    }

    echo json_encode([
        'success'  => true,
        'messages' => $formatted
    ]);
    exit;
}

// 6. FETCH NEWEST MESSAGES (Fast 2s Delta Polling for live reliability)
if ($action === 'fetch_new') {
    $channel = trim($_GET['channel'] ?? 'lobby');
    $after_id = (int)($_GET['after_id'] ?? 0);
    $partner_id = (int)($_GET['partner_id'] ?? 0);

    $messages = [];
    if ($channel === 'lobby') {
        $stmt = $db->prepare("
            SELECT lm.id, lm.user_id, lm.body, lm.media_type, lm.media_url, lm.media_thumb, lm.media_duration, lm.created_at,
                   u.username, u.is_verified, u.is_admin
            FROM lobby_messages lm
            JOIN users u ON lm.user_id = u.id
            WHERE lm.id > :after_id
            ORDER BY lm.id ASC
            LIMIT 50
        ");
        $stmt->execute(['after_id' => $after_id]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $m) {
            $messages[] = [
                'id'             => (int)$m['id'],
                'user_id'        => (int)$m['user_id'],
                'username'       => $m['username'],
                'is_verified'    => !empty($m['is_verified']),
                'is_admin'       => !empty($m['is_admin']),
                'avatar_color'   => get_avatar_color($m['username']),
                'initials'       => get_initials($m['username']),
                'body'           => $m['body'] ?? '',
                'media_type'     => $m['media_type'] ?? 'text',
                'media_url'      => $m['media_url'] ?? null,
                'media_thumb'    => $m['media_thumb'] ?? null,
                'media_duration' => (int)($m['media_duration'] ?? 0),
                'created_at'     => time_ago($m['created_at'])
            ];
        }
    } elseif ($channel === 'dm' && $partner_id > 0) {
        $stmt = $db->prepare("
            SELECT id, from_id, to_id, body, media_type, media_url, media_thumb, media_duration, is_seen, seen_at, created_at
            FROM messages
            WHERE ((from_id = :my_id1 AND to_id = :p_id1) OR (from_id = :p_id2 AND to_id = :my_id2))
              AND id > :after_id
            ORDER BY id ASC
            LIMIT 50
        ");
        $stmt->execute([
            'my_id1'    => $my_id, 'p_id1' => $partner_id,
            'p_id2'     => $partner_id, 'my_id2' => $my_id,
            'after_id'  => $after_id
        ]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $m) {
            $messages[] = [
                'id'             => (int)$m['id'],
                'from_id'        => (int)$m['from_id'],
                'to_id'          => (int)$m['to_id'],
                'body'           => $m['body'] ?? '',
                'media_type'     => $m['media_type'] ?? 'text',
                'media_url'      => $m['media_url'] ?? null,
                'media_thumb'    => $m['media_thumb'] ?? null,
                'media_duration' => (int)($m['media_duration'] ?? 0),
                'is_seen'        => (int)$m['is_seen'],
                'created_at'     => time_ago($m['created_at'])
            ];
        }

        // Auto-mark incoming as seen if we are currently viewing this thread
        $db->prepare("UPDATE messages SET is_seen = 1, seen_at = NOW() WHERE to_id = :my_id AND from_id = :p_id AND is_seen = 0")
           ->execute(['my_id' => $my_id, 'p_id' => $partner_id]);
    }

    echo json_encode([
        'success'  => true,
        'messages' => $messages
    ]);
    exit;
}

// 7. WEBRTC CALL SIGNALING & CALL LOGGING
if ($action === 'call_signal') {
    $to_id = (int)($_POST['to_id'] ?? 0);
    $signal_type = trim($_POST['signal_type'] ?? '');
    $signal_data = json_decode($_POST['signal_data'] ?? '{}', true) ?: [];

    if ($to_id <= 0 || $to_id === $my_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid call recipient']);
        exit;
    }

    $valid_signals = ['call_ring', 'call_ringing_ack', 'call_answer', 'call_candidate', 'call_candidates_batch', 'call_reject', 'call_end', 'call_log'];
    if (!in_array($signal_type, $valid_signals, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid signal type']);
        exit;
    }

    $payload = array_merge($signal_data, [
        'from_id'       => $my_id,
        'from_username' => $current_user['username'],
        'from_name'     => $current_user['display_name'] ?: $current_user['username'],
        'from_avatar'   => $current_user['avatar'] ?? null,
        'timestamp'     => time()
    ]);

    // Dispatch real-time signal event to recipient channel
    dispatch_event('user:' . $to_id, $signal_type, $payload, $my_id);

    // If call ended, missed, or declined, write to messages table so it appears in chat history
    if ($signal_type === 'call_log') {
        $call_type = $signal_data['call_type'] ?? 'audio';
        $status = $signal_data['status'] ?? 'ended'; // 'ended', 'missed', 'declined'
        $duration = max(0, (int)($signal_data['duration'] ?? 0));

        $dur_str = '';
        if ($status === 'ended') {
            $m = floor($duration / 60);
            $s = $duration % 60;
            $dur_str = ' · ' . sprintf("%d:%02d", $m, $s);
        }

        if ($status === 'ended') {
            $body = ($call_type === 'video' ? '📹 Video call' : '📞 Voice call') . ' ended' . $dur_str;
        } elseif ($status === 'missed') {
            $body = ($call_type === 'video' ? '📹 Missed video call' : '📞 Missed voice call');
        } else {
            $body = ($call_type === 'video' ? '📹 Video call declined' : '📞 Voice call declined');
        }

        $stmt = $db->prepare("
            INSERT INTO messages (from_id, to_id, body, media_type, media_duration, is_seen, created_at)
            VALUES (:from_id, :to_id, :body, 'call', :duration, 0, NOW())
        ");
        $stmt->execute([
            'from_id'  => $my_id,
            'to_id'    => $to_id,
            'body'     => $body,
            'duration' => $duration
        ]);
        $msg_id = (int)$db->lastInsertId();

        $msg_payload = [
            'id'             => $msg_id,
            'from_id'        => $my_id,
            'to_id'          => $to_id,
            'body'           => $body,
            'media_type'     => 'call',
            'media_url'      => null,
            'media_thumb'    => null,
            'media_duration' => $duration,
            'is_seen'        => 0,
            'created_at'     => 'just now'
        ];
        dispatch_event('user:' . $to_id, 'dm_message', $msg_payload, $my_id);

        echo json_encode(['success' => true, 'message' => $msg_payload]);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action']);
