<?php
/**
 * ============================================================================
 * NEXLY — REUSABLE NEWSLETTER, 4-COLUMN FOOTER & FLOATING PILL PARTIAL
 * Sections 12 & 13 of the Annnimate-style Page Structure
 * ============================================================================
 */
?>
<!-- ==========================================================================
     12. NEWSLETTER SIGNUP FIELD + 4-COLUMN FOOTER + SOCIAL ICONS + COPYRIGHT
     ========================================================================== -->
<footer class="an-footer" aria-labelledby="footer-heading">
  <h2 id="footer-heading" class="visually-hidden"><?= e($site_config['name']) ?> Footer and Newsletter</h2>

  <div class="an-container">
    <!-- Newsletter Signup Card -->
    <div class="an-newsletter-card an-reveal">
      <div class="an-newsletter-copy">
        <span class="an-eyebrow">RELEASE NOTES &amp; CHANGELOG</span>
        <h3 class="an-newsletter-title">Stay in the loop when new motion drops.</h3>
        <p class="an-newsletter-sub">One concise dispatch every month covering voice engine upgrades, Khmer typography refinements, and studio templates.</p>
      </div>

      <form class="an-newsletter-form" id="newsletter-form" action="#newsletter-form" method="POST" aria-labelledby="newsletter-label">
        <div class="an-field-group">
          <label for="newsletter-email" id="newsletter-label" class="an-field-label">
            Work or student email <span aria-hidden="true">*</span>
          </label>
          <div class="an-newsletter-input-row">
            <input
              type="email"
              id="newsletter-email"
              name="email"
              class="an-input"
              placeholder="you@studio.kh"
              autocomplete="email"
              autocorrect="off"
              autocapitalize="none"
              spellcheck="false"
              enterkeyhint="send"
              aria-describedby="newsletter-hint"
              required
            >
            <button type="submit" class="an-btn an-btn-mint an-magnetic">
              <span class="an-btn-slot">
                <span class="an-btn-label">Subscribe</span>
                <span class="an-btn-label" aria-hidden="true">Subscribe ↗</span>
              </span>
            </button>
          </div>
          <span id="newsletter-hint" class="an-field-hint">No spam. Unsubscribe in one click anytime.</span>
          <div id="newsletter-status" class="an-newsletter-status" role="status" aria-live="polite"></div>
        </div>
      </form>
    </div>

    <!-- 4-Column Footer Links Grid -->
    <div class="an-footer-grid">
      <!-- Brand Column -->
      <div class="an-footer-brand-col">
        <a href="<?= BASE_URL ?>/index.php" class="an-logo">
          <span class="an-logo-mark" aria-hidden="true"><span class="an-logo-core"></span></span>
          <span class="an-logo-text"><?= e($site_config['name']) ?></span>
        </a>
        <p class="an-footer-tagline">
          Motion-driven real-time voice, snap, and text chat studio crafted for creators, students, and fast-moving teams.
        </p>
        <!-- Social Icons -->
        <div class="an-social-row" aria-label="Social media links">
          <a href="<?= BASE_URL ?>/chat.php" class="an-social-btn" aria-label="Nexly Global Lobby">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          </a>
          <a href="https://github.com" target="_blank" rel="noopener" class="an-social-btn" aria-label="GitHub">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
          </a>
          <a href="https://x.com" target="_blank" rel="noopener" class="an-social-btn" aria-label="X (Twitter)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4l11.733 16h4.267l-11.733 -16z"/><path d="M4 20l6.768 -6.768m2.46 -2.46l6.772 -6.772"/></svg>
          </a>
          <a href="https://discord.com" target="_blank" rel="noopener" class="an-social-btn" aria-label="Community">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><path d="M7.5 7.5c3.5-1 5.5-1 9 0"/><path d="M7 16.5c3.5 1 6.5 1 10 0"/><path d="M15.5 17c0 1 1.5 3 2 3 1.5 0 2.833-1.667 3.5-3 .667-1.667.5-5.833-1.5-11.5-1.457-1.015-3-1.34-4.5-1.5l-1 2.5"/><path d="M8.5 17c0 1-1.356 3-1.832 3-1.429 0-2.698-1.667-3.333-3-.635-1.667-.476-5.833 1.428-11.5C6.151 4.485 7.5 4.16 9 4l1 2.5"/></svg>
          </a>
        </div>
      </div>

      <!-- Column 1: Product -->
      <div class="an-footer-col">
        <h4 class="an-footer-col-title">Product</h4>
        <ul class="an-footer-links">
          <li><a href="<?= BASE_URL ?>/chat.php">Global Chat Lobby</a></li>
          <li><a href="#interactive-demo">Interactive Customizer</a></li>
          <li><a href="#benefits">48kHz Voice Engine</a></li>
          <li><a href="#pricing">Pricing &amp; Plans</a></li>
        </ul>
      </div>

      <!-- Column 2: Features -->
      <div class="an-footer-col">
        <h4 class="an-footer-col-title">Shipped</h4>
        <ul class="an-footer-links">
          <li><a href="#latest-shipped">Live Voice Notes 2.0</a></li>
          <li><a href="#latest-shipped">Instant HD Snaps</a></li>
          <li><a href="#latest-shipped">Khmer + Latin Type</a></li>
          <li><a href="#showcase">Interface Showcase</a></li>
        </ul>
      </div>

      <!-- Column 3: Account -->
      <div class="an-footer-col">
        <h4 class="an-footer-col-title">Studio</h4>
        <ul class="an-footer-links">
          <?php if (!empty($current_user)): ?>
            <li><a href="<?= BASE_URL ?>/chat.php">Open Chat Studio</a></li>
            <li><a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($current_user['username']) ?>">My Profile</a></li>
            <li><a href="<?= BASE_URL ?>/settings.php">Settings</a></li>
          <?php else: ?>
            <li><a href="<?= BASE_URL ?>/login.php">Sign In</a></li>
            <li><a href="<?= BASE_URL ?>/register.php">Create Account</a></li>
            <li><a href="<?= BASE_URL ?>/chat.php">Guest Preview</a></li>
          <?php endif; ?>
          <li><a href="#faq">Documentation &amp; FAQ</a></li>
        </ul>
      </div>

      <!-- Column 4: Resources -->
      <div class="an-footer-col">
        <h4 class="an-footer-col-title">Stack &amp; Specs</h4>
        <ul class="an-footer-links">
          <li><a href="#faq">PHP + MySQL + SSE</a></li>
          <li><a href="#faq">GSAP + Lenis Motion</a></li>
          <li><a href="#faq">WCAG Contrast Ready</a></li>
          <li><a href="#faq">Reduced-Motion Safe</a></li>
        </ul>
      </div>
    </div>

    <!-- Bottom Copyright Row -->
    <div class="an-footer-bottom">
      <p>&copy; <?= date('Y') ?> <?= e($site_config['name']) ?> Studio. Crafted with human precision in Phnom Penh · សួស្តី.</p>
      <div class="an-footer-meta">
        <span>Accent: <code>#b3fca0</code></span>
        <span>·</span>
        <span>Surface: <code>#141314</code></span>
      </div>
    </div>
  </div>
</footer>

<!-- ==========================================================================
     13. FLOATING BOTTOM PILL ("Just shipped: [feature] · yesterday")
     Slides in after a short delay; includes link + accessible dismiss button.
     ========================================================================== -->
<aside class="an-floating-pill" id="floating-shipped-pill" aria-label="Latest release notification" hidden>
  <span class="an-pill-pulse" aria-hidden="true"></span>
  <a href="<?= e($site_config['floating_pill']['url']) ?>" class="an-pill-link">
    <?= e($site_config['floating_pill']['label']) ?>
    <span class="an-pill-arrow" aria-hidden="true">↗</span>
  </a>
  <button type="button" class="an-pill-close" id="floating-pill-close" aria-label="Dismiss announcement">
    ✕
  </button>
</aside>

<!-- Cursor-follow label for Section 6 ("Just shipped / Latest" cards) -->
<div class="an-cursor-pill" id="cursor-follow-pill" aria-hidden="true">
  <span>Open Live ↗</span>
</div>

<?php if (function_exists('is_logged_in') && is_logged_in()): ?>
  <!-- Native Smartphone Calling Modals & WebRTC Engine -->
  <?php require __DIR__ . '/call_modals.php'; ?>
<?php endif; ?>
