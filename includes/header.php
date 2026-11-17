<?php
// Nexly X — Live Chat Header
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$current_user = current_user();
$page_title = $page_title ?? 'Nexly X — Live Studio Chat';
$current_page = basename($_SERVER['PHP_SELF']);
$unread_dm_count = $current_user ? get_unread_dm_count($current_user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  
  <!-- CSRF & Color Scheme Meta -->
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <meta name="color-scheme" content="light">
  <meta name="theme-color" content="#1C1B18">
  
  <!-- Typography: Space Grotesk (Brand, Headings) + Inter (Body) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheet (Auto Cache-Busting) -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: time() ?>">
  
  <?php
    $init_max_event_id = 0;
    try {
        $db_h = get_db();
        $me_stmt = $db_h->query("SELECT COALESCE(MAX(id), 0) FROM events");
        $init_max_event_id = (int)$me_stmt->fetchColumn();
    } catch (Exception $e) {}
  ?>
  <script>
    window.SNAPWALL_BASE = "<?= BASE_URL ?>";
    window.NEXLY_BASE = "<?= BASE_URL ?>";
    window.CURRENT_USER_ID = <?= $current_user ? (int)$current_user['id'] : 'null' ?>;
    window.CURRENT_USER_NAME = <?= $current_user ? json_encode($current_user['username']) : 'null' ?>;
    window.CURRENT_USER_VERIFIED = <?= ($current_user && !empty($current_user['is_verified'])) ? 'true' : 'false' ?>;
    window.CURRENT_USER_ADMIN = <?= ($current_user && !empty($current_user['is_admin'])) ? 'true' : 'false' ?>;
    window.CSRF_TOKEN = "<?= csrf_token() ?>";
    window.LATEST_EVENT_ID = <?= $init_max_event_id ?>;
  </script>
</head>
<body class="<?= e($body_class ?? '') ?>">

  <!-- Weak Password Mandatory Banner -->
  <?php if ($current_user && !empty($current_user['must_change_password'])): ?>
    <div class="password-warning-banner">
      <div class="password-warning-inner">
        <span>Your password is too weak, change it before going online.</span>
        <a href="<?= BASE_URL ?>/settings.php" class="btn btn-sm btn-accent">Change password</a>
      </div>
    </div>
  <?php endif; ?>

  <!-- Mobile Top Header (visible under 760px) -->
  <header class="mobile-header">
    <div class="brand">
      <a href="<?= BASE_URL ?>/chat.php" class="brand-link">
        <span class="brand-logo-mark" aria-hidden="true"></span>
        <span class="logo-text">NEXLY X</span>
      </a>
    </div>
    <div style="display:flex; align-items:center; gap:8px;">
      <?php if ($current_user): ?>
        <button type="button" class="btn btn-sm btn-accent" onclick="openNewChatModal()" aria-label="Start new chat">
          <svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="10" y1="4" x2="10" y2="16"/><line x1="4" y1="10" x2="16" y2="10"/></svg>
          <span>New</span>
        </button>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-sm btn-accent">
          <span>Sign In</span>
        </a>
      <?php endif; ?>
    </div>
  </header>

  <!-- Mobile Navigation Bar (visible under 900px) -->
  <nav class="mobile-nav-bar" aria-label="Mobile">
    <a href="<?= BASE_URL ?>/chat.php" class="btn btn-sm <?= (($current_page === 'chat.php' || $current_page === 'index.php') && empty($_GET['u'])) ? 'btn-yellow' : '' ?>">Lobby</a>
    <button type="button" class="btn btn-sm <?= (!empty($_GET['u'])) ? 'btn-yellow' : '' ?>" onclick="openNewChatModal()">
      Direct Chat <?php if ($unread_dm_count > 0): ?><span class="badge-count" style="font-size:10px; min-width:18px; height:18px;"><?= $unread_dm_count ?></span><?php endif; ?>
    </button>
    <?php if ($current_user): ?>
      <a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($current_user['username']) ?>" class="btn btn-sm <?= ($current_page === 'profile.php') ? 'btn-yellow' : '' ?>">Profile</a>
      <a href="<?= BASE_URL ?>/settings.php" class="btn btn-sm <?= ($current_page === 'settings.php') ? 'btn-yellow' : '' ?>">Settings</a>
      <?php if (!empty($current_user['is_admin'])): ?>
        <a href="<?= BASE_URL ?>/admin.php" class="btn btn-sm <?= ($current_page === 'admin.php') ? 'btn-yellow' : '' ?>">Admin</a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm" style="color:var(--muted);">Log out</a>
    <?php endif; ?>
  </nav>

  <div class="layout-container">
