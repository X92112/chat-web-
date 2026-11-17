<?php
// Right Sidebar — Column 4 (264px Surface #FBFAF7, left border #E2DDD2, scrolls)
$current_user = current_user();
$online_users = get_online_users(15);

$db = get_db();
$my_id = $current_user ? (int)$current_user['id'] : 0;
$dir_stmt = $db->prepare("
    SELECT id, username, display_name, bio, status_mood, avatar, is_verified, last_seen
    FROM users
    WHERE is_banned = 0 AND id != :my_id
    ORDER BY (last_seen >= (NOW() - INTERVAL 60 SECOND)) DESC, is_verified DESC, id ASC
    LIMIT 12
");
$dir_stmt->execute(['my_id' => $my_id]);
$directory_members = $dir_stmt->fetchAll();
?>
<aside class="sidebar-right">
  <!-- SECTION: ONLINE NOW -->
  <div class="right-panel-section">
    <div class="right-panel-heading">
      <span>ONLINE NOW</span>
      <span class="right-panel-count" id="online-count-num"><?= count($online_users) ?></span>
    </div>

    <div class="right-panel-online-list" id="online-users-container">
      <?php if (empty($online_users)): ?>
        <div class="right-panel-empty">No other members online</div>
      <?php else: ?>
        <?php foreach ($online_users as $u): ?>
          <?php 
            $chat_href = ($current_user && (int)$u['id'] !== (int)$current_user['id']) ? (BASE_URL . '/chat.php?u=' . urlencode($u['username'])) : (BASE_URL . '/profile.php?u=' . urlencode($u['username']));
            $u_status = (!empty($u['status_mood'])) ? $u['status_mood'] : ((strcasecmp($u['username'], 'rt') === 0) ? 'Building Nexly' : 'Online');
          ?>
          <a href="<?= $chat_href ?>" class="right-panel-online-row" title="Chat with @<?= e($u['username']) ?>">
            <div class="right-panel-online-avatar-slot">
              <?= render_avatar($u['username'], 32, '', $u['avatar'] ?? null) ?>
              <span class="online-status-dot-avatar" aria-hidden="true"></span>
            </div>
            <div class="right-panel-online-meta">
              <span class="right-panel-user-name">
                <?= e($u['display_name'] ?: $u['username']) ?><?= verified_badge($u['is_verified'] ?? 0) ?>
              </span>
              <span class="right-panel-user-status"><?= e($u_status) ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- SECTION: MEMBERS -->
  <?php if (!empty($directory_members)): ?>
    <div class="right-panel-section" style="margin-top:28px;">
      <div class="right-panel-heading">
        <span>MEMBERS</span>
      </div>

      <div class="right-panel-members-list">
        <?php foreach ($directory_members as $m): ?>
          <?php 
            $m_online = !empty($m['last_seen']) && (time() - strtotime($m['last_seen'])) < 60;
            $m_status = $m['status_mood'] ?: ((strcasecmp($m['username'], 'rt') === 0) ? 'Building Nexly' : ($m['bio'] ?: 'Available to chat'));
          ?>
          <div class="right-panel-member-row">
            <div class="right-panel-member-info">
              <a href="<?= BASE_URL ?>/chat.php?u=<?= urlencode($m['username']) ?>" class="right-panel-avatar-link">
                <?= render_avatar($m['username'], 36, '', $m['avatar'] ?? null) ?>
                <?php if ($m_online): ?>
                  <span class="online-status-dot-avatar" aria-hidden="true"></span>
                <?php endif; ?>
              </a>
              <div class="right-panel-member-text">
                <a href="<?= BASE_URL ?>/chat.php?u=<?= urlencode($m['username']) ?>" class="right-panel-member-name">
                  <?= e($m['username']) ?><?= verified_badge($m['is_verified'] ?? 0) ?>
                </a>
                <span class="right-panel-member-sub" title="<?= e($m_status) ?>">
                  <?= e($m_status) ?>
                </span>
              </div>
            </div>

            <!-- 44x44 ICON-ONLY ghost button with chat bubble icon -->
            <a href="<?= BASE_URL ?>/chat.php?u=<?= urlencode($m['username']) ?>" 
               class="right-panel-chat-btn" 
               aria-label="Chat with <?= e($m['username']) ?>" 
               title="Chat with <?= e($m['username']) ?>">
              <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M17 10c0 3.3-3.1 6-7 6a8.5 8.5 0 0 1-3.1-.6L3 16.5l1.3-3.3C3.5 12.1 3 11.1 3 10c0-3.3 3.1-6 7-6s7 2.7 7 6z"/>
              </svg>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</aside>
