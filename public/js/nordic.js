document.addEventListener('DOMContentLoaded', () => {
  const header = document.getElementById('nordicHeader');
  const burger = document.getElementById('nordicBurger');
  const buyDropdown = document.getElementById('buyDropdown');
  const buyToggle = document.getElementById('buyToggle');
  const backToTop = document.getElementById('backToTop');
  const nordicFooter = document.querySelector('.nordic-footer');
  const body = document.body;
  const productCarousel = document.getElementById('productCarousel');
  const oatsBenefitsCarousel = document.getElementById('oatsBenefitsCarousel');
  const productCopyTitle = document.getElementById('productCopyTitle');
  const productCopyText = document.getElementById('productCopyText');
  const oatsBenefitsCopyTitle = document.getElementById('oatsBenefitsCopyTitle');
  const oatsBenefitsCopyText = document.getElementById('oatsBenefitsCopyText');
  const HIDE_DELAY = 1000;
  const TOP_THRESHOLD = 10;
  const HEADER_SCROLLED_ENTER_THRESHOLD = 24;
  const HEADER_SCROLLED_EXIT_THRESHOLD = 8;
  let headerHideTimer = null;
  let lastScrollY = window.scrollY;
  let headerIsScrolled = window.scrollY > HEADER_SCROLLED_ENTER_THRESHOLD;

  const showHeader = () => {
    header?.classList.remove('header-hidden');
  };

  const syncHeaderScrolledState = () => {
    if (!header) return;
    const scrollY = window.scrollY;

    if (!headerIsScrolled && scrollY >= HEADER_SCROLLED_ENTER_THRESHOLD) {
      headerIsScrolled = true;
    } else if (headerIsScrolled && scrollY <= HEADER_SCROLLED_EXIT_THRESHOLD) {
      headerIsScrolled = false;
    }

    header.classList.toggle('scrolled', headerIsScrolled);
  };

  const scheduleHeaderHide = () => {
    if (!header) return;
    clearTimeout(headerHideTimer);
    headerHideTimer = setTimeout(() => {
      if (!header.classList.contains('mobile-open') && !buyDropdown?.classList.contains('open')) {
        header.classList.add('header-hidden');
      }
    }, HIDE_DELAY);
  };

  const syncBackToTopFooterOffset = () => {
    if (!backToTop) return;
    const baseBottom = 24;

    if (!nordicFooter) {
      backToTop.style.bottom = `${baseBottom}px`;
      return;
    }

    const footerTop = nordicFooter.getBoundingClientRect().top;
    const overlap = Math.max(0, window.innerHeight - footerTop);
    backToTop.style.bottom = `${baseBottom + overlap}px`;
  };

  window.addEventListener('scroll', () => {
    const currentScrollY = window.scrollY;
    const isAtTop = currentScrollY <= TOP_THRESHOLD;
    const isScrollingDown = currentScrollY > lastScrollY;
    const keepHeaderOpen = header?.classList.contains('mobile-open') || buyDropdown?.classList.contains('open');

    if (isAtTop || !isScrollingDown || keepHeaderOpen) {
      showHeader();
      clearTimeout(headerHideTimer);
    } else {
      showHeader();
      scheduleHeaderHide();
    }

    syncHeaderScrolledState();
    if (backToTop) {
      backToTop.classList.toggle('visible', currentScrollY > 400);
    }
    syncBackToTopFooterOffset();
    lastScrollY = currentScrollY;
  }, { passive: true });

  window.addEventListener('resize', syncBackToTopFooterOffset, { passive: true });

  const parseCarouselCopy = (carouselEl, fallbackDetails = []) => {
    if (!carouselEl) return fallbackDetails;
    try {
      const parsed = JSON.parse(carouselEl.dataset.copy || '[]');
      if (!Array.isArray(parsed) || !parsed.length) return fallbackDetails;
      return parsed.map((item, index) => ({
        title: String(item?.title || fallbackDetails[index]?.title || ''),
        description: String(item?.description || fallbackDetails[index]?.description || ''),
        titleField: String(item?.title_field || fallbackDetails[index]?.titleField || ''),
        textField: String(item?.text_field || fallbackDetails[index]?.textField || '')
      }));
    } catch (error) {
      return fallbackDetails;
    }
  };

  const productDetails = parseCarouselCopy(productCarousel, [
    {
      title: 'Whole Grain Oats',
      description: 'Made from whole oat groats for a hearty texture and naturally rich fiber, ideal for filling breakfasts and wholesome recipes.',
      titleField: 'nordic_product_copy_title_1',
      textField: 'nordic_product_copy_text_1'
    },
    {
      title: 'Instant Oats',
      description: 'Pre-processed for fast preparation while keeping a creamy oat taste, perfect for busy mornings when you need a quick meal.',
      titleField: 'nordic_product_copy_title_2',
      textField: 'nordic_product_copy_text_2'
    },
    {
      title: 'Quick Cook Oats',
      description: 'Cut finer than whole oats so they cook in minutes with a soft bite, great for porridge, smoothies, and baking mixes.',
      titleField: 'nordic_product_copy_title_3',
      textField: 'nordic_product_copy_text_3'
    }
  ]);

  const oatsBenefitsDetails = parseCarouselCopy(oatsBenefitsCarousel, [
    {
      title: 'Heart Health Support',
      description: 'Oats are rich in fiber, support heart health, and help keep you full longer. They are a good source of nutrients for a balanced daily meal.',
      titleField: 'nordic_oats_benefits_copy_title_1',
      textField: 'nordic_oats_benefits_body_1'
    },
    {
      title: 'Digestive Wellness',
      description: 'Their soluble and insoluble fiber blend supports gut health and helps maintain smoother digestion through the day.',
      titleField: 'nordic_oats_benefits_copy_title_2',
      textField: 'nordic_oats_benefits_body_2'
    },
    {
      title: 'Steady Energy',
      description: 'Complex carbohydrates release energy gradually, helping you feel fueled and satisfied longer after meals.',
      titleField: 'nordic_oats_benefits_copy_title_3',
      textField: 'nordic_oats_benefits_body_3'
    }
  ]);

  burger?.addEventListener('click', () => {
    const isOpen = header.classList.toggle('mobile-open');
    body.classList.toggle('menu-open', isOpen);
    burger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    showHeader();
    if (isOpen) {
      clearTimeout(headerHideTimer);
    } else {
      scheduleHeaderHide();
    }
    syncHeaderScrolledState();
  });

  buyToggle?.addEventListener('click', (event) => {
    event.stopPropagation();
    const isOpen = buyDropdown?.classList.toggle('open');
    buyToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    showHeader();
    if (isOpen) {
      clearTimeout(headerHideTimer);
    } else {
      scheduleHeaderHide();
    }
  });

  document.addEventListener('click', (event) => {
    if (!buyDropdown?.contains(event.target)) {
      buyDropdown?.classList.remove('open');
      buyToggle?.setAttribute('aria-expanded', 'false');
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      buyDropdown?.classList.remove('open');
      buyToggle?.setAttribute('aria-expanded', 'false');
    }
  });

  function setupCarousel({ trackId, prevId, nextId, dotsId, autoMs = 4000, onSlideChange }) {
    const track = document.getElementById(trackId);
    if (!track) return;

    const prev = document.getElementById(prevId) || track.parentElement?.querySelector('.product-carousel-arrow.prev, .carousel-arrow.prev');
    const next = document.getElementById(nextId) || track.parentElement?.querySelector('.product-carousel-arrow.next, .carousel-arrow.next');
    const dots = Array.from(document.querySelectorAll(`#${dotsId} .dot`));
    const realSlides = Array.from(track.children);
    const totalSlides = realSlides.length;
    if (totalSlides <= 1) return;

    const leadingClone = realSlides[totalSlides - 1].cloneNode(true);
    const trailingClone = realSlides[0].cloneNode(true);
    leadingClone.setAttribute('aria-hidden', 'true');
    trailingClone.setAttribute('aria-hidden', 'true');
    track.insertBefore(leadingClone, track.firstChild);
    track.appendChild(trailingClone);

    const viewport = track.parentElement;
    let currentSlide = 0;
    let autoSlideTimer;
    let holdTimer = null;
    let isDragging = false;
    let dragStartX = 0;
    let dragDeltaX = 0;
    let activePointerId = null;
    let isTransitioning = false;

    const normalizeIndex = (index) => (index % totalSlides + totalSlides) % totalSlides;

    const applySlideState = () => {
      const activeSlide = normalizeIndex(currentSlide);
      dots.forEach((dot, dotIndex) => {
        dot.classList.toggle('active', dotIndex === activeSlide);
      });

      realSlides.forEach((slide, slideIndex) => {
        slide.classList.toggle('is-active', slideIndex === activeSlide);
      });

      if (typeof onSlideChange === 'function') {
        onSlideChange(activeSlide);
      }
    };

    const setTrackPosition = (animate = true) => {
      if (animate) {
        isTransitioning = true;
      }
      track.style.transition = animate ? 'transform 500ms ease-in-out' : 'none';
      track.style.transform = `translateX(-${(currentSlide + 1) * 100}%)`;
      applySlideState();
    };

    const goToSlide = (index, { animate = true } = {}) => {
      currentSlide = index;
      setTrackPosition(animate);
    };

    const normalizeLoopIndexForStep = () => {
      if (currentSlide === totalSlides || currentSlide === -1) {
        currentSlide = currentSlide === totalSlides ? 0 : totalSlides - 1;
        return;
      }

      if (currentSlide > totalSlides || currentSlide < -1) {
        currentSlide = normalizeIndex(currentSlide);
      }
    };

    const stepSlide = (direction) => {
      if (isTransitioning) return false;
      normalizeLoopIndexForStep();
      currentSlide = Math.max(-1, Math.min(totalSlides, currentSlide + direction));
      setTrackPosition(true);
      return true;
    };

    const startAutoSlide = () => {
      if (!autoMs) return;
      clearInterval(autoSlideTimer);
      autoSlideTimer = setInterval(() => {
        stepSlide(1);
      }, autoMs);
    };

    const stopAutoSlide = () => {
      clearInterval(autoSlideTimer);
      autoSlideTimer = null;
    };

    const isDesktopWhyNordic = () =>
      trackId === 'productCarouselTrack' && window.matchMedia('(min-width: 901px)').matches;

    const stopHoldSlide = () => {
      if (!holdTimer) return;
      clearInterval(holdTimer);
      holdTimer = null;
      startAutoSlide();
    };

    const startHoldSlide = (direction) => {
      if (!isDesktopWhyNordic()) return;
      stopAutoSlide();
      clearInterval(holdTimer);
      holdTimer = setInterval(() => {
        stepSlide(direction);
      }, 550);
    };

    const resetDragState = () => {
      isDragging = false;
      dragStartX = 0;
      dragDeltaX = 0;
      activePointerId = null;
      track.classList.remove('is-dragging');
    };

    const snapToCurrent = () => {
      goToSlide(currentSlide, { animate: Math.abs(dragDeltaX) > 0 });
    };

    const normalizeLoopPosition = () => {
      if (currentSlide !== totalSlides && currentSlide !== -1) return;
      currentSlide = currentSlide === totalSlides ? 0 : totalSlides - 1;
      setTrackPosition(false);
      void track.offsetWidth;
      track.style.transition = 'transform 500ms ease-in-out';
    };

    const onPointerMove = (event) => {
      if (!isDragging || event.pointerId !== activePointerId) return;
      const rawDragDeltaX = event.clientX - dragStartX;
      const maxDrag = viewport?.clientWidth || 320;
      // Keep drag within one slide width to avoid showing a phantom extra page.
      dragDeltaX = Math.max(-maxDrag, Math.min(maxDrag, rawDragDeltaX));
      track.style.transform = `translateX(calc(${-(currentSlide + 1) * 100}% + ${dragDeltaX}px))`;
    };

    const onPointerEnd = (event) => {
      if (!isDragging || event.pointerId !== activePointerId) return;
      const threshold = Math.min(120, (viewport?.clientWidth || 320) * 0.16);

      if (dragDeltaX <= -threshold) {
        currentSlide += 1;
      } else if (dragDeltaX >= threshold) {
        currentSlide -= 1;
      }

      snapToCurrent();
      resetDragState();
      startAutoSlide();
    };

    next?.addEventListener('click', () => {
      stepSlide(1);
      startAutoSlide();
    });

    prev?.addEventListener('click', () => {
      stepSlide(-1);
      startAutoSlide();
    });

    [next, prev].forEach((button, index) => {
      if (!button) return;
      const direction = index === 0 ? 1 : -1;

      button.addEventListener('pointerdown', () => {
        startHoldSlide(direction);
      });
      button.addEventListener('pointerup', stopHoldSlide);
      button.addEventListener('pointercancel', stopHoldSlide);
      button.addEventListener('pointerleave', stopHoldSlide);
    });

    dots.forEach((dot) => {
      dot.addEventListener('click', () => {
        if (isTransitioning) return;
        const targetSlide = Number(dot.dataset.slide);
        if (normalizeIndex(currentSlide) === targetSlide) {
          startAutoSlide();
          return;
        }
        normalizeLoopPosition();
        goToSlide(Number(dot.dataset.slide));
        startAutoSlide();
      });
    });

    track.addEventListener('transitionend', (event) => {
      if (event.propertyName !== 'transform') return;
      normalizeLoopPosition();
      isTransitioning = false;
    });

    track.querySelectorAll('img').forEach((image) => {
      image.addEventListener('dragstart', (event) => event.preventDefault());
    });

    viewport?.addEventListener('pointerdown', (event) => {
      if (event.pointerType === 'mouse' && event.button !== 0) return;
      if (event.target instanceof Element && event.target.closest('.product-carousel-arrow, .carousel-arrow, .dot')) {
        return;
      }
      if (isTransitioning) return;
      normalizeLoopPosition();
      event.preventDefault();
      stopAutoSlide();
      isDragging = true;
      dragStartX = event.clientX;
      dragDeltaX = 0;
      activePointerId = event.pointerId;
      track.style.transition = 'none';
      track.classList.add('is-dragging');
      viewport.setPointerCapture?.(event.pointerId);
    });

    viewport?.addEventListener('pointermove', onPointerMove);
    viewport?.addEventListener('pointerup', onPointerEnd);
    viewport?.addEventListener('pointercancel', onPointerEnd);
    viewport?.addEventListener('lostpointercapture', () => {
      if (!isDragging) return;
      snapToCurrent();
      resetDragState();
      startAutoSlide();
    });

    goToSlide(0, { animate: false });
    void track.offsetWidth;
    track.style.transition = 'transform 500ms ease-in-out';
    isTransitioning = false;
    startAutoSlide();
  }

  setupCarousel({
    trackId: 'carouselTrack',
    prevId: 'carouselPrev',
    nextId: 'carouselNext',
    dotsId: 'carouselDots',
    autoMs: 4000
  });

  setupCarousel({
    trackId: 'productCarouselTrack',
    prevId: 'productCarouselPrev',
    nextId: 'productCarouselNext',
    dotsId: 'productCarouselDots',
    autoMs: 4000,
    onSlideChange: (index) => {
      if (!productCopyTitle || !productCopyText || !productDetails[index]) return;
      productCopyTitle.textContent = productDetails[index].title;
      productCopyText.textContent = productDetails[index].description;
      if (productDetails[index].titleField) {
        productCopyTitle.setAttribute('data-ve-field', productDetails[index].titleField);
      }
      if (productDetails[index].textField) {
        productCopyText.setAttribute('data-ve-field', productDetails[index].textField);
      }
    }
  });

  setupCarousel({
    trackId: 'oatsBenefitsCarouselTrack',
    prevId: 'oatsBenefitsCarouselPrev',
    nextId: 'oatsBenefitsCarouselNext',
    dotsId: 'oatsBenefitsCarouselDots',
    autoMs: 4000,
    onSlideChange: (index) => {
      if (!oatsBenefitsCopyTitle || !oatsBenefitsCopyText || !oatsBenefitsDetails[index]) return;
      oatsBenefitsCopyTitle.textContent = oatsBenefitsDetails[index].title;
      oatsBenefitsCopyText.textContent = oatsBenefitsDetails[index].description;
      if (oatsBenefitsDetails[index].titleField) {
        oatsBenefitsCopyTitle.setAttribute('data-ve-field', oatsBenefitsDetails[index].titleField);
      }
      if (oatsBenefitsDetails[index].textField) {
        oatsBenefitsCopyText.setAttribute('data-ve-field', oatsBenefitsDetails[index].textField);
      }
    }
  });

  syncHeaderScrolledState();
  if (backToTop) {
    backToTop.classList.toggle('visible', window.scrollY > 400);
    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
  syncBackToTopFooterOffset();
});
