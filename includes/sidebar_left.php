<?php
// Left Sidebar Menu — Column 1 (208px Ink #1C1B18)
$current_page = basename($_SERVER['PHP_SELF']);
$current_user = current_user();
$unread_dm_count = $current_user ? get_unread_dm_count($current_user['id']) : 0;

$in_chat_page = ($current_page === 'chat.php' || $current_page === 'index.php');
$is_lobby     = ($in_chat_page && empty($_GET['u']));
$is_dm        = ($in_chat_page && !empty($_GET['u']));
$is_me        = ($current_user && $current_page === 'profile.php' && ($_GET['u'] ?? '') === $current_user['username']);
$is_settings  = ($current_page === 'settings.php');
$is_admin_pg  = ($current_page === 'admin.php');
?>
<aside class="sidebar-left">
  <!-- Brand Header -->
  <div class="sidebar-brand-block">
    <a href="<?= BASE_URL ?>/chat.php" class="sidebar-brand-link">
      <span class="brand-square-mark" aria-hidden="true"></span>
      <span class="brand-title-text">NEXLY X</span>
    </a>
    <div class="brand-subtitle-text">Voice &amp; studio chat</div>
  </div>

  <!-- Primary Navigation -->
  <nav class="sidebar-nav" aria-label="Primary Navigation">
    <a href="<?= BASE_URL ?>/index.php" class="sidebar-nav-item">
      <svg class="sidebar-nav-icon" viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M16 10H4M9 5l-5 5 5 5"/>
      </svg>
      <span class="sidebar-nav-label">Landing home</span>
    </a>

    <a href="<?= BASE_URL ?>/chat.php" class="sidebar-nav-item <?= $is_lobby ? 'active' : '' ?>" <?= $is_lobby ? 'aria-current="page"' : '' ?>>
      <svg class="sidebar-nav-icon" viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="4" y1="8" x2="16" y2="8"/>
        <line x1="4" y1="12" x2="16" y2="12"/>
        <line x1="9" y1="3" x2="7" y2="17"/>
        <line x1="13" y1="3" x2="11" y2="17"/>
      </svg>
      <span class="sidebar-nav-label">Public lobby</span>
    </a>

    <a href="<?= BASE_URL ?>/chat.php#dm-list" class="sidebar-nav-item <?= ($is_dm || $in_chat_page) ? 'active' : '' ?>" <?= ($is_dm || $in_chat_page) ? 'aria-current="page"' : '' ?> onclick="if(document.getElementById('chat-member-filter')){document.getElementById('chat-member-filter').focus();}">
      <svg class="sidebar-nav-icon" viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M17 10c0 3.3-3.1 6-7 6a8.5 8.5 0 0 1-3.1-.6L3 16.5l1.3-3.3C3.5 12.1 3 11.1 3 10c0-3.3 3.1-6 7-6s7 2.7 7 6z"/>
      </svg>
      <span class="sidebar-nav-label">Direct messages</span>
      <span id="nav-chat-badge" class="sidebar-nav-count" style="<?= ($unread_dm_count > 0) ? '' : 'display:none;' ?>"><?= $unread_dm_count ?></span>
    </a>

    <?php if ($current_user): ?>
      <a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($current_user['username']) ?>" class="sidebar-nav-item <?= $is_me ? 'active' : '' ?>" <?= $is_me ? 'aria-current="page"' : '' ?>>
        <svg class="sidebar-nav-icon" viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M16 17v-1.5A3.5 3.5 0 0 0 12.5 12h-5A3.5 3.5 0 0 0 4 15.5V17"/>
          <circle cx="10" cy="6.5" r="3.5"/>
        </svg>
        <span class="sidebar-nav-label">My profile</span>
      </a>

      <a href="<?= BASE_URL ?>/settings.php" class="sidebar-nav-item <?= $is_settings ? 'active' : '' ?>" <?= $is_settings ? 'aria-current="page"' : '' ?>>
        <svg class="sidebar-nav-icon" viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="10" cy="10" r="3"/>
          <path d="M16.5 12.5l1.2-1-1.2-2.1-1.5.4a6 6 0 0 0-1.2-.7L13.5 7.6h-2.4l-.3 1.5a6 6 0 0 0-1.2.7l-1.5-.4-1.2 2.1 1.2 1a6 6 0 0 0 0 1.4l-1.2 1 1.2 2.1 1.5-.4a6 6 0 0 0 1.2.7l.3 1.5h2.4l.3-1.5a6 6 0 0 0 1.2-.7l1.5.4 1.2-2.1-1.2-1a6 6 0 0 0 0-1.4z"/>
        </svg>
        <span class="sidebar-nav-label">Settings</span>
      </a>

      <?php if (!empty($current_user['is_admin'])): ?>
        <a href="<?= BASE_URL ?>/admin.php" class="sidebar-nav-item <?= $is_admin_pg ? 'active' : '' ?>" <?= $is_admin_pg ? 'aria-current="page"' : '' ?>>
          <svg class="sidebar-nav-icon" viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10 2l7 3v5c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9V5l7-3z"/>
          </svg>
          <span class="sidebar-nav-label">Admin console</span>
        </a>
      <?php endif; ?>
    <?php endif; ?>
  </nav>

  <!-- Primary Action Button -->
  <?php if ($current_user): ?>
    <button type="button" class="sidebar-btn-primary" onclick="openNewChatModal()">
      <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="10" y1="4" x2="10" y2="16"/>
        <line x1="4" y1="10" x2="16" y2="10"/>
      </svg>
      <span>Start new chat</span>
    </button>

    <!-- Bottom User Section -->
    <div class="sidebar-bottom-user">
      <div class="sidebar-user-avatar-wrap">
        <?= render_avatar($current_user['username'], 38, 'sidebar-avatar') ?>
      </div>
      <div class="sidebar-user-meta">
        <div class="sidebar-user-name-line">
          <span class="sidebar-user-name"><?= e($current_user['display_name'] ?: $current_user['username']) ?></span>
          <?= verified_badge($current_user['is_verified']) ?>
        </div>
        <div class="sidebar-user-subtitle">
          <?= (strcasecmp($current_user['username'], 'rt') === 0) ? 'CEO of Nexly' : e($current_user['status_mood'] ?: '@' . $current_user['username']) ?>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/logout.php" class="sidebar-signout-btn">Sign out</a>
    </div>
  <?php else: ?>
    <a href="<?= BASE_URL ?>/login.php" class="sidebar-btn-primary">
      <span>Sign In</span>
    </a>
  <?php endif; ?>
</aside>
