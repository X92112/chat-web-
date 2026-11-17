<?php
/**
 * ============================================================================
 * NEXLY X — MIGUEL RECIO ARCHITECTURAL SWISS LANDING PAGE (index.php)
 * Clean concrete paper #edeef1 · Ink #161618 · Infra red #ff3a3a · Hairline 1px
 * Display typography in "Anton" · Monospace in "JetBrains Mono"
 * ============================================================================
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$current_user = current_user();
require_once __DIR__ . '/includes/landing_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light">
  <meta name="theme-color" content="#edeef1">
  <meta name="description" content="<?= e($site_config['hero_subheadline']) ?>">
  <title><?= e($site_config['name']) ?> X — Architectural Voice, Snap &amp; Studio Chat</title>

  <!-- Google Fonts: Anton (Display) + JetBrains Mono (Specs) + Inter (Body) + Noto Sans Khmer -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;700&display=swap" rel="stylesheet">

  <!-- Architectural Landing Stylesheet -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/landing.css?v=<?= @filemtime(__DIR__ . '/assets/css/landing.css') ?: time() ?>">

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
<body class="mr-body">

  <?php
  // SECTIONS 1 & 2: Top Hairline Marquee + Sticky Architectural Minimal Navbar
  require __DIR__ . '/includes/landing_navbar.php';
  ?>

  <main id="main-content">

    <!-- ======================================================================
         HERO SECTION: 12-Column Architectural Swiss Grid
         ====================================================================== -->
    <section class="mr-hero" aria-labelledby="hero-title">
      <div class="mr-wrap">
        <div class="mr-g12 mr-hero-grid">
          
          <!-- Hero Left Column (7 cols): Display Typography & CTAs -->
          <div class="mr-hero-left">
            <div class="mr-tag mr-mono mr-fade">
              <span class="mr-sq"></span>
              <span>REAL-TIME VOICE &amp; STUDIO CHAT · PHNOM PENH</span>
            </div>

            <h1 class="mr-display mr-dh mr-xl mr-hero-title" id="hero-title">
              <span class="mr-ln">FAST TALK.</span>
              <span class="mr-ln">STUDIO SOUND.</span>
              <span class="mr-ln mr-accent-line">PURE <span class="mr-hcyc" data-words='["SPEED.","CLARITY.","CONNECTION.","PRECISION."]'>SPEED.</span></span>
            </h1>

            <p class="mr-hero-desc mr-fade">
              A calm, editorial real-time chat and voice studio engineered with sub-20ms SSE latency, 48kHz lossless voice notes, and bilingual Khmer + Latin typography. Designed for fast-moving cohorts, creators, and engineers.
            </p>

            <div class="mr-hero-actions mr-fade">
              <a href="<?= e($site_config['cta_primary_url']) ?>" class="mr-pill mr-pill-primary">
                <span><?= e($site_config['cta_primary_text']) ?> ↗</span>
              </a>
              <a href="#interactive-demo" class="mr-pill mr-pill-outline">
                <span>EXPLORE PLAYGROUND ↓</span>
              </a>
            </div>

            <!-- Architectural Technical Specifications Strip -->
            <div class="mr-specs-strip mr-mono mr-fade">
              <div class="mr-spec-item">
                <span class="mr-spec-lbl">STREAM //</span>
                <span class="mr-spec-val">ACTIVE SSE</span>
              </div>
              <div class="mr-spec-item">
                <span class="mr-spec-lbl">LATENCY //</span>
                <span class="mr-spec-val">&lt; 18MS</span>
              </div>
              <div class="mr-spec-item">
                <span class="mr-spec-lbl">CODEC //</span>
                <span class="mr-spec-val">48KHZ OPUS</span>
              </div>
              <div class="mr-spec-item">
                <span class="mr-spec-lbl">GRID //</span>
                <span class="mr-spec-val">12-COL SWISS</span>
              </div>
            </div>
          </div>

          <!-- Hero Right Column (5 cols): Architectural Viewport (No Floating Junk!) -->
          <div class="mr-hero-right">
            <div class="mr-fig-box">
              <div class="mr-fig-header mr-mono">
                <span class="mr-fig-title">FIG. 01 — STUDIO VIEWPORT</span>
                <span class="mr-fig-status"><span class="mr-status-dot"></span> LIVE 60FPS</span>
              </div>

              <div class="mr-fig-inner">
                <!-- Hairline Crosshair Corner Marks -->
                <div class="mr-cross mr-cross-tl" aria-hidden="true"></div>
                <div class="mr-cross mr-cross-tr" aria-hidden="true"></div>
                <div class="mr-cross mr-cross-bl" aria-hidden="true"></div>
                <div class="mr-cross mr-cross-br" aria-hidden="true"></div>

                <!-- Chat Studio Mockup -->
                <div class="mr-mockup-window">
                  <div class="mr-mockup-bar">
                    <div class="mr-bar-dots">
                      <span class="mr-bar-dot"></span>
                      <span class="mr-bar-dot"></span>
                      <span class="mr-bar-dot"></span>
                    </div>
                    <span class="mr-mockup-room mr-mono">#public-lobby // 12 voices live</span>
                    <span class="mr-mono mr-latency-pill">14ms SSE</span>
                  </div>

                  <div class="mr-mockup-body">
                    <!-- Date Pill -->
                    <div class="mr-mock-divider mr-mono">TODAY</div>

                    <!-- Incoming Text Message -->
                    <div class="mr-mock-row mr-mock-incoming">
                      <div class="mr-mock-avatar mr-mono">SL</div>
                      <div class="mr-mock-content">
                        <div class="mr-mock-meta mr-mono">
                          <span class="mr-mock-name">SOKHA LIM</span>
                          <span class="mr-badge-check">✓</span>
                          <span class="mr-mock-time">10:42 AM</span>
                        </div>
                        <div class="mr-mock-bubble">
                          Just deployed the new 48kHz audio engine and Khmer baseline typography to the production cluster.
                        </div>
                      </div>
                    </div>

                    <!-- Outgoing Voice Note -->
                    <div class="mr-mock-row mr-mock-outgoing">
                      <div class="mr-mock-avatar mr-mock-avatar-me mr-mono">RT</div>
                      <div class="mr-mock-content">
                        <div class="mr-mock-meta mr-mono">
                          <span class="mr-mock-name">YOU</span>
                          <span class="mr-badge-check">✓</span>
                          <span class="mr-mock-time">JUST NOW</span>
                        </div>
                        <div class="mr-mock-bubble mr-mock-voice">
                          <div class="mr-mock-voice-header mr-mono">
                            <span class="mr-mock-play">▶</span>
                            <span>VOICE NOTE · 0:18</span>
                            <span class="mr-voice-badge">48KHZ STEREO</span>
                          </div>
                          <div class="mr-mock-waveform" aria-label="Audio waveform graphic">
                            <?php 
                              $heights = [30, 50, 75, 40, 90, 100, 70, 45, 85, 60, 40, 80, 95, 65, 35, 80, 90, 65, 50, 70, 85, 45, 60, 30];
                              foreach ($heights as $h): 
                            ?>
                              <span class="mr-wave-bar" style="height: <?= $h ?>%;"></span>
                            <?php endforeach; ?>
                          </div>
                        </div>
                        <div class="mr-mock-seen mr-mono">✓ Seen</div>
                      </div>
                    </div>

                    <!-- Call Event Pill -->
                    <div class="mr-call-event-pill mr-mono">
                      <span class="mr-call-pill-icon">☎</span>
                      <span class="mr-call-pill-text">Voice call ended · 0:37</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mr-fig-caption mr-mono">
                [ ARCHITECTURE // REAL-TIME DUPLEX AUDIO · NO DISCORD BLOAT · ZERO NEON GLOW ]
              </div>
            </div>
          </div>

        </div>

        <!-- Logos / Cohort Strip -->
        <div class="mr-logos-bar">
          <span class="mr-logos-label mr-mono">DEPLOYED ACROSS //</span>
          <div class="mr-logos-list mr-mono">
            <?php foreach ($site_config['featured_logos'] as $logo): ?>
              <span class="mr-logo-item"><?= e($logo) ?></span>
              <span class="mr-logo-sep">/</span>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </section>

    <!-- ======================================================================
         SECTION 01: CAPABILITIES & ARCHITECTURAL SPECS
         ====================================================================== -->
    <section class="mr-section" id="capabilities" aria-labelledby="cap-title">
      <div class="mr-wrap">
        <div class="mr-sec-header">
          <div class="mr-tag mr-mono">
            <span class="mr-sq"></span>
            <span>[ 01 / WHAT WE BUILT ]</span>
          </div>
          <h2 class="mr-display mr-sec-title" id="cap-title">ENGINEERED FOR RAW SPEED AND QUIET FOCUS.</h2>
          <p class="mr-sec-desc">
            Six architectural pillars that replace generic chat bloat with deterministic responsiveness, tactile typography, and human audio fidelity.
          </p>
        </div>

        <div class="mr-grid-cards">
          <?php foreach ($benefits as $idx => $b): ?>
            <article class="mr-card">
              <div class="mr-card-top mr-mono">
                <span class="mr-card-num">[ 0<?= $idx + 1 ?> ]</span>
                <span class="mr-card-sys"><?= e($b['icon']) ?></span>
              </div>
              <h3 class="mr-display mr-card-title"><?= e($b['title']) ?></h3>
              <p class="mr-card-desc"><?= e($b['desc']) ?></p>
              <div class="mr-card-footer mr-mono">SPEC // SYSTEM OK</div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         SECTION 02: INTERACTIVE STUDIO PLAYGROUND
         ====================================================================== -->
    <section class="mr-section" id="interactive-demo" aria-labelledby="demo-title">
      <div class="mr-wrap">
        <div class="mr-sec-header">
          <div class="mr-tag mr-mono">
            <span class="mr-sq"></span>
            <span>[ 02 / REAL-TIME PLAYGROUND ]</span>
          </div>
          <h2 class="mr-display mr-sec-title" id="demo-title">TUNE THE STUDIO IN REAL TIME.</h2>
          <p class="mr-sec-desc">
            Drag the parameters to inspect how the Swiss architectural token system dynamically adjusts corner radii, motion tempo, and bilingual typography.
          </p>
        </div>

        <div class="mr-playground-grid">
          <!-- Playground Preview Viewport -->
          <div class="mr-play-viewport">
            <div class="mr-play-card" id="demo-preview-card">
              <div class="mr-play-card-header mr-mono">
                <span>#design-critique-lobby</span>
                <span class="mr-latency-pill"><span class="mr-status-dot"></span> 14ms SSE LIVE</span>
              </div>

              <div class="mr-play-msg">
                <div class="mr-mock-avatar mr-mono">SC</div>
                <div class="mr-mock-content">
                  <div class="mr-mock-meta mr-mono">SOPHEA · 10:42 AM</div>
                  <div class="mr-mock-bubble" id="demo-primary-text">
                    Just dropped the new 48kHz studio voice waveform and zero-latency snap preview!
                  </div>
                </div>
              </div>

              <div class="mr-play-msg mr-play-msg-own">
                <div class="mr-mock-avatar mr-mock-avatar-me mr-mono">ME</div>
                <div class="mr-mock-content">
                  <div class="mr-mock-meta mr-mono">YOU · NOW</div>
                  <div class="mr-mock-bubble mr-mock-voice">
                    <div class="mr-mock-voice-header mr-mono">
                      <span>VOICE NOTE · 0:18</span>
                      <span>48KHZ OPUS</span>
                    </div>
                    <div class="mr-mock-waveform" id="demo-waveform-box">
                      <?php for ($w = 0; $w < 22; $w++): ?>
                        <span class="mr-wave-bar" style="height: <?= [40, 65, 90, 50, 100, 75, 45, 80, 95, 60, 40, 85, 90, 70, 55, 75, 90, 45, 60, 30, 80, 50][$w] ?>%;"></span>
                      <?php endfor; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mr-play-cta-row">
              <span class="mr-mono" style="font-size:12px; color:var(--paper-mut);">TEST LIVE WITH ACTUAL WEBRTC STREAM:</span>
              <a href="<?= BASE_URL ?>/chat.php" class="mr-pill mr-pill-primary">
                <span>OPEN LIVE STUDIO ↗</span>
              </a>
            </div>
          </div>

          <!-- Playground Controls Panel -->
          <div class="mr-play-controls">
            <div class="mr-controls-head mr-mono">
              <span>PARAMETER MATRIX</span>
              <button type="button" id="demo-reset-btn" class="mr-btn-reset">[ RESET DEFAULTS ]</button>
            </div>

            <div class="mr-control-group">
              <div class="mr-ctrl-row">
                <label for="demo-radius-slider" class="mr-mono">CORNER RADIUS</label>
                <span class="mr-mono mr-ctrl-val" id="demo-radius-val">24px</span>
              </div>
              <input type="range" id="demo-radius-slider" min="0" max="32" value="24" class="mr-slider">
            </div>

            <div class="mr-control-group">
              <div class="mr-ctrl-row">
                <label for="demo-blur-slider" class="mr-mono">SURFACE BLUR</label>
                <span class="mr-mono mr-ctrl-val" id="demo-blur-val">14px</span>
              </div>
              <input type="range" id="demo-blur-slider" min="0" max="28" value="14" class="mr-slider">
            </div>

            <div class="mr-control-group">
              <div class="mr-ctrl-row">
                <label for="demo-speed-slider" class="mr-mono">WAVE MOTION TEMPO</label>
                <span class="mr-mono mr-ctrl-val" id="demo-speed-val">1.0x</span>
              </div>
              <input type="range" id="demo-speed-slider" min="0.4" max="1.6" step="0.1" value="1.0" class="mr-slider">
            </div>

            <div class="mr-control-group">
              <label for="demo-accent-select" class="mr-mono" style="display:block; margin-bottom:8px;">ACCENT COLORWAY</label>
              <select id="demo-accent-select" class="mr-select mr-mono">
                <option value="#ff3a3a" selected>Miguel Recio Infra Red (#ff3a3a)</option>
                <option value="#161618">Architectural Ink (#161618)</option>
                <option value="#1db954">Terminal Green (#1db954)</option>
                <option value="#2563eb">Electric Cobalt (#2563eb)</option>
                <option value="#d8401f">Editorial Flame (#d8401f)</option>
              </select>
            </div>

            <div class="mr-control-group mr-toggles">
              <label class="mr-toggle-label mr-mono">
                <input type="checkbox" id="demo-wave-toggle" checked>
                <span>SHOW 48KHZ WAVEFORM</span>
              </label>

              <label class="mr-toggle-label mr-mono">
                <input type="checkbox" id="demo-khmer-toggle">
                <span>PREVIEW KHMER SCRIPT (ភាសាខ្មែរ)</span>
              </label>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- ======================================================================
         SECTION 03: CHANGELOG / LATEST SHIPPED
         ====================================================================== -->
    <section class="mr-section" id="latest-shipped" aria-labelledby="changelog-title">
      <div class="mr-wrap">
        <div class="mr-sec-header">
          <div class="mr-tag mr-mono">
            <span class="mr-sq"></span>
            <span>[ 03 / RELEASE LOG ]</span>
          </div>
          <h2 class="mr-display mr-sec-title" id="changelog-title">CONTINUOUS DISPATCH FROM THE WORKSHOP.</h2>
          <p class="mr-sec-desc">
            Each update strictly improves audio throughput, latency boundaries, and editorial layout integrity.
          </p>
        </div>

        <div class="mr-changelog-table">
          <?php foreach ($latest_shipped as $idx => $item): ?>
            <div class="mr-cl-row">
              <div class="mr-cl-date mr-mono"><?= e($item['date']) ?></div>
              <div class="mr-cl-tag mr-mono"><span><?= e($item['tag']) ?></span></div>
              <div class="mr-cl-title">
                <a href="<?= e($item['url']) ?>" class="mr-cl-link">
                  <span><?= e($item['title']) ?></span>
                  <span class="mr-cl-arrow">↗</span>
                </a>
              </div>
              <div class="mr-cl-status mr-mono">[ DEPLOYED // PROD ]</div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         SECTION 04: INTERFACE VIEWPORTS SHOWCASE
         ====================================================================== -->
    <section class="mr-section" id="showcase" aria-labelledby="showcase-title">
      <div class="mr-wrap">
        <div class="mr-sec-header">
          <div class="mr-tag mr-mono">
            <span class="mr-sq"></span>
            <span>[ 04 / INTERFACE GALLERY ]</span>
          </div>
          <h2 class="mr-display mr-sec-title" id="showcase-title">EVERY SURFACE DOCUMENTED WITH PRECISION.</h2>
          <p class="mr-sec-desc">
            Architectural viewports showing the 4-column studio layout, calling overlays, and DM threads.
          </p>
        </div>

        <div class="mr-showcase-grid">
          <?php foreach ($showcase_items as $idx => $shot): ?>
            <figure class="mr-showcase-fig">
              <div class="mr-fig-top mr-mono">
                <span>FIG. 0<?= $idx + 2 ?> // <?= strtoupper(e($shot['title'])) ?></span>
                <span>60FPS ARCH</span>
              </div>
              <div class="mr-fig-image-wrap">
                <img
                  src="<?= e($shot['image']) ?>"
                  alt="<?= e($shot['alt']) ?>"
                  width="800"
                  height="500"
                  loading="lazy"
                  decoding="async"
                  class="mr-showcase-img"
                >
              </div>
              <figcaption class="mr-fig-caption mr-mono">
                <?= e($shot['caption']) ?>
              </figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         SECTION 05: TESTIMONIALS / WALL OF LOVE
         ====================================================================== -->
    <section class="mr-section" id="testimonials" aria-labelledby="test-title">
      <div class="mr-wrap">
        <div class="mr-sec-header">
          <div class="mr-tag mr-mono">
            <span class="mr-sq"></span>
            <span>[ 05 / VERIFIED FEEDBACK ]</span>
          </div>
          <h2 class="mr-display mr-sec-title" id="test-title">TRUSTED BY TEAMS TIRED OF DISCORD NOISE.</h2>
          <p class="mr-sec-desc">
            Direct dispatches from founders, design cohort leads, and student builders.
          </p>
        </div>

        <div class="mr-testimonials-grid">
          <?php foreach ($testimonials as $idx => $t): ?>
            <blockquote class="mr-quote-card">
              <div class="mr-quote-top mr-mono">
                <span>INDEX 0<?= $idx + 1 ?> // VERIFIED USER</span>
                <span class="mr-quote-check">✓</span>
              </div>
              <p class="mr-quote-body">&ldquo;<?= e($t['quote']) ?>&rdquo;</p>
              <footer class="mr-quote-author">
                <div class="mr-qa-info">
                  <div class="mr-display mr-qa-name"><?= e($t['name']) ?></div>
                  <div class="mr-mono mr-qa-role"><?= e($t['role']) ?></div>
                </div>
              </footer>
            </blockquote>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         SECTION 06: PRICING & TIERS
         ====================================================================== -->
    <section class="mr-section" id="pricing" aria-labelledby="pricing-title">
      <div class="mr-wrap">
        <div class="mr-sec-header">
          <div class="mr-tag mr-mono">
            <span class="mr-sq"></span>
            <span>[ 06 / SIMPLE TIERS ]</span>
          </div>
          <h2 class="mr-display mr-sec-title" id="pricing-title">START FREE. EXPAND WHEN READY.</h2>
          <p class="mr-sec-desc">
            No dark patterns or forced upgrades. Toggle between monthly and annual terms.
          </p>

          <!-- Monthly / Yearly Billing Toggle -->
          <div class="mr-billing-row">
            <div class="mr-billing-toggle mr-mono" role="group" aria-label="Billing frequency">
              <button type="button" class="mr-bill-btn is-active" id="billing-monthly" aria-pressed="true">
                [ MONTHLY ]
              </button>
              <button type="button" class="mr-bill-btn" id="billing-yearly" aria-pressed="false">
                [ YEARLY -25% ]
              </button>
            </div>
          </div>
        </div>

        <div class="mr-pricing-grid">
          <?php foreach ($pricing_plans as $idx => $plan): ?>
            <article class="mr-plan-card <?= $plan['popular'] ? 'is-featured' : '' ?>">
              <div class="mr-plan-top mr-mono">
                <span class="mr-plan-tier">[ 0<?= $idx + 1 ?> ] <?= strtoupper(e($plan['name'])) ?></span>
                <?php if ($plan['popular']): ?>
                  <span class="mr-plan-badge mr-badge-infra">POPULAR</span>
                <?php else: ?>
                  <span class="mr-plan-badge">STANDARD</span>
                <?php endif; ?>
              </div>

              <div class="mr-plan-cost">
                <span class="mr-plan-currency">$</span>
                <span
                  class="mr-display mr-plan-val"
                  data-monthly="<?= (int)$plan['price_monthly'] ?>"
                  data-yearly="<?= (int)$plan['price_yearly'] ?>"
                ><?= (int)$plan['price_monthly'] ?></span>
                <span class="mr-mono mr-plan-unit"><?= e($plan['unit']) ?></span>
              </div>

              <p class="mr-plan-desc"><?= e($plan['desc']) ?></p>

              <ul class="mr-plan-features mr-mono">
                <?php foreach ($plan['features'] as $f): ?>
                  <li>
                    <span class="mr-check">✓</span>
                    <span><?= e($f) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>

              <div class="mr-plan-action">
                <a href="<?= e($plan['cta_url']) ?>" class="mr-pill <?= $plan['popular'] ? 'mr-pill-primary' : 'mr-pill-outline' ?>">
                  <span><?= e($plan['cta_text']) ?> ↗</span>
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         SECTION 07: FAQ ACCORDION
         ====================================================================== -->
    <section class="mr-section" id="faq" aria-labelledby="faq-title">
      <div class="mr-wrap">
        <div class="mr-sec-header">
          <div class="mr-tag mr-mono">
            <span class="mr-sq"></span>
            <span>[ 07 / DOCUMENTATION &amp; FAQ ]</span>
          </div>
          <h2 class="mr-display mr-sec-title" id="faq-title">ANSWERS TO TECHNICAL INQUIRIES.</h2>
          <p class="mr-sec-desc">
            Direct details regarding the WebRTC audio pipeline, server-sent events, and security architecture.
          </p>
          <div style="margin-top:16px;">
            <button type="button" id="faq-toggle-all" class="mr-btn-reset mr-mono">[ EXPAND ALL + ]</button>
          </div>
        </div>

        <div class="mr-faq-list" id="faq-accordion-group">
          <?php foreach ($faq_items as $idx => $faq): ?>
            <details class="mr-faq-item" <?= $idx === 0 ? 'open' : '' ?>>
              <summary class="mr-faq-sum">
                <span class="mr-mono mr-faq-idx">[ 0<?= $idx + 1 ?> ]</span>
                <span class="mr-faq-question"><?= e($faq['q']) ?></span>
                <span class="mr-faq-icon mr-mono" aria-hidden="true">+</span>
              </summary>
              <div class="mr-faq-ans">
                <p><?= e($faq['a']) ?></p>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         SECTION 08: FINAL HERO CTA (Deep Ink Swiss Box)
         ====================================================================== -->
    <section class="mr-section mr-final-cta-section" aria-labelledby="cta-callout-title">
      <div class="mr-wrap">
        <div class="mr-final-box">
          <div class="mr-tag mr-mono" style="color:var(--infra);">
            <span class="mr-sq" style="background:var(--infra);"></span>
            <span>[ 08 / ENTER THE STUDIO ]</span>
          </div>

          <h2 class="mr-display mr-final-title" id="cta-callout-title">
            READY TO TALK AT THE SPEED OF LIGHT?
          </h2>

          <p class="mr-final-desc">
            Join the public lobby immediately or register a verified handle in under 15 seconds. No credit card required.
          </p>

          <div class="mr-final-actions">
            <a href="<?= e($site_config['cta_primary_url']) ?>" class="mr-pill mr-pill-infra">
              <span>START FREE ACCOUNT ↗</span>
            </a>
            <a href="<?= BASE_URL ?>/chat.php" class="mr-pill mr-pill-ghost-ink">
              <span>EXPLORE PUBLIC LOBBY →</span>
            </a>
          </div>
        </div>
      </div>
    </section>

  </main>

  <?php
  // SECTIONS 12 & 13: Swiss Newsletter + Architectural 4-Column Footer + Calling Modals
  require __DIR__ . '/includes/landing_footer.php';
  ?>

  <!-- GSAP 3 + ScrollTrigger + Lenis CDN for Swiss kinetic motion -->
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.13/dist/lenis.min.js" defer></script>

  <!-- Architectural Swiss Motion & Customizer Script -->
  <script src="<?= BASE_URL ?>/assets/js/landing.js?v=<?= @filemtime(__DIR__ . '/assets/js/landing.js') ?: time() ?>" defer></script>
</body>
</html>
