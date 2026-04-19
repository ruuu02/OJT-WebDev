document.addEventListener('DOMContentLoaded', () => {
  const header = document.getElementById('nordicHeader');
  const burger = document.getElementById('nordicBurger');
  const buyDropdown = document.getElementById('buyDropdown');
  const buyToggle = document.getElementById('buyToggle');
  const backToTop = document.getElementById('backToTop');
  const nordicFooter = document.querySelector('.nordic-footer');
  const body = document.body;
  const recipeCards = Array.from(document.querySelectorAll('.recipe-card__link'));
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
    const baseBottom = window.matchMedia('(max-width: 479px)').matches ? 16 : 24;

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

  recipeCards.forEach((card) => {
    card.addEventListener('focus', () => card.parentElement.classList.add('focused'));
    card.addEventListener('blur', () => card.parentElement.classList.remove('focused'));
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
