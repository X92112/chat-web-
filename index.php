<?php
/**
 * ============================================================================
 * NEXLY — AWARD-STYLE MOTION LANDING PAGE (index.php)
 * Inspired by annnimate.com · Mint #b3fca0 + Near-Black #141314 + Off-White #f5f5f3
 * ============================================================================
 * SECTIONS IN EXACT ORDER:
 *  1. Slim top marquee banner              (includes/landing_navbar.php)
 *  2. Sticky minimal navbar + mobile menu  (includes/landing_navbar.php)
 *  3. Hero + Word Reveal + Featured Logos
 *  4. Interactive Live Demo & Customizer
 *  5. Benefits Grid (6 cards)
 *  6. "Just Shipped / Latest" Gallery Grid (6 cards + cursor-follow label)
 *  7. Infinite Horizontal Showcase Marquee
 *  8. Testimonials (3 quote cards)
 *  9. Pricing (Monthly/Yearly toggle + 3 cards + rolling numbers)
 * 10. FAQ Accordion (smooth height animation)
 * 11. Final Big CTA Section
 * 12. Newsletter + 4-Column Footer         (includes/landing_footer.php)
 * 13. Floating "Just shipped" Bottom Pill  (includes/landing_footer.php)
 * ============================================================================
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$current_user = current_user();
require_once __DIR__ . '/includes/landing_data.php';

// Split hero headline into spans for GSAP word-by-word reveal
$hero_words = explode(' ', $site_config['hero_headline']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- MANDATORY: Declare support for both light and dark themes to prevent FOUC -->
  <meta name="color-scheme" content="light dark">
  <meta name="theme-color" content="#b3fca0">
  <meta name="description" content="<?= e($site_config['hero_subheadline']) ?>">
  <title><?= e($site_config['name']) ?> — Motion-Driven Live Voice, Snap &amp; Text Chat Studio</title>

  <!-- Google Fonts: Inter + Noto Sans Khmer fallback -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Khmer:wght@400;500;700&display=swap" rel="stylesheet">

  <!-- Main Landing Stylesheet -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/landing.css">
  
  <?php if ($current_user): ?>
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
      window.CURRENT_USER_ID = <?= (int)$current_user['id'] ?>;
      window.CURRENT_USER_NAME = <?= json_encode($current_user['username']) ?>;
      window.CURRENT_USER_VERIFIED = <?= !empty($current_user['is_verified']) ? 'true' : 'false' ?>;
      window.CURRENT_USER_ADMIN = <?= !empty($current_user['is_admin']) ? 'true' : 'false' ?>;
      window.CSRF_TOKEN = "<?= csrf_token() ?>";
      window.LATEST_EVENT_ID = <?= $init_max_event_id ?>;
    </script>
  <?php endif; ?>
</head>
<body class="an-body">

  <?php
  // SECTIONS 1 & 2: Top Looping Marquee + Sticky Minimal Navbar + Mobile Drawer
  require __DIR__ . '/includes/landing_navbar.php';
  ?>

  <main id="main-content">

    <!-- ======================================================================
         3. HERO SECTION (Big Headline · 1-Line Sub · 2 Buttons · Logos Row)
         ====================================================================== -->
    <section class="an-hero" aria-labelledby="hero-title">
      <!-- Dynamic Ambient Glowing Mesh Orbs (Transforms & Continuous Rotation) -->
      <div class="an-hero-ambient" aria-hidden="true">
        <div class="an-hero-orb an-hero-orb-1"></div>
        <div class="an-hero-orb an-hero-orb-2"></div>
        <div class="an-hero-orb an-hero-orb-3"></div>
      </div>

      <div class="an-container" style="position:relative; z-index:2;">
        <!-- 3D Floating Interactive Widgets (Levitation Animation + Parallax Depth) -->
        <div class="an-hero-floating-wrap" aria-hidden="true">
          <!-- Floating Left Badge: Live Voice Room -->
          <div class="an-hero-float-badge an-float-left an-tilt-widget">
            <div class="an-float-avatar-row">
              <span class="an-mini-avatar" style="background:#b3fca0; color:#141314;">RT</span>
              <span class="an-mini-avatar" style="background:#a5f3fc; color:#141314;">SL</span>
              <span class="an-mini-avatar" style="background:#f5d0fe; color:#141314;">DA</span>
              <span class="an-live-pulse-badge"></span>
            </div>
            <div class="an-float-info">
              <span class="an-float-title">Lobby Room #1</span>
              <span class="an-float-sub">12 voices live now</span>
            </div>
            <div class="an-mini-equalizer">
              <span class="an-eq-bar"></span>
              <span class="an-eq-bar"></span>
              <span class="an-eq-bar"></span>
              <span class="an-eq-bar"></span>
            </div>
          </div>

          <!-- Floating Right Badge: 48kHz HD Audio -->
          <div class="an-hero-float-badge an-float-right an-tilt-widget">
            <div class="an-float-icon-box">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                <line x1="12" y1="19" x2="12" y2="23"/>
                <line x1="8" y1="23" x2="16" y2="23"/>
              </svg>
            </div>
            <div class="an-float-info">
              <span class="an-float-title">48kHz HD Audio</span>
              <span class="an-float-sub">Lossless Voice Engine</span>
            </div>
            <span class="an-shipped-tag" style="padding:0.2rem 0.55rem; font-size:0.7rem;">14ms SSE</span>
          </div>
        </div>

        <div class="an-eyebrow an-hero-fade">
          <span class="an-live-dot" aria-hidden="true"></span>
          <span><?= e($site_config['badge']) ?></span>
        </div>

        <h1 class="an-hero-headline" id="hero-title">
          <?php foreach ($hero_words as $idx => $word): ?>
            <?php $is_highlight = in_array(strtolower(trim($word, '.')), ['motion-driven', 'live', 'fast']); ?>
            <span class="an-hero-word <?= $is_highlight ? 'an-hero-accent' : '' ?>"><?= e($word) ?></span>
          <?php endforeach; ?>
        </h1>

        <p class="an-hero-sub an-hero-fade">
          <?= e($site_config['hero_subheadline']) ?>
        </p>

        <div class="an-hero-actions an-hero-fade">
          <!-- Primary Filled Mint Button -->
          <a href="<?= e($site_config['cta_primary_url']) ?>" class="an-btn an-btn-mint an-magnetic">
            <span class="an-btn-slot">
              <span class="an-btn-label"><?= e($site_config['cta_primary_text']) ?></span>
              <span class="an-btn-label" aria-hidden="true"><?= e($site_config['cta_primary_text']) ?> ↗</span>
            </span>
          </a>

          <!-- Secondary Outline Button -->
          <a href="<?= e($site_config['cta_secondary_url']) ?>" class="an-btn an-btn-outline an-magnetic">
            <span class="an-btn-slot">
              <span class="an-btn-label"><?= e($site_config['cta_secondary_text']) ?></span>
              <span class="an-btn-label" aria-hidden="true"><?= e($site_config['cta_secondary_text']) ?> →</span>
            </span>
          </a>
        </div>

        <!-- "Used by / Featured in" Logos Row -->
        <div class="an-logos-strip an-hero-fade">
          <p class="an-logos-caption">Used daily by design cohorts, student labs &amp; creative studios</p>
          <ul class="an-logos-row" aria-label="Featured studio partners">
            <?php foreach ($site_config['featured_logos'] as $logo_name): ?>
              <li class="an-logo-pill"><?= e($logo_name) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         4. INTERACTIVE DEMO SECTION (Live Preview Card + Customize Controls)
         ====================================================================== -->
    <section class="an-section an-scroll-reveal" id="interactive-demo" aria-labelledby="demo-heading">
      <div class="an-container">
        <div class="an-section-header">
          <span class="an-eyebrow">LIVE PLAYGROUND</span>
          <h2 class="an-section-title" id="demo-heading">Tune the chat studio in real time.</h2>
          <p class="an-section-sub">Drag the sliders, switch the studio accent, or toggle Khmer script to preview how Nexly adapts to your room aesthetic.</p>
        </div>

        <div class="an-demo-grid an-stagger-group">
          <!-- Live Preview Card -->
          <div class="an-demo-stage an-stagger-item">
            <div class="an-demo-preview-card" id="demo-preview-card">
              <div class="an-demo-room-top">
                <div class="an-demo-room-badge">
                  <span class="an-demo-pulse" aria-hidden="true"></span>
                  <span>#design-critique-lobby</span>
                </div>
                <span class="an-shipped-tag">14ms SSE LIVE</span>
              </div>

              <!-- Incoming Message -->
              <div class="an-demo-msg">
                <div class="an-demo-avatar" aria-hidden="true">SC</div>
                <div class="an-demo-bubble">
                  <div style="font-size:0.76rem; color:var(--text-muted); margin-bottom:0.25rem;">Sophea · 10:42 AM</div>
                  <div id="demo-primary-text">Just dropped the new 48kHz studio voice waveform and zero-latency snap preview!</div>
                </div>
              </div>

              <!-- Outgoing Voice Note Message -->
              <div class="an-demo-msg an-demo-msg-own">
                <div class="an-demo-avatar" aria-hidden="true">ME</div>
                <div class="an-demo-bubble">
                  <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
                    <span>Voice Note · 0:18</span>
                    <span style="font-size:0.75rem; opacity:0.8;">48kHz</span>
                  </div>
                  <div class="an-demo-waveform" id="demo-waveform-box" aria-label="Animated voice waveform preview">
                    <?php for ($w = 0; $w < 22; $w++): ?>
                      <span class="an-wave-bar"></span>
                    <?php endfor; ?>
                  </div>
                </div>
              </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-top:1.25rem;">
              <span style="font-size:0.84rem; color:var(--text-muted);">Ready to test with real messages and voice recording?</span>
              <a href="<?= BASE_URL ?>/chat.php" class="an-btn an-btn-mint" style="min-height:42px; padding:0.5rem 1.15rem; font-size:0.85rem;">
                Launch Full Chat Studio ↗
              </a>
            </div>
          </div>

          <!-- "Customize" Control Panel -->
          <aside class="an-demo-controls an-stagger-item" aria-label="Customize live preview">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <h3 style="margin:0; font-size:1.15rem; font-weight:700;">Customize</h3>
              <button type="button" id="demo-reset-btn" class="an-login-link" style="background:none; border:0; cursor:pointer; font-size:0.82rem;">
                Reset defaults
              </button>
            </div>

            <!-- Slider 1: Card Corner Radius -->
            <div class="an-control-row">
              <div class="an-control-label-wrap">
                <label for="demo-radius-slider">Corner Radius</label>
                <span class="an-control-val" id="demo-radius-val">24px</span>
              </div>
              <input type="range" id="demo-radius-slider" class="an-range" min="8" max="40" value="24">
            </div>

            <!-- Slider 2: Backdrop Blur -->
            <div class="an-control-row">
              <div class="an-control-label-wrap">
                <label for="demo-blur-slider">Surface Glass Blur</label>
                <span class="an-control-val" id="demo-blur-val">14px</span>
              </div>
              <input type="range" id="demo-blur-slider" class="an-range" min="0" max="28" value="14">
            </div>

            <!-- Slider 3: Waveform Motion Speed -->
            <div class="an-control-row">
              <div class="an-control-label-wrap">
                <label for="demo-speed-slider">Motion Tempo</label>
                <span class="an-control-val" id="demo-speed-val">1.0x</span>
              </div>
              <input type="range" id="demo-speed-slider" class="an-range" min="0.4" max="1.4" step="0.1" value="1.0">
            </div>

            <!-- Dropdown: Studio Accent -->
            <div class="an-control-row">
              <label for="demo-accent-select" style="font-size:0.86rem; font-weight:600;">Studio Accent Highlight</label>
              <select id="demo-accent-select" class="an-select">
                <option value="#b3fca0" selected>Annnimate Mint (#b3fca0)</option>
                <option value="#d9f99d">Electric Lime (#d9f99d)</option>
                <option value="#a5f3fc">Glacier Cyan (#a5f3fc)</option>
                <option value="#fde68a">Solar Amber (#fde68a)</option>
                <option value="#f5d0fe">Studio Orchid (#f5d0fe)</option>
              </select>
            </div>

            <!-- Toggle 1: Live Voice Waveform -->
            <div class="an-toggle-row">
              <input type="checkbox" id="demo-wave-toggle" checked style="width:20px; height:20px;">
              <label for="demo-wave-toggle" style="font-size:0.88rem; font-weight:600; cursor:pointer; flex:1; margin-left:0.65rem;">
                Show 48kHz Voice Waveform
              </label>
            </div>

            <!-- Toggle 2: Khmer Script Sample -->
            <div class="an-toggle-row">
              <input type="checkbox" id="demo-khmer-toggle" style="width:20px; height:20px;">
              <label for="demo-khmer-toggle" style="font-size:0.88rem; font-weight:600; cursor:pointer; flex:1; margin-left:0.65rem;">
                Preview Khmer Typography (ភាសាខ្មែរ)
              </label>
            </div>
          </aside>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         5. BENEFITS GRID (6 Short Cards · Bold Title + One Sentence)
         ====================================================================== -->
    <section class="an-section an-scroll-reveal" id="benefits" aria-labelledby="benefits-heading">
      <div class="an-container">
        <div class="an-section-header">
          <span class="an-eyebrow">WHY NEXLY</span>
          <h2 class="an-section-title" id="benefits-heading">Built for clarity, speed, and human warmth.</h2>
          <p class="an-section-sub">Six core pillars that make every conversation feel effortless on desktop and mobile.</p>
        </div>

        <div class="an-benefits-grid an-stagger-group">
          <?php foreach ($benefits as $b): ?>
            <article class="an-benefit-card an-tilt-card an-stagger-item">
              <span class="an-benefit-num" aria-hidden="true"><?= e($b['icon']) ?></span>
              <h3 class="an-benefit-title"><?= e($b['title']) ?></h3>
              <p class="an-benefit-desc"><?= e($b['desc']) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         6. "JUST SHIPPED / LATEST" GALLERY GRID (Zoom + Lift + Cursor Label)
         ====================================================================== -->
    <section class="an-section an-scroll-reveal" id="latest-shipped" aria-labelledby="shipped-heading">
      <div class="an-container">
        <div class="an-section-header">
          <span class="an-eyebrow">JUST SHIPPED · CHANGELOG</span>
          <h2 class="an-section-title" id="shipped-heading">Fresh out of the motion lab.</h2>
          <p class="an-section-sub">Hover any release card to inspect or jump straight into the live studio to try it yourself.</p>
        </div>

        <div class="an-shipped-grid an-stagger-group">
          <?php foreach ($latest_shipped as $item): ?>
            <a href="<?= e($item['url']) ?>" class="an-shipped-card an-stagger-item">
              <div class="an-shipped-media">
                <img
                  src="<?= e($item['image']) ?>"
                  alt="<?= e($item['alt']) ?>"
                  width="600"
                  height="380"
                  loading="lazy"
                  decoding="async"
                  class="an-shipped-img"
                >
              </div>
              <div class="an-shipped-body">
                <div>
                  <h3 class="an-shipped-title"><?= e($item['title']) ?></h3>
                  <span class="an-shipped-meta">Shipped <?= e($item['date']) ?></span>
                </div>
                <span class="an-shipped-tag"><?= e($item['tag']) ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         7. SHOWCASE HORIZONTAL AUTO-SCROLL MARQUEE (Pauses on Hover)
         ====================================================================== -->
    <section class="an-section an-scroll-reveal" id="showcase" aria-labelledby="showcase-heading">
      <div class="an-container">
        <div class="an-section-header">
          <span class="an-eyebrow">INTERFACE SHOWCASE</span>
          <h2 class="an-section-title" id="showcase-heading">Every surface designed with intent.</h2>
          <p class="an-section-sub">An infinite tour across the Global Lobby, Direct Threads, Voice Studio, and Creator Profiles.</p>
        </div>
      </div>

      <div class="an-showcase-scroller" role="region" aria-label="Interface screenshot showcase" tabindex="0">
        <div class="an-showcase-track">
          <?php for ($loop = 0; $loop < 2; $loop++): ?>
            <?php foreach ($showcase_items as $shot): ?>
              <figure class="an-showcase-card" <?= $loop === 1 ? 'aria-hidden="true"' : '' ?> style="margin:0;">
                <img
                  src="<?= e($shot['image']) ?>"
                  alt="<?= $loop === 1 ? '' : e($shot['alt']) ?>"
                  width="800"
                  height="500"
                  loading="lazy"
                  decoding="async"
                  class="an-showcase-img"
                >
                <figcaption class="an-showcase-caption-row">
                  <span class="an-showcase-title"><?= e($shot['title']) ?></span>
                  <span class="an-showcase-sub"><?= e($shot['caption']) ?></span>
                </figcaption>
              </figure>
            <?php endforeach; ?>
          <?php endfor; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         8. TESTIMONIALS (3 Quote Cards with Avatar, Name, and Role)
         ====================================================================== -->
    <section class="an-section an-scroll-reveal" id="testimonials" aria-labelledby="testimonials-heading">
      <div class="an-container">
        <div class="an-section-header">
          <span class="an-eyebrow">WALL OF LOVE</span>
          <h2 class="an-section-title" id="testimonials-heading">Trusted by creators &amp; engineers.</h2>
          <p class="an-section-sub">Hear from teams running daily design critiques and study groups on Nexly.</p>
        </div>

        <div class="an-testimonials-grid an-stagger-group">
          <?php foreach ($testimonials as $t): ?>
            <blockquote class="an-quote-card an-tilt-card an-stagger-item" style="margin:0;">
              <p class="an-quote-text">&ldquo;<?= e($t['quote']) ?>&rdquo;</p>
              <footer class="an-quote-author">
                <img
                  src="<?= e($t['avatar']) ?>"
                  alt="Avatar of <?= e($t['name']) ?>"
                  width="48"
                  height="48"
                  loading="lazy"
                  decoding="async"
                  class="an-quote-avatar"
                >
                <div>
                  <div class="an-quote-name"><?= e($t['name']) ?></div>
                  <div class="an-quote-role"><?= e($t['role']) ?></div>
                </div>
              </footer>
            </blockquote>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         9. PRICING SECTION (Monthly/Yearly Toggle · 3 Cards · Rolling Numbers)
         ====================================================================== -->
    <section class="an-section an-scroll-reveal" id="pricing" aria-labelledby="pricing-heading">
      <div class="an-container">
        <div class="an-section-header">
          <span class="an-eyebrow">SIMPLE PRICING</span>
          <h2 class="an-section-title" id="pricing-heading">Start free. Scale your studio anytime.</h2>
          <p class="an-section-sub">Switch between monthly and yearly billing to see instant savings roll in.</p>
        </div>

        <!-- Monthly / Yearly Toggle -->
        <div class="an-billing-switch-wrap">
          <div class="an-billing-toggle" role="group" aria-label="Billing frequency">
            <button type="button" class="an-billing-btn" id="billing-monthly" aria-pressed="true">
              Monthly
            </button>
            <button type="button" class="an-billing-btn" id="billing-yearly" aria-pressed="false">
              Yearly
            </button>
          </div>
          <span class="an-save-pill">Save 25% on Yearly</span>
        </div>

        <!-- 3 Plan Cards (Middle highlighted as "Most popular") -->
        <div class="an-pricing-grid an-stagger-group">
          <?php foreach ($pricing_plans as $plan): ?>
            <article class="an-price-card <?= $plan['popular'] ? 'is-popular' : '' ?> an-tilt-card an-stagger-item">
              <div>
                <div class="an-price-badge-row">
                  <h3 class="an-price-tier"><?= e($plan['name']) ?></h3>
                  <?php if ($plan['popular']): ?>
                    <span class="an-popular-pill"><?= e($plan['badge']) ?></span>
                  <?php else: ?>
                    <span class="an-shipped-tag"><?= e($plan['badge']) ?></span>
                  <?php endif; ?>
                </div>

                <div class="an-price-value-row">
                  <span class="an-price-currency">$</span>
                  <span
                    class="an-price-number"
                    data-monthly="<?= (int)$plan['price_monthly'] ?>"
                    data-yearly="<?= (int)$plan['price_yearly'] ?>"
                  ><?= (int)$plan['price_monthly'] ?></span>
                  <span class="an-price-unit"><?= e($plan['unit']) ?></span>
                </div>

                <p class="an-benefit-desc"><?= e($plan['desc']) ?></p>

                <ul class="an-price-Checklist">
                  <?php foreach ($plan['features'] as $feat): ?>
                    <li>
                      <span class="an-check-icon" aria-hidden="true">✓</span>
                      <span><?= e($feat) ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <a
                href="<?= e($plan['cta_url']) ?>"
                class="an-btn <?= $plan['popular'] ? 'an-btn-mint' : 'an-btn-outline' ?> an-magnetic"
                style="width:100%;"
              >
                <span class="an-btn-slot">
                  <span class="an-btn-label"><?= e($plan['cta_text']) ?></span>
                  <span class="an-btn-label" aria-hidden="true"><?= e($plan['cta_text']) ?> ↗</span>
                </span>
              </a>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         10. FAQ ACCORDION (Adjacent <details> + Smooth Height Animation)
         ====================================================================== -->
    <section class="an-section an-scroll-reveal" id="faq" aria-labelledby="faq-heading">
      <div class="an-container">
        <div class="an-section-header">
          <span class="an-eyebrow">DOCUMENTATION &amp; FAQ</span>
          <h2 class="an-section-title" id="faq-heading">Frequently asked questions.</h2>
          <p class="an-section-sub">Everything you need to know about running, customizing, and sharing your Nexly Studio.</p>
          <div style="margin-top:1.1rem;">
            <button type="button" id="faq-toggle-all" class="an-btn an-btn-outline" aria-expanded="false" style="min-height:40px; padding:0.45rem 1.1rem; font-size:0.82rem;">
              Expand all
            </button>
          </div>
        </div>

        <div class="an-faq-wrap" id="faq-accordion-group">
          <?php foreach ($faq_items as $index => $faq): ?>
            <details class="an-faq-item" <?= $index === 0 ? 'open' : '' ?>>
              <summary class="an-faq-summary"><?= e($faq['q']) ?></summary>
              <div class="an-faq-answer">
                <p style="margin:0;"><?= e($faq['a']) ?></p>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         11. FINAL BIG CTA SECTION (Repeating Headline & Primary/Secondary CTAs)
         ====================================================================== -->
    <section class="an-section an-scroll-reveal" aria-labelledby="final-cta-heading">
      <div class="an-container">
        <div class="an-final-cta-card an-stagger-group">
          <span class="an-eyebrow" style="background:var(--near-black); color:var(--mint); border-color:var(--near-black);">
            READY WHEN YOU ARE
          </span>
          <h2 class="an-final-cta-title an-stagger-item" id="final-cta-heading">
            <?= e($site_config['hero_headline']) ?>
          </h2>
          <p class="an-final-cta-sub an-stagger-item">
            Jump into the live lobby right now or create your verified handle in under 15 seconds.
          </p>
          <div class="an-hero-actions an-stagger-item" style="margin-block-end:0;">
            <a href="<?= e($site_config['cta_primary_url']) ?>" class="an-btn an-btn-dark an-magnetic">
              <span class="an-btn-slot">
                <span class="an-btn-label"><?= e($site_config['cta_primary_text']) ?></span>
                <span class="an-btn-label" aria-hidden="true"><?= e($site_config['cta_primary_text']) ?> ↗</span>
              </span>
            </a>
            <a href="<?= e($site_config['cta_secondary_url']) ?>" class="an-btn an-btn-mint an-magnetic" style="border:2px solid var(--near-black);">
              <span class="an-btn-slot">
                <span class="an-btn-label"><?= e($site_config['cta_secondary_text']) ?></span>
                <span class="an-btn-label" aria-hidden="true"><?= e($site_config['cta_secondary_text']) ?> →</span>
              </span>
            </a>
          </div>
        </div>
      </div>
    </section>

  </main>

  <?php
  // SECTIONS 12 & 13: Newsletter + 4-Column Footer + Floating Bottom Pill
  require __DIR__ . '/includes/landing_footer.php';
  ?>

  <!-- GSAP 3 + ScrollTrigger + Lenis Smooth Scroll CDN (Deferred for fast first paint) -->
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.13/dist/lenis.min.js" defer></script>

  <!-- Nexly Annnimate Motion & Customizer Script -->
  <script src="<?= BASE_URL ?>/assets/js/landing.js" defer></script>
</body>
</html>
