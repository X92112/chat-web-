<?php
// Snapwall / Nexly Server-Sent Events Endpoint
// Channel-based real-time event streaming
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Authentication check
$user = current_user();
if (!$user) {
    http_response_code(401);
    header('Content-Type: text/event-stream; charset=utf-8');
    echo "event: error\ndata: " . json_encode(['error' => 'Unauthenticated']) . "\n\n";
    exit;
}

$my_id = (int)$user['id'];
$user_channel = 'user:' . $my_id;

// CRITICAL: Close session before entering loop so PHP does not block other requests
session_write_close();

// Turn off output buffering for real-time flushing
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', '0');
@ini_set('implicit_flush', '1');
while (ob_get_level() > 0) {
    @ob_end_flush();
}

// SSE Headers
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-transform');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
header('Content-Encoding: none');

$db = get_db();

// Determine starting event ID
$last_event_id = 0;
if (isset($_SERVER['HTTP_LAST_EVENT_ID']) && is_numeric($_SERVER['HTTP_LAST_EVENT_ID'])) {
    $last_event_id = (int)$_SERVER['HTTP_LAST_EVENT_ID'];
} elseif (isset($_GET['last_id']) && is_numeric($_GET['last_id'])) {
    $last_event_id = (int)$_GET['last_id'];
} else {
    // When there is no Last-Event-ID, start from the current max id
    $max_stmt = $db->query("SELECT COALESCE(MAX(id), 0) FROM events");
    $last_event_id = (int)$max_stmt->fetchColumn();
}

// 2KB padding to defeat Cloudflare tunnel buffer on connect
echo ":" . str_repeat(" ", 2048) . "\n\n";
// Reconnect recommendation in ms
echo "retry: 1500\n\n";
flush();

// Clean up events older than 1 hour occasionally
cleanup_old_events();

$start_time = time();
$last_ping = time();
$loop_duration = 24; // loop ~24-25 seconds then exit for clean reconnection

while ((time() - $start_time) < $loop_duration) {
    if (connection_aborted()) {
        break;
    }

    // Query channels visible to this user: 'lobby', 'global', and 'user:<id>'
    $stmt = $db->prepare("
        SELECT id, channel, user_id, type, payload, created_at
        FROM events
        WHERE channel IN ('lobby', 'global', :user_channel)
          AND id > :last_id
        ORDER BY id ASC
        LIMIT 50
    ");
    $stmt->execute([
        'user_channel' => $user_channel,
        'last_id'      => $last_event_id
    ]);
    $events = $stmt->fetchAll();

    if (!empty($events)) {
        foreach ($events as $event) {
            $event_id = (int)$event['id'];
            $type = $event['type'];
            $payload_raw = $event['payload'];

            // Stream event
            echo "id: {$event_id}\n";
            echo "event: {$type}\n";
            echo "data: {$payload_raw}\n\n";
            flush();

            $last_event_id = max($last_event_id, $event_id);
        }
    } else {
        // Send keepalive ping only every 12 seconds to prevent buffer and connection bloat
        if ((time() - $last_ping) >= 12) {
            echo ": keepalive\n\n";
            flush();
            $last_ping = time();
        }
        usleep(250000); // 250ms polling for low DB load with instant responsiveness
    }
}

// Clean loop end; EventSource will automatically reconnect with Last-Event-ID
echo ": reconnect\n\n";
flush();
exit;
