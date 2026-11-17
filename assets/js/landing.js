/**
 * ============================================================================
 * NEXLY X — MIGUEL RECIO ARCHITECTURAL SWISS MOTION & INTERACTION ENGINE
 * Kinetic headline word cycling, interactive playground, Lenis smooth scroll,
 * and precision parameter matrix controls.
 * ============================================================================
 */
(function () {
  'use strict';

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ==========================================================================
     1. SCROLL READING PROGRESS INDICATOR
     ========================================================================== */
  function initProgressBar() {
    const bar = document.getElementById('scroll-progress-bar');
    if (!bar) return;

    function update() {
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const scrollPos = window.scrollY || document.documentElement.scrollTop;
      const progress = docHeight > 0 ? Math.min(100, Math.max(0, (scrollPos / docHeight) * 100)) : 0;
      bar.style.width = progress + '%';
      bar.setAttribute('aria-valuenow', Math.round(progress));
    }

    window.addEventListener('scroll', update, { passive: true });
    update();
  }

  /* ==========================================================================
     2. KINETIC CYCLING WORD ANIMATION (MIGUEL RECIO HEADLINE EFFECT)
     ========================================================================== */
  function initHeadlineWordCycle() {
    const cycEl = document.querySelector('.mr-hcyc');
    if (!cycEl) return;

    let words = ['SPEED.', 'CLARITY.', 'CONNECTION.', 'PRECISION.'];
    try {
      const parsed = JSON.parse(cycEl.getAttribute('data-words'));
      if (Array.isArray(parsed) && parsed.length > 0) words = parsed;
    } catch (e) {}

    let currentIdx = 0;
    if (prefersReducedMotion) return;

    setInterval(function () {
      cycEl.classList.add('is-switching');
      setTimeout(function () {
        currentIdx = (currentIdx + 1) % words.length;
        cycEl.textContent = words[currentIdx];
        cycEl.classList.remove('is-switching');
      }, 250);
    }, 2400);
  }

  /* ==========================================================================
     3. MOBILE NAVIGATION DRAWER
     ========================================================================== */
  function initMobileDrawer() {
    const openBtn = document.getElementById('mobile-drawer-open');
    const closeBtn = document.getElementById('mobile-drawer-close');
    const drawer = document.getElementById('mobile-nav-drawer');

    if (!openBtn || !drawer) return;

    openBtn.addEventListener('click', function () {
      drawer.showModal();
      openBtn.setAttribute('aria-expanded', 'true');
    });

    function closeDrawer() {
      drawer.close();
      openBtn.setAttribute('aria-expanded', 'false');
    }

    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

    drawer.addEventListener('click', function (e) {
      if (e.target === drawer) closeDrawer();
    });

    drawer.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', closeDrawer);
    });
  }

  /* ==========================================================================
     4. INTERACTIVE PLAYGROUND PARAMETER MATRIX
     ========================================================================== */
  function initPlayground() {
    const root = document.documentElement;
    const radiusSlider = document.getElementById('demo-radius-slider');
    const radiusVal = document.getElementById('demo-radius-val');
    const blurSlider = document.getElementById('demo-blur-slider');
    const blurVal = document.getElementById('demo-blur-val');
    const speedSlider = document.getElementById('demo-speed-slider');
    const speedVal = document.getElementById('demo-speed-val');
    const accentSelect = document.getElementById('demo-accent-select');
    const waveToggle = document.getElementById('demo-wave-toggle');
    const khmerToggle = document.getElementById('demo-khmer-toggle');
    const resetBtn = document.getElementById('demo-reset-btn');

    const previewCard = document.getElementById('demo-preview-card');
    const waveBox = document.getElementById('demo-waveform-box');
    const msgEl = document.getElementById('demo-primary-text');

    if (!radiusSlider) return;

    function update() {
      const r = radiusSlider.value + 'px';
      const b = blurSlider.value + 'px';
      const s = speedSlider.value;
      const accent = accentSelect ? accentSelect.value : '#ff3a3a';

      root.style.setProperty('--demo-radius', r);
      root.style.setProperty('--demo-blur', b);
      root.style.setProperty('--demo-accent', accent);

      if (radiusVal) radiusVal.textContent = r;
      if (blurVal) blurVal.textContent = b;
      if (speedVal) speedVal.textContent = s + 'x';

      if (previewCard && !prefersReducedMotion) {
        previewCard.style.transform = 'scale(1.01)';
        setTimeout(function () {
          previewCard.style.transform = '';
        }, 160);
      }

      if (waveBox && waveToggle) {
        waveBox.style.display = waveToggle.checked ? 'flex' : 'none';
      }

      if (msgEl && khmerToggle) {
        msgEl.textContent = khmerToggle.checked
          ? 'សួស្តី! យើងទើបតែដាក់ឱ្យប្រើប្រាស់សំឡេង 48kHz ថ្មីនៅក្នុងបន្ទប់ជជែកផ្ទាល់។'
          : 'Just dropped the new 48kHz studio voice waveform and zero-latency snap preview!';
      }
    }

    [radiusSlider, blurSlider, speedSlider].forEach(function (slider) {
      if (slider) slider.addEventListener('input', update);
    });

    if (accentSelect) accentSelect.addEventListener('change', update);
    if (waveToggle) waveToggle.addEventListener('change', update);
    if (khmerToggle) khmerToggle.addEventListener('change', update);

    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        radiusSlider.value = '24';
        blurSlider.value = '14';
        speedSlider.value = '1.0';
        if (accentSelect) accentSelect.value = '#ff3a3a';
        if (waveToggle) waveToggle.checked = true;
        if (khmerToggle) khmerToggle.checked = false;
        update();
      });
    }

    update();
  }

  /* ==========================================================================
     5. PRICING BILLING FREQUENCY TOGGLE (MONTHLY / YEARLY)
     ========================================================================== */
  function initPricingToggle() {
    const btnMonthly = document.getElementById('billing-monthly');
    const btnYearly = document.getElementById('billing-yearly');
    const priceVals = document.querySelectorAll('.mr-plan-val');

    if (!btnMonthly || !btnYearly || !priceVals.length) return;

    function setMode(isYearly) {
      btnMonthly.classList.toggle('is-active', !isYearly);
      btnMonthly.setAttribute('aria-pressed', !isYearly ? 'true' : 'false');
      btnYearly.classList.toggle('is-active', isYearly);
      btnYearly.setAttribute('aria-pressed', isYearly ? 'true' : 'false');

      priceVals.forEach(function (valEl) {
        const target = isYearly ? valEl.getAttribute('data-yearly') : valEl.getAttribute('data-monthly');
        const start = parseInt(valEl.textContent, 10) || 0;
        const end = parseInt(target, 10) || 0;

        if (prefersReducedMotion || typeof window.gsap === 'undefined' || start === end) {
          valEl.textContent = target;
          return;
        }

        const counter = { num: start };
        window.gsap.to(counter, {
          num: end,
          duration: 0.45,
          ease: 'power3.out',
          onUpdate: function () {
            valEl.textContent = Math.round(counter.num);
          },
        });
      });
    }

    btnMonthly.addEventListener('click', function () { setMode(false); });
    btnYearly.addEventListener('click', function () { setMode(true); });
  }

  /* ==========================================================================
     6. FAQ ACCORDION EXPAND/COLLAPSE ALL
     ========================================================================== */
  function initFaq() {
    const toggleBtn = document.getElementById('faq-toggle-all');
    const faqGroup = document.getElementById('faq-accordion-group');
    if (!faqGroup) return;

    const items = Array.from(faqGroup.querySelectorAll('details.mr-faq-item'));

    function checkState() {
      if (!toggleBtn) return;
      const allOpen = items.every(function (d) { return d.open; });
      toggleBtn.textContent = allOpen ? '[ COLLAPSE ALL - ]' : '[ EXPAND ALL + ]';
    }

    if (toggleBtn) {
      toggleBtn.addEventListener('click', function () {
        const anyClosed = items.some(function (d) { return !d.open; });
        items.forEach(function (d) { d.open = anyClosed; });
        checkState();
      });
    }

    items.forEach(function (d) {
      d.addEventListener('toggle', checkState);
    });
  }

  /* ==========================================================================
     7. NEWSLETTER SUBMISSION HANDLER
     ========================================================================== */
  function initNewsletter() {
    const form = document.getElementById('newsletter-form');
    const input = document.getElementById('newsletter-email');
    const status = document.getElementById('newsletter-status');

    if (!form || !input || !status) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const val = input.value.trim();
      if (!val || !val.includes('@')) {
        status.textContent = 'ERROR // PLEASE ENTER A VALID EMAIL ADDRESS.';
        status.style.color = 'var(--infra)';
        input.focus();
        return;
      }
      status.textContent = '✓ SUBSCRIBED // YOU WILL RECEIVE THE NEXT RELEASE DISPATCH.';
      status.style.color = 'var(--ok)';
      form.reset();
    });
  }

  /* ==========================================================================
     8. SMOOTH SCROLL (LENIS FALLBACK)
     ========================================================================== */
  function initLenis() {
    if (prefersReducedMotion || typeof window.Lenis === 'undefined') return;

    const lenis = new window.Lenis({
      duration: 1.1,
      easing: function (t) {
        return Math.min(1, 1.001 - Math.pow(2, -10 * t));
      },
      smoothWheel: true,
    });

    function raf(time) {
      lenis.raf(time);
      requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);
  }

  // Initialize all modules when DOM is ready
  document.addEventListener('DOMContentLoaded', function () {
    initProgressBar();
    initHeadlineWordCycle();
    initMobileDrawer();
    initPlayground();
    initPricingToggle();
    initFaq();
    initNewsletter();
    initLenis();
  });
})();
