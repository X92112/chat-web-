/**
 * ============================================================================
 * NEXLY — ANNNIMATE.COM MOTION & INTERACTION ENGINE
 * Stack: GSAP 3 + ScrollTrigger + Lenis Smooth Scroll + Vanilla ES6 Fallbacks
 * ============================================================================
 */
(function () {
  'use strict';

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const isCoarsePointer = window.matchMedia('(pointer: coarse)').matches || window.innerWidth < 768;
  const hasGSAP = typeof window.gsap !== 'undefined';
  const hasScrollTrigger = typeof window.ScrollTrigger !== 'undefined';
  const hasLenis = typeof window.Lenis !== 'undefined';

  let lenisInstance = null;

  if (hasGSAP && hasScrollTrigger) {
    window.gsap.registerPlugin(window.ScrollTrigger);
  }

  /* ==========================================================================
     1. SMOOTH SCROLLING (Lenis + GSAP Ticker Sync)
     ========================================================================== */
  function initSmoothScroll() {
    if (prefersReducedMotion || isCoarsePointer || !hasLenis) return;

    lenisInstance = new window.Lenis({
      duration: 1.05,
      easing: function (t) {
        return Math.min(1, 1.001 - Math.pow(2, -10 * t));
      },
      smoothWheel: true,
    });

    if (hasGSAP && hasScrollTrigger) {
      lenisInstance.on('scroll', window.ScrollTrigger.update);
      window.gsap.ticker.add(function (time) {
        lenisInstance.raf(time * 1000);
      });
      window.gsap.ticker.lagSmoothing(0);
    } else {
      function raf(time) {
        if (!lenisInstance) return;
        lenisInstance.raf(time);
        requestAnimationFrame(raf);
      }
      requestAnimationFrame(raf);
    }
  }

  /* ==========================================================================
     2. MOBILE NAVIGATION DRAWER (<dialog> Modal + Light Dismiss + Escape)
     ========================================================================== */
  function initMobileDrawer() {
    const drawer = document.getElementById('mobile-nav-drawer');
    const sheet = document.getElementById('mobile-drawer-sheet');
    const openBtn = document.getElementById('mobile-drawer-open');
    const closeBtn = document.getElementById('mobile-drawer-close');

    if (!drawer || !openBtn) return;

    function openDrawer() {
      if (typeof drawer.showModal === 'function') {
        drawer.showModal();
      } else {
        drawer.setAttribute('open', '');
      }
      openBtn.setAttribute('aria-expanded', 'true');
      if (hasGSAP && !prefersReducedMotion && sheet) {
        window.gsap.fromTo(
          sheet,
          { xPercent: 100 },
          { xPercent: 0, duration: 0.42, ease: 'power3.out' }
        );
      }
    }

    function closeDrawer() {
      if (hasGSAP && !prefersReducedMotion && sheet && drawer.open) {
        window.gsap.to(sheet, {
          xPercent: 100,
          duration: 0.32,
          ease: 'power3.in',
          onComplete: function () {
            if (typeof drawer.close === 'function') drawer.close();
            else drawer.removeAttribute('open');
            openBtn.setAttribute('aria-expanded', 'false');
          },
        });
      } else {
        if (typeof drawer.close === 'function') drawer.close();
        else drawer.removeAttribute('open');
        openBtn.setAttribute('aria-expanded', 'false');
      }
    }

    openBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

    // Light-dismiss when tapping outside the sheet
    drawer.addEventListener('click', function (e) {
      if (sheet && !sheet.contains(e.target)) {
        closeDrawer();
      }
    });

    // Close when clicking any in-page anchor link inside drawer
    drawer.querySelectorAll('a[href^="#"]').forEach(function (link) {
      link.addEventListener('click', closeDrawer);
    });

    drawer.addEventListener('close', function () {
      openBtn.setAttribute('aria-expanded', 'false');
    });
  }

  /* ==========================================================================
     3. HERO WORD-BY-WORD REVEAL & SCROLL-TRIGGERED SECTION STAGGERS
     ========================================================================== */
  function initHeroAndScrollAnimations() {
    if (prefersReducedMotion || !hasGSAP) return;

    const words = document.querySelectorAll('.an-hero-word');
    if (words.length) {
      window.gsap.fromTo(
        words,
        { y: 34, opacity: 0 },
        {
          y: 0,
          opacity: 1,
          duration: 0.68,
          stagger: 0.045,
          ease: 'power3.out',
          clearProps: 'transform,opacity',
        }
      );
    }

    const heroSub = document.querySelectorAll('.an-hero-fade');
    if (heroSub.length) {
      window.gsap.fromTo(
        heroSub,
        { y: 20, opacity: 0 },
        {
          y: 0,
          opacity: 1,
          duration: 0.65,
          stagger: 0.08,
          delay: 0.25,
          ease: 'power3.out',
          clearProps: 'transform,opacity',
        }
      );
    }

    if (!hasScrollTrigger) return;

    // Fade/slide up sections and staggered card groups
    document.querySelectorAll('.an-stagger-group').forEach(function (group) {
      const items = group.querySelectorAll('.an-stagger-item');
      if (!items.length) return;

      window.gsap.fromTo(
        items,
        { y: 32, opacity: 0 },
        {
          y: 0,
          opacity: 1,
          duration: 0.65,
          stagger: 0.08,
          ease: 'power3.out',
          clearProps: 'transform,opacity',
          scrollTrigger: {
            trigger: group,
            start: 'top 86%',
            once: true,
          },
        }
      );
    });
  }

  /* ==========================================================================
     4. INTERACTIVE DEMO CUSTOMIZER (Sliders, Dropdown, Toggles -> Live Preview)
     ========================================================================== */
  function initInteractiveDemo() {
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

    const waveContainer = document.getElementById('demo-waveform-box');
    const msgTextEl = document.getElementById('demo-primary-text');

    if (!radiusSlider) return;

    function updateDemo() {
      const r = radiusSlider.value + 'px';
      const b = blurSlider.value + 'px';
      const s = (1.6 - parseFloat(speedSlider.value)).toFixed(2) + 's';
      const accent = accentSelect ? accentSelect.value : '#b3fca0';

      root.style.setProperty('--demo-radius', r);
      root.style.setProperty('--demo-blur', b);
      root.style.setProperty('--demo-speed', s);
      root.style.setProperty('--demo-accent', accent);

      if (radiusVal) radiusVal.textContent = r;
      if (blurVal) blurVal.textContent = b;
      if (speedVal) speedVal.textContent = speedSlider.value + 'x';

      if (waveContainer && waveToggle) {
        waveContainer.hidden = !waveToggle.checked;
      }

      if (msgTextEl && khmerToggle) {
        msgTextEl.textContent = khmerToggle.checked
          ? 'សួស្តី! យើងទើបតែដាក់ឱ្យប្រើប្រាស់សំឡេង 48kHz ថ្មីនៅក្នុងបន្ទប់ជជែកផ្ទាល់។'
          : 'Just dropped the new 48kHz studio voice waveform and zero-latency snap preview!';
      }
    }

    [radiusSlider, blurSlider, speedSlider].forEach(function (el) {
      if (el) el.addEventListener('input', updateDemo);
    });
    if (accentSelect) accentSelect.addEventListener('change', updateDemo);
    if (waveToggle) waveToggle.addEventListener('change', updateDemo);
    if (khmerToggle) khmerToggle.addEventListener('change', updateDemo);

    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        radiusSlider.value = '24';
        blurSlider.value = '14';
        speedSlider.value = '1.0';
        if (accentSelect) accentSelect.value = '#b3fca0';
        if (waveToggle) waveToggle.checked = true;
        if (khmerToggle) khmerToggle.checked = false;
        updateDemo();
      });
    }

    updateDemo();
  }

  /* ==========================================================================
     5. MAGNETIC BUTTONS, 3D CARD TILT & CURSOR-FOLLOW PILL (Desktop Pointer)
     ========================================================================== */
  function initPointerMicroInteractions() {
    if (prefersReducedMotion || isCoarsePointer) return;

    // Subtle magnetic buttons
    document.querySelectorAll('.an-magnetic').forEach(function (btn) {
      btn.addEventListener('mousemove', function (e) {
        const rect = btn.getBoundingClientRect();
        const x = (e.clientX - rect.left - rect.width / 2) * 0.18;
        const y = (e.clientY - rect.top - rect.height / 2) * 0.22;
        if (hasGSAP) {
          window.gsap.to(btn, { x: x, y: y, duration: 0.3, ease: 'power3.out' });
        }
      });
      btn.addEventListener('mouseleave', function () {
        if (hasGSAP) {
          window.gsap.to(btn, { x: 0, y: 0, duration: 0.45, ease: 'power3.out' });
        }
      });
    });

    // Subtle 3D tilt on cards
    document.querySelectorAll('.an-tilt-card').forEach(function (card) {
      card.addEventListener('mousemove', function (e) {
        const rect = card.getBoundingClientRect();
        const relX = (e.clientX - rect.left) / rect.width - 0.5;
        const relY = (e.clientY - rect.top) / rect.height - 0.5;
        if (hasGSAP) {
          window.gsap.to(card, {
            rotateY: relX * 5,
            rotateX: -relY * 5,
            y: -5,
            transformPerspective: 900,
            duration: 0.35,
            ease: 'power3.out',
          });
        }
      });
      card.addEventListener('mouseleave', function () {
        if (hasGSAP) {
          window.gsap.to(card, {
            rotateY: 0,
            rotateX: 0,
            y: 0,
            duration: 0.5,
            ease: 'power3.out',
          });
        }
      });
    });

    // Cursor-follow label for Section 6 ("Just shipped / Latest" cards)
    const cursorPill = document.getElementById('cursor-follow-pill');
    const shippedCards = document.querySelectorAll('.an-shipped-card');
    if (cursorPill && shippedCards.length) {
      window.addEventListener('pointermove', function (e) {
        if (!cursorPill.classList.contains('is-active')) return;
        if (hasGSAP) {
          window.gsap.to(cursorPill, {
            left: e.clientX,
            top: e.clientY,
            duration: 0.2,
            ease: 'power3.out',
          });
        } else {
          cursorPill.style.left = e.clientX + 'px';
          cursorPill.style.top = e.clientY + 'px';
        }
      });

      shippedCards.forEach(function (card) {
        card.addEventListener('pointerenter', function (e) {
          cursorPill.style.left = e.clientX + 'px';
          cursorPill.style.top = e.clientY + 'px';
          cursorPill.classList.add('is-active');
        });
        card.addEventListener('pointerleave', function () {
          cursorPill.classList.remove('is-active');
        });
      });
    }
  }

  /* ==========================================================================
     6. PRICING MONTHLY / YEARLY TOGGLE WITH ROLLING NUMBERS
     ========================================================================== */
  function initPricingToggle() {
    const btnMonthly = document.getElementById('billing-monthly');
    const btnYearly = document.getElementById('billing-yearly');
    const priceEls = document.querySelectorAll('.an-price-number');

    if (!btnMonthly || !btnYearly || !priceEls.length) return;

    function setBilling(mode) {
      const isYearly = mode === 'yearly';
      btnMonthly.setAttribute('aria-pressed', isYearly ? 'false' : 'true');
      btnYearly.setAttribute('aria-pressed', isYearly ? 'true' : 'false');

      priceEls.forEach(function (el) {
        const target = parseInt(
          isYearly ? el.getAttribute('data-yearly') : el.getAttribute('data-monthly'),
          10
        );
        const start = parseInt(el.textContent, 10) || 0;

        if (prefersReducedMotion || start === target) {
          el.textContent = String(target);
          return;
        }

        if (hasGSAP) {
          const counter = { val: start };
          window.gsap.to(counter, {
            val: target,
            duration: 0.55,
            ease: 'power3.out',
            onUpdate: function () {
              el.textContent = String(Math.round(counter.val));
            },
          });
        } else {
          el.textContent = String(target);
        }
      });
    }

    btnMonthly.addEventListener('click', function () {
      setBilling('monthly');
    });
    btnYearly.addEventListener('click', function () {
      setBilling('yearly');
    });
  }

  /* ==========================================================================
     7. FAQ ACCORDION EXPAND/COLLAPSE ALL + SMOOTH HEIGHT ANIMATION
     ========================================================================== */
  function initFaqAccordion() {
    const toggleAllBtn = document.getElementById('faq-toggle-all');
    const faqSection = document.getElementById('faq-accordion-group');
    if (!faqSection) return;

    const detailsList = Array.from(faqSection.querySelectorAll('details.an-faq-item'));

    function syncToggleAllState() {
      if (!toggleAllBtn) return;
      const allOpen = detailsList.every(function (d) {
        return d.open;
      });
      toggleAllBtn.setAttribute('aria-expanded', allOpen ? 'true' : 'false');
      toggleAllBtn.textContent = allOpen ? 'Collapse all' : 'Expand all';
    }

    if (toggleAllBtn) {
      toggleAllBtn.addEventListener('click', function () {
        const expand = toggleAllBtn.getAttribute('aria-expanded') !== 'true';
        detailsList.forEach(function (d) {
          d.open = expand;
        });
        syncToggleAllState();
      });
    }

    detailsList.forEach(function (d) {
      d.addEventListener('toggle', syncToggleAllState);
    });
  }

  /* ==========================================================================
     8. NEWSLETTER FORM VALIDATION & FLOATING PILL SLIDE-IN
     ========================================================================== */
  function initNewsletterAndPill() {
    const form = document.getElementById('newsletter-form');
    const emailInput = document.getElementById('newsletter-email');
    const statusEl = document.getElementById('newsletter-status');

    if (form && emailInput && statusEl) {
      emailInput.addEventListener('input', function () {
        emailInput.setAttribute('aria-invalid', 'false');
        emailInput.setCustomValidity('');
      });

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        const val = emailInput.value.trim();
        if (!val || !val.includes('@')) {
          emailInput.setAttribute('aria-invalid', 'true');
          statusEl.textContent = 'Please enter a valid work or student email address.';
          emailInput.focus();
          return;
        }
        statusEl.textContent = '✓ Subscribed! You will receive the next Nexly Studio release notes.';
        form.reset();
      });
    }

    // Section 13: Floating bottom pill slides in after 1.3s delay
    const pill = document.getElementById('floating-shipped-pill');
    const pillClose = document.getElementById('floating-pill-close');

    if (pill) {
      setTimeout(function () {
        pill.hidden = false;
        requestAnimationFrame(function () {
          pill.classList.add('is-visible');
        });
      }, 1300);

      if (pillClose) {
        pillClose.addEventListener('click', function () {
          pill.classList.remove('is-visible');
          setTimeout(function () {
            pill.hidden = true;
          }, 450);
        });
      }
    }
  }

  /* ==========================================================================
     9. CLEANUP ON PAGE CHANGE (Prevent Memory Leaks & Layout Shift)
     ========================================================================== */
  window.addEventListener('pagehide', function () {
    if (lenisInstance && typeof lenisInstance.destroy === 'function') {
      lenisInstance.destroy();
      lenisInstance = null;
    }
    if (hasScrollTrigger) {
      window.ScrollTrigger.getAll().forEach(function (st) {
        st.kill();
      });
    }
    if (hasGSAP) {
      window.gsap.killTweensOf('*');
    }
  });

  // Boot all modules on DOMContentLoaded
  document.addEventListener('DOMContentLoaded', function () {
    initSmoothScroll();
    initMobileDrawer();
    initHeroAndScrollAnimations();
    initInteractiveDemo();
    initPointerMicroInteractions();
    initPricingToggle();
    initFaqAccordion();
    initNewsletterAndPill();
  });
})();
