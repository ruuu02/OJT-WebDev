document.addEventListener('DOMContentLoaded', () => {
  const header = document.getElementById('nordicHeader');
  const burger = document.getElementById('nordicBurger');
  const buyDropdown = document.getElementById('buyDropdown');
  const buyToggle = document.getElementById('buyToggle');
  const backToTop = document.getElementById('backToTop');
  const nordicFooter = document.querySelector('.nordic-footer');
  const body = document.body;
  const pageTitle = document.getElementById('detailsPageTitle');
  const cardTitle = document.getElementById('detailsCardTitle');
  const cardDescription = document.getElementById('detailsCardDescription');
  const sizeTitle = document.querySelector('.product-size-title');
  const buyTitle = document.querySelector('.product-buy-title');
  const sizesList = document.getElementById('detailsSizes');
  const featuresList = document.getElementById('detailsFeatures');
  const mainFlipCard = document.getElementById('detailsMainFlipCard');
  const mainImageFront = document.getElementById('detailsMainImageFront');
  const mainImageBack = document.getElementById('detailsMainImageBack');
  const variantButtons = Array.from(document.querySelectorAll('#detailsVariants .variant-thumb'));
  const shopeeLinks = Array.from(document.querySelectorAll('.js-link-shopee'));
  const lazadaLinks = Array.from(document.querySelectorAll('.js-link-lazada'));
  const tiktokLinks = Array.from(document.querySelectorAll('.js-link-tiktok'));
  const shopeeLabels = shopeeLinks.map((link) => link.querySelector('span')).filter(Boolean);
  const lazadaLabels = lazadaLinks.map((link) => link.querySelector('span')).filter(Boolean);
  const tiktokLabels = tiktokLinks.map((link) => link.querySelector('span')).filter(Boolean);

  const HIDE_DELAY = 1000;
  const TOP_THRESHOLD = 10;
  const BACK_TO_TOP_SHOW_THRESHOLD = 120;
  const HEADER_SCROLLED_ENTER_THRESHOLD = 24;
  const HEADER_SCROLLED_EXIT_THRESHOLD = 8;

  let headerHideTimer = null;
  let lastScrollY = window.scrollY;
  let headerIsScrolled = window.scrollY > HEADER_SCROLLED_ENTER_THRESHOLD;
  let activeProduct = null;
  let sizePills = [];

  const products = {
    'whole-grain-oats': {
      name: 'Whole Grain Oats',
      description: 'A hearty oat option with rich texture and naturally high fiber for balanced meals.',
      sizeTitle: 'Available Sizes',
      sizeLabels: { '1kg': '1kg', '500g': '500g', '250g': '250g' },
      buyTitle: 'Buy Online',
      buyLabels: { shopee: 'Shopee', lazada: 'Lazada', tiktok: 'TikTok Shop' },
      sizes: ['1kg', '500g', '250g'],
      sizeImages: {
        '1kg': '/images/nordic/Nordic-Oats-Whole-Grain-Rolled-Oats-1kg.png',
        '500g': '/images/nordic/product%20details/Nordic-Oats-Whole-Grain-Rolled-Oats-500g.png',
        '250g': '/images/nordic/product%20details/Nordic-Oats-Whole-Grain-Rolled-Oats-250g.png'
      },
      sizeBackImages: {
        '1kg': '/images/nordic/Nordic-Oats-Whole-Grain-Rolled-Oats-1kg-Back.png',
        '500g': '/images/nordic/product%20details/Nordic-Oats-Whole-Grain-Rolled-Oats-500g-Back.png',
        '250g': '/images/nordic/product%20details/Nordic-Oats-Whole-Grain-Rolled-Oats-250g-Back.png'
      },
      sizeLinks: {
        '1kg': {
          shopee: 'https://shopee.ph/Nordic-Oats-Whole-Grain-Rolled-Oats-1Kg-i.1281308282.28653490404?extraParams=%7B%22display_model_id%22%3A187601389598%2C%22model_selection_logic%22%3A3%7D&sp_atk=6415ff95-abea-471f-8856-1c6ae6340256&xptdk=6415ff95-abea-471f-8856-1c6ae6340256',
          lazada: 'https://www.lazada.com.ph/products/pdp-i4494582788.html?spm=a2o4l.searchlist.list.1.630b35f6xHqE0b',
          tiktok: 'https://vt.tiktok.com/ZS9dkMwEHfeyo-nWwOC/'
        },
        '500g': {
          shopee: 'https://shopee.ph/Nordic-Oats-Whole-Grain-Rolled-Oats-500g-i.1281308282.29157967397?extraParams=%7B%22display_model_id%22%3A217724765435%2C%22model_selection_logic%22%3A3%7D&sp_atk=46bec80d-a88e-49a3-b40a-e2b5698428eb&xptdk=46bec80d-a88e-49a3-b40a-e2b5698428eb',
          lazada: 'https://www.lazada.com.ph/products/pdp-i4583863963.html?spm=a2o4l.searchlist.list.3.630b35f6xHqE0b',
          tiktok: 'https://vt.tiktok.com/ZS9dkreajTudB-EifUf/'
        },
        '250g': {
          shopee: 'https://shopee.ph/Nordic-Oats-Whole-Grain-Rolled-Oats-250g-i.1281308282.29508737370?extraParams=%7B%22display_model_id%22%3A205331551052%2C%22model_selection_logic%22%3A3%7D&sp_atk=7f5929d0-c910-46dd-98b8-cfdf5639d7b2&xptdk=7f5929d0-c910-46dd-98b8-cfdf5639d7b2',
          lazada: 'https://www.lazada.com.ph/products/pdp-i4597009867.html?spm=a2o4l.searchlist.list.11.630b35f6xHqE0b',
          tiktok: 'https://vt.tiktok.com/ZS9dkrRdjRheT-OcpAw/'
        }
      },
      features: [
        'High in dietary fiber',
        'Great for hot meals and baking',
        'No artificial colorants'
      ]
    },
    'instant-oats': {
      name: 'Instant Oats',
      description: 'Quick-cooking oats made for busy mornings without sacrificing taste and nutrition.',
      sizeTitle: 'Available Sizes',
      sizeLabels: { '1kg': '1kg', '500g': '500g', '250g': '250g' },
      buyTitle: 'Buy Online',
      buyLabels: { shopee: 'Shopee', lazada: 'Lazada', tiktok: 'TikTok Shop' },
      sizes: ['1kg', '500g', '250g'],
      sizeImages: {
        '1kg': '/images/nordic/Nordic-Oats-Instant-Oatmeal-1kg.png',
        '500g': '/images/nordic/product%20details/Nordic-Oats-Instant-Oatmeal-500g.png',
        '250g': '/images/nordic/product%20details/Nordic-Oats-Instant-Oatmeal-250g.png'
      },
      sizeBackImages: {
        '1kg': '/images/nordic/Nordic-Oats-Instant-Oatmeal-1kg-Back.png',
        '500g': '/images/nordic/product%20details/Nordic-Oats-Instant-Oatmeal-500g-Back.png',
        '250g': '/images/nordic/product%20details/Nordic-Oats-Instant-Oatmeal-250g-Back.png'
      },
      sizeLinks: {
        '1kg': {
          shopee: 'https://shopee.ph/Nordic-Oats-Instant-Oatmeal-1Kg-i.1281308282.25482554287?extraParams=%7B%22display_model_id%22%3A215191886622%2C%22model_selection_logic%22%3A3%7D&sp_atk=104316d6-9e76-4bc3-8195-dde04ea46071&xptdk=104316d6-9e76-4bc3-8195-dde04ea46071',
          lazada: 'https://www.lazada.com.ph/products/pdp-i4541244341-s26021868805.html?spm=a2o4l.10450891.0.0.21962939LP8FIT&search=store&mp=3',
          tiktok: 'https://vt.tiktok.com/ZS9dkr4r2TdTM-Y5YSo/'
        },
        '500g': {
          shopee: 'https://shopee.ph/Nordic-Oats-Instant-Oatmeal-500g-i.1281308282.25584794385?extraParams=%7B%22display_model_id%22%3A215311751368%2C%22model_selection_logic%22%3A3%7D&sp_atk=79c2a19e-f7ff-42d4-a2b7-8760152a53e3&xptdk=79c2a19e-f7ff-42d4-a2b7-8760152a53e3',
          lazada: 'https://www.lazada.com.ph/products/pdp-i4583931582.html?spm=a2o4l.searchlist.list.5.13ee2d61L2zytc',
          tiktok: 'https://vt.tiktok.com/ZS9dkr4cKeTp1-cENPn/'
        },
        '250g': {
          shopee: 'https://shopee.ph/Nordic-Oats-Instant-Oatmeal-250g-i.1281308282.28408736892?extraParams=%7B%22display_model_id%22%3A251436502260%2C%22model_selection_logic%22%3A3%7D&sp_atk=51205216-17d4-4d7a-bca9-758c48bc87e5&xptdk=51205216-17d4-4d7a-bca9-758c48bc87e5',
          lazada: 'https://www.lazada.com.ph/products/pdp-i4596964952.html?spm=a2o4l.searchlist.list.1.13ee2d61L2zytc',
          tiktok: 'https://vt.tiktok.com/ZS9dkrqESG2J7-yblL4/'
        }
      },
      features: [
        'Ready in minutes',
        'Smooth texture for easy eating',
        'Perfect for breakfast bowls and shakes'
      ]
    },
    'quick-cook-oats': {
      name: 'Quick Cook Oats',
      description: 'A balanced choice between texture and speed, ideal for everyday meals and recipes.',
      sizeTitle: 'Available Sizes',
      sizeLabels: { '1kg': '1kg', '500g': '500g', '250g': '250g' },
      buyTitle: 'Buy Online',
      buyLabels: { shopee: 'Shopee', lazada: 'Lazada', tiktok: 'TikTok Shop' },
      sizes: ['1kg', '500g', '250g'],
      sizeImages: {
        '1kg': '/images/nordic/Nordic-Oats-Quick-Cook-Oatmeal-1kg.png',
        '500g': '/images/nordic/product%20details/Nordic-Oats-Quick-Cook-Oatmeal-500g.png',
        '250g': '/images/nordic/product%20details/Nordic-Oats-Quick-Cook-Oatmeal-250g.png'
      },
      sizeBackImages: {
        '1kg': '/images/nordic/Nordic-Oats-Quick-Cook-Oatmeal-1kg-Back.png',
        '500g': '/images/nordic/product%20details/Nordic-Oats-Quick-Cook-Oatmeal-500g-Back.png',
        '250g': '/images/nordic/product%20details/Nordic-Oats-Quick-Cook-Oatmeal-250g-Back.png'
      },
      sizeLinks: {
        '1kg': {
          shopee: 'https://shopee.ph/Nordic-Oats-Quick-Cook-Oatmeal-1Kg-i.1281308282.29903488654?extraParams=%7B%22display_model_id%22%3A251067032661%2C%22model_selection_logic%22%3A3%7D&sp_atk=9917f839-8ad2-4adb-9b48-9cefa7b5347e&xptdk=9917f839-8ad2-4adb-9b48-9cefa7b5347e',
          lazada: 'https://www.lazada.com.ph/products/pdp-i4494473850.html?spm=a2o4l.searchlist.list.1.777250b1MpTuwE',
          tiktok: 'https://vt.tiktok.com/ZS9dkhR4vwN7X-kEQcQ/'
        },
        '500g': {
          shopee: 'https://shopee.ph/Nordic-Oats-Quick-Cook-Oatmeal-500g-i.1281308282.27857972879?extraParams=%7B%22display_model_id%22%3A246384366864%2C%22model_selection_logic%22%3A3%7D&sp_atk=6817ad60-1ad7-430c-876b-cb538807ec8c&xptdk=6817ad60-1ad7-430c-876b-cb538807ec8c',
          lazada: 'https://www.lazada.com.ph/products/pdp-i4583864856.html?spm=a2o4l.searchlist.list.7.777250b1MpTuwE',
          tiktok: 'https://vt.tiktok.com/ZS9dkhL9kjb3G-VyjFS/'
        },
        '250g': {
          shopee: 'https://shopee.ph/Nordic-Oats-Quick-Cook-Oatmeal-250g-i.1281308282.28658742211?extraParams=%7B%22display_model_id%22%3A207745076390%2C%22model_selection_logic%22%3A3%7D&sp_atk=cdc226d6-65db-456b-b194-f44ed1c7ff95&xptdk=cdc226d6-65db-456b-b194-f44ed1c7ff95',
          lazada: 'https://www.lazada.com.ph/products/pdp-i4597029715.html?spm=a2o4l.searchlist.list.9.777250b1MpTuwE',
          tiktok: 'https://vt.tiktok.com/ZS9dkhYu7xnfw-GwqVG/'
        }
      },
      features: [
        'Cooks faster than whole grain oats',
        'Works well for porridge and savory dishes',
        'Consistent texture for daily use'
      ]
    }
  };

  const productOverrides = window.NORDIC_PRODUCT_DETAIL_OVERRIDES || {};
  Object.entries(productOverrides).forEach(([slug, override]) => {
    if (!products[slug] || !override || typeof override !== 'object') return;
    if (override.name) {
      products[slug].name = override.name;
    }
    if (override.description) {
      products[slug].description = override.description;
    }
    if (override.sizeTitle) {
      products[slug].sizeTitle = override.sizeTitle;
    }
    if (override.buyTitle) {
      products[slug].buyTitle = override.buyTitle;
    }
    if (override.sizeLabels && typeof override.sizeLabels === 'object') {
      products[slug].sizeLabels = { ...products[slug].sizeLabels, ...override.sizeLabels };
    }
    if (override.buyLabels && typeof override.buyLabels === 'object') {
      products[slug].buyLabels = { ...products[slug].buyLabels, ...override.buyLabels };
    }
    if (Array.isArray(override.features) && override.features.length) {
      products[slug].features = override.features.filter((feature) => String(feature || '').trim() !== '');
    }
    if (override.sizeImages && typeof override.sizeImages === 'object') {
      products[slug].sizeImages = {
        ...products[slug].sizeImages,
        ...override.sizeImages,
      };
    }
    if (override.frontImage1kg) {
      products[slug].sizeImages = {
        ...products[slug].sizeImages,
        '1kg': override.frontImage1kg,
      };
    }
    if (override.sizeBackImages && typeof override.sizeBackImages === 'object') {
      products[slug].sizeBackImages = {
        ...products[slug].sizeBackImages,
        ...override.sizeBackImages,
      };
    }
    if (override.backImage1kg) {
      products[slug].sizeBackImages = {
        ...products[slug].sizeBackImages,
        '1kg': override.backImage1kg,
      };
    }
    if (override.sizeLinks && typeof override.sizeLinks === 'object') {
      products[slug].sizeLinks = {
        ...products[slug].sizeLinks,
        ...Object.keys(override.sizeLinks).reduce((carry, sizeKey) => {
          carry[sizeKey] = {
            ...(products[slug].sizeLinks?.[sizeKey] || {}),
            ...(override.sizeLinks?.[sizeKey] || {}),
          };
          return carry;
        }, {}),
      };
    }
  });

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

  function setLinkGroup(elements, href) {
    elements.forEach((link) => {
      link.href = href || '#';
    });
  }

  function applyBuyLinksBySize(product, size) {
    const linksBySize = product?.sizeLinks?.[size] || {};
    setLinkGroup(shopeeLinks, linksBySize.shopee);
    setLinkGroup(lazadaLinks, linksBySize.lazada);
    setLinkGroup(tiktokLinks, linksBySize.tiktok);
  }

  function sizeLabelForProduct(product, size) {
    return product?.sizeLabels?.[size] || size;
  }

  function requestedSizeFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const rawSize = String(params.get('size') || '').trim().toLowerCase();
    if (!rawSize) return null;

    if (rawSize === '1kg' || rawSize === '500g' || rawSize === '250g') {
      return rawSize;
    }

    return null;
  }

  function setActiveSize(size) {
    if (!activeProduct) return;
    const sizeLabel = sizeLabelForProduct(activeProduct, size);
    const frontImageUrl = activeProduct.sizeImages?.[size] || activeProduct.sizeImages?.['1kg'];
    const backImageUrl = activeProduct.sizeBackImages?.[size]
      || activeProduct.sizeBackImages?.['1kg']
      || frontImageUrl;

    if (mainImageFront && frontImageUrl) {
      mainImageFront.src = frontImageUrl;
      mainImageFront.alt = `${activeProduct.name} ${sizeLabel} front`;
    }

    if (mainImageBack && backImageUrl) {
      mainImageBack.src = backImageUrl;
      mainImageBack.alt = `${activeProduct.name} ${sizeLabel} back`;
    }

    if (mainFlipCard) {
      mainFlipCard.dataset.size = size;
    }

    setMainFlipState(false);

    variantButtons.forEach((button) => {
      const isActive = button.dataset.size === size;
      button.classList.toggle('is-active', isActive);
    });

    sizePills.forEach((pill) => {
      const isActive = pill.dataset.size === size;
      pill.classList.toggle('is-active', isActive);
    });

    applyBuyLinksBySize(activeProduct, size);
  }

  function setMainFlipState(isFlipped) {
    if (!mainFlipCard) return;
    mainFlipCard.classList.toggle('is-flipped', isFlipped);
    mainFlipCard.setAttribute('aria-pressed', isFlipped ? 'true' : 'false');
  }

  function renderSizes(product, activeSize) {
    if (!sizesList) return;
    sizesList.innerHTML = '';
    sizePills = [];

    product.sizes.forEach((size) => {
      const sizeLabel = sizeLabelForProduct(product, size);
      const sizePill = document.createElement('button');
      sizePill.type = 'button';
      sizePill.className = `size-pill${size === activeSize ? ' is-active' : ''}`;
      sizePill.dataset.size = size;
      sizePill.textContent = sizeLabel;
      sizePill.setAttribute('aria-label', `${product.name} ${sizeLabel}`);
      sizePill.addEventListener('click', (event) => {
        event.preventDefault();
        setActiveSize(size);
      });
      sizesList.appendChild(sizePill);
      sizePills.push(sizePill);
    });
  }

  function renderProduct(product) {
    if (!product) return;
    activeProduct = product;
    const requestedSize = requestedSizeFromUrl();
    const defaultSize = requestedSize && product.sizes.includes(requestedSize) ? requestedSize : '1kg';

    if (pageTitle) pageTitle.textContent = product.name;
    if (cardTitle) cardTitle.textContent = product.name;
    if (cardDescription) cardDescription.textContent = product.description;
    if (sizeTitle) sizeTitle.textContent = product.sizeTitle || 'Available Sizes';
    if (buyTitle) buyTitle.textContent = product.buyTitle || 'Buy Online';
    shopeeLabels.forEach((label) => { label.textContent = product.buyLabels?.shopee || 'Shopee'; });
    lazadaLabels.forEach((label) => { label.textContent = product.buyLabels?.lazada || 'Lazada'; });
    tiktokLabels.forEach((label) => { label.textContent = product.buyLabels?.tiktok || 'TikTok Shop'; });

    renderSizes(product, defaultSize);
    setActiveSize(defaultSize);

    variantButtons.forEach((button) => {
      const size = button.dataset.size;
      const variantImage = button.querySelector('img');
      const variantLabel = button.querySelector('span');
      const imageUrl = product.sizeImages?.[size];
      const sizeLabel = sizeLabelForProduct(product, size);
      if (variantImage && imageUrl) {
        variantImage.src = imageUrl;
        variantImage.alt = `${product.name} ${sizeLabel}`;
      }
      if (variantLabel) variantLabel.textContent = String(sizeLabel || '').toUpperCase();
    });

    if (featuresList) {
      featuresList.innerHTML = '';
      (product.features || []).forEach((feature) => {
        if (!String(feature || '').trim()) return;
        const li = document.createElement('li');
        li.textContent = feature;
        featuresList.appendChild(li);
      });
    }
  }

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
      backToTop.classList.toggle('visible', currentScrollY > BACK_TO_TOP_SHOW_THRESHOLD);
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

  variantButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const size = button.dataset.size;
      if (!size) return;
      setActiveSize(size);
    });
  });

  mainFlipCard?.addEventListener('click', () => {
    const isFlipped = mainFlipCard.classList.contains('is-flipped');
    setMainFlipState(!isFlipped);
  });

  mainFlipCard?.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    event.preventDefault();
    const isFlipped = mainFlipCard.classList.contains('is-flipped');
    setMainFlipState(!isFlipped);
  });

  const productKey = document.body?.dataset.product || 'whole-grain-oats';
  renderProduct(products[productKey] || products['whole-grain-oats']);
  syncHeaderScrolledState();
  if (backToTop) {
    backToTop.classList.toggle('visible', window.scrollY > BACK_TO_TOP_SHOW_THRESHOLD);
    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
  syncBackToTopFooterOffset();
});
