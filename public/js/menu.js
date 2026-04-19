// Core DOM references used across Menu page interactions.
const burgerBtn = document.getElementById("burgerBtn");
const body = document.body;
const siteHeader = document.querySelector(".site-header");
const menuSlider = document.getElementById("menuSlider");
const menuRow = document.getElementById("menuRow");
const menuSlidePrev = document.getElementById("menuSlidePrev");
const menuSlideNext = document.getElementById("menuSlideNext");
const imageTrack = document.getElementById("imageTrack");
const imageDots = document.getElementById("imageDots");
const HEADER_HIDE_DELAY = 1000;
const TOP_THRESHOLD = 10;
const HEADER_SCROLLED_ENTER_THRESHOLD = 24;
const HEADER_SCROLLED_EXIT_THRESHOLD = 8;
const mobileDropdownMedia = window.matchMedia("(max-width: 900px)");

// Header visibility state for scroll-based hide/show behavior.
let headerHideTimer = null;
let lastScrollY = window.scrollY;
let headerIsScrolled = window.scrollY > HEADER_SCROLLED_ENTER_THRESHOLD;

const hasOpenDropdown = () => Boolean(document.querySelector(".dropdown[open]"));

// Show header immediately.
const showHeader = () => {
  siteHeader?.classList.remove("header-hidden");
};

const syncHeaderScrolledState = () => {
  if (!siteHeader) return;
  const scrollY = window.scrollY;

  if (!headerIsScrolled && scrollY >= HEADER_SCROLLED_ENTER_THRESHOLD) {
    headerIsScrolled = true;
  } else if (headerIsScrolled && scrollY <= HEADER_SCROLLED_EXIT_THRESHOLD) {
    headerIsScrolled = false;
  }

  siteHeader.classList.toggle("scrolled", headerIsScrolled);
};

// Hide header after a delay while scrolling down.
const scheduleHeaderHide = () => {
  if (!siteHeader) return;
  clearTimeout(headerHideTimer);
  headerHideTimer = setTimeout(() => {
    if (!body.classList.contains("menu-open") && !hasOpenDropdown()) {
      siteHeader.classList.add("header-hidden");
    }
  }, HEADER_HIDE_DELAY);
};

window.addEventListener(
  "scroll",
  () => {
    const currentScrollY = window.scrollY;
    const isAtTop = currentScrollY <= TOP_THRESHOLD;
    const isScrollingDown = currentScrollY > lastScrollY;
    const keepHeaderOpen = hasOpenDropdown();

    if (isAtTop || !isScrollingDown || keepHeaderOpen) {
      showHeader();
      clearTimeout(headerHideTimer);
    } else {
      showHeader();
      scheduleHeaderHide();
    }

    syncHeaderScrolledState();
    lastScrollY = currentScrollY;
  },
  { passive: true }
);

let closeDropdowns = () => {};

// Toggle mobile navigation drawer via burger button.
burgerBtn?.addEventListener("click", () => {
  const isOpen = body.classList.toggle("menu-open");
  burgerBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
  showHeader();
  if (isOpen) {
    clearTimeout(headerHideTimer);
  } else {
    closeDropdowns();
    scheduleHeaderHide();
  }
  syncHeaderScrolledState();
});

// Enable hover/focus behavior for nav dropdowns.
const initDropdownHover = () => {
  const dropdowns = Array.from(document.querySelectorAll(".dropdown"));
  const isMobileDropdownMode = () => mobileDropdownMedia.matches;
  const closeAll = (except = null) => {
    dropdowns.forEach((dropdown) => {
      if (dropdown !== except) {
        dropdown.open = false;
      }
    });
  };
  closeDropdowns = closeAll;

  dropdowns.forEach((dropdown) => {
    let closeTimer = null;
    const summary = dropdown.querySelector("summary");

    const open = () => {
      clearTimeout(closeTimer);
      closeAll(dropdown);
      dropdown.open = true;
    };

    const close = () => {
      closeTimer = setTimeout(() => {
        dropdown.open = false;
      }, 120);
    };

    dropdown.addEventListener("mouseenter", () => {
      if (isMobileDropdownMode()) return;
      open();
    });
    dropdown.addEventListener("mouseleave", () => {
      if (isMobileDropdownMode()) return;
      close();
    });
    dropdown.addEventListener("focusin", () => {
      if (isMobileDropdownMode()) return;
      open();
    });
    dropdown.addEventListener("focusout", (event) => {
      if (isMobileDropdownMode()) return;
      if (!dropdown.contains(event.relatedTarget)) {
        close();
      }
    });

    summary?.addEventListener("click", (event) => {
      if (isMobileDropdownMode()) {
        event.preventDefault();
        const willOpen = !dropdown.open;
        closeAll(willOpen ? dropdown : null);
        dropdown.open = willOpen;
        showHeader();
        clearTimeout(headerHideTimer);
        return;
      }

      window.requestAnimationFrame(() => {
        if (dropdown.open) {
          closeAll(dropdown);
        }
      });
    });
  });

  document.addEventListener("click", (event) => {
    if (!event.target.closest(".dropdown")) {
      closeAll();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeAll();
    }
  });
};

// Generic carousel helper used by gallery/menu sliders.
const initCarousel = (
  track,
  items,
  prevBtn,
  nextBtn,
  dotsWrap,
  intervalMs = 3000,
  dotClass = "menu-dot"
) => {
  if (!track || !items.length) return;

  const baseItems = Array.from(items);
  const slideCount = baseItems.length;
  if (slideCount <= 1) return;

  let currentIndex = 0;
  let maxIndex = 0;
  let step = 0;
  let timer = null;
  let dots = [];
  let isDragging = false;
  let dragStartX = 0;
  let dragDeltaX = 0;
  let activePointerId = null;
  let isTransitioning = false;
  let isLoopMode = false;
  let leadingClone = null;
  let trailingClone = null;
  const viewport = track.parentElement;
  const transitionValue = "transform 0.55s ease";

  const normalizeIndex = (index) => {
    if (!slideCount) return 0;
    return (index % slideCount + slideCount) % slideCount;
  };

  const ensureLoopClones = () => {
    if (isLoopMode || slideCount <= 1) return;
    leadingClone = baseItems[slideCount - 1].cloneNode(true);
    trailingClone = baseItems[0].cloneNode(true);
    leadingClone.setAttribute("aria-hidden", "true");
    trailingClone.setAttribute("aria-hidden", "true");
    track.insertBefore(leadingClone, track.firstChild);
    track.appendChild(trailingClone);
    isLoopMode = true;
  };

  const removeLoopClones = () => {
    if (!isLoopMode) return;
    leadingClone?.remove();
    trailingClone?.remove();
    leadingClone = null;
    trailingClone = null;
    isLoopMode = false;
  };

  const activeDotIndex = () => (isLoopMode ? normalizeIndex(currentIndex) : currentIndex);
  const visualIndex = () => (isLoopMode ? currentIndex + 1 : currentIndex);

  const render = () => {
    track.style.transform = `translateX(-${visualIndex() * step}px)`;
    const activeIndex = activeDotIndex();
    dots.forEach((dot, idx) => {
      dot.classList.toggle("active", idx === activeIndex);
      dot.setAttribute("aria-pressed", idx === activeIndex ? "true" : "false");
    });
  };

  const buildDots = () => {
    if (!dotsWrap) return;
    dotsWrap.innerHTML = "";
    dots = [];

    const totalDots = isLoopMode ? slideCount : maxIndex + 1;
    for (let i = 0; i < totalDots; i += 1) {
      const dot = document.createElement("button");
      dot.type = "button";
      dot.className = dotClass;
      dot.setAttribute("aria-label", `Go to slide ${i + 1}`);
      dot.addEventListener("click", () => {
        if (isTransitioning) return;
        if (activeDotIndex() === i) {
          startAutoplay();
          return;
        }
        currentIndex = i;
        track.style.transition = transitionValue;
        isTransitioning = true;
        render();
        startAutoplay();
      });
      dotsWrap.appendChild(dot);
      dots.push(dot);
    }
  };

  const nextSlide = () => {
    if (isTransitioning) return;
    if (isLoopMode) {
      normalizeLoopIndexForStep();
      currentIndex = Math.min(slideCount, currentIndex + 1);
    } else {
      currentIndex = currentIndex >= maxIndex ? 0 : currentIndex + 1;
    }
    track.style.transition = transitionValue;
    isTransitioning = true;
    render();
  };

  const prevSlide = () => {
    if (isTransitioning) return;
    if (isLoopMode) {
      normalizeLoopIndexForStep();
      currentIndex = Math.max(-1, currentIndex - 1);
    } else {
      currentIndex = currentIndex <= 0 ? maxIndex : currentIndex - 1;
    }
    track.style.transition = transitionValue;
    isTransitioning = true;
    render();
  };

  const recalc = () => {
    const viewportWidth = track.parentElement.clientWidth;
    const itemWidth = baseItems[0].getBoundingClientRect().width;
    const styles = getComputedStyle(track);
    const gap = parseFloat(styles.gap || styles.columnGap || 0);

    step = itemWidth + gap;
    const visibleCards = Math.max(1, Math.floor((viewportWidth + gap) / step));
    const shouldLoop = slideCount > 1 && visibleCards === 1;

    if (shouldLoop) {
      ensureLoopClones();
      maxIndex = slideCount - 1;
      currentIndex = normalizeIndex(currentIndex);
    } else {
      removeLoopClones();
      maxIndex = Math.max(0, slideCount - visibleCards);
      if (currentIndex > maxIndex) {
        currentIndex = maxIndex;
      }
      if (currentIndex < 0) {
        currentIndex = 0;
      }
    }

    buildDots();
    track.style.transition = "none";
    render();
    void track.offsetWidth;
    track.style.transition = transitionValue;
    isTransitioning = false;
  };

  const stopAutoplay = () => {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  };

  const snapToCurrent = () => {
    track.style.transition = transitionValue;
    render();
  };

  const normalizeLoopPosition = () => {
    if (!isLoopMode) return;
    if (currentIndex !== slideCount && currentIndex !== -1) return;

    currentIndex = currentIndex === slideCount ? 0 : slideCount - 1;
    track.style.transition = "none";
    render();
    void track.offsetWidth;
    track.style.transition = transitionValue;
  };

  const normalizeLoopIndexForStep = () => {
    if (!isLoopMode) return;

    if (currentIndex === slideCount || currentIndex === -1) {
      currentIndex = currentIndex === slideCount ? 0 : slideCount - 1;
      return;
    }

    if (currentIndex > slideCount || currentIndex < -1) {
      currentIndex = normalizeIndex(currentIndex);
    }
  };

  const resetDragState = () => {
    isDragging = false;
    dragStartX = 0;
    dragDeltaX = 0;
    activePointerId = null;
    track.classList.remove("is-dragging");
  };

  const onPointerMove = (event) => {
    if (!isDragging || event.pointerId !== activePointerId) return;
    const rawDragDeltaX = event.clientX - dragStartX;
    // Keep drag within one slide width so loop mode never exposes a phantom page.
    dragDeltaX = Math.max(-step, Math.min(step, rawDragDeltaX));
    // Non-loop fallback keeps edge-locking to avoid dragging in place.
    const isPullingPastStart = !isLoopMode && currentIndex === 0 && dragDeltaX > 0;
    const isPullingPastEnd = !isLoopMode && currentIndex === maxIndex && dragDeltaX < 0;
    const effectiveDeltaX = (isPullingPastStart || isPullingPastEnd) ? 0 : dragDeltaX;
    track.style.transform = `translateX(${-(visualIndex() * step) + effectiveDeltaX}px)`;
  };

  const onPointerEnd = (event) => {
    if (!isDragging || event.pointerId !== activePointerId) return;

    const dragThreshold = Math.min(120, step * 0.18);
    let didSlideChange = false;

    if (dragDeltaX <= -dragThreshold) {
      currentIndex = isLoopMode
        ? currentIndex + 1
        : (currentIndex >= maxIndex ? 0 : currentIndex + 1);
      didSlideChange = true;
    } else if (dragDeltaX >= dragThreshold) {
      currentIndex = isLoopMode
        ? currentIndex - 1
        : (currentIndex <= 0 ? maxIndex : currentIndex - 1);
      didSlideChange = true;
    }

    isTransitioning = didSlideChange || Math.abs(dragDeltaX) > 0;
    snapToCurrent();
    resetDragState();
    startAutoplay();
  };

  const startAutoplay = () => {
    stopAutoplay();
    if (maxIndex === 0) return;
    timer = setInterval(nextSlide, intervalMs);
  };

  recalc();
  startAutoplay();

  window.addEventListener("resize", () => {
    recalc();
    startAutoplay();
  });

  track.addEventListener("mouseenter", stopAutoplay);
  track.addEventListener("mouseleave", startAutoplay);
  track.addEventListener("transitionend", (event) => {
    if (event.propertyName !== "transform") return;
    normalizeLoopPosition();
    isTransitioning = false;
  });
  track.querySelectorAll("img").forEach((image) => {
    image.addEventListener("dragstart", (event) => {
      event.preventDefault();
    });
  });
  viewport?.addEventListener("pointerdown", (event) => {
    if (event.pointerType === "mouse" && event.button !== 0) return;
    if (maxIndex === 0) return;
    if (isTransitioning) return;
    normalizeLoopPosition();

    event.preventDefault();
    isDragging = true;
    dragStartX = event.clientX;
    dragDeltaX = 0;
    activePointerId = event.pointerId;
    stopAutoplay();
    track.style.transition = "none";
    track.classList.add("is-dragging");
    viewport.setPointerCapture?.(event.pointerId);
  });
  viewport?.addEventListener("pointermove", onPointerMove);
  viewport?.addEventListener("pointerup", onPointerEnd);
  viewport?.addEventListener("pointercancel", onPointerEnd);
  viewport?.addEventListener("lostpointercapture", () => {
    if (!isDragging) return;
    isTransitioning = Math.abs(dragDeltaX) > 0;
    snapToCurrent();
    resetDragState();
    startAutoplay();
  });

  prevBtn?.addEventListener("click", () => {
    prevSlide();
    startAutoplay();
  });

  nextBtn?.addEventListener("click", () => {
    nextSlide();
    startAutoplay();
  });
};

// Horizontal carousel behavior for menu-card rows.
const initMenuRowCarousel = (viewport, track, prevBtn, nextBtn) => {
  if (!viewport || !track) return;
  const cards = Array.from(track.querySelectorAll(".menu-card"));
  if (!cards.length) return;

  let currentIndex = 0;
  let maxIndex = 0;
  let step = 0;

  const render = () => {
    track.style.transform = `translateX(-${currentIndex * step}px)`;
    prevBtn?.classList.toggle("menu-slide-btn-active", currentIndex > 0);
    nextBtn?.classList.toggle("menu-slide-btn-active", currentIndex < maxIndex);
  };

  const recalc = () => {
    const viewportWidth = viewport.clientWidth;
    const itemWidth = cards[0].getBoundingClientRect().width;
    const styles = getComputedStyle(track);
    const gap = parseFloat(styles.gap || styles.columnGap || 0);

    step = itemWidth + gap;
    const visibleCards = Math.max(1, Math.floor((viewportWidth + gap) / step));
    maxIndex = Math.max(0, cards.length - visibleCards);

    if (currentIndex > maxIndex) {
      currentIndex = maxIndex;
    }
    render();
  };

  prevBtn?.addEventListener("click", () => {
    currentIndex = Math.max(0, currentIndex - 1);
    render();
  });

  nextBtn?.addEventListener("click", () => {
    currentIndex = Math.min(maxIndex, currentIndex + 1);
    render();
  });

  window.addEventListener("resize", recalc, { passive: true });
  recalc();
};

// Hover pop animation for menu cards.
const initMenuCardHover = () => {
  const cards = Array.from(document.querySelectorAll('.menu-card[data-overlap-card="true"]'));
  if (!cards.length) return;

  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const hoverInKeyframes = [
    { transform: "translateY(0) scale(0.99)", boxShadow: "0 10px 24px rgba(15, 23, 42, 0.08)" },
    { transform: "translateY(-1px) scale(1.03)", boxShadow: "0 16px 28px rgba(15, 23, 42, 0.14)" },
  ];
  const hoverOutKeyframes = [
    { transform: "translateY(-1px) scale(1.03)", boxShadow: "0 16px 28px rgba(15, 23, 42, 0.14)" },
    { transform: "translateY(0) scale(1)", boxShadow: "0 10px 24px rgba(15, 23, 42, 0.08)" },
  ];

  cards.forEach((card) => {
    let animation = null;

    const animateCard = (keyframes, addClass) => {
      if (reduceMotion) {
        card.classList.toggle("is-popped", addClass);
        card.style.zIndex = addClass ? "40" : "1";
        card.style.transform = addClass ? "translateY(0) scale(1.01)" : "translateY(0) scale(1)";
        card.style.boxShadow = addClass
          ? "0 12px 22px rgba(15, 23, 42, 0.12)"
          : "0 10px 24px rgba(15, 23, 42, 0.08)";
        return;
      }

      animation?.cancel();
      card.classList.toggle("is-popped", addClass);
      card.style.zIndex = addClass ? "40" : "1";
      animation = card.animate(keyframes, {
        duration: 260,
        easing: "cubic-bezier(0.22, 1, 0.36, 1)",
        fill: "forwards",
      });
    };

    card.addEventListener("mouseenter", () => animateCard(hoverInKeyframes, true));
    card.addEventListener("mouseleave", () => animateCard(hoverOutKeyframes, false));
    card.addEventListener("focusin", () => animateCard(hoverInKeyframes, true));
    card.addEventListener("focusout", () => animateCard(hoverOutKeyframes, false));
  });
};

// Selection/click behavior for menu cards.
const initMenuCardClick = () => {
  const cards = Array.from(document.querySelectorAll('.menu-card[data-overlap-card="true"]'));
  if (!cards.length) return;

  const clearActive = () => {
    cards.forEach((card) => {
      card.classList.remove("is-selected");
      card.setAttribute("aria-pressed", "false");
    });
  };

  cards.forEach((card) => {
    card.setAttribute("role", "button");
    card.setAttribute("aria-pressed", "false");
    const title = card.querySelector("h3");

    const toggle = () => {
      const willSelect = !card.classList.contains("is-selected");
      clearActive();
      if (willSelect) {
        card.classList.add("is-selected");
        card.setAttribute("aria-pressed", "true");
        title?.animate(
          [
            { transform: "rotate(0deg)" },
            { transform: "rotate(-10deg)" },
            { transform: "rotate(-6deg)" },
          ],
          {
            duration: 420,
            easing: "cubic-bezier(0.22, 1, 0.36, 1)",
            fill: "forwards",
          }
        );
      } else {
        title?.animate(
          [{ transform: "rotate(-6deg)" }, { transform: "rotate(0deg)" }],
          { duration: 260, easing: "ease-out", fill: "forwards" }
        );
      }
    };

    card.addEventListener("click", toggle);
    card.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        toggle();
      }
    });
  });
};

// Swiper-like horizontal movement for circular category cards.
const initCategorySwiper = () => {
  const viewport = document.getElementById("categorySwiper");
  const track = document.getElementById("categorySwiperTrack");
  if (!viewport || !track) return null;

  const cards = Array.from(track.querySelectorAll(".category-circle-card"));
  if (!cards.length) return null;

  let currentIndex = 0;
  let step = 0;
  let maxIndex = 0;
  let timer = null;
  let isGridLayout = false;

  const render = () => {
    if (isGridLayout) {
      track.style.transform = "none";
      return;
    }
    track.style.transform = `translateX(-${currentIndex * step}px)`;
  };

  const recalc = () => {
    isGridLayout = getComputedStyle(track).display === "grid";
    if (isGridLayout) {
      currentIndex = 0;
      maxIndex = 0;
      step = 0;
      render();
      stopAutoplay();
      return;
    }

    const viewportWidth = viewport.clientWidth;
    const itemWidth = cards[0].getBoundingClientRect().width;
    const styles = getComputedStyle(track);
    const gap = parseFloat(styles.gap || styles.columnGap || 0);

    step = itemWidth + gap;
    const visibleCards = Math.max(1, Math.floor((viewportWidth + gap) / step));
    maxIndex = Math.max(0, cards.length - visibleCards);
    if (currentIndex > maxIndex) currentIndex = maxIndex;
    render();
  };

  const next = () => {
    if (maxIndex === 0) return;
    currentIndex = currentIndex >= maxIndex ? 0 : currentIndex + 1;
    render();
  };

  const prev = () => {
    if (maxIndex === 0) return;
    currentIndex = currentIndex <= 0 ? maxIndex : currentIndex - 1;
    render();
  };

  const stopAutoplay = () => {
    if (!timer) return;
    clearInterval(timer);
    timer = null;
  };

  const startAutoplay = () => {
    stopAutoplay();
    if (maxIndex === 0) return;
    timer = window.setInterval(next, 2000);
  };

  window.addEventListener("resize", recalc, { passive: true });
  viewport.addEventListener("mouseenter", stopAutoplay);
  viewport.addEventListener("mouseleave", startAutoplay);
  recalc();
  startAutoplay();
  return { next, prev };
};

// Category click handling: navigates to each card's linked page.
const initCategoryPopup = () => {
  const popup = document.getElementById("categoryPopup");
  const popupImage = document.getElementById("categoryPopupImage");
  const popupTitle = document.getElementById("categoryPopupTitle");
  const popupLink = document.getElementById("categoryPopupLink");
  const closeBtn = document.getElementById("categoryPopupClose");
  if (!popup || !popupImage || !popupTitle || !popupLink) return;

  const navigateToCardPage = (card) => {
    const link = card.getAttribute("data-category-link");
    if (!link || link === "#") return;
    window.location.href = link;
  };

  const closePopup = () => {
    popup.classList.remove("is-open");
    popup.setAttribute("aria-hidden", "true");
    body.classList.remove("popup-open");
  };

  document.addEventListener("click", (event) => {
    const card = event.target.closest(".category-circle-card");
    if (card) {
      navigateToCardPage(card);
      return;
    }
    if (event.target.closest("[data-popup-close='true']")) {
      closePopup();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closePopup();
      return;
    }
    const card = event.target.closest?.(".category-circle-card");
    if (card && (event.key === "Enter" || event.key === " ")) {
      event.preventDefault();
      navigateToCardPage(card);
    }
  });

  closeBtn?.addEventListener("click", closePopup);
};

// Press/click feedback class for category cards.
const initCategoryCardFx = () => {
  const cards = Array.from(document.querySelectorAll(".category-circle-card"));
  if (!cards.length) return;

  const activate = (card) => {
    card.classList.add("is-category-active");
    window.setTimeout(() => card.classList.remove("is-category-active"), 2000);
  };

  cards.forEach((card) => {
    card.addEventListener("mousedown", () => activate(card));
    card.addEventListener("touchstart", () => activate(card), { passive: true });
    card.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        activate(card);
      }
    });
  });
};

// Product detail gallery: clicking a thumbnail promotes it to the main slot.
const initProductDetailGallery = () => {
  const thumbs = Array.from(document.querySelectorAll(".menu-product-gallery-thumb"));
  const stageCards = Array.from(document.querySelectorAll("[data-gallery-slot]"));
  const mainImg = document.querySelector("[data-gallery-slot='main'] img");
  const leftImg = document.querySelector("[data-gallery-slot='left'] img");
  const rightImg = document.querySelector("[data-gallery-slot='right'] img");
  if (!thumbs.length || !mainImg || !leftImg || !rightImg) return;

  const images = thumbs.map((thumb) => thumb.getAttribute("data-gallery-image")).filter(Boolean);
  if (!images.length) return;

  const render = (activeIndex) => {
    const mainSrc = images[activeIndex] || images[0];
    const leftSrc = images[(activeIndex - 1 + images.length) % images.length] || mainSrc;
    const rightSrc = images[(activeIndex + 1) % images.length] || mainSrc;

    mainImg.src = mainSrc;
    leftImg.src = leftSrc;
    rightImg.src = rightSrc;

    stageCards.forEach((card) => card.classList.remove("is-active"));
    document.querySelector("[data-gallery-slot='main']")?.classList.add("is-active");

    thumbs.forEach((thumb, index) => {
      thumb.classList.toggle("is-active", index === activeIndex);
    });
  };

  const getIndexForSrc = (src) => images.findIndex((image) => src?.includes(image));

  stageCards.forEach((card) => {
    card.addEventListener("click", () => {
      const img = card.querySelector("img");
      const index = getIndexForSrc(img?.getAttribute("src"));
      if (index >= 0) render(index);
    });
    card.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        const img = card.querySelector("img");
        const index = getIndexForSrc(img?.getAttribute("src"));
        if (index >= 0) render(index);
      }
    });
  });

  const initialIndex = Math.max(0, thumbs.findIndex((thumb) => thumb.classList.contains("is-active")));
  render(initialIndex);
};

// Animated "pan flip" toggle that moves category cards next/prev.
const initPancakePanToggle = (swiperController) => {
  const toggle = document.getElementById("pancakePanToggle");
  const track = document.querySelector(".pancakepan-toggle-track");
  if (!toggle || !track) return;

  toggle.addEventListener("change", () => {
    track.classList.remove("is-flipping");
    // Force reflow to reliably retrigger animation on every click.
    void track.offsetWidth;
    track.classList.add("is-flipping");
    window.setTimeout(() => track.classList.remove("is-flipping"), 1300);

    if (toggle.checked) {
      swiperController?.next?.();
      return;
    }
    swiperController?.prev?.();
  });
};

// Fixed right-side scroll button: shows after scrolling down.
const initScrollTopButton = () => {
  const btn = document.querySelector(".menu-scroll-top");
  if (!btn) return;
  const showAtY = 400;
  const footer = document.querySelector(".menu-footer");

  const updateVisibility = () => {
    const shouldShow = window.scrollY > showAtY;
    btn.classList.toggle("is-visible", shouldShow);

    let footerOffset = 0;
    if (footer) {
      const footerRect = footer.getBoundingClientRect();
      const overlap = window.innerHeight - footerRect.top;
      footerOffset = overlap > 0 ? overlap + 16 : 0;
    }
    btn.style.setProperty("--scroll-top-footer-offset", `${Math.max(0, footerOffset)}px`);
    const atPageEnd = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
    btn.style.setProperty("--scroll-top-end-shift", atPageEnd ? "20px" : "0px");
  };

  window.addEventListener("scroll", updateVisibility, { passive: true });
  window.addEventListener("resize", updateVisibility, { passive: true });
  updateVisibility();
};

// Swipeable mini-showcases for product type previews on the all-products page.
const initProductTypeShowcases = () => {
  const showcases = Array.from(document.querySelectorAll("[data-product-showcase]"));
  if (!showcases.length) return;

  showcases.forEach((showcase) => {
    const viewport = showcase.querySelector(".menu-product-showcase__viewport");
    const track = showcase.querySelector(".menu-product-showcase__track");
    const dots = showcase.querySelector(".menu-product-showcase__dots");
    const slides = Array.from(showcase.querySelectorAll(".menu-product-showcase__slide"));
    const link = showcase.getAttribute("data-product-link");
    if (!viewport || !track || slides.length <= 1) return;

    let pointerStartX = 0;
    let pointerStartY = 0;
    let moved = false;

    viewport.addEventListener("pointerdown", (event) => {
      pointerStartX = event.clientX;
      pointerStartY = event.clientY;
      moved = false;
    });

    viewport.addEventListener("pointermove", (event) => {
      if (Math.abs(event.clientX - pointerStartX) > 10 || Math.abs(event.clientY - pointerStartY) > 10) {
        moved = true;
      }
    });

    showcase.addEventListener("click", (event) => {
      if (!link) return;
      if (event.target.closest(".menu-product-showcase__dots")) return;
      if (moved) {
        event.preventDefault();
        return;
      }
      window.location.href = link;
    });

    showcase.addEventListener("keydown", (event) => {
      if (!link) return;
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        window.location.href = link;
      }
    });

    initCarousel(
      track,
      slides,
      null,
      null,
      dots,
      2600,
      "menu-product-showcase-dot"
    );
  });
};

// Product detail type toggles for grouped variants like flavored/unflavored jelly.
const initProductTypeSwitchers = () => {
  const switchers = Array.from(document.querySelectorAll("[data-product-type-switch]"));
  if (!switchers.length) return;

  switchers.forEach((switcher) => {
    const buttons = Array.from(switcher.querySelectorAll("[data-type-target]"));
    const container = switcher.closest(".menu-product-info__panel");
    const groups = Array.from(container?.querySelectorAll("[data-type-group]") || []);
    const classificationRow = container?.querySelector(".menu-product-info__row--classification");
    const gallery = document.querySelector(".menu-product-gallery");
    const typeMedia = (() => {
      try {
        return JSON.parse(gallery?.getAttribute("data-type-media") || "{}");
      } catch {
        return {};
      }
    })();
    if (!buttons.length || !groups.length) return;

    const syncClassificationHeight = () => {
      if (!classificationRow) return;

      let maxHeight = 0;

      groups.forEach((group) => {
        const previousDisplay = group.style.display;
        const previousPosition = group.style.position;
        const previousVisibility = group.style.visibility;
        const previousWidth = group.style.width;

        group.style.display = "grid";
        group.style.position = "absolute";
        group.style.visibility = "hidden";
        group.style.width = `${classificationRow.clientWidth}px`;

        maxHeight = Math.max(maxHeight, group.offsetHeight);

        group.style.display = previousDisplay;
        group.style.position = previousPosition;
        group.style.visibility = previousVisibility;
        group.style.width = previousWidth;
      });

      classificationRow.style.height = `${maxHeight + 8}px`;
      classificationRow.style.minHeight = `${maxHeight + 8}px`;
    };

    const activate = (target) => {
      buttons.forEach((button) => {
        const isActive = button.getAttribute("data-type-target") === target;
        button.classList.toggle("is-active", isActive);
        button.setAttribute("aria-pressed", isActive ? "true" : "false");
      });

      groups.forEach((group) => {
        group.classList.toggle("is-active", group.getAttribute("data-type-group") === target);
      });

      const media = typeMedia?.[target];
      const images = Array.isArray(media?.images) ? media.images.filter(Boolean) : [];
      if (gallery && images.length) {
        const mainImg = gallery.querySelector("[data-gallery-slot='main'] img");
        const leftImg = gallery.querySelector("[data-gallery-slot='left'] img");
        const rightImg = gallery.querySelector("[data-gallery-slot='right'] img");
        const [mainSrc, leftSrc = images[0], rightSrc = images[0]] = images;

        if (mainImg) {
          mainImg.src = mainSrc;
          mainImg.alt = `${target} ${mainImg.alt || "product image"}`.trim();
        }

        if (leftImg) {
          leftImg.src = leftSrc;
          leftImg.alt = `${target} alternate image`;
        }

        if (rightImg) {
          rightImg.src = rightSrc;
          rightImg.alt = `${target} package view`;
        }
      }
    };

    buttons.forEach((button) => {
      button.addEventListener("click", () => activate(button.getAttribute("data-type-target")));
    });

    syncClassificationHeight();
    window.addEventListener("resize", syncClassificationHeight);
    activate(buttons.find((button) => button.classList.contains("is-active"))?.getAttribute("data-type-target"));
  });
};

// Mobile-only description clamp with a small see more / see less toggle.
const initProductDescriptionToggle = () => {
  const rows = Array.from(document.querySelectorAll(".menu-product-info__row--desc"));
  if (!rows.length) return;

  const mobileQuery = window.matchMedia("(max-width: 640px)");
  const collapsedLines = 3;
  const collapseLabel = " see more";
  const expandLabel = " see less";

  const setExpandedState = (desc, text, button, fullText, expanded) => {
    text.textContent = fullText;
    button.textContent = expandLabel;
    button.hidden = false;
    desc.classList.toggle("is-expanded", expanded);
    button.setAttribute("aria-expanded", expanded ? "true" : "false");
  };

  const setCollapsedState = (desc, text, button, fullText, lineHeight) => {
    const collapsedHeight = lineHeight * collapsedLines;
    const cleanText = fullText.trim();

    text.textContent = cleanText;
    button.textContent = expandLabel;
    button.hidden = false;

    if (desc.scrollHeight <= collapsedHeight + 4) {
      button.hidden = true;
      button.setAttribute("aria-expanded", "false");
      return;
    }

    let low = 0;
    let high = cleanText.length;
    let best = "";

    while (low <= high) {
      const mid = Math.floor((low + high) / 2);
      const candidate = cleanText.slice(0, mid).trimEnd();
      text.textContent = `${candidate}...`;
      button.textContent = collapseLabel;

      if (desc.scrollHeight <= collapsedHeight + 4) {
        best = candidate;
        low = mid + 1;
      } else {
        high = mid - 1;
      }
    }

    text.textContent = best ? `${best}...` : cleanText;
    button.textContent = collapseLabel;
    button.hidden = false;
    button.setAttribute("aria-expanded", "false");
  };

  const syncRow = (row) => {
    const desc = row.querySelector(".menu-product-info__desc");
    const text = row.querySelector(".menu-product-info__desc-text");
    const button = row.querySelector(".menu-product-info__desc-toggle");
    if (!desc || !text || !button) return;

    const fullText = text.dataset.fullText || text.textContent || "";
    text.dataset.fullText = fullText;

    const isMobile = mobileQuery.matches;
    desc.classList.remove("is-expanded");
    text.textContent = fullText;
    button.hidden = true;
    button.textContent = collapseLabel;
    button.setAttribute("aria-expanded", "false");
    button.onclick = null;

    if (!isMobile) return;

    const computed = window.getComputedStyle(desc);
    let lineHeight = Number.parseFloat(computed.lineHeight);
    if (!Number.isFinite(lineHeight)) {
      const fontSize = Number.parseFloat(computed.fontSize) || 16;
      lineHeight = fontSize * 1.45;
    }

    button.onclick = () => {
      const isExpanded = desc.classList.contains("is-expanded");
      if (isExpanded) {
        desc.classList.remove("is-expanded");
        setCollapsedState(desc, text, button, fullText, lineHeight);
        return;
      }

      setExpandedState(desc, text, button, fullText, true);
    };

    setCollapsedState(desc, text, button, fullText, lineHeight);
  };

  const syncAll = () => rows.forEach(syncRow);

  if (typeof mobileQuery.addEventListener === "function") {
    mobileQuery.addEventListener("change", syncAll);
  } else if (typeof mobileQuery.addListener === "function") {
    mobileQuery.addListener(syncAll);
  }

  window.addEventListener("resize", syncAll, { passive: true });
  syncAll();
};

// Keep product detail titles on a single line by shrinking the font size only when needed.
const initProductTitleFit = () => {
  const titles = Array.from(document.querySelectorAll(".menu-product-info__title"));
  if (!titles.length) return;

  const syncTitle = (title) => {
    const computed = window.getComputedStyle(title);
    const maxFontSize = Number.parseFloat(title.dataset.fitMaxFontSize || computed.fontSize);
    const minFontSize = Number.parseFloat(title.dataset.fitMinFontSize || "16");
    const lineHeightRatio = 1.06;

    if (!Number.isFinite(maxFontSize) || !Number.isFinite(minFontSize)) return;

    title.dataset.fitMaxFontSize = String(maxFontSize);
    title.style.fontSize = `${maxFontSize}px`;

    let current = maxFontSize;
    while (title.scrollWidth > title.clientWidth + 1 && current > minFontSize) {
      current -= 0.5;
      title.style.fontSize = `${current}px`;
    }

    title.style.lineHeight = lineHeightRatio;
  };

  const syncAll = () => titles.forEach(syncTitle);

  window.addEventListener("resize", syncAll, { passive: true });
  syncAll();
};

// Initialize all menu page UI behaviors.
initDropdownHover();
initMenuRowCarousel(menuSlider, menuRow, menuSlidePrev, menuSlideNext);
initMenuCardHover();
const categorySwiperController = initCategorySwiper();
initCategoryPopup();
initCategoryCardFx();
initProductDetailGallery();
initPancakePanToggle(categorySwiperController);
initScrollTopButton();
initProductTypeShowcases();
initProductTypeSwitchers();
initProductTitleFit();
syncHeaderScrolledState();

initCarousel(
  imageTrack,
  Array.from(imageTrack?.querySelectorAll(".gallery-slide") || []),
  null,
  null,
  imageDots,
  2000,
  "image-dot"
);
