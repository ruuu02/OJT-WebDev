// Carousel interval handle — cleared before each re-init so calling
// initUltrafood() more than once (e.g. after visual-editor canvas refresh)
// never leaves orphaned timers behind.
let _carouselTimer = null;

function initUltrafood() {

  if (_carouselTimer !== null) {
    clearInterval(_carouselTimer);
    _carouselTimer = null;
  }

  const header    = document.getElementById('header');
  const backToTop = document.getElementById('backToTop');
  const body = document.body;
  const burger = document.getElementById('burger');
  const mobileNav = document.getElementById('mobileNav');

  const HIDE_DELAY = 1000;
  const TOP_THRESHOLD = 10;
  const HEADER_SCROLLED_ENTER_THRESHOLD = 24;
  const HEADER_SCROLLED_EXIT_THRESHOLD = 8;
  let hideTimer    = null;
  let isHidden     = false;
  let isScrolled   = window.scrollY > HEADER_SCROLLED_ENTER_THRESHOLD;
  let mobileNavOpen = false;

  function showHeader() {
    if (!isHidden) return;
    isHidden = false;
    header.classList.remove('header-hidden');
  }

  function hideHeader() {
    if (isHidden || mobileNavOpen || window.scrollY <= TOP_THRESHOLD) return;
    isHidden = true;
    header.classList.add('header-hidden');
  }

  function scheduleHide() {
    clearTimeout(hideTimer);
    hideTimer = setTimeout(hideHeader, HIDE_DELAY);
  }

  function syncHeaderScrolledState() {
    const scrollY = window.scrollY;
    if (!isScrolled && scrollY >= HEADER_SCROLLED_ENTER_THRESHOLD) {
      isScrolled = true;
    } else if (isScrolled && scrollY <= HEADER_SCROLLED_EXIT_THRESHOLD) {
      isScrolled = false;
    }
    header.classList.toggle('scrolled', isScrolled);
  }

  window.addEventListener('scroll', () => {
    const currentScrollY = window.scrollY;
    const isAtTop = currentScrollY <= TOP_THRESHOLD;

    if (isAtTop || mobileNavOpen) {
      showHeader();
      clearTimeout(hideTimer);
    } else {
      showHeader();
      scheduleHide();
    }

    syncHeaderScrolledState();
    if (backToTop) backToTop.classList.toggle('visible', currentScrollY > 400);
    highlightNav();
  }, { passive: true });

  if (header) {
    document.addEventListener('mousemove', e => {
      if (e.clientY < 80 && isHidden) {
        showHeader();
        scheduleHide();
      }
    });

    document.addEventListener('touchstart', () => {
      showHeader();
      scheduleHide();
    }, { passive: true });
  }

  if (burger && mobileNav) {
    burger.addEventListener('click', () => {
      const open = burger.classList.toggle('open');
      mobileNav.classList.toggle('open', open);
      mobileNavOpen = burger.classList.contains('open');
      body.classList.toggle('menu-open', mobileNavOpen);
      burger.setAttribute('aria-expanded', mobileNavOpen ? 'true' : 'false');
      showHeader();
      if (mobileNavOpen) {
        clearTimeout(hideTimer);
      } else {
        scheduleHide();
      }
      syncHeaderScrolledState();
    });

    document.querySelectorAll('.mobile-nav-link').forEach(l =>
      l.addEventListener('click', () => {
        burger.classList.remove('open');
        mobileNav.classList.remove('open');
        mobileNavOpen = false;
        body.classList.remove('menu-open');
        burger.setAttribute('aria-expanded', 'false');
        scheduleHide();
      })
    );
  }

  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const t = document.querySelector(a.getAttribute('href'));
      if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  });

  const slides = document.querySelectorAll('#hero .slide');
  const cdots  = document.querySelectorAll('#hero .carousel-dot');
  let curSlide = 0;

  if (slides.length && cdots.length) {
    function goSlide(idx) {
      slides[curSlide].classList.remove('active');
      cdots[curSlide].classList.remove('active');
      curSlide = ((idx % slides.length) + slides.length) % slides.length;
      slides[curSlide].classList.add('active');
      cdots[curSlide].classList.add('active');
    }

    cdots.forEach((dot, i) => dot.addEventListener('click', () => goSlide(i)));
    _carouselTimer = setInterval(() => goSlide(curSlide + 1), 6000);
  }

  const revEls = document.querySelectorAll('.reveal-up, .reveal-left, .reveal-right');
  if (revEls.length) {
    const revObs = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          e.target.classList.remove('hidden-up');
        } else {
          const rect = e.target.getBoundingClientRect();
          if (rect.top > 0) {
            e.target.classList.remove('visible');
            e.target.classList.remove('hidden-up');
          }
        }
      });
    }, { threshold: 0.10, rootMargin: '0px 0px -40px 0px' });

    revEls.forEach(el => revObs.observe(el));
  }

  const brandCards = document.querySelectorAll('.brand-card');
  if (brandCards.length) {
    const shouldTapAnimate = () => window.matchMedia('(max-width: 767px)').matches;
    const tapDuration = 160;

    const triggerTap = (card) => {
      if (!shouldTapAnimate()) return;
      card.classList.add('is-tapped');
      window.setTimeout(() => card.classList.remove('is-tapped'), tapDuration);
    };

    brandCards.forEach(card => {
      card.addEventListener('touchstart', () => triggerTap(card), { passive: true });
      card.addEventListener('click', () => triggerTap(card));
    });
  }

  document.querySelectorAll('.stat-num[data-count]').forEach(el => {
    const obs = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting) {
        const target = parseInt(el.dataset.count);
        let c = 0;
        const step = target / (1600 / 16);
        const timer = setInterval(() => {
          c = Math.min(c + step, target);
          el.textContent = Math.floor(c).toLocaleString();
          if (c >= target) clearInterval(timer);
        }, 16);
        obs.unobserve(el);
      }
    }, { threshold: 0.5 });
    obs.observe(el);
  });

  // ========== HISTORY TIMELINE - NEW VERSION ==========
  const histSection = document.getElementById('history');
  const cardsContainer = document.getElementById('histCardsContainer');

  if (cardsContainer && histSection) {

    const cards = Array.from(document.querySelectorAll('.history-card'));
    const allDots = Array.from(document.querySelectorAll('.hist-dot'));
    const histPages = Array.from(document.querySelectorAll('.hist-page'));
    const histPips = Array.from(document.querySelectorAll('.hist-page-pip'));
    const prevBtn = document.getElementById('histPrev');
    const nextBtn = document.getElementById('histNext');

    const ITEMS_PER_PAGE = 4;
    let activeIdx = 0;
    let isAnimating = false;
    let queuedIdx = null;
    const ANIM_DURATION = 680;

    // Build background images from hidden refs
    const HIST_BG = {};
    for (let i = 0; i < 7; i++) {
      const ref = document.getElementById('histBgRef' + i);
      HIST_BG[i] = ref ? ref.src : 'images/banner/UFDI-History-Timeline-BG-' + (i + 1) + '.png';
    }

    Object.values(HIST_BG).forEach(src => { new Image().src = src; });

    // Create background layer with fade transition
    const existingBgLayer = histSection.querySelector('.hist-bg-layer');
    if (existingBgLayer) existingBgLayer.remove();

    const bgLayer = document.createElement('div');
    bgLayer.className = 'hist-bg-layer';

    const imgA = document.createElement('img');
    const imgB = document.createElement('img');
    imgA.className = 'hist-bg-img is-active';
    imgB.className = 'hist-bg-img';
    imgA.alt = '';
    imgB.alt = '';
    imgA.setAttribute('aria-hidden', 'true');
    imgB.setAttribute('aria-hidden', 'true');

    // Force image-only styling on the actual timeline background images.
    const applyHistBgImgStyle = () => {
      const desktop = window.matchMedia('(min-width: 1024px)').matches;
      const mobile = window.matchMedia('(max-width: 767px)').matches;

      const width = mobile ? '100%' : (desktop ? '100%' : '90%');
      const height = mobile ? '92%' : (desktop ? '90%' : '82%');
      const radius = mobile ? '18px' : (desktop ? '12px' : '30px');
      const fit = (desktop || mobile) ? 'cover' : 'contain';

      [imgA, imgB].forEach((img) => {
        img.style.setProperty('width', width, 'important');
        img.style.setProperty('height', height, 'important');
        img.style.setProperty('border-radius', radius, 'important');
        img.style.setProperty('clip-path', `inset(0 round ${radius})`, 'important');
        img.style.setProperty('object-fit', fit, 'important');
        img.style.setProperty('object-position', 'center center', 'important');
      });
    };
    applyHistBgImgStyle();
    window.addEventListener('resize', applyHistBgImgStyle, { passive: true });

    imgA.src = HIST_BG[0];
    imgB.src = HIST_BG[0];

    bgLayer.appendChild(imgA);
    bgLayer.appendChild(imgB);
    histSection.insertBefore(bgLayer, histSection.firstChild);

    let topLayer = imgA;
    let botLayer = imgB;
    let bgTimer = null;
    let lastBgIdx = 0;
    let bgSwapToken = 0;

    function setHistBg(idx) {
      const nextSrc = HIST_BG[idx];
      if (!nextSrc || idx === lastBgIdx) return;

      lastBgIdx = idx;
      clearTimeout(bgTimer);

      const activeImg = imgA.classList.contains('is-active') ? imgA : imgB;
      const inactiveImg = activeImg === imgA ? imgB : imgA;
      const nextImg = inactiveImg;
      const prevImg = activeImg;
      const swapToken = ++bgSwapToken;
      let swapped = false;

      const finalizeSwap = () => {
        if (swapped || swapToken !== bgSwapToken) return;
        swapped = true;
        requestAnimationFrame(() => {
          requestAnimationFrame(() => {
            nextImg.classList.add('is-active');
            // Delay removing the previous active layer so there is always
            // at least one fully-painted image visible (prevents white flash).
            bgTimer = setTimeout(() => {
              prevImg.classList.remove('is-active');
              topLayer = nextImg;
              botLayer = prevImg;
            }, 60);
          });
        });
      };

      if (nextImg.src !== nextSrc) {
        nextImg.src = nextSrc;
      }

      if (nextImg.decode) {
        nextImg.decode().then(finalizeSwap).catch(finalizeSwap);
      } else if (nextImg.complete) {
        finalizeSwap();
      } else {
        nextImg.onload = () => {
          nextImg.onload = null;
          finalizeSwap();
        };
        nextImg.onerror = () => {
          nextImg.onerror = null;
          finalizeSwap();
        };
      }
    }

    function normalizeHistoryIndex(idx) {
      return ((idx % cards.length) + cards.length) % cards.length;
    }

    function applyHistoryTextDensity() {
      const isMobileHistory = window.matchMedia('(max-width: 767px)').matches;
      const densityClasses = ['hist-copy-long', 'hist-copy-xlong', 'hist-copy-compact'];
      const bottomSlack = isMobileHistory ? 18 : 12;

      cards.forEach((card) => {
        const content = card.querySelector('.hist-card-content');

        if (!content) return;

        content.classList.remove(...densityClasses);

        for (const densityClass of densityClasses) {
          if (content.scrollHeight <= (content.clientHeight - bottomSlack)) break;
          content.classList.add(densityClass);
        }
      });
    }

    function updateCards(idx) {
      idx = normalizeHistoryIndex(idx);
      if (isAnimating) {
        queuedIdx = idx;
        return;
      }
      if (idx === activeIdx) return;

      isAnimating = true;
      activeIdx = idx;

      cards.forEach((card, i) => {
        card.classList.remove('active', 'adjacent', 'adjacent-far', 'adjacent-left', 'adjacent-right');
        
        if (i === idx) {
          card.classList.add('active');
          const content = card.querySelector('.hist-card-content');
          if (content) {
            content.classList.remove('active');
            content.classList.add('active');
          }
        } else if (i === idx - 1 || (idx === 0 && i === cards.length - 1)) {
          card.classList.add('adjacent', 'adjacent-left');
        } else if (i === idx + 1 || (idx === cards.length - 1 && i === 0)) {
          card.classList.add('adjacent', 'adjacent-right');
        } else {
          card.classList.add('adjacent-far');
        }
      });

      allDots.forEach(d => d.classList.remove('active'));
      const activeDot = allDots.find(d => parseInt(d.dataset.idx) === idx);
      if (activeDot) activeDot.classList.add('active');

      const pageIdx = Math.floor(idx / ITEMS_PER_PAGE);
      histPages.forEach(p => p.classList.remove('active-page'));
      if (histPages[pageIdx]) histPages[pageIdx].classList.add('active-page');

      histPips.forEach((p, i) => p.classList.toggle('active', i === pageIdx));

      setHistBg(idx);
      applyHistoryTextDensity();

      setTimeout(() => {
        isAnimating = false;
        if (queuedIdx !== null) {
          const nextIdx = queuedIdx;
          queuedIdx = null;
          if (nextIdx !== activeIdx) {
            updateCards(nextIdx);
          }
        }
      }, ANIM_DURATION);
    }

    cards.forEach((card, i) => {
      card.addEventListener('click', () => {
        if (!card.classList.contains('active')) updateCards(i);
      });
    });

    allDots.forEach(d => {
      d.addEventListener('click', () => {
        const idx = parseInt(d.dataset.idx);
        if (!isNaN(idx) && idx !== activeIdx) updateCards(idx);
      });
    });

    histPips.forEach(p => {
      p.addEventListener('click', () => {
        const page = parseInt(p.dataset.page);
        if (!isNaN(page)) {
          const targetIdx = page * ITEMS_PER_PAGE;
          if (targetIdx !== activeIdx) updateCards(targetIdx);
        }
      });
    });

    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        prevBtn.style.transform = 'scale(0.9)';
        setTimeout(() => { prevBtn.style.transform = ''; }, 200);
        updateCards(activeIdx - 1);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        nextBtn.style.transform = 'scale(0.9)';
        setTimeout(() => { nextBtn.style.transform = ''; }, 200);
        updateCards(activeIdx + 1);
      });
    }

    // Initialize first card
    cards.forEach((card, i) => {
      if (i === 0) {
        card.classList.add('active');
        const content = card.querySelector('.hist-card-content');
        if (content) content.classList.add('active');
      } else if (i === 1) {
        card.classList.add('adjacent', 'adjacent-right');
      } else {
        card.classList.add('adjacent-far');
      }
    });

    allDots.forEach(d => d.classList.remove('active'));
    const firstDot = allDots.find(d => parseInt(d.dataset.idx) === 0);
    if (firstDot) firstDot.classList.add('active');

    applyHistoryTextDensity();
    window.addEventListener('resize', applyHistoryTextDensity, { passive: true });

    window.setHistBgAt = function (idx, newSrc) {
      HIST_BG[idx] = newSrc;
      lastBgIdx = -1;
      setHistBg(idx);
    };
  }

  const contactForm = document.getElementById('contactForm');
  const formSuccess = document.getElementById('formSuccess');
  const formError = document.getElementById('formError');
  const isVisualEditor = document.body?.dataset?.isVisualEditor === '1';
  let activeInquiryToast = null;
  const showInquiryToast = (message, type = 'success', options = {}) => {
    const persist = !!options.persist;
    const id = 'inquiry-toast-style';
    let style = document.getElementById(id);
    if (!style) {
      style = document.createElement('style');
      style.id = id;
      document.head.appendChild(style);
    }
    style.textContent = `
        .inquiry-toast{position:fixed;left:50%;top:50%;transform:translate(-50%,-50%) scale(.94);z-index:9999;min-width:320px;max-width:min(92vw,560px);padding:26px 28px;border-radius:20px;font-family:var(--font-body,"Asap",Arial,sans-serif);font-size:16px;font-weight:600;line-height:1.5;box-shadow:none;opacity:0;pointer-events:none;transition:opacity .42s ease,transform .58s cubic-bezier(.2,.85,.28,1);text-align:center}
        .inquiry-toast.is-show{opacity:1;transform:translate(-50%,-50%) scale(1)}
        .inquiry-toast--success{background:linear-gradient(180deg,#ffffff,#f7fff8);color:#1d8f3a;border:1px solid rgba(29,143,58,.25)}
        .inquiry-toast--loading{background:#ffffff;color:#1d8f3a;border:1px solid rgba(29,143,58,.25)}
        .inquiry-toast--error{background:#ffffff;color:#7f1d1d;border:1px solid rgba(127,29,29,.22)}
        .inquiry-toast__stack{display:flex;flex-direction:column;align-items:center;gap:14px}
        .inquiry-toast__message{color:#1d8f3a;font-weight:700}
        .inquiry-toast__icon{width:66px;height:66px;flex:0 0 66px;color:#20a54a;filter:none}
        .inquiry-toast__icon circle,.inquiry-toast__icon path{transform-box:fill-box;transform-origin:center}
        .inquiry-toast__icon circle{fill:none;stroke:currentColor;stroke-width:2.4;opacity:.92;stroke-dasharray:58;stroke-dashoffset:58;animation:inquiry-ring 1.18s cubic-bezier(.16,1,.3,1) forwards}
        .inquiry-toast__icon path{fill:none;stroke:currentColor;stroke-width:3.3;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:22;stroke-dashoffset:22;animation:inquiry-check .92s .78s cubic-bezier(.2,.9,.3,1) forwards}
        .inquiry-toast__loading-icon{width:62px;height:62px;flex:0 0 62px;color:#20a54a;animation:inquiry-spin 1.3s linear infinite}
        .inquiry-toast__loading-icon circle{fill:none;stroke:currentColor;stroke-width:2.8;stroke-linecap:round;stroke-dasharray:42 18;opacity:.95}
        .inquiry-toast--error .inquiry-toast__icon circle,.inquiry-toast--error .inquiry-toast__icon path{animation:none;transform:none;stroke-dasharray:none;stroke-dashoffset:0;opacity:.85}
        @keyframes inquiry-ring{0%{stroke-dashoffset:58;opacity:.25;transform:scale(.84)}65%{opacity:1}100%{stroke-dashoffset:0;opacity:1;transform:scale(1)}}
        @keyframes inquiry-check{0%{stroke-dashoffset:22;opacity:.35}100%{stroke-dashoffset:0;opacity:1}}
        @keyframes inquiry-spin{to{transform:rotate(360deg)}}
      `;
    if (activeInquiryToast && activeInquiryToast.parentNode) {
      activeInquiryToast.remove();
      activeInquiryToast = null;
    }
    const toast = document.createElement('div');
    toast.className = `inquiry-toast ${type === 'error' ? 'inquiry-toast--error' : 'inquiry-toast--success'}`;
    if (type === 'success') {
      toast.innerHTML = `
        <div class="inquiry-toast__stack">
          <span class="inquiry-toast__message">${String(message).replace(/[&<>"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c])}</span>
          <svg class="inquiry-toast__icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9.2"></circle>
            <path d="M7.7 12.6l2.9 2.9 5.8-6.1"></path>
          </svg>
        </div>
      `;
    } else if (type === 'loading') {
      toast.className = 'inquiry-toast inquiry-toast--loading';
      toast.innerHTML = `
        <div class="inquiry-toast__stack">
          <span class="inquiry-toast__message">${String(message).replace(/[&<>"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c])}</span>
          <svg class="inquiry-toast__loading-icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle>
          </svg>
        </div>
      `;
    } else {
      toast.textContent = message;
    }
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('is-show'));
    const closeToast = () => {
      toast.classList.remove('is-show');
      setTimeout(() => {
        if (toast.parentNode) toast.remove();
      }, 320);
    };
    if (persist) {
      activeInquiryToast = toast;
    } else {
      setTimeout(closeToast, 3600);
    }
    return {
      close: () => {
        if (activeInquiryToast === toast) activeInquiryToast = null;
        closeToast();
      },
    };
  };

  if (contactForm && formSuccess) {
    formSuccess.style.display = 'none';
    if (formError) formError.style.display = 'none';
    
    contactForm.addEventListener('submit', async e => {
      e.preventDefault();
      if (formError) formError.style.display = 'none';
      formSuccess.style.display = 'none';

      if (isVisualEditor) {
        showInquiryToast(formSuccess.textContent || "Thank you! We'll be in touch soon.", 'success');
        contactForm.reset();
        return;
      }

      const submitBtn = contactForm.querySelector('button[type="submit"]');
      const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
      let sendingToast = null;

      try {
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = 'Sending...';
        }
        sendingToast = showInquiryToast('Sending your message...', 'loading', { persist: true });

        const formData = new FormData(contactForm);
        if (!formData.get('page_slug')) {
          formData.append('page_slug', contactForm.dataset.pageSlug || 'ultrafood');
        }

        const submitUrl = contactForm.dataset.submitUrl || contactForm.getAttribute('action') || '/contact/send';
        const response = await fetch(submitUrl, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
          body: formData,
        });

        let payload = {};
        try {
          payload = await response.json();
        } catch (_) {
          payload = {};
        }

        if (!response.ok || !payload.success) {
          throw new Error(payload.message || 'Could not send your message.');
        }

        if (payload.message) {
          formSuccess.textContent = payload.message;
        }
        contactForm.reset();
        if (sendingToast && typeof sendingToast.close === 'function') sendingToast.close();
        const toastType = payload.mail_status && payload.mail_status !== 'sent' ? 'error' : 'success';
        showInquiryToast(formSuccess.textContent || "Thank you! We'll be in touch soon.", toastType);
      } catch (err) {
        if (sendingToast && typeof sendingToast.close === 'function') sendingToast.close();
        if (formError) {
          formError.textContent = err?.message || 'Something went wrong. Please try again.';
          showInquiryToast(formError.textContent, 'error');
        }
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnHtml;
        }
      }
    });
  }

  if (backToTop) {
    backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav-link');

  function highlightNav() {
    let current = '';
    sections.forEach(s => { if (window.scrollY >= s.offsetTop - 100) current = s.id; });
    navLinks.forEach(l => {
      l.classList.remove('active-link');
      if (l.getAttribute('href') === '#' + current) l.classList.add('active-link');
    });
  }

  syncHeaderScrolledState();
  if (navLinks.length) highlightNav();

}

window.initUltrafood = initUltrafood;

document.addEventListener('DOMContentLoaded', initUltrafood);
