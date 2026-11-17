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

  function getGSAP() {
    return typeof window.gsap !== 'undefined' ? window.gsap : null;
  }
  function getScrollTrigger() {
    return typeof window.ScrollTrigger !== 'undefined' ? window.ScrollTrigger : null;
  }
  function getLenis() {
    return typeof window.Lenis !== 'undefined' ? window.Lenis : null;
  }

  let lenisInstance = null;

  /* ==========================================================================
     0. SCROLL PROGRESS INDICATOR (Fallback for browsers without native CSS timeline)
     ========================================================================== */
  function initScrollProgressBar() {
    const bar = document.getElementById('scroll-progress-bar');
    if (!bar) return;

    if (window.CSS && CSS.supports && CSS.supports('(animation-timeline: scroll()) and (animation-range: 0% 100%)')) {
      return; // CSS native scroll-timeline handles this smoothly
    }

    function updateProgress() {
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const scrollPos = window.scrollY || document.documentElement.scrollTop;
      const progress = docHeight > 0 ? Math.min(1, Math.max(0, scrollPos / docHeight)) : 0;
      bar.style.transform = `scaleX(${progress})`;
      bar.setAttribute('aria-valuenow', Math.round(progress * 100));
    }

    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
  }

  /* ==========================================================================
     0.5 SCROLL REVEALS INTERSECTION OBSERVER (Modern Web Guidance Fallback)
     ========================================================================== */
  function initIntersectionReveals() {
    if (prefersReducedMotion) return;

    if (!window.CSS || !CSS.supports || !CSS.supports('(animation-timeline: view()) and (animation-range: entry)')) {
      const observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-inview');
              observer.unobserve(entry.target);
            }
          });
        },
        {
          threshold: 0.1,
          rootMargin: '0px 0px -40px 0px',
        }
      );

      document.querySelectorAll('.an-scroll-reveal, .an-stagger-item').forEach(function (el) {
        observer.observe(el);
      });
    }
  }

  /* ==========================================================================
     1. SMOOTH SCROLLING (Lenis + GSAP Ticker Sync)
     ========================================================================== */
  function initSmoothScroll() {
    if (prefersReducedMotion || isCoarsePointer) return;
    const LenisClass = getLenis();
    if (!LenisClass) return;

    lenisInstance = new LenisClass({
      duration: 1.05,
      easing: function (t) {
        return Math.min(1, 1.001 - Math.pow(2, -10 * t));
      },
      smoothWheel: true,
    });

    const gsap = getGSAP();
    const st = getScrollTrigger();

    if (gsap && st) {
      gsap.registerPlugin(st);
      lenisInstance.on('scroll', st.update);
      gsap.ticker.add(function (time) {
        lenisInstance.raf(time * 1000);
      });
      gsap.ticker.lagSmoothing(0);
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
      const gsap = getGSAP();
      if (gsap && !prefersReducedMotion && sheet) {
        gsap.fromTo(
          sheet,
          { xPercent: 100 },
          { xPercent: 0, duration: 0.42, ease: 'power3.out' }
        );
      }
    }

    function closeDrawer() {
      const gsap = getGSAP();
      if (gsap && !prefersReducedMotion && sheet && drawer.open) {
        gsap.to(sheet, {
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

    drawer.addEventListener('click', function (e) {
      if (sheet && !sheet.contains(e.target)) {
        closeDrawer();
      }
    });

    drawer.querySelectorAll('a[href^="#"]').forEach(function (link) {
      link.addEventListener('click', closeDrawer);
    });

    drawer.addEventListener('close', function () {
      openBtn.setAttribute('aria-expanded', 'false');
    });
  }

  /* ==========================================================================
     3. HERO WORD-BY-WORD REVEAL & 3D PARALLAX GYRO
     ========================================================================== */
  function initHeroAndScrollAnimations() {
    if (prefersReducedMotion) return;

    const gsap = getGSAP();
    const st = getScrollTrigger();

    if (gsap) {
      const words = document.querySelectorAll('.an-hero-word');
      if (words.length) {
        gsap.fromTo(
          words,
          { y: 40, opacity: 0, rotateX: -20, scale: 0.94 },
          {
            y: 0,
            opacity: 1,
            rotateX: 0,
            scale: 1,
            duration: 0.75,
            stagger: 0.05,
            ease: 'back.out(1.4)',
            clearProps: 'transform,opacity',
          }
        );
      }

      const heroSub = document.querySelectorAll('.an-hero-fade');
      if (heroSub.length) {
        gsap.fromTo(
          heroSub,
          { y: 24, opacity: 0 },
          {
            y: 0,
            opacity: 1,
            duration: 0.7,
            stagger: 0.09,
            delay: 0.28,
            ease: 'power3.out',
            clearProps: 'transform,opacity',
          }
        );
      }

      if (st) {
        document.querySelectorAll('.an-stagger-group').forEach(function (group) {
          const items = group.querySelectorAll('.an-stagger-item');
          if (!items.length) return;

          gsap.fromTo(
            items,
            { y: 35, opacity: 0, scale: 0.96 },
            {
              y: 0,
              opacity: 1,
              scale: 1,
              duration: 0.7,
              stagger: 0.09,
              ease: 'power3.out',
              clearProps: 'transform,opacity,scale',
              scrollTrigger: {
                trigger: group,
                start: 'top 86%',
                once: true,
              },
            }
          );
        });
      }
    }

    // Hero 3D Parallax on pointermove
    if (!isCoarsePointer) {
      const hero = document.querySelector('.an-hero');
      const floatLeft = document.querySelector('.an-float-left');
      const floatRight = document.querySelector('.an-float-right');
      const orbs = document.querySelectorAll('.an-hero-orb');

      if (hero) {
        hero.addEventListener('mousemove', function (e) {
          const rect = hero.getBoundingClientRect();
          const x = (e.clientX - rect.left) / rect.width - 0.5;
          const y = (e.clientY - rect.top) / rect.height - 0.5;

          if (floatLeft) {
            floatLeft.style.transform = `translate3d(${x * -28}px, ${y * -24}px, 0) rotate(${x * 6}deg)`;
          }
          if (floatRight) {
            floatRight.style.transform = `translate3d(${x * 32}px, ${y * 26}px, 0) rotate(${x * -6}deg)`;
          }
          orbs.forEach(function (orb, i) {
            const factor = (i + 1) * 20;
            orb.style.transform = `translate3d(${x * factor}px, ${y * factor}px, 0)`;
          });
        });

        hero.addEventListener('mouseleave', function () {
          if (floatLeft) floatLeft.style.transform = '';
          if (floatRight) floatRight.style.transform = '';
          orbs.forEach(function (orb) {
            orb.style.transform = '';
          });
        });
      }
    }
  }

  /* ==========================================================================
     4. INTERACTIVE DEMO CUSTOMIZER & WAVEFORM EQUALIZER HARMONICS
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

    const previewCard = document.getElementById('demo-preview-card');
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

      if (previewCard && !prefersReducedMotion) {
        previewCard.style.transform = 'scale(1.015)';
        setTimeout(function () {
          previewCard.style.transform = '';
        }, 180);
      }

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

    const gsap = getGSAP();

    // Subtle magnetic buttons
    document.querySelectorAll('.an-magnetic').forEach(function (btn) {
      btn.addEventListener('mousemove', function (e) {
        const rect = btn.getBoundingClientRect();
        const x = (e.clientX - rect.left - rect.width / 2) * 0.22;
        const y = (e.clientY - rect.top - rect.height / 2) * 0.26;
        if (gsap) {
          gsap.to(btn, { x: x, y: y, duration: 0.3, ease: 'power3.out' });
        } else {
          btn.style.transform = `translate3d(${x}px, ${y}px, 0)`;
        }
      });
      btn.addEventListener('mouseleave', function () {
        if (gsap) {
          gsap.to(btn, { x: 0, y: 0, duration: 0.45, ease: 'power3.out' });
        } else {
          btn.style.transform = '';
        }
      });
    });

    // 3D Tilt on Cards
    document.querySelectorAll('.an-tilt-card').forEach(function (card) {
      card.addEventListener('mousemove', function (e) {
        const rect = card.getBoundingClientRect();
        const relX = (e.clientX - rect.left) / rect.width - 0.5;
        const relY = (e.clientY - rect.top) / rect.height - 0.5;
        if (gsap) {
          gsap.to(card, {
            rotateY: relX * 8,
            rotateX: -relY * 8,
            y: -8,
            transformPerspective: 1000,
            duration: 0.35,
            ease: 'power3.out',
          });
        } else {
          card.style.transform = `perspective(1000px) rotateY(${relX * 8}deg) rotateX(${-relY * 8}deg) translateY(-8px)`;
        }
      });
      card.addEventListener('mouseleave', function () {
        if (gsap) {
          gsap.to(card, {
            rotateY: 0,
            rotateX: 0,
            y: 0,
            duration: 0.5,
            ease: 'power3.out',
          });
        } else {
          card.style.transform = '';
        }
      });
    });

    // Cursor-follow label for Section 6 ("Just shipped / Latest" cards)
    const cursorPill = document.getElementById('cursor-follow-pill');
    const shippedCards = document.querySelectorAll('.an-shipped-card');
    if (cursorPill && shippedCards.length) {
      window.addEventListener('pointermove', function (e) {
        if (!cursorPill.classList.contains('is-active')) return;
        if (gsap) {
          gsap.to(cursorPill, {
            left: e.clientX,
            top: e.clientY,
            duration: 0.18,
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

      const gsap = getGSAP();

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

        if (gsap) {
          const counter = { val: start };
          gsap.to(counter, {
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

    const pill = document.getElementById('floating-shipped-pill');
    const pillClose = document.getElementById('floating-pill-close');

    if (pill) {
      setTimeout(function () {
        pill.hidden = false;
        requestAnimationFrame(function () {
          pill.classList.add('is-visible');
        });
      }, 1200);

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
    const st = getScrollTrigger();
    if (st) {
      st.getAll().forEach(function (instance) {
        instance.kill();
      });
    }
    const gsap = getGSAP();
    if (gsap) {
      gsap.killTweensOf('*');
    }
  });

  // Boot all modules on DOMContentLoaded
  document.addEventListener('DOMContentLoaded', function () {
    initScrollProgressBar();
    initIntersectionReveals();
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
