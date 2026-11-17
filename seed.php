<?php
// Snapwall / Nexly Database Seeder
// Run once from the browser or CLI, then delete this file.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/helpers.php';

$db = get_db();

echo "Seeding Snapwall / Nexly accounts...\n";

// 1. Seed or Upsert CEO / Admin account: "RT"
$rt_username = 'RT';
$rt_email = 'rt@snapwall.local';
$rt_password_hash = password_hash('123', PASSWORD_DEFAULT);
$rt_bio = 'CEO of snapwall';

$rt_stmt = $db->prepare("SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1");
$rt_stmt->execute(['u' => $rt_username, 'e' => $rt_email]);
$rt_existing = $rt_stmt->fetch();

if ($rt_existing) {
    $db->prepare("
        UPDATE users 
        SET username = :u, email = :e, password_hash = :p, bio = :b,
            is_verified = 1, is_admin = 1, must_change_password = 1, last_seen = NOW()
        WHERE id = :id
    ")->execute([
        'u'  => $rt_username,
        'e'  => $rt_email,
        'p'  => $rt_password_hash,
        'b'  => $rt_bio,
        'id' => $rt_existing['id']
    ]);
    $rt_id = (int)$rt_existing['id'];
} else {
    $db->prepare("
        INSERT INTO users (username, email, password_hash, bio, is_verified, is_admin, must_change_password, last_seen, created_at)
        VALUES (:u, :e, :p, :b, 1, 1, 1, NOW(), NOW())
    ")->execute([
        'u' => $rt_username,
        'e' => $rt_email,
        'p' => $rt_password_hash,
        'b' => $rt_bio
    ]);
    $rt_id = (int)$db->lastInsertId();
}
echo "  [OK] User RT seeded (id: $rt_id, verified=1, admin=1, must_change_password=1)\n";

// 2. Ensure normal users exist for multi-user tests
$normal_users = [
    ['username' => 'Dara', 'email' => 'dara@snapwall.local', 'bio' => 'city architecture and shadow hunter', 'verified' => 0],
    ['username' => 'Sokha', 'email' => 'sokha@snapwall.local', 'bio' => 'documenting morning light and quiet corners', 'verified' => 0],
    ['username' => 'Lina', 'email' => 'lina@snapwall.local', 'bio' => 'street food enthusiast & film shooter', 'verified' => 0]
];

$normal_pass_hash = password_hash('password123', PASSWORD_DEFAULT);

foreach ($normal_users as $u) {
    $chk = $db->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
    $chk->execute(['u' => $u['username']]);
    $exists = $chk->fetch();
    if (!$exists) {
        $db->prepare("
            INSERT INTO users (username, email, password_hash, bio, is_verified, is_admin, must_change_password, last_seen, created_at)
            VALUES (:u, :e, :p, :b, :v, 0, 0, NOW(), NOW())
        ")->execute([
            'u' => $u['username'],
            'e' => $u['email'],
            'p' => $normal_pass_hash,
            'b' => $u['bio'],
            'v' => $u['verified']
        ]);
        echo "  [OK] Created normal user: {$u['username']}\n";
    }
}

// 3. Ensure sample post exists for RT and Dara
$dara_id = (int)$db->query("SELECT id FROM users WHERE username = 'Dara'")->fetchColumn();

// Follow relationship: Dara follows RT, Sokha follows RT, RT follows Dara
$f_stmt = $db->prepare("INSERT IGNORE INTO follows (follower_id, following_id, created_at) VALUES (:f, :t, NOW())");
if ($dara_id) {
    $f_stmt->execute(['f' => $dara_id, 't' => $rt_id]);
    $f_stmt->execute(['f' => $rt_id, 't' => $dara_id]);
}
$sokha_id = (int)$db->query("SELECT id FROM users WHERE username = 'Sokha'")->fetchColumn();
if ($sokha_id) {
    $f_stmt->execute(['f' => $sokha_id, 't' => $rt_id]);
    $f_stmt->execute(['f' => $dara_id, 't' => $sokha_id]);
}

// Ensure at least one welcome message in lobby
$lobby_cnt = (int)$db->query("SELECT COUNT(*) FROM lobby_messages")->fetchColumn();
if ($lobby_cnt === 0) {
    $db->prepare("INSERT INTO lobby_messages (user_id, body, created_at) VALUES (:u, :b, NOW())")->execute([
        'u' => $rt_id,
        'b' => 'welcome to the snapwall lobby! Say hi to everyone online.'
    ]);
    echo "  [OK] Seeded initial lobby message\n";
}

echo "Seeding completed successfully!\n";
