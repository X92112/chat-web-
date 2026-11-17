<?php
// Nexly X — Member Chat Profile & Direct Messaging Card
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$current_user = current_user();
$username = trim($_GET['u'] ?? '');

if ($username === '') {
    if ($current_user) {
        header("Location: " . BASE_URL . "/profile.php?u=" . urlencode($current_user['username']));
        exit;
    }
    header("Location: " . BASE_URL . "/chat.php");
    exit;
}

$db = get_db();

// Fetch profile user
$stmt = $db->prepare("
    SELECT id, username, email, bio, avatar, banner, display_name, status_mood, location, website,
           is_verified, is_admin, is_banned, last_seen, created_at 
    FROM users 
    WHERE username = :u 
    LIMIT 1
");
$stmt->execute(['u' => $username]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    http_response_code(404);
    $page_title = 'Member not found — Nexly X';
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/sidebar_left.php';
    echo '<main class="feed-column"><div class="card" style="padding:40px; text-align:center;"><h2>Member @' . e($username) . ' does not exist.</h2><div style="margin-top:14px;"><a href="' . BASE_URL . '/chat.php" class="btn btn-yellow">Return to Live Chat</a></div></div></main>';
    include __DIR__ . '/includes/sidebar_right.php';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$profile_id = (int)$profile_user['id'];
$is_me = ($current_user && (int)$current_user['id'] === $profile_id);

$bio_updated_message = !empty($_GET['updated']) ? 'Profile saved.' : '';
if (!empty($_SESSION['flash_error'])) {
    $flash_error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
} else {
    $flash_error = '';
}

$is_online_now = !empty($profile_user['last_seen']) && (time() - strtotime($profile_user['last_seen'])) <= 60;

// Lobby messages count & recent public messages by this member
$lobby_cnt_stmt = $db->prepare("SELECT COUNT(*) FROM lobby_messages WHERE user_id = :id");
$lobby_cnt_stmt->execute(['id' => $profile_id]);
$lobby_msg_count = (int)$lobby_cnt_stmt->fetchColumn();

$recent_lobby_stmt = $db->prepare("
    SELECT id, user_id, body, media_type, media_url, media_thumb, media_duration, created_at
    FROM lobby_messages
    WHERE user_id = :id
    ORDER BY id DESC
    LIMIT 10
");
$recent_lobby_stmt->execute(['id' => $profile_id]);
$recent_lobby_msgs = $recent_lobby_stmt->fetchAll();

$pastel_bg = get_avatar_color($profile_user['username']);
$banner_url = !empty($profile_user['banner']) ? (UPLOAD_URL . '/' . rawurlencode($profile_user['banner'])) : null;
$clean_status = trim(preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', (string)($profile_user['status_mood'] ?? '')));

$page_title = e($profile_user['display_name'] ?: $profile_user['username']) . ' (@' . e($profile_user['username']) . ') — Chat Profile · Nexly X';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar_left.php';
?>

<main class="feed-column">
  <!-- Member Chat Profile Card -->
  <div class="card profile-showcase-card">
    <div class="profile-cover-banner <?= $banner_url ? 'has-banner' : 'no-banner' ?>" id="profile-cover-banner" style="<?= $banner_url ? 'background-image:url(\'' . e($banner_url) . '\'); background-size:cover; background-position:center;' : 'background-color:' . $pastel_bg . ';' ?>">
      <?php if ($is_me): ?>
        <button type="button" class="btn btn-sm profile-cover-edit-btn" onclick="openEditProfileModal('banner')">
          <?= $banner_url ? 'Change cover' : 'Add cover photo' ?>
        </button>
      <?php endif; ?>
    </div>

    <div class="profile-showcase-body">
      <div class="profile-avatar-row">
        <!-- Overlapping Avatar -->
        <div class="profile-avatar-wrap">
          <?= render_avatar($profile_user['username'], 84, 'profile-main-avatar', $profile_user['avatar'] ?? null) ?>
          <?php if ($is_online_now): ?>
            <span class="profile-online-badge" title="Online now"></span>
          <?php endif; ?>
          <?php if ($is_me): ?>
            <button type="button" class="profile-avatar-edit-fab" onclick="openEditProfileModal('avatar')" title="Change profile photo">
              photo
            </button>
          <?php endif; ?>
        </div>

        <!-- Top Right Action Buttons -->
        <div class="profile-action-buttons">
          <?php if ($is_me): ?>
            <button type="button" class="btn btn-sm btn-yellow bsx-cta-btn" onclick="openEditProfileModal()">
              <span>Edit Profile</span>
              <?= bsx_arrow_icon() ?>
            </button>
            <a href="<?= BASE_URL ?>/chat.php" class="btn btn-sm btn-orange bsx-cta-btn">
              <span>Open Live Chat</span>
              <?= bsx_arrow_icon() ?>
            </a>
          <?php elseif ($current_user): ?>
            <a href="<?= BASE_URL ?>/chat.php?u=<?= urlencode($profile_user['username']) ?>" class="btn btn-sm btn-orange bsx-cta-btn" title="Send direct message">
              <span>Direct Message</span>
              <?= bsx_arrow_icon() ?>
            </a>
            <button type="button" class="btn btn-sm" onclick="shareProfileLink('<?= e(addslashes($profile_user['username'])) ?>')">
              Copy Link
            </button>
          <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-sm btn-orange bsx-cta-btn">
              <span>Sign in to chat</span>
              <?= bsx_arrow_icon() ?>
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Identity -->
      <div class="profile-identity-block">
        <div class="profile-name-line">
          <h1 class="profile-username">
            <?= e($profile_user['display_name'] ?: $profile_user['username']) ?>
          </h1>
          <?= verified_badge($profile_user['is_verified']) ?>
          <?php if (!empty($profile_user['is_admin'])): ?>
            <span class="role-badge-admin">admin</span>
          <?php endif; ?>
        </div>

        <div class="profile-handle-line">
          <span class="profile-handle">@<?= e($profile_user['username']) ?></span>
          <?php if ($clean_status !== ''): ?>
            <span class="profile-status-note">— <?= e($clean_status) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Bio -->
      <p class="profile-bio"><?= nl2br(e($profile_user['bio'] ?: 'Available on Nexly X Live Studio Chat.')) ?></p>

      <!-- Editorial Metadata Line -->
      <div class="profile-meta-line">
        <?php if ($is_online_now): ?>
          <span style="color:var(--green); font-weight:600;"><span class="live-dot"></span> Online right now</span>
          <span class="meta-dot">·</span>
        <?php else: ?>
          <span>Last active <?= time_ago($profile_user['last_seen'] ?? null) ?></span>
          <span class="meta-dot">·</span>
        <?php endif; ?>

        <?php if (!empty($profile_user['location'])): ?>
          <span><?= e($profile_user['location']) ?></span>
          <span class="meta-dot">·</span>
        <?php endif; ?>

        <?php if (!empty($profile_user['website'])): ?>
          <?php
            $clean_host = preg_replace('#^https?://(www\.)?#i', '', $profile_user['website']);
            $clean_host = rtrim($clean_host, '/');
          ?>
          <a href="<?= e($profile_user['website']) ?>" target="_blank" rel="noopener noreferrer" class="profile-web-link"><?= e($clean_host) ?></a>
          <span class="meta-dot">·</span>
        <?php endif; ?>

        <span>joined <?= strtolower(date('M Y', strtotime($profile_user['created_at'] ?? 'now'))) ?></span>
      </div>

      <!-- Chat Stats Line -->
      <div class="profile-stats-line">
        <span><strong class="stat-num"><?= $lobby_msg_count ?></strong> lobby messages</span>
        <span><strong class="stat-num"><?= $is_online_now ? 'Live' : 'Idle' ?></strong> studio status</span>
      </div>

      <?php if ($bio_updated_message): ?>
        <div class="profile-toast-inline success"><?= e($bio_updated_message) ?></div>
      <?php endif; ?>
      <?php if ($flash_error): ?>
        <div class="profile-toast-inline error"><?= e($flash_error) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Public Lobby Activity Card -->
  <div class="card" style="padding:20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--border-subtle);">
      <h2 style="font-size:15px; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--muted);">
        Recent # Lobby Messages by @<?= e($profile_user['username']) ?>
      </h2>
      <?php if (!$is_me && $current_user): ?>
        <a href="<?= BASE_URL ?>/chat.php?u=<?= urlencode($profile_user['username']) ?>" class="btn btn-sm">
          Message @<?= e($profile_user['username']) ?>
        </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/chat.php" class="btn btn-sm">
          Go to # Lobby
        </a>
      <?php endif; ?>
    </div>

    <?php if (empty($recent_lobby_msgs)): ?>
      <div style="padding:28px 16px; text-align:center; color:var(--muted);">
        <p style="margin-bottom:12px;">No public Lobby messages from @<?= e($profile_user['username']) ?> yet.</p>
        <?php if (!$is_me && $current_user): ?>
          <a href="<?= BASE_URL ?>/chat.php?u=<?= urlencode($profile_user['username']) ?>" class="btn btn-orange bsx-cta-btn">
            <span>Start Direct Chat with @<?= e($profile_user['username']) ?></span>
            <?= bsx_arrow_icon() ?>
          </a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div style="display:flex; flex-direction:column; gap:10px;">
        <?php foreach ($recent_lobby_msgs as $lm): ?>
          <div class="message-bubble theirs" style="max-width:100%;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px; font-size:12px;">
              <strong>@<?= e($profile_user['username']) ?> <?= verified_badge($profile_user['is_verified']) ?></strong>
              <span style="color:var(--muted); font-size:11px;"><?= time_ago($lm['created_at']) ?> in # Lobby</span>
            </div>
            <?= render_chat_media($lm) ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php if ($is_me): ?>
<!-- Native Light-Dismiss Dialog: Edit Profile & Upload Avatar/Banner -->
<dialog id="edit-profile-dialog" class="brutalist-dialog dialog-wide" closedby="any" aria-labelledby="edit-profile-title">
  <div class="dialog-header">
    <h2 id="edit-profile-title">Edit Chat Profile</h2>
    <form method="dialog">
      <button type="submit" class="dialog-close-btn" aria-label="Close dialog">Close</button>
    </form>
  </div>

  <form method="POST" action="<?= BASE_URL ?>/actions/update_profile.php" enctype="multipart/form-data" class="dialog-body edit-profile-form">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/profile.php?u=<?= urlencode($profile_user['username']) ?>&updated=1">

    <div class="edit-profile-media-section">
      <div class="edit-banner-preview" id="edit-banner-preview" style="<?= $banner_url ? 'background-image:url(\'' . e($banner_url) . '\'); background-size:cover; background-position:center;' : 'background-color:' . $pastel_bg . ';' ?>">
        <label for="banner-photo-input" class="btn btn-sm btn-yellow" style="cursor:pointer;">
          Choose cover photo
        </label>
        <input type="file" id="banner-photo-input" name="banner_photo" accept="image/jpeg,image/png,image/webp,image/gif" class="visually-hidden" onchange="previewProfileBanner(this)">
      </div>

      <div class="edit-avatar-row">
        <div class="edit-avatar-circle-wrap" id="edit-avatar-preview-wrap">
          <?= render_avatar($profile_user['username'], 72, '', $profile_user['avatar'] ?? null) ?>
        </div>
        <div style="display:flex; flex-direction:column; gap:6px;">
          <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <label for="avatar-photo-input" class="btn btn-sm btn-orange" style="cursor:pointer;">
              Choose profile photo
            </label>
            <input type="file" id="avatar-photo-input" name="avatar_photo" accept="image/jpeg,image/png,image/webp,image/gif" class="visually-hidden" onchange="previewProfileAvatar(this)">
            <?php if (!empty($profile_user['avatar'])): ?>
              <label style="display:inline-flex; align-items:center; gap:5px; font-size:12.5px; font-weight:700; cursor:pointer;">
                <input type="checkbox" name="remove_avatar" value="1"> use initials instead
              </label>
            <?php endif; ?>
            <?php if (!empty($profile_user['banner'])): ?>
              <label style="display:inline-flex; align-items:center; gap:5px; font-size:12.5px; font-weight:700; cursor:pointer;">
                <input type="checkbox" name="remove_banner" value="1"> remove cover
              </label>
            <?php endif; ?>
          </div>
          <span style="font-size:12px; color:var(--muted);">JPG, PNG, WebP or GIF up to 5 MB.</span>
        </div>
      </div>
    </div>

    <div class="edit-profile-grid">
      <div class="form-group">
        <label class="form-label" for="edit-display-name">Display Name</label>
        <input type="text" id="edit-display-name" name="display_name" class="form-control" maxlength="60"
               value="<?= e($profile_user['display_name'] ?? '') ?>" placeholder="<?= e($profile_user['username']) ?>" autocomplete="name">
      </div>

      <div class="form-group">
        <label class="form-label" for="edit-status-mood">Chat Status</label>
        <input type="text" id="edit-status-mood" name="status_mood" class="form-control" maxlength="80"
               value="<?= e($clean_status) ?>" placeholder="around to chat">
        <div class="mood-preset-chips">
          <button type="button" class="mood-chip" onclick="setMoodPreset('around to chat')">around to chat</button>
          <button type="button" class="mood-chip" onclick="setMoodPreset('voice chat ready')">voice chat ready</button>
          <button type="button" class="mood-chip" onclick="setMoodPreset('in the studio')">in the studio</button>
          <button type="button" class="mood-chip" onclick="setMoodPreset('busy right now')">busy right now</button>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="edit-bio">Bio (up to 300 chars)</label>
      <textarea id="edit-bio" name="bio" class="form-control" rows="3" maxlength="300" placeholder="Tell people a bit about yourself..."><?= e($profile_user['bio'] ?? '') ?></textarea>
    </div>

    <div class="edit-profile-grid">
      <div class="form-group">
        <label class="form-label" for="edit-location">City / place</label>
        <input type="text" id="edit-location" name="location" class="form-control" maxlength="80"
               value="<?= e($profile_user['location'] ?? '') ?>" placeholder="Phnom Penh">
      </div>

      <div class="form-group">
        <label class="form-label" for="edit-website">Website</label>
        <input type="text" id="edit-website" name="website" class="form-control" maxlength="180"
               value="<?= e($profile_user['website'] ?? '') ?>" placeholder="https://example.com"
               autocorrect="off" autocapitalize="none" spellcheck="false">
      </div>
    </div>

    <div class="dialog-footer">
      <button type="button" class="btn" onclick="document.getElementById('edit-profile-dialog').close()">Cancel</button>
      <button type="submit" class="btn btn-orange bsx-cta-btn">
        <span>Save Profile</span>
        <?= bsx_arrow_icon() ?>
      </button>
    </div>
  </form>
</dialog>
<?php endif; ?>

<?php
include __DIR__ . '/includes/sidebar_right.php';
include __DIR__ . '/includes/footer.php';
?>
