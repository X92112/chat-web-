<?php
/**
 * ============================================================================
 * NEXLY X — MIGUEL RECIO ARCHITECTURAL TOP MARQUEE & STICKY NAVBAR
 * Clean Swiss architectural design system: #edeef1 paper, #161618 ink, #ff3a3a
 * ============================================================================
 */
?>
<!-- Dynamic Scroll Progress Indicator -->
<div class="mr-scroll-progress" id="scroll-progress-bar" role="progressbar" aria-label="Reading progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>

<!-- Top Architectural Hairline Marquee -->
<div class="mr-top-marquee" role="region" aria-label="Platform announcement">
  <div class="mr-marquee-track">
    <?php for ($loop = 0; $loop < 2; $loop++): ?>
      <div class="mr-marquee-group" <?= $loop === 1 ? 'aria-hidden="true"' : '' ?>>
        <span class="mr-marquee-item">NEXLY X</span>
        <span class="mr-marquee-sep">✱</span>
        <span class="mr-marquee-item">ZERO-LATENCY STUDIO CHAT</span>
        <span class="mr-marquee-sep">✱</span>
        <span class="mr-marquee-item">48KHZ LOSSLESS VOICE NOTES</span>
        <span class="mr-marquee-sep">✱</span>
        <span class="mr-marquee-item">SUB-20MS SSE STREAM</span>
        <span class="mr-marquee-sep">✱</span>
        <span class="mr-marquee-item">BILINGUAL KHMER &amp; ENGLISH</span>
        <span class="mr-marquee-sep">✱</span>
        <span class="mr-marquee-item">PUBLIC LOBBY &amp; DIRECT THREADS</span>
        <span class="mr-marquee-sep">✱</span>
        <span class="mr-marquee-item">AVAILABLE WORLDWIDE</span>
        <span class="mr-marquee-sep">✱</span>
      </div>
    <?php endfor; ?>
  </div>
</div>

<!-- Sticky Minimal Architectural Navbar -->
<header class="mr-navbar" id="site-navbar">
  <div class="mr-navbar-inner mr-wrap">
    <!-- Brand / Logo -->
    <a href="<?= BASE_URL ?>/index.php" class="mr-brand" aria-label="Nexly X Home">
      <span class="mr-brand-icon" aria-hidden="true">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
          <rect width="28" height="28" rx="6" fill="#161618"/>
          <path d="M7 21V7L14 14V21H7Z" fill="#edeef1"/>
          <path d="M14 7L21 14V21H14V7Z" fill="#ff3a3a"/>
        </svg>
      </span>
      <span class="mr-brand-name">NEXLY X</span>
      <span class="mr-brand-tag mr-mono">v2.4</span>
    </a>

    <!-- Center Status Pill -->
    <div class="mr-nav-status mr-mono">
      <span class="mr-status-dot"></span>
      <span>12 ONLINE · SUB-20MS SSE</span>
    </div>

    <!-- Right Navigation & Action -->
    <div class="mr-nav-right">
      <nav class="mr-nav-links mr-mono" aria-label="Primary navigation">
        <a href="#capabilities" class="mr-nav-link">[ 01 ] FEATURES</a>
        <a href="#interactive-demo" class="mr-nav-link">[ 02 ] PLAYGROUND</a>
        <a href="#showcase" class="mr-nav-link">[ 03 ] SHOWCASE</a>
        <a href="#pricing" class="mr-nav-link">[ 04 ] PRICING</a>
        <a href="#faq" class="mr-nav-link">[ 05 ] FAQ</a>
      </nav>

      <?php if (!empty($current_user)): ?>
        <a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($current_user['username']) ?>" class="mr-user-pill mr-mono">
          @<?= e($current_user['username']) ?>
        </a>
        <a href="<?= BASE_URL ?>/chat.php" class="mr-btn-action mr-mono">
          <span>ENTER STUDIO ↗</span>
        </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php" class="mr-login-btn mr-mono">LOGIN</a>
        <a href="<?= BASE_URL ?>/chat.php" class="mr-btn-action mr-mono">
          <span>LIVE CHAT ↗</span>
        </a>
      <?php endif; ?>

      <!-- Mobile Menu Trigger -->
      <button type="button" class="mr-mobile-trigger" id="mobile-drawer-open" aria-label="Open menu" aria-expanded="false">
        <span></span>
        <span></span>
      </button>
    </div>
  </div>
</header>

<!-- Mobile Drawer -->
<dialog class="mr-mobile-drawer" id="mobile-nav-drawer" aria-label="Mobile Navigation">
  <div class="mr-drawer-sheet" id="mobile-drawer-sheet">
    <div class="mr-drawer-top">
      <div class="mr-brand">
        <span class="mr-brand-name">NEXLY X</span>
        <span class="mr-brand-tag mr-mono">v2.4</span>
      </div>
      <button type="button" class="mr-drawer-close mr-mono" id="mobile-drawer-close" aria-label="Close menu">
        [ CLOSE × ]
      </button>
    </div>

    <nav class="mr-drawer-links mr-mono">
      <a href="#capabilities" class="mr-drawer-link">[ 01 ] ARCHITECTURE &amp; FEATURES</a>
      <a href="#interactive-demo" class="mr-drawer-link">[ 02 ] LIVE STUDIO PLAYGROUND</a>
      <a href="#latest-shipped" class="mr-drawer-link">[ 03 ] CHANGELOG &amp; RELEASES</a>
      <a href="#showcase" class="mr-drawer-link">[ 04 ] INTERFACE TOUR</a>
      <a href="#pricing" class="mr-drawer-link">[ 05 ] STUDIO PLANS &amp; PRICING</a>
      <a href="#faq" class="mr-drawer-link">[ 06 ] DOCUMENTATION &amp; FAQ</a>
      <a href="<?= BASE_URL ?>/chat.php" class="mr-drawer-link mr-drawer-highlight">➔ OPEN LIVE STUDIO LOBBY</a>
    </nav>

    <div class="mr-drawer-bottom">
      <a href="<?= BASE_URL ?>/chat.php" class="mr-btn-action mr-mono" style="width:100%; text-align:center;">
        <span>ENTER LIVE CHAT STUDIO ↗</span>
      </a>
    </div>
  </div>
</dialog>
