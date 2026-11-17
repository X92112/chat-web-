<?php
// Test multi-user LAN interaction using the machine's local IP address
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/helpers.php';

$lan_ip = '172.20.0.232';
$lan_base = "http://$lan_ip/snapwall";

echo "=================================================================\n";
echo "   NEXLY: TESTING LAN MULTI-USER REAL-TIME CHAT ($lan_ip)         \n";
echo "=================================================================\n\n";

class LanClient {
    public string $username;
    public string $cookie_file;
    public string $csrf_token = '';
    public int $last_event_id = 0;
    public int $user_id = 0;

    public function __construct(string $username) {
        $this->username = $username;
        $this->cookie_file = tempnam(sys_get_temp_dir(), 'lan_sess_' . $username . '_');
    }

    public function __destruct() {
        if (file_exists($this->cookie_file)) {
            @unlink($this->cookie_file);
        }
    }

    public function req(string $url, string $method = 'GET', $data = null, array $headers = []): array {
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

    public function login(string $password, string $base): bool {
        $this->req("$base/login.php");
        $res = $this->req("$base/login.php", 'POST', http_build_query([
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

    public function poll(string $base): array {
        $res = $this->req("$base/actions/poll.php?last_id={$this->last_event_id}");
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

$devA = new LanClient('Sokha');
$devB = new LanClient('Dara');

echo "1. Device A connecting via LAN IP ($lan_base)...\n";
$okA = $devA->login('password123', $lan_base);
if ($okA) {
    echo "  [PASS] Device A (Sokha) authenticated via LAN\n";
} else {
    echo "  [FAIL] Device A authentication failed\n";
    exit(1);
}

echo "2. Device B connecting via LAN IP ($lan_base)...\n";
$okB = $devB->login('password123', $lan_base);
if ($okB) {
    echo "  [PASS] Device B (Dara) authenticated via LAN\n";
} else {
    echo "  [FAIL] Device B authentication failed\n";
    exit(1);
}

// Sync event baselines
$devA->poll($lan_base);
$devB->poll($lan_base);

// 3. Device A sends a message in the Lobby on LAN
echo "3. Device A sends Lobby message on LAN...\n";
sleep(1);
$unique_lobby_msg = "Hey from Device A on LAN! [Code: " . bin2hex(random_bytes(4)) . "]";
$res_lobby = $devA->req("$lan_base/actions/chat_message.php", 'POST', [
    'channel'    => 'lobby',
    'csrf_token' => $devA->csrf_token,
    'body'       => $unique_lobby_msg
]);
$j_lobby = json_decode($res_lobby['body'], true);
if (!empty($j_lobby['success'])) {
    echo "  [PASS] Device A message posted to Lobby\n";
} else {
    echo "  [FAIL] Device A could not post to Lobby: " . $res_lobby['body'] . "\n";
    exit(1);
}

// 4. Device B polls/receives Device A's Lobby message on LAN
echo "4. Device B receives Device A's message on LAN...\n";
$eventsB = $devB->poll($lan_base);
$found_lobby = false;
foreach ($eventsB as $ev) {
    if ($ev['type'] === 'lobby_message' && ($ev['payload']['body'] ?? '') === $unique_lobby_msg) {
        $found_lobby = true;
        break;
    }
}
if ($found_lobby) {
    echo "  [PASS] Device B received Device A's Lobby message in real-time!\n";
} else {
    echo "  [FAIL] Device B did not receive message\n";
    exit(1);
}

// 5. Device B sends a direct message to Device A on LAN
echo "5. Device B sends 1-to-1 DM to Device A on LAN...\n";
sleep(1);
$unique_dm_msg = "Direct reply from Device B to Device A on Wi-Fi [Code: " . bin2hex(random_bytes(4)) . "]";
$res_dm = $devB->req("$lan_base/actions/chat_message.php", 'POST', [
    'channel'    => 'dm',
    'to_id'      => $devA->user_id,
    'csrf_token' => $devB->csrf_token,
    'body'       => $unique_dm_msg
]);
$j_dm = json_decode($res_dm['body'], true);
if (!empty($j_dm['success'])) {
    echo "  [PASS] Device B direct message sent to Device A\n";
} else {
    echo "  [FAIL] Device B DM failed: " . $res_dm['body'] . "\n";
    exit(1);
}

// 6. Device A receives Device B's DM on LAN
echo "6. Device A receives Device B's direct message on LAN...\n";
$eventsA = $devA->poll($lan_base);
$found_dm = false;
foreach ($eventsA as $ev) {
    if ($ev['type'] === 'dm_message' && ($ev['payload']['body'] ?? '') === $unique_dm_msg) {
        $found_dm = true;
        break;
    }
}
if ($found_dm) {
    echo "  [PASS] Device A received Device B's direct message in real-time!\n";
} else {
    echo "  [FAIL] Device A did not receive DM\n";
    exit(1);
}

echo "\n=================================================================\n";
echo "   LAN REAL-TIME CHAT FULLY OPERATIONAL OVER $lan_ip !\n";
echo "=================================================================\n";
