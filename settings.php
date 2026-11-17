<?php
// Snapwall / Nexly Account & Profile Settings Page
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$current_user = require_login();
$page_title = 'Settings — Nexly';

$error = '';
$success = '';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_or_fail(false);
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $curr_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $current_user['id']]);
        $row = $stmt->fetch();

        if (!password_verify($curr_pass, $row['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_pass) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } elseif ($new_pass !== $confirm_pass) {
            $error = 'New passwords do not match.';
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $u_stmt = $db->prepare("
                UPDATE users 
                SET password_hash = :hash, must_change_password = 0 
                WHERE id = :id
            ");
            $u_stmt->execute(['hash' => $new_hash, 'id' => $current_user['id']]);
            $current_user['must_change_password'] = 0;
            $success = 'Password successfully updated! Your account is fully secured.';
        }
    } elseif ($action === 'update_profile') {
        $display_name = trim($_POST['display_name'] ?? '');
        $bio          = trim($_POST['bio'] ?? '');
        $status_mood  = trim($_POST['status_mood'] ?? '');
        $location     = trim($_POST['location'] ?? '');
        $website      = trim($_POST['website'] ?? '');

        if (mb_strlen($display_name) > 60) $display_name = mb_substr($display_name, 0, 60);
        if (mb_strlen($bio) > 300)          $bio = mb_substr($bio, 0, 300);
        if (mb_strlen($status_mood) > 80)   $status_mood = mb_substr($status_mood, 0, 80);
        if (mb_strlen($location) > 80)      $location = mb_substr($location, 0, 80);
        if (mb_strlen($website) > 180)      $website = mb_substr($website, 0, 180);

        if ($website !== '' && !preg_match('#^https?://#i', $website)) {
            $website = 'https://' . $website;
        }

        $avatar_filename = $current_user['avatar'] ?? null;
        $banner_filename = $current_user['banner'] ?? null;

        try {
            if (!empty($_POST['remove_avatar'])) $avatar_filename = null;
            if (!empty($_POST['remove_banner'])) $banner_filename = null;

            if (!empty($_FILES['avatar_photo']['tmp_name']) && $_FILES['avatar_photo']['error'] === UPLOAD_ERR_OK) {
                $saved = process_photo_upload($_FILES['avatar_photo']);
                $avatar_filename = $saved['thumb'] ?: $saved['filename'];
            }
            if (!empty($_FILES['banner_photo']['tmp_name']) && $_FILES['banner_photo']['error'] === UPLOAD_ERR_OK) {
                $saved_banner = process_photo_upload($_FILES['banner_photo']);
                $banner_filename = $saved_banner['filename'];
            }

            $u_stmt = $db->prepare("
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
            $u_stmt->execute([
                'display_name' => $display_name !== '' ? $display_name : null,
                'bio'          => $bio !== '' ? $bio : null,
                'status_mood'  => $status_mood !== '' ? $status_mood : null,
                'location'     => $location !== '' ? $location : null,
                'website'      => $website !== '' ? $website : null,
                'avatar'       => $avatar_filename,
                'banner'       => $banner_filename,
                'id'           => $current_user['id']
            ]);

            set_user_avatar_cache($current_user['username'], $avatar_filename);
            $current_user = current_user(true);
            $success = 'Your profile has been updated!';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$pastel_bg = get_avatar_color($current_user['username']);
$banner_url = !empty($current_user['banner']) ? (UPLOAD_URL . '/' . rawurlencode($current_user['banner'])) : null;

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar_left.php';
?>

<main class="feed-column">
  <div class="card" style="padding:28px 24px; margin-bottom:20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:6px;">
      <h1 style="font-size:24px; font-weight:800;">
        Profile & Account Settings
      </h1>
      <a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($current_user['username']) ?>" class="btn btn-sm">
        View my profile
      </a>
    </div>
    <p style="color:var(--muted); font-size:14px; margin-bottom:20px;">
      customize your avatar photo, cover banner, profile details, and account security.
    </p>

    <?php if ($error): ?>
      <div class="alert-box" style="margin-bottom:16px;"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div style="background:rgba(16, 185, 129, 0.14); border:1px solid rgba(16, 185, 129, 0.4); border-radius:4px; padding:10px 14px; font-weight:600; color:#34D399; margin-bottom:16px;">
        <?= e($success) ?>
      </div>
    <?php endif; ?>

    <!-- Profile Customization Form -->
    <div style="margin-bottom:28px; padding-bottom:24px; border-bottom:1px solid var(--border-subtle);">
      <h2 style="font-size:18px; font-weight:800; margin-bottom:14px;">Public Profile & Photos</h2>

      <form method="POST" action="<?= BASE_URL ?>/settings.php" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:14px;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="update_profile">

        <div class="edit-profile-media-section">
          <div class="edit-banner-preview" id="edit-banner-preview" style="<?= $banner_url ? 'background-image:url(\'' . e($banner_url) . '\'); background-size:cover; background-position:center;' : 'background-color:' . $pastel_bg . ';' ?>">
            <label for="settings-banner-input" class="btn btn-sm btn-yellow" style="cursor:pointer;">
              Change cover banner
            </label>
            <input type="file" id="settings-banner-input" name="banner_photo" accept="image/jpeg,image/png,image/webp" class="visually-hidden" onchange="previewProfileBanner(this)">
          </div>

          <div class="edit-avatar-row">
            <div class="edit-avatar-circle-wrap" id="edit-avatar-preview-wrap">
              <?= render_avatar($current_user['username'], 72, '', $current_user['avatar'] ?? null) ?>
            </div>
            <div style="display:flex; flex-direction:column; gap:6px;">
              <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <label for="settings-avatar-input" class="btn btn-sm btn-orange" style="cursor:pointer;">
                  Upload profile photo
                </label>
                <input type="file" id="settings-avatar-input" name="avatar_photo" accept="image/jpeg,image/png,image/webp" class="visually-hidden" onchange="previewProfileAvatar(this)">
                <?php if (!empty($current_user['avatar'])): ?>
                  <label style="display:inline-flex; align-items:center; gap:5px; font-size:12.5px; font-weight:700; cursor:pointer;">
                    <input type="checkbox" name="remove_avatar" value="1"> Reset to initials
                  </label>
                <?php endif; ?>
                <?php if (!empty($current_user['banner'])): ?>
                  <label style="display:inline-flex; align-items:center; gap:5px; font-size:12.5px; font-weight:700; cursor:pointer;">
                    <input type="checkbox" name="remove_banner" value="1"> Reset cover
                  </label>
                <?php endif; ?>
              </div>
              <span style="font-size:12px; color:var(--muted);">JPG, PNG or WebP up to 5 MB.</span>
            </div>
          </div>
        </div>

        <div class="edit-profile-grid">
          <div class="form-group">
            <label class="form-label" for="set_display_name">Display name</label>
            <input type="text" id="set_display_name" name="display_name" class="form-control" maxlength="60"
                   value="<?= e($current_user['display_name'] ?? '') ?>" placeholder="<?= e($current_user['username']) ?>" autocomplete="name">
          </div>

          <div class="form-group">
            <label class="form-label" for="set_status_mood">Short status note</label>
            <input type="text" id="set_status_mood" name="status_mood" class="form-control" maxlength="80"
                   value="<?= e($current_user['status_mood'] ?? '') ?>" placeholder="shooting street film">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="bio_input">Bio (up to 300 chars)</label>
          <textarea id="bio_input" name="bio" class="form-control" rows="3" maxlength="300" placeholder="A little about yourself..."><?= e($current_user['bio'] ?? '') ?></textarea>
        </div>

        <div class="edit-profile-grid">
          <div class="form-group">
            <label class="form-label" for="set_location">Location</label>
            <input type="text" id="set_location" name="location" class="form-control" maxlength="80"
                   value="<?= e($current_user['location'] ?? '') ?>" placeholder="Phnom Penh">
          </div>

          <div class="form-group">
            <label class="form-label" for="set_website">Website / Portfolio link</label>
            <input type="text" id="set_website" name="website" class="form-control" maxlength="180"
                   value="<?= e($current_user['website'] ?? '') ?>" placeholder="https://example.com"
                   autocorrect="off" autocapitalize="none" spellcheck="false">
          </div>
        </div>

        <div>
          <button type="submit" class="btn btn-yellow">Save profile changes</button>
        </div>
      </form>
    </div>

    <!-- Password Change Section -->
    <div>
      <h2 style="font-size:18px; font-weight:800; margin-bottom:12px;">Change Password</h2>

      <?php if (!empty($current_user['must_change_password'])): ?>
        <div style="background:rgba(2, 41, 213, 0.2); border:1px solid var(--blue-bright); border-radius:4px; padding:10px 14px; font-weight:500; color:var(--text-ice); margin-bottom:16px; font-size:13.5px;">
          Please set a new password (at least 6 characters) to secure your studio account.
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>/settings.php" style="display:flex; flex-direction:column; gap:12px; max-width:420px;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="change_password">

        <div class="form-group">
          <label class="form-label" for="curr_pass">Current password</label>
          <input type="password" id="curr_pass" name="current_password" class="form-control" required autocomplete="current-password">
        </div>

        <div class="form-group">
          <label class="form-label" for="new_pass">New password (min 6 chars)</label>
          <input type="password" id="new_pass" name="new_password" class="form-control" required autocomplete="new-password">
        </div>

        <div class="form-group">
          <label class="form-label" for="conf_pass">Confirm new password</label>
          <input type="password" id="conf_pass" name="confirm_password" class="form-control" required autocomplete="new-password">
        </div>

        <div style="margin-top:6px;">
          <button type="submit" class="btn btn-orange">Update password</button>
        </div>
      </form>
    </div>

  </div>
</main>

<?php
include __DIR__ . '/includes/sidebar_right.php';
include __DIR__ . '/includes/footer.php';
?>
