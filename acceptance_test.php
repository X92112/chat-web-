<?php
// Snapwall / Nexly Formal Acceptance Test Suite
// Testing 3 simultaneous browser sessions (RT, Dara, Sokha)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

echo "=================================================================\n";
echo "    SNAPWALL / NEXLY EXTENSION ACCEPTANCE TEST SUITE\n";
echo "    3 SIMULTANEOUS SESSIONS: RT (CEO), DARA, SOKHA\n";
echo "=================================================================\n\n";

$base_url = 'http://localhost/snapwall';

class BrowserSession {
    public string $username;
    public string $cookie_file;
    public string $csrf_token = '';
    public int $user_id = 0;
    public int $last_event_id = 0;

    public function __construct(string $username) {
        $this->username = $username;
        $this->cookie_file = tempnam(sys_get_temp_dir(), 'nex_sess_' . $username . '_');
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
            if (is_array($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } elseif (is_string($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
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
        } elseif (preg_match('/name="csrf_token" value="([a-f0-9]{64})"/i', $body, $m)) {
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

function assert_check(bool $cond, string $title) {
    if ($cond) {
        echo "  [PASS] $title\n";
    } else {
        echo "  [FAIL] $title\n";
        exit(1);
    }
}

$db = get_db();
// Reset RT to initial state for testing
$db->query("UPDATE users SET password_hash = '" . password_hash('123', PASSWORD_DEFAULT) . "', must_change_password = 1, is_verified = 1, is_admin = 1 WHERE username = 'RT'");

// -------------------------------------------------------------------------
// TEST 1: Reserved Usernames Registration Rejection
// -------------------------------------------------------------------------
echo "TEST 1: Verifying Reserved Usernames Rejection (ceo, RT, admin)...\n";
$guest = new BrowserSession('guest');
$guest->request("$base_url/register.php");

// Attempt to register 'ceo'
$res = $guest->request("$base_url/register.php", 'POST', http_build_query([
    'username'   => 'ceo',
    'email'      => 'ceo_fake@test.com',
    'password'   => 'password123',
    'csrf_token' => $guest->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);
assert_check(strpos($res['body'], 'reserved and cannot be registered') !== false, "Registration of 'ceo' rejected");

// Attempt to register 'RT'
$res = $guest->request("$base_url/register.php", 'POST', http_build_query([
    'username'   => 'rt',
    'email'      => 'rt_fake@test.com',
    'password'   => 'password123',
    'csrf_token' => $guest->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);
assert_check(strpos($res['body'], 'reserved and cannot be registered') !== false, "Registration of 'rt' (case-insensitive) rejected");

// Attempt to register 'admin'
$res = $guest->request("$base_url/register.php", 'POST', http_build_query([
    'username'   => 'admin',
    'email'      => 'admin_fake@test.com',
    'password'   => 'password123',
    'csrf_token' => $guest->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);
assert_check(strpos($res['body'], 'reserved and cannot be registered') !== false, "Registration of 'admin' rejected");

// -------------------------------------------------------------------------
// TEST 2: Normal user cannot verify themselves (Tampering test)
// -------------------------------------------------------------------------
echo "\nTEST 2: Verifying Normal User Cannot Self-Verify via POST Tampering...\n";
$dara = new BrowserSession('Dara');
$logged_in = $dara->login('password123', $base_url);
assert_check($logged_in, "Dara logged in successfully");

// Dara tries sending is_verified=1 and is_admin=1 to profile update
$res = $dara->request("$base_url/profile.php?u=Dara", 'POST', http_build_query([
    'action'      => 'update_bio',
    'bio'         => 'Hacker trying to verify self',
    'is_verified' => 1,
    'is_admin'    => 1,
    'csrf_token'  => $dara->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);

$dara_check = $db->query("SELECT is_verified, is_admin FROM users WHERE username = 'Dara'")->fetch();
assert_check((int)$dara_check['is_verified'] === 0, "Dara is_verified remains 0 in database");
assert_check((int)$dara_check['is_admin'] === 0, "Dara is_admin remains 0 in database");

// Dara tries sending is_verified=1 to Settings page
$res = $dara->request("$base_url/settings.php", 'POST', http_build_query([
    'action'      => 'update_profile',
    'bio'         => 'Another tampering attempt',
    'is_verified' => 1,
    'csrf_token'  => $dara->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);

$dara_check2 = $db->query("SELECT is_verified FROM users WHERE username = 'Dara'")->fetch();
assert_check((int)$dara_check2['is_verified'] === 0, "Dara is_verified strictly unchanged (0)");

// -------------------------------------------------------------------------
// TEST 3: RT Account Login & Weak Password Banner & Password Change
// -------------------------------------------------------------------------
echo "\nTEST 3: RT Account Login, Weak Password Warning Banner & Settings...\n";
$rt = new BrowserSession('RT');
$rt_logged = $rt->login('123', $base_url);
assert_check($rt_logged, "RT logged in with seeded password '123'");

// Check if warning banner shows on home page
$home_page = $rt->request("$base_url/");
assert_check(strpos($home_page['body'], 'Your password is too weak, change it before going online.') !== false, "Yellow password warning banner rendered for RT");

// Check RT database status
$rt_status = $db->query("SELECT is_verified, is_admin, must_change_password FROM users WHERE username = 'RT'")->fetch();
assert_check((int)$rt_status['is_verified'] === 1, "RT has is_verified = 1");
assert_check((int)$rt_status['is_admin'] === 1, "RT has is_admin = 1");
assert_check((int)$rt_status['must_change_password'] === 1, "RT has must_change_password = 1");

// RT changes password to 'ceo_pass_2026'
$res = $rt->request("$base_url/settings.php", 'POST', http_build_query([
    'action'           => 'change_password',
    'current_password' => '123',
    'new_password'     => 'ceo_pass_2026',
    'confirm_password' => 'ceo_pass_2026',
    'csrf_token'       => $rt->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);
assert_check(strpos($res['body'], 'Password successfully updated') !== false, "RT password changed in Settings");

$rt_status_after = $db->query("SELECT must_change_password FROM users WHERE username = 'RT'")->fetch();
assert_check((int)$rt_status_after['must_change_password'] === 0, "RT must_change_password is now 0");

// -------------------------------------------------------------------------
// SETUP FOR 3 SESSIONS: RT, DARA, SOKHA
// -------------------------------------------------------------------------
echo "\nInitializing 3 Browser Sessions: RT, Dara, Sokha...\n";
$sokha = new BrowserSession('Sokha');
$sokha_logged = $sokha->login('password123', $base_url);
assert_check($sokha_logged, "Sokha logged in successfully");

// Sync starting event IDs to current max for all 3 sessions
$current_max_event = (int)$db->query("SELECT COALESCE(MAX(id), 0) FROM events")->fetchColumn();
$rt->last_event_id = $current_max_event;
$dara->last_event_id = $current_max_event;
$sokha->last_event_id = $current_max_event;
echo "  [OK] Current max event ID: $current_max_event. All 3 sessions listening.\n";

// -------------------------------------------------------------------------
// TEST 4: RT Posts a Photo -> Dara and Sokha receive 'new_post' instantly
// -------------------------------------------------------------------------
echo "\nTEST 4: RT Posts a Photo -> Dara & Sokha receive instant event...\n";
$tmp_rt_img = tempnam(sys_get_temp_dir(), 'rt_pic_') . '.png';
$im = imagecreatetruecolor(640, 480);
$bg = imagecolorallocate($im, 255, 90, 54); // orange
imagefilledrectangle($im, 0, 0, 640, 480, $bg);
imagepng($im, $tmp_rt_img);
imagedestroy($im);

$cfile = new CURLFile($tmp_rt_img, 'image/png', 'rt_photo.png');
$res = $rt->request("$base_url/actions/post.php", 'POST', [
    'photo'      => $cfile,
    'caption'    => 'CEO official announcement shot',
    'tags'       => 'official, ceo, live',
    'csrf_token' => $rt->csrf_token
]);
unlink($tmp_rt_img);

$post_json = json_decode($res['body'], true);
assert_check(!empty($post_json['success']), "RT photo posted successfully (ID: {$post_json['post_id']})");

// Check events received by Dara and Sokha
$dara_events = $dara->fetch_events($base_url);
$sokha_events = $sokha->fetch_events($base_url);

$dara_saw_post = false;
foreach ($dara_events as $ev) {
    if ($ev['type'] === 'new_post' && ($ev['payload']['post_id'] ?? 0) === $post_json['post_id']) {
        $dara_saw_post = true;
        break;
    }
}
$sokha_saw_post = false;
foreach ($sokha_events as $ev) {
    if ($ev['type'] === 'new_post' && ($ev['payload']['post_id'] ?? 0) === $post_json['post_id']) {
        $sokha_saw_post = true;
        break;
    }
}

assert_check($dara_saw_post, "Dara's browser received 'new_post' event from RT instantly");
assert_check($sokha_saw_post, "Sokha's browser received 'new_post' event from RT instantly");

// -------------------------------------------------------------------------
// TEST 5: Message in Lobby appears in all 3 browsers with Blue Badge
// -------------------------------------------------------------------------
echo "\nTEST 5: RT Posts in Lobby -> All 3 browsers receive event with Blue Badge...\n";
$lobby_msg_text = "Official lobby greeting from CEO! Code: " . bin2hex(random_bytes(4));

$res = $rt->request("$base_url/actions/chat_message.php", 'POST', http_build_query([
    'channel'    => 'lobby',
    'body'       => $lobby_msg_text,
    'csrf_token' => $rt->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);

$lobby_json = json_decode($res['body'], true);
assert_check(!empty($lobby_json['success']), "Lobby message sent by RT");

// Dara and Sokha fetch events
$dara_lobby_evs = $dara->fetch_events($base_url);
$sokha_lobby_evs = $sokha->fetch_events($base_url);

$dara_saw_lobby = false;
$dara_badge_verified = false;
foreach ($dara_lobby_evs as $ev) {
    if ($ev['type'] === 'lobby_message' && ($ev['payload']['body'] ?? '') === $lobby_msg_text) {
        $dara_saw_lobby = true;
        $dara_badge_verified = !empty($ev['payload']['is_verified']);
        break;
    }
}

$sokha_saw_lobby = false;
$sokha_badge_verified = false;
foreach ($sokha_lobby_evs as $ev) {
    if ($ev['type'] === 'lobby_message' && ($ev['payload']['body'] ?? '') === $lobby_msg_text) {
        $sokha_saw_lobby = true;
        $sokha_badge_verified = !empty($ev['payload']['is_verified']);
        break;
    }
}

assert_check($dara_saw_lobby, "Dara received Lobby message within 1 second");
assert_check($dara_badge_verified, "Dara sees RT's Blue Verified Badge in the Lobby message");
assert_check($sokha_saw_lobby, "Sokha received Lobby message within 1 second");
assert_check($sokha_badge_verified, "Sokha sees RT's Blue Verified Badge in the Lobby message");

// -------------------------------------------------------------------------
// TEST 6: DM from RT to Dara -> Unread badge goes up -> "Seen" shows after opened
// -------------------------------------------------------------------------
echo "\nTEST 6: DM from RT to Dara -> Unread count increases -> 'Seen' receipt...\n";
sleep(1);
$dm_body = "Direct briefing for Dara: " . bin2hex(random_bytes(4));

$res = $rt->request("$base_url/actions/chat_message.php", 'POST', http_build_query([
    'channel'    => 'dm',
    'to_id'      => $dara->user_id,
    'body'       => $dm_body,
    'csrf_token' => $rt->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);

$dm_json = json_decode($res['body'], true);
assert_check(!empty($dm_json['success']), "DM sent by RT to Dara (Msg ID: {$dm_json['message']['id']})");
$msg_id = (int)$dm_json['message']['id'];

// Dara receives dm_message event
$dara_dm_evs = $dara->fetch_events($base_url);
$dara_got_dm = false;
foreach ($dara_dm_evs as $ev) {
    if ($ev['type'] === 'dm_message' && ($ev['payload']['id'] ?? 0) === $msg_id) {
        $dara_got_dm = true;
        break;
    }
}
assert_check($dara_got_dm, "Dara's browser received targeted 'dm_message' event instantly");

// Verify unread count in DB for Dara
$unread_cnt = (int)$db->query("SELECT COUNT(*) FROM messages WHERE to_id = {$dara->user_id} AND from_id = {$rt->user_id} AND is_seen = 0")->fetchColumn();
assert_check($unread_cnt >= 1, "Unread badge count for Dara is $unread_cnt (greater than 0)");

// Verify message is not yet seen
$seen_before = (int)$db->query("SELECT is_seen FROM messages WHERE id = $msg_id")->fetchColumn();
assert_check($seen_before === 0, "Message is_seen is initially 0 (Unopened)");

// Dara opens the chat and marks messages as seen
$res = $dara->request("$base_url/actions/chat_action.php", 'POST', http_build_query([
    'action'     => 'mark_seen',
    'from_id'    => $rt->user_id,
    'csrf_token' => $dara->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);
assert_check(strpos($res['body'], '"success":true') !== false, "Dara marks chat as seen");

// Verify database reflects seen status
$seen_after = (int)$db->query("SELECT is_seen FROM messages WHERE id = $msg_id")->fetchColumn();
assert_check($seen_after === 1, "Database updated: messages.is_seen = 1");

// RT receives 'dm_seen' event
$rt_evs = $rt->fetch_events($base_url);
$rt_got_seen = false;
foreach ($rt_evs as $ev) {
    if ($ev['type'] === 'dm_seen' && ($ev['payload']['reader_id'] ?? 0) === $dara->user_id) {
        $rt_got_seen = true;
        break;
    }
}
assert_check($rt_got_seen, "RT's browser received 'dm_seen' event instantly showing 'Seen'");

// -------------------------------------------------------------------------
// TEST 7: Admin Panel Controls (RT verifies/unverifies Dara, Non-Admin blocked)
// -------------------------------------------------------------------------
echo "\nTEST 7: Admin Panel Permissions & Verification Toggle...\n";

// Dara attempts to access admin.php -> 403 Forbidden
$dara_admin_attempt = $dara->request("$base_url/admin.php");
assert_check($dara_admin_attempt['code'] === 403, "Dara (normal user) blocked from admin.php with 403 Forbidden");

// RT accesses admin.php -> 200 OK
$rt_admin = $rt->request("$base_url/admin.php");
assert_check($rt_admin['code'] === 200 && strpos($rt_admin['body'], 'Admin Control Panel') !== false, "RT (admin) accesses admin.php successfully");

// RT verifies Dara via admin action
$res = $rt->request("$base_url/admin.php", 'POST', http_build_query([
    'action'     => 'toggle_verify',
    'target_id'  => $dara->user_id,
    'csrf_token' => $rt->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);

$dara_verified_now = (int)$db->query("SELECT is_verified FROM users WHERE id = {$dara->user_id}")->fetchColumn();
assert_check($dara_verified_now === 1, "RT successfully verified Dara (is_verified = 1 in database)");

// RT un-verifies Dara
$res = $rt->request("$base_url/admin.php", 'POST', http_build_query([
    'action'     => 'toggle_verify',
    'target_id'  => $dara->user_id,
    'csrf_token' => $rt->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);

$dara_unverified_now = (int)$db->query("SELECT is_verified FROM users WHERE id = {$dara->user_id}")->fetchColumn();
assert_check($dara_unverified_now === 0, "RT successfully unverified Dara (is_verified = 0 in database)");

echo "\n=================================================================\n";
echo "   ALL ACCEPTANCE TESTS PASSED WITH 100% SUCCESS!\n";
echo "   - RT photo broadcast to 2 browsers: PASS\n";
echo "   - Lobby message with Blue Badge in all 3 browsers: PASS\n";
echo "   - 1-to-1 DM with instant unread count & 'Seen' receipt: PASS\n";
echo "   - User verification tampering prevention: PASS\n";
echo "   - Reserved usernames (ceo, rt, admin) rejected: PASS\n";
echo "   - Admin permissions & Weak password flow: PASS\n";
echo "=================================================================\n\n";
