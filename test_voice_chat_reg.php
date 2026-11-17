<?php
// Comprehensive Automated Test for Voice Notes, Chat Pictures & Register Page
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/helpers.php';

$base_url = 'http://localhost/snapwall';

class TestSession {
    public string $username;
    public string $cookie_file;
    public string $csrf_token = '';
    public int $last_event_id = 0;
    public int $user_id = 0;

    public function __construct(string $username) {
        $this->username = $username;
        $this->cookie_file = tempnam(sys_get_temp_dir(), 'test_sess_' . $username . '_');
    }

    public function __destruct() {
        if (file_exists($this->cookie_file)) {
            @unlink($this->cookie_file);
        }
    }

    public function request(string $url, string $method = 'GET', $data = null, array $headers = []): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $all_headers = $headers;
        if ($this->csrf_token && !isset($headers['X-CSRF-Token'])) {
            $all_headers[] = 'X-CSRF-Token: ' . $this->csrf_token;
        }
        if (!empty($all_headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $all_headers);
        }

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (preg_match('/<meta name="csrf-token" content="([a-f0-9]{64})"/i', $body, $m)) {
            $this->csrf_token = $m[1];
        } elseif (preg_match('/name="csrf_token"\s+value="([a-f0-9]{64})"/i', $body, $m)) {
            $this->csrf_token = $m[1];
        }

        return ['code' => $code, 'body' => $body];
    }

    public function login(string $password, string $base_url): bool {
        $this->request("$base_url/login.php");
        $res = $this->request("$base_url/login.php", 'POST', http_build_query([
            'username'   => $this->username,
            'password'   => $password,
            'csrf_token' => $this->csrf_token
        ]), ['Content-Type: application/x-www-form-urlencoded']);

        $db = get_db();
        $stmt = $db->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $stmt->execute(['u' => $this->username]);
        $this->user_id = (int)$stmt->fetchColumn();

        return ($res['code'] === 200 && strpos($res['body'], 'What did you shoot today?') !== false);
    }

    public function fetch_events(string $base_url): array {
        $res = $this->request("$base_url/actions/poll.php?last_id={$this->last_event_id}");
        $json = json_decode($res['body'], true);
        $events = $json['events'] ?? [];
        if (!empty($events)) {
            $max_id = $this->last_event_id;
            foreach ($events as $ev) {
                if ($ev['id'] > $max_id) $max_id = $ev['id'];
            }
            $this->last_event_id = $max_id;
        }
        return $events;
    }
}

$db = get_db();
// Reset RT password to 123
$db->query("UPDATE users SET password_hash = '" . password_hash('123', PASSWORD_DEFAULT) . "', must_change_password = 1, is_verified = 1, is_admin = 1 WHERE username = 'RT'");

$passed = 0;
$total = 0;

function assert_test(bool $cond, string $desc) {
    global $passed, $total;
    $total++;
    if ($cond) {
        $passed++;
        echo "  [PASS] $desc\n";
    } else {
        echo "  [FAIL] $desc\n";
        exit(1);
    }
}

echo "=================================================================\n";
echo "   NEXLY: TESTING CHAT PICTURES, VOICE NOTES & REGISTER PAGE     \n";
echo "=================================================================\n\n";

// -------------------------------------------------------------
// 1. TEST NEW REGISTER PAGE
// -------------------------------------------------------------
echo "TEST 1: Testing Registration Validation & Account Creation...\n";
$guest = new TestSession('guest_' . mt_rand(100, 999));
$guest->request("$base_url/register.php");

// Reserved username
$r1 = $guest->request("$base_url/register.php", 'POST', [
    'username'   => 'official',
    'email'      => 'official@nexly.test',
    'password'   => 'validpass123',
    'csrf_token' => $guest->csrf_token
]);
assert_test(strpos($r1['body'], 'This username is reserved and cannot be registered.') !== false, "Registration of reserved username 'official' is rejected");

// Short password (< 6 chars)
$r2 = $guest->request("$base_url/register.php", 'POST', [
    'username'   => 'lens_' . mt_rand(100, 999),
    'email'      => 'lens_' . mt_rand(100, 999) . '@nexly.test',
    'password'   => '12345',
    'csrf_token' => $guest->csrf_token
]);
assert_test(strpos($r2['body'], 'at least 6 characters') !== false, "Registration with password < 6 chars is rejected");

// Successful new registration
$new_user_name = 'lens_' . mt_rand(1000, 9999);
$new_email = $new_user_name . '@nexly.test';
$r3 = $guest->request("$base_url/register.php", 'POST', [
    'username'   => $new_user_name,
    'email'      => $new_email,
    'password'   => 'great_password_2026',
    'bio'        => 'landscape & morning coffee',
    'csrf_token' => $guest->csrf_token
]);
$chk_u = $db->prepare("SELECT id, username, bio, is_verified, is_admin FROM users WHERE username = :u LIMIT 1");
$chk_u->execute(['u' => $new_user_name]);
$created_user = $chk_u->fetch();

assert_test($created_user !== false, "New user '$new_user_name' successfully registered and created in database");
assert_test((int)$created_user['is_verified'] === 0 && (int)$created_user['is_admin'] === 0, "New user cannot elevate privileges (is_verified=0, is_admin=0)");

// -------------------------------------------------------------
// 2. TEST CHAT PICTURE UPLOAD (LOBBY & DM)
// -------------------------------------------------------------
echo "\nTEST 2: Testing Chat Picture Upload in Lobby & DM...\n";

// Login Dara and RT
$dara = new TestSession('Dara');
$dara->login('password123', $base_url);
$dara->request("$base_url/chat.php");

$rt = new TestSession('RT');
$rt->login('123', $base_url);
$rt->request("$base_url/chat.php");

// Sync RT event ID
$rt->fetch_events($base_url);

// Create temporary image
$test_img = sys_get_temp_dir() . '/test_chat_pic_' . mt_rand(1000, 9999) . '.png';
$im = imagecreatetruecolor(250, 180);
$orange = imagecolorallocate($im, 255, 90, 54);
imagefill($im, 0, 0, $orange);
imagepng($im, $test_img);
imagedestroy($im);

sleep(1); // rate limit

// A. Dara sends photo to Lobby
$res_lobby_pic = $dara->request("$base_url/actions/chat_message.php", 'POST', [
    'channel'    => 'lobby',
    'csrf_token' => $dara->csrf_token,
    'body'       => 'Check out my new lens test in Lobby!',
    'media_type' => 'image',
    'chat_image' => new CURLFile($test_img, 'image/png', 'sunset.png')
]);
$json_lobby_pic = json_decode($res_lobby_pic['body'], true);

assert_test(!empty($json_lobby_pic['success']), "Lobby photo message sent successfully");
assert_test(!empty($json_lobby_pic['message']['media_url']), "Photo filename returned in payload");
assert_test(file_exists(UPLOAD_DIR . '/' . $json_lobby_pic['message']['media_url']), "Full photo file exists on disk in /uploads");
assert_test(!empty($json_lobby_pic['message']['media_thumb']) && file_exists(UPLOAD_DIR . '/' . $json_lobby_pic['message']['media_thumb']), "Thumbnail file created on disk in /uploads");

sleep(1); // rate limit

// B. Dara sends photo in 1-to-1 DM to RT
$res_dm_pic = $dara->request("$base_url/actions/chat_message.php", 'POST', [
    'channel'    => 'dm',
    'to_id'      => $rt->user_id,
    'csrf_token' => $dara->csrf_token,
    'body'       => 'Private photo for CEO RT',
    'media_type' => 'image',
    'chat_image' => new CURLFile($test_img, 'image/png', 'private_preview.png')
]);
$json_dm_pic = json_decode($res_dm_pic['body'], true);

assert_test(!empty($json_dm_pic['success']), "1-to-1 DM photo sent successfully to RT");
assert_test(!empty($json_dm_pic['message']['media_url']), "DM Photo URL present in payload");

// RT receives SSE/Poll event for DM photo
$rt_events = $rt->fetch_events($base_url);
$dm_event_found = false;
foreach ($rt_events as $ev) {
    if ($ev['type'] === 'dm_message' && ($ev['payload']['media_type'] ?? '') === 'image') {
        $dm_event_found = true;
        break;
    }
}
assert_test($dm_event_found, "RT received real-time dm_message event containing photo attachment");

// -------------------------------------------------------------
// 3. TEST VOICE MESSAGE UPLOAD (LOBBY & DM)
// -------------------------------------------------------------
echo "\nTEST 3: Testing Voice Note Upload in Lobby & DM...\n";

// Create temporary audio file (minimal WAV)
$test_audio = sys_get_temp_dir() . '/test_voice_' . mt_rand(1000, 9999) . '.wav';
$sample_rate = 8000;
$data_len = 8000;
$wav_header = pack(
    'a4Va4a4VvvVVvva4V',
    'RIFF', $data_len + 36, 'WAVE', 'fmt ', 16, 1, 1, $sample_rate, $sample_rate, 1, 8, 'data', $data_len
);
file_put_contents($test_audio, $wav_header . str_repeat("\x80", $data_len));

sleep(1); // rate limit

// A. Dara sends voice note to Lobby (no text, voice only)
$res_voice_lobby = $dara->request("$base_url/actions/chat_message.php", 'POST', [
    'channel'        => 'lobby',
    'csrf_token'     => $dara->csrf_token,
    'body'           => '',
    'media_type'     => 'audio',
    'media_duration' => 9,
    'voice_audio'    => new CURLFile($test_audio, 'audio/wav', 'voice_lobby.wav')
]);
$json_voice_lobby = json_decode($res_voice_lobby['body'], true);

assert_test(!empty($json_voice_lobby['success']), "Voice note sent to Lobby successfully");
assert_test(!empty($json_voice_lobby['message']['media_url']), "Voice note audio URL returned in payload");
assert_test(file_exists(UPLOAD_DIR . '/' . $json_voice_lobby['message']['media_url']), "Voice note file exists on disk in /uploads");
assert_test((int)$json_voice_lobby['message']['media_duration'] === 9, "Voice note duration (9s) saved correctly");

sleep(1); // rate limit

// B. Dara sends voice note in 1-to-1 DM to RT
$res_voice_dm = $dara->request("$base_url/actions/chat_message.php", 'POST', [
    'channel'        => 'dm',
    'to_id'          => $rt->user_id,
    'csrf_token'     => $dara->csrf_token,
    'body'           => 'Listen to my audio memo',
    'media_type'     => 'audio',
    'media_duration' => 16,
    'voice_audio'    => new CURLFile($test_audio, 'audio/wav', 'voice_dm.wav')
]);
$json_voice_dm = json_decode($res_voice_dm['body'], true);

assert_test(!empty($json_voice_dm['success']), "1-to-1 DM voice note sent successfully to RT");
assert_test(!empty($json_voice_dm['message']['media_url']), "DM voice audio URL returned in payload");

// RT receives SSE/Poll event for DM voice note
$rt_events2 = $rt->fetch_events($base_url);
$voice_event_found = false;
foreach ($rt_events2 as $ev) {
    if ($ev['type'] === 'dm_message' && ($ev['payload']['media_type'] ?? '') === 'audio') {
        $voice_event_found = true;
        break;
    }
}
assert_test($voice_event_found, "RT received real-time dm_message event containing voice note attachment");

// -------------------------------------------------------------
// 4. TEST RENDERING HELPER (render_chat_media)
// -------------------------------------------------------------
echo "\nTEST 4: Testing render_chat_media() Output Markup...\n";

$rendered_img = render_chat_media([
    'media_type'     => 'image',
    'media_url'      => $json_lobby_pic['message']['media_url'],
    'media_thumb'    => $json_lobby_pic['message']['media_thumb'],
    'body'           => 'Look at this photo',
    'media_duration' => 0
]);
assert_test(strpos($rendered_img, 'chat-media-image') !== false, "Image container rendered by helper");
assert_test(strpos($rendered_img, 'openChatLightbox') !== false, "Lightbox trigger link included in image HTML");
assert_test(strpos($rendered_img, 'Look at this photo') !== false, "Caption included in image HTML");

$rendered_audio = render_chat_media([
    'media_type'     => 'audio',
    'media_url'      => $json_voice_lobby['message']['media_url'],
    'media_thumb'    => null,
    'body'           => '',
    'media_duration' => 9
]);
assert_test(strpos($rendered_audio, 'chat-voice-player') !== false, "Custom voice player container rendered by helper");
assert_test(strpos($rendered_audio, 'voice-play-btn') !== false, "Voice play button rendered");
assert_test(strpos($rendered_audio, '<audio') !== false, "Underlying <audio> tag present for playback");

// Cleanup temp files
@unlink($test_img);
@unlink($test_audio);

echo "\n=================================================================\n";
echo "   RESULTS: $passed / $total TESTS PASSED (100% SUCCESS!)\n";
echo "=================================================================\n";

exit(0);
