<?php
// Snapwall Comprehensive Automated Test Suite
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

echo "=== STARTING SNAPWALL FULL END-TO-END TEST SUITE ===\n\n";

$base_url = 'http://localhost/snapwall';

class TestClient {
    private string $cookie_file;
    public string $csrf_token = '';
    public string $username = '';

    public function __construct(string $name) {
        $this->username = $name;
        $this->cookie_file = tempnam(sys_get_temp_dir(), 'snap_cookie_' . $name . '_');
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

        // Try extracting CSRF token if present
        if (preg_match('/<meta name="csrf-token" content="([a-f0-9]{64})"/i', $body, $m)) {
            $this->csrf_token = $m[1];
        } elseif (preg_match('/name="csrf_token" value="([a-f0-9]{64})"/i', $body, $m)) {
            $this->csrf_token = $m[1];
        }

        return ['code' => $code, 'body' => $body];
    }
}

$clientA = new TestClient('Sokha');
$clientB = new TestClient('Dara');

function assert_true(bool $cond, string $message) {
    if ($cond) {
        echo "  [PASS] $message\n";
    } else {
        echo "  [FAIL] $message\n";
        exit(1);
    }
}

// TEST 1: Login Account A (Sokha)
echo "1. Testing Login with Account A (Sokha)...\n";
$res = $clientA->request("$base_url/login.php");
assert_true($res['code'] === 200, "Login page returned 200");
assert_true(!empty($clientA->csrf_token), "Extracted CSRF token for Sokha: " . substr($clientA->csrf_token, 0, 10) . "...");

$post_data = [
    'username'   => 'Sokha',
    'password'   => 'password123',
    'csrf_token' => $clientA->csrf_token
];
$res = $clientA->request("$base_url/login.php", 'POST', http_build_query($post_data), ['Content-Type: application/x-www-form-urlencoded']);
if (strpos($res['body'], 'What did you shoot today?') === false) {
    echo "DEBUG LOGIN RES BODY:\n" . substr($res['body'], 0, 800) . "\n";
}
assert_true(strpos($res['body'], 'What did you shoot today?') !== false, "Sokha logged in and redirected to home feed");

// TEST 2: Login Account B (Dara)
echo "\n2. Testing Login with Account B (Dara)...\n";
$res = $clientB->request("$base_url/login.php");
$post_data = [
    'username'   => 'Dara',
    'password'   => 'password123',
    'csrf_token' => $clientB->csrf_token
];
$res = $clientB->request("$base_url/login.php", 'POST', http_build_query($post_data), ['Content-Type: application/x-www-form-urlencoded']);
assert_true(strpos($res['body'], 'What did you shoot today?') !== false, "Dara logged in successfully");

// TEST 3: Post a photo by Dara
echo "\n3. Testing Photo Upload & GD Thumbnail Generation by Dara...\n";
// Create a temporary PNG image to upload
$tmp_test_png = tempnam(sys_get_temp_dir(), 'snap_test_') . '.png';
$im = imagecreatetruecolor(700, 500);
$c_bg = imagecolorallocate($im, 40, 120, 200);
imagefilledrectangle($im, 0, 0, 700, 500, $c_bg);
imagepng($im, $tmp_test_png);
imagedestroy($im);

$cfile = new CURLFile($tmp_test_png, 'image/png', 'test_upload.png');
$upload_post = [
    'photo'      => $cfile,
    'caption'    => 'testing live upload from test suite',
    'tags'       => 'test, automation, live',
    'csrf_token' => $clientB->csrf_token
];

$res = $clientB->request("$base_url/actions/post.php", 'POST', $upload_post);
unlink($tmp_test_png);

assert_true($res['code'] === 200, "Upload endpoint returned 200");
$json = json_decode($res['body'], true);
assert_true(!empty($json['success']), "Upload response reported success: true");
$uploaded_post_id = (int)$json['post_id'];
assert_true($uploaded_post_id > 0, "Received new post ID: $uploaded_post_id");

// Verify in DB that thumb and filename exist and are files on disk
$db = get_db();
$stmt = $db->prepare("SELECT filename, thumb FROM posts WHERE id = :id");
$stmt->execute(['id' => $uploaded_post_id]);
$post_row = $stmt->fetch();
assert_true(!empty($post_row['filename']) && !empty($post_row['thumb']), "Filename and thumb saved in database");

$orig_file_path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $post_row['filename'];
$thumb_file_path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $post_row['thumb'];
assert_true(file_exists($orig_file_path), "Original image exists on disk: " . basename($orig_file_path));
assert_true(file_exists($thumb_file_path), "GD thumbnail exists on disk: " . basename($thumb_file_path));

$thumb_info = getimagesize($thumb_file_path);
assert_true($thumb_info[0] <= 600 && $thumb_info[1] <= 600, "Thumbnail dimension is <= 600px (Actual: {$thumb_info[0]}x{$thumb_info[1]})");

// TEST 4: Explore Feed and Search
echo "\n4. Testing Explore Feed and Search...\n";
$res = $clientA->request("$base_url/explore.php?q=automation");
assert_true(strpos($res['body'], 'testing live upload from test suite') !== false, "Found post via Explore search for 'automation'");

// TEST 5: Like Post by Account A (Sokha likes Dara's post)
echo "\n5. Testing Like toggle by Sokha on Dara's post...\n";
$res = $clientA->request("$base_url/actions/like.php", 'POST', http_build_query([
    'post_id'    => $uploaded_post_id,
    'csrf_token' => $clientA->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);

$like_json = json_decode($res['body'], true);
assert_true($like_json['success'] === true && $like_json['liked'] === true, "Post successfully liked by Sokha");
assert_true($like_json['like_count'] === 1, "Like count is now 1");

// TEST 6: Comment on Post by Account A (Sokha comments on Dara's post)
echo "\n6. Testing Comments by Sokha on Dara's post...\n";
$res = $clientA->request("$base_url/actions/comment.php", 'POST', http_build_query([
    'post_id'    => $uploaded_post_id,
    'body'       => 'sweet shot Dara, crisp lighting!',
    'csrf_token' => $clientA->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);

$comm_json = json_decode($res['body'], true);
assert_true($comm_json['success'] === true, "Comment added successfully");
$comment_id = (int)$comm_json['comment']['id'];
assert_true($comment_id > 0, "New comment ID created: $comment_id");

// TEST 7: Notifications check for Account B (Dara)
echo "\n7. Testing Notifications for Dara (like + comment from Sokha)...\n";
$res = $clientB->request("$base_url/notifications.php");
assert_true(strpos($res['body'], 'Sokha') !== false, "Sokha appears in Dara's notifications");
assert_true(strpos($res['body'], 'liked your photo') !== false, "Notification 'liked your photo' present");
assert_true(strpos($res['body'], 'commented on your photo') !== false, "Notification 'commented on your photo' present");

// TEST 8: Direct Messaging between Dara and Sokha
echo "\n8. Testing 1-to-1 Direct Messages between Dara and Sokha...\n";
$sokha_id = (int)$db->query("SELECT id FROM users WHERE username = 'Sokha'")->fetchColumn();

$res = $clientB->request("$base_url/actions/message.php", 'POST', http_build_query([
    'to_id'      => $sokha_id,
    'body'       => 'Hey Sokha, thanks for checking out my photo!',
    'csrf_token' => $clientB->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);

$msg_json = json_decode($res['body'], true);
assert_true($msg_json['success'] === true, "Message sent successfully from Dara to Sokha");

// Sokha reads messages
$res = $clientA->request("$base_url/messages.php?u=Dara");
assert_true(strpos($res['body'], 'thanks for checking out my photo!') !== false, "Sokha sees message from Dara in thread");

// TEST 9: Follow / Unfollow
echo "\n9. Testing Follow / Unfollow...\n";
$dara_id = (int)$db->query("SELECT id FROM users WHERE username = 'Dara'")->fetchColumn();

// Check if Sokha follows Dara
$res = $clientA->request("$base_url/actions/follow.php", 'POST', http_build_query([
    'target_id'  => $dara_id,
    'csrf_token' => $clientA->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);

$f_json = json_decode($res['body'], true);
assert_true($f_json['success'] === true, "Follow toggle succeeded");

// TEST 10: Delete Comment by Sokha
echo "\n10. Testing Delete comment...\n";
$res = $clientA->request("$base_url/actions/delete.php", 'POST', http_build_query([
    'type'       => 'comment',
    'id'         => $comment_id,
    'csrf_token' => $clientA->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);

$del_json = json_decode($res['body'], true);
assert_true($del_json['success'] === true, "Comment deleted successfully");

// TEST 11: Delete Post by Dara
echo "\n11. Testing Delete post by Dara...\n";
$res = $clientB->request("$base_url/actions/delete.php", 'POST', http_build_query([
    'type'       => 'post',
    'id'         => $uploaded_post_id,
    'csrf_token' => $clientB->csrf_token
]), ['Content-Type: application/x-www-form-urlencoded']);

$del_p_json = json_decode($res['body'], true);
assert_true($del_p_json['success'] === true, "Post deleted successfully");
assert_true(!file_exists($orig_file_path), "Original image was cleaned up from disk");
assert_true(!file_exists($thumb_file_path), "Thumbnail image was cleaned up from disk");

// TEST 12: Real-time Fallback Polling / Heartbeat
echo "\n12. Testing Fallback Polling & Heartbeat...\n";
$res = $clientA->request("$base_url/actions/poll.php?heartbeat=1");
$poll_json = json_decode($res['body'], true);
assert_true($poll_json['success'] === true, "Heartbeat poll returned success");
assert_true($poll_json['online_count'] >= 2, "Online count includes active accounts ({$poll_json['online_count']})");

// TEST 13: SSE Endpoint headers and handshake
echo "\n13. Testing SSE stream endpoint headers...\n";
$ch = curl_init("$base_url/stream.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, tempnam(sys_get_temp_dir(), 'dummy'));
$res_headers = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
assert_true($http_code === 401, "Unauthenticated request to stream.php returns 401 as required");

echo "\n=== ALL 13 CORE SNAPWALL FEATURE TESTS PASSED WITH 100% SUCCESS! ===\n\n";
