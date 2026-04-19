document.addEventListener('DOMContentLoaded', () => {
  const header = document.getElementById('nordicHeader');
  const burger = document.getElementById('nordicBurger');
  const buyDropdown = document.getElementById('buyDropdown');
  const buyToggle = document.getElementById('buyToggle');
  const recipePdfBtns = Array.from(document.querySelectorAll('[data-recipe-action="pdf"]'));
  const recipeShareBtns = Array.from(document.querySelectorAll('[data-recipe-action="share"]'));
  const recipePage = document.querySelector('.nordic-recipe-details-page');
  const recipeLayout = document.querySelector('.recipe-details-layout');
  const recipeRight = document.querySelector('.recipe-details-right');
  const previewTooltip = document.getElementById('nordicIngredientProductPreview');
  const previewImage = previewTooltip?.querySelector('[data-preview-image]');
  const previewName = previewTooltip?.querySelector('[data-preview-name]');
  const previewMeta = previewTooltip?.querySelector('[data-preview-meta]');
  const ingredientProductTriggers = Array.from(
    document.querySelectorAll('.ingredients-card .nordic-product-preview-trigger[data-product-slug]')
  );
  const backToTop = document.getElementById('backToTop');
  const nordicFooter = document.querySelector('.nordic-footer');
  const body = document.body;
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

  const syncRecipePanelHeight = () => {
    if (!recipePage || !recipeLayout) return;

    if (window.matchMedia('(max-width: 1024px)').matches) {
      recipePage.style.removeProperty('--rd-panel-height');
      return;
    }

    const styles = window.getComputedStyle(recipePage);
    const bottomOffset = parseFloat(styles.getPropertyValue('--rd-scroll-bottom')) || 24;
    const top = recipeLayout.getBoundingClientRect().top;
    const height = Math.max(300, Math.floor(window.innerHeight - top - bottomOffset));
    recipePage.style.setProperty('--rd-panel-height', `${height}px`);
  };

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

  const supportsDesktopHoverPreview = () => {
    if (!window.matchMedia('(min-width: 1025px)').matches) return false;
    if (!window.matchMedia('(hover: hover)').matches) return false;
    if (!window.matchMedia('(pointer: fine)').matches) return false;
    return true;
  };

  const hideIngredientPreview = () => {
    if (!previewTooltip) return;
    previewTooltip.classList.remove('is-visible');
    previewTooltip.classList.remove('has-image');
    previewTooltip.setAttribute('aria-hidden', 'true');
  };

  const positionIngredientPreview = (clientX, clientY) => {
    if (!previewTooltip) return;

    const offset = 16;
    const minMargin = 12;
    let left = clientX + offset;
    let top = clientY + offset;
    const rect = previewTooltip.getBoundingClientRect();

    if (left + rect.width > window.innerWidth - minMargin) {
      left = Math.max(minMargin, clientX - rect.width - offset);
    }
    if (top + rect.height > window.innerHeight - minMargin) {
      top = Math.max(minMargin, clientY - rect.height - offset);
    }

    previewTooltip.style.left = `${left}px`;
    previewTooltip.style.top = `${top}px`;
  };

  const showIngredientPreview = (target, event) => {
    if (!previewTooltip) return;
    if (!supportsDesktopHoverPreview()) return;

    const name = String(target.getAttribute('data-product-name') || target.textContent || 'Product').trim();
    const size = String(target.getAttribute('data-product-size') || '').trim();
    const image = String(target.getAttribute('data-product-image') || '').trim();

    if (previewName) {
      previewName.textContent = name || 'Product';
    }
    if (previewMeta) {
      previewMeta.textContent = size || '';
    }
    if (previewImage) {
      if (image) {
        previewImage.src = image;
        previewImage.alt = `${name || 'Product'} preview`;
        previewImage.hidden = false;
        previewTooltip.classList.add('has-image');
      } else {
        previewImage.hidden = true;
        previewImage.removeAttribute('src');
        previewImage.alt = '';
        previewTooltip.classList.remove('has-image');
      }
    }

    previewTooltip.classList.add('is-visible');
    previewTooltip.setAttribute('aria-hidden', 'false');
    positionIngredientPreview(event.clientX, event.clientY);
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
    syncRecipePanelHeight();
    lastScrollY = currentScrollY;
  }, { passive: true });

  const syncRightPanelWheelScroll = (event) => {
    if (!recipeRight || window.matchMedia('(max-width: 1024px)').matches) return;
    if (recipeRight.scrollHeight <= recipeRight.clientHeight) return;
    recipeRight.scrollTop += event.deltaY;
    event.preventDefault();
  };

  recipeLayout?.addEventListener('wheel', syncRightPanelWheelScroll, { passive: false });
  window.addEventListener('resize', syncRecipePanelHeight, { passive: true });
  window.addEventListener('resize', syncBackToTopFooterOffset, { passive: true });
  window.addEventListener('orientationchange', syncRecipePanelHeight, { passive: true });
  window.addEventListener('load', syncRecipePanelHeight, { passive: true });

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

  recipePdfBtns.forEach((button) => {
    button.addEventListener('click', () => {
      const pdfUrl = button.dataset.pdfUrl;
      if (!pdfUrl) return;
      window.location.href = pdfUrl;
    });
  });

  recipeShareBtns.forEach((button) => {
    button.addEventListener('click', async () => {
      const labelEl = button.querySelector('[data-share-label]');
      const shareData = {
        title: document.title,
        text: 'Check out this Nordic recipe.',
        url: window.location.href,
      };

      try {
        if (navigator.share) {
          await navigator.share(shareData);
          return;
        }

        if (navigator.clipboard?.writeText) {
          await navigator.clipboard.writeText(window.location.href);
          const previousLabel = labelEl?.textContent || 'Share';
          if (labelEl) labelEl.textContent = 'Link Copied';
          window.setTimeout(() => {
            if (labelEl) labelEl.textContent = previousLabel || 'Share';
          }, 1800);
        }
      } catch (error) {
        console.error('Recipe share failed:', error);
      }
    });
  });

  if (previewTooltip && ingredientProductTriggers.length > 0) {
    ingredientProductTriggers.forEach((trigger) => {
      trigger.addEventListener('mouseenter', (event) => {
        showIngredientPreview(trigger, event);
      });

      trigger.addEventListener('mousemove', (event) => {
        if (!previewTooltip.classList.contains('is-visible')) return;
        positionIngredientPreview(event.clientX, event.clientY);
      });

      trigger.addEventListener('mouseleave', hideIngredientPreview);
    });

    window.addEventListener('scroll', hideIngredientPreview, { passive: true });
    window.addEventListener('resize', hideIngredientPreview, { passive: true });
  }

  syncHeaderScrolledState();
  if (backToTop) {
    backToTop.classList.toggle('visible', window.scrollY > 400);
    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
  syncBackToTopFooterOffset();
  syncRecipePanelHeight();
  window.setTimeout(syncRecipePanelHeight, 120);
});
