<?php
// Nexly X — Dedicated Live Chat Hub (Calm Editorial UI Redesign)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$current_user = require_login();
$db = get_db();
$my_id = (int)$current_user['id'];
$is_admin = !empty($current_user['is_admin']);

// Determine active mode: 'lobby' or 'dm'
$partner_name = trim($_GET['u'] ?? '');
$mode = 'lobby';
$active_partner = null;

if ($partner_name !== '') {
    $p_stmt = $db->prepare("SELECT id, username, display_name, bio, status_mood, avatar, is_verified, is_admin, last_seen FROM users WHERE username = :u AND is_banned = 0 LIMIT 1");
    $p_stmt->execute(['u' => $partner_name]);
    $partner_user = $p_stmt->fetch();
    if ($partner_user && (int)$partner_user['id'] !== $my_id) {
        $active_partner = $partner_user;
        $mode = 'dm';
    }
}

// 1. Fetch All Members / Conversations List for Chat Sidebar (anyone you can chat with)
$conv_sql = "
    SELECT u.id, u.username, u.display_name, u.bio, u.status_mood, u.avatar, u.is_verified, u.last_seen,
           (SELECT body FROM messages WHERE (from_id = u.id AND to_id = :my_id1) OR (from_id = :my_id2 AND to_id = u.id) ORDER BY id DESC LIMIT 1) AS last_body,
           (SELECT media_type FROM messages WHERE (from_id = u.id AND to_id = :my_id3) OR (from_id = :my_id4 AND to_id = u.id) ORDER BY id DESC LIMIT 1) AS last_media_type,
           (SELECT media_duration FROM messages WHERE (from_id = u.id AND to_id = :my_id5) OR (from_id = :my_id6 AND to_id = u.id) ORDER BY id DESC LIMIT 1) AS last_media_duration,
           (SELECT created_at FROM messages WHERE (from_id = u.id AND to_id = :my_id7) OR (from_id = :my_id8 AND to_id = u.id) ORDER BY id DESC LIMIT 1) AS last_time,
           (SELECT COUNT(*) FROM messages WHERE from_id = u.id AND to_id = :my_id9 AND is_seen = 0) AS unread_count
    FROM users u
    WHERE u.id != :my_id10 AND u.is_banned = 0
    ORDER BY (unread_count > 0) DESC, (last_time IS NOT NULL) DESC, last_time DESC, (u.last_seen >= (NOW() - INTERVAL 60 SECOND)) DESC, u.is_verified DESC, u.username ASC
";
$conv_stmt = $db->prepare($conv_sql);
$conv_stmt->execute([
    'my_id1' => $my_id, 'my_id2' => $my_id, 'my_id3' => $my_id,
    'my_id4' => $my_id, 'my_id5' => $my_id, 'my_id6' => $my_id,
    'my_id7' => $my_id, 'my_id8' => $my_id, 'my_id9' => $my_id,
    'my_id10' => $my_id
]);
$conversations = $conv_stmt->fetchAll();

// 2. Fetch Initial Messages
$lobby_messages = [];
$dm_messages = [];

if ($mode === 'lobby') {
    // Load last 50 Lobby messages
    $l_stmt = $db->prepare("
        SELECT lm.id, lm.user_id, lm.body, lm.media_type, lm.media_url, lm.media_thumb, lm.media_duration, lm.created_at,
               u.username, u.is_verified, u.is_admin
        FROM lobby_messages lm
        JOIN users u ON lm.user_id = u.id
        ORDER BY lm.id DESC
        LIMIT 50
    ");
    $l_stmt->execute();
    $lobby_messages = array_reverse($l_stmt->fetchAll());
} else {
    // DM Mode: mark unread messages from this partner as seen
    $partner_id = (int)$active_partner['id'];
    $db->prepare("
        UPDATE messages 
        SET is_seen = 1, seen_at = NOW() 
        WHERE to_id = :my_id AND from_id = :p_id AND is_seen = 0
    ")->execute(['my_id' => $my_id, 'p_id' => $partner_id]);

    // Notify partner that their messages were seen
    dispatch_event('user:' . $partner_id, 'dm_seen', [
        'reader_id'   => $my_id,
        'reader_name' => $current_user['username'],
        'seen_at'     => date('H:i')
    ], $my_id);

    // Load last 100 DM messages in chronological order (fetch newest first, then reverse for display)
    $m_stmt = $db->prepare("
        SELECT id, from_id, to_id, body, media_type, media_url, media_thumb, media_duration, is_seen, seen_at, created_at
        FROM messages
        WHERE (from_id = :my_id1 AND to_id = :p_id1)
           OR (from_id = :p_id2 AND to_id = :my_id2)
        ORDER BY id DESC
        LIMIT 100
    ");
    $m_stmt->execute([
        'my_id1' => $my_id, 'p_id1' => $partner_id,
        'p_id2'  => $partner_id, 'my_id2' => $my_id
    ]);
    $dm_messages = array_reverse($m_stmt->fetchAll());
}

$page_title = ($mode === 'lobby') ? 'Nexly X — # Public Lobby' : ($active_partner['display_name'] ?: $active_partner['username']) . ' · Nexly X';
$body_class = 'is-chat-page';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar_left.php';
?>

<script>
  window.CHAT_MODE = <?= json_encode($mode) ?>;
  window.ACTIVE_PARTNER_ID = <?= $active_partner ? (int)$active_partner['id'] : 'null' ?>;
  window.ACTIVE_PARTNER_NAME = <?= $active_partner ? json_encode($active_partner['username']) : 'null' ?>;
  window.ACTIVE_PARTNER_DISPLAY = <?= $active_partner ? json_encode($active_partner['display_name'] ?: $active_partner['username']) : 'null' ?>;
  window.ACTIVE_PARTNER_AVATAR = <?= $active_partner ? json_encode($active_partner['avatar'] ?? null) : 'null' ?>;
  window.OLDEST_LOBBY_ID = <?= !empty($lobby_messages) ? (int)$lobby_messages[0]['id'] : 0 ?>;
  window.LATEST_LOBBY_ID = <?= !empty($lobby_messages) ? (int)end($lobby_messages)['id'] : 0 ?>;
  window.OLDEST_DM_ID = <?= !empty($dm_messages) ? (int)$dm_messages[0]['id'] : 0 ?>;
  window.LATEST_DM_ID = <?= !empty($dm_messages) ? (int)end($dm_messages)['id'] : 0 ?>;
</script>

<!-- COLUMN 2: CONVERSATION LIST (surface #FBFAF7, right border #E2DDD2, 304px) -->
<aside class="chat-sidebar" id="dm-list" aria-label="Conversation list">
  <!-- Header: "Direct messages" (Space Grotesk 600, 20px) + muted count -->
  <div class="convo-list-header">
    <h1 class="convo-list-title">Direct messages</h1>
    <span class="convo-list-count"><?= count($conversations) ?></span>
  </div>

  <!-- Search input "Filter people" with search icon (44px, white, 1px border, 12px radius) -->
  <div class="convo-search-wrap">
    <label for="chat-member-filter" class="visually-hidden">Filter people</label>
    <div class="convo-search-box">
      <svg class="convo-search-icon" viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="9" cy="9" r="6"/>
        <line x1="13.5" y1="13.5" x2="17.5" y2="17.5"/>
      </svg>
      <input type="search" id="chat-member-filter" class="convo-search-input" placeholder="Filter people..." autocomplete="off" oninput="filterChatSidebar(this.value)">
    </div>
  </div>

  <!-- Pinned row: "Public lobby" with subtitle "Everyone's live chat" and green online dot -->
  <a href="<?= BASE_URL ?>/chat.php" class="convo-row convo-pinned <?= ($mode === 'lobby') ? 'active' : '' ?>">
    <div class="convo-avatar-slot">
      <div class="avatar-circle lobby-avatar" style="width:40px; height:40px; background-color:#E8D9C4; color:#4A3A22; font-family:var(--font-brand); font-size:16px; font-weight:600;">
        #
      </div>
      <span class="convo-online-dot" aria-hidden="true"></span>
    </div>
    <div class="convo-meta">
      <div class="convo-name-row">
        <span class="convo-name">Public lobby</span>
      </div>
      <span class="convo-preview">Everyone's live chat</span>
    </div>
  </a>

  <!-- Divider -->
  <div class="convo-divider" role="separator"></div>

  <!-- Conversation rows (min-height 64px, 40px tinted avatar, verified badge, one-line preview with 14px event icons, now timestamp) -->
  <div class="dm-conversations-scroll" id="dm-conversations-scroll">
    <?php if (empty($conversations)): ?>
      <div class="convo-empty-note">
        No other members registered yet.
      </div>
    <?php else: ?>
      <?php foreach ($conversations as $index => $c): ?>
        <?php 
          $is_act = ($mode === 'dm' && $active_partner && (int)$active_partner['id'] === (int)$c['id']);
          $is_online = (!empty($c['last_seen']) && (time() - strtotime($c['last_seen'])) < 60);

          // Build one-line preview with small 14px event icon
          $preview_icon_svg = '';
          $preview_text = '';
          $m_type = $c['last_media_type'] ?? '';
          $m_body = trim($c['last_body'] ?? '');

          if ($m_type === 'call') {
              $is_missed = stripos($m_body, 'missed') !== false || stripos($m_body, 'declined') !== false;
              $is_video = stripos($m_body, 'video') !== false;
              if ($is_missed) {
                  $preview_icon_svg = '<svg viewBox="0 0 20 20" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;"><line x1="2" y1="2" x2="18" y2="18"/><path d="M8.5 13.5a16.2 16.2 0 0 1-3.2-3.2 16.5 16.5 0 0 1-2.4-7 1.7 1.7 0 0 1 1.7-1.8h2.5a1.7 1.7 0 0 1 1.7 1.5c.2.8.4 1.6.6 2.4a1.7 1.7 0 0 1-.4 1.8l-1.1 1.1"/></svg>';
                  $preview_text = $is_video ? 'Missed video call' : 'Missed voice call';
              } elseif ($is_video) {
                  $preview_icon_svg = '<svg viewBox="0 0 20 20" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;"><rect x="2" y="4" width="12" height="12" rx="2"/><polygon points="14 8 18 5 18 15 14 12"/></svg>';
                  $preview_text = 'Video call ended';
              } else {
                  $preview_icon_svg = '<svg viewBox="0 0 20 20" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;"><path d="M17 13.8v2.5a1.7 1.7 0 0 1-1.8 1.7 16.5 16.5 0 0 1-7.2-2.6 16.2 16.2 0 0 1-5-5A16.5 16.5 0 0 1 .4 3.2 1.7 1.7 0 0 1 2.1 1.4h2.5a1.7 1.7 0 0 1 1.7 1.5 10.7 10.7 0 0 0 .6 2.4 1.7 1.7 0 0 1-.4 1.8L5.4 8.2a13.2 13.2 0 0 0 5 5l1.1-1.1a1.7 1.7 0 0 1 1.8-.4 10.7 10.7 0 0 0 2.4.6 1.7 1.7 0 0 1 1.3 1.5z"/></svg>';
                  $preview_text = 'Voice call ended';
              }
          } elseif ($m_type === 'image') {
              $preview_icon_svg = '<svg viewBox="0 0 20 20" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;"><rect x="2" y="5" width="16" height="12" rx="2"/><circle cx="10" cy="11" r="3"/><circle cx="14.5" cy="8" r="0.75" fill="currentColor"/></svg>';
              $preview_text = $m_body ?: 'Photo';
          } elseif ($m_type === 'audio') {
              $preview_icon_svg = '<svg viewBox="0 0 20 20" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;"><path d="M10 2a2.5 2.5 0 0 0-2.5 2.5v5a2.5 2.5 0 0 0 5 0v-5A2.5 2.5 0 0 0 10 2z"/><path d="M15 9.5a5 5 0 0 1-10 0"/><line x1="10" y1="14.5" x2="10" y2="18"/><line x1="7" y1="18" x2="13" y2="18"/></svg>';
              $dur = (int)($c['last_media_duration'] ?? 0);
              $preview_text = 'Voice note' . ($dur > 0 ? ' (' . gmdate($dur >= 3600 ? "H:i:s" : "i:s", $dur) . ')' : '');
          } else {
              $preview_text = $m_body ?: ($c['status_mood'] ?: 'Tap to start chatting');
          }

          // Timestamp: "now" for newest conversation or within 60s
          $time_label = '';
          if (!empty($c['last_time'])) {
              $diff = time() - strtotime($c['last_time']);
              if ($diff < 60 || ($index === 0 && $diff < 300)) {
                  $time_label = 'now';
              } elseif ($diff < 86400) {
                  $time_label = date('H:i', strtotime($c['last_time']));
              } else {
                  $time_label = date('M j', strtotime($c['last_time']));
              }
          }
        ?>
        <a href="<?= BASE_URL ?>/chat.php?u=<?= urlencode($c['username']) ?>"
           class="convo-row dm-sidebar-item <?= $is_act ? 'active' : '' ?>"
           data-user="<?= e(strtolower($c['username'] . ' ' . ($c['display_name'] ?? ''))) ?>">
          <div class="convo-avatar-slot">
            <?= render_avatar($c['username'], 40, '', $c['avatar'] ?? null) ?>
            <?php if ($is_online): ?>
              <span class="convo-online-dot" aria-hidden="true"></span>
            <?php endif; ?>
          </div>

          <div class="convo-meta">
            <div class="convo-name-row">
              <span class="convo-name">
                <?= e($c['display_name'] ?: $c['username']) ?>
                <?= verified_badge($c['is_verified']) ?>
              </span>
              <?php if ($time_label !== ''): ?>
                <span class="convo-time"><?= e($time_label) ?></span>
              <?php endif; ?>
            </div>

            <div class="convo-preview-row">
              <span class="convo-preview">
                <?php if ($preview_icon_svg !== ''): ?>
                  <span class="convo-preview-icon"><?= $preview_icon_svg ?></span>
                <?php endif; ?>
                <span class="convo-preview-text"><?= e($preview_text) ?></span>
              </span>

              <?php if (!empty($c['unread_count'])): ?>
                <span class="badge-count convo-unread-badge"><?= (int)$c['unread_count'] ?></span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</aside>

<!-- COLUMN 3: CHAT PANEL (background #F4F1EA) -->
<main class="chat-main-pane" id="chat-main-column">
  
  <!-- Header (84px, surface bg, bottom border #E2DDD2) -->
  <header class="chat-header">
    <?php if ($mode === 'lobby'): ?>
      <div class="chat-header-user">
        <div class="avatar-circle" style="width:46px; height:46px; background-color:#E8D9C4; color:#4A3A22; font-family:var(--font-brand); font-size:18px; font-weight:600;">
          #
        </div>
        <div class="chat-header-text">
          <h2 class="chat-header-name">Public Lobby</h2>
          <div class="chat-header-status">
            <span class="header-status-dot"></span>
            <span>Live voice, photo &amp; studio chat for everyone</span>
          </div>
        </div>
      </div>
      <div class="chat-header-actions">
        <button type="button" class="btn btn-outline" onclick="openNewChatModal()">
          Direct chat
        </button>
      </div>
    <?php else: ?>
      <div class="chat-header-user">
        <a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($active_partner['username']) ?>" class="chat-header-avatar-link">
          <?= render_avatar($active_partner['username'], 46, '', $active_partner['avatar'] ?? null) ?>
        </a>
        <div class="chat-header-text">
          <h2 class="chat-header-name">
            <a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($active_partner['username']) ?>">
              <?= e($active_partner['display_name'] ?: $active_partner['username']) ?>
            </a>
            <?= verified_badge($active_partner['is_verified']) ?>
          </h2>
          <div class="chat-header-status">
            <?php
              $p_online = (!empty($active_partner['last_seen']) && (time() - strtotime($active_partner['last_seen'])) < 60);
            ?>
            <?php if ($p_online): ?>
              <span class="header-status-dot"></span>
              <span class="header-status-online">Online now</span>
            <?php else: ?>
              <span class="header-status-offline">last active <?= time_ago($active_partner['last_seen'] ?? null) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="chat-header-actions">
        <!-- Outline button "Call" (phone icon) -->
        <button type="button" class="btn btn-outline btn-call-trigger" onclick="startWebRtcCall('audio')" title="Start Voice Call" aria-label="Start Voice Call">
          <svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M17 13.8v2.5a1.7 1.7 0 0 1-1.8 1.7 16.5 16.5 0 0 1-7.2-2.6 16.2 16.2 0 0 1-5-5A16.5 16.5 0 0 1 .4 3.2 1.7 1.7 0 0 1 2.1 1.4h2.5a1.7 1.7 0 0 1 1.7 1.5 10.7 10.7 0 0 0 .6 2.4 1.7 1.7 0 0 1-.4 1.8L5.4 8.2a13.2 13.2 0 0 0 5 5l1.1-1.1a1.7 1.7 0 0 1 1.8-.4 10.7 10.7 0 0 0 2.4.6 1.7 1.7 0 0 1 1.3 1.5z"/>
          </svg>
          <span>Call</span>
        </button>

        <!-- Outline button "Video" (video icon) -->
        <button type="button" class="btn btn-outline btn-call-trigger" onclick="startWebRtcCall('video')" title="Start Video Call" aria-label="Start Video Call">
          <svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="2" y="4" width="12" height="12" rx="2"/>
            <polygon points="14 8 18 5 18 15 14 12"/>
          </svg>
          <span>Video</span>
        </button>

        <!-- Ghost button "Profile" -->
        <a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($active_partner['username']) ?>" class="btn btn-ghost" aria-label="View user profile">
          Profile
        </a>
      </div>
    <?php endif; ?>
  </header>

  <!-- Messages area (padding 28px 40px, column, 14px gap) -->
  <div class="chat-messages-area" id="chat-messages-area">
    <!-- Centered uppercase "TODAY" day divider (12px, muted, letter-spacing .06em) -->
    <div class="chat-day-divider">
      <span>TODAY</span>
    </div>

    <?php if ($mode === 'lobby'): ?>
      <!-- Load Older Button for Lobby -->
      <div class="chat-load-older-wrap">
        <button type="button" id="btn-load-older-lobby" class="btn btn-sm btn-outline" onclick="loadOlderLobbyMessages()">
          ↑ Load older messages
        </button>
      </div>

      <div id="lobby-messages-list" class="messages-stack">
        <?php foreach ($lobby_messages as $lm): ?>
          <?php 
            $is_mine = ((int)$lm['user_id'] === $my_id); 
            $can_del = ($is_mine || $is_admin);
            $is_call = (($lm['media_type'] ?? '') === 'call');
          ?>
          <?php if ($is_call): ?>
            <div class="call-pill-row" id="lobby-msg-<?= (int)$lm['id'] ?>">
              <?= render_chat_media($lm) ?>
            </div>
          <?php else: ?>
            <div class="message-bubble-wrapper <?= $is_mine ? 'mine' : 'theirs' ?>" id="lobby-msg-<?= (int)$lm['id'] ?>">
              <div class="message-sender-line">
                <a href="<?= BASE_URL ?>/chat.php?u=<?= urlencode($lm['username']) ?>" class="message-sender-name">
                  <?= e($lm['username']) ?>
                </a>
                <?= verified_badge($lm['is_verified']) ?>
                <span class="message-time-text"><?= time_ago($lm['created_at']) ?></span>
                <?php if ($can_del): ?>
                  <button type="button" class="comment-del" onclick="deleteLobbyMessage(<?= (int)$lm['id'] ?>)" title="Delete message" aria-label="Delete message">✕</button>
                <?php endif; ?>
              </div>
              <div class="message-bubble <?= $is_mine ? 'bubble-mine' : 'bubble-theirs' ?>">
                <?= render_chat_media($lm) ?>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <!-- Load Older Button for DM -->
      <div class="chat-load-older-wrap" id="dm-load-older-container" <?= count($dm_messages) >= 100 ? '' : 'style="display:none;"' ?>>
        <button type="button" id="btn-load-older-dm" class="btn btn-sm btn-outline" onclick="loadOlderDmMessages()">
          ↑ Load older messages
        </button>
      </div>

      <!-- DM Thread -->
      <div id="dm-messages-list" class="messages-stack">
        <?php if (empty($dm_messages)): ?>
          <div class="dm-empty-state" id="dm-empty-state">
            <div class="dm-empty-avatar-wrap">
              <?= render_avatar($active_partner['username'], 56, '', $active_partner['avatar'] ?? null) ?>
            </div>
            <h3 class="dm-empty-title">
              Chat with <?= e($active_partner['display_name'] ?: $active_partner['username']) ?>
            </h3>
            <p class="dm-empty-subtitle">Send a message, photo, or voice note below to start your conversation.</p>
          </div>
        <?php endif; ?>

        <?php 
          // Find the last sent message index to show "Seen"
          $last_mine_idx = -1;
          for ($i = count($dm_messages) - 1; $i >= 0; $i--) {
              if ((int)$dm_messages[$i]['from_id'] === $my_id && ($dm_messages[$i]['media_type'] ?? '') !== 'call') {
                  $last_mine_idx = $i;
                  break;
              }
          }
        ?>

        <?php foreach ($dm_messages as $idx => $dm): ?>
          <?php 
            $is_mine = ((int)$dm['from_id'] === $my_id); 
            $is_call = (($dm['media_type'] ?? '') === 'call');
            $can_del = ($is_mine || $is_admin);
          ?>
          <?php if ($is_call): ?>
            <!-- CALL EVENT PILL: small centered pill (white bg, 1px border, 999px radius, 32px circular icon holder #F4F1EA) -->
            <div class="call-pill-row" id="dm-msg-<?= (int)$dm['id'] ?>">
              <?= render_chat_media($dm) ?>
            </div>
          <?php else: ?>
            <div class="message-bubble-wrapper <?= $is_mine ? 'mine' : 'theirs' ?>" id="dm-msg-<?= (int)$dm['id'] ?>">
              <div class="message-bubble <?= $is_mine ? 'bubble-mine' : 'bubble-theirs' ?>">
                <?php if ($can_del): ?>
                  <button type="button" class="comment-del" onclick="deleteDmMessage(<?= (int)$dm['id'] ?>)" title="Delete message" aria-label="Delete message">✕</button>
                <?php endif; ?>
                <?= render_chat_media($dm) ?>
              </div>

              <?php if ($is_mine && $idx === $last_mine_idx): ?>
                <!-- Seen Indicator right under the last sent message -->
                <div class="dm-seen-status" id="dm-seen-status-<?= (int)$dm['id'] ?>">
                  <svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="3 8.5 6.5 12 13 4"/>
                  </svg>
                  <span>Seen</span>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Typing Indicator -->
  <div id="typing-indicator" class="typing-indicator" style="display:none;">
    <span class="live-dot" style="width:7px; height:7px;"></span>
    <span id="typing-text" style="font-size:12px; font-weight:600; color:var(--muted);"></span>
  </div>

  <!-- Attachment Preview Drawer -->
  <div id="chat-attachment-preview" class="chat-attachment-preview" style="display:none;">
    <div class="attachment-preview-box">
      <img id="chat-preview-img" src="" alt="Photo preview" style="display:none;">
      <div id="chat-preview-voice" style="display:none;" class="preview-voice-badge">
        <span class="voice-badge-icon">🎤</span>
        <div style="display:flex; flex-direction:column; line-height:1.2;">
          <strong style="font-size:12.5px;">Voice Note Ready</strong>
          <span id="voice-preview-duration" style="font-size:11px; color:var(--muted);">0:00</span>
        </div>
        <audio id="voice-preview-audio" controls style="height:28px; width:170px; margin-left:8px;"></audio>
      </div>
      <div class="attachment-meta">
        <span id="chat-preview-name" style="font-weight:600; font-size:13px;">file.jpg</span>
        <span id="chat-preview-size" style="color:var(--muted); font-size:11.5px;"></span>
      </div>
      <button type="button" class="btn-clear-attachment" onclick="clearChatAttachment()" title="Remove attachment" aria-label="Remove attachment">✕</button>
    </div>
  </div>

  <!-- Live Voice Recording Active Panel -->
  <div id="chat-recording-panel" class="chat-recording-panel" style="display:none;">
    <div class="rec-status-wrap">
      <span class="rec-dot"></span>
      <strong class="rec-label">REC</strong>
      <span id="rec-timer" class="rec-timer">00:00</span>
    </div>
    <div class="rec-waveforms">
      <span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span>
    </div>
    <div class="rec-actions">
      <button type="button" class="btn btn-sm btn-outline" onclick="cancelVoiceRecording()">✕ Cancel</button>
      <button type="button" class="btn btn-accent btn-sm" onclick="finishAndSendVoiceRecording()">✓ Send Voice</button>
    </div>
  </div>

  <!-- Composer (surface bg, top border, padding 16px 28px 20px) -->
  <form class="chat-composer-bar" id="chat-composer-form" onsubmit="handleSendChatMessage(event)">
    <!-- 48px square button: Attach Photo -->
    <label for="chat-image-input" class="composer-tool-btn" title="Send a photo" aria-label="Send a photo">
      <svg viewBox="0 0 20 20" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="2" y="5" width="16" height="12" rx="2"/>
        <circle cx="10" cy="11" r="3"/>
        <circle cx="14.5" cy="8" r="0.75" fill="currentColor"/>
      </svg>
    </label>
    <input type="file" id="chat-image-input" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" onchange="handleChatImageSelect(event)">

    <!-- 48px square button: Record Voice -->
    <button type="button" class="composer-tool-btn" id="btn-chat-voice" onclick="startVoiceRecording()" title="Record voice note" aria-label="Record voice note">
      <svg viewBox="0 0 20 20" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M10 2a2.5 2.5 0 0 0-2.5 2.5v5a2.5 2.5 0 0 0 5 0v-5A2.5 2.5 0 0 0 10 2z"/>
        <path d="M15 9.5a5 5 0 0 1-10 0"/>
        <line x1="10" y1="14.5" x2="10" y2="18"/>
        <line x1="7" y1="18" x2="13" y2="18"/>
      </svg>
    </button>
    <input type="file" id="chat-audio-file-input" accept="audio/*" style="display:none;" onchange="handleChatAudioFileSelect(event)">

    <!-- 48px text input: "Write a message" -->
    <div class="composer-input-wrap">
      <textarea 
        id="chat-textarea" 
        class="composer-textarea" 
        placeholder="Write a message..." 
        rows="1" 
        maxlength="500" 
        autocomplete="off"
        onkeydown="handleChatKeydown(event)"
        oninput="handleChatTyping()"
        aria-label="Write a message"
      ></textarea>
    </div>

    <!-- Accent Send Button (48px tall, #D8401F, white text) -->
    <button type="submit" class="composer-send-btn" id="chat-send-submit-btn" aria-label="Send message">
      <span>Send</span>
      <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="17.5" y1="2.5" x2="8.5" y2="11.5"/>
        <polygon points="17.5 2.5 12 17.5 8.5 11.5 2.5 8 17.5 2.5"/>
      </svg>
    </button>
  </form>

</main>

<!-- COLUMN 4: RIGHT PANEL (surface #FBFAF7, left border #E2DDD2, 264px, scrolls) -->
<?php include __DIR__ . '/includes/sidebar_right.php'; ?>

<!-- Lightbox Modal for Chat Photos -->
<div id="chat-lightbox-modal" class="chat-lightbox-modal" style="display:none;" onclick="closeChatLightbox(event)">
  <div class="chat-lightbox-content" onclick="event.stopPropagation()">
    <button type="button" class="chat-lightbox-close" onclick="closeChatLightbox()" title="Close image" aria-label="Close image">✕</button>
    <img id="chat-lightbox-img" src="" alt="Full view">
    <div class="chat-lightbox-bar">
      <a id="chat-lightbox-download" href="" target="_blank" download class="btn btn-sm btn-accent">Download photo</a>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/chat.js?v=<?= @filemtime(__DIR__ . '/assets/js/chat.js') ?: time() ?>"></script>

<?php
include __DIR__ . '/includes/footer.php';
?>
