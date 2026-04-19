/* ══════════════════════════════════════════════════
   UltraFood — app.js
   ══════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  /* ── 1. SCROLL REVEAL ─────────────────────────── */
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll('.reveal-up, .reveal-left, .reveal-right')
    .forEach(el => revealObserver.observe(el));


  /* ── 2. HEADER scroll behaviour ──────────────── */
  const header = document.getElementById('header');
  const onScroll = () => {
    header.classList.toggle('scrolled', window.scrollY > 60);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();


  /* ── 3. ACTIVE NAV LINK (scroll spy) ─────────── */
  const sections  = document.querySelectorAll('section[id]');
  const navLinks  = document.querySelectorAll('.nav-link');

  const spyObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const id = entry.target.getAttribute('id');
        navLinks.forEach(link => {
          link.classList.toggle('active-link', link.getAttribute('href') === `#${id}`);
        });
      }
    });
  }, { rootMargin: '-40% 0px -55% 0px' });

  sections.forEach(s => spyObserver.observe(s));


  /* ── 4. MOBILE NAV ────────────────────────────── */
  const burger    = document.getElementById('burger');
  const mobileNav = document.getElementById('mobileNav');
  const body = document.body;

  burger.addEventListener('click', () => {
    const open = burger.classList.toggle('open');
    mobileNav.classList.toggle('open', open);
    body.classList.toggle('menu-open', open);
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  mobileNav.querySelectorAll('.mobile-nav-link').forEach(link => {
    link.addEventListener('click', () => {
      burger.classList.remove('open');
      mobileNav.classList.remove('open');
      body.classList.remove('menu-open');
      burger.setAttribute('aria-expanded', 'false');
    });
  });


  /* ── 5. HERO CAROUSEL ─────────────────────────── */
  const slides = document.querySelectorAll('#hero .slide');
  const dots   = document.querySelectorAll('#hero .carousel-dot');
  let current  = 0;
  let carouselTimer;

  const goTo = (idx) => {
    if (!slides.length) return;
    slides[current].classList.remove('active');
    dots[current].classList.remove('active');
    current = ((idx % slides.length) + slides.length) % slides.length;
    slides[current].classList.add('active');
    dots[current].classList.add('active');
  };

  const startCarousel = () => {
    if (carouselTimer) clearInterval(carouselTimer);
    if (slides.length) {
      carouselTimer = setInterval(() => goTo(current + 1), 6000);
    }
  };

  startCarousel();

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      clearInterval(carouselTimer);
      goTo(i);
      startCarousel();
    });
  });


  /* ── 6. HISTORY TIMELINE (No Typing Animation) ─── */
  const histSection = document.getElementById('history');
  const track = document.getElementById('histCarouselTrack');

  if (track && histSection) {
    const boxes = Array.from(track.querySelectorAll('.history-content-box'));
    const allDots = Array.from(document.querySelectorAll('.hist-dot'));
    const histPages = Array.from(document.querySelectorAll('.hist-page'));
    const histPips = Array.from(document.querySelectorAll('.hist-page-pip'));
    const prevBtn = document.getElementById('histPrev');
    const nextBtn = document.getElementById('histNext');

    const ITEMS_PER_PAGE = 4;
    let activeIdx = 0;
    let isAnimating = false;
    let pendingIdx = null;
    const ANIM_DURATION = 450;

    // Build HIST_BG from hidden img tags
    const HIST_BG = {};
    for (let i = 0; i < 7; i++) {
      const ref = document.getElementById('histBgRef' + i);
      HIST_BG[i] = ref ? ref.src : 'images/banner/UFDI-History-Timeline-BG-' + (i + 1) + '.png';
    }

    // Preload background images
    Object.values(HIST_BG).forEach(src => { new Image().src = src; });

    // Create background layer
    const existingBgLayer = histSection.querySelector('.hist-bg-layer');
    if (existingBgLayer) existingBgLayer.remove();

    const bgLayer = document.createElement('div');
    bgLayer.className = 'hist-bg-layer';

    const imgA = document.createElement('div');
    const imgB = document.createElement('div');
    imgA.className = 'hist-bg-img is-active';
    imgB.className = 'hist-bg-img';

    imgA.style.backgroundImage = `url('${HIST_BG[0]}')`;
    imgB.style.backgroundImage = `url('${HIST_BG[0]}')`;

    bgLayer.appendChild(imgA);
    bgLayer.appendChild(imgB);
    histSection.insertBefore(bgLayer, histSection.firstChild);

    let topLayer = imgA;
    let botLayer = imgB;
    let bgTimer = null;
    let lastBgIdx = 0;

    function setHistBg(idx) {
      if (!HIST_BG[idx] || idx === lastBgIdx) return;
      lastBgIdx = idx;
      clearTimeout(bgTimer);
      botLayer.style.backgroundImage = `url('${HIST_BG[idx]}')`;
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          botLayer.classList.add('is-active');
          topLayer.classList.remove('is-active');
          bgTimer = setTimeout(() => {
            const prev = topLayer;
            topLayer = botLayer;
            botLayer = prev;
          }, ANIM_DURATION);
        });
      });
    }

    function initCard() {
      boxes.forEach((box, i) => {
        box.classList.remove('active', 'adjacent', 'adjacent-far');
        if (i === 0) {
          box.classList.add('active');
          const entry = box.querySelector('.hist-entry');
          if (entry) entry.classList.add('active');
        } else if (i === 1) {
          box.classList.add('adjacent');
        } else {
          box.classList.add('adjacent-far');
        }
      });

      allDots.forEach(d => d.classList.remove('active'));
      const firstDot = allDots.find(d => parseInt(d.dataset.idx) === 0);
      if (firstDot) firstDot.classList.add('active');

      histPages.forEach((p, i) => {
        p.classList.toggle('active-page', i === 0);
        p.style.opacity = '';
      });

      histPips.forEach((p, i) => p.classList.toggle('active', i === 0));
    }

    function updateCarousel(idx) {
      if (isAnimating) {
        pendingIdx = idx;
        return;
      }

      idx = ((idx % boxes.length) + boxes.length) % boxes.length;
      if (idx === activeIdx) return;

      isAnimating = true;
      pendingIdx = null;
      activeIdx = idx;

      boxes.forEach((box, i) => {
        box.classList.remove('active', 'adjacent', 'adjacent-far');
        void box.offsetWidth;
        if (i === idx) {
          box.classList.add('active');
          const entry = box.querySelector('.hist-entry');
          if (entry) {
            entry.classList.remove('active');
            void entry.offsetWidth;
            entry.classList.add('active');
          }
        } else if (i === idx - 1 || i === idx + 1) {
          box.classList.add('adjacent');
        } else {
          box.classList.add('adjacent-far');
        }
      });

      allDots.forEach(d => {
        d.classList.remove('active');
        d.style.pointerEvents = 'none';
      });
      setTimeout(() => {
        const activeDot = allDots.find(d => parseInt(d.dataset.idx) === idx);
        if (activeDot) activeDot.classList.add('active');
      }, 60);

      const pageIdx = Math.floor(idx / ITEMS_PER_PAGE);
      histPages.forEach(p => {
        p.classList.remove('active-page');
        p.style.opacity = '';
      });
      void document.body.offsetWidth;
      if (histPages[pageIdx]) histPages[pageIdx].classList.add('active-page');

      histPips.forEach((p, i) => p.classList.toggle('active', i === pageIdx));

      setHistBg(idx);

      setTimeout(() => {
        isAnimating = false;
        allDots.forEach(d => { d.style.pointerEvents = ''; });
        if (pendingIdx !== null && pendingIdx !== activeIdx) updateCarousel(pendingIdx);
      }, ANIM_DURATION + 50);
    }

    boxes.forEach((box, i) => {
      box.addEventListener('click', () => {
        if (!box.classList.contains('active')) updateCarousel(i);
      });
    });

    allDots.forEach(d => {
      d.addEventListener('click', () => {
        const idx = parseInt(d.dataset.idx);
        if (!isNaN(idx) && idx !== activeIdx) updateCarousel(idx);
      });
      d.addEventListener('mouseenter', () => {
        if (!d.classList.contains('active')) d.style.transform = 'translateY(-2px)';
      });
      d.addEventListener('mouseleave', () => { d.style.transform = ''; });
    });

    histPips.forEach(p => {
      p.addEventListener('click', () => {
        const page = parseInt(p.dataset.page);
        if (!isNaN(page)) {
          const targetIdx = page * ITEMS_PER_PAGE;
          if (targetIdx !== activeIdx) updateCarousel(targetIdx);
        }
      });
    });

    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        prevBtn.style.transform = 'scale(0.9)';
        setTimeout(() => { prevBtn.style.transform = ''; }, 200);
        updateCarousel((activeIdx - 1 + boxes.length) % boxes.length);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        nextBtn.style.transform = 'scale(0.9)';
        setTimeout(() => { nextBtn.style.transform = ''; }, 200);
        updateCarousel((activeIdx + 1) % boxes.length);
      });
    }

    initCard();

    window.setHistBgAt = function (idx, newSrc) {
      HIST_BG[idx] = newSrc;
      lastBgIdx = -1;
      setHistBg(idx);
    };
  }


  /* ── 7. CONTACT FORM ──────────────────────────── */
  const form        = document.getElementById('contactForm');
  const formSuccess = document.getElementById('formSuccess');
  if (formSuccess) formSuccess.style.display = 'none';

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      form.style.display = 'none';
      if (formSuccess) formSuccess.classList.add('show');
      formSuccess.style.display = 'block';
    });
  }


  /* ── 8. BACK TO TOP ───────────────────────────── */
  const backToTop = document.getElementById('backToTop');
  if (backToTop) {
    backToTop.style.display = 'none';

    window.addEventListener('scroll', () => {
      backToTop.style.display = window.scrollY > 400 ? 'flex' : 'none';
    }, { passive: true });

    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

});