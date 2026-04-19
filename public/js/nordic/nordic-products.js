function initNordicProducts() {
  if (typeof window.__nordicProductsCleanup === 'function') {
    window.__nordicProductsCleanup();
    window.__nordicProductsCleanup = null;
  }

  const header = document.getElementById('nordicHeader');
  const burger = document.getElementById('nordicBurger');
  const buyDropdown = document.getElementById('buyDropdown');
  const buyToggle = document.getElementById('buyToggle');
  const backToTop = document.getElementById('backToTop');
  const nordicFooter = document.querySelector('.nordic-footer');
  const body = document.body;
  const product3dStage = document.getElementById('product3dStage');
  const productListTitle = document.querySelector('.nordic-products-list-title');
  const productBreadcrumbCurrent = document.querySelector('.nordic-products-list-text .is-current');
  const product3dCards = Array.from(document.querySelectorAll('#product3dStage .product-3d-card'));
  const detailsBaseUrl = document.querySelector('.nordic-products-carousel')?.dataset.detailsUrl || '/nordic/products/details';
  const params = new URLSearchParams(window.location.search);
  const requestedProduct = params.get('product');
  const has3dCarousel = Boolean(product3dStage && product3dCards.length);

  if (!header) {
    return;
  }

  const HIDE_DELAY = 1000;
  const TOP_THRESHOLD = 10;
  const BACK_TO_TOP_SHOW_THRESHOLD = 120;
  const HEADER_SCROLLED_ENTER_THRESHOLD = 24;
  const HEADER_SCROLLED_EXIT_THRESHOLD = 8;
  let current3dIndex = 0;
  let isStageAnimating = false;
  let titleAnimationTimer = null;
  let headerHideTimer = null;
  let stageAnimationTimer = null;
  let lastScrollY = window.scrollY;
  let headerIsScrolled = window.scrollY > HEADER_SCROLLED_ENTER_THRESHOLD;

  const showHeader = () => {
    header.classList.remove('header-hidden');
  };

  const syncHeaderScrolledState = () => {
    const scrollY = window.scrollY;

    if (!headerIsScrolled && scrollY >= HEADER_SCROLLED_ENTER_THRESHOLD) {
      headerIsScrolled = true;
    } else if (headerIsScrolled && scrollY <= HEADER_SCROLLED_EXIT_THRESHOLD) {
      headerIsScrolled = false;
    }

    header.classList.toggle('scrolled', headerIsScrolled);
  };

  const scheduleHeaderHide = () => {
    clearTimeout(headerHideTimer);
    headerHideTimer = setTimeout(() => {
      if (!header.classList.contains('mobile-open') && !buyDropdown?.classList.contains('open')) {
        header.classList.add('header-hidden');
      }
    }, HIDE_DELAY);
  };

  const syncSelectedProductInUrl = (productKey) => {
    if (!productKey) return;
    const nextUrl = new URL(window.location.href);
    nextUrl.searchParams.set('product', productKey);
    window.history.replaceState({}, '', nextUrl);
  };

  const setProductListTitle = (nextTitle, nextFieldKey = '') => {
    if (!productListTitle || !nextTitle) return;
    const currentTitle = productListTitle.textContent?.trim();
    const currentFieldKey = productListTitle.getAttribute('data-ve-field') || '';
    if (currentTitle === nextTitle && currentFieldKey === nextFieldKey) return;

    productListTitle.classList.add('is-updating');
    if (titleAnimationTimer) {
      clearTimeout(titleAnimationTimer);
    }

    titleAnimationTimer = setTimeout(() => {
      productListTitle.textContent = nextTitle;
      if (nextFieldKey) {
        productListTitle.setAttribute('data-ve-field', nextFieldKey);
      }
      productListTitle.classList.remove('is-updating');
      titleAnimationTimer = null;
    }, 110);
  };

  const setProductBreadcrumbCurrent = (nextTitle, nextFieldKey = '') => {
    if (!productBreadcrumbCurrent || !nextTitle) return;
    const currentTitle = productBreadcrumbCurrent.textContent?.trim();
    const currentFieldKey = productBreadcrumbCurrent.getAttribute('data-ve-field') || '';
    if (currentTitle === nextTitle && currentFieldKey === nextFieldKey) return;

    productBreadcrumbCurrent.textContent = nextTitle;
    if (nextFieldKey) {
      productBreadcrumbCurrent.setAttribute('data-ve-field', nextFieldKey);
    }
  };

  const apply3dClasses = () => {
    const leftIndex = (current3dIndex - 1 + product3dCards.length) % product3dCards.length;
    const rightIndex = (current3dIndex + 1) % product3dCards.length;

    product3dCards.forEach((card, index) => {
      card.classList.remove('is-left', 'is-center', 'is-right');

      if (index === current3dIndex) {
        card.classList.add('is-center');
      } else if (index === leftIndex) {
        card.classList.add('is-left');
      } else if (index === rightIndex) {
        card.classList.add('is-right');
      }

      if (index !== current3dIndex) {
        card.classList.remove('is-flipped');
      }

      const flipButton = card.querySelector('.product-flip-toggle');
      if (flipButton) {
        const isCenterCard = index === current3dIndex;
        flipButton.disabled = !isCenterCard;
        flipButton.tabIndex = isCenterCard ? 0 : -1;
        flipButton.setAttribute('aria-hidden', isCenterCard ? 'false' : 'true');
        flipButton.setAttribute('aria-pressed', card.classList.contains('is-flipped') ? 'true' : 'false');
        flipButton.textContent = card.classList.contains('is-flipped') ? 'Show Front' : 'Show Back';
      }
    });

    const activeCard = product3dCards[current3dIndex];
    const activeName = activeCard?.dataset.title?.trim();
    const activeTitleKey = activeCard?.dataset.titleKey?.trim() || '';
    const activeProductKey = activeCard?.dataset.product;
    if (activeName) {
      setProductListTitle(activeName, activeTitleKey);
      setProductBreadcrumbCurrent(activeName, activeTitleKey);
    }
    syncSelectedProductInUrl(activeProductKey);
  };

  const goTo3d = (index) => {
    if (isStageAnimating) return;
    isStageAnimating = true;
    current3dIndex = (index + product3dCards.length) % product3dCards.length;
    apply3dClasses();
    if (stageAnimationTimer) {
      clearTimeout(stageAnimationTimer);
    }
    stageAnimationTimer = setTimeout(() => {
      isStageAnimating = false;
      stageAnimationTimer = null;
    }, 440);
  };

  const goToProductDetails = (productKey) => {
    let targetUrl = '';
    if (window.location.pathname.includes('/admin/visual-editor')) {
      const visualUrl = new URL(window.location.href);
      visualUrl.searchParams.set('page', 'nordic_product_details');
      if (productKey) {
        visualUrl.searchParams.set('product', productKey);
      } else {
        visualUrl.searchParams.delete('product');
      }
      targetUrl = visualUrl.toString();
    } else {
      const rawTarget = productKey
        ? detailsBaseUrl.replace('__SLUG__', encodeURIComponent(productKey))
        : detailsBaseUrl.replace(/\/__SLUG__$/, '');
      targetUrl = new URL(rawTarget, window.location.origin).toString();
    }
    window.location.assign(targetUrl);
  };

  const toggleCardFlip = (card) => {
    if (!card?.classList.contains('is-center')) return;
    card.classList.toggle('is-flipped');
    const flipButton = card.querySelector('.product-flip-toggle');
    if (flipButton) {
      const isFlipped = card.classList.contains('is-flipped');
      flipButton.setAttribute('aria-pressed', isFlipped ? 'true' : 'false');
      flipButton.textContent = isFlipped ? 'Show Front' : 'Show Back';
    }
  };

  const pointIsInsideRect = (x, y, rect) =>
    x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;

  const syncBackToTopFooterOffset = () => {
    if (!backToTop) return;
    const baseBottom = window.matchMedia('(max-width: 479px)').matches ? 16 : 24;

    if (!nordicFooter) {
      backToTop.style.bottom = `${baseBottom}px`;
      return;
    }

    const footerTop = nordicFooter.getBoundingClientRect().top;
    const overlap = Math.max(0, window.innerHeight - footerTop);
    backToTop.style.bottom = `${baseBottom + overlap}px`;
  };

  const onScroll = () => {
    const currentScrollY = window.scrollY;
    const isAtTop = currentScrollY <= TOP_THRESHOLD;
    const isScrollingDown = currentScrollY > lastScrollY;
    const keepHeaderOpen = header.classList.contains('mobile-open') || buyDropdown?.classList.contains('open');

    if (isAtTop || !isScrollingDown || keepHeaderOpen) {
      showHeader();
      clearTimeout(headerHideTimer);
    } else {
      showHeader();
      scheduleHeaderHide();
    }

    syncHeaderScrolledState();
    if (backToTop) {
      backToTop.classList.toggle('visible', currentScrollY > BACK_TO_TOP_SHOW_THRESHOLD);
    }
    syncBackToTopFooterOffset();
    lastScrollY = currentScrollY;
  };

  const onBurgerClick = () => {
    const isOpen = header.classList.toggle('mobile-open');
    body.classList.toggle('menu-open', isOpen);
    burger?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    showHeader();
    if (isOpen) {
      clearTimeout(headerHideTimer);
    } else {
      scheduleHeaderHide();
    }
    syncHeaderScrolledState();
  };

  const onBuyToggleClick = (event) => {
    event.stopPropagation();
    const isOpen = buyDropdown?.classList.toggle('open');
    buyToggle?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    showHeader();
    if (isOpen) {
      clearTimeout(headerHideTimer);
    } else {
      scheduleHeaderHide();
    }
  };

  const onDocumentClick = (event) => {
    if (!buyDropdown?.contains(event.target)) {
      buyDropdown?.classList.remove('open');
      buyToggle?.setAttribute('aria-expanded', 'false');
    }
  };

  const onDocumentKeydown = (event) => {
    if (event.key === 'Escape') {
      buyDropdown?.classList.remove('open');
      buyToggle?.setAttribute('aria-expanded', 'false');
    }
  };

  const onStageClick = (event) => {
    const stageRect = product3dStage.getBoundingClientRect();
    const flipButton = event.target instanceof Element
      ? event.target.closest('.product-flip-toggle')
      : null;
    if (flipButton) {
      const card = flipButton.closest('.product-3d-card');
      toggleCardFlip(card);
      return;
    }

    const clickedCard = event.target instanceof Element
      ? event.target.closest('.product-3d-card')
      : null;
    if (clickedCard) {
      const clickedIndex = product3dCards.indexOf(clickedCard);
      if (clickedIndex >= 0 && clickedIndex !== current3dIndex) {
        goTo3d(clickedIndex);
        return;
      }
    }

    const activeCard = product3dCards[current3dIndex];
    const activeImage = activeCard?.querySelector('.product-flip-face.is-front img');
    const activeImageRect = activeImage?.getBoundingClientRect();
    const clickedCenterImage = activeImageRect
      ? pointIsInsideRect(event.clientX, event.clientY, activeImageRect)
      : false;

    if (clickedCenterImage) {
      goToProductDetails(activeCard?.dataset.product);
      return;
    }

    const stageCenterX = stageRect.left + (stageRect.width / 2);
    if (event.clientX < stageCenterX) {
      goTo3d(current3dIndex - 1);
      return;
    }

    goTo3d(current3dIndex + 1);
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', syncBackToTopFooterOffset, { passive: true });
  burger?.addEventListener('click', onBurgerClick);
  buyToggle?.addEventListener('click', onBuyToggleClick);
  document.addEventListener('click', onDocumentClick);
  document.addEventListener('keydown', onDocumentKeydown);
  if (has3dCarousel) {
    product3dStage.addEventListener('click', onStageClick);

    if (requestedProduct) {
      const requestedIndex = product3dCards.findIndex((card) => card.dataset.product === requestedProduct);
      if (requestedIndex >= 0) {
        current3dIndex = requestedIndex;
      }
    }

    apply3dClasses();
  }
  syncHeaderScrolledState();
  if (backToTop) {
    backToTop.classList.toggle('visible', window.scrollY > BACK_TO_TOP_SHOW_THRESHOLD);
    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
  syncBackToTopFooterOffset();

  window.__nordicProductsCleanup = () => {
    clearTimeout(titleAnimationTimer);
    clearTimeout(headerHideTimer);
    clearTimeout(stageAnimationTimer);
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('resize', syncBackToTopFooterOffset);
    burger?.removeEventListener('click', onBurgerClick);
    buyToggle?.removeEventListener('click', onBuyToggleClick);
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onDocumentKeydown);
    if (has3dCarousel) {
      product3dStage.removeEventListener('click', onStageClick);
    }
  };
}

window.initNordicProducts = initNordicProducts;

document.addEventListener('DOMContentLoaded', initNordicProducts);
