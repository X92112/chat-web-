<?php
/**
 * ============================================================================
 * NEXLY — REUSABLE TOP MARQUEE & STICKY NAVBAR PARTIAL
 * Sections 1 & 2 of the Annnimate-style Page Structure
 * ============================================================================
 */
?>
<!-- ==========================================================================
     1. SLIM TOP BANNER / INFINITE MARQUEE ANNOUNCEMENT
     ========================================================================== -->
<div class="an-top-marquee" role="region" aria-label="Latest platform announcements">
  <div class="an-marquee-track" id="top-marquee-track">
    <?php for ($loop = 0; $loop < 2; $loop++): ?>
      <div class="an-marquee-group" <?= $loop === 1 ? 'aria-hidden="true"' : '' ?>>
        <?php foreach ($site_config['marquee_items'] as $item): ?>
          <span class="an-marquee-item">
            <span class="an-marquee-dot" aria-hidden="true"></span>
            <span><?= e($item) ?></span>
          </span>
        <?php endforeach; ?>
      </div>
    <?php endfor; ?>
  </div>
</div>

<!-- ==========================================================================
     2. STICKY MINIMAL NAVBAR (Logo left · Links center · Login + Mint CTA right)
     ========================================================================== -->
<header class="an-navbar-wrap" id="site-navbar">
  <div class="an-navbar-inner">
    <!-- Left: Brand Logo -->
    <a href="<?= BASE_URL ?>/index.php" class="an-logo" aria-current="page">
      <span class="an-logo-mark" aria-hidden="true">
        <span class="an-logo-core"></span>
      </span>
      <span class="an-logo-text"><?= e($site_config['name']) ?></span>
      <span class="an-logo-tag">2.4</span>
    </a>

    <!-- Center: Primary Navigation Links -->
    <nav class="an-nav-center" aria-label="Primary">
      <ul class="an-nav-list">
        <li><a href="#interactive-demo" class="an-nav-link">Explore</a></li>
        <li><a href="#benefits" class="an-nav-link">Features</a></li>
        <li><a href="#latest-shipped" class="an-nav-link">Shipped</a></li>
        <li><a href="#pricing" class="an-nav-link">Pricing</a></li>
        <li><a href="#faq" class="an-nav-link">Docs &amp; FAQ</a></li>
        <li>
          <a href="<?= BASE_URL ?>/chat.php" class="an-nav-link an-nav-live">
            <span class="an-live-dot" aria-hidden="true"></span>
            Live Chat
          </a>
        </li>
      </ul>
    </nav>

    <!-- Right: Login Link + Mint "Get started" Button + Mobile Trigger -->
    <div class="an-nav-right">
      <?php if (!empty($current_user)): ?>
        <a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($current_user['username']) ?>" class="an-login-link">
          @<?= e($current_user['username']) ?>
        </a>
        <a href="<?= BASE_URL ?>/chat.php" class="an-btn an-btn-mint an-magnetic">
          <span class="an-btn-slot">
            <span class="an-btn-label">Open Studio</span>
            <span class="an-btn-label" aria-hidden="true">Open Studio ↗</span>
          </span>
        </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php" class="an-login-link">Login</a>
        <a href="<?= BASE_URL ?>/register.php" class="an-btn an-btn-mint an-magnetic">
          <span class="an-btn-slot">
            <span class="an-btn-label">Get started</span>
            <span class="an-btn-label" aria-hidden="true">Get started ↗</span>
          </span>
        </a>
      <?php endif; ?>

      <!-- Mobile Menu Trigger Button -->
      <button
        type="button"
        class="an-mobile-trigger"
        id="mobile-drawer-open"
        aria-label="Open navigation menu"
        aria-expanded="false"
        aria-controls="mobile-nav-drawer"
      >
        <span class="an-hamburger-line"></span>
        <span class="an-hamburger-line"></span>
      </button>
    </div>
  </div>
</header>

<!-- ==========================================================================
     ACCESSIBLE MOBILE NAVIGATION DRAWER (<dialog> modal top-layer)
     ========================================================================== -->
<dialog class="an-mobile-drawer" id="mobile-nav-drawer" aria-label="Mobile navigation menu">
  <div class="an-drawer-sheet" id="mobile-drawer-sheet">
    <div class="an-drawer-header">
      <a href="<?= BASE_URL ?>/index.php" class="an-logo">
        <span class="an-logo-mark" aria-hidden="true"><span class="an-logo-core"></span></span>
        <span class="an-logo-text"><?= e($site_config['name']) ?></span>
      </a>
      <button type="button" class="an-drawer-close" id="mobile-drawer-close" aria-label="Close menu" autofocus>
        ✕
      </button>
    </div>

    <nav class="an-drawer-nav" aria-label="Mobile">
      <ul class="an-drawer-list">
        <li><a href="#interactive-demo" class="an-drawer-link">Explore Interactive Demo</a></li>
        <li><a href="#benefits" class="an-drawer-link">Features &amp; Benefits</a></li>
        <li><a href="#latest-shipped" class="an-drawer-link">Just Shipped</a></li>
        <li><a href="#showcase" class="an-drawer-link">Studio Showcase</a></li>
        <li><a href="#pricing" class="an-drawer-link">Pricing Plans</a></li>
        <li><a href="#faq" class="an-drawer-link">Docs &amp; FAQ</a></li>
        <li><a href="<?= BASE_URL ?>/chat.php" class="an-drawer-link an-drawer-highlight">Open Live Chat Lobby ↗</a></li>
      </ul>
    </nav>

    <div class="an-drawer-footer">
      <?php if (!empty($current_user)): ?>
        <a href="<?= BASE_URL ?>/chat.php" class="an-btn an-btn-mint" style="width:100%; justify-content:center;">
          Open Live Chat (@<?= e($current_user['username']) ?>)
        </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php" class="an-btn an-btn-outline" style="width:100%; justify-content:center;">
          Login to account
        </a>
        <a href="<?= BASE_URL ?>/register.php" class="an-btn an-btn-mint" style="width:100%; justify-content:center;">
          Get started free
        </a>
      <?php endif; ?>
    </div>
  </div>
</dialog>
