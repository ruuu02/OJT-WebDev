/**
 * Visual Editor — admin/visual-editor.js
 *
 * Reads CMS_SCHEMA (injected by the blade view), walks every zone for the
 * active page, wraps each matched DOM element in an .ve-edit-zone container,
 * and appends a pencil button that opens the correct modal pre-filled with
 * live data from ALL_CONTENTS / ALL_MEDIA / ALL_CONTACTS.
 *
 * Globals expected (set by the blade view before this script loads):
 *   CMS_SCHEMA   — full config/cms.php as JSON
 *   ACTIVE_PAGE  — current page slug string
 *   ALL_MEDIA    — all rows from carousel_images + logos + product_images + recipe_images
 *   ALL_CONTENTS — all rows from text_content + product_details + featured_recipes
 *   ALL_CONTACTS — all rows from contact_numbers + email_addresses + links
 *   ROUTES       — object with named Laravel route URLs
 */

/* ═══════════════════════════════════════════════════════════════════════════
   UTILITIES
   ═══════════════════════════════════════════════════════════════════════════ */

const CSRF = () => document.querySelector('meta[name="csrf-token"]').content;

/** POST / PUT / DELETE via fetch, returns parsed JSON. */
async function apiFetch(url, method = 'POST', body = null) {
  const opts = {
    method,
    headers: { 'X-CSRF-TOKEN': CSRF(), 'Accept': 'application/json' },
  };
  if (body instanceof FormData) {
    opts.body = body;
  } else if (body) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  }
  const res = await fetch(url, opts);
  const text = await res.text();
  try { return { ok: res.ok, status: res.status, data: JSON.parse(text) }; }
  catch { return { ok: res.ok, status: res.status, data: text }; }
}

function apiErrorMessage(data, fallback = 'Request failed.') {
  if (!data) return fallback;
  if (typeof data === 'string') return data || fallback;
  if (typeof data?.message === 'string' && data.message.trim()) return data.message.trim();
  if (data?.errors && typeof data.errors === 'object') {
    const firstError = Object.values(data.errors).flat().find(Boolean);
    if (firstError) return String(firstError);
  }
  return fallback;
}

async function loadImageForUpload(file) {
  if (typeof createImageBitmap === 'function') {
    try {
      return await createImageBitmap(file);
    } catch (e) {
      // Fall back to an <img> decoder below.
    }
  }

  const objectUrl = URL.createObjectURL(file);
  try {
    const img = await new Promise((resolve, reject) => {
      const el = new Image();
      el.onload = () => resolve(el);
      el.onerror = () => reject(new Error('Could not read the selected image.'));
      el.src = objectUrl;
    });
    return img;
  } finally {
    URL.revokeObjectURL(objectUrl);
  }
}

const VISUAL_EDITOR_IMAGE_MAX_BYTES = 1500 * 1024;
const VISUAL_EDITOR_IMAGE_MAX_DIMENSION = 1600;
const VISUAL_EDITOR_IMAGE_ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

async function normalizeUploadImage(file, options = {}) {
  if (!(file instanceof File)) return null;

  const {
    maxDimension = VISUAL_EDITOR_IMAGE_MAX_DIMENSION,
    maxBytes = VISUAL_EDITOR_IMAGE_MAX_BYTES,
    quality = 0.82,
    minQuality = 0.55,
    allowedTypes = VISUAL_EDITOR_IMAGE_ALLOWED_TYPES,
  } = options;

  const rawType = String(file.type || '').toLowerCase();
  const isAllowedType = allowedTypes.includes(rawType);
  const outputType = isAllowedType ? rawType : 'image/jpeg';
  const supportsAlpha = outputType === 'image/png' || outputType === 'image/webp';
  const supportsQuality = outputType !== 'image/png';

  if (isAllowedType && file.size <= maxBytes) {
    return file;
  }

  const image = await loadImageForUpload(file);
  const sourceWidth = image.width || image.naturalWidth || 0;
  const sourceHeight = image.height || image.naturalHeight || 0;
  if (!sourceWidth || !sourceHeight) {
    throw new Error('Could not read the selected image.');
  }

  const canvas = document.createElement('canvas');
  const renderBlob = async (targetWidth, targetHeight, outputQuality) => {
    canvas.width = targetWidth;
    canvas.height = targetHeight;

    const ctx = canvas.getContext('2d', { alpha: supportsAlpha });
    if (!ctx) {
      throw new Error('Could not prepare the selected image.');
    }

    if (!supportsAlpha) {
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, targetWidth, targetHeight);
    } else {
      ctx.clearRect(0, 0, targetWidth, targetHeight);
    }
    ctx.drawImage(image, 0, 0, targetWidth, targetHeight);

    return await new Promise((resolve) => {
      const encoderQuality = supportsQuality ? outputQuality : undefined;
      canvas.toBlob(resolve, outputType, encoderQuality);
    });
  };

  let attemptScale = Math.min(1, maxDimension / Math.max(sourceWidth, sourceHeight));
  let attemptQuality = quality;
  let blob = null;

  while (attemptScale > 0.2) {
    const targetWidth = Math.max(1, Math.round(sourceWidth * attemptScale));
    const targetHeight = Math.max(1, Math.round(sourceHeight * attemptScale));
    blob = await renderBlob(targetWidth, targetHeight, attemptQuality);

    if (!blob) break;
    if (blob.size <= maxBytes) {
      break;
    }

    if (supportsQuality && attemptQuality > minQuality) {
      attemptQuality = Math.max(minQuality, attemptQuality - 0.08);
      continue;
    }

    attemptScale *= 0.85;
    attemptQuality = quality;
  }

  if (!blob) {
    throw new Error('Could not process the selected image.');
  }

  if (blob.size > maxBytes) {
    throw new Error('The selected image is too large. Please choose a smaller image.');
  }

  const extensionByType = {
    'image/jpeg': 'jpg',
    'image/png': 'png',
    'image/webp': 'webp',
  };
  const baseName = (file.name || 'upload').replace(/\.[^.]+$/, '');
  const outputExtension = extensionByType[outputType] || 'jpg';
  return new File([blob], `${baseName}.${outputExtension}`, {
    type: outputType,
    lastModified: Date.now(),
  });
}

async function appendNormalizedImageToFormData(formData, fieldName, file) {
  if (!(formData instanceof FormData) || !(file instanceof File)) return;
  formData.delete(fieldName);
  const normalizedFile = await normalizeUploadImage(file);
  if (normalizedFile) {
    formData.append(fieldName, normalizedFile);
  }
}

/** Show a brief toast at the bottom of the screen. */
function toast(message, type = 'success') {
  const el = document.getElementById('ve-toast');
  el.textContent = message;
  el.className = `ve-toast ve-toast--${type} ve-toast--visible`;
  el.hidden = false;
  clearTimeout(el._timer);
  el._timer = setTimeout(() => {
    el.classList.remove('ve-toast--visible');
  }, 3200);
}

/** Escape HTML special chars for safe innerHTML insertion. */
function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/** Get image public URL from a media row (existing storage convention). */
function mediaUrl(row) {
  const sectionDir = {
    carousel: 'banner',
    logo:     'logo',
    product:  'banner',
    recipe:   'icons',
    menu:     'menu',
  };
  const dir = sectionDir[row.section] ?? row.section;
  return `/images/${dir}/${row.filename}`;
}

/**
 * Re-fetch the current visual editor page and swap #ve-canvas content,
 * then re-inject pencil overlays on the fresh DOM.
 * This makes text, image, and contact changes visible in the canvas
 * without a full page reload.
 */
let _refreshing = false;

function hoistCanvasStyles(sourceRoot = document) {
  const head = document.head;
  if (!head || !sourceRoot?.querySelectorAll) return;

  head.querySelectorAll('[data-ve-hoisted-style]').forEach((node) => node.remove());

  sourceRoot.querySelectorAll('#ve-canvas style').forEach((styleNode, index) => {
    const clone = document.createElement('style');
    clone.dataset.veHoistedStyle = '1';
    clone.dataset.veHoistedIndex = String(index);
    clone.textContent = styleNode.textContent || '';
    head.appendChild(clone);
  });
}

async function refreshCanvas() {
  if (_refreshing) return;
  _refreshing = true;
  try {
    // 1) Reload ALL_CONTENTS from DB so head-injected CMS colors match undo/save (no stale JS cache).
    const stateUrl =
      `${ROUTES.visualEditorContents}?page=${encodeURIComponent(ACTIVE_PAGE)}&_=${Date.now()}`;
    const stateRes = await fetch(stateUrl, {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (stateRes.ok) {
      const fresh = await stateRes.json();
      if (Array.isArray(fresh) && Array.isArray(ALL_CONTENTS)) {
        ALL_CONTENTS.length = 0;
        fresh.forEach((row) => ALL_CONTENTS.push(row));
      }
    }

    // 2) Full HTML must not be served from disk/browser cache or canvas stays visually stale.
    const htmlUrl = new URL(window.location.href);
    htmlUrl.searchParams.set('_ve', String(Date.now()));
    const res = await fetch(htmlUrl.toString(), {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        Accept: 'text/html',
        'Cache-Control': 'no-cache',
        Pragma: 'no-cache',
      },
    });
    if (!res.ok) return;
    const html   = await res.text();
    const parser = new DOMParser();
    const doc    = parser.parseFromString(html, 'text/html');
    const newCanvas = doc.getElementById('ve-canvas');
    if (!newCanvas) return;
    const canvas = document.getElementById('ve-canvas');
    canvas.innerHTML = newCanvas.innerHTML;

    // Nested views embed end-of-body CMS <style> that can override head rules if HTML was ever stale.
    canvas.querySelectorAll(
      '#ultrafood-cms-text-styles, #menu-cms-text-styles, #nordic-cms-text-styles'
    ).forEach((n) => n.remove());

    hoistCanvasStyles(document);
    applyVeCmsFieldStyles();
    buildOverlays();
    if (typeof window.initUltrafood === 'function') window.initUltrafood();
    if (typeof window.initNordicProducts === 'function') window.initNordicProducts();
  } catch (e) {
    console.warn('Canvas refresh failed:', e);
  } finally {
    _refreshing = false;
  }
}

/**
 * Write text field color/font/size from ALL_CONTENTS into document head so the
 * admin canvas shows saved styles. Nested full-page views inside #ve-canvas
 * often lose their own <style> when innerHTML is replaced after fetch.
 */
function applyVeCmsFieldStyles() {
  const el = document.getElementById('ve-cms-field-styles');
  if (!el || !Array.isArray(ALL_CONTENTS)) return;
  const byKey = {};
  ALL_CONTENTS.forEach(row => {
    if (row && row.key != null) byKey[row.key] = row.value;
  });
  const bases = new Set();
  Object.keys(byKey).forEach(k => {
    if (k.endsWith('_color')) bases.add(k.slice(0, -6));
    else if (k.endsWith('_font_size')) bases.add(k.slice(0, -10));
    else if (k.endsWith('_font')) bases.add(k.slice(0, -5));
  });
  const lines = [];
  const escBase = (base) =>
    (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(base) : String(base).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
  bases.forEach((base) => {
    const color = String(byKey[`${base}_color`] || '').trim();
    const font = String(byKey[`${base}_font`] || '').trim();
    const size = String(byKey[`${base}_font_size`] || '').trim();
    if (!color && !font && !size) return;
    const parts = [];
    if (color) {
      const hex = /^#/.test(color) ? normalizeHex(color) : color;
      parts.push(`color:${hex} !important`);
    }
    if (font) {
      const safe = font.replace(/'/g, "\\'");
      parts.push(`font-family:'${safe}',ui-sans-serif,system-ui,sans-serif !important`);
    }
    if (size) parts.push(`font-size:${size} !important`);
    lines.push(`#ve-canvas [data-ve-field="${escBase(base)}"]{${parts.join(';')}}`);
  });
  el.textContent = lines.join('\n');
  bases.forEach((base) => {
    const font = String(byKey[`${base}_font`] || '').trim();
    if (font) loadGoogleFont(font);
  });
}


/* ═══════════════════════════════════════════════════════════════════════════
   MODAL HELPERS
   ═══════════════════════════════════════════════════════════════════════════ */

let _activeModal = null;

function closeCanvasDropdowns() {
  const canvas = document.getElementById('ve-canvas');
  if (!canvas) return;

  canvas.querySelectorAll('details[open]').forEach((el) => {
    el.removeAttribute('open');
  });

  const burgerBtn = canvas.querySelector('#burgerBtn');
  if (burgerBtn) {
    burgerBtn.setAttribute('aria-expanded', 'false');
  }

  const mainNav = canvas.querySelector('#mainNav');
  if (mainNav) {
    mainNav.classList.remove('is-open', 'open', 'active');
  }
}

function openModal(id) {
  const el = document.getElementById(id);
  if (!el) return;
  closeCanvasDropdowns();
  closeModal();          // close any currently open modal first
  el.hidden = false;
  _activeModal = el;
  document.body.style.overflow = 'hidden';
  // Focus the first focusable element
  const first = el.querySelector('button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
  if (first) setTimeout(() => first.focus(), 60);
}

function closeModal(id = null) {
  const el = id ? document.getElementById(id) : _activeModal;
  if (!el) return;

  // If the style modal is dismissed without a successful save, restore the
  // live canvas preview to the values that were active when the modal opened.
  if (el.id === 've-modal-style' && !_styleSaved) {
    revertStyleLivePreview();
  }

  el.hidden = true;
  if (_activeModal === el) _activeModal = null;
  document.body.style.overflow = '';
}

/** Wire all static close buttons (data-modal attribute) and backdrop clicks. */
function initModalDismiss() {
  // Close buttons inside modals
  document.querySelectorAll('[data-modal]').forEach(btn => {
    btn.addEventListener('click', () => closeModal(btn.dataset.modal));
  });

  // Click outside the modal box
  document.querySelectorAll('.ve-modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', e => {
      if (e.target === backdrop) closeModal(backdrop.id);
    });
  });

  // ESC key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && _activeModal) closeModal();
  });
}


/* ═══════════════════════════════════════════════════════════════════════════
   OVERLAY INJECTION
   ═══════════════════════════════════════════════════════════════════════════ */

const PENCIL_SVG = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none"
  stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
</svg>`;

/**
 * Wrap an element in an .ve-edit-zone div and append a pencil button.
 * Returns the pencil button element.
 *
 * Nested zones are allowed (e.g. a strength-card text zone can contain a
 * separate strength-icon image zone inside it). We only skip an element
 * that has already been wrapped directly — identified by the data-ve-key
 * attribute placed on the element itself.
 */
function injectOverlay(el, zoneKey, zone) {
  // Skip if this exact element was already wrapped for this zone
  if (el.dataset.veKey) return null;
  el.dataset.veKey = zoneKey;

  if (zone?.overlay_mode === 'detached') {
    el.classList.add('ve-edit-target');
    el.dataset.zoneKey = zoneKey;

    const btn = document.createElement('button');
    btn.className = 've-pencil-btn ve-pencil-btn--detached';
    btn.type = 'button';
    btn.setAttribute('aria-label', `Edit: ${zone.label}`);
    btn.innerHTML = `${PENCIL_SVG}<span>${zone.label}</span>`;
    btn.dataset.zoneKey = zoneKey;
    btn.dataset.zoneType = zone.type;
    btn.style.position = 'fixed';
    btn.style.opacity = '0';
    btn.style.pointerEvents = 'none';

    const placeDetachedButton = () => {
      const rect = el.getBoundingClientRect();
      const width = btn.offsetWidth || 120;
      btn.style.top = `${Math.max(8, rect.top + 8)}px`;
      btn.style.left = `${Math.max(8, rect.right - width - 8)}px`;
    };

    const showDetachedButton = () => {
      placeDetachedButton();
      btn.style.opacity = '1';
      btn.style.pointerEvents = 'auto';
      btn.style.transform = 'translateY(0)';
    };

    const hideDetachedButton = () => {
      btn.style.opacity = '0';
      btn.style.pointerEvents = 'none';
      btn.style.transform = 'translateY(-4px)';
    };

    requestAnimationFrame(placeDetachedButton);
    window.addEventListener('scroll', placeDetachedButton, { passive: true });
    window.addEventListener('resize', placeDetachedButton);
    el.addEventListener('mouseenter', showDetachedButton);
    el.addEventListener('mouseleave', hideDetachedButton);
    btn.addEventListener('mouseenter', showDetachedButton);
    btn.addEventListener('mouseleave', hideDetachedButton);

    document.body.appendChild(btn);
    return btn;
  }

  if (zone?.overlay_mode === 'inplace') {
    el.classList.add('ve-edit-target');
    el.dataset.zoneKey = zoneKey;
    if (window.getComputedStyle(el).position === 'static') {
      el.style.position = 'relative';
    }

    const btn = document.createElement('button');
    btn.className = 've-pencil-btn';
    btn.type = 'button';
    btn.setAttribute('aria-label', `Edit: ${zone.label}`);
    btn.innerHTML = `${PENCIL_SVG}<span>${zone.label}</span>`;
    btn.dataset.zoneKey = zoneKey;
    btn.dataset.zoneType = zone.type;
    el.appendChild(btn);
    return btn;
  }

  const wrapper = document.createElement('div');
  wrapper.className = 've-edit-zone';
  wrapper.dataset.zoneKey = zoneKey;

  // Move el into wrapper (preserving its place in the DOM)
  el.parentNode.insertBefore(wrapper, el);
  wrapper.appendChild(el);

  const btn = document.createElement('button');
  btn.className = 've-pencil-btn';
  btn.setAttribute('aria-label', `Edit: ${zone.label}`);
  btn.innerHTML = `${PENCIL_SVG}<span>${zone.label}</span>`;

  // Store zone data on the button for the click handler
  btn.dataset.zoneKey  = zoneKey;
  btn.dataset.zoneType = zone.type;

  wrapper.appendChild(btn);
  return btn;
}

/**
 * Walk every zone in the active page's schema, find its DOM element,
 * inject the overlay, and bind the pencil-click handler.
 */
function buildOverlays() {
  const pageSchema = CMS_SCHEMA[ACTIVE_PAGE];
  if (!pageSchema) return;

  const canvas = document.getElementById('ve-canvas');

  // ── Step 1: collect ALL elements BEFORE any DOM mutation ──────────────
  // This is critical: injecting overlays wraps elements and changes the DOM,
  // which breaks :nth-child selectors for subsequent querySelector calls.
  // By collecting first, every selector resolves against the original DOM.
  const collected = Object.entries(pageSchema.zones).map(([zoneKey, zone]) => {
    const el = canvas
      ? canvas.querySelector(zone.selector)
      : document.querySelector(zone.selector);
    return { zoneKey, zone, el };
  });

  // Menu category product names (mcli_{id}_name): same text modal + Save + Undo as other zones
  const mcliCollected = [];
  if (canvas && typeof ACTIVE_PAGE === 'string' && (ACTIVE_PAGE.startsWith('menu_cat_') || ACTIVE_PAGE === 'menu_item_details' || ACTIVE_PAGE === 'menu_recipe_category')) {
    canvas.querySelectorAll('[data-ve-field]').forEach((el) => {
      const fieldKey = el.getAttribute('data-ve-field');
      if (!fieldKey || (!/^mcli_\d+_name$/.test(fieldKey) && !/^mr_\d+_name$/.test(fieldKey))) return;
      if (el.dataset.veKey) return;
      const zoneKey = /^mr_\d+_name$/.test(fieldKey) ? `mr_zone_${fieldKey}` : `mcli_zone_${fieldKey}`;
      const zone = {
        type: 'text',
        label: /^mr_\d+_name$/.test(fieldKey) ? 'Recipe name' : 'Product name',
        fields: [fieldKey],
        defaults: { [fieldKey]: (el.textContent || '').trim() },
      };
      mcliCollected.push({ zoneKey, zone, el });
    });
  }

  // ── Step 2: inject overlays in order (schema zones first, then product names)
  [...collected, ...mcliCollected].forEach(({ zoneKey, zone, el }) => {
    if (!el) return;

    const btn = injectOverlay(el, zoneKey, zone);
    if (!btn) return;

    btn.addEventListener('click', e => {
      e.stopPropagation();
      e.preventDefault();
      handlePencilClick(zoneKey, zone);
    });
  });
}

/**
 * Some frontend components (notably the Menu gallery carousel) use pointerdown
 * handlers + preventDefault to implement dragging. That can prevent the
 * subsequent click event from firing on our pencil button.
 *
 * Fix: intercept pointer interactions on the pencil button in capture phase so
 * the underlying component never sees them.
 */
function initPencilPointerGuard() {
  const canvas = document.getElementById('ve-canvas');
  if (!canvas) return;

  const guard = (e) => {
    if (!e.target?.closest) return;
    if (e.target.closest('.ve-pencil-btn')) {
      e.stopPropagation();
      // Do NOT preventDefault here; we want a normal click to fire.
    }
  };

  canvas.addEventListener('pointerdown', guard, true);
  canvas.addEventListener('mousedown', guard, true);
  canvas.addEventListener('touchstart', guard, { capture: true, passive: true });
}

/**
 * Intercept ALL link clicks inside the canvas so the editor never
 * navigates away when clicking brand cards, footer links, etc.
 * Pencil button clicks are excluded — they handle their own stopPropagation.
 */
function initCanvasLinkGuard() {
  const canvas = document.getElementById('ve-canvas');
  if (!canvas) return;

  const toPath = (url) => {
    try {
      const u = new URL(url, window.location.origin);
      const path = (u.pathname || '/').replace(/\/+$/, '');
      return path || '/';
    } catch (e) {
      return '';
    }
  };
  const pageByUrl = new Map(
    Object.entries(VISUAL_PAGE_URLS || {}).map(([slug, url]) => [toPath(url), slug])
  );
  const menuBase            = toPath(VISUAL_PAGE_URLS?.menu || '/brands/menu');
  const menuProductsBase    = '/brands/menu/products';
  const menuRecipesBase     = '/brands/menu/recipelist';
  const menuRecipesCatBase  = '/brands/menu/recipes';
  const menuCategoryBase    = '/brands/menu/category';
  const nordicBase          = toPath(VISUAL_PAGE_URLS?.nordic || '/brands/nordic');
  const nordicProductsBase  = '/brands/nordic/products';
  const nordicRecipesBase   = '/brands/nordic/recipes';
  const homeBase            = toPath(VISUAL_PAGE_URLS?.ultrafood || '/');

  canvas.addEventListener('click', e => {
    const menuCard = e.target.closest('[data-category-link]');
    if (menuCard && !e.target.closest('.ve-pencil-btn')) {
      // Menu category cards are JS-driven (not anchor tags). Keep navigation
      // inside Visual Editor by forcing the mapped visual page.
      e.preventDefault();
      e.stopPropagation();
      window.location.href = `${VISUAL_EDITOR_BASE}?page=menu_recipelist`;
      return;
    }

    const anchor = e.target.closest('a[href]');
    if (anchor && !e.target.closest('.ve-pencil-btn')) {
      const rawHref = (anchor.getAttribute('href') || '').trim();

      // Allow in-page anchors (e.g. #section) to keep normal scrolling behavior.
      if (rawHref.startsWith('#')) return;

      e.preventDefault();

      const clicked = toPath(anchor.href);
      let slug = pageByUrl.get(clicked);

      // Keep brand sub-pages inside visual editor and map to dropdown pages.
      // 1) /brands/menu/products/category/{category} → per-category grid page
      if (!slug && clicked.startsWith(menuProductsBase + '/category/')) {
        const cat = clicked.slice((menuProductsBase + '/category/').length).replace(/^\/+|\/+$/g, '');
        const safeCat = cat
          .toLowerCase()
          .replace(/[^a-z0-9-]+/g, '-')
          .replace(/-+/g, '-')
          .replace(/^-+|-+$/g, '');
        slug = safeCat ? `menu_cat_${safeCat.replace(/-/g, '_')}` : 'menu_cat_jelly_mixes';
      }
      // 2) /brands/menu/products/{category}/{item} → menu_item_details (per-item page)
      if (!slug && clicked.startsWith(menuProductsBase + '/')) {
        const afterBase = clicked.slice(menuProductsBase.length).replace(/^\/+/, '');
        const segments = afterBase.split('/').filter(Boolean);
        if (segments.length >= 2) {
          slug = 'menu_item_details';
          // Preserve the clicked item so the editor can render the right details.
          const catSlug  = segments[0];
          const itemSlug = segments[1];
          window.location.href =
            `${VISUAL_EDITOR_BASE}?page=${encodeURIComponent(slug)}&category=${encodeURIComponent(catSlug)}&item=${encodeURIComponent(itemSlug)}`;
          return;
        }
      }
      if (!slug && clicked === menuProductsBase) {
        slug = 'menu_recipelist';
      }
      if (!slug && clicked === menuRecipesBase) {
        slug = 'menu_recipelist';
      }
      // 2b) /brands/menu/recipes/{category} → recipe category management page
      if (!slug && clicked.startsWith(menuRecipesCatBase + '/')) {
        const after = clicked.slice((menuRecipesCatBase + '/').length);
        const segs = after.split('/').filter(Boolean);
        if (segs.length >= 2) {
          slug = 'menu_recipe_detail';
          const catSlug = segs[0];
          const recipeSlug = segs[1];
          window.location.href =
            `${VISUAL_EDITOR_BASE}?page=${encodeURIComponent(slug)}&cat=${encodeURIComponent(catSlug)}&recipe=${encodeURIComponent(recipeSlug)}`;
          return;
        }
        if (segs.length === 1) {
          slug = 'menu_recipe_category';
          const catSlug = segs[0];
          window.location.href =
            `${VISUAL_EDITOR_BASE}?page=${encodeURIComponent(slug)}&cat=${encodeURIComponent(catSlug)}`;
          return;
        }
      }
      if (!slug && clicked.startsWith(menuCategoryBase + '/')) {
        slug = 'menu_recipelist';
      }
      if (!slug && menuBase && clicked.startsWith(menuBase + '/')) {
        slug = 'menu';
      }

      if (!slug && clicked.startsWith(nordicProductsBase + '/')) {
        const afterBase = clicked.slice(nordicProductsBase.length).replace(/^\/+/, '');
        const segments = afterBase.split('/').filter(Boolean);
        if (segments.length >= 1) {
          const productSlug = segments[0];
          window.location.href = `${VISUAL_EDITOR_BASE}?page=nordic_product_details&product=${encodeURIComponent(productSlug)}`;
          return;
        }
        window.location.href = `${VISUAL_EDITOR_BASE}?page=nordic_product_details`;
        return;
      }
      if (!slug && clicked === nordicProductsBase) {
        slug = 'nordic_products';
      }
      if (!slug && clicked.startsWith(nordicRecipesBase + '/')) {
        const after = clicked.slice((nordicRecipesBase + '/').length);
        const segs = after.split('/').filter(Boolean);
        if (segs.length >= 1) {
          slug = 'nordic_recipe_details';
          const recipeSlug = segs[0];
          window.location.href =
            `${VISUAL_EDITOR_BASE}?page=${encodeURIComponent(slug)}&recipe=${encodeURIComponent(recipeSlug)}`;
          return;
        }
      }
      if (!slug && clicked === nordicRecipesBase) {
        slug = 'nordic_recipes';
      }
      if (!slug && nordicBase && clicked.startsWith(nordicBase + '/')) {
        slug = 'nordic';
      }
      // Root-only fallback for ultrafood
      if (!slug && clicked === homeBase) {
        slug = 'ultrafood';
      }

      if (slug) {
        window.location.href = `${VISUAL_EDITOR_BASE}?page=${encodeURIComponent(slug)}`;
        return;
      }

      toast('This link is disabled in Visual Editor. Use the Page dropdown to navigate.', 'error');
    }
  }, true); // capture phase — fires before the anchor's own handler

  // Keep forms from navigating away from Visual Editor (e.g. contact forms).
  canvas.addEventListener('submit', e => {
    e.preventDefault();
    toast('Form submit is disabled in Visual Editor.', 'error');
  }, true);
}

/* ═══════════════════════════════════════════════════════════════════════════
   NORDIC RECIPES — VISUAL EDITOR CRUD (flat list)
   ═══════════════════════════════════════════════════════════════════════════ */

function initNordicRecipesVe() {
  const canvas = document.getElementById('ve-canvas');
  if (!canvas) return;

  if (!['nordic_recipes', 'nordic_recipe_details'].includes(String(ACTIVE_PAGE || ''))) return;

  const state =
    (typeof VE_NORDIC_RECIPE_STATE !== 'undefined' && VE_NORDIC_RECIPE_STATE)
      ? VE_NORDIC_RECIPE_STATE
      : null;
  const rawNordicProductReferences =
    (typeof VE_NORDIC_PRODUCT_REFERENCES !== 'undefined' && Array.isArray(VE_NORDIC_PRODUCT_REFERENCES))
      ? VE_NORDIC_PRODUCT_REFERENCES
      : [];
  const productReferences = Array.isArray(rawNordicProductReferences)
    ? rawNordicProductReferences
        .map((row) => ({
          slug: String(row?.slug || '').trim(),
          title: String(row?.title || '').trim(),
        }))
        .filter((row) => row.slug && row.title)
    : [];
  const escapeRegExp = (value) => String(value || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const buildMentionToken = (product) => {
    const safeLabel = String(product?.title || '').trim().replace(/[\[\]\(\)]/g, '');
    const slug = String(product?.slug || '').trim();
    if (!safeLabel || !slug) return '';
    return `[@${safeLabel}](nordic-product:${slug})`;
  };

  const mentionSuggest = {
    open: false,
    target: '',
    start: -1,
    end: -1,
    query: '',
    rows: [],
    activeIndex: 0,
  };

  const getMentionTargetInput = () => {
    if (mentionSuggest.target === 'procedures') return document.getElementById('ve-nordic-recipe-procedures');
    return document.getElementById('ve-nordic-recipe-ingredients');
  };
  const getMentionTargetBox = () => {
    if (mentionSuggest.target === 'procedures') return document.getElementById('ve-nordic-recipe-procedure-suggest');
    return document.getElementById('ve-nordic-recipe-ingredient-suggest');
  };
  const closeMentionSuggestions = () => {
    const ingredientBox = document.getElementById('ve-nordic-recipe-ingredient-suggest');
    const procedureBox = document.getElementById('ve-nordic-recipe-procedure-suggest');
    if (ingredientBox) {
      ingredientBox.hidden = true;
      ingredientBox.innerHTML = '';
    }
    if (procedureBox) {
      procedureBox.hidden = true;
      procedureBox.innerHTML = '';
    }
    mentionSuggest.open = false;
    mentionSuggest.target = '';
    mentionSuggest.start = -1;
    mentionSuggest.end = -1;
    mentionSuggest.query = '';
    mentionSuggest.rows = [];
    mentionSuggest.activeIndex = 0;
  };
  const getMentionContextAtCursor = (text, cursor) => {
    const safeText = String(text || '');
    const safeCursor = Math.max(0, Math.min(Number(cursor || 0), safeText.length));
    const lineStart = safeText.lastIndexOf('\n', Math.max(0, safeCursor - 1)) + 1;
    const segment = safeText.slice(lineStart, safeCursor);
    const atPosInSegment = segment.lastIndexOf('@');
    if (atPosInSegment < 0) return null;
    const beforeAt = segment.slice(0, atPosInSegment);
    if (beforeAt && !/[\s(,;:]$/.test(beforeAt)) return null;
    const query = segment.slice(atPosInSegment + 1);
    if (/[\[\]\(\)]/.test(query)) return null;
    return {
      start: lineStart + atPosInSegment,
      end: safeCursor,
      query,
    };
  };
  const findMentionSuggestions = (rawQuery) => {
    const query = String(rawQuery || '').trim().toLowerCase();
    return productReferences
      .map((row) => {
        const label = row.title.toLowerCase();
        const idx = query ? label.indexOf(query) : 0;
        if (query && idx < 0) return null;
        return { row, idx };
      })
      .filter(Boolean)
      .sort((a, b) => {
        if (a.idx !== b.idx) return a.idx - b.idx;
        if (a.row.title.length !== b.row.title.length) return a.row.title.length - b.row.title.length;
        return a.row.title.localeCompare(b.row.title);
      })
      .slice(0, 7)
      .map((entry) => entry.row);
  };
  const renderMentionSuggestions = () => {
    const box = getMentionTargetBox();
    if (!box || !mentionSuggest.open || !mentionSuggest.rows.length) {
      closeMentionSuggestions();
      return;
    }
    const activeIndex = Math.max(0, Math.min(mentionSuggest.activeIndex, mentionSuggest.rows.length - 1));
    mentionSuggest.activeIndex = activeIndex;
    box.innerHTML = mentionSuggest.rows.map((row, idx) => {
      const isActive = idx === activeIndex;
      return `
        <button
          type="button"
          class="ve-mention-item${isActive ? ' is-active' : ''}"
          data-ve-mention-slug="${esc(row.slug)}"
          data-ve-mention-idx="${idx}"
        >
          <span class="ve-mention-item-main">${esc(row.title)}</span>
        </button>
      `;
    }).join('');
    box.hidden = false;
  };
  const openMentionSuggestions = (ctx, target) => {
    mentionSuggest.open = true;
    mentionSuggest.target = target === 'procedures' ? 'procedures' : 'ingredients';
    mentionSuggest.start = Number(ctx?.start ?? -1);
    mentionSuggest.end = Number(ctx?.end ?? -1);
    mentionSuggest.query = String(ctx?.query || '');
    mentionSuggest.rows = findMentionSuggestions(mentionSuggest.query);
    mentionSuggest.activeIndex = 0;
    renderMentionSuggestions();
  };
  const applyMentionSuggestion = (row) => {
    const targetEl = getMentionTargetInput();
    if (!targetEl || !row || mentionSuggest.start < 0 || mentionSuggest.end < mentionSuggest.start) return;
    const token = buildMentionToken(row);
    if (!token) return;
    const value = String(targetEl.value || '');
    const before = value.slice(0, mentionSuggest.start);
    const after = value.slice(mentionSuggest.end);
    targetEl.value = `${before}${token}${after}`;
    const nextCursor = before.length + token.length;
    targetEl.setSelectionRange(nextCursor, nextCursor);
    targetEl.focus();
    closeMentionSuggestions();
  };
  const normalizeTextMentions = (rawText) => {
    let normalized = String(rawText || '');
    if (!normalized.trim()) return normalized;
    normalized = normalized.replace(/\[@([^\]]+)\]\(menu-product:\d+\)/gi, (_full, label) => `@${String(label || '').trim()}`);
    const alreadyTokenRegex = /\[@([^\]]+)\]\(nordic-product:([a-z0-9\-]+)\)/gi;
    const protectedTokens = [];
    normalized = normalized.replace(alreadyTokenRegex, (m) => {
      const marker = `__VE_NORDIC_TOKEN_${protectedTokens.length}__`;
      protectedTokens.push(m);
      return marker;
    });
    const byLongestTitle = [...productReferences].sort((a, b) => b.title.length - a.title.length);
    byLongestTitle.forEach((row) => {
      const title = String(row.title || '').trim();
      if (!title) return;
      const token = buildMentionToken(row);
      if (!token) return;
      const mentionRegex = new RegExp(`(^|\\s)@${escapeRegExp(title)}(?=\\s|$|[.,;:!?])`, 'gi');
      normalized = normalized.replace(mentionRegex, (full, lead) => `${lead}${token}`);
    });
    protectedTokens.forEach((token, idx) => {
      normalized = normalized.replace(`__VE_NORDIC_TOKEN_${idx}__`, token);
    });
    return normalized;
  };

  const openNordicModal = (mode, row = null) => {
    const modal = document.getElementById('ve-modal-nordic-recipe');
    if (!modal) return;

    document.getElementById('ve-nordic-recipe-title').textContent =
      mode === 'edit' ? 'Edit Nordic recipe' : 'Add Nordic recipe';

    const idEl   = document.getElementById('ve-nordic-recipe-id');
    const nameEl = document.getElementById('ve-nordic-recipe-name');
    const slugEl = document.getElementById('ve-nordic-recipe-slug');
    const imgEl  = document.getElementById('ve-nordic-recipe-image');
    const descEl = document.getElementById('ve-nordic-recipe-description');
    const servEl = document.getElementById('ve-nordic-recipe-servings');
    const calEl  = document.getElementById('ve-nordic-recipe-calories');
    const ingEl  = document.getElementById('ve-nordic-recipe-ingredients');
    const procEl = document.getElementById('ve-nordic-recipe-procedures');

    if (idEl)   idEl.value = mode === 'edit' && row ? String(row.id ?? '') : '';
    if (nameEl) nameEl.value = mode === 'edit' && row ? (row.name ?? '') : '';
    if (slugEl) slugEl.value = mode === 'edit' && row ? (row.slug ?? '') : '';
    if (descEl) descEl.value = mode === 'edit' && row ? (row.description ?? '') : '';
    if (servEl) servEl.value = mode === 'edit' && row ? (row.servings ?? '') : '';
    if (calEl)  calEl.value  = mode === 'edit' && row ? (row.calories ?? '') : '';
    if (ingEl)  ingEl.value  = mode === 'edit' && row ? (Array.isArray(row.ingredients) ? row.ingredients.join('\n') : '') : '';
    if (procEl) procEl.value = mode === 'edit' && row ? (Array.isArray(row.procedures) ? row.procedures.join('\n') : '') : '';
    if (imgEl)  imgEl.value = '';
    closeMentionSuggestions();

    window.openModal('ve-modal-nordic-recipe');
  };

  const findRecipeById = (id) => {
    const rows = state?.recipes || [];
    return rows.find(r => String(r.id) === String(id)) || null;
  };

  const save = async () => {
    const id = (document.getElementById('ve-nordic-recipe-id')?.value || '').trim();
    const name = (document.getElementById('ve-nordic-recipe-name')?.value || '').trim();
    const slug = (document.getElementById('ve-nordic-recipe-slug')?.value || '').trim();
    const image = document.getElementById('ve-nordic-recipe-image')?.files?.[0] || null;
    const description = document.getElementById('ve-nordic-recipe-description')?.value || '';
    const servings = document.getElementById('ve-nordic-recipe-servings')?.value || '';
    const calories = document.getElementById('ve-nordic-recipe-calories')?.value || '';
    const ingredientsRaw = document.getElementById('ve-nordic-recipe-ingredients')?.value || '';
    const ingredients = normalizeTextMentions(ingredientsRaw);
    const proceduresRaw = document.getElementById('ve-nordic-recipe-procedures')?.value || '';
    const procedures = normalizeTextMentions(proceduresRaw);

    if (!name) {
      toast('Name is required.', 'error');
      return;
    }
    const ingEl = document.getElementById('ve-nordic-recipe-ingredients');
    if (ingEl && ingEl.value !== ingredients) ingEl.value = ingredients;
    const procEl = document.getElementById('ve-nordic-recipe-procedures');
    if (procEl && procEl.value !== procedures) procEl.value = procedures;

    const fd = new FormData();
    fd.append('name', name);
    fd.append('slug', slug);
    fd.append('description', description);
    fd.append('servings', servings);
    fd.append('calories', calories);
    fd.append('ingredients', ingredients);
    fd.append('procedures', procedures);
    if (image) {
      try {
        await appendNormalizedImageToFormData(fd, 'image', image);
      } catch (e) {
        toast(e?.message || 'Could not prepare the selected recipe image.', 'error');
        return;
      }
    }

    let ok, data;
    if (id) {
      fd.append('_method', 'PUT');
      ({ ok, data } = await apiFetch(`${ROUTES.nordicRecipesUpdate}/${id}`, 'POST', fd));
    } else {
      ({ ok, data } = await apiFetch(ROUTES.nordicRecipesStore, 'POST', fd));
    }

    if (!ok || !data?.ok) {
      console.error('Nordic recipe save failed', data);
      toast(apiErrorMessage(data, 'Could not save recipe.'), 'error');
      return;
    }

    toast('Recipe saved.');
    closeModal('ve-modal-nordic-recipe');
    await refreshCanvas();
  };

  const destroy = async (id) => {
    if (!id) return;
    const { ok, data } = await apiFetch(`${ROUTES.nordicRecipesDestroy}/${id}`, 'DELETE');
    if (!ok || !data?.ok) {
      console.error('Nordic recipe delete failed', data);
      toast('Could not delete recipe. Check the console.', 'error');
      return;
    }
    toast('Recipe deleted.');
    await refreshCanvas();
  };

  const saveBtn = document.getElementById('ve-nordic-recipe-save');
  if (saveBtn && !saveBtn.dataset.bound) {
    saveBtn.dataset.bound = '1';
    saveBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      save();
    });
  }

  const bindTextareaMentionInput = (inputEl, target) => {
    if (!inputEl || inputEl.dataset.veMentionBound) return;
    inputEl.dataset.veMentionBound = '1';
    inputEl.addEventListener('input', () => {
      const ctx = getMentionContextAtCursor(inputEl.value, inputEl.selectionStart ?? 0);
      if (!ctx) {
        closeMentionSuggestions();
        return;
      }
      openMentionSuggestions(ctx, target);
    });
    inputEl.addEventListener('click', () => {
      const ctx = getMentionContextAtCursor(inputEl.value, inputEl.selectionStart ?? 0);
      if (!ctx) {
        closeMentionSuggestions();
        return;
      }
      openMentionSuggestions(ctx, target);
    });
    inputEl.addEventListener('keydown', (e) => {
      if (!mentionSuggest.open || !mentionSuggest.rows.length) return;
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        mentionSuggest.activeIndex = (mentionSuggest.activeIndex + 1) % mentionSuggest.rows.length;
        renderMentionSuggestions();
        return;
      }
      if (e.key === 'ArrowUp') {
        e.preventDefault();
        mentionSuggest.activeIndex = (mentionSuggest.activeIndex - 1 + mentionSuggest.rows.length) % mentionSuggest.rows.length;
        renderMentionSuggestions();
        return;
      }
      if (e.key === 'Enter' || e.key === 'Tab') {
        e.preventDefault();
        const row = mentionSuggest.rows[mentionSuggest.activeIndex];
        applyMentionSuggestion(row);
        return;
      }
      if (e.key === 'Escape') {
        e.preventDefault();
        closeMentionSuggestions();
      }
    });
    inputEl.addEventListener('blur', () => {
      window.setTimeout(() => closeMentionSuggestions(), 140);
    });
  };
  bindTextareaMentionInput(document.getElementById('ve-nordic-recipe-ingredients'), 'ingredients');
  bindTextareaMentionInput(document.getElementById('ve-nordic-recipe-procedures'), 'procedures');

  const bindMentionSuggestionBox = (boxEl) => {
    if (!boxEl || boxEl.dataset.veBound) return;
    boxEl.dataset.veBound = '1';
    boxEl.addEventListener('mousedown', (e) => {
      e.preventDefault();
      const button = e.target.closest('[data-ve-mention-slug]');
      if (!button) return;
      const mentionSlug = String(button.getAttribute('data-ve-mention-slug') || '').trim();
      const row = productReferences.find((item) => item.slug === mentionSlug);
      if (!row) return;
      applyMentionSuggestion(row);
    });
  };
  bindMentionSuggestionBox(document.getElementById('ve-nordic-recipe-ingredient-suggest'));
  bindMentionSuggestionBox(document.getElementById('ve-nordic-recipe-procedure-suggest'));

  canvas.addEventListener('click', (e) => {
    const add = e.target.closest('.ve-nordic-add-recipe');
    if (add) { e.preventDefault(); e.stopPropagation(); openNordicModal('create'); return; }

    const del = e.target.closest('.ve-nordic-del-recipe');
    if (del) {
      e.preventDefault(); e.stopPropagation();
      const id = del.getAttribute('data-id') || del.closest('[data-recipe-id]')?.getAttribute('data-recipe-id');
      destroy(id);
      return;
    }

    const edit = e.target.closest('.ve-nordic-edit-recipe');
    if (edit) {
      e.preventDefault(); e.stopPropagation();
      const id = edit.closest('[data-recipe-id]')?.getAttribute('data-recipe-id');
      const row = findRecipeById(id);
      openNordicModal('edit', row);
      return;
    }
  }, true);
}

/* ═══════════════════════════════════════════════════════════════════════════
   MENU RECIPES — VISUAL EDITOR CRUD (categories + recipes)
   ═══════════════════════════════════════════════════════════════════════════ */

function initMenuRecipesVe() {
  const canvas = document.getElementById('ve-canvas');
  if (!canvas) return;

  const state = getVeRecipeState();
  const rawMenuProductReferences =
    (typeof VE_MENU_PRODUCT_REFERENCES !== 'undefined' && Array.isArray(VE_MENU_PRODUCT_REFERENCES))
      ? VE_MENU_PRODUCT_REFERENCES
      : [];
  const productReferences = Array.isArray(rawMenuProductReferences)
    ? rawMenuProductReferences
        .map((row) => ({
          id: Number(row?.id || 0),
          title: String(row?.title || '').trim(),
          categoryLabel: String(row?.category_label || '').trim(),
        }))
        .filter((row) => row.id > 0 && row.title)
    : [];
  const escapeRegExp = (value) => String(value || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

  const buildIngredientMentionToken = (product) => {
    const safeLabel = String(product?.title || '').trim().replace(/[\[\]\(\)]/g, '');
    const id = Number(product?.id || 0);
    if (!safeLabel || !id) return '';
    return `[@${safeLabel}](menu-product:${id})`;
  };

  const mentionSuggest = {
    open: false,
    target: '',
    start: -1,
    end: -1,
    query: '',
    rows: [],
    activeIndex: 0,
  };

  const getMentionTargetInput = () => {
    if (mentionSuggest.target === 'procedures') return document.getElementById('ve-recipe-procedures');
    return document.getElementById('ve-recipe-ingredients');
  };

  const getMentionTargetBox = () => {
    if (mentionSuggest.target === 'procedures') return document.getElementById('ve-recipe-procedure-suggest');
    return document.getElementById('ve-recipe-ingredient-suggest');
  };

  const closeMentionSuggestions = () => {
    const ingredientBox = document.getElementById('ve-recipe-ingredient-suggest');
    const procedureBox = document.getElementById('ve-recipe-procedure-suggest');
    if (ingredientBox) {
      ingredientBox.hidden = true;
      ingredientBox.innerHTML = '';
    }
    if (procedureBox) {
      procedureBox.hidden = true;
      procedureBox.innerHTML = '';
    }
    mentionSuggest.open = false;
    mentionSuggest.target = '';
    mentionSuggest.start = -1;
    mentionSuggest.end = -1;
    mentionSuggest.query = '';
    mentionSuggest.rows = [];
    mentionSuggest.activeIndex = 0;
  };

  const getMentionContextAtCursor = (text, cursor) => {
    const safeText = String(text || '');
    const safeCursor = Math.max(0, Math.min(Number(cursor || 0), safeText.length));
    const lineStart = safeText.lastIndexOf('\n', Math.max(0, safeCursor - 1)) + 1;
    const segment = safeText.slice(lineStart, safeCursor);
    const atPosInSegment = segment.lastIndexOf('@');
    if (atPosInSegment < 0) return null;

    const beforeAt = segment.slice(0, atPosInSegment);
    if (beforeAt && !/[\s(,;:]$/.test(beforeAt)) {
      return null;
    }

    const query = segment.slice(atPosInSegment + 1);
    if (/[\[\]\(\)]/.test(query)) return null;

    const globalAt = lineStart + atPosInSegment;
    return {
      start: globalAt,
      end: safeCursor,
      query,
    };
  };

  const findMentionSuggestions = (rawQuery) => {
    const query = String(rawQuery || '').trim().toLowerCase();
    const ranked = productReferences
      .map((row) => {
        const label = row.title.toLowerCase();
        const idx = query ? label.indexOf(query) : 0;
        if (query && idx < 0) return null;
        return { row, idx };
      })
      .filter(Boolean)
      .sort((a, b) => {
        if (a.idx !== b.idx) return a.idx - b.idx;
        if (a.row.title.length !== b.row.title.length) return a.row.title.length - b.row.title.length;
        return a.row.title.localeCompare(b.row.title);
      })
      .slice(0, 7)
      .map((entry) => entry.row);

    return ranked;
  };

  const renderMentionSuggestions = () => {
    const box = getMentionTargetBox();
    if (!box || !mentionSuggest.open || !mentionSuggest.rows.length) {
      closeMentionSuggestions();
      return;
    }

    const activeIndex = Math.max(0, Math.min(mentionSuggest.activeIndex, mentionSuggest.rows.length - 1));
    mentionSuggest.activeIndex = activeIndex;

    box.innerHTML = mentionSuggest.rows.map((row, idx) => {
      const isActive = idx === activeIndex;
      const subtitle = row.categoryLabel ? `<span class="ve-mention-item-sub">${esc(row.categoryLabel)}</span>` : '';
      return `
        <button
          type="button"
          class="ve-mention-item${isActive ? ' is-active' : ''}"
          data-ve-mention-id="${row.id}"
          data-ve-mention-idx="${idx}"
        >
          <span class="ve-mention-item-main">${esc(row.title)}</span>
          ${subtitle}
        </button>
      `;
    }).join('');
    box.hidden = false;
  };

  const openMentionSuggestions = (ctx, target) => {
    mentionSuggest.open = true;
    mentionSuggest.target = target === 'procedures' ? 'procedures' : 'ingredients';
    mentionSuggest.start = Number(ctx?.start ?? -1);
    mentionSuggest.end = Number(ctx?.end ?? -1);
    mentionSuggest.query = String(ctx?.query || '');
    mentionSuggest.rows = findMentionSuggestions(mentionSuggest.query);
    mentionSuggest.activeIndex = 0;
    renderMentionSuggestions();
  };

  const applyMentionSuggestion = (row) => {
    const targetEl = getMentionTargetInput();
    if (!targetEl || !row || mentionSuggest.start < 0 || mentionSuggest.end < mentionSuggest.start) return;

    const token = buildIngredientMentionToken(row);
    if (!token) return;

    const value = String(targetEl.value || '');
    const before = value.slice(0, mentionSuggest.start);
    const after = value.slice(mentionSuggest.end);
    targetEl.value = `${before}${token}${after}`;
    const nextCursor = before.length + token.length;
    targetEl.setSelectionRange(nextCursor, nextCursor);
    targetEl.focus();
    closeMentionSuggestions();
  };

  const normalizeTextMentions = (rawText) => {
    let normalized = String(rawText || '');
    if (!normalized.trim()) return normalized;

    const alreadyTokenRegex = /\[@([^\]]+)\]\(menu-product:(\d+)\)/g;
    const protectedTokens = [];
    normalized = normalized.replace(alreadyTokenRegex, (m) => {
      const marker = `__VE_TOKEN_${protectedTokens.length}__`;
      protectedTokens.push(m);
      return marker;
    });

    const byLongestTitle = [...productReferences].sort((a, b) => b.title.length - a.title.length);
    byLongestTitle.forEach((row) => {
      const title = String(row.title || '').trim();
      if (!title) return;
      const token = buildIngredientMentionToken(row);
      if (!token) return;
      const mentionRegex = new RegExp(`(^|\\s)@${escapeRegExp(title)}(?=\\s|$|[.,;:!?])`, 'gi');
      normalized = normalized.replace(mentionRegex, (full, lead) => `${lead}${token}`);
    });

    protectedTokens.forEach((token, idx) => {
      normalized = normalized.replace(`__VE_TOKEN_${idx}__`, token);
    });

    return normalized;
  };

  const findRecipeById = (id) => {
    const rows = Array.isArray(state?.recipes) ? state.recipes : [];
    return rows.find((row) => String(row?.id || '') === String(id || '')) || null;
  };
  const upsertRecipeState = (recipe) => {
    if (!recipe || !state) return;
    if (!Array.isArray(state.recipes)) state.recipes = [];
    const nextRecipe = { ...recipe };
    const index = state.recipes.findIndex((row) => String(row?.id || '') === String(nextRecipe.id || ''));
    if (index >= 0) state.recipes[index] = { ...state.recipes[index], ...nextRecipe };
    else state.recipes.push(nextRecipe);
    if (String(state.activeRecipe?.id || '') === String(nextRecipe.id || '')) {
      state.activeRecipe = { ...state.activeRecipe, ...nextRecipe };
    }
  };
  const removeRecipeFromState = (recipeId) => {
    if (!state || !Array.isArray(state.recipes)) return;
    const matchId = String(recipeId || '');
    state.recipes = state.recipes.filter((row) => String(row?.id || '') !== matchId);
    if (String(state.activeRecipe?.id || '') === matchId) {
      state.activeRecipe = null;
    }
  };

  const saveCatModal = async () => {
    const id = (document.getElementById('ve-recipe-cat-id')?.value || '').trim();
    const name = (document.getElementById('ve-recipe-cat-name')?.value || '').trim();
    const slug = (document.getElementById('ve-recipe-cat-slug')?.value || '').trim();
    const icon = document.getElementById('ve-recipe-cat-icon')?.files?.[0] || null;
    if (!name) { toast('Enter a category name', 'error'); return; }

    const fd = new FormData();
    fd.append('name', name);
    if (slug) fd.append('slug', slug);
    if (icon) fd.append('icon', icon);

    const saveBtn = document.getElementById('ve-recipe-cat-save');
    const orig = saveBtn?.innerHTML;
    if (saveBtn) { saveBtn.disabled = true; saveBtn.innerHTML = `<span class="ve-spinner"></span> Saving…`; }

    let ok, data;
    if (id) {
      fd.append('_method', 'PUT');
      ({ ok, data } = await apiFetch(`${ROUTES.menuRecipeCategoriesUpdate}/${id}`, 'POST', fd));
    } else {
      ({ ok, data } = await apiFetch(ROUTES.menuRecipeCategoriesStore, 'POST', fd));
    }

    if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = orig; }
    if (!ok || !data?.ok) { toast(data?.message || 'Could not save category', 'error'); return; }

    toast('Category saved');
    closeModal('ve-modal-recipe-category');
    await refreshCanvas();
  };

  const deleteCategory = async () => {
    if (!state?.activeCategory?.id) { toast('No category selected', 'error'); return; }
    if (!window.confirm('Delete this category and all its recipes?')) return;
    const { ok, data } = await apiFetch(`${ROUTES.menuRecipeCategoriesDestroy}/${state.activeCategory.id}`, 'DELETE');
    if (!ok || !data?.ok) { toast(data?.message || 'Could not delete category', 'error'); return; }
    toast('Category deleted');
    await refreshCanvas();
  };

  const RECIPE_LEVEL_OPTIONS = ['Basic', 'Average', 'Pro'];

  const setRecipeDifficultyValue = (selectEl, value) => {
    if (!selectEl) return;
    const normalized = String(value || '').trim();
    if (!normalized) {
      selectEl.value = RECIPE_LEVEL_OPTIONS[0];
      return;
    }
    if (!RECIPE_LEVEL_OPTIONS.includes(normalized)) {
      // Keep legacy/custom saved values editable instead of silently replacing them.
      const legacyOption = document.createElement('option');
      legacyOption.value = normalized;
      legacyOption.textContent = normalized;
      legacyOption.dataset.legacyDifficulty = '1';
      selectEl.appendChild(legacyOption);
    }
    selectEl.value = normalized;
  };

  const openRecipeModal = (mode) => {
    const modal = document.getElementById('ve-modal-recipe');
    if (!modal) return;
    if (!state?.activeCategory?.id) { toast('No category selected', 'error'); return; }

    const isDetailPageEdit = String(ACTIVE_PAGE || '') === 'menu_recipe_detail' && mode === 'edit';
    const isCreateMode = mode === 'create';
    const title = document.getElementById('ve-recipe-title');
    const idEl = document.getElementById('ve-recipe-id');
    const catIdEl = document.getElementById('ve-recipe-category-id');
    const nameEl = document.getElementById('ve-recipe-name');
    const nameGroup = document.getElementById('ve-recipe-name-group');
    const slugEl = document.getElementById('ve-recipe-slug');
    const slugGroup = document.getElementById('ve-recipe-slug-group');
    const imgEl = document.getElementById('ve-recipe-image');
    const imgGroup = document.getElementById('ve-recipe-image-group');
    const descEl = document.getElementById('ve-recipe-description');
    const servEl = document.getElementById('ve-recipe-servings');
    const diffEl = document.getElementById('ve-recipe-difficulty');
    const calEl = document.getElementById('ve-recipe-calories');
    const calGroup = document.getElementById('ve-recipe-calories-group');
    const ingEl = document.getElementById('ve-recipe-ingredients');
    const procEl = document.getElementById('ve-recipe-procedures');
    closeMentionSuggestions();

    if (title) title.textContent = isDetailPageEdit ? 'Edit recipe details' : (mode === 'edit' ? 'Edit recipe' : 'Add recipe');
    if (catIdEl) catIdEl.value = String(state.activeCategory.id);
    if (imgEl) imgEl.value = '';
    // Keep recipe name editable in all edit modes (including detail page edit).
    if (nameGroup) nameGroup.hidden = false;
    if (slugGroup) slugGroup.hidden = isDetailPageEdit || isCreateMode;
    // Keep recipe image editable in all edit modes (including detail page edit).
    if (imgGroup) imgGroup.hidden = false;
    if (calGroup) calGroup.hidden = isCreateMode;
    if (nameEl) nameEl.required = !isDetailPageEdit;

    if (diffEl) {
      [...diffEl.querySelectorAll('option[data-legacy-difficulty="1"]')].forEach((opt) => opt.remove());
    }

    if (mode === 'edit') {
      if (!state?.activeRecipe?.id) { toast('No recipe selected', 'error'); return; }
      const r = state.activeRecipe;
      if (idEl) idEl.value = String(r.id);
      if (nameEl) nameEl.value = r.name || '';
      if (slugEl) slugEl.value = r.slug || '';
      if (descEl) descEl.value = r.description || '';
      if (servEl) servEl.value = r.servings || '';
      setRecipeDifficultyValue(diffEl, r.difficulty);
      if (calEl) calEl.value = r.calories || '';
      if (ingEl) ingEl.value = Array.isArray(r.ingredients) ? r.ingredients.join('\n') : '';
      if (procEl) procEl.value = Array.isArray(r.procedures) ? r.procedures.join('\n') : '';
    } else {
      if (idEl) idEl.value = '';
      if (nameEl) nameEl.value = '';
      if (slugEl) slugEl.value = '';
      if (descEl) descEl.value = '';
      if (servEl) servEl.value = '';
      setRecipeDifficultyValue(diffEl, RECIPE_LEVEL_OPTIONS[0]);
      if (calEl) calEl.value = '';
      if (ingEl) ingEl.value = '';
      if (procEl) procEl.value = '';
    }

    openModal('ve-modal-recipe');
  };

  const saveRecipeModal = async () => {
    const id = (document.getElementById('ve-recipe-id')?.value || '').trim();
    const categoryId = (document.getElementById('ve-recipe-category-id')?.value || '').trim();
    const name = (document.getElementById('ve-recipe-name')?.value || '').trim();
    const slug = (document.getElementById('ve-recipe-slug')?.value || '').trim();
    const image = document.getElementById('ve-recipe-image')?.files?.[0] || null;
    const showNameFields = !(document.getElementById('ve-recipe-name-group')?.hidden);
    const showSlugFields = !(document.getElementById('ve-recipe-slug-group')?.hidden);
    const showImageFields = !(document.getElementById('ve-recipe-image-group')?.hidden);
    const showCaloriesField = !(document.getElementById('ve-recipe-calories-group')?.hidden);
    const description = document.getElementById('ve-recipe-description')?.value || '';
    const servings = document.getElementById('ve-recipe-servings')?.value || '';
    const difficulty = document.getElementById('ve-recipe-difficulty')?.value || '';
    const calories = document.getElementById('ve-recipe-calories')?.value || '';
    const ingredientsRaw = document.getElementById('ve-recipe-ingredients')?.value || '';
    const ingredients = normalizeTextMentions(ingredientsRaw);
    const proceduresRaw = document.getElementById('ve-recipe-procedures')?.value || '';
    const procedures = normalizeTextMentions(proceduresRaw);

    if (!id && !name) { toast('Enter a recipe name', 'error'); return; }
    if (!id && !categoryId) { toast('Missing category', 'error'); return; }
    const ingEl = document.getElementById('ve-recipe-ingredients');
    if (ingEl && ingEl.value !== ingredients) {
      ingEl.value = ingredients;
    }
    const procEl = document.getElementById('ve-recipe-procedures');
    if (procEl && procEl.value !== procedures) {
      procEl.value = procedures;
    }

    const fd = new FormData();
    if (!id || showNameFields) fd.append('category_id', categoryId);
    if (!id || showNameFields) fd.append('name', name);
    if (showSlugFields && slug) fd.append('slug', slug);
    if (showImageFields && image) {
      try {
        await appendNormalizedImageToFormData(fd, 'image', image);
      } catch (e) {
        toast(e?.message || 'Could not prepare the selected recipe image.', 'error');
        return;
      }
    }
    fd.append('description', description);
    fd.append('servings', servings);
    fd.append('difficulty', difficulty);
    if (showCaloriesField) fd.append('calories', calories);
    fd.append('ingredients', ingredients);
    fd.append('procedures', procedures);

    const saveBtn = document.getElementById('ve-recipe-save');
    const orig = saveBtn?.innerHTML;
    if (saveBtn) { saveBtn.disabled = true; saveBtn.innerHTML = `<span class="ve-spinner"></span> Saving…`; }

    let ok, data;
    if (id) {
      fd.append('_method', 'PUT');
      ({ ok, data } = await apiFetch(`${ROUTES.menuRecipesUpdate}/${id}`, 'POST', fd));
    } else {
      ({ ok, data } = await apiFetch(ROUTES.menuRecipesStore, 'POST', fd));
    }

    if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = orig; }
    if (!ok || !data?.ok) { toast(data?.message || 'Could not save recipe', 'error'); return; }

    upsertRecipeState(data?.recipe);
    toast('Recipe saved');
    closeModal('ve-modal-recipe');
    await refreshCanvas();
  };

  const deleteRecipe = async () => {
    if (!state?.activeRecipe?.id) { toast('No recipe selected', 'error'); return; }
    if (!window.confirm('Delete this recipe?')) return;
    const recipeId = state.activeRecipe.id;
    const { ok, data } = await apiFetch(`${ROUTES.menuRecipesDestroy}/${recipeId}`, 'DELETE');
    if (!ok || !data?.ok) { toast(data?.message || 'Could not delete recipe', 'error'); return; }
    removeRecipeFromState(recipeId);
    toast('Recipe deleted');
    await refreshCanvas();
  };

  const catSaveBtn = document.getElementById('ve-recipe-cat-save');
  if (catSaveBtn && !catSaveBtn.dataset.veBound) {
    catSaveBtn.dataset.veBound = '1';
    catSaveBtn.addEventListener('click', saveCatModal);
  }
  const recipeSaveBtn = document.getElementById('ve-recipe-save');
  if (recipeSaveBtn && !recipeSaveBtn.dataset.veBound) {
    recipeSaveBtn.dataset.veBound = '1';
    recipeSaveBtn.addEventListener('click', saveRecipeModal);
  }
  const bindTextareaMentionInput = (inputEl, target) => {
    if (!inputEl || inputEl.dataset.veMentionBound) return;
    inputEl.dataset.veMentionBound = '1';
    inputEl.addEventListener('input', () => {
      const ctx = getMentionContextAtCursor(inputEl.value, inputEl.selectionStart ?? 0);
      if (!ctx) {
        closeMentionSuggestions();
        return;
      }
      openMentionSuggestions(ctx, target);
    });
    inputEl.addEventListener('click', () => {
      const ctx = getMentionContextAtCursor(inputEl.value, inputEl.selectionStart ?? 0);
      if (!ctx) {
        closeMentionSuggestions();
        return;
      }
      openMentionSuggestions(ctx, target);
    });
    inputEl.addEventListener('keydown', (e) => {
      if (!mentionSuggest.open || !mentionSuggest.rows.length) return;

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        mentionSuggest.activeIndex = (mentionSuggest.activeIndex + 1) % mentionSuggest.rows.length;
        renderMentionSuggestions();
        return;
      }
      if (e.key === 'ArrowUp') {
        e.preventDefault();
        mentionSuggest.activeIndex = (mentionSuggest.activeIndex - 1 + mentionSuggest.rows.length) % mentionSuggest.rows.length;
        renderMentionSuggestions();
        return;
      }
      if (e.key === 'Enter' || e.key === 'Tab') {
        e.preventDefault();
        const row = mentionSuggest.rows[mentionSuggest.activeIndex];
        applyMentionSuggestion(row);
        return;
      }
      if (e.key === 'Escape') {
        e.preventDefault();
        closeMentionSuggestions();
      }
    });
    inputEl.addEventListener('blur', () => {
      window.setTimeout(() => closeMentionSuggestions(), 140);
    });
  };

  bindTextareaMentionInput(document.getElementById('ve-recipe-ingredients'), 'ingredients');
  bindTextareaMentionInput(document.getElementById('ve-recipe-procedures'), 'procedures');

  const bindMentionSuggestionBox = (boxEl) => {
    if (!boxEl || boxEl.dataset.veBound) return;
    boxEl.dataset.veBound = '1';
    boxEl.addEventListener('mousedown', (e) => {
      e.preventDefault();
      const button = e.target.closest('[data-ve-mention-id]');
      if (!button) return;
      const mentionId = Number(button.getAttribute('data-ve-mention-id') || 0);
      const row = productReferences.find((item) => item.id === mentionId);
      if (!row) return;
      applyMentionSuggestion(row);
    });
  };

  bindMentionSuggestionBox(document.getElementById('ve-recipe-ingredient-suggest'));
  bindMentionSuggestionBox(document.getElementById('ve-recipe-procedure-suggest'));

  canvas.addEventListener('click', (e) => {
    if (e.target.closest('.ve-recipe-add-category')) { e.preventDefault(); e.stopPropagation(); openMenuRecipeCategoryModal('create'); return; }
    if (e.target.closest('.ve-recipe-edit-category')) {
      e.preventDefault();
      e.stopPropagation();
      const activeCategoryId = state?.activeCategory?.id ?? null;
      openMenuRecipeCategoryModal('edit', activeCategoryId);
      return;
    }
    if (e.target.closest('.ve-recipe-del-category')) { e.preventDefault(); e.stopPropagation(); deleteCategory(); return; }
    if (e.target.closest('.ve-recipe-add-recipe')) { e.preventDefault(); e.stopPropagation(); openRecipeModal('create'); return; }
    if (e.target.closest('.ve-recipe-edit-recipe')) {
      e.preventDefault();
      e.stopPropagation();
      const id = e.target.closest('.ve-recipe-edit-recipe')?.getAttribute('data-id')
        || e.target.closest('[data-recipe-id]')?.getAttribute('data-recipe-id');
      const row = findRecipeById(id);
      if (row) state.activeRecipe = row;
      openRecipeModal('edit');
      return;
    }
    if (e.target.closest('.ve-recipe-del-recipe')) {
      e.preventDefault();
      e.stopPropagation();
      const id = e.target.closest('.ve-recipe-del-recipe')?.getAttribute('data-id')
        || e.target.closest('[data-recipe-id]')?.getAttribute('data-recipe-id');
      const row = findRecipeById(id);
      if (row) state.activeRecipe = row;
      deleteRecipe();
      return;
    }
  }, true);
}

/**
 * Menu page can inherit conflicting scroll contexts from frontend styles.
 * Force a single scroll container (document) when a Menu visual page is active.
 */
function normalizeMenuScrollContext() {
  // Disabled: Menu scroll ownership is now handled by editor CSS
  // (single scroll container on #ve-canvas for menu pages).
  return;
}


/* ═══════════════════════════════════════════════════════════════════════════
   PENCIL CLICK DISPATCHER
   ═══════════════════════════════════════════════════════════════════════════ */

function handlePencilClick(zoneKey, zone) {
  if (zone.type === 'text')    return openTextModal(zoneKey, zone);
  if (zone.type === 'image')   return openImageModal(zoneKey, zone);
  if (zone.type === 'style')   return openStyleModal(zoneKey, zone);
  if (zone.type === 'contact') return openContactModal(zoneKey, zone);
}


/* ═══════════════════════════════════════════════════════════════════════════
   TEXT MODAL
   ═══════════════════════════════════════════════════════════════════════════ */

let _textZoneKey      = null;
let _textZone         = null;
let _textZoneFields   = [];
let _textOriginalVals = {};   // fieldKey → text value when modal opened
let _textOriginalFont = {};   // fieldKey → { family, size, color } when modal opened
let _textSaved        = false;
let _textPrevSave     = null;
let _pendingRecipeCategoryDeletes = new Set();

const FIELD_FONT_LIST = [
  '', 'Anek Latin', 'Spline Sans', 'DM Sans', 'Inter', 'Roboto', 'Lato', 'Poppins', 'Montserrat',
];
const FIELD_SIZE_LIST = [
  '', '12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px', '36px', '42px', '48px', '56px', '64px',
];

function resolveTextZoneFields(zoneKey, zone) {
  const configured = Array.isArray(zone?.fields) ? zone.fields.filter(Boolean) : [];
  const supportsDynamicFields =
    (ACTIVE_PAGE === 'menu_item_details' && zoneKey === 'menu_item_details_main')
    || (ACTIVE_PAGE === 'menu_recipe_category' && zoneKey === 'menu_recipe_category_title')
    || (ACTIVE_PAGE === 'nordic_products' && zoneKey === 'nordic_products_selected_product');

  if (!supportsDynamicFields) {
    return configured;
  }

  if (ACTIVE_PAGE === 'nordic_products' && zoneKey === 'nordic_products_selected_product') {
    const product = resolveNordicSelectedProductConfig(zone);
    if (!product?.title_key) return configured;
    zone._nordicSelectedProduct = product;
    zone.defaults = { ...(zone.defaults || {}), [product.title_key]: product.title_default || '' };
    zone.field_labels = { ...(zone.field_labels || {}), [product.title_key]: product.title_label || 'Product title' };
    return [product.title_key];
  }

  const canvas = document.getElementById('ve-canvas');
  const root = canvas?.querySelector(zone.selector);
  if (!root) return configured;

  const dynamic = [];
  const rootFieldKey = root.getAttribute?.('data-ve-field');
  if (rootFieldKey) {
    if (/^menu_item_\d+_(description|lazada_url|shopee_url|shopee_mall_url|tiktok_url)$/.test(rootFieldKey)) {
      dynamic.push(rootFieldKey);
    }
    if (zoneKey === 'menu_recipe_category_title' && /^menu_nav_recipe_category_\d+_label$/.test(rootFieldKey)) {
      dynamic.push(rootFieldKey);
    }
  }
  root.querySelectorAll('[data-ve-field]').forEach((el) => {
    const fieldKey = el.getAttribute('data-ve-field');
    if (!fieldKey) return;
    if (/^menu_item_\d+_(description|lazada_url|shopee_url|shopee_mall_url|tiktok_url)$/.test(fieldKey)) {
      dynamic.push(fieldKey);
    }
    if (zoneKey === 'menu_recipe_category_title' && /^menu_nav_recipe_category_\d+_label$/.test(fieldKey)) {
      dynamic.push(fieldKey);
    }
  });

  return dynamic.length ? [...new Set(dynamic)] : configured;
}

function zonePageId(zoneKey) {
  const zoneKeyText = String(zoneKey || '');

  if (
    zoneKeyText.startsWith('menu_footer_')
    || zoneKeyText.startsWith('menu_header_')
    || zoneKeyText === 'menu_recipe_category_title'
  ) {
    return Number(PAGE_IDS?.menu || PAGE_ID);
  }
  if (zoneKeyText.startsWith('nordic_products_')) {
    return Number(PAGE_IDS?.nordic_products || PAGE_ID || PAGE_IDS?.nordic || 0);
  }
  if (zoneKeyText.startsWith('nordic_product_details_')) {
    return Number(PAGE_IDS?.nordic_product_details || PAGE_ID || PAGE_IDS?.nordic || 0);
  }
  if (zoneKeyText.startsWith('nordic_recipes_')) {
    return Number(PAGE_IDS?.nordic_recipes || PAGE_ID || PAGE_IDS?.nordic || 0);
  }
  if (zoneKeyText.startsWith('nordic_recipe_details_')) {
    return Number(PAGE_IDS?.nordic_recipe_details || PAGE_ID || PAGE_IDS?.nordic || 0);
  }
  if (
    zoneKeyText.startsWith('nordic_header_')
    || zoneKeyText.startsWith('nordic_footer_')
  ) {
    return Number(PAGE_IDS?.nordic || PAGE_ID);
  }
  return Number(PAGE_ID);
}

function menuCategoryPageId(categorySlug) {
  const normalized = String(categorySlug || '').trim().toLowerCase();
  if (!normalized) return 0;
  const pageKey = normalized === 'noodles-and-pastas'
    ? 'menu_cat_noodles_pastas'
    : `menu_cat_${normalized.replace(/-/g, '_')}`;
  return Number(PAGE_IDS?.[pageKey] || 0);
}

function syncedMenuItemNamePageIds(fieldKey, currentPageId, zone = null) {
  const ids = new Set([Number(currentPageId || 0)]);
  if (!/^mcli_\d+_name$/.test(String(fieldKey || ''))) {
    return [...ids].filter(Boolean);
  }

  ids.add(Number(PAGE_IDS?.menu || 0));
  ids.add(Number(PAGE_IDS?.menu_item_details || 0));

  const categoryPageId = menuCategoryPageId(zone?._mcliCategorySlug);
  if (categoryPageId) ids.add(categoryPageId);

  return [...ids].filter(Boolean);
}

function updateTextContentCache(fieldKey, pageId, value, font, fontSize, color) {
  const normalizedPageId = Number(pageId || 0);
  if (!normalizedPageId) return;

  const idx = ALL_CONTENTS.findIndex(r => r.key === fieldKey && Number(r.page_id || 0) === normalizedPageId);
  if (idx >= 0) {
    ALL_CONTENTS[idx].value = value;
  } else {
    ALL_CONTENTS.push({ key: fieldKey, value, section: 'text_content', page_id: normalizedPageId });
  }

  const updateCache = (subKey, val) => {
    const ci = ALL_CONTENTS.findIndex(r => r.key === subKey && Number(r.page_id || 0) === normalizedPageId);
    if (val) {
      if (ci >= 0) ALL_CONTENTS[ci].value = val;
      else ALL_CONTENTS.push({ key: subKey, value: val, section: 'text_content', page_id: normalizedPageId });
    } else if (ci >= 0) {
      ALL_CONTENTS.splice(ci, 1);
    }
  };

  updateCache(fieldKey + '_color', color);
  updateCache(fieldKey + '_font', font);
  updateCache(fieldKey + '_font_size', fontSize);
}

function getVeRecipeState() {
  return (typeof VE_RECIPE_STATE !== 'undefined' && VE_RECIPE_STATE) ? VE_RECIPE_STATE : null;
}

function openMenuRecipeCategoryModal(mode = 'create', categoryId = null) {
  const modal = document.getElementById('ve-modal-recipe-category');
  if (!modal) return;

  const title = document.getElementById('ve-recipe-cat-title');
  const idEl = document.getElementById('ve-recipe-cat-id');
  const nameEl = document.getElementById('ve-recipe-cat-name');
  const slugEl = document.getElementById('ve-recipe-cat-slug');
  const iconEl = document.getElementById('ve-recipe-cat-icon');
  const state = getVeRecipeState();
  const categories = Array.isArray(state?.categories) ? state.categories : [];
  const category = categoryId == null
    ? null
    : categories.find((item) => String(item?.id || '') === String(categoryId));

  if (title) title.textContent = mode === 'edit' ? 'Edit category' : 'Add category';
  if (iconEl) iconEl.value = '';

  if (mode === 'edit') {
    if (!category) {
      toast('No recipe category selected', 'error');
      return;
    }
    if (idEl) idEl.value = String(category.id || '');
    if (nameEl) nameEl.value = category.name || '';
    if (slugEl) slugEl.value = category.slug || '';
  } else {
    if (idEl) idEl.value = '';
    if (nameEl) nameEl.value = '';
    if (slugEl) slugEl.value = '';
  }

  openModal('ve-modal-recipe-category');
}

function buildRecipeCategoryEditor(category = null) {
  const id = category?.id ? String(category.id) : '';
  const name = category?.name || '';
  const iconUrl = category?.icon_url || '';
  const font = category?.font || '';
  const size = category?.size || '';
  const color = category?.color ? normalizeHex(category.color) : '#000000';
  const fontOpts = FIELD_FONT_LIST
    .map(f => `<option value="${esc(f)}"${font === f ? ' selected' : ''}>${esc(f) || '— font —'}</option>`)
    .join('');
  const sizeOpts = FIELD_SIZE_LIST
    .map(s => `<option value="${esc(s)}"${size === s ? ' selected' : ''}>${esc(s) || '— size —'}</option>`)
    .join('');
  const wrap = document.createElement('div');
  wrap.className = 've-field-group ve-recipe-cat-inline-group';
  if (id) wrap.dataset.categoryId = id;
  wrap.innerHTML = `
    <label>${esc(id ? 'Recipe item' : 'New recipe item')}</label>
    <input
      type="text"
      class="ve-input ve-recipe-cat-name"
      value="${esc(name)}"
      placeholder="Recipe label"
    />
    <div class="ve-field-style" style="margin-top:8px;">
      <button type="button" class="ve-field-style-toggle" aria-expanded="false">Style options</button>
      <div class="ve-field-style-body" hidden>
        <div class="ve-style-row">
          <select class="ve-select ve-recipe-cat-font">${fontOpts}</select>
          <select class="ve-select ve-recipe-cat-size">${sizeOpts}</select>
          <div class="ve-color-row">
            <input type="color" class="ve-color-input ve-recipe-cat-color" value="${esc(color)}" />
            <span class="ve-color-value">${esc(color)}</span>
          </div>
        </div>
        <div class="ve-recipe-cat-icon-preview">
          ${iconUrl
            ? `<img src="${esc(iconUrl)}" alt="" class="ve-recipe-cat-icon-image" />`
            : `<span class="ve-recipe-cat-icon-fallback">No recipe icon yet.</span>`}
        </div>
        <input type="file" class="ve-recipe-cat-icon-file" accept="image/*,.svg" style="width:100%;margin-top:10px;font-size:13px;" />
        <p style="font-size:12px;color:#666;margin:6px 0 0">Choose a file and save to replace this dropdown icon.</p>
      </div>
    </div>
    <div class="ve-recipe-cat-inline-actions" style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
      <button type="button" class="ve-btn ve-btn--ghost ve-recipe-cat-remove">
        ${id ? 'Delete item' : 'Remove'}
      </button>
    </div>
  `;
  const toggle = wrap.querySelector('.ve-field-style-toggle');
  const body = wrap.querySelector('.ve-field-style-body');
  toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
    if (body) body.hidden = open;
    wrap.querySelector('.ve-field-style')?.classList.toggle('is-open', !open);
  });
  if (toggle) toggle.dataset.veStyleBound = '1';
  const colorInput = wrap.querySelector('.ve-recipe-cat-color');
  const colorValue = wrap.querySelector('.ve-color-value');
  const iconPreview = wrap.querySelector('.ve-recipe-cat-icon-preview');
  const iconFileInput = wrap.querySelector('.ve-recipe-cat-icon-file');
  colorInput?.addEventListener('input', () => {
    if (colorValue) colorValue.textContent = normalizeHex(colorInput.value);
  });
  iconFileInput?.addEventListener('change', () => {
    if (!iconPreview) return;
    const file = iconFileInput.files && iconFileInput.files[0];
    if (!file) {
      iconPreview.innerHTML = iconUrl
        ? `<img src="${esc(iconUrl)}" alt="" class="ve-recipe-cat-icon-image" />`
        : `<span class="ve-recipe-cat-icon-fallback">No recipe icon yet.</span>`;
      return;
    }
    try {
      iconPreview.innerHTML = `<img src="${esc(URL.createObjectURL(file))}" alt="" class="ve-recipe-cat-icon-image" />`;
    } catch (e) {
      iconPreview.innerHTML = iconUrl
        ? `<img src="${esc(iconUrl)}" alt="" class="ve-recipe-cat-icon-image" />`
        : `<span class="ve-recipe-cat-icon-fallback">No recipe icon yet.</span>`;
    }
  });
  return wrap;
}

const BUY_NOW_ICON_CONFIG = {
  menu_nav_buy_now_shopee: {
    iconKey: 'menu_nav_buy_now_shopee_icon',
    fallbackSvg: `<svg viewBox="0 0 24 24" fill="none"><path d="M7 7.5A1.5 1.5 0 0 1 8.5 6h7A1.5 1.5 0 0 1 17 7.5v9A1.5 1.5 0 0 1 15.5 18h-7A1.5 1.5 0 0 1 7 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/><path d="M10 9.5h4M10 12h4M10 14.5h2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9.2 4.8h5.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`,
  },
  menu_nav_buy_now_shopee_mall: {
    iconKey: 'menu_nav_buy_now_shopee_mall_icon',
    fallbackSvg: `<svg viewBox="0 0 24 24" fill="none"><path d="M4 8.5 12 5l8 3.5v9L12 21l-8-3.5v-9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 5v16M4 8.5l8 3.5 8-3.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>`,
  },
  menu_nav_buy_now_lazada: {
    iconKey: 'menu_nav_buy_now_lazada_icon',
    fallbackSvg: `<svg viewBox="0 0 24 24" fill="none"><path d="M4.5 7.5h15v9h-15z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7.5 11h3m-3 3h5m2-6v9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`,
  },
  menu_nav_buy_now_tiktok_shop: {
    iconKey: 'menu_nav_buy_now_tiktok_shop_icon',
    fallbackSvg: `<svg viewBox="0 0 24 24" fill="none"><path d="M14.5 4.5c.7 1.6 1.9 2.8 3.5 3.4v2.4a7.3 7.3 0 0 1-3.2-.8v5.2a4.8 4.8 0 1 1-4.1-4.8v2.3a2.4 2.4 0 1 0 1.8 2.3V4.5h2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
  },
  nordic_nav_buy_now_shopee: {
    iconKey: 'nordic_nav_buy_now_shopee_icon',
    fallbackSvg: `<svg viewBox="0 0 24 24" fill="none"><path d="M7 7.5A1.5 1.5 0 0 1 8.5 6h7A1.5 1.5 0 0 1 17 7.5v9A1.5 1.5 0 0 1 15.5 18h-7A1.5 1.5 0 0 1 7 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/><path d="M10 9.5h4M10 12h4M10 14.5h2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9.2 4.8h5.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`,
  },
  nordic_nav_buy_now_lazada: {
    iconKey: 'nordic_nav_buy_now_lazada_icon',
    fallbackSvg: `<svg viewBox="0 0 24 24" fill="none"><path d="M4.5 7.5h15v9h-15z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7.5 11h3m-3 3h5m2-6v9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`,
  },
  nordic_nav_buy_now_tiktok_shop: {
    iconKey: 'nordic_nav_buy_now_tiktok_shop_icon',
    fallbackSvg: `<svg viewBox="0 0 24 24" fill="none"><path d="M14.5 4.5c.7 1.6 1.9 2.8 3.5 3.4v2.4a7.3 7.3 0 0 1-3.2-.8v5.2a4.8 4.8 0 1 1-4.1-4.8v2.3a2.4 2.4 0 1 0 1.8 2.3V4.5h2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
  },
};

function getBuyNowIconConfig(fieldKey) {
  return BUY_NOW_ICON_CONFIG[fieldKey] || null;
}

function getBuyNowIconEntriesForZone(zoneKey) {
  const prefix = zoneKey === 'nordic_header_nav' ? 'nordic_nav_buy_now_' : 'menu_nav_buy_now_';
  return Object.entries(BUY_NOW_ICON_CONFIG).filter(([fieldKey]) => fieldKey.startsWith(prefix));
}

function renderBuyNowIconPreview(previewEl, fieldKey, src = '') {
  if (!previewEl) return;
  const config = getBuyNowIconConfig(fieldKey);
  if (!config) return;
  const iconSrc = String(src || '').trim();
  previewEl.innerHTML = iconSrc
    ? `<img src="${esc(iconSrc)}" alt="" class="ve-buy-now-icon-image" />`
    : config.fallbackSvg || `<span class="ve-buy-now-icon-fallback">No icon selected.</span>`;
}

function appendBuyNowIconTools(container) {
  if (!['menu_header_nav', 'nordic_header_nav'].includes(_textZoneKey)) return;

  const targetPageId = zonePageId(_textZoneKey);
  getBuyNowIconEntriesForZone(_textZoneKey).forEach(([fieldKey, config]) => {
    const input = container.querySelector(`[data-key="${fieldKey}"]`);
    const group = input?.closest('.ve-field-group');
    const styleBody = group?.querySelector('.ve-field-style-body');
    if (!group || !styleBody || styleBody.querySelector('.ve-buy-now-icon-preview')) return;

    const savedIcon = findContentRow(config.iconKey, targetPageId)?.value || '';
    const wrap = document.createElement('div');
    wrap.className = 've-buy-now-icon-tools';
    wrap.innerHTML = `
      <input type="hidden" class="ve-buy-now-icon-value" value="${esc(savedIcon)}" />
      <div class="ve-buy-now-icon-preview"></div>
      <input type="file" class="ve-buy-now-icon-file" accept="image/*,.svg" style="width:100%;margin-top:10px;font-size:13px;" />
      <p style="font-size:12px;color:#666;margin:6px 0 0">Choose a file and save to replace this dropdown icon.</p>
    `;

    styleBody.appendChild(wrap);

    const preview = wrap.querySelector('.ve-buy-now-icon-preview');
    const iconValueInput = wrap.querySelector('.ve-buy-now-icon-value');
    const iconFileInput = wrap.querySelector('.ve-buy-now-icon-file');
    renderBuyNowIconPreview(preview, fieldKey, savedIcon);

    iconFileInput?.addEventListener('change', () => {
      const file = iconFileInput.files && iconFileInput.files[0];
      if (!file) {
        renderBuyNowIconPreview(preview, fieldKey, iconValueInput?.value || '');
        return;
      }
      try {
        renderBuyNowIconPreview(preview, fieldKey, URL.createObjectURL(file));
      } catch (e) {
        renderBuyNowIconPreview(preview, fieldKey, iconValueInput?.value || '');
      }
    });
  });
}

function buildProductNavEditor(item = null) {
  const label = item?.label || '';
  const url = item?.url || '';
  const customIcon = item?.icon || '';
  const font = item?.font || '';
  const size = item?.size || '';
  const color = item?.color ? normalizeHex(item.color) : '#000000';
  const PRODUCT_DROPDOWN_ICON_MAP = {
    'jelly-mixes': '/images/icons/Jelly-Mixes-Icon.svg',
    'breading-mixes': '/images/icons/Breading-Mixes-Icon.svg',
    'powder-mixes': '/images/icons/Powder-Mixes-Icon.svg',
    'bouillon-cubes': '/images/icons/Bouillon-Cubes-Icon.svg',
    'noodles-and-pastas': '/images/icons/Noodles-and-Pastas-Icon.svg',
    'powdered-drinks': '/images/icons/Powdered-Drinks-Icon.svg',
    'professional-series': '/images/icons/Professional-Series-Icon.svg',
  };
  const normalizeProductSlug = (value) => String(value || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9-]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-+|-+$/g, '');
  const slugFromUrl = (value) => {
    const path = String(value || '').trim();
    const prefix = '/brands/menu/products/category/';
    if (!path.startsWith(prefix)) return '';
    return normalizeProductSlug(path.slice(prefix.length).replace(/^\/+|\/+$/g, ''));
  };
  const resolveProductIcon = () => {
    const slug = slugFromUrl(url) || normalizeProductSlug(label);
    return {
      slug,
      src: customIcon || PRODUCT_DROPDOWN_ICON_MAP[slug] || '',
    };
  };
  const productIcon = resolveProductIcon();
  const fontOpts = FIELD_FONT_LIST
    .map(f => `<option value="${esc(f)}"${font === f ? ' selected' : ''}>${esc(f) || '— font —'}</option>`)
    .join('');
  const sizeOpts = FIELD_SIZE_LIST
    .map(s => `<option value="${esc(s)}"${size === s ? ' selected' : ''}>${esc(s) || '— size —'}</option>`)
    .join('');
  const wrap = document.createElement('div');
  wrap.className = 've-field-group ve-product-nav-inline-group';
  wrap.innerHTML = `
    <label>Product item</label>
    <input
      type="text"
      class="ve-input ve-product-nav-label"
      value="${esc(label)}"
      placeholder="Product label"
    />
    <input type="hidden" class="ve-product-nav-icon-value" value="${esc(customIcon)}" />
    <div class="ve-field-style" style="margin-top:8px;">
      <button type="button" class="ve-field-style-toggle" aria-expanded="false">Style options</button>
      <div class="ve-field-style-body" hidden>
        <div class="ve-style-row">
          <select class="ve-select ve-product-nav-font">${fontOpts}</select>
          <select class="ve-select ve-product-nav-size">${sizeOpts}</select>
          <div class="ve-color-row">
            <input type="color" class="ve-color-input ve-product-nav-color" value="${esc(color)}" />
            <span class="ve-color-value">${esc(color)}</span>
          </div>
        </div>
        <div class="ve-product-nav-icon-preview" data-product-icon-slug="${esc(productIcon.slug)}">
          ${productIcon.src
            ? `<img src="${esc(productIcon.src)}" alt="" class="ve-product-nav-icon-image" />`
            : `<span class="ve-product-nav-icon-fallback">No product icon is mapped for this label yet.</span>`}
        </div>
        <input type="file" class="ve-product-nav-icon-file" accept="image/*" style="width:100%;margin-top:10px;font-size:13px;" />
        <p style="font-size:12px;color:#666;margin:6px 0 0">Choose a file and save to replace this dropdown icon.</p>
      </div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
      <button type="button" class="ve-btn ve-btn--ghost ve-product-nav-remove">Delete item</button>
    </div>
  `;
  const toggle = wrap.querySelector('.ve-field-style-toggle');
  const body = wrap.querySelector('.ve-field-style-body');
  toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
    if (body) body.hidden = open;
    wrap.querySelector('.ve-field-style')?.classList.toggle('is-open', !open);
  });
  if (toggle) toggle.dataset.veStyleBound = '1';
  const colorInput = wrap.querySelector('.ve-product-nav-color');
  const colorValue = wrap.querySelector('.ve-color-value');
  const labelInput = wrap.querySelector('.ve-product-nav-label');
  const iconValueInput = wrap.querySelector('.ve-product-nav-icon-value');
  const iconPreview = wrap.querySelector('.ve-product-nav-icon-preview');
  const iconFileInput = wrap.querySelector('.ve-product-nav-icon-file');
  colorInput?.addEventListener('input', () => {
    if (colorValue) colorValue.textContent = normalizeHex(colorInput.value);
  });
  const syncIconPreview = (previewSrc = '') => {
    if (!iconPreview) return;
    const slug = slugFromUrl(url) || normalizeProductSlug(labelInput.value);
    const src = previewSrc || iconValueInput?.value || PRODUCT_DROPDOWN_ICON_MAP[slug] || '';
    iconPreview.dataset.productIconSlug = slug;
    iconPreview.innerHTML = src
      ? `<img src="${esc(src)}" alt="" class="ve-product-nav-icon-image" />`
      : `<span class="ve-product-nav-icon-fallback">No product icon is mapped for this label yet.</span>`;
  };
  labelInput?.addEventListener('input', () => syncIconPreview());
  iconFileInput?.addEventListener('change', () => {
    const file = iconFileInput.files && iconFileInput.files[0];
    if (!file) {
      syncIconPreview();
      return;
    }
    try {
      syncIconPreview(URL.createObjectURL(file));
    } catch (e) {
      syncIconPreview();
    }
  });
  return wrap;
}

function appendMenuHeaderProductTools(container, zone) {
  if (_textZoneKey !== 'menu_header_nav') return;

  const targetPageId = zonePageId(_textZoneKey);
  const dynamicRows = ALL_CONTENTS
    .filter((row) => Number(row.page_id || 0) === targetPageId && /^menu_nav_products_item_\d+_label$/.test(String(row.key || '')))
    .sort((a, b) => {
      const ai = Number(String(a.key || '').match(/^menu_nav_products_item_(\d+)_label$/)?.[1] || 0);
      const bi = Number(String(b.key || '').match(/^menu_nav_products_item_(\d+)_label$/)?.[1] || 0);
      return ai - bi;
    })
    .map((row) => {
      const idx = String(row.key || '').match(/^menu_nav_products_item_(\d+)_label$/)?.[1] || '';
      return {
        label: row.value || '',
        url: findContentRow(`menu_nav_products_item_${idx}_url`, targetPageId)?.value || '',
        icon: findContentRow(`menu_nav_products_item_${idx}_label_icon`, targetPageId)?.value || '',
        font: findContentRow(`menu_nav_products_item_${idx}_label_font`, targetPageId)?.value || '',
        size: findContentRow(`menu_nav_products_item_${idx}_label_font_size`, targetPageId)?.value || '',
        color: findContentRow(`menu_nav_products_item_${idx}_label_color`, targetPageId)?.value || '',
      };
    });

  const legacyKeys = [
    ['menu_nav_products_jelly_mixes', 'menu_nav_products_jelly_mixes_url'],
    ['menu_nav_products_breading_mixes', 'menu_nav_products_breading_mixes_url'],
    ['menu_nav_products_powder_mixes', 'menu_nav_products_powder_mixes_url'],
    ['menu_nav_products_bouillon_cubes', 'menu_nav_products_bouillon_cubes_url'],
    ['menu_nav_products_noodles_pastas', 'menu_nav_products_noodles_pastas_url'],
    ['menu_nav_products_powdered_drinks', 'menu_nav_products_powdered_drinks_url'],
    ['menu_nav_products_professional_series', 'menu_nav_products_professional_series_url'],
  ];

  const rows = dynamicRows.length
    ? dynamicRows
    : legacyKeys.map(([labelKey, urlKey]) => ({
        label: findContentRow(labelKey, targetPageId)?.value ?? zone?.defaults?.[labelKey] ?? '',
        url: findContentRow(urlKey, targetPageId)?.value ?? zone?.defaults?.[urlKey] ?? '',
        icon: findContentRow(`${labelKey}_icon`, targetPageId)?.value || '',
        font: findContentRow(`${labelKey}_font`, targetPageId)?.value || '',
        size: findContentRow(`${labelKey}_font_size`, targetPageId)?.value || '',
        color: findContentRow(`${labelKey}_color`, targetPageId)?.value || '',
      }));

  const wrap = document.createElement('div');
  wrap.className = 've-field-group ve-header-product-nav-group';
  wrap.innerHTML = `
    <p style="margin:0 0 10px;color:#6b7280;font-size:13px;">
      Edit the product dropdown items directly here. You can also add or remove items before saving.
    </p>
    <div class="ve-product-nav-inline-list"></div>
    <button type="button" class="ve-btn ve-btn--primary ve-product-nav-add" style="margin-top:10px;">
      Add product dropdown item
    </button>
  `;

  const list = wrap.querySelector('.ve-product-nav-inline-list');
  if (list) {
    rows.forEach((row) => list.appendChild(buildProductNavEditor(row)));
  }

  wrap.querySelector('.ve-product-nav-add')?.addEventListener('click', () => {
    list?.appendChild(buildProductNavEditor());
  });

  wrap.addEventListener('click', (e) => {
    const removeBtn = e.target.closest('.ve-product-nav-remove');
    if (!removeBtn) return;
    const group = removeBtn.closest('.ve-product-nav-inline-group');
    const label = group?.querySelector('.ve-product-nav-label')?.value?.trim() || 'this product item';
    if (!window.confirm(`Delete "${label}" from the product dropdown? Save Changes is required to apply it.`)) {
      return;
    }
    group?.remove();
  });

  container.appendChild(wrap);
}

function appendMenuHeaderRecipeCategoryTools(container) {
  if (_textZoneKey !== 'menu_header_nav') return;

  const state = getVeRecipeState();
  const categories = Array.isArray(state?.categories) ? state.categories : [];

  const wrap = document.createElement('div');
  wrap.className = 've-field-group ve-header-recipe-cat-group';
  wrap.innerHTML = `
    <p style="margin:0 0 10px;color:#6b7280;font-size:13px;">
      Edit the recipe dropdown items directly here. You can also add or remove items before saving.
    </p>
    <div class="ve-recipe-cat-inline-list"></div>
    <button type="button" class="ve-btn ve-btn--primary ve-header-recipe-cat-add" style="margin-top:10px;">
      Add recipe dropdown item
    </button>
  `;

  const list = wrap.querySelector('.ve-recipe-cat-inline-list');
  if (list) {
    if (categories.length) {
      categories.forEach((category) => {
        const baseKey = `menu_nav_recipe_category_${category.id}_label`;
        list.appendChild(buildRecipeCategoryEditor({
          ...category,
          icon_url: category.icon_url || '',
          font: findContentRow(`${baseKey}_font`, zonePageId(_textZoneKey))?.value || '',
          size: findContentRow(`${baseKey}_font_size`, zonePageId(_textZoneKey))?.value || '',
          color: findContentRow(`${baseKey}_color`, zonePageId(_textZoneKey))?.value || '',
        }));
      });
    } else {
      const empty = document.createElement('div');
      empty.className = 've-nav-modal-empty';
      empty.textContent = 'No recipe dropdown items yet.';
      list.appendChild(empty);
    }
  }

  wrap.querySelector('.ve-header-recipe-cat-add')?.addEventListener('click', () => {
    const inlineList = wrap.querySelector('.ve-recipe-cat-inline-list');
    if (!inlineList) return;
    inlineList.querySelector('.ve-nav-modal-empty')?.remove();
    inlineList.appendChild(buildRecipeCategoryEditor());
  });

  wrap.addEventListener('click', (e) => {
    const removeBtn = e.target.closest('.ve-recipe-cat-remove');
    if (!removeBtn) return;
    const group = removeBtn.closest('.ve-recipe-cat-inline-group');
    if (!group) return;
    const label = group.querySelector('.ve-recipe-cat-name')?.value?.trim() || 'this recipe item';
    if (!window.confirm(`Delete "${label}" from the recipe dropdown? Save Changes is required to apply it.`)) {
      return;
    }
    const id = group.dataset.categoryId || '';
    if (id) _pendingRecipeCategoryDeletes.add(id);
    group.remove();
    const inlineList = wrap.querySelector('.ve-recipe-cat-inline-list');
    if (inlineList && !inlineList.querySelector('.ve-recipe-cat-inline-group')) {
      const empty = document.createElement('div');
      empty.className = 've-nav-modal-empty';
      empty.textContent = 'No recipe dropdown items yet.';
      inlineList.appendChild(empty);
    }
  });

  container.appendChild(wrap);
}

function createMenuHeaderNavSection(title, description, open = false) {
  const section = document.createElement('details');
  section.className = 've-nav-modal-section';
  if (open) section.open = true;
  section.innerHTML = `
    <summary>
      <span class="ve-nav-modal-section-title">${esc(title)}</span>
      <span class="ve-nav-modal-section-desc">${esc(description)}</span>
    </summary>
    <div class="ve-nav-modal-section-body"></div>
  `;
  return section;
}

function collectMenuHeaderFieldGroups(container) {
  return Array.from(container.children).filter((child) => child.classList.contains('ve-field-group'));
}

function fieldKeyFromGroup(group) {
  return group.querySelector('[data-key]')?.getAttribute('data-key') || '';
}

function findMediaRowById(section, id) {
  const mediaId = String(id || '').trim();
  if (!mediaId) return null;
  return ALL_MEDIA.find((row) => row.section === section && String(row.id) === mediaId) || null;
}

function resolveZoneFormMedia(zone, targetPageId) {
  if (!zone?.form_media_key || !zone?.form_media_section) {
    return { value: '', row: null, url: zone?.form_media_fallback_url || '' };
  }

  const value = String(findContentRow(zone.form_media_key, targetPageId)?.value || '').trim();
  const row = findMediaRowById(zone.form_media_section, value);
  const url = row ? mediaUrl(row) : (zone.form_media_fallback_url || '');
  return { value, row, url };
}

function prependTextZoneMediaTools(container, zone) {
  if (!zone?.form_media_key || !zone?.form_media_section) return;

  const targetPageId = zonePageId(_textZoneKey);
  const currentMedia = resolveZoneFormMedia(zone, targetPageId);
  const wrap = document.createElement('div');
  wrap.className = 've-field-group ve-text-zone-media-group';
  wrap.innerHTML = `
    <label>${esc(zone.form_media_label || 'Background image')}</label>
    <input type="hidden" class="ve-text-zone-media-value" value="${esc(currentMedia.value)}" />
    <div class="ve-text-zone-media-preview" style="border:1px solid #d7dfec;border-radius:14px;overflow:hidden;background:#f4f7fb;margin-bottom:10px;">
      ${currentMedia.url
        ? `<img src="${esc(currentMedia.url)}" alt="" class="ve-text-zone-media-image" style="width:100%;height:180px;display:block;object-fit:cover;" />`
        : `<span class="ve-text-zone-media-empty" style="display:block;padding:18px;color:#64748b;font-size:13px;">No background image selected yet.</span>`}
    </div>
    <input type="file" class="ve-text-zone-media-file" accept="image/*,.svg" style="width:100%;font-size:13px;" />
    <p style="font-size:12px;color:#666;margin:6px 0 0">Choose a file and save to replace this background image.</p>
  `;

  container.prepend(wrap);

  const preview = wrap.querySelector('.ve-text-zone-media-preview');
  const fileInput = wrap.querySelector('.ve-text-zone-media-file');
  fileInput?.addEventListener('change', () => {
    const file = fileInput.files && fileInput.files[0];
    if (!preview) return;
    if (!file) {
      preview.innerHTML = currentMedia.url
        ? `<img src="${esc(currentMedia.url)}" alt="" class="ve-text-zone-media-image" style="width:100%;height:180px;display:block;object-fit:cover;" />`
        : `<span class="ve-text-zone-media-empty" style="display:block;padding:18px;color:#64748b;font-size:13px;">No background image selected yet.</span>`;
      return;
    }
    try {
      preview.innerHTML = `<img src="${esc(URL.createObjectURL(file))}" alt="" class="ve-text-zone-media-image" style="width:100%;height:180px;display:block;object-fit:cover;" />`;
    } catch (e) {
      preview.innerHTML = currentMedia.url
        ? `<img src="${esc(currentMedia.url)}" alt="" class="ve-text-zone-media-image" style="width:100%;height:180px;display:block;object-fit:cover;" />`
        : `<span class="ve-text-zone-media-empty" style="display:block;padding:18px;color:#64748b;font-size:13px;">No background image selected yet.</span>`;
    }
  });
}

function resolveNordicSelectedProductConfig(zone) {
  const products = Array.isArray(zone?.products) ? zone.products : [];
  const params = new URLSearchParams(window.location.search);
  const selectedSlug = String(params.get('product') || products[0]?.slug || 'whole-grain-oats').trim();
  return products.find((product) => String(product?.slug || '') === selectedSlug) || products[0] || null;
}

function appendNordicSelectedProductTools(container, zone) {
  if (_textZoneKey !== 'nordic_products_selected_product') return;

  const product = resolveNordicSelectedProductConfig(zone);
  if (!product) return;

  const targetPageId = zonePageId(_textZoneKey);
  zone._nordicSelectedProduct = product;

  const buildPreviewSrc = (mediaKey, fallback) => {
    const row = findMediaRowById('product', findContentRow(mediaKey, targetPageId)?.value || '');
    if (row) return mediaUrl(row);
    const fallbackPath = String(fallback || '').trim();
    if (!fallbackPath) return '';
    if (/^https?:\/\//i.test(fallbackPath) || fallbackPath.startsWith('/')) return fallbackPath;
    return `/${fallbackPath.replace(/^\/+/, '')}`;
  };

  const imageTools = [
    {
      label: product.front_label || 'Front image',
      mediaKey: product.front_media_key,
      fallback: product.front_fallback,
      className: 've-nordic-product-front',
    },
    {
      label: product.back_label || 'Back image',
      mediaKey: product.back_media_key,
      fallback: product.back_fallback,
      className: 've-nordic-product-back',
    },
  ];

  imageTools.reverse().forEach((tool) => {
    const previewSrc = buildPreviewSrc(tool.mediaKey, tool.fallback);
    const wrap = document.createElement('div');
    wrap.className = `ve-field-group ve-nordic-product-media-group ${tool.className}`;
    wrap.dataset.mediaKey = tool.mediaKey;
    wrap.innerHTML = `
      <label>${esc(tool.label)}</label>
      <div class="ve-nordic-product-media-preview" style="border:1px solid #d7dfec;border-radius:14px;overflow:hidden;background:#f4f7fb;margin-bottom:10px;">
        ${previewSrc
          ? `<img src="${esc(previewSrc)}" alt="" style="width:100%;height:180px;display:block;object-fit:contain;background:#fff;" />`
          : `<span style="display:block;padding:18px;color:#64748b;font-size:13px;">No product image selected yet.</span>`}
      </div>
      <input type="file" class="ve-nordic-product-media-file" accept="image/jpeg,image/png,image/webp" style="width:100%;font-size:13px;" />
      <p style="font-size:12px;color:#666;margin:6px 0 0">Choose a file and save to replace this product image.</p>
    `;

    const titleGroup = container.querySelector(`[data-key="${product.title_key}"]`)?.closest('.ve-field-group');
    if (titleGroup) {
      container.insertBefore(wrap, titleGroup.nextSibling);
    } else {
      container.prepend(wrap);
    }

    const preview = wrap.querySelector('.ve-nordic-product-media-preview');
    const fileInput = wrap.querySelector('.ve-nordic-product-media-file');
    fileInput?.addEventListener('change', () => {
      const file = fileInput.files && fileInput.files[0];
      if (!preview) return;
      if (!file) {
        preview.innerHTML = previewSrc
          ? `<img src="${esc(previewSrc)}" alt="" style="width:100%;height:180px;display:block;object-fit:contain;background:#fff;" />`
          : `<span style="display:block;padding:18px;color:#64748b;font-size:13px;">No product image selected yet.</span>`;
        return;
      }
      try {
        preview.innerHTML = `<img src="${esc(URL.createObjectURL(file))}" alt="" style="width:100%;height:180px;display:block;object-fit:contain;background:#fff;" />`;
      } catch (e) {
        preview.innerHTML = previewSrc
          ? `<img src="${esc(previewSrc)}" alt="" style="width:100%;height:180px;display:block;object-fit:contain;background:#fff;" />`
          : `<span style="display:block;padding:18px;color:#64748b;font-size:13px;">No product image selected yet.</span>`;
      }
    });
  });
}

function organizeMenuHeaderNavFields(container) {
  if (_textZoneKey !== 'menu_header_nav') return;

  const groups = collectMenuHeaderFieldGroups(container);
  const recipeGroup = groups.find((group) => group.classList.contains('ve-header-recipe-cat-group')) || null;
  const productGroup = groups.find((group) => group.classList.contains('ve-header-product-nav-group')) || null;
  const standardGroups = groups.filter((group) => group !== recipeGroup && group !== productGroup);

  const generalSection = createMenuHeaderNavSection(
    'Main navigation',
    'Top-level header labels',
    false,
  );
  const productsSection = createMenuHeaderNavSection(
    'Products',
    'Dropdown labels for the product menu',
    false,
  );
  const recipesSection = createMenuHeaderNavSection(
    'Recipes',
    'Recipe category items shown in the header',
    false,
  );
  const buyNowSection = createMenuHeaderNavSection(
    'Buy Now',
    'Marketplace labels shown under Buy Now',
    false,
  );

  const generalBody = generalSection.querySelector('.ve-nav-modal-section-body');
  const productsBody = productsSection.querySelector('.ve-nav-modal-section-body');
  const recipesBody = recipesSection.querySelector('.ve-nav-modal-section-body');
  const buyNowBody = buyNowSection.querySelector('.ve-nav-modal-section-body');

  standardGroups.forEach((group) => {
    const fieldKey = fieldKeyFromGroup(group);
    if (!fieldKey) return;

    if (/^menu_nav_products_/.test(fieldKey)) {
      return;
    }

    if (/^menu_nav_buy_now_/.test(fieldKey)) {
      buyNowBody?.appendChild(group);
      return;
    }

    generalBody?.appendChild(group);
  });

  if (productGroup) {
    productsBody?.appendChild(productGroup);
  }

  if (recipeGroup) {
    recipesBody?.appendChild(recipeGroup);
  } else {
    const empty = document.createElement('div');
    empty.className = 've-nav-modal-empty';
    empty.textContent = 'No recipe dropdown items yet.';
    recipesBody?.appendChild(empty);
  }

  container.innerHTML = '';

  [generalSection, productsSection, recipesSection, buyNowSection].forEach((section) => {
    const body = section.querySelector('.ve-nav-modal-section-body');
    if (!body || !body.children.length) return;
    container.appendChild(section);
  });
}

function organizeNordicHeaderNavFields(container) {
  if (_textZoneKey !== 'nordic_header_nav') return;

  const groups = collectMenuHeaderFieldGroups(container);
  const generalSection = createMenuHeaderNavSection(
    'Main navigation',
    'Top-level header labels',
    false,
  );
  const buyNowSection = createMenuHeaderNavSection(
    'Buy Now',
    'Marketplace labels and links shown under Buy Now',
    false,
  );

  const generalBody = generalSection.querySelector('.ve-nav-modal-section-body');
  const buyNowBody = buyNowSection.querySelector('.ve-nav-modal-section-body');

  groups.forEach((group) => {
    const fieldKey = fieldKeyFromGroup(group);
    if (!fieldKey) return;

    if (/^nordic_nav_buy_now/.test(fieldKey)) {
      buyNowBody?.appendChild(group);
      return;
    }

    generalBody?.appendChild(group);
  });

  container.innerHTML = '';

  [generalSection, buyNowSection].forEach((section) => {
    const body = section.querySelector('.ve-nav-modal-section-body');
    if (!body || !body.children.length) return;
    container.appendChild(section);
  });
}

function findContentRow(fieldKey, targetPageId = null) {
  const normalizedPageId = targetPageId == null ? null : Number(targetPageId);
  return ALL_CONTENTS.find(r => {
    if (r.key !== fieldKey) return false;
    if (normalizedPageId == null) return true;
    return Number(r.page_id || 0) === normalizedPageId;
  }) || ALL_CONTENTS.find(r => r.key === fieldKey);
}

function openTextModal(zoneKey, zone) {
  _textZoneKey = zoneKey;
  _textZone    = zone;

  document.getElementById('ve-text-modal-title').textContent = `Edit: ${zone.label}`;

  const container = document.getElementById('ve-text-fields');
  container.innerHTML = '';

  const fields = resolveTextZoneFields(zoneKey, zone);
  _textZoneFields = fields;

  _textSaved        = false;
  _textOriginalVals = {};
  _textOriginalFont = {};
  _pendingRecipeCategoryDeletes = new Set();

  fields.forEach(fieldKey => {
    const targetPageId = zonePageId(zoneKey);
    const existing   = findContentRow(fieldKey, targetPageId);
    const value      = existing ? (existing.value ?? '') : (zone.defaults?.[fieldKey] ?? '');
    const savedFont  = findContentRow(fieldKey + '_font', targetPageId)?.value  ?? '';
    const savedSize  = findContentRow(fieldKey + '_font_size', targetPageId)?.value ?? '';
    const savedColor = findContentRow(fieldKey + '_color', targetPageId)?.value ?? '';
    const hexColor   = savedColor ? normalizeHex(savedColor) : '#000000';

    const label  = zone?.field_labels?.[fieldKey]
      || fieldKey.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    const isLong = fieldKey.includes('desc') || fieldKey.includes('para') || fieldKey.includes('text')
      || /^mcli_\d+_name$/.test(fieldKey)
      || /^mr_\d+_name$/.test(fieldKey);

    // Snapshot for undo
    _textOriginalVals[fieldKey] = value;
    // hadColor: true only when there is an actual saved record (not just the #000000 default)
    _textOriginalFont[fieldKey] = { family: savedFont, size: savedSize, color: hexColor, hadColor: savedColor !== '' };

    const fontOpts = FIELD_FONT_LIST
      .map(f => `<option value="${esc(f)}"${savedFont === f ? ' selected' : ''}>${esc(f) || '— font —'}</option>`)
      .join('');
    const sizeOpts = FIELD_SIZE_LIST
      .map(s => `<option value="${esc(s)}"${savedSize === s ? ' selected' : ''}>${esc(s) || '— size —'}</option>`)
      .join('');

    const group = document.createElement('div');
    group.className = 've-field-group';
    group.innerHTML = `
      <label for="ve-field-${esc(fieldKey)}">${esc(label)}</label>
      ${isLong
        ? `<textarea id="ve-field-${esc(fieldKey)}" class="ve-textarea" data-key="${esc(fieldKey)}" rows="3">${esc(value)}</textarea>`
        : `<input type="text" id="ve-field-${esc(fieldKey)}" class="ve-input" data-key="${esc(fieldKey)}" value="${esc(value)}" />`
      }
      <div class="ve-field-style" data-field-key="${esc(fieldKey)}">
        <button type="button" class="ve-field-style-toggle" aria-expanded="false">Style options</button>
        <div class="ve-field-style-body" hidden>
          <div class="ve-style-row">
            <select class="ve-select" data-style-key="${esc(fieldKey)}" data-style-type="font">${fontOpts}</select>
            <select class="ve-select" data-style-key="${esc(fieldKey)}" data-style-type="size">${sizeOpts}</select>
            <div class="ve-color-row">
              <input type="color" class="ve-color-input ve-field-color" data-style-key="${esc(fieldKey)}" value="${esc(hexColor)}" />
              <span class="ve-color-value">${esc(hexColor)}</span>
            </div>
          </div>
        </div>
      </div>
    `;
    container.appendChild(group);
  });

  appendMenuHeaderProductTools(container, zone);
  appendMenuHeaderRecipeCategoryTools(container);
  appendBuyNowIconTools(container);
  prependTextZoneMediaTools(container, zone);
  appendNordicSelectedProductTools(container, zone);
  organizeMenuHeaderNavFields(container);
  organizeNordicHeaderNavFields(container);

  container.querySelectorAll('.ve-field-style').forEach((wrap) => {
    const btn = wrap.querySelector('.ve-field-style-toggle');
    const body = wrap.querySelector('.ve-field-style-body');
    if (!btn || !body || btn.dataset.veStyleBound === '1') return;
    btn.addEventListener('click', () => {
      const open = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', open ? 'false' : 'true');
      body.hidden = open;
      wrap.classList.toggle('is-open', !open);
    });
    btn.dataset.veStyleBound = '1';
  });

  // Match picker to what is actually rendered in the canvas (not only DB)
  const canvas = document.getElementById('ve-canvas');
  fields.forEach(fieldKey => {
    const el = canvas?.querySelector(`[data-ve-field="${CSS.escape(fieldKey)}"]`);
    if (!el) return;
    const hex = rgbStringToHex(getComputedStyle(el).color);
    if (!hex) return;
    const styleEl = container.querySelector(`.ve-field-style[data-field-key="${CSS.escape(fieldKey)}"]`);
    const colorInp = styleEl?.querySelector('.ve-field-color');
    const colorVal = styleEl?.querySelector('.ve-color-value');
    if (colorInp) colorInp.value = hex;
    if (colorVal) colorVal.textContent = hex;
    if (_textOriginalFont[fieldKey]) _textOriginalFont[fieldKey].color = hex;
  });

  // Product grid: replace image from same modal as product name (mcli_{id}_name)
  const mcliMatch = fields.length === 1 ? String(fields[0] || '').match(/^mcli_(\d+)_name$/) : null;
  const mrMatch = fields.length === 1 ? String(fields[0] || '').match(/^mr_(\d+)_name$/) : null;
  if (mcliMatch) {
    const itemId = mcliMatch[1];
    const card = canvas?.querySelector(`.menu-category-card[data-item-id="${CSS.escape(itemId)}"]`);
    const detailInfo = canvas?.querySelector('.menu-product-info[data-category-slug]');
    const thumbImg = card?.querySelector('.menu-category-card-thumb img');
    const src = thumbImg?.getAttribute('src') || '';
    const cardBackSrc = card?.getAttribute('data-back-image-url') || '';
    const detailFlipCard = canvas?.querySelector('#menuProductFlipCard');
    const detailFrontImg = detailFlipCard?.querySelector('.menu-product-flip-face.is-front');
    const detailBackImg = detailFlipCard?.querySelector('.menu-product-flip-face.is-back');
    const frontSrc = detailFrontImg?.getAttribute('src') || src;
    const backSrc = detailBackImg?.getAttribute('src') || cardBackSrc || '';
    const categorySlug = card?.getAttribute('data-category-slug') || detailInfo?.getAttribute('data-category-slug') || '';
    const isJelly = isJellyMixesCategory(categorySlug);
    const flavorType = normalizeMenuItemFlavorType(card?.getAttribute('data-item-flavor-type'));
    const flavorWrap = document.createElement('div');
    flavorWrap.className = 've-field-group';
    flavorWrap.hidden = !isJelly;
    flavorWrap.innerHTML = `
      <p class="ve-section-label" style="margin-top:1rem">Product type</p>
      <select id="ve-mcli-flavor-type" class="ve-select" style="width:100%;" ${isJelly ? '' : 'disabled'}>
        <option value="unflavored"${flavorType === 'unflavored' ? ' selected' : ''}>Unflavored</option>
        <option value="flavored"${flavorType === 'flavored' ? ' selected' : ''}>Flavored</option>
      </select>
    `;
    container.appendChild(flavorWrap);
    const wrap = document.createElement('div');
    wrap.className = 've-field-group ve-mcli-image-group';
    wrap.innerHTML = `
      <p class="ve-section-label" style="margin-top:1rem">Product front image</p>
      <div class="ve-mcli-image-preview-wrap" style="margin-bottom:10px;max-width:220px;border-radius:10px;overflow:hidden;background:#f3f4f6;min-height:80px;display:flex;align-items:center;justify-content:center;">
        ${frontSrc ? `<img id="ve-mcli-image-preview" src="${esc(frontSrc)}" alt="" style="max-width:100%;max-height:160px;display:block;" />` : '<span class="ve-mcli-no-image" style="padding:1rem;color:#888;font-size:13px;">No image yet</span>'}
      </div>
      <input type="file" id="ve-mcli-image-input" accept="image/jpeg,image/png,image/webp" style="width:100%;font-size:13px;" />
      <p style="font-size:12px;color:#666;margin:6px 0 0">Choose a new file and Save to replace the front product image.</p>
      <p class="ve-section-label" style="margin-top:1rem">Product back image</p>
      <div class="ve-mcli-image-preview-wrap" style="margin-bottom:10px;max-width:220px;border-radius:10px;overflow:hidden;background:#f3f4f6;min-height:80px;display:flex;align-items:center;justify-content:center;">
        ${backSrc ? `<img id="ve-mcli-back-image-preview" src="${esc(backSrc)}" alt="" style="max-width:100%;max-height:160px;display:block;" />` : '<span class="ve-mcli-no-back-image" style="padding:1rem;color:#888;font-size:13px;">No back image yet</span>'}
      </div>
      <input type="file" id="ve-mcli-back-image-input" accept="image/jpeg,image/png,image/webp" style="width:100%;font-size:13px;" />
      <p style="font-size:12px;color:#666;margin:6px 0 0">Choose a new file and Save to replace the back product image.</p>
    `;
    container.appendChild(wrap);
    const fileInp = wrap.querySelector('#ve-mcli-image-input');
    const backFileInp = wrap.querySelector('#ve-mcli-back-image-input');
    const updatePreview = (input, previewId, emptyClass, wrapIndex) => {
      input?.addEventListener('change', () => {
        const f = input.files && input.files[0];
        if (!f) return;
        let img = wrap.querySelector(`#${previewId}`);
        if (!img) {
          const no = wrap.querySelector(`.${emptyClass}`);
          if (no) no.remove();
          const previewWrap = wrap.querySelectorAll('.ve-mcli-image-preview-wrap')[wrapIndex];
          if (previewWrap) {
            img = document.createElement('img');
            img.id = previewId;
            img.alt = '';
            img.style.cssText = 'max-width:100%;max-height:160px;display:block;';
            previewWrap.appendChild(img);
          }
        }
        if (img) try { img.src = URL.createObjectURL(f); } catch (e) { /* ignore */ }
      });
    };
    updatePreview(fileInp, 've-mcli-image-preview', 've-mcli-no-image', 0);
    updatePreview(backFileInp, 've-mcli-back-image-preview', 've-mcli-no-back-image', 1);
    zone._mcliItemId = itemId;
    zone._mcliCategorySlug = categorySlug;
    zone._mcliOriginalFlavorType = flavorType;
    zone._mcliFlavorDisabled = !isJelly;
    zone._mrRecipeId = null;
  } else if (mrMatch) {
    const recipeId = mrMatch[1];
    const card = canvas?.querySelector(`.menu-category-card[data-recipe-id="${CSS.escape(recipeId)}"]`);
    const thumbImg = card?.querySelector('.menu-category-card-thumb img');
    const src = thumbImg?.getAttribute('src') || '';
    const wrap = document.createElement('div');
    wrap.className = 've-field-group ve-mr-image-group';
    wrap.innerHTML = `
      <p class="ve-section-label" style="margin-top:1rem">Recipe image</p>
      <div class="ve-mr-image-preview-wrap" style="margin-bottom:10px;max-width:220px;border-radius:10px;overflow:hidden;background:#f3f4f6;min-height:80px;display:flex;align-items:center;justify-content:center;">
        ${src ? `<img id="ve-mr-image-preview" src="${esc(src)}" alt="" style="max-width:100%;max-height:160px;display:block;" />` : '<span class="ve-mr-no-image" style="padding:1rem;color:#888;font-size:13px;">No image yet</span>'}
      </div>
      <input type="file" id="ve-mr-image-input" accept="image/*" style="width:100%;font-size:13px;" />
      <p style="font-size:12px;color:#666;margin:6px 0 0">Choose a new file and Save to replace the card photo.</p>
    `;
    container.appendChild(wrap);
    const fileInp = wrap.querySelector('#ve-mr-image-input');
    fileInp?.addEventListener('change', () => {
      const f = fileInp.files && fileInp.files[0];
      if (!f) return;
      let img = wrap.querySelector('#ve-mr-image-preview');
      if (!img) {
        const no = wrap.querySelector('.ve-mr-no-image');
        if (no) no.remove();
        const w = wrap.querySelector('.ve-mr-image-preview-wrap');
        if (w) {
          img = document.createElement('img');
          img.id = 've-mr-image-preview';
          img.alt = '';
          img.style.cssText = 'max-width:100%;max-height:160px;display:block;';
          w.appendChild(img);
        }
      }
      if (img) try { img.src = URL.createObjectURL(f); } catch (e) { /* ignore */ }
    });
    zone._mcliItemId = null;
    zone._mcliCategorySlug = null;
    zone._mcliOriginalFlavorType = null;
    zone._mcliFlavorDisabled = true;
    zone._mrRecipeId = recipeId;
  } else {
    zone._mcliItemId = null;
    zone._mcliCategorySlug = null;
    zone._mcliOriginalFlavorType = null;
    zone._mcliFlavorDisabled = true;
    zone._mrRecipeId = null;
  }

  _textPrevSave = _loadPrevSave(zoneKey);
  _refreshTextUndoButton();

  openModal('ve-modal-text');
}

/** Dynamically load a Google Font if it isn't already in the page. */
function loadGoogleFont(family) {
  if (!family || !String(family).trim()) return;
  const f = String(family).trim();
  if (['DM Sans', 'Anek Latin', 'Spline Sans'].includes(f)) return;
  const id = `gf-${f.replace(/\s+/g, '-').replace(/[^a-zA-Z0-9-]/g, '')}`;
  if (document.getElementById(id)) return;
  const link = document.createElement('link');
  link.id   = id;
  link.rel  = 'stylesheet';
  link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(f).replace(/%20/g, '+')}:wght@400;600;700&display=swap`;
  document.head.appendChild(link);
}

function isJellyMixesCategory(slug) {
  return String(slug || '').trim().toLowerCase() === 'jelly-mixes';
}

function normalizeMenuItemFlavorType(value, fallback = 'unflavored') {
  const normalized = String(value || '').trim().toLowerCase();
  return ['flavored', 'unflavored'].includes(normalized) ? normalized : fallback;
}

function syncMenuItemFlavorField(categorySlug, flavorType = 'unflavored') {
  const wrap = document.getElementById('ve-menu-item-flavor-wrap');
  const select = document.getElementById('ve-menu-item-flavor-type');
  if (!wrap || !select) return;

  const show = isJellyMixesCategory(categorySlug);
  wrap.hidden = !show;
  select.disabled = !show;
  if (show) {
    select.value = normalizeMenuItemFlavorType(flavorType);
  } else {
    select.value = 'unflavored';
  }
}

function snapshotMenuHeaderNavState(targetPageId) {
  const productRows = [];
  const productUseDynamic = String(findContentRow('menu_nav_products_use_dynamic', targetPageId)?.value || '').trim() === '1';
  ALL_CONTENTS
    .filter((row) => Number(row.page_id || 0) === targetPageId && /^menu_nav_products_item_\d+_label$/.test(String(row.key || '')))
    .sort((a, b) => {
      const ai = Number(String(a.key || '').match(/^menu_nav_products_item_(\d+)_label$/)?.[1] || 0);
      const bi = Number(String(b.key || '').match(/^menu_nav_products_item_(\d+)_label$/)?.[1] || 0);
      return ai - bi;
    })
    .forEach((row) => {
      const idx = String(row.key || '').match(/^menu_nav_products_item_(\d+)_label$/)?.[1] || '';
      const baseKey = `menu_nav_products_item_${idx}_label`;
      productRows.push({
        label: row.value || '',
        icon: findContentRow(`${baseKey}_icon`, targetPageId)?.value || '',
        font: findContentRow(`${baseKey}_font`, targetPageId)?.value || '',
        size: findContentRow(`${baseKey}_font_size`, targetPageId)?.value || '',
        color: findContentRow(`${baseKey}_color`, targetPageId)?.value || '',
      });
    });

  const recipeRows = [];
  const state = getVeRecipeState();
  const categories = Array.isArray(state?.categories) ? state.categories : [];
  categories.forEach((category) => {
    const id = String(category?.id || '').trim();
    if (!id) return;
    const baseKey = `menu_nav_recipe_category_${id}_label`;
    recipeRows.push({
      id,
      name: findContentRow(baseKey, targetPageId)?.value || category.name || '',
      icon_url: category.icon_url || '',
      font: findContentRow(`${baseKey}_font`, targetPageId)?.value || '',
      size: findContentRow(`${baseKey}_font_size`, targetPageId)?.value || '',
      color: findContentRow(`${baseKey}_color`, targetPageId)?.value || '',
    });
  });

  const buyNowIcons = {};
  getBuyNowIconEntriesForZone('menu_header_nav').forEach(([, { iconKey }]) => {
    buyNowIcons[iconKey] = findContentRow(iconKey, targetPageId)?.value || '';
  });

  return { productRows, recipeRows, productUseDynamic, buyNowIcons };
}

function snapshotNordicHeaderNavState(targetPageId) {
  const buyNowIcons = {};
  getBuyNowIconEntriesForZone('nordic_header_nav').forEach(([, { iconKey }]) => {
    buyNowIcons[iconKey] = findContentRow(iconKey, targetPageId)?.value || '';
  });
  return { buyNowIcons };
}

function snapshotTextZoneMediaState(zone, targetPageId) {
  if (!zone?.form_media_key) return { mediaValue: '' };
  return {
    mediaValue: String(findContentRow(zone.form_media_key, targetPageId)?.value || '').trim(),
  };
}

function snapshotNordicSelectedProductState(zone, targetPageId) {
  const product = zone?._nordicSelectedProduct || resolveNordicSelectedProductConfig(zone);
  if (!product) return { frontMediaValue: '', backMediaValue: '' };
  return {
    frontMediaValue: String(findContentRow(product.front_media_key, targetPageId)?.value || '').trim(),
    backMediaValue: String(findContentRow(product.back_media_key, targetPageId)?.value || '').trim(),
  };
}

async function saveInlineRecipeCategory(id, name, iconFile = null) {
  const fd = new FormData();
  fd.append('name', name);
  if (iconFile instanceof File) {
    fd.append('icon', iconFile);
  }
  if (id) {
    fd.append('_method', 'PUT');
  }

  const url = id ? `${ROUTES.menuRecipeCategoriesUpdate}/${id}` : ROUTES.menuRecipeCategoriesStore;
  const res = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'X-CSRF-TOKEN': CSRF(),
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: fd,
  });
  const data = await res.json().catch(() => ({}));

  return { ok: res.ok, data };
}

async function uploadMenuHeaderIcon(file, targetPageId) {
  if (!(file instanceof File)) return '';

  const fd = new FormData();
  fd.append('page_id', String(targetPageId));
  fd.append('section', 'recipe');
  fd.append('image', file);

  const { ok, data } = await apiFetch(ROUTES.mediaUpload, 'POST', fd);
  if (!ok || !data?.success || !data?.media?.url) {
    throw new Error('Product dropdown icon upload failed.');
  }

  try {
    return new URL(String(data.media.url), window.location.origin).pathname;
  } catch (e) {
    return String(data.media.url || '').trim();
  }
}

async function uploadProductDropdownIcon(file, targetPageId) {
  return uploadMenuHeaderIcon(file, targetPageId);
}

/** Collect all field values and upsert via content.updateField */
async function saveTextModal() {
  if (!_textZone) return;

  const fields = _textZoneFields ?? _textZone.fields ?? [];
  const hasFormMedia = Boolean(_textZone?.form_media_key && _textZone?.form_media_section);
  if (fields.length === 0 && !hasFormMedia) { closeModal('ve-modal-text'); return; }

  const saveBtn = document.getElementById('ve-text-save');
  const origHtml = saveBtn.innerHTML;
  saveBtn.disabled = true;
  saveBtn.innerHTML = `<span class="ve-spinner"></span> Saving…`;

  // Snapshot pre-save values so "Undo Last Save" can restore them
  const preSaveSnap = {};
  const targetPageId = zonePageId(_textZoneKey);
  fields.forEach(f => {
    const existing = findContentRow(f, targetPageId);
    if (existing?.value != null) preSaveSnap[f] = {
      value: existing.value,
      font: findContentRow(`${f}_font`, targetPageId)?.value || null,
      font_size: findContentRow(`${f}_font_size`, targetPageId)?.value || null,
      color: findContentRow(`${f}_color`, targetPageId)?.value || null,
    };
  });
  if (_textZoneKey === 'menu_header_nav') {
    preSaveSnap.__menu_header_nav = snapshotMenuHeaderNavState(targetPageId);
  } else if (_textZoneKey === 'nordic_header_nav') {
    preSaveSnap.__nordic_header_nav = snapshotNordicHeaderNavState(targetPageId);
  } else if (_textZoneKey === 'nordic_products_selected_product') {
    preSaveSnap.__nordic_selected_product = snapshotNordicSelectedProductState(_textZone, targetPageId);
  } else if (_textZone?.form_media_key) {
    preSaveSnap.__text_zone_media = snapshotTextZoneMediaState(_textZone, targetPageId);
  }
  if (Object.keys(preSaveSnap).length > 0) {
    _storePrevSave(_textZoneKey, preSaveSnap);
  }

  let allOk = true;
  let partialSave = false;
  const saveIssues = [];

  for (const fieldKey of fields) {
    const inputEl = document.querySelector(`[data-key="${fieldKey}"]`);
    if (!inputEl) continue;

    // Read per-field style controls
    const styleEl    = document.querySelector(`.ve-field-style[data-field-key="${fieldKey}"]`);
    const fontFamily = styleEl?.querySelector('[data-style-type="font"]')?.value || null;
    const fontSize   = styleEl?.querySelector('[data-style-type="size"]')?.value || null;
    const colorInp   = styleEl?.querySelector('.ve-field-color');
    const pickerVal  = colorInp?.value || null;

    // Always persist current picker so public pages get a *_color row (end-of-body
    // CSS reads every saved _color). Omit only when picker missing.
    const fontColor = pickerVal || null;

    const existingValue = findContentRow(fieldKey, targetPageId)?.value ?? '';
    const existingFont = findContentRow(`${fieldKey}_font`, targetPageId)?.value ?? null;
    const existingFontSize = findContentRow(`${fieldKey}_font_size`, targetPageId)?.value ?? null;
    const existingColor = findContentRow(`${fieldKey}_color`, targetPageId)?.value ?? null;
    const nextValue = inputEl.value ?? '';
    const nextFont = fontFamily || null;
    const nextFontSize = fontSize || null;
    const nextColor = fontColor || null;

    if (
      String(existingValue) === String(nextValue) &&
      String(existingFont || '') === String(nextFont || '') &&
      String(existingFontSize || '') === String(nextFontSize || '') &&
      String(existingColor || '') === String(nextColor || '')
    ) {
      continue;
    }

    const syncPageIds = syncedMenuItemNamePageIds(fieldKey, targetPageId, _textZone);
    const extraSyncPageIds = syncPageIds.filter((pageId) => Number(pageId || 0) !== Number(targetPageId || 0));

    const payload = {
      page_id:   targetPageId,
      section:   'text_content',
      base_key:  fieldKey,
      value:     nextValue,
      font:      nextFont,
      font_size: nextFontSize,
      color:     nextColor,
    };

    const { ok, data } = await apiFetch(ROUTES.contentUpdateField, 'POST', payload);
    if (!ok) {
      allOk = false;
      partialSave = true;
      saveIssues.push(apiErrorMessage(data, `Could not save ${fieldKey}.`));
      console.error('contentUpdateField failed:', data);
    } else {
      // Update local cache — text value
      const idx = ALL_CONTENTS.findIndex(r => r.key === fieldKey && Number(r.page_id || 0) === targetPageId);
      if (idx >= 0) {
        ALL_CONTENTS[idx].value = nextValue;
      } else {
        ALL_CONTENTS.push({ key: fieldKey, value: nextValue, section: 'text_content', page_id: targetPageId });
      }
      // Update local cache — color/font/size so re-opening the modal shows current values
      const updateCache = (subKey, val) => {
        const ci = ALL_CONTENTS.findIndex(r => r.key === subKey && Number(r.page_id || 0) === targetPageId);
        if (val) {
          if (ci >= 0) ALL_CONTENTS[ci].value = val;
          else ALL_CONTENTS.push({ key: subKey, value: val, section: 'text_content', page_id: targetPageId });
        } else if (ci >= 0) {
          ALL_CONTENTS.splice(ci, 1);
        }
      };
      updateCache(fieldKey + '_color',     fontColor);
      updateCache(fieldKey + '_font',      fontFamily);
      updateCache(fieldKey + '_font_size', fontSize);
      if (fontFamily) loadGoogleFont(fontFamily);

      for (const pageId of extraSyncPageIds) {
        const extraPayload = {
          page_id:   pageId,
          section:   'text_content',
          base_key:  fieldKey,
          value:     nextValue,
          font:      nextFont,
          font_size: nextFontSize,
          color:     nextColor,
        };
        const extraResult = await apiFetch(ROUTES.contentUpdateField, 'POST', extraPayload);
        if (!extraResult.ok) {
          allOk = false;
          partialSave = true;
          saveIssues.push(apiErrorMessage(extraResult.data, `Could not sync ${fieldKey}.`));
          console.error('contentUpdateField sync failed:', extraResult.data);
          break;
        }
        updateTextContentCache(fieldKey, pageId, nextValue, fontFamily, fontSize, fontColor);
      }
    }
  }

  // Optional: update product title / replace product image (menu category line item)
  const mcliId = _textZone && _textZone._mcliItemId;
  const mcliNameKey = mcliId ? `mcli_${mcliId}_name` : null;
  const mcliNameInput = mcliNameKey ? document.querySelector(`[data-key="${mcliNameKey}"]`) : null;
  const mcliFlavorSelect = document.getElementById('ve-mcli-flavor-type');
  const imgInput = document.getElementById('ve-mcli-image-input');
  const backImgInput = document.getElementById('ve-mcli-back-image-input');
  const hasImageUpdate = !!(imgInput && imgInput.files && imgInput.files[0]);
  const hasBackImageUpdate = !!(backImgInput && backImgInput.files && backImgInput.files[0]);
  const hasTitleUpdate = !!(mcliId && mcliNameInput);
  const hasFlavorUpdate = !!(mcliId && mcliFlavorSelect && !(_textZone && _textZone._mcliFlavorDisabled));
  if (mcliId && (hasTitleUpdate || hasImageUpdate || hasBackImageUpdate || hasFlavorUpdate)) {
    let productImagePrepFailed = false;
    const fd = new FormData();
    if (hasTitleUpdate) fd.append('title', mcliNameInput.value);
    if (hasFlavorUpdate) fd.append('flavor_type', normalizeMenuItemFlavorType(mcliFlavorSelect.value));
    try {
      if (hasImageUpdate) {
        await appendNormalizedImageToFormData(fd, 'image', imgInput.files[0]);
      }
      if (hasBackImageUpdate) {
        await appendNormalizedImageToFormData(fd, 'back_image', backImgInput.files[0]);
      }
    } catch (e) {
      productImagePrepFailed = true;
      allOk = false;
      partialSave = true;
      saveIssues.push(e?.message || 'Could not prepare the selected product image.');
      console.error('menu item image prepare failed', e);
    }
    if (!productImagePrepFailed) {
      fd.append('_method', 'PUT');
      const token = CSRF();
      try {
        const res = await fetch(`${ROUTES.menuCategoryItemsUpdate}/${mcliId}`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'X-CSRF-TOKEN': token,
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: fd,
        });
        const j = await res.json().catch(() => ({}));
        if (!res.ok || !j.ok) {
          allOk = false;
          partialSave = true;
          saveIssues.push(apiErrorMessage(j, 'Could not save the product images/details.'));
          console.error('menu item update failed', j);
        }
      } catch (e) {
        allOk = false;
        partialSave = true;
        saveIssues.push('Could not reach the product save endpoint.');
        console.error(e);
      }
    }
  }

  const mrId = _textZone && _textZone._mrRecipeId;
  const mrNameKey = mrId ? `mr_${mrId}_name` : null;
  const mrNameInput = mrNameKey ? document.querySelector(`[data-key="${mrNameKey}"]`) : null;
  const mrImgInput = document.getElementById('ve-mr-image-input');
  const hasRecipeImageUpdate = !!(mrImgInput && mrImgInput.files && mrImgInput.files[0]);
  const hasRecipeNameUpdate = !!(mrId && mrNameInput);
  if (mrId && (hasRecipeNameUpdate || hasRecipeImageUpdate)) {
    const fd = new FormData();
    if (hasRecipeNameUpdate) fd.append('name', mrNameInput.value);
    if (hasRecipeImageUpdate) {
      try {
        await appendNormalizedImageToFormData(fd, 'image', mrImgInput.files[0]);
      } catch (e) {
        allOk = false;
        partialSave = true;
        saveIssues.push(e?.message || 'Could not prepare the selected recipe image.');
        console.error('menu recipe image prepare failed', e);
      }
    }
    fd.append('_method', 'PUT');
    const token = CSRF();
    try {
      const res = await fetch(`${ROUTES.menuRecipesUpdate}/${mrId}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'X-CSRF-TOKEN': token,
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: fd,
      });
      const j = await res.json().catch(() => ({}));
      if (!res.ok || !j.ok) {
        allOk = false;
        partialSave = true;
        saveIssues.push(apiErrorMessage(j, 'Could not save the recipe image/details.'));
        console.error('menu recipe update failed', j);
      }
    } catch (e) {
      allOk = false;
      partialSave = true;
      saveIssues.push('Could not reach the recipe save endpoint.');
      console.error(e);
    }
  }

  if (allOk && ['menu_header_nav', 'nordic_header_nav'].includes(_textZoneKey)) {
    for (const [fieldKey, config] of getBuyNowIconEntriesForZone(_textZoneKey)) {
      const input = document.querySelector(`[data-key="${fieldKey}"]`);
      const group = input?.closest('.ve-field-group');
      if (!group) continue;

      const iconValueInput = group.querySelector('.ve-buy-now-icon-value');
      const iconFile = group.querySelector('.ve-buy-now-icon-file')?.files?.[0] || null;
      let customIcon = iconValueInput?.value?.trim() || '';

      if (iconFile) {
        try {
          customIcon = await uploadMenuHeaderIcon(iconFile, targetPageId);
          if (iconValueInput) iconValueInput.value = customIcon;
        } catch (e) {
          allOk = false;
          console.error(e);
          break;
        }
      }

      if (customIcon) {
        const result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
          page_id: targetPageId,
          section: 'text_content',
          base_key: config.iconKey,
          value: customIcon,
        });
        if (!result.ok) {
          allOk = false;
          console.error('buy now icon save failed', result.data);
          break;
        }
      } else {
        await apiFetch(ROUTES.contentDestroyByKey, 'DELETE', {
          page_id: targetPageId,
          section: 'text_content',
          key: config.iconKey,
        });
      }

      const cacheIndex = ALL_CONTENTS.findIndex((row) =>
        row.key === config.iconKey && Number(row.page_id || 0) === targetPageId
      );
      if (customIcon) {
        if (cacheIndex >= 0) ALL_CONTENTS[cacheIndex].value = customIcon;
        else ALL_CONTENTS.push({ key: config.iconKey, value: customIcon, section: 'text_content', page_id: targetPageId });
      } else if (cacheIndex >= 0) {
        ALL_CONTENTS.splice(cacheIndex, 1);
      }
    }

    if (!allOk) {
      saveBtn.disabled = false;
      saveBtn.innerHTML = origHtml;
      toast('Could not save some values. Check the console.', 'error');
      return;
    }

    const existingDynamicProductKeys = Array.from(new Set(
      ALL_CONTENTS
        .filter((row) => Number(row.page_id || 0) === targetPageId && /^menu_nav_products_item_\d+_(label|url|label_icon)(?:_(?:font|font_size|color))?$/.test(String(row.key || '')))
        .map((row) => {
          const key = String(row.key || '');
          if (/_label_(?:font|font_size|color)$/.test(key)) return key.replace(/_(font|font_size|color)$/, '');
          return key;
        })
        .filter(Boolean)
    ));

    for (const key of existingDynamicProductKeys) {
      await apiFetch(ROUTES.contentDestroyByKey, 'DELETE', {
        page_id: targetPageId,
        section: 'text_content',
        key,
      });
    }

    const staleDynamicIndexes = [];
    ALL_CONTENTS.forEach((row, index) => {
      if (Number(row.page_id || 0) !== targetPageId) return;
      if (/^menu_nav_products_item_\d+_(label|url|label_icon)(?:_(?:font|font_size|color))?$/.test(String(row.key || ''))) {
        staleDynamicIndexes.push(index);
      }
    });
    staleDynamicIndexes.reverse().forEach((index) => ALL_CONTENTS.splice(index, 1));

    const productGroups = Array.from(document.querySelectorAll('.ve-product-nav-inline-group'));
    let productIndex = 0;
    for (const group of productGroups) {
      const label = group.querySelector('.ve-product-nav-label')?.value?.trim() || '';
      const iconValueInput = group.querySelector('.ve-product-nav-icon-value');
      const iconFile = group.querySelector('.ve-product-nav-icon-file')?.files?.[0] || null;
      const font = group.querySelector('.ve-product-nav-font')?.value?.trim() || null;
      const size = group.querySelector('.ve-product-nav-size')?.value?.trim() || null;
      const color = group.querySelector('.ve-product-nav-color')?.value?.trim() || null;
      if (!label) continue;
      productIndex += 1;
      const labelKey = `menu_nav_products_item_${productIndex}_label`;
      const urlKey = `menu_nav_products_item_${productIndex}_url`;
      const iconKey = `${labelKey}_icon`;
      const slug = label
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '') || `item-${productIndex}`;
      const url = `/brands/menu/products/category/${slug}`;
      let customIcon = iconValueInput?.value?.trim() || '';

      if (iconFile) {
        try {
          customIcon = await uploadProductDropdownIcon(iconFile, targetPageId);
          if (iconValueInput) iconValueInput.value = customIcon;
        } catch (e) {
          allOk = false;
          console.error(e);
          break;
        }
      }

      let result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
        page_id: targetPageId,
        section: 'text_content',
        base_key: labelKey,
        value: label,
        font,
        font_size: size,
        color,
      });
      if (!result.ok) {
        allOk = false;
        console.error('product nav label save failed', result.data);
        break;
      }

      result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
        page_id: targetPageId,
        section: 'text_content',
        base_key: urlKey,
        value: url || '#',
      });
      if (!result.ok) {
        allOk = false;
        console.error('product nav url save failed', result.data);
        break;
      }

      if (customIcon) {
        result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
          page_id: targetPageId,
          section: 'text_content',
          base_key: iconKey,
          value: customIcon,
        });
        if (!result.ok) {
          allOk = false;
          console.error('product nav icon save failed', result.data);
          break;
        }
      } else {
        await apiFetch(ROUTES.contentDestroyByKey, 'DELETE', {
          page_id: targetPageId,
          section: 'text_content',
          key: iconKey,
        });
      }

      ALL_CONTENTS.push({ key: labelKey, value: label, section: 'text_content', page_id: targetPageId });
      ALL_CONTENTS.push({ key: urlKey, value: url || '#', section: 'text_content', page_id: targetPageId });
      if (customIcon) ALL_CONTENTS.push({ key: iconKey, value: customIcon, section: 'text_content', page_id: targetPageId });
      if (font) ALL_CONTENTS.push({ key: `${labelKey}_font`, value: font, section: 'text_content', page_id: targetPageId });
      if (size) ALL_CONTENTS.push({ key: `${labelKey}_font_size`, value: size, section: 'text_content', page_id: targetPageId });
      if (color) ALL_CONTENTS.push({ key: `${labelKey}_color`, value: color, section: 'text_content', page_id: targetPageId });
    }

    if (allOk) {
      const dynamicFlag = '1';
      const result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
        page_id: targetPageId,
        section: 'text_content',
        base_key: 'menu_nav_products_use_dynamic',
        value: dynamicFlag,
      });
      if (!result.ok) {
        allOk = false;
        console.error('product nav dynamic flag save failed', result.data);
      } else {
        const existingFlag = ALL_CONTENTS.find((row) =>
          row.key === 'menu_nav_products_use_dynamic' && Number(row.page_id || 0) === targetPageId
        );
        if (existingFlag) existingFlag.value = dynamicFlag;
        else ALL_CONTENTS.push({ key: 'menu_nav_products_use_dynamic', value: dynamicFlag, section: 'text_content', page_id: targetPageId });
      }
    }

    for (const id of Array.from(_pendingRecipeCategoryDeletes)) {
      await apiFetch(ROUTES.contentDestroyByKey, 'DELETE', {
        page_id: targetPageId,
        section: 'text_content',
        key: `menu_nav_recipe_category_${id}_label`,
      });
      const { ok, data } = await apiFetch(`${ROUTES.menuRecipeCategoriesDestroy}/${id}`, 'DELETE');
      if (!ok || !data?.ok) {
        allOk = false;
        console.error('recipe category delete failed', data);
      }
    }

    if (allOk) {
      const recipeGroups = Array.from(document.querySelectorAll('.ve-recipe-cat-inline-group'));
      for (const group of recipeGroups) {
        const id = (group.dataset.categoryId || '').trim();
        const name = group.querySelector('.ve-recipe-cat-name')?.value?.trim() || '';
        const iconFile = group.querySelector('.ve-recipe-cat-icon-file')?.files?.[0] || null;
        const font = group.querySelector('.ve-recipe-cat-font')?.value?.trim() || null;
        const size = group.querySelector('.ve-recipe-cat-size')?.value?.trim() || null;
        const color = group.querySelector('.ve-recipe-cat-color')?.value?.trim() || null;

        if (!name) {
          if (id) {
            allOk = false;
            toast('Recipe dropdown items need a name.', 'error');
            break;
          }
          continue;
        }

        let ok;
        let data;
        let savedId = id;
        ({ ok, data } = await saveInlineRecipeCategory(id, name, iconFile));
        if (!id) {
          savedId = String(data?.category?.id || '');
        }

        if (!ok || !data?.ok) {
          allOk = false;
          console.error('recipe category save failed', data);
          break;
        }

        if (savedId) {
          const currentState = getVeRecipeState();
          const categories = Array.isArray(currentState?.categories) ? currentState.categories : [];
          const existing = categories.find((category) => String(category?.id || '') === savedId);
          const nextCategory = {
            ...(existing || {}),
            ...(data?.category || {}),
          };
          if (existing) {
            Object.assign(existing, nextCategory);
          } else {
            categories.push(nextCategory);
          }
        }

        if (savedId) {
          const styleResult = await apiFetch(ROUTES.contentUpdateField, 'POST', {
            page_id: targetPageId,
            section: 'text_content',
            base_key: `menu_nav_recipe_category_${savedId}_label`,
            value: name,
            font,
            font_size: size,
            color,
          });
          if (!styleResult.ok) {
            allOk = false;
            console.error('recipe category style save failed', styleResult.data);
            break;
          }
        }
      }
    }
  }

  if (allOk && _textZone?.form_media_key && _textZone?.form_media_section) {
    const mediaGroup = document.querySelector('.ve-text-zone-media-group');
    const mediaFile = mediaGroup?.querySelector('.ve-text-zone-media-file')?.files?.[0] || null;

    if (mediaFile) {
      const form = new FormData();
      form.append('image', mediaFile);
      form.append('section', _textZone.form_media_section);
      form.append('page_id', targetPageId);

      const { ok, data } = await apiFetch(ROUTES.mediaUpload, 'POST', form);
      if (!ok || !data?.media?.id) {
        allOk = false;
        console.error('text zone media save failed', data);
      } else {
        const newRow = { ...data.media, section: _textZone.form_media_section, is_active: true };
        const mediaIndex = ALL_MEDIA.findIndex((row) =>
          row.section === newRow.section && String(row.id) === String(newRow.id)
        );
        if (mediaIndex >= 0) ALL_MEDIA[mediaIndex] = newRow;
        else ALL_MEDIA.push(newRow);

        const result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
          page_id: targetPageId,
          section: 'text_content',
          base_key: _textZone.form_media_key,
          value: String(newRow.id),
        });
        if (!result.ok) {
          allOk = false;
          console.error('text zone media key save failed', result.data);
        } else {
          const contentIndex = ALL_CONTENTS.findIndex((row) =>
            row.key === _textZone.form_media_key && Number(row.page_id || 0) === targetPageId
          );
          if (contentIndex >= 0) ALL_CONTENTS[contentIndex].value = String(newRow.id);
          else ALL_CONTENTS.push({ key: _textZone.form_media_key, value: String(newRow.id), section: 'text_content', page_id: targetPageId });
        }
      }
    }
  }

  if (allOk && _textZoneKey === 'nordic_products_selected_product' && _textZone?._nordicSelectedProduct) {
    const product = _textZone._nordicSelectedProduct;
    const mediaGroups = Array.from(document.querySelectorAll('.ve-nordic-product-media-group'));

    for (const group of mediaGroups) {
      const mediaKey = group.dataset.mediaKey || '';
      const file = group.querySelector('.ve-nordic-product-media-file')?.files?.[0] || null;
      if (!mediaKey || !file) continue;

      const form = new FormData();
      form.append('image', file);
      form.append('section', 'product');
      form.append('page_id', targetPageId);

      const { ok, data } = await apiFetch(ROUTES.mediaUpload, 'POST', form);
      if (!ok || !data?.media?.id) {
        allOk = false;
        console.error('nordic product image save failed', { targetPageId, mediaKey, data });
        toast(apiErrorMessage(data, 'Could not save this product image.'), 'error');
        break;
      }

      const newRow = { ...data.media, section: 'product', is_active: true };
      const mediaIndex = ALL_MEDIA.findIndex((row) =>
        row.section === 'product' && String(row.id) === String(newRow.id)
      );
      if (mediaIndex >= 0) ALL_MEDIA[mediaIndex] = newRow;
      else ALL_MEDIA.push(newRow);

      const result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
        page_id: targetPageId,
        section: 'text_content',
        base_key: mediaKey,
        value: String(newRow.id),
      });
      if (!result.ok) {
        allOk = false;
        console.error('nordic product media key save failed', { targetPageId, mediaKey, data: result.data });
        toast(apiErrorMessage(result.data, 'Could not connect the product image.'), 'error');
        break;
      }

      const contentIndex = ALL_CONTENTS.findIndex((row) =>
        row.key === mediaKey && Number(row.page_id || 0) === targetPageId
      );
      if (contentIndex >= 0) ALL_CONTENTS[contentIndex].value = String(newRow.id);
      else ALL_CONTENTS.push({ key: mediaKey, value: String(newRow.id), section: 'text_content', page_id: targetPageId });
    }
  }

  saveBtn.disabled = false;
  saveBtn.innerHTML = origHtml;

  if (allOk) {
    _textSaved = true;
    toast('Changes saved successfully.');
    closeModal('ve-modal-text');
    await refreshCanvas();
  } else {
    const firstIssue = saveIssues.find(Boolean);
    if (partialSave && firstIssue) {
      toast(`Saved what we could. ${firstIssue}`, 'error');
    } else {
      toast('Some fields could not be saved.', 'error');
    }
  }
}


/* ── Text modal undo helpers ─────────────────────────────────────────────── */

function _refreshTextUndoButton() {
  const btn = document.getElementById('ve-text-undo');
  if (!btn) return;
  const iconSvg = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.34"/></svg>`;
  if (_textPrevSave) {
    btn.innerHTML  = `${iconSvg} Undo Last Save`;
    btn.title      = 'Revert the live site to the values from before your last save';
    btn.classList.add('ve-btn--undo-saved');
  } else {
    btn.innerHTML  = `${iconSvg} Undo Changes`;
    btn.title      = 'Revert all fields to the values they had when you opened this modal';
    btn.classList.remove('ve-btn--undo-saved');
  }
}

async function undoTextChanges() {
  if (_textPrevSave) {
    await performTextUndoLastSave();
  } else {
    (_textZoneFields ?? _textZone?.fields ?? []).forEach(fieldKey => {
      // Revert text input
      const inputEl = document.querySelector(`[data-key="${fieldKey}"]`);
      if (inputEl) inputEl.value = _textOriginalVals[fieldKey] ?? '';

      // Revert per-field style controls
      const orig = _textOriginalFont[fieldKey];
      if (!orig) return;
      const styleEl = document.querySelector(`.ve-field-style[data-field-key="${fieldKey}"]`);
      if (!styleEl) return;
      const fontSel  = styleEl.querySelector('[data-style-type="font"]');
      const sizeSel  = styleEl.querySelector('[data-style-type="size"]');
      const colorInp = styleEl.querySelector('.ve-field-color');
      const colorVal = styleEl.querySelector('.ve-color-value');
      if (fontSel)  fontSel.value  = orig.family;
      if (sizeSel)  sizeSel.value  = orig.size;
      if (colorInp) colorInp.value = orig.color;
      if (colorVal) colorVal.textContent = normalizeHex(orig.color);
    });
    const mcliFlavorSelect = document.getElementById('ve-mcli-flavor-type');
    if (mcliFlavorSelect && !(_textZone && _textZone._mcliFlavorDisabled)) {
      mcliFlavorSelect.value = normalizeMenuItemFlavorType(_textZone?._mcliOriginalFlavorType);
    }
    toast('Changes reverted to last saved values.');
    // In-modal undo only — canvas still matches server; refresh so picker/canvas stay in sync visually
    await refreshCanvas();
    applyVeCmsFieldStyles();
  }
}

/** Drop _font / _font_size / _color rows from JS cache (matches server after undo clears them). */
function _removeTextStyleCache(fieldKey) {
  ['_color', '_font', '_font_size'].forEach(suf => {
    const subKey = fieldKey + suf;
    const ci = ALL_CONTENTS.findIndex(r => r.key === subKey);
    if (ci >= 0) ALL_CONTENTS.splice(ci, 1);
  });
}

async function performTextUndoLastSave() {
  if (!_textPrevSave || !_textZone) return;

  const undoBtn  = document.getElementById('ve-text-undo');
  const origHtml = undoBtn.innerHTML;
  undoBtn.disabled = true;
  undoBtn.innerHTML = `<span class="ve-spinner"></span> Reverting…`;

  const fields = _textZoneFields ?? _textZone.fields ?? [];
  const targetPageId = zonePageId(_textZoneKey);
  let allOk = true;

  for (const fieldKey of fields) {
    const snap = _textPrevSave[fieldKey];
    if (snap == null) continue;
    const syncPageIds = syncedMenuItemNamePageIds(fieldKey, targetPageId, _textZone);
    const extraSyncPageIds = syncPageIds.filter((pageId) => Number(pageId || 0) !== Number(targetPageId || 0));
    const payload = typeof snap === 'object' && snap !== null
      ? {
          page_id: targetPageId,
          section: 'text_content',
          base_key: fieldKey,
          value: Object.prototype.hasOwnProperty.call(snap, 'value') ? snap.value : '',
          font: Object.prototype.hasOwnProperty.call(snap, 'font') ? snap.font : null,
          font_size: Object.prototype.hasOwnProperty.call(snap, 'font_size') ? snap.font_size : null,
          color: Object.prototype.hasOwnProperty.call(snap, 'color') ? snap.color : null,
        }
      : {
          page_id: targetPageId,
          section: 'text_content',
          base_key: fieldKey,
          value: snap,
          font: null,
          font_size: null,
          color: null,
        };

    const { ok, data } = await apiFetch(ROUTES.contentUpdateField, 'POST', payload);

    if (!ok) {
      allOk = false;
      console.error('text undo last save failed for', fieldKey, data);
    } else {
      const idx = ALL_CONTENTS.findIndex(r => r.key === fieldKey && Number(r.page_id || 0) === targetPageId);
      if (idx >= 0) ALL_CONTENTS[idx].value = payload.value;
      else ALL_CONTENTS.push({ key: fieldKey, value: payload.value, section: 'text_content', page_id: targetPageId });
      _textOriginalVals[fieldKey] = payload.value;
      const updateCache = (subKey, val) => {
        const ci = ALL_CONTENTS.findIndex(r => r.key === subKey && Number(r.page_id || 0) === targetPageId);
        if (val) {
          if (ci >= 0) ALL_CONTENTS[ci].value = val;
          else ALL_CONTENTS.push({ key: subKey, value: val, section: 'text_content', page_id: targetPageId });
        } else if (ci >= 0) {
          ALL_CONTENTS.splice(ci, 1);
        }
      };
      updateCache(fieldKey + '_font', payload.font);
      updateCache(fieldKey + '_font_size', payload.font_size);
      updateCache(fieldKey + '_color', payload.color);

      for (const pageId of extraSyncPageIds) {
        const extraPayload = { ...payload, page_id: pageId };
        const extraResult = await apiFetch(ROUTES.contentUpdateField, 'POST', extraPayload);
        if (!extraResult.ok) {
          allOk = false;
          console.error('text undo sync failed for', fieldKey, extraResult.data);
          break;
        }
        updateTextContentCache(fieldKey, pageId, payload.value, payload.font, payload.font_size, payload.color);
      }
    }
  }

  const mcliId = _textZone && _textZone._mcliItemId;
  const mcliNameKey = mcliId ? `mcli_${mcliId}_name` : null;
  const mcliFlavorSelect = document.getElementById('ve-mcli-flavor-type');
  if (allOk && mcliId && ((mcliNameKey && _textPrevSave[mcliNameKey] != null) || (mcliFlavorSelect && !(_textZone && _textZone._mcliFlavorDisabled)))) {
    const fd = new FormData();
    if (mcliNameKey && _textPrevSave[mcliNameKey] != null) {
      fd.append('title', _textPrevSave[mcliNameKey]);
    }
    if (mcliFlavorSelect && !(_textZone && _textZone._mcliFlavorDisabled)) {
      fd.append('flavor_type', normalizeMenuItemFlavorType(_textZone?._mcliOriginalFlavorType));
    }
    fd.append('_method', 'PUT');
    const token = CSRF();
    try {
      const res = await fetch(`${ROUTES.menuCategoryItemsUpdate}/${mcliId}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'X-CSRF-TOKEN': token,
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: fd,
      });
      const j = await res.json().catch(() => ({}));
      if (!res.ok || !j.ok) {
        allOk = false;
        console.error('menu item undo failed', j);
      }
    } catch (e) {
      allOk = false;
      console.error(e);
    }
  }

  if (allOk && _textZoneKey === 'menu_header_nav' && _textPrevSave.__menu_header_nav) {
    const snap = _textPrevSave.__menu_header_nav;
    for (const [, { iconKey }] of getBuyNowIconEntriesForZone('menu_header_nav')) {
      const previousIcon = String(snap.buyNowIcons?.[iconKey] || '').trim();
      if (previousIcon) {
        const result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
          page_id: targetPageId,
          section: 'text_content',
          base_key: iconKey,
          value: previousIcon,
        });
        if (!result.ok) {
          allOk = false;
          console.error('buy now icon undo failed', result.data);
          break;
        }
      } else {
        await apiFetch(ROUTES.contentDestroyByKey, 'DELETE', {
          page_id: targetPageId,
          section: 'text_content',
          key: iconKey,
        });
      }

      const cacheIndex = ALL_CONTENTS.findIndex((row) =>
        row.key === iconKey && Number(row.page_id || 0) === targetPageId
      );
      if (previousIcon) {
        if (cacheIndex >= 0) ALL_CONTENTS[cacheIndex].value = previousIcon;
        else ALL_CONTENTS.push({ key: iconKey, value: previousIcon, section: 'text_content', page_id: targetPageId });
      } else if (cacheIndex >= 0) {
        ALL_CONTENTS.splice(cacheIndex, 1);
      }
    }

    if (!allOk) {
      undoBtn.innerHTML = origHtml;
      undoBtn.disabled = false;
      toast('Could not revert some values. Check the console.', 'error');
      return;
    }

    const existingDynamicProductKeys = Array.from(new Set(
      ALL_CONTENTS
        .filter((row) => Number(row.page_id || 0) === targetPageId && /^menu_nav_products_item_\d+_(label|url|label_icon)(?:_(?:font|font_size|color))?$/.test(String(row.key || '')))
        .map((row) => {
          const key = String(row.key || '');
          if (/_label_(?:font|font_size|color)$/.test(key)) return key.replace(/_(font|font_size|color)$/, '');
          return key;
        })
        .filter(Boolean)
    ));
    for (const key of existingDynamicProductKeys) {
      await apiFetch(ROUTES.contentDestroyByKey, 'DELETE', {
        page_id: targetPageId,
        section: 'text_content',
        key,
      });
    }
    const staleDynamicIndexes = [];
    ALL_CONTENTS.forEach((row, index) => {
      if (Number(row.page_id || 0) !== targetPageId) return;
      if (/^menu_nav_products_item_\d+_(label|url|label_icon)(?:_(?:font|font_size|color))?$/.test(String(row.key || ''))) {
        staleDynamicIndexes.push(index);
      }
    });
    staleDynamicIndexes.reverse().forEach((index) => ALL_CONTENTS.splice(index, 1));

    let productIndex = 0;
    for (const row of (snap.productRows || [])) {
      const label = String(row?.label || '').trim();
      if (!label) continue;
      productIndex += 1;
      const labelKey = `menu_nav_products_item_${productIndex}_label`;
      const urlKey = `menu_nav_products_item_${productIndex}_url`;
      const iconKey = `${labelKey}_icon`;
      const slug = label.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || `item-${productIndex}`;
      const url = `/brands/menu/products/category/${slug}`;

      let result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
        page_id: targetPageId,
        section: 'text_content',
        base_key: labelKey,
        value: label,
        font: row.font || null,
        font_size: row.size || null,
        color: row.color || null,
      });
      if (!result.ok) {
        allOk = false;
        console.error('product nav undo failed', result.data);
        break;
      }
      result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
        page_id: targetPageId,
        section: 'text_content',
        base_key: urlKey,
        value: url,
      });
      if (!result.ok) {
        allOk = false;
        console.error('product nav url undo failed', result.data);
        break;
      }
      if (row.icon) {
        result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
          page_id: targetPageId,
          section: 'text_content',
          base_key: iconKey,
          value: row.icon,
        });
        if (!result.ok) {
          allOk = false;
          console.error('product nav icon undo failed', result.data);
          break;
        }
      }
      ALL_CONTENTS.push({ key: labelKey, value: label, section: 'text_content', page_id: targetPageId });
      ALL_CONTENTS.push({ key: urlKey, value: url, section: 'text_content', page_id: targetPageId });
      if (row.icon) ALL_CONTENTS.push({ key: iconKey, value: row.icon, section: 'text_content', page_id: targetPageId });
      if (row.font) ALL_CONTENTS.push({ key: `${labelKey}_font`, value: row.font, section: 'text_content', page_id: targetPageId });
      if (row.size) ALL_CONTENTS.push({ key: `${labelKey}_font_size`, value: row.size, section: 'text_content', page_id: targetPageId });
      if (row.color) ALL_CONTENTS.push({ key: `${labelKey}_color`, value: row.color, section: 'text_content', page_id: targetPageId });
    }

    if (allOk) {
      await apiFetch(ROUTES.contentUpdateField, 'POST', {
        page_id: targetPageId,
        section: 'text_content',
        base_key: 'menu_nav_products_use_dynamic',
        value: snap.productUseDynamic ? '1' : '0',
      });
    }

    if (allOk) {
      for (const row of (snap.recipeRows || [])) {
        const id = String(row?.id || '').trim();
        if (!id) continue;
        const baseKey = `menu_nav_recipe_category_${id}_label`;
        const result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
          page_id: targetPageId,
          section: 'text_content',
          base_key: baseKey,
          value: row.name || '',
          font: row.font || null,
          font_size: row.size || null,
          color: row.color || null,
        });
        if (!result.ok) {
          allOk = false;
          console.error('recipe nav undo failed', result.data);
          break;
        }
      }
    }
  }

  if (allOk && _textZoneKey === 'nordic_header_nav' && _textPrevSave.__nordic_header_nav) {
    const snap = _textPrevSave.__nordic_header_nav;
    for (const [, { iconKey }] of getBuyNowIconEntriesForZone('nordic_header_nav')) {
      const previousIcon = String(snap.buyNowIcons?.[iconKey] || '').trim();
      if (previousIcon) {
        const result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
          page_id: targetPageId,
          section: 'text_content',
          base_key: iconKey,
          value: previousIcon,
        });
        if (!result.ok) {
          allOk = false;
          console.error('buy now icon undo failed', result.data);
          break;
        }
      } else {
        await apiFetch(ROUTES.contentDestroyByKey, 'DELETE', {
          page_id: targetPageId,
          section: 'text_content',
          key: iconKey,
        });
      }

      const cacheIndex = ALL_CONTENTS.findIndex((row) =>
        row.key === iconKey && Number(row.page_id || 0) === targetPageId
      );
      if (previousIcon) {
        if (cacheIndex >= 0) ALL_CONTENTS[cacheIndex].value = previousIcon;
        else ALL_CONTENTS.push({ key: iconKey, value: previousIcon, section: 'text_content', page_id: targetPageId });
      } else if (cacheIndex >= 0) {
        ALL_CONTENTS.splice(cacheIndex, 1);
      }
    }

    if (!allOk) {
      undoBtn.innerHTML = origHtml;
      undoBtn.disabled = false;
      toast('Could not revert some values. Check the console.', 'error');
      return;
    }
  }

  if (allOk && _textZone?.form_media_key && _textPrevSave.__text_zone_media) {
    const previousMediaValue = String(_textPrevSave.__text_zone_media.mediaValue || '').trim();
    if (previousMediaValue) {
      const result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
        page_id: targetPageId,
        section: 'text_content',
        base_key: _textZone.form_media_key,
        value: previousMediaValue,
      });
      if (!result.ok) {
        allOk = false;
        console.error('text zone media undo failed', result.data);
      }
    } else {
      await apiFetch(ROUTES.contentDestroyByKey, 'DELETE', {
        page_id: targetPageId,
        section: 'text_content',
        key: _textZone.form_media_key,
      });
    }

    if (allOk) {
      const cacheIndex = ALL_CONTENTS.findIndex((row) =>
        row.key === _textZone.form_media_key && Number(row.page_id || 0) === targetPageId
      );
      if (previousMediaValue) {
        if (cacheIndex >= 0) ALL_CONTENTS[cacheIndex].value = previousMediaValue;
        else ALL_CONTENTS.push({ key: _textZone.form_media_key, value: previousMediaValue, section: 'text_content', page_id: targetPageId });
      } else if (cacheIndex >= 0) {
        ALL_CONTENTS.splice(cacheIndex, 1);
      }
    } else {
      undoBtn.innerHTML = origHtml;
      undoBtn.disabled = false;
      toast('Could not revert some values. Check the console.', 'error');
      return;
    }
  }

  if (allOk && _textZoneKey === 'nordic_products_selected_product' && _textPrevSave.__nordic_selected_product) {
    const product = _textZone?._nordicSelectedProduct || resolveNordicSelectedProductConfig(_textZone);
    const snap = _textPrevSave.__nordic_selected_product;
    const mediaPairs = [
      { key: product?.front_media_key, value: String(snap.frontMediaValue || '').trim() },
      { key: product?.back_media_key, value: String(snap.backMediaValue || '').trim() },
    ];

    for (const pair of mediaPairs) {
      if (!pair.key) continue;
      if (pair.value) {
        const result = await apiFetch(ROUTES.contentUpdateField, 'POST', {
          page_id: targetPageId,
          section: 'text_content',
          base_key: pair.key,
          value: pair.value,
        });
        if (!result.ok) {
          allOk = false;
          console.error('nordic product image undo failed', result.data);
          break;
        }
      } else {
        await apiFetch(ROUTES.contentDestroyByKey, 'DELETE', {
          page_id: targetPageId,
          section: 'text_content',
          key: pair.key,
        });
      }

      const cacheIndex = ALL_CONTENTS.findIndex((row) =>
        row.key === pair.key && Number(row.page_id || 0) === targetPageId
      );
      if (pair.value) {
        if (cacheIndex >= 0) ALL_CONTENTS[cacheIndex].value = pair.value;
        else ALL_CONTENTS.push({ key: pair.key, value: pair.value, section: 'text_content', page_id: targetPageId });
      } else if (cacheIndex >= 0) {
        ALL_CONTENTS.splice(cacheIndex, 1);
      }
    }

    if (!allOk) {
      undoBtn.innerHTML = origHtml;
      undoBtn.disabled = false;
      toast('Could not revert some values. Check the console.', 'error');
      return;
    }
  }

  undoBtn.disabled = false;

  if (allOk) {
    _clearPrevSave(_textZoneKey);
    _textPrevSave = null;
    _textSaved    = true;
    _refreshTextUndoButton();
    toast('Reverted to previous saved values.');
    closeModal('ve-modal-text');
    await refreshCanvas();
  } else {
    undoBtn.innerHTML = origHtml;
    toast('Could not revert some values. Check the console.', 'error');
  }
}


/* ═══════════════════════════════════════════════════════════════════════════
   STYLE MODAL (COLOR PICKERS + GRADIENT)
   ═══════════════════════════════════════════════════════════════════════════ */

let _styleZoneKey      = null;
let _styleZone         = null;
let _styleTargetEl     = null;
let _styleOriginalVals = {};   // fieldKey → raw saved value (for in-modal undo)
let _styleSaved        = false; // true after a successful save (skip live-preview revert on close)
let _stylePrevSave     = null;  // snapshot of values BEFORE the last save (for undo-last-save)

/* ── localStorage helpers for single-level undo-after-save ─────────────── */
function _prevSaveKey(zoneKey) {
  return `ve_prev_${PAGE_ID}_${zoneKey}`;
}
function _storePrevSave(zoneKey, snap) {
  try { localStorage.setItem(_prevSaveKey(zoneKey), JSON.stringify(snap)); } catch (e) {}
}
function _loadPrevSave(zoneKey) {
  try {
    const raw = localStorage.getItem(_prevSaveKey(zoneKey));
    return raw ? JSON.parse(raw) : null;
  } catch (e) { return null; }
}
function _clearPrevSave(zoneKey) {
  try { localStorage.removeItem(_prevSaveKey(zoneKey)); } catch (e) {}
}

/* Update the undo button label + title to match current mode */
function _refreshUndoButton() {
  const btn = document.getElementById('ve-style-undo');
  if (!btn) return;
  const iconSvg = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.34"/></svg>`;
  if (_stylePrevSave) {
    btn.innerHTML  = `${iconSvg} Undo Last Save`;
    btn.title      = 'Revert the live site to the values from before your last save';
    btn.classList.add('ve-btn--undo-saved');
  } else {
    btn.innerHTML  = `${iconSvg} Undo Changes`;
    btn.title      = 'Revert all fields to the values they had when you opened this modal';
    btn.classList.remove('ve-btn--undo-saved');
  }
}

const STYLE_CSS_VAR_MAP = {
  back_to_top_bg:       '--btt-bg',
  back_to_top_fg:       '--btt-fg',
  back_to_top_hover_bg: '--btt-hover-bg',
  header_bg:            '--header-bg',
  hero_bg:              '--hero-bg',
  about_bg:             '--about-bg',
  mission_bg:           '--mission-bg',
  strengths_bg:         '--strengths-bg',
  brands_bg:            '--brands-bg',
  history_bg:           '--history-bg',
  clients_bg:           '--clients-bg',
  contact_bg:           '--contact-bg',
  footer_bg:            '--footer-bg',
};

const GRAD_DIRECTIONS = [
  { dir: 'to top',          icon: '↑', title: 'To Top' },
  { dir: 'to top right',    icon: '↗', title: 'To Top Right' },
  { dir: 'to right',        icon: '→', title: 'To Right' },
  { dir: 'to bottom right', icon: '↘', title: 'To Bottom Right' },
  { dir: 'to bottom',       icon: '↓', title: 'To Bottom' },
  { dir: 'to bottom left',  icon: '↙', title: 'To Bottom Left' },
  { dir: 'to left',         icon: '←', title: 'To Left' },
  { dir: 'to top left',     icon: '↖', title: 'To Top Left' },
];

function prettyKeyLabel(key) {
  return String(key ?? '')
    .replace(/^back_to_top_/, 'Back to top ')
    .replace(/_/g, ' ')
    .replace(/\b(bg)\b/g, 'background')
    .replace(/\b(fg)\b/g, 'text')
    .replace(/\bhover background\b/g, 'hover background')
    .replace(/\b\w/g, m => m.toUpperCase());
}

/** Convert getComputedStyle().color (rgb/rgba) to #rrggbb for <input type="color"> */
function rgbStringToHex(rgb) {
  const m = String(rgb || '').match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
  if (!m) return null;
  const toHex = n => Math.min(255, parseInt(n, 10)).toString(16).padStart(2, '0');
  return `#${toHex(m[1])}${toHex(m[2])}${toHex(m[3])}`;
}

function normalizeHex(val, fallback = '#000000') {
  const s = String(val ?? '').trim();
  if (/^#[0-9a-fA-F]{6}$/.test(s)) return s.toLowerCase();
  if (/^#[0-9a-fA-F]{3}$/.test(s)) {
    const r = s[1], g = s[2], b = s[3];
    return (`#${r}${r}${g}${g}${b}${b}`).toLowerCase();
  }
  return fallback;
}

/**
 * Parse a stored style value — either a plain hex colour or a CSS linear-gradient.
 * Returns { mode:'solid', color } or { mode:'gradient', direction, color1, color2 }.
 */
function parseStyleValue(val) {
  const s = String(val ?? '').trim();
  const m = s.match(
    /^linear-gradient\(\s*(to\s+\w+(?:\s+\w+)?)\s*,\s*(#[0-9a-fA-F]{3,6})\s*,\s*(#[0-9a-fA-F]{3,6})\s*\)$/i
  );
  if (m) {
    return { mode: 'gradient', direction: m[1].trim().toLowerCase(), color1: normalizeHex(m[2]), color2: normalizeHex(m[3]) };
  }
  return { mode: 'solid', color: normalizeHex(s, '#000000') };
}

/** Read the current committed value from a field's inputs. */
function getStyleFieldValue(key) {
  const group = document.querySelector(`[data-style-field="${key}"]`);
  if (!group) return '#000000';
  const activeMode = group.querySelector('.ve-mode-btn--active')?.dataset.mode ?? 'solid';
  if (activeMode === 'gradient') {
    const dir = group.querySelector('.ve-grad-dir-btn--active')?.dataset.dir ?? 'to bottom';
    const c1  = normalizeHex(group.querySelector('.ve-grad-c1')?.value ?? '#000000');
    const c2  = normalizeHex(group.querySelector('.ve-grad-c2')?.value ?? '#000000');
    return `linear-gradient(${dir}, ${c1}, ${c2})`;
  }
  return normalizeHex(group.querySelector('input[type="color"].ve-solid-color')?.value ?? '#000000');
}

/** Apply value (hex OR gradient string) as a CSS variable on the live canvas element. */
function applyStyleLivePreview(key, value) {
  const cssVar = STYLE_CSS_VAR_MAP[key];
  if (_styleTargetEl && cssVar) _styleTargetEl.style.setProperty(cssVar, value);
}

/** Revert all field live-preview values back to what was stored when the modal opened. */
function revertStyleLivePreview() {
  Object.entries(_styleOriginalVals).forEach(([key, raw]) => applyStyleLivePreview(key, raw));
}

/** Update the gradient swatch preview strip for a field. */
function updateGradientPreview(key) {
  const el = document.getElementById(`ve-grad-preview-${key}`);
  if (el) el.style.background = getStyleFieldValue(key);
}

/** Set all inputs for a field to match a raw value string (used by undo). */
function setStyleFieldInputs(key, raw) {
  const group = document.querySelector(`[data-style-field="${key}"]`);
  if (!group) return;
  const parsed = parseStyleValue(raw);
  const toGrad = parsed.mode === 'gradient';

  // Switch mode buttons
  group.querySelectorAll('.ve-mode-btn').forEach(b =>
    b.classList.toggle('ve-mode-btn--active', b.dataset.mode === parsed.mode)
  );
  group.querySelector('.ve-solid-panel')?.toggleAttribute('hidden', toGrad);
  group.querySelector('.ve-gradient-panel')?.toggleAttribute('hidden', !toGrad);

  if (toGrad) {
    // Direction
    group.querySelectorAll('.ve-grad-dir-btn').forEach(b =>
      b.classList.toggle('ve-grad-dir-btn--active', b.dataset.dir === parsed.direction)
    );
    // Color stops
    const c1inp = group.querySelector('.ve-grad-c1');
    const c2inp = group.querySelector('.ve-grad-c2');
    if (c1inp) { c1inp.value = parsed.color1; group.querySelector('.ve-grad-c1-val').textContent = parsed.color1; }
    if (c2inp) { c2inp.value = parsed.color2; group.querySelector('.ve-grad-c2-val').textContent = parsed.color2; }
    updateGradientPreview(key);
  } else {
    const inp = group.querySelector('input.ve-solid-color');
    const val = group.querySelector('.ve-solid-val');
    if (inp) { inp.value = parsed.color; if (val) val.textContent = parsed.color; }
  }
}

async function undoStyleChanges() {
  if (_stylePrevSave) {
    await performUndoLastSave();
  } else {
    (_styleZone?.fields ?? []).forEach(key => {
      const original = _styleOriginalVals[key];
      if (original === undefined) return;
      setStyleFieldInputs(key, original);
      applyStyleLivePreview(key, original);
    });
    toast('Changes reverted to last saved values.');
    await refreshCanvas();
    applyVeCmsFieldStyles();
  }
}

async function performUndoLastSave() {
  if (!_stylePrevSave || !_styleZone) return;

  const undoBtn  = document.getElementById('ve-style-undo');
  const origHtml = undoBtn.innerHTML;
  undoBtn.disabled = true;
  undoBtn.innerHTML = `<span class="ve-spinner"></span> Reverting…`;

  const fields = _styleZone.fields ?? [];
  let allOk = true;

  for (const fieldKey of fields) {
    const value = _stylePrevSave[fieldKey];
    if (value == null) continue;

    const { ok, data } = await apiFetch(ROUTES.contentUpdateField, 'POST', {
      page_id:   PAGE_ID,
      section:   'text_content',
      base_key:  fieldKey,
      value,
      font:      null,
      font_size: null,
    });

    if (!ok) {
      allOk = false;
      console.error('undo last save failed for', fieldKey, data);
    } else {
      const idx = ALL_CONTENTS.findIndex(r => r.key === fieldKey);
      if (idx >= 0) ALL_CONTENTS[idx].value = value;
      else ALL_CONTENTS.push({ key: fieldKey, value, section: 'text_content' });

      setStyleFieldInputs(fieldKey, value);
      applyStyleLivePreview(fieldKey, value);
      _styleOriginalVals[fieldKey] = value;
    }
  }

  undoBtn.disabled = false;

  if (allOk) {
    _clearPrevSave(_styleZoneKey);
    _stylePrevSave = null;
    _styleSaved    = true; // prevent revertStyleLivePreview on modal close
    _refreshUndoButton();
    toast('Reverted to previous saved values.');
    closeModal('ve-modal-style');
    await refreshCanvas();
    applyVeCmsFieldStyles();
  } else {
    undoBtn.innerHTML = origHtml;
    toast('Could not revert some values. Check the console.', 'error');
  }
}

function openStyleModal(zoneKey, zone) {
  _styleZoneKey      = zoneKey;
  _styleZone         = zone;
  _styleSaved        = false;
  _styleOriginalVals = {};
  _stylePrevSave     = _loadPrevSave(zoneKey); // null if no prior save to undo

  document.getElementById('ve-style-modal-title').textContent = `Edit Styles: ${zone.label}`;

  const fields    = zone.fields ?? [];
  const container = document.getElementById('ve-style-fields');
  container.innerHTML = '';

  const canvas = document.getElementById('ve-canvas');
  _styleTargetEl = canvas ? canvas.querySelector(zone.selector) : document.querySelector(zone.selector);

  const DEFAULTS = {
    back_to_top_bg:       '#4B7A08',
    back_to_top_fg:       '#ffffff',
    back_to_top_hover_bg: '#1a2e04',
    header_bg:            '#ffffff',
    hero_bg:              'transparent',
    about_bg:             '#ffffff',
    mission_bg:           '#ffffff',
    strengths_bg:         '#ffffff',
    brands_bg:            '#ffffff',
    history_bg:           'transparent',
    clients_bg:           '#ffffff',
    contact_bg:           '#79CE1B',
    footer_bg:            '#ffffff',
  };

  fields.forEach(fieldKey => {
    const existing = ALL_CONTENTS.find(r => r.key === fieldKey)?.value;
    const raw = (existing != null && existing !== '') ? existing : (DEFAULTS[fieldKey] ?? '#000000');
    _styleOriginalVals[fieldKey] = raw;

    const parsed = parseStyleValue(raw);
    const isGrad = parsed.mode === 'gradient';

    // For the solid picker seed: use color1 when we're currently in gradient mode
    const solidSeed = isGrad ? parsed.color1 : parsed.color;
    const c1Seed    = isGrad ? parsed.color1 : parsed.color;
    const c2Seed    = isGrad ? parsed.color2 : '#ffffff';
    const dirSeed   = isGrad ? parsed.direction : 'to bottom';

    const dirBtnsHtml = GRAD_DIRECTIONS.map(d => `
      <button type="button" class="ve-grad-dir-btn${d.dir === dirSeed ? ' ve-grad-dir-btn--active' : ''}"
        data-dir="${esc(d.dir)}" title="${esc(d.title)}" aria-label="${esc(d.title)}">${d.icon}</button>
    `).join('');

    const gradPreviewStyle = `linear-gradient(${esc(dirSeed)}, ${esc(c1Seed)}, ${esc(c2Seed)})`;

    const group = document.createElement('div');
    group.className = 've-field-group ve-style-field';
    group.dataset.styleField = fieldKey;

    group.innerHTML = `
      <label>${esc(prettyKeyLabel(fieldKey))}</label>

      <div class="ve-mode-toggle" role="group" aria-label="Color mode">
        <button type="button" class="ve-mode-btn${!isGrad ? ' ve-mode-btn--active' : ''}" data-mode="solid" data-key="${esc(fieldKey)}">Solid</button>
        <button type="button" class="ve-mode-btn${isGrad  ? ' ve-mode-btn--active' : ''}" data-mode="gradient" data-key="${esc(fieldKey)}">Gradient</button>
      </div>

      <div class="ve-solid-panel"${isGrad ? ' hidden' : ''}>
        <div class="ve-color-row">
          <input type="color" class="ve-color-input ve-solid-color" data-key="${esc(fieldKey)}" value="${esc(solidSeed)}" />
          <span class="ve-color-value ve-solid-val">${esc(solidSeed)}</span>
        </div>
      </div>

      <div class="ve-gradient-panel"${!isGrad ? ' hidden' : ''}>
        <div class="ve-grad-dir-wrap">
          <span class="ve-grad-sublabel">Direction</span>
          <div class="ve-grad-dir-grid">${dirBtnsHtml}</div>
        </div>
        <div class="ve-grad-colors">
          <div class="ve-grad-color-group">
            <span class="ve-grad-sublabel">From</span>
            <div class="ve-color-row">
              <input type="color" class="ve-color-input ve-grad-c1" data-key="${esc(fieldKey)}" value="${esc(c1Seed)}" />
              <span class="ve-color-value ve-grad-c1-val">${esc(c1Seed)}</span>
            </div>
          </div>
          <div class="ve-grad-color-group">
            <span class="ve-grad-sublabel">To</span>
            <div class="ve-color-row">
              <input type="color" class="ve-color-input ve-grad-c2" data-key="${esc(fieldKey)}" value="${esc(c2Seed)}" />
              <span class="ve-color-value ve-grad-c2-val">${esc(c2Seed)}</span>
            </div>
          </div>
        </div>
        <div class="ve-grad-preview" id="ve-grad-preview-${esc(fieldKey)}" style="background:${gradPreviewStyle}"></div>
      </div>
    `;
    container.appendChild(group);
  });

  // ── Wire events ───────────────────────────────────────────────────────────

  // Mode toggle
  container.querySelectorAll('.ve-mode-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const key   = btn.dataset.key;
      const group = btn.closest('[data-style-field]');
      const mode  = btn.dataset.mode;
      group.querySelectorAll('.ve-mode-btn').forEach(b => b.classList.toggle('ve-mode-btn--active', b === btn));
      group.querySelector('.ve-solid-panel')?.toggleAttribute('hidden', mode !== 'solid');
      group.querySelector('.ve-gradient-panel')?.toggleAttribute('hidden', mode !== 'gradient');
      applyStyleLivePreview(key, getStyleFieldValue(key));
      if (mode === 'gradient') updateGradientPreview(key);
    });
  });

  // Solid color picker
  container.querySelectorAll('.ve-solid-color').forEach(inp => {
    inp.addEventListener('input', () => {
      const key = inp.dataset.key;
      const val = normalizeHex(inp.value);
      const group = inp.closest('[data-style-field]');
      const out = group.querySelector('.ve-solid-val');
      if (out) out.textContent = val;
      applyStyleLivePreview(key, val);
    });
  });

  // Gradient direction buttons
  container.querySelectorAll('.ve-grad-dir-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.closest('[data-style-field]');
      group.querySelectorAll('.ve-grad-dir-btn').forEach(b => b.classList.toggle('ve-grad-dir-btn--active', b === btn));
      const key = group.dataset.styleField;
      updateGradientPreview(key);
      applyStyleLivePreview(key, getStyleFieldValue(key));
    });
  });

  // Gradient color pickers (From / To)
  container.querySelectorAll('.ve-grad-c1, .ve-grad-c2').forEach(inp => {
    inp.addEventListener('input', () => {
      const key   = inp.dataset.key;
      const val   = normalizeHex(inp.value);
      const group = inp.closest('[data-style-field]');
      const isC1  = inp.classList.contains('ve-grad-c1');
      group.querySelector(isC1 ? '.ve-grad-c1-val' : '.ve-grad-c2-val').textContent = val;
      updateGradientPreview(key);
      applyStyleLivePreview(key, getStyleFieldValue(key));
    });
  });

  _refreshUndoButton();
  openModal('ve-modal-style');
}

async function saveStyleModal() {
  if (!_styleZone) return;
  const fields = _styleZone.fields ?? [];
  if (fields.length === 0) { closeModal('ve-modal-style'); return; }

  // Snapshot the current saved values BEFORE overwriting them so the admin
  // can "Undo Last Save" from the next modal open.
  const preSaveSnap = {};
  fields.forEach(f => { preSaveSnap[f] = _styleOriginalVals[f] ?? null; });
  _storePrevSave(_styleZoneKey, preSaveSnap);

  const saveBtn  = document.getElementById('ve-style-save');
  const origHtml = saveBtn.innerHTML;
  saveBtn.disabled = true;
  saveBtn.innerHTML = `<span class="ve-spinner"></span> Saving…`;

  let allOk = true;

  for (const fieldKey of fields) {
    const value = getStyleFieldValue(fieldKey);

    const payload = {
      page_id:   PAGE_ID,
      section:   'text_content',
      base_key:  fieldKey,
      value,
      font:      null,
      font_size: null,
    };

    const { ok, data } = await apiFetch(ROUTES.contentUpdateField, 'POST', payload);
    if (!ok) {
      allOk = false;
      console.error('contentUpdateField failed:', data);
    } else {
      const idx = ALL_CONTENTS.findIndex(r => r.key === fieldKey);
      if (idx >= 0) {
        ALL_CONTENTS[idx].value = value;
      } else {
        ALL_CONTENTS.push({ key: fieldKey, value, section: 'text_content' });
      }
      // Update the undo baseline so further edits in the same session undo correctly
      _styleOriginalVals[fieldKey] = value;
    }
  }

  saveBtn.disabled = false;
  saveBtn.innerHTML = origHtml;

  if (allOk) {
    _styleSaved = true;
    toast('Style changes saved successfully.');
    await refreshCanvas();
    closeModal('ve-modal-style');
  } else {
    toast('Some style fields could not be saved. Check the console.', 'error');
  }
}


/* ═══════════════════════════════════════════════════════════════════════════
   IMAGE MODAL
   ═══════════════════════════════════════════════════════════════════════════ */

let _imageZoneKey = null;
let _imageZone    = null;
let _pendingFile  = null;

function openImageModal(zoneKey, zone) {
  _imageZoneKey = zoneKey;
  _imageZone    = zone;
  _pendingFile  = null;

  document.getElementById('ve-image-modal-title').textContent = `Edit Image: ${zone.label}`;

  // Reset upload UI
  resetDropZone();

  // Populate existing images
  renderImageGrid(zone.section);

  // Disable upload button until a file is selected
  document.getElementById('ve-image-upload-btn').disabled = true;

  // For replace-only zones that already have a managed image, hide the upload
  // section — the admin must use the Replace button on the thumbnail instead.
  // When rows = [] (first-time setup), keep the upload section visible.
  const { rows, replaceOnly } = imageGridConfig(zone.section);
  const hasManagedRows = rows.some(row => String(row?.id ?? '').trim() !== '');
  const hideUpload = replaceOnly && hasManagedRows;
  const uploadSection = document.getElementById('ve-upload-section');
  const uploadBtn     = document.getElementById('ve-image-upload-btn');
  if (uploadSection) uploadSection.hidden = hideUpload;
  if (uploadBtn)     uploadBtn.hidden     = hideUpload;

  openModal('ve-modal-image');
}

function findZoneManagedMediaRow(targetPageId, section, liveKey, formKey) {
  const liveRow = liveKey ? findContentRow(liveKey, targetPageId) : null;
  const formRow = formKey ? findContentRow(formKey, targetPageId) : null;
  const targetId = liveRow?.value
    ? String(liveRow.value)
    : (formRow?.value ? String(formRow.value) : null);

  if (!targetId) return null;
  return ALL_MEDIA.find(r => r.section === section && String(r.id) === targetId) || null;
}

function imageGridCustomRows(section) {
  const pageSchema = CMS_SCHEMA[ACTIVE_PAGE];
  const zoneSchema = pageSchema?.zones?.[_imageZoneKey];
  if (!Array.isArray(zoneSchema?.preview_selectors) || !zoneSchema.preview_selectors.length) {
    return null;
  }

  const canvas = document.getElementById('ve-canvas');
  const targetPageId = zonePageId(_imageZoneKey);
  const labels = Array.isArray(zoneSchema.preview_labels) ? zoneSchema.preview_labels : [];
  const liveKeys = Array.isArray(zoneSchema.media_keys) ? zoneSchema.media_keys : [];
  const formKeys = Array.isArray(zoneSchema.form_media_keys) ? zoneSchema.form_media_keys : [];

  const rows = zoneSchema.preview_selectors.map((selector, idx) => {
    const imgEl = canvas?.querySelector(selector);
    const managedRow = findZoneManagedMediaRow(
      targetPageId,
      section,
      liveKeys[idx] || null,
      formKeys[idx] || null
    );

    if (!imgEl && !managedRow) return null;

    return {
      id: managedRow?.id ?? '',
      section,
      is_active: true,
      original_name: labels[idx] || managedRow?.original_name || `Image ${idx + 1}`,
      filename: managedRow?.filename || '',
      preview_src: imgEl?.src || (managedRow ? mediaUrl(managedRow) : ''),
      live_key: liveKeys[idx] || '',
      replace_disabled: !managedRow?.id,
    };
  }).filter(Boolean);

  return rows.length ? rows : [];
}

/**
 * Derive a filter function for the image grid based on the active zone key.
 * History BG zones each map to a single specific carousel image (indices 3-9).
 * Showing ALL carousel images there would confuse with hero slides, so we
 * restrict the grid and hide the "Set Active" button for those zones.
 */
function imageGridConfig(section) {
  const customRows = imageGridCustomRows(section);
  if (customRows) {
    return { rows: customRows, replaceOnly: true, customRows: true };
  }

  // ── Zones with media_key → show only the one specific image ──────────────
  // media_key is the text_content key holding the stable DB row ID.
  // ALL_CONTENTS includes text_content rows, so we can look up directly.
  const pageSchema = CMS_SCHEMA[ACTIVE_PAGE];
  const zoneSchema = pageSchema?.zones?.[_imageZoneKey];
  if (zoneSchema?.media_key) {
    const contentRow = findContentRow(zoneSchema.media_key, zonePageId(_imageZoneKey));
    const targetId   = contentRow ? String(contentRow.value) : null;
    const target     = targetId
      ? ALL_MEDIA.find(r => r.section === section && String(r.id) === targetId)
      : null;
    return { rows: target ? [target] : [], replaceOnly: true, customRows: false };
  }

  // ── Carousel section (hero slides) → exclude history BG images ───────────
  // History BG images share the carousel_images table but should never appear
  // in the hero carousel modal — they have their own dedicated zones.
  let rows = ALL_MEDIA.filter(r => r.section === section);
  if (section === 'carousel') {
    const histBgIdSet = new Set(Object.values(HIST_BG_IDS).map(String));
    rows = rows.filter(r => !histBgIdSet.has(String(r.id)));
  }

  return { rows, replaceOnly: false, customRows: false };
}

function renderImageGrid(section) {
  const grid = document.getElementById('ve-image-grid');
  const { rows, replaceOnly, customRows } = imageGridConfig(section);
  const pageSchema = CMS_SCHEMA[ACTIVE_PAGE];
  const zoneSchema = pageSchema?.zones?.[_imageZoneKey];
  const allowDelete = zoneSchema?.allow_delete !== false;

  if (rows.length === 0) {
    grid.innerHTML = `<span class="ve-image-grid-empty">No managed images yet — upload one below to take control.</span>`;
    return;
  }

  grid.innerHTML = rows.map(row => `
    <div class="ve-image-thumb ${row.is_active ? 've-image-thumb--active' : ''}"
         data-id="${row.id}" data-section="${esc(row.section)}">
      <img src="${esc(customRows ? (row.preview_src || '') : mediaUrl(row))}" alt="${esc(row.original_name ?? row.filename)}" loading="lazy" />
      ${!replaceOnly && row.is_active
        ? `<span class="ve-image-active-badge">Active</span>`
        : !replaceOnly
          ? `<button class="ve-image-set-active-btn ve-image-active-badge"
               style="cursor:pointer;background:#e2e8f0;color:#334155;"
               data-id="${row.id}" data-section="${esc(row.section)}">Set active</button>`
          : ''}
      <div class="ve-image-thumb-actions">
        <button class="ve-image-replace-btn" data-id="${row.id}" data-section="${esc(row.section)}" data-live-key="${esc(row.live_key || '')}" aria-label="Replace image" title="Replace this image" ${row.replace_disabled ? 'disabled' : ''}>
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Replace
        </button>
        ${allowDelete
          ? `<button class="ve-image-delete-btn" data-id="${row.id}" data-section="${esc(row.section)}" aria-label="Delete image" title="Delete image">&#x2715;</button>`
          : ''}
      </div>
      <span class="ve-image-name">${esc(row.original_name ?? row.filename)}</span>
    </div>
  `).join('');

  // Hidden file input for replace (one shared, reused per click)
  let replaceInput = grid.querySelector('.ve-replace-file-input');
  if (!replaceInput) {
    replaceInput = document.createElement('input');
    replaceInput.type = 'file';
    replaceInput.accept = 'image/jpeg,image/png,image/webp';
    replaceInput.className = 've-replace-file-input';
    replaceInput.style.display = 'none';
    grid.appendChild(replaceInput);
  }

  // Bind replace
  grid.querySelectorAll('.ve-image-replace-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      if (btn.disabled || !btn.dataset.id) return;
      replaceInput.dataset.targetId      = btn.dataset.id;
      replaceInput.dataset.targetSection = btn.dataset.section;
      replaceInput.dataset.targetLiveKey = btn.dataset.liveKey || '';
      replaceInput.value = '';
      replaceInput.click();
    });
  });

  replaceInput.onchange = () => {
    const file = replaceInput.files[0];
    if (file) replaceImage(
      replaceInput.dataset.targetId,
      replaceInput.dataset.targetSection,
      file,
      replaceInput.dataset.targetLiveKey || ''
    );
  };

  if (!replaceOnly) {
    // Bind set-active
    grid.querySelectorAll('.ve-image-set-active-btn').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        setActiveImage(btn.dataset.id, btn.dataset.section);
      });
    });

    // Clicking the thumb itself also sets active
    grid.querySelectorAll('.ve-image-thumb').forEach(thumb => {
      thumb.addEventListener('click', () => {
        if (!thumb.classList.contains('ve-image-thumb--active')) {
          setActiveImage(thumb.dataset.id, thumb.dataset.section);
        }
      });
    });
  }

  // Bind delete
  grid.querySelectorAll('.ve-image-delete-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      deleteImage(btn.dataset.id, btn.dataset.section);
    });
  });
}

async function replaceImage(id, section, file, liveKey = '') {
  const thumb = document.querySelector(`.ve-image-thumb[data-id="${id}"]`);
  const replaceBtn = thumb ? thumb.querySelector('.ve-image-replace-btn') : null;
  if (replaceBtn) { replaceBtn.innerHTML = '<span class="ve-spinner"></span>'; replaceBtn.disabled = true; }

  const form = new FormData();
  form.append('image', file);
  form.append('section', section);
  form.append('_method', 'PUT');

  const { ok, data } = await apiFetch(`${ROUTES.mediaUpdate}/${id}`, 'POST', form);

  if (!ok) {
    toast(data?.message ?? 'Replace failed.', 'error');
    if (replaceBtn) { replaceBtn.innerHTML = 'Replace'; replaceBtn.disabled = false; }
    return;
  }

  // Update local cache
  const idx = ALL_MEDIA.findIndex(r => String(r.id) === String(id));
  if (idx >= 0) {
    ALL_MEDIA[idx].filename      = data.media.filename;
    ALL_MEDIA[idx].original_name = data.media.original_name;
  }

  if (liveKey) {
    const targetPageId = zonePageId(_imageZoneKey);
    const { ok: ck } = await apiFetch(ROUTES.contentUpdateField, 'POST', {
      page_id: targetPageId,
      section: 'text_content',
      base_key: liveKey,
      value: String(id),
    });
    if (ck) {
      const ci = ALL_CONTENTS.findIndex(r => r.key === liveKey && Number(r.page_id || 0) === targetPageId);
      if (ci >= 0) ALL_CONTENTS[ci].value = String(id);
      else ALL_CONTENTS.push({ key: liveKey, value: String(id), section: 'text_content', page_id: targetPageId });
    }
  }

  if (data.media?.filename) {
    const newFilename = data.media.filename;

    if (_imageZoneKey && _imageZoneKey.startsWith('history_bg_')) {
      // History BG: update hidden ref img + trigger JS live background swap
      const bgIdx  = parseInt(_imageZoneKey.replace('history_bg_', ''), 10);
      const newSrc = `/images/banner/${newFilename}`;
      const refEl  = document.getElementById('histBgRef' + bgIdx);
      if (refEl) refEl.src = newSrc;
      if (typeof window.setHistBgAt === 'function') window.setHistBgAt(bgIdx, newSrc);
    } else {
      // For all other single-image zones: update the canvas <img> directly
      // using the zone's CSS selector from the schema.
      const pageSchema = CMS_SCHEMA[ACTIVE_PAGE];
      const zoneSchema = pageSchema?.zones?.[_imageZoneKey];
      if (zoneSchema?.selector) {
        const canvas   = document.getElementById('ve-canvas');
        let imgEl = null;

        if (liveKey && Array.isArray(zoneSchema?.media_keys) && Array.isArray(zoneSchema?.preview_selectors)) {
          const previewIdx = zoneSchema.media_keys.findIndex((key) => String(key) === String(liveKey));
          if (previewIdx >= 0) {
            imgEl = canvas?.querySelector(zoneSchema.preview_selectors[previewIdx]);
          }
        }

        if (!imgEl) {
          const targetEl = canvas?.querySelector(zoneSchema.selector);
          imgEl = targetEl?.tagName === 'IMG'
            ? targetEl
            : targetEl?.querySelector?.('img');
        }

        if (imgEl) {
          // Map section name to subdirectory (mirrors MediaController $dirMap)
          const dirMap   = { carousel: 'banner', logo: 'logo', product: 'banner', recipe: 'icons' };
          const dir      = dirMap[section] ?? section;
          imgEl.src      = `/images/${dir}/${newFilename}`;
        }
      }
    }
  }

  renderImageGrid(section);
  toast('Image replaced successfully.');
}

async function setActiveImage(id, section) {
  const { ok, data } = await apiFetch(`${ROUTES.mediaSetActive}/${id}`, 'PATCH', { section });
  if (!ok) { toast('Could not set active image.', 'error'); return; }

  // When toggling carousel hero slides, never touch history BG entries.
  const histBgIdSet = section === 'carousel'
    ? new Set(Object.values(HIST_BG_IDS).map(String))
    : new Set();

  // Update local cache — deactivate all in same section (excluding history BGs),
  // then activate the chosen one.
  ALL_MEDIA.forEach(r => {
    if (r.section === section && !histBgIdSet.has(String(r.id))) {
      r.is_active = (String(r.id) === String(id));
    }
  });

  renderImageGrid(section);
  toast('Active image updated.');
  await refreshCanvas();
}

async function deleteImage(id, section) {
  if (!confirm('Delete this image? This cannot be undone.')) return;

  // MediaController::destroy requires ?section=... to choose the correct table + directory.
  const url = `${ROUTES.mediaDestroy}/${id}?section=${encodeURIComponent(section)}`;
  const { ok } = await apiFetch(url, 'DELETE');
  if (!ok) { toast('Could not delete image.', 'error'); return; }

  // If this zone has a media_key, clear it from text_content so the blade
  // reverts to its hardcoded fallback image on the next canvas refresh.
  const pageSchema = CMS_SCHEMA[ACTIVE_PAGE];
  const zoneSchema = pageSchema?.zones?.[_imageZoneKey];
  if (zoneSchema?.media_key) {
    const targetPageId = zonePageId(_imageZoneKey);
    await apiFetch(ROUTES.contentDestroyByKey, 'DELETE', {
      page_id: targetPageId, section: 'text_content', key: zoneSchema.media_key,
    });
    const ci = ALL_CONTENTS.findIndex(r => r.key === zoneSchema.media_key && Number(r.page_id || 0) === targetPageId);
    if (ci >= 0) ALL_CONTENTS.splice(ci, 1);
  }

  // Remove from local cache
  const idx = ALL_MEDIA.findIndex(r => String(r.id) === String(id));
  if (idx >= 0) ALL_MEDIA.splice(idx, 1);

  renderImageGrid(section);
  await refreshCanvas();
  toast('Image deleted.');
}

function resetDropZone() {
  _pendingFile = null;
  document.getElementById('ve-file-input').value = '';
  document.getElementById('ve-upload-preview').hidden = true;
  document.getElementById('ve-upload-preview-img').src = '';
  document.getElementById('ve-upload-preview-name').textContent = '';
  document.getElementById('ve-image-upload-btn').disabled = true;
}

function previewFile(file) {
  if (!file) return;
  _pendingFile = file;

  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('ve-upload-preview-img').src = e.target.result;
  };
  reader.readAsDataURL(file);

  document.getElementById('ve-upload-preview-name').textContent = file.name;
  document.getElementById('ve-upload-preview').hidden = false;
  document.getElementById('ve-image-upload-btn').disabled = false;
}

async function uploadImage() {
  if (!_pendingFile || !_imageZone) return;

  const uploadBtn = document.getElementById('ve-image-upload-btn');
  uploadBtn.disabled = true;
  uploadBtn.innerHTML = `<span class="ve-spinner"></span> Uploading…`;

  const form = new FormData();
  const targetPageId = zonePageId(_imageZoneKey);
  form.append('image', _pendingFile);
  form.append('section', _imageZone.section);
  form.append('page_id', targetPageId);

  const { ok, data } = await apiFetch(ROUTES.mediaUpload, 'POST', form);

  uploadBtn.innerHTML = 'Upload &amp; Set Active';

  if (!ok || !data?.media) {
    toast(data?.message ?? 'Upload failed.', 'error');
    uploadBtn.disabled = false;
    return;
  }

  // Add to local cache
  const newRow = { ...data.media, section: _imageZone.section, is_active: true };
  // Deactivate others in same section
  ALL_MEDIA.forEach(r => { if (r.section === _imageZone.section) r.is_active = false; });
  ALL_MEDIA.push(newRow);

  // If this zone uses a stable media_key, register the new image's ID in text_content
  // so that imageGridConfig can find it and the blade can render it by stable ID.
  const pageSchema  = CMS_SCHEMA[ACTIVE_PAGE];
  const zoneSchema  = pageSchema?.zones?.[_imageZoneKey];
  if (zoneSchema?.media_key) {
    const { ok: ck } = await apiFetch(ROUTES.contentUpdateField, 'POST', {
      page_id:  targetPageId,
      section:  'text_content',
      base_key: zoneSchema.media_key,
      value:    String(newRow.id),
    });
    if (ck) {
      const ci = ALL_CONTENTS.findIndex(r => r.key === zoneSchema.media_key && Number(r.page_id || 0) === targetPageId);
      if (ci >= 0) ALL_CONTENTS[ci].value = String(newRow.id);
      else         ALL_CONTENTS.push({ key: zoneSchema.media_key, value: String(newRow.id), section: 'text_content', page_id: targetPageId });
    }
  }

  resetDropZone();
  renderImageGrid(_imageZone.section);
  toast('Image uploaded and set as active.');
  await refreshCanvas();
}

function initDropZone() {
  const zone  = document.getElementById('ve-drop-zone');
  const input = document.getElementById('ve-file-input');

  // File picker
  input.addEventListener('change', () => {
    if (input.files[0]) previewFile(input.files[0]);
  });

  // Drag events
  zone.addEventListener('dragover', e => {
    e.preventDefault();
    zone.classList.add('ve-drag-over');
  });
  zone.addEventListener('dragleave', () => zone.classList.remove('ve-drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('ve-drag-over');
    const file = e.dataTransfer.files[0];
    if (file && /^image\/(jpeg|png|webp)$/i.test(file.type)) previewFile(file);
    else toast('Please drop a JPG, PNG or WEBP image.', 'error');
  });

  // Clear preview button
  document.getElementById('ve-upload-clear').addEventListener('click', resetDropZone);

  // Upload button
  document.getElementById('ve-image-upload-btn').addEventListener('click', uploadImage);
}


/* ═══════════════════════════════════════════════════════════════════════════
   CONTACT MODAL
   ═══════════════════════════════════════════════════════════════════════════ */

let _contactZone     = null;
let _editingContactId = null;

function openContactModal(zoneKey, zone) {
  _contactZone      = zone;
  _editingContactId = null;

  document.getElementById('ve-contact-modal-title').textContent = `Edit: ${zone.label}`;
  document.getElementById('ve-contact-label-input').value = '';
  document.getElementById('ve-contact-value-input').value = '';
  document.getElementById('ve-contact-add').textContent = 'Add';

  renderContactList(zone.contact_type);
  openModal('ve-modal-contact');
}

function renderContactList(type) {
  const list  = document.getElementById('ve-contact-list');
  const rows  = ALL_CONTACTS.filter(r => r.type === type);

  if (rows.length === 0) {
    list.innerHTML = `<span class="ve-contact-empty">No entries yet.</span>`;
    return;
  }

  list.innerHTML = rows.map(row => `
    <div class="ve-contact-row" data-id="${row.id}">
      <span class="ve-contact-row-label">${esc(row.label ?? row.type)}</span>
      <span class="ve-contact-row-value">${esc(row.value ?? row.number ?? row.email ?? row.url ?? '')}</span>
      <div class="ve-contact-row-actions">
        <button class="ve-contact-edit-btn" data-id="${row.id}">Edit</button>
        <button class="ve-contact-delete-btn" data-id="${row.id}">Delete</button>
      </div>
    </div>
  `).join('');

  list.querySelectorAll('.ve-contact-edit-btn').forEach(btn => {
    btn.addEventListener('click', () => prefillContactEdit(btn.dataset.id));
  });

  list.querySelectorAll('.ve-contact-delete-btn').forEach(btn => {
    btn.addEventListener('click', () => deleteContact(btn.dataset.id));
  });
}

function prefillContactEdit(id) {
  const row = ALL_CONTACTS.find(r => String(r.id) === String(id));
  if (!row) return;

  _editingContactId = id;
  document.getElementById('ve-contact-label-input').value = row.label ?? '';
  document.getElementById('ve-contact-value-input').value =
    row.value ?? row.number ?? row.email ?? row.url ?? '';
  document.getElementById('ve-contact-add').textContent = 'Update';
  document.getElementById('ve-contact-label-input').focus();
}

async function saveContact() {
  if (!_contactZone) return;

  const labelEl = document.getElementById('ve-contact-label-input');
  const valueEl = document.getElementById('ve-contact-value-input');
  const label   = labelEl.value.trim();
  const value   = valueEl.value.trim();

  if (!label || !value) {
    toast('Both label and value are required.', 'error');
    return;
  }

  const addBtn   = document.getElementById('ve-contact-add');
  const origText = addBtn.textContent;
  addBtn.disabled = true;
  addBtn.innerHTML = `<span class="ve-spinner"></span>`;

  let ok, data;

  if (_editingContactId) {
    // Update existing
    ({ ok, data } = await apiFetch(`${ROUTES.contactUpdate}/${_editingContactId}`, 'PUT', { label, value }));
    if (ok) {
      const idx = ALL_CONTACTS.findIndex(r => String(r.id) === String(_editingContactId));
      if (idx >= 0) {
        ALL_CONTACTS[idx].label = label;
        // Normalise the value field name across all three contact tables
        const row = ALL_CONTACTS[idx];
        if ('number' in row)  row.number = value;
        else if ('email' in row) row.email  = value;
        else if ('url'   in row) row.url    = value;
        else                     row.value  = value;
      }
    }
  } else {
    // Create new
    ({ ok, data } = await apiFetch(ROUTES.contactSave, 'POST', {
      type:  _contactZone.contact_type,
      label,
      value,
    }));
    if (ok && data?.contact) {
      ALL_CONTACTS.push({ ...data.contact, type: _contactZone.contact_type });
    }
  }

  addBtn.disabled = false;
  addBtn.textContent = origText;

  if (ok) {
    const wasEditing = !!_editingContactId;
    _editingContactId = null;
    labelEl.value = '';
    valueEl.value = '';
    addBtn.textContent = 'Add';
    renderContactList(_contactZone.contact_type);
    toast(wasEditing ? 'Entry updated.' : 'Entry saved.');
    await refreshCanvas();
  } else {
    toast('Could not save entry. Check the console.', 'error');
    console.error(data);
  }
}

async function deleteContact(id) {
  if (!confirm('Delete this entry?')) return;

  const { ok } = await apiFetch(`${ROUTES.contactDestroy}/${id}`, 'DELETE');
  if (!ok) { toast('Could not delete entry.', 'error'); return; }

  const idx = ALL_CONTACTS.findIndex(r => String(r.id) === String(id));
  if (idx >= 0) ALL_CONTACTS.splice(idx, 1);

  // If we were editing this row, clear the form
  if (String(_editingContactId) === String(id)) {
    _editingContactId = null;
    document.getElementById('ve-contact-label-input').value = '';
    document.getElementById('ve-contact-value-input').value = '';
    document.getElementById('ve-contact-add').textContent = 'Add';
  }

  renderContactList(_contactZone.contact_type);
  toast('Entry deleted.');
  await refreshCanvas();
}


/* ═══════════════════════════════════════════════════════════════════════════
   WIRING STATIC EVENT LISTENERS
   ═══════════════════════════════════════════════════════════════════════════ */

function initStaticListeners() {
  // Text modal — save button
  document.getElementById('ve-text-save')
    .addEventListener('click', saveTextModal);

  // Text modal — undo button
  document.getElementById('ve-text-undo')
    .addEventListener('click', undoTextChanges);

  // Style modal — save button
  document.getElementById('ve-style-save')
    .addEventListener('click', saveStyleModal);

  // Style modal — undo button
  document.getElementById('ve-style-undo')
    .addEventListener('click', undoStyleChanges);

  // Style modal — cancel button (revert + close handled inside closeModal)
  document.getElementById('ve-style-cancel')
    .addEventListener('click', () => closeModal('ve-modal-style'));

  // Per-field color picker — live hex display update (event delegation)
  document.getElementById('ve-text-fields').addEventListener('input', e => {
    if (e.target.classList.contains('ve-field-color')) {
      const span = e.target.parentElement?.querySelector('.ve-color-value');
      if (span) span.textContent = normalizeHex(e.target.value);
    }
  });

  // Per-field font select — load Google Font on change (event delegation)
  document.getElementById('ve-text-fields').addEventListener('change', e => {
    if (e.target.dataset.styleType === 'font') {
      loadGoogleFont(e.target.value);
    }
  });

  // Contact modal — add / update button
  document.getElementById('ve-contact-add')
    .addEventListener('click', saveContact);

  // Allow pressing Enter in contact inputs to trigger save
  ['ve-contact-label-input', 've-contact-value-input'].forEach(id => {
    document.getElementById(id).addEventListener('keydown', e => {
      if (e.key === 'Enter') { e.preventDefault(); saveContact(); }
    });
  });
}


/* ═══════════════════════════════════════════════════════════════════════════
   BOOTSTRAP
   ═══════════════════════════════════════════════════════════════════════════ */

function initMenuCategoryVeFontSelects() {
  const fontSel = document.getElementById('ve-menu-item-font');
  const sizeSel = document.getElementById('ve-menu-item-size');
  if (!fontSel || fontSel.options.length) return;
  fontSel.innerHTML = FIELD_FONT_LIST.map(f => `<option value="${esc(f)}">${esc(f) || '— font —'}</option>`).join('');
  sizeSel.innerHTML = FIELD_SIZE_LIST.map(s => `<option value="${esc(s)}">${esc(s) || '— size —'}</option>`).join('');
  const menuModal = document.getElementById('ve-modal-menu-item');
  if (menuModal) {
    menuModal.querySelectorAll('.ve-field-style').forEach((wrap) => {
      const btn = wrap.querySelector('.ve-field-style-toggle');
      const body = wrap.querySelector('.ve-field-style-body');
      if (!btn || !body || btn.dataset.veStyleBound) return;
      btn.dataset.veStyleBound = '1';
      if (body.hasAttribute('hidden')) body.removeAttribute('hidden');
      btn.addEventListener('click', () => {
        const open = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', open ? 'false' : 'true');
        body.hidden = open;
        wrap.classList.toggle('is-open', !open);
      });
    });
  }
}

function initMenuCategoryVe() {
  initMenuCategoryVeFontSelects();
  syncMenuItemFlavorField('', 'unflavored');
  const colorInp = document.getElementById('ve-menu-item-color');
  const colorVal = document.getElementById('ve-menu-item-color-val');
  if (colorInp && colorVal) {
    colorInp.addEventListener('input', () => { colorVal.textContent = normalizeHex(colorInp.value); });
  }

  const canvas = document.getElementById('ve-canvas');
  if (!canvas) return;
  canvas.addEventListener('click', async (e) => {
    const moveBtn = e.target.closest('.ve-menu-item-move');
    if (moveBtn) {
      e.preventDefault();
      e.stopPropagation();
      const id = moveBtn.getAttribute('data-id');
      const direction = moveBtn.getAttribute('data-direction');
      if (!id || !direction) return;
      try {
        const { ok, data } = await apiFetch(`${ROUTES.menuCategoryItemsMove}/${id}/move`, 'POST', {
          direction,
        });
        if (!ok || !data?.ok) {
          toast(apiErrorMessage(data, 'Reorder failed'), 'error');
          return;
        }
        if (data?.moved === false) {
          toast('Product is already at the edge of this group');
          return;
        }
        toast('Product order updated');
        await refreshCanvas();
      } catch (err) {
        console.warn(err);
        toast('Reorder failed', 'error');
      }
      return;
    }
    const addBtn = e.target.closest('.ve-menu-add-product');
    if (addBtn) {
      e.preventDefault();
      e.stopPropagation();
      const grid = addBtn.closest('.menu-category-grid');
      const slug = grid && grid.dataset ? grid.dataset.categorySlug : null;
      const defaultFlavor = addBtn.getAttribute('data-default-flavor') || 'unflavored';
      if (!slug) return;
      const cat = document.getElementById('ve-menu-item-category-slug');
      const modal = document.getElementById('ve-modal-menu-item');
      if (cat) cat.value = slug;
      const nameEl = document.getElementById('ve-menu-item-name-input');
      const fi = document.getElementById('ve-menu-item-image-input');
      const bi = document.getElementById('ve-menu-item-back-image-input');
      const shopeeMallUrlInput = document.getElementById('ve-menu-item-shopee-mall-url');
      const fontSel = document.getElementById('ve-menu-item-font');
      const sizeSel = document.getElementById('ve-menu-item-size');
      if (nameEl) nameEl.value = '';
      if (fi) fi.value = '';
      if (bi) bi.value = '';
      if (shopeeMallUrlInput) shopeeMallUrlInput.value = '';
      if (fontSel) fontSel.value = '';
      if (sizeSel) sizeSel.value = '';
      syncMenuItemFlavorField(slug, defaultFlavor);
      if (colorInp) colorInp.value = '#111111';
      if (colorVal) colorVal.textContent = '#111111';
      if (modal) modal.hidden = false;
      return;
    }
    const delBtn = e.target.closest('.ve-menu-item-del');
    if (delBtn) {
      e.preventDefault();
      e.stopPropagation();
      const id = delBtn.getAttribute('data-id');
      if (!id || !window.confirm('Delete this product?')) return;
      const token = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
      try {
        await fetch(`${ROUTES.menuCategoryItemsDestroy}/${id}?page_id=${encodeURIComponent(PAGE_ID)}`, {
          method: 'DELETE',
          credentials: 'same-origin',
          headers: {
            'X-CSRF-TOKEN': token,
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        toast('Deleted');
        await refreshCanvas();
      } catch (err) {
        console.warn(err);
        toast('Delete failed', 'error');
      }
    }
  });
  const saveBtn = document.getElementById('ve-menu-item-save');
  if (saveBtn && !saveBtn.dataset.menuCatBound) {
    saveBtn.dataset.menuCatBound = '1';
    saveBtn.addEventListener('click', async () => {
      const form = document.getElementById('ve-menu-item-form');
      const modal = document.getElementById('ve-modal-menu-item');
      const nameEl = document.getElementById('ve-menu-item-name-input');
      const frontImageInput = document.getElementById('ve-menu-item-image-input');
      const backImageInput = document.getElementById('ve-menu-item-back-image-input');
      if (!form || !nameEl || !String(nameEl.value || '').trim()) {
        toast('Enter a product name', 'error');
        return;
      }
      const fd = new FormData(form);
      const token = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
      const fontSel = document.getElementById('ve-menu-item-font');
      const sizeSel = document.getElementById('ve-menu-item-size');
      const colorInp2 = document.getElementById('ve-menu-item-color');
      const shopeeMallUrlInput = document.getElementById('ve-menu-item-shopee-mall-url');
      const categorySlug = document.getElementById('ve-menu-item-category-slug')?.value || '';
      const flavorSelect = document.getElementById('ve-menu-item-flavor-type');
      if (isJellyMixesCategory(categorySlug) && flavorSelect && !flavorSelect.value) {
        flavorSelect.value = 'unflavored';
      }
      try {
        if (frontImageInput?.files?.[0]) {
          await appendNormalizedImageToFormData(fd, 'image', frontImageInput.files[0]);
        }
        if (backImageInput?.files?.[0]) {
          await appendNormalizedImageToFormData(fd, 'back_image', backImageInput.files[0]);
        }
        const res = await fetch(ROUTES.menuCategoryItemsStore, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'X-CSRF-TOKEN': token,
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: fd,
        });
        const j = await res.json().catch(() => ({}));
        if (!res.ok || !j.ok) {
          toast(apiErrorMessage(j, 'Could not save the product. If the images are very large, try a smaller file.'), 'error');
          return;
        }
        const id = j.item && j.item.id;
        if (id) {
          const baseKey = `mcli_${id}_name`;
          const fontFamily = (fontSel && fontSel.value) || null;
          const fontSize = (sizeSel && sizeSel.value) || null;
          const fontColor = (colorInp2 && colorInp2.value) || null;
          if (fontFamily) loadGoogleFont(fontFamily);
          const payload = {
            page_id: PAGE_ID,
            section: 'text_content',
            base_key: baseKey,
            value: nameEl.value.trim(),
            font: fontFamily || null,
            font_size: fontSize || null,
            color: fontColor || null,
          };
          const { ok } = await apiFetch(ROUTES.contentUpdateField, 'POST', payload);
          if (ok) {
            const upd = (k, v) => {
              const i = ALL_CONTENTS.findIndex(r => r.key === k);
              if (v) {
                if (i >= 0) ALL_CONTENTS[i].value = v;
                else ALL_CONTENTS.push({ key: k, value: v, section: 'text_content' });
              }
            };
            upd(baseKey, payload.value);
            if (fontColor) upd(baseKey + '_color', fontColor);
            if (fontFamily) upd(baseKey + '_font', fontFamily);
            if (fontSize) upd(baseKey + '_font_size', fontSize);
          }

          const shopeeMallUrl = String(shopeeMallUrlInput?.value || '').trim();
          if (shopeeMallUrl) {
            const shopeeMallKey = `menu_item_${id}_shopee_mall_url`;
            const menuPageId = Number(PAGE_IDS?.menu || PAGE_ID);
            const mallPayload = {
              page_id: menuPageId,
              section: 'text_content',
              base_key: shopeeMallKey,
              value: shopeeMallUrl,
            };
            const mallResult = await apiFetch(ROUTES.contentUpdateField, 'POST', mallPayload);
            if (mallResult.ok) {
              const existingIndex = ALL_CONTENTS.findIndex((row) =>
                row.key === shopeeMallKey && Number(row.page_id || 0) === menuPageId
              );
              if (existingIndex >= 0) {
                ALL_CONTENTS[existingIndex].value = shopeeMallUrl;
              } else {
                ALL_CONTENTS.push({
                  key: shopeeMallKey,
                  value: shopeeMallUrl,
                  section: 'text_content',
                  page_id: menuPageId,
                });
              }
            }
          }
        }
        if (modal) modal.hidden = true;
        toast('Product added');
        applyVeCmsFieldStyles();
        await refreshCanvas();
      } catch (err) {
        console.warn(err);
        toast('Save failed', 'error');
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', () => {
  normalizeMenuScrollContext();
  initModalDismiss();
  initStaticListeners();
  initDropZone();
  initPencilPointerGuard();
  initCanvasLinkGuard();
  initMenuCategoryVe();
  initMenuRecipesVe();
  initNordicRecipesVe();
  hoistCanvasStyles(document);
  applyVeCmsFieldStyles();
  buildOverlays();
});

