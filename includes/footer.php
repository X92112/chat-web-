<?php
// Common Footer — Start New Chat Modal & Toast
$db_footer = get_db();
$my_uid_footer = isset($current_user['id']) ? (int)$current_user['id'] : 0;
$all_chat_users = [];
if ($my_uid_footer > 0) {
    $u_stmt_f = $db_footer->prepare("
        SELECT id, username, display_name, bio, status_mood, avatar, is_verified, last_seen
        FROM users
        WHERE is_banned = 0 AND id != :my_id
        ORDER BY (last_seen >= (NOW() - INTERVAL 60 SECOND)) DESC, is_verified DESC, username ASC
        LIMIT 100
    ");
    $u_stmt_f->execute(['my_id' => $my_uid_footer]);
    $all_chat_users = $u_stmt_f->fetchAll();
}
?>
  </div> <!-- End layout-container -->

  <!-- Native Light-Dismiss Dialog: Start New Direct Chat -->
  <dialog id="new-chat-dialog" class="brutalist-dialog" closedby="any" aria-labelledby="new-chat-dialog-title">
    <div class="dialog-header">
      <h2 id="new-chat-dialog-title">Start Direct Chat</h2>
      <form method="dialog">
        <button type="submit" class="dialog-close-btn" aria-label="Close dialog">Close</button>
      </form>
    </div>
    <div class="dialog-body" style="display:flex; flex-direction:column; gap:12px;">
      <div class="form-group">
        <label for="new-chat-search-input" class="form-label">Find a member</label>
        <input type="search" id="new-chat-search-input" class="form-control" placeholder="Search by username or name..." autocomplete="off" oninput="filterNewChatMembers(this.value)">
      </div>

      <div class="user-list-modal" id="new-chat-members-list" style="max-height:340px; overflow-y:auto;">
        <a href="<?= BASE_URL ?>/chat.php" class="modal-user-row" style="text-decoration:none;">
          <div class="modal-user-link">
            <span class="avatar-circle" style="width:38px; height:38px; background:var(--blue); color:#FFFFFF; font-size:16px;">#</span>
            <div>
              <div style="font-weight:700; color:#FFFFFF;"># Public Lobby</div>
              <div style="font-size:12px; color:var(--muted);">Join the live group channel for everyone</div>
            </div>
          </div>
          <span class="btn btn-sm btn-yellow">Enter</span>
        </a>

        <?php foreach ($all_chat_users as $cu): ?>
          <?php $cu_online = !empty($cu['last_seen']) && (time() - strtotime($cu['last_seen'])) < 60; ?>
          <a href="<?= BASE_URL ?>/chat.php?u=<?= urlencode($cu['username']) ?>" class="modal-user-row new-chat-member-item" data-search="<?= e(strtolower($cu['username'] . ' ' . ($cu['display_name'] ?? '') . ' ' . ($cu['bio'] ?? ''))) ?>" style="text-decoration:none;">
            <div class="modal-user-link">
              <div style="position:relative; flex-shrink:0;">
                <?= render_avatar($cu['username'], 38, '', $cu['avatar'] ?? null) ?>
                <?php if ($cu_online): ?>
                  <span class="live-dot" style="position:absolute; bottom:0; right:0; width:9px; height:9px; border:1.5px solid #1B1E24;"></span>
                <?php endif; ?>
              </div>
              <div>
                <div style="font-weight:700; color:#FFFFFF; display:flex; align-items:center; gap:4px;">
                  <span><?= e($cu['display_name'] ?: $cu['username']) ?></span>
                  <?= verified_badge($cu['is_verified']) ?>
                </div>
                <div style="font-size:12px; color:var(--muted);">
                  @<?= e($cu['username']) ?><?= !empty($cu['status_mood']) ? ' · ' . e($cu['status_mood']) : '' ?>
                </div>
              </div>
            </div>
            <span class="btn btn-sm">Message</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </dialog>

  <!-- Toast Notification -->
  <div id="snap-toast" class="snap-toast" role="status" aria-live="polite"></div>

  <!-- =====================================================================
       NATIVE SMARTPHONE CALL OVERLAYS (EXACT MATCH FOR USER SCREENSHOT)
       Inline styles included to guarantee immediate native render regardless of browser cache
       ===================================================================== -->

  <!-- JavaScript App Core -->
  <script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= @filemtime(__DIR__ . '/../assets/js/app.js') ?: time() ?>"></script>

  <!-- Native Smartphone Calling Modals & Media Elements -->
  <?php require __DIR__ . '/call_modals.php'; ?>
</body>
</html>
