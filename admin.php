<?php
// Snapwall / Nexly Admin Dashboard (Admins Only)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$current_user = require_admin();
$page_title = 'Admin Panel — Nexly';

$db = get_db();
$msg = '';
$err = '';

// Handle Admin Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_or_fail(false);
    $action = $_POST['action'] ?? '';
    $target_id = (int)($_POST['target_id'] ?? 0);

    if ($action === 'toggle_verify' && $target_id > 0) {
        $stmt = $db->prepare("SELECT id, username, is_verified FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $target_id]);
        $target = $stmt->fetch();
        if ($target) {
            $new_val = $target['is_verified'] ? 0 : 1;
            $db->prepare("UPDATE users SET is_verified = :v WHERE id = :id")->execute(['v' => $new_val, 'id' => $target_id]);
            $msg = "User @" . $target['username'] . ($new_val ? " is now VERIFIED." : " verification REMOVED.");
        }
    } elseif ($action === 'toggle_ban' && $target_id > 0) {
        if ($target_id === (int)$current_user['id']) {
            $err = "You cannot ban your own account!";
        } else {
            $stmt = $db->prepare("SELECT id, username, is_banned FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $target_id]);
            $target = $stmt->fetch();
            if ($target) {
                $new_val = $target['is_banned'] ? 0 : 1;
                $db->prepare("UPDATE users SET is_banned = :b WHERE id = :id")->execute(['b' => $new_val, 'id' => $target_id]);
                $msg = "User @" . $target['username'] . ($new_val ? " has been BANNED." : " has been UNBANNED.");
            }
        }
    }
}

// Fetch all users
$users = $db->query("
    SELECT id, username, email, bio, is_verified, is_admin, is_banned, last_seen, created_at,
           ((SELECT COUNT(*) FROM lobby_messages WHERE user_id = users.id) +
            (SELECT COUNT(*) FROM messages WHERE from_id = users.id)) AS msg_count
    FROM users 
    ORDER BY is_admin DESC, is_verified DESC, id ASC
")->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar_left.php';
?>

<main class="feed-column">
  <div class="card" style="padding:24px 20px; margin-bottom:20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; border-bottom:var(--border-width) solid var(--ink); padding-bottom:12px;">
      <h1 style="font-size:22px; font-weight:800;">
        Admin Control Panel
      </h1>
      <span style="font-size:12px; font-weight:800; background:var(--yellow); padding:3px 8px; border:1.5px solid var(--ink); border-radius:4px;">
        CEO / ADMIN
      </span>
    </div>

    <p style="color:var(--muted); font-size:13.5px; margin-bottom:16px;">
      Manage users, verification badges, and platform moderation.
    </p>

    <?php if ($msg): ?>
      <div style="background:rgba(16, 185, 129, 0.14); border:1px solid rgba(16, 185, 129, 0.4); border-radius:4px; padding:8px 14px; font-weight:600; color:#34D399; margin-bottom:14px; font-size:13.5px;">
        <?= e($msg) ?>
      </div>
    <?php endif; ?>

    <?php if ($err): ?>
      <div class="alert-box" style="margin-bottom:14px;"><?= e($err) ?></div>
    <?php endif; ?>

    <!-- User Table -->
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; font-size:13.5px;">
        <thead>
          <tr style="border-bottom:1px solid var(--border-strong); text-align:left; color:var(--muted); font-size:11.5px; text-transform:uppercase; letter-spacing:0.06em;">
            <th style="padding:8px 6px;">User</th>
            <th style="padding:8px 6px;">Email</th>
            <th style="padding:8px 6px;">Role / Status</th>
            <th style="padding:8px 6px; text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:10px 6px;">
                <div style="display:flex; align-items:center; gap:8px;">
                  <?= render_avatar($u['username'], 30) ?>
                  <div>
                    <a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($u['username']) ?>" style="font-weight:600;">
                      <?= e($u['username']) ?>
                    </a>
                    <?= verified_badge($u['is_verified']) ?>
                    <div style="font-size:11px; color:var(--muted);"><?= (int)$u['msg_count'] ?> messages</div>
                  </div>
                </div>
              </td>
              <td style="padding:10px 6px; color:var(--muted); font-size:12.5px;">
                <?= e($u['email']) ?>
              </td>
              <td style="padding:10px 6px;">
                <?php if (!empty($u['is_admin'])): ?>
                  <span class="role-badge-admin">Admin</span>
                <?php endif; ?>
                <?php if (!empty($u['is_verified'])): ?>
                  <span style="background:rgba(2, 41, 213, 0.25); border:1px solid #4D65FF; color:#ECF0F8; border-radius:2px; padding:2px 6px; font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.04em;">Verified</span>
                <?php endif; ?>
                <?php if (!empty($u['is_banned'])): ?>
                  <span style="background:rgba(239, 68, 68, 0.2); border:1px solid #EF4444; color:#FCA5A5; border-radius:2px; padding:2px 6px; font-size:10.5px; font-weight:600; text-transform:uppercase;">Banned</span>
                <?php else: ?>
                  <span style="color:var(--green); font-size:11px; font-weight:600;">Active</span>
                <?php endif; ?>
              </td>
              <td style="padding:10px 6px; text-align:right;">
                <div style="display:inline-flex; gap:6px; align-items:center;">
                  <!-- Verify Toggle -->
                  <form method="POST" action="<?= BASE_URL ?>/admin.php" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="toggle_verify">
                    <input type="hidden" name="target_id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-sm <?= empty($u['is_verified']) ? 'btn-yellow' : '' ?>" style="padding:2px 8px; font-size:12px; min-height:28px;">
                      <?= empty($u['is_verified']) ? 'Verify' : 'Unverify' ?>
                    </button>
                  </form>

                  <!-- Ban Toggle -->
                  <?php if ((int)$u['id'] !== (int)$current_user['id']): ?>
                    <form method="POST" action="<?= BASE_URL ?>/admin.php" style="display:inline;" onsubmit="return confirm('Change ban status for <?= e($u['username']) ?>?');">
                      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                      <input type="hidden" name="action" value="toggle_ban">
                      <input type="hidden" name="target_id" value="<?= (int)$u['id'] ?>">
                      <button type="submit" class="btn btn-sm" style="padding:2px 8px; font-size:12px; min-height:28px; <?= !empty($u['is_banned']) ? 'background:var(--blue); color:#fff;' : 'background:rgba(239, 68, 68, 0.18); color:#FCA5A5; border-color:rgba(239, 68, 68, 0.4);' ?>">
                        <?= !empty($u['is_banned']) ? 'Unban' : 'Ban' ?>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</main>

<?php
include __DIR__ . '/includes/sidebar_right.php';
include __DIR__ . '/includes/footer.php';
?>
