<?php
/**
 * ============================================================================
 * NEXLY X — MIGUEL RECIO ARCHITECTURAL SWISS FOOTER
 * Hairline 1px borders, #edeef1 paper background, JetBrains Mono metadata
 * ============================================================================
 */
?>
<!-- Architectural Swiss Footer -->
<footer class="mr-footer" aria-labelledby="footer-heading">
  <h2 id="footer-heading" class="visually-hidden"><?= e($site_config['name']) ?> System Specifications and Colophon</h2>

  <div class="mr-wrap">
    <!-- Newsletter / Dispatch Banner -->
    <div class="mr-footer-newsletter">
      <div class="mr-fn-left">
        <div class="mr-tag mr-mono">
          <span class="mr-sq"></span>
          <span>DISPATCH &amp; RELEASES</span>
        </div>
        <h3 class="mr-display mr-fn-title">STAY IN SYNC WITH THE STUDIO.</h3>
        <p class="mr-fn-desc">
          One concise monthly release log covering voice engine upgrades, Khmer typography refinements, and WebRTC performance benchmarks.
        </p>
      </div>

      <form class="mr-fn-form" id="newsletter-form" action="#newsletter-form" method="POST">
        <div class="mr-fn-input-group">
          <input
            type="email"
            id="newsletter-email"
            name="email"
            class="mr-input mr-mono"
            placeholder="engineer@studio.kh"
            autocomplete="email"
            spellcheck="false"
            required
          >
          <button type="submit" class="mr-pill mr-pill-primary">
            <span>SUBSCRIBE ↗</span>
          </button>
        </div>
        <div class="mr-fn-note mr-mono">ZERO SPAM. ONE-CLICK UNSUBSCRIBE ANYTIME.</div>
        <div id="newsletter-status" class="mr-fn-status mr-mono" role="status" aria-live="polite"></div>
      </form>
    </div>

    <!-- 4-Column Architectural Links Grid -->
    <div class="mr-footer-grid">
      <!-- Col 0: Brand & Location -->
      <div class="mr-fcol mr-fcol-brand">
        <a href="<?= BASE_URL ?>/index.php" class="mr-brand" aria-label="Nexly X Home">
          <span class="mr-brand-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 28 28" fill="none">
              <rect width="28" height="28" rx="6" fill="#161618"/>
              <path d="M7 21V7L14 14V21H7Z" fill="#edeef1"/>
              <path d="M14 7L21 14V21H14V7Z" fill="#ff3a3a"/>
            </svg>
          </span>
          <span class="mr-brand-name">NEXLY X</span>
          <span class="mr-brand-tag mr-mono">v2.4</span>
        </a>
        <p class="mr-fbrand-text">
          Precision real-time voice, snap, and text chat studio. Built with raw performance, bilingual Khmer + Latin typography, and architectural clarity.
        </p>
        <div class="mr-fmeta mr-mono">
          <div>LAT: 11.5564° N · LON: 104.9282° E</div>
          <div>PHNOM PENH · KINGDOM OF CAMBODIA</div>
        </div>
      </div>

      <!-- Col 1: Product -->
      <div class="mr-fcol">
        <h4 class="mr-fcol-title mr-mono">[ 01 ] PRODUCT</h4>
        <ul class="mr-flinks">
          <li><a href="<?= BASE_URL ?>/chat.php">Live Chat Studio</a></li>
          <li><a href="#interactive-demo">Real-Time Playground</a></li>
          <li><a href="#capabilities">48kHz Voice Engine</a></li>
          <li><a href="#pricing">Studio Tier Pricing</a></li>
        </ul>
      </div>

      <!-- Col 2: Changelog -->
      <div class="mr-fcol">
        <h4 class="mr-fcol-title mr-mono">[ 02 ] CHANGELOG</h4>
        <ul class="mr-flinks">
          <li><a href="#latest-shipped">v2.4 — Voice Notes 2.0</a></li>
          <li><a href="#latest-shipped">v2.3 — Sub-20ms SSE Stream</a></li>
          <li><a href="#latest-shipped">v2.2 — Khmer Type Engine</a></li>
          <li><a href="#showcase">Interface Viewports</a></li>
        </ul>
      </div>

      <!-- Col 3: Navigation -->
      <div class="mr-fcol">
        <h4 class="mr-fcol-title mr-mono">[ 03 ] NAVIGATION</h4>
        <ul class="mr-flinks">
          <?php if (!empty($current_user)): ?>
            <li><a href="<?= BASE_URL ?>/chat.php">Open My Chats</a></li>
            <li><a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($current_user['username']) ?>">Profile (@<?= e($current_user['username']) ?>)</a></li>
            <li><a href="<?= BASE_URL ?>/settings.php">Account Settings</a></li>
          <?php else: ?>
            <li><a href="<?= BASE_URL ?>/login.php">Sign In</a></li>
            <li><a href="<?= BASE_URL ?>/register.php">Create Free Account</a></li>
            <li><a href="<?= BASE_URL ?>/chat.php">Guest Preview</a></li>
          <?php endif; ?>
          <li><a href="#faq">Questions &amp; Specs</a></li>
        </ul>
      </div>

      <!-- Col 4: System Specs -->
      <div class="mr-fcol">
        <h4 class="mr-fcol-title mr-mono">[ 04 ] SYSTEM SPECS</h4>
        <ul class="mr-flinks mr-mono" style="font-size:12px; color:var(--paper-mut);">
          <li>STACK // PHP 8.2 + MYSQL</li>
          <li>ENGINE // SERVER-SENT EVENTS</li>
          <li>VOICE // 48KHZ OPUS LOSSLESS</li>
          <li>GRID // SWISS ARCHITECTURAL</li>
        </ul>
      </div>
    </div>

    <!-- Colophon / Copyright Bottom Bar -->
    <div class="mr-footer-bottom mr-mono">
      <div class="mr-fb-left">
        <span>&copy; <?= date('Y') ?> NEXLY X STUDIO. ALL RIGHTS RESERVED.</span>
        <span>·</span>
        <span>DESIGNED WITH PRECISION</span>
      </div>
      <div class="mr-fb-right">
        <span>PALETTE: <span style="color:var(--infra); font-weight:700;">#FF3A3A</span> / #161618 / #EDEEF1</span>
        <span>·</span>
        <a href="#site-navbar" class="mr-back-to-top">[ BACK TO TOP ↑ ]</a>
      </div>
    </div>
  </div>
</footer>

<?php if (function_exists('is_logged_in') && is_logged_in()): ?>
  <!-- Native Smartphone Calling Modals & WebRTC Engine -->
  <?php require __DIR__ . '/call_modals.php'; ?>
<?php endif; ?>
