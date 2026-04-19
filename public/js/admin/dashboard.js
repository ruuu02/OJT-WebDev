const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// ── Section → physical directory mapping ─────────────────────────────
// DB section keys do not match the actual public/images/ subdirectory names.
const SECTION_DIR = {
    carousel: 'banner',
    logo:     'logo',
    product:  'banner',
    recipe:   'icons',
};
function mediaUrl(section, filename) {
    const dir = SECTION_DIR[section] ?? section;
    return `/images/${dir}/${filename}`;
}

// ── Stable history background IDs (shared with visual editor) ────────
// Used to hide history BG entries from the generic "carousel" bucket in
// the classic dashboard, so only the 3 hero slides are shown there.
const HIST_BG_ID_SET = (() => {
    try {
        if (!Array.isArray(ALL_CONTENTS)) return new Set();
        const ids = [];
        for (const row of ALL_CONTENTS) {
            if (!row || !row.key) continue;
            if (row.key.startsWith('history_bg_id_') || row.key.startsWith('media_id_history_bg_')) {
                ids.push(String(row.value));
            }
        }
        return new Set(ids);
    } catch (e) {
        return new Set();
    }
})();

// ── Slot-key constants (mirror config/cms.php media_key values) ────────
// Used by upload and delete helpers to keep text_content in sync with
// whichever tool (Visual Editor or Classic Dashboard) modifies an image.
const LOGO_SLOT_KEYS = {
    ULTRAFOOD: 'media_id_header_logo',
    MENU:      'media_id_brand_menu_logo',
    NORDIC:    'media_id_brand_nordic_logo',
};
const PRODUCT_SLOT_KEYS = ['media_id_about_image', 'media_id_clients_map', 'media_id_clients_legend'];
const RECIPE_SLOT_KEYS  = [
    'media_id_mission_icon',    'media_id_vision_icon',
    'media_id_strength_1_icon', 'media_id_strength_2_icon',
    'media_id_strength_3_icon', 'media_id_strength_4_icon',
];

// ── Current selection state ───────────────────────────────────────────
let current = { section: null, pageId: null, pageTitle: null };

// Image sections (use upload modal)
const IMAGE_SECTIONS = ['carousel', 'logo', 'product', 'recipe'];
// Text/content sections
const TEXT_SECTIONS = ['text_content', 'product_details', 'featured_recipes'];
// Contact sections
const CONTACT_SECTIONS = ['phone', 'email', 'link'];

// ── Section → Field mapping for guided text entry ─────────────────────
const CONTENT_SECTIONS = [
    { key: 'header', label: 'Header & Navigation', fields: [
        { key: 'header_site_title',    label: 'Site Title (browser tab)' },
        { key: 'header_nav_font',      label: 'Nav link font (all links)' },
        { key: 'header_nav_font_size',  label: 'Nav link font size (all links)' },
        { key: 'header_nav_about',     label: 'Nav link: About Us' },
        { key: 'header_nav_purpose',   label: 'Nav link: Purpose' },
        { key: 'header_nav_strengths', label: 'Nav link: Strengths' },
        { key: 'header_nav_brands',    label: 'Nav link: Brands' },
        { key: 'header_nav_history',   label: 'Nav link: History' },
        { key: 'header_nav_contact',    label: 'Nav link: Contact' },
    ]},
    { key: 'about', label: 'About Us', fields: [
        { key: 'about_heading',  label: 'Heading' },
        { key: 'about_desc_1',   label: 'Description – Paragraph 1' },
        { key: 'about_desc_2',   label: 'Description – Paragraph 2' },
    ]},
    { key: 'vision', label: 'Vision', fields: [
        { key: 'vision_text', label: 'Vision Text' },
    ]},
    { key: 'mission', label: 'Mission', fields: [
        { key: 'mission_text', label: 'Mission Text' },
    ]},
    { key: 'strengths', label: 'Strengths', fields: [
        { key: 'strengths_heading',  label: 'Section Heading' },
        { key: 'strength_1_title',   label: 'Card 1 – Title' },
        { key: 'strength_1_desc',    label: 'Card 1 – Description' },
        { key: 'strength_2_title',   label: 'Card 2 – Title' },
        { key: 'strength_2_desc',    label: 'Card 2 – Description' },
        { key: 'strength_3_title',   label: 'Card 3 – Title' },
        { key: 'strength_3_desc',    label: 'Card 3 – Description' },
        { key: 'strength_4_title',   label: 'Card 4 – Title' },
        { key: 'strength_4_desc',    label: 'Card 4 – Description' },
        { key: 'strength_5_title',   label: 'Card 5 – Title' },
        { key: 'strength_5_desc',    label: 'Card 5 – Description' },
        { key: 'strength_6_title',   label: 'Card 6 – Title' },
        { key: 'strength_6_desc',    label: 'Card 6 – Description' },
    ]},
    { key: 'brands', label: 'Brands', fields: [
        { key: 'brands_heading',    label: 'Section Heading' },
        { key: 'brands_subheading', label: 'Section Subheading (italic)' },
        { key: 'brand_1_name',      label: 'Brand 1 – Name' },
        { key: 'brand_1_desc',      label: 'Brand 1 – Description' },
        { key: 'brand_2_name',      label: 'Brand 2 – Name' },
        { key: 'brand_2_desc',      label: 'Brand 2 – Description' },
    ]},
    { key: 'history', label: 'History', fields: [
        { key: 'history_heading', label: 'Section Heading' },
        { key: 'history_bg_url',  label: 'Background Image URL' },
        { key: 'history_1984', label: '1984 — Title & Description' },
        { key: 'history_1987', label: '1987 — Title & Description' },
        { key: 'history_1990', label: '1990 — Title & Description' },
        { key: 'history_1995', label: '1995 — Title & Description' },
        { key: 'history_1998', label: '1998 — Title & Description' },
        { key: 'history_2004', label: '2004 — Title & Description' },
        { key: 'history_2005', label: '2005 — Title & Description' },
        { key: 'history_2006', label: '2006 — Title & Description' },
        { key: 'history_2008', label: '2008 — Title & Description' },
        { key: 'history_2013', label: '2013 — Title & Description' },
        { key: 'history_2015', label: '2015 — Title & Description' },
        { key: 'history_2017', label: '2017 — Title & Description' },
        { key: 'history_2020', label: '2020 — Title & Description' },
        { key: 'history_2022', label: '2022 — Title & Description' },
        { key: 'history_2024', label: '2024 — Title & Description' },
    ]},
    { key: 'contact', label: 'Contact', fields: [
        { key: 'contact_heading', label: 'Section Heading' },
        { key: 'contact_subtext', label: 'Subtext / Tagline' },
        { key: 'map_embed_src',   label: 'Google Maps Embed URL' },
    ]},
    { key: 'footer', label: 'Footer', fields: [
        { key: 'footer_tagline',   label: 'Tagline' },
        { key: 'footer_copyright', label: 'Copyright Text' },
        { key: 'footer_crafted',   label: '"Crafted with…" Text' },
    ]},
];

// ── Google Fonts — free, open source (fonts.google.com) ──────────────
// No API key needed to load via CDN; curated list of popular families.
const FONT_OPTIONS = [
    // Page defaults (currently active on the user-facing page)
    { name: 'ZCOOL XiaoWei',       category: 'Page Default' },
    { name: 'Spline Sans',         category: 'Page Default' },
    // Serif
    { name: 'Playfair Display',    category: 'Serif' },
    { name: 'Merriweather',        category: 'Serif' },
    { name: 'Lora',                category: 'Serif' },
    { name: 'EB Garamond',         category: 'Serif' },
    { name: 'Cormorant Garamond',  category: 'Serif' },
    { name: 'Cinzel',              category: 'Serif' },
    { name: 'Libre Baskerville',   category: 'Serif' },
    // Sans-serif
    { name: 'DM Sans',             category: 'Sans-serif' },
    { name: 'Inter',               category: 'Sans-serif' },
    { name: 'Poppins',             category: 'Sans-serif' },
    { name: 'Nunito',              category: 'Sans-serif' },
    { name: 'Raleway',             category: 'Sans-serif' },
    { name: 'Montserrat',          category: 'Sans-serif' },
    { name: 'Lato',                category: 'Sans-serif' },
    { name: 'Open Sans',           category: 'Sans-serif' },
    { name: 'Roboto',              category: 'Sans-serif' },
    { name: 'Source Sans 3',       category: 'Sans-serif' },
    { name: 'Noto Sans',           category: 'Sans-serif' },
    // Display
    { name: 'Oswald',              category: 'Display' },
    { name: 'Bebas Neue',          category: 'Display' },
    { name: 'Abril Fatface',       category: 'Display' },
    { name: 'Righteous',           category: 'Display' },
    // Monospace
    { name: 'DM Mono',             category: 'Monospace' },
    { name: 'Space Mono',          category: 'Monospace' },
    { name: 'Fira Code',           category: 'Monospace' },
];

// Tracks which fonts have already been injected into <head>
const _loadedFonts = new Set();

// ── History year helpers ───────────────────────────────────────────────
// Returns true for keys like 'history_1984' (year-level, not _title/_desc)
function isHistoryYear(key) {
    return /^history_\d{4}$/.test(key);
}

// Returns the year base key ('history_1984') if the key is a history sub-field,
// otherwise returns null.
function getHistoryYearBase(key) {
    const m = key.match(/^(history_\d{4})_(title|desc)$/);
    return m ? m[1] : null;
}

// Show/hide the dual history inputs vs the single value textarea
function toggleHistoryYearUI(show) {
    document.getElementById('historyYearInputs').classList.toggle('hidden', !show);
    document.getElementById('singleValueWrap').classList.toggle('hidden', show);
    if (!show) {
        document.getElementById('historyTitle').value = '';
        document.getElementById('historyDesc').value  = '';
    }
}

function loadGoogleFont(fontName) {
    if (!fontName || _loadedFonts.has(fontName)) return;
    _loadedFonts.add(fontName);
    const id  = 'gf-' + fontName.replace(/\s+/g, '-').toLowerCase();
    if (document.getElementById(id)) return;
    const slug = fontName.replace(/\s+/g, '+');
    const link = document.createElement('link');
    link.id   = id;
    link.rel  = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${slug}:ital,wght@0,300;0,400;0,600;0,700;0,900;1,400&display=swap`;
    document.head.appendChild(link);
}

// ── Dropdown ─────────────────────────────────────────────────────────
function toggleDropdown() {
    document.getElementById('dropdownMenu').classList.toggle('open');
    document.getElementById('chevron').classList.toggle('open');
}
document.addEventListener('click', function (e) {
    if (!e.target.closest('.dropdown')) {
        document.getElementById('dropdownMenu').classList.remove('open');
        document.getElementById('chevron').classList.remove('open');
    }
});

// ── Left panel row selection ──────────────────────────────────────────
function selectRow(el, section, pageId, pageTitle) {
    document.querySelectorAll('.section-row').forEach(r => r.classList.remove('active'));
    el.classList.add('active');

    current = { section, pageId, pageTitle };

    hideAllPanels();

    if (IMAGE_SECTIONS.includes(section)) {
        showImagePanel(section, pageId, pageTitle);
    } else if (TEXT_SECTIONS.includes(section)) {
        showTextPanel(section, pageId, pageTitle);
    } else if (CONTACT_SECTIONS.includes(section)) {
        showContactPanel(section, pageId, pageTitle);
    }
}

function hideAllPanels() {
    document.getElementById('emptyState').classList.add('hidden');
    document.getElementById('imagePanel').classList.add('hidden');
    document.getElementById('textPanel').classList.add('hidden');
    document.getElementById('contactPanel').classList.add('hidden');
}

// ── Image Panel ───────────────────────────────────────────────────────
function showImagePanel(section, pageId, pageTitle) {
    document.getElementById('imgSectionLabel').textContent = section.replace(/_/g, ' ').toUpperCase();
    document.getElementById('imgPageTitle').textContent = pageTitle;
    document.getElementById('imagePanel').classList.remove('hidden');

    // Clear upload state
    document.getElementById('imgPreviewArea').classList.add('hidden');
    document.getElementById('imgDropZone').classList.remove('hidden');
    document.getElementById('imgFileInput').value = '';
    document.getElementById('uploadMsg').classList.add('hidden');

    renderExistingImages(section, pageId);
}

function renderExistingImages(section, pageId) {
    const container = document.getElementById('existingImages');
    let items = ALL_MEDIA.filter(m => m.section === section && m.page_id == pageId);

    // For the generic "carousel" section, exclude history background entries.
    // Those are managed via the visual editor's dedicated History BG zones.
    if (section === 'carousel' && HIST_BG_ID_SET.size) {
        items = items.filter(m => !HIST_BG_ID_SET.has(String(m.id)));
    }

    // For LOGO cards (Ultrafood header logo, Menu brand logo, Nordic brand logo),
    // show only the specific image bound to that card, using the same stable
    // media_id_* keys the visual editor uses, instead of dumping all logos.
    if (section === 'logo') {
        const page = Array.isArray(PAGES) ? PAGES.find(p => p.id == pageId) : null;
        const title = (page && page.title ? String(page.title) : '').toUpperCase();
        let mediaKey = null;

        if (title.includes('ULTRAFOOD')) {
            mediaKey = 'media_id_header_logo';
        } else if (title.includes('MENU')) {
            mediaKey = 'media_id_brand_menu_logo';
        } else if (title.includes('KORPALA') || title.includes('NORDIC')) {
            mediaKey = 'media_id_brand_nordic_logo';
        }

        if (mediaKey) {
            const row = Array.isArray(ALL_CONTENTS)
                ? ALL_CONTENTS.find(r => r.section === 'text_content' && r.page_id == pageId && r.key === mediaKey)
                : null;
            const targetId = row ? String(row.value) : null;
            items = targetId
                ? ALL_MEDIA.filter(m => m.section === 'logo' && String(m.id) === targetId)
                : [];
        }
    }

    // Product images: show only slot-bound images (About, Clients Map, Clients Legend)
    // so we don't show duplicates or mix unrelated images.
    if (section === 'product') {
        const productMediaKeys = ['media_id_about_image', 'media_id_clients_map', 'media_id_clients_legend'];
        const ids = (Array.isArray(ALL_CONTENTS) ? ALL_CONTENTS : [])
            .filter(r => r.section === 'text_content' && r.page_id == pageId && productMediaKeys.includes(r.key))
            .map(r => String(r.value));
        const idSet = new Set(ids);
        items = items.filter(m => idSet.has(String(m.id)));
        // Deduplicate by id (keep first) so each slot appears once
        const seen = new Set();
        items = items.filter(m => {
            if (seen.has(String(m.id))) return false;
            seen.add(String(m.id));
            return true;
        });
    }

    // Recipe images: show only slot-bound icons (Mission, Vision, Strength 1–4)
    if (section === 'recipe') {
        const recipeMediaKeys = [
            'media_id_mission_icon', 'media_id_vision_icon',
            'media_id_strength_1_icon', 'media_id_strength_2_icon',
            'media_id_strength_3_icon', 'media_id_strength_4_icon',
        ];
        const ids = (Array.isArray(ALL_CONTENTS) ? ALL_CONTENTS : [])
            .filter(r => r.section === 'text_content' && r.page_id == pageId && recipeMediaKeys.includes(r.key))
            .map(r => String(r.value));
        const idSet = new Set(ids);
        items = items.filter(m => idSet.has(String(m.id)));
        const seen = new Set();
        items = items.filter(m => {
            if (seen.has(String(m.id))) return false;
            seen.add(String(m.id));
            return true;
        });
    }

    if (!items.length) {
        container.innerHTML = '<p class="text-sm text-gray-400 col-span-3">No images uploaded yet.</p>';
        return;
    }

    container.innerHTML = items.map(m => `
        <div class="relative group rounded-lg overflow-hidden border ${m.is_active ? 'border-green-400' : 'border-gray-200'} bg-white" id="media-${m.id}">
            <img src="${mediaUrl(m.section, m.filename)}" alt="${m.original_name}"
                 class="w-full h-24 object-cover ${m.is_active ? '' : 'opacity-50'}">
            ${m.is_active
                ? `<span class="absolute top-1 left-1 text-[0.55rem] font-bold uppercase tracking-wide text-green-700 bg-green-100 border border-green-200 px-1.5 py-0.5 rounded-full leading-none pointer-events-none">Active</span>`
                : ''}
            <div class="p-1.5">
                <p class="text-xs text-gray-500 truncate font-mono">${m.original_name}</p>
            </div>
            <div class="absolute top-1 right-1 flex gap-1 opacity-0 group-hover:opacity-100 transition">
                <button type="button" onclick='event.stopPropagation(); openReplaceModal(${m.id}, ${JSON.stringify(m.section)}, ${m.page_id})'
                        class="bg-gray-800 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center leading-none hover:bg-gray-700"
                        title="Replace image">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-1.414a2 2 0 01.586-1.414z"/></svg>
                </button>
                <button type="button" onclick='event.stopPropagation(); deleteMedia(${m.id}, ${JSON.stringify(m.section)})'
                        class="bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center leading-none hover:bg-red-600"
                        title="Delete">
                    &times;
                </button>
            </div>
        </div>
    `).join('');
}

// ── Slot helpers (shared by upload and delete) ────────────────────────

// Returns { mediaKey, isFull } for sections that use stable slot binding,
// or null for carousel (which has no slot system).
// isFull = true when every slot for that section+page already has a value.
function resolveUploadSlot(section, pageId) {
    if (section === 'logo') {
        const page  = (PAGES || []).find(p => p.id == pageId);
        const title = (page ? String(page.title) : '').toUpperCase();
        const key   = title.includes('MENU')                           ? LOGO_SLOT_KEYS.MENU
                    : (title.includes('KORPALA') || title.includes('NORDIC')) ? LOGO_SLOT_KEYS.NORDIC
                    : title.includes('ULTRAFOOD')                      ? LOGO_SLOT_KEYS.ULTRAFOOD
                    : null;
        return key ? { mediaKey: key, isFull: false } : null;
    }
    const slots = section === 'product' ? PRODUCT_SLOT_KEYS
                : section === 'recipe'  ? RECIPE_SLOT_KEYS : null;
    if (!slots) return null; // carousel — no slot binding
    const firstEmpty = slots.find(k =>
        !(ALL_CONTENTS || []).find(r =>
            r.section === 'text_content' && r.page_id == pageId && r.key === k && r.value
        )
    );
    return firstEmpty ? { mediaKey: firstEmpty, isFull: false } : { mediaKey: null, isFull: true };
}

// Calls contentUpdateField to write mediaKey = newId into text_content,
// then updates the local ALL_CONTENTS cache.
async function saveMediaKey(mediaKey, newId, pageId) {
    const form = new FormData();
    form.append('_token',   CSRF);
    form.append('page_id',  pageId);
    form.append('section',  'text_content');
    form.append('base_key', mediaKey);
    form.append('value',    String(newId));
    const r = await fetch(ROUTES.contentUpdateField, {
        method: 'POST', body: form,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    });
    if (!r.ok) return;
    const ci = (ALL_CONTENTS || []).findIndex(c =>
        c.key === mediaKey && c.section === 'text_content' && c.page_id == pageId
    );
    if (ci >= 0) ALL_CONTENTS[ci].value = String(newId);
    else ALL_CONTENTS.push({ id: 0, page_id: pageId, section: 'text_content', key: mediaKey, value: String(newId) });
}

function previewFile(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('imgPreviewImg').src = e.target.result;
        document.getElementById('imgPreviewName').textContent = file.name;
        document.getElementById('imgPreviewArea').classList.remove('hidden');
        document.getElementById('imgDropZone').classList.add('hidden');
    };
    reader.readAsDataURL(file);
}

async function submitUpload() {
    const file = document.getElementById('imgFileInput').files[0];
    if (!file) { showMsg('uploadMsg', 'Please select a file.', 'red'); return; }

    // For slot-bound sections (logo / product / recipe), check whether a free
    // slot exists before uploading to avoid creating orphaned media rows.
    const slot = resolveUploadSlot(current.section, current.pageId);
    if (slot && slot.isFull) {
        showMsg('uploadMsg', 'All image slots are filled — use the Replace button on existing images.', 'red');
        return;
    }

    const form = new FormData();
    form.append('_token', CSRF);
    form.append('page_id', current.pageId);
    form.append('section', current.section);
    form.append('image', file);

    try {
        const r    = await fetch(ROUTES.mediaUpload, { method: 'POST', body: form });
        const data = await r.json();
        if (data.success) {
            ALL_MEDIA.push({
                id: data.media.id,
                page_id: current.pageId,
                section: current.section,
                filename: data.media.filename,
                original_name: file.name,
            });
            // Link the new image to its slot so renderExistingImages can find it
            if (slot && slot.mediaKey) {
                await saveMediaKey(slot.mediaKey, data.media.id, current.pageId);
            }
            showMsg('uploadMsg', 'Uploaded successfully!', 'green');
            renderExistingImages(current.section, current.pageId);
            document.getElementById('imgPreviewArea').classList.add('hidden');
            document.getElementById('imgDropZone').classList.remove('hidden');
            document.getElementById('imgFileInput').value = '';
        } else {
            showMsg('uploadMsg', 'Upload failed. Try again.', 'red');
        }
    } catch {
        showMsg('uploadMsg', 'Upload error. Check file and try again.', 'red');
    }
}

function deleteMedia(id, section) {
    if (!confirm('Delete this image?')) return;
    fetch(ROUTES.mediaDestroy + '/' + id + '?section=' + section, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;

        // Remove from local cache and DOM
        const idx = ALL_MEDIA.findIndex(m => m.id === id);
        if (idx !== -1) ALL_MEDIA.splice(idx, 1);
        document.getElementById('media-' + id)?.remove();

        // If this image was bound to a media_id_* slot, clear that key from
        // text_content so the slot reverts to its fallback state.
        const mediaKeyRow = (ALL_CONTENTS || []).find(r =>
            r.section === 'text_content' &&
            r.page_id == current.pageId &&
            r.key.startsWith('media_id_') &&
            String(r.value) === String(id)
        );
        if (mediaKeyRow) {
            const params = new URLSearchParams({
                section: 'text_content',
                key:     mediaKeyRow.key,
                page_id: current.pageId,
            });
            fetch(ROUTES.contentDestroyByKey + '?' + params.toString(), {
                method:  'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            }).then(() => {
                const ci = ALL_CONTENTS.indexOf(mediaKeyRow);
                if (ci >= 0) ALL_CONTENTS.splice(ci, 1);
                renderExistingImages(current.section, current.pageId);
            });
        } else {
            renderExistingImages(current.section, current.pageId);
        }
    });
}

// ── Text/Content Panel ────────────────────────────────────────────────
let editingContentBaseKey = null;  // when set, form is in "edit" mode for this field

function showTextPanel(section, pageId, pageTitle) {
    document.getElementById('txtSectionLabel').textContent = section.replace(/_/g, ' ').toUpperCase();
    document.getElementById('txtPageTitle').textContent = pageTitle;
    document.getElementById('textPanel').classList.remove('hidden');
    editingContentBaseKey = null;
    clearContentForm();
    document.getElementById('contentMsg').classList.add('hidden');
    initTextPanelDropdowns();
    renderExistingContents(section, pageId);
    updateContentFormState();
}

function clearContentForm() {
    document.getElementById('contentKey').value = '';
    document.getElementById('contentValue').value = '';
    document.getElementById('contentFont').value = '';
    document.getElementById('contentFontSize').value = '';
    document.getElementById('fontPreviewWrap').classList.add('hidden');
    document.getElementById('historyTitle').value = '';
    document.getElementById('historyDesc').value  = '';
    document.getElementById('historyYearInputs').classList.add('hidden');
    document.getElementById('singleValueWrap').classList.remove('hidden');
}

function updateContentFormState() {
    const btn = document.getElementById('contentSaveBtn');
    const hint = document.getElementById('contentEditHint');
    if (editingContentBaseKey) {
        if (btn) btn.textContent = 'Update';
        if (hint) { hint.textContent = 'Editing: ' + editingContentBaseKey; hint.classList.remove('hidden'); }
    } else {
        if (btn) btn.textContent = 'Save';
        if (hint) hint.classList.add('hidden');
    }
}

// ── Populate Section & Font dropdowns on first open ───────────────────
function initTextPanelDropdowns() {
    const secSel  = document.getElementById('contentSection');
    const fontSel = document.getElementById('contentFont');

    // Populate section picker (only once)
    if (secSel.options.length <= 1) {
        CONTENT_SECTIONS.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.key;
            opt.textContent = s.label;
            secSel.appendChild(opt);
        });
    }

    // Populate font picker grouped by category (only once)
    if (fontSel.options.length <= 1) {
        const categories = [...new Set(FONT_OPTIONS.map(f => f.category))];
        categories.forEach(cat => {
            const grp = document.createElement('optgroup');
            grp.label = cat;
            FONT_OPTIONS.filter(f => f.category === cat).forEach(f => {
                const opt = document.createElement('option');
                opt.value = f.name;
                opt.textContent = f.name;
                grp.appendChild(opt);
            });
            fontSel.appendChild(grp);
        });
    }

    // Reset pickers
    secSel.value = '';
    document.getElementById('contentField').value = '';
    document.getElementById('contentField').disabled = true;
    fontSel.value = '';
    document.getElementById('contentFontSize').value = '';
    document.getElementById('fontPreviewWrap').classList.add('hidden');
}

// ── Section picker changed → populate Field picker ────────────────────
function onContentSectionChange() {
    const secKey  = document.getElementById('contentSection').value;
    const fieldSel = document.getElementById('contentField');
    fieldSel.innerHTML = '<option value="">— pick field —</option>';

    if (!secKey) {
        fieldSel.disabled = true;
        return;
    }

    const sec = CONTENT_SECTIONS.find(s => s.key === secKey);
    if (!sec) return;

    sec.fields.forEach(f => {
        const opt = document.createElement('option');
        opt.value = f.key;
        opt.textContent = f.label;
        fieldSel.appendChild(opt);
    });
    fieldSel.disabled = false;
    document.getElementById('contentKey').value = '';
}

// ── Field picker changed → auto-fill Key input ────────────────────────
function onContentFieldChange() {
    const fieldKey = document.getElementById('contentField').value;
    document.getElementById('contentKey').value = fieldKey;
    toggleHistoryYearUI(isHistoryYear(fieldKey));
}

// ── Font selector changed → load font + show preview ─────────────────
function onFontChange() {
    const fontName = document.getElementById('contentFont').value;
    const wrap     = document.getElementById('fontPreviewWrap');
    const box      = document.getElementById('fontPreviewBox');

    if (!fontName) {
        wrap.classList.add('hidden');
        return;
    }

    loadGoogleFont(fontName);

    const value = document.getElementById('contentValue').value.trim();
    box.textContent = value || 'The quick brown fox jumps over the lazy dog.';
    box.style.fontFamily = `'${fontName}', sans-serif`;

    const sizeVal = document.getElementById('contentFontSize').value;
    box.style.fontSize = sizeVal || '';

    wrap.classList.remove('hidden');
}

function getBaseKey(key) {
    return key.replace(/_font_size$|_font$/, '');
}

function renderExistingContents(section, pageId) {
    const container = document.getElementById('existingContents');
    let items = ALL_CONTENTS.filter(c => c.section === section && c.page_id == pageId);

    // Deduplicate by key (same page/section/key can have multiple rows from legacy data)
    const byKey = new Map();
    items.forEach(c => { byKey.set(c.key, c); });
    items = Array.from(byKey.values());

    if (!items.length) {
        container.innerHTML = '<p class="text-sm text-gray-400">No entries yet.</p>';
        return;
    }

    // Group by base key; history year sub-fields (history_YEAR_title / _desc) are
    // merged into a single history_YEAR group.
    const byBase = {};
    items.forEach(c => {
        const histBase = getHistoryYearBase(c.key);
        if (histBase) {
            if (!byBase[histBase]) byBase[histBase] = { type: 'history_year', title: null, desc: null };
            if (c.key.endsWith('_title')) byBase[histBase].title = c.value;
            else if (c.key.endsWith('_desc'))  byBase[histBase].desc  = c.value;
            return;
        }
        const base = getBaseKey(c.key);
        if (!byBase[base]) byBase[base] = { type: 'text', value: null, font: null, font_size: null };
        if (c.key === base) byBase[base].value = c.value;
        else if (c.key === base + '_font')      byBase[base].font      = c.value;
        else if (c.key === base + '_font_size') byBase[base].font_size = c.value;
    });

    const baseKeys = Object.keys(byBase).sort();
    container.innerHTML = baseKeys.map(baseKey => {
        const g      = byBase[baseKey];
        const safeId = String(baseKey).replace(/[^a-zA-Z0-9_-]/g, '_');

        if (g.type === 'history_year') {
            const year        = baseKey.replace('history_', '');
            const titlePreview = (g.title || '').length > 80  ? g.title.slice(0, 80)  + '…' : (g.title || '—');
            const descPreview  = (g.desc  || '').length > 100 ? g.desc.slice(0, 100)  + '…' : (g.desc  || '—');
            return `
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200" id="content-group-${safeId}" data-base-key="${escHtml(baseKey)}">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-gray-700 mb-1">${escHtml(year)}</p>
                        <p class="text-xs text-gray-500"><span class="font-semibold">Title:</span> ${escHtml(titlePreview)}</p>
                        <p class="text-xs text-gray-500 mt-0.5"><span class="font-semibold">Desc:</span> ${escHtml(descPreview)}</p>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <button type="button" onclick='openEditContent(${JSON.stringify(baseKey)})'
                                class="text-gray-600 hover:text-gray-800 text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-100">Edit</button>
                        <button type="button" onclick='deleteContentByKey(${JSON.stringify(baseKey)}, ${JSON.stringify(section)})'
                                class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border border-red-200 rounded hover:bg-red-50">Delete</button>
                    </div>
                </div>
            </div>`;
        }

        // Regular text entry
        const valuePreview = (g.value || '').length > 120 ? (g.value || '').slice(0, 120) + '…' : (g.value || '');
        const meta = [g.font && escHtml(g.font), g.font_size && escHtml(g.font_size)].filter(Boolean).join(' · ');
        return `
        <div class="flex items-start justify-between gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200" id="content-group-${safeId}" data-base-key="${escHtml(baseKey)}">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-600">${escHtml(baseKey)}</p>
                <p class="text-sm text-gray-700 mt-0.5 break-words">${escHtml(valuePreview)}</p>
                ${meta ? `<p class="text-xs text-gray-500 mt-1">${meta}</p>` : ''}
            </div>
            <div class="flex gap-1 shrink-0">
                <button type="button" onclick='openEditContent(${JSON.stringify(baseKey)})'
                        class="text-gray-600 hover:text-gray-800 text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-100">Edit</button>
                <button type="button" onclick='deleteContentByKey(${JSON.stringify(baseKey)}, ${JSON.stringify(section)})'
                        class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border border-red-200 rounded hover:bg-red-50">Delete</button>
            </div>
        </div>`;
    }).join('');
}

function openEditContent(baseKey) {
    const section = current.section;
    const pageId  = current.pageId;

    // History year: populate dual title+desc inputs and set section/field pickers
    if (isHistoryYear(baseKey)) {
        const year     = baseKey.replace('history_', '');
        const titleRow = ALL_CONTENTS.find(c => c.section === section && c.page_id == pageId && c.key === `history_${year}_title`);
        const descRow  = ALL_CONTENTS.find(c => c.section === section && c.page_id == pageId && c.key === `history_${year}_desc`);

        document.getElementById('contentKey').value   = baseKey;
        document.getElementById('historyTitle').value = (titleRow && titleRow.value) || '';
        document.getElementById('historyDesc').value  = (descRow  && descRow.value)  || '';

        // Sync the Section & Field dropdowns so the user sees what's being edited
        const secSel = document.getElementById('contentSection');
        secSel.value = 'history';
        onContentSectionChange();
        document.getElementById('contentField').value = baseKey;

        toggleHistoryYearUI(true);
        editingContentBaseKey = baseKey;
        updateContentFormState();
        return;
    }

    // Regular text field
    const main    = ALL_CONTENTS.find(c => c.section === section && c.page_id == pageId && c.key === baseKey);
    const fontRow = ALL_CONTENTS.find(c => c.section === section && c.page_id == pageId && c.key === baseKey + '_font');
    const sizeRow = ALL_CONTENTS.find(c => c.section === section && c.page_id == pageId && c.key === baseKey + '_font_size');

    document.getElementById('contentKey').value       = baseKey;
    document.getElementById('contentValue').value     = (main    && main.value)    || '';
    document.getElementById('contentFont').value      = (fontRow && fontRow.value) || '';
    document.getElementById('contentFontSize').value  = (sizeRow && sizeRow.value) || '';
    document.getElementById('fontPreviewWrap').classList.add('hidden');
    if ((fontRow && fontRow.value) || (sizeRow && sizeRow.value)) onFontChange();

    editingContentBaseKey = baseKey;
    updateContentFormState();
}

function deleteContentByKey(baseKey, section) {
    const isHistYear = isHistoryYear(baseKey);
    const confirmMsg = isHistYear
        ? 'Delete this history entry? Both the Title and Description will be removed.'
        : 'Delete this entire field? The value and its font/size settings will be removed.';
    if (!confirm(confirmMsg)) return;

    // History year: delete both _title and _desc sub-keys
    if (isHistYear) {
        const year = baseKey.replace('history_', '');
        const subKeys = [`history_${year}_title`, `history_${year}_desc`];
        Promise.all(subKeys.map(k => {
            const params = new URLSearchParams({ section, key: k, page_id: current.pageId });
            return fetch(ROUTES.contentDestroyByKey + '?' + params.toString(), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            }).then(r => r.json());
        })).then(() => {
            ALL_CONTENTS.splice(0, ALL_CONTENTS.length, ...ALL_CONTENTS.filter(c =>
                !(c.section === section && c.page_id == current.pageId && subKeys.includes(c.key))
            ));
            const safeId = String(baseKey).replace(/[^a-zA-Z0-9_-]/g, '_');
            document.getElementById('content-group-' + safeId)?.remove();
            if (editingContentBaseKey === baseKey) {
                editingContentBaseKey = null;
                clearContentForm();
                updateContentFormState();
            }
        });
        return;
    }

    // Regular field: delete key + _font + _font_size
    const params = new URLSearchParams({ section, key: baseKey, page_id: current.pageId });
    fetch(ROUTES.contentDestroyByKey + '?' + params.toString(), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            ALL_CONTENTS.splice(0, ALL_CONTENTS.length, ...ALL_CONTENTS.filter(c =>
                !(c.section === section && c.page_id == current.pageId && (c.key === baseKey || c.key === baseKey + '_font' || c.key === baseKey + '_font_size'))
            ));
            const safeId = String(baseKey).replace(/[^a-zA-Z0-9_-]/g, '_');
            const el = document.getElementById('content-group-' + safeId);
            if (el) el.remove();
            if (editingContentBaseKey === baseKey) {
                editingContentBaseKey = null;
                clearContentForm();
                updateContentFormState();
            }
        }
    });
}

// Keys that store only font or only size — value can come from Font Options when Content/Value is empty
const NAV_STYLE_KEYS = { font: 'header_nav_font', size: 'header_nav_font_size' };

function saveContent() {
    const key      = document.getElementById('contentKey').value.trim();
    let value      = document.getElementById('contentValue').value.trim();
    const fontName = document.getElementById('contentFont').value.trim();
    const fontSize = document.getElementById('contentFontSize').value.trim();

    // ── History year: save/update both title and description together ─────
    if (isHistoryYear(key)) {
        const year  = key.replace('history_', '');
        const title = document.getElementById('historyTitle').value.trim();
        const desc  = document.getElementById('historyDesc').value.trim();

        if (!title && !desc) {
            showMsg('contentMsg', 'Enter a Title and/or Description for this year.', 'red');
            return;
        }

        // Use updateField (upsert) so re-saving an existing year doesn't duplicate rows
        const upsertHist = (suffix, val) => {
            if (!val) return Promise.resolve(null);
            const form = new FormData();
            form.append('_token',   CSRF);
            form.append('page_id',  current.pageId);
            form.append('section',  current.section);
            form.append('base_key', `history_${year}_${suffix}`);
            form.append('value',    val);
            return fetch(ROUTES.contentUpdateField, {
                method: 'POST', body: form,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            }).then(r => {
                if (!r.ok) return r.text().then(t => {
                    let msg = r.status === 419 ? 'Session expired — please refresh.' : r.status + ' ' + r.statusText;
                    try { const d = JSON.parse(t); if (d.message) msg = d.message; } catch (_) {}
                    throw new Error(msg);
                });
                return r.json();
            });
        };

        Promise.all([upsertHist('title', title), upsertHist('desc', desc)])
            .then(() => {
                // Sync ALL_CONTENTS in memory
                const sec  = current.section;
                const pid  = current.pageId;
                const syncRow = (k, v) => {
                    if (!v) return;
                    const row = ALL_CONTENTS.find(c => c.section === sec && c.page_id == pid && c.key === k);
                    if (row) row.value = v;
                    else ALL_CONTENTS.push({ id: 0, page_id: pid, section: sec, key: k, value: v });
                };
                syncRow(`history_${year}_title`, title);
                syncRow(`history_${year}_desc`,  desc);

                showMsg('contentMsg', 'History entry saved!', 'green');
                editingContentBaseKey = null;
                clearContentForm();
                updateContentFormState();
                // Reset pickers
                document.getElementById('contentSection').value = '';
                document.getElementById('contentField').value   = '';
                document.getElementById('contentField').disabled = true;
                renderExistingContents(sec, pid);
            })
            .catch(err => showMsg('contentMsg', err.message || 'Save failed — please retry.', 'red'));
        return;
    }

    // For "Nav link font" / "Nav link font size", allow value from Font Options when Content/Value is empty
    if (key === NAV_STYLE_KEYS.font && !value && fontName) value = fontName;
    if (key === NAV_STYLE_KEYS.size && !value && fontSize) value = fontSize;

    // Edit mode: allow saving when we have at least one thing to update (value, font, or size)
    if (editingContentBaseKey) {
        const hasValue = value.length > 0;
        const hasFontOrSize = fontName || fontSize;
        if (!key || (!hasValue && !hasFontOrSize)) {
            showMsg('contentMsg', 'Enter a content value and/or choose Font options to update.', 'red');
            return;
        }
        // When Content/Value is empty in edit mode, keep existing main value (so we only update font/size if desired)
        let submitValue = value;
        if (!submitValue && editingContentBaseKey === 'header_nav' && hasFontOrSize) submitValue = '\u00A0';
        else if (!submitValue && hasFontOrSize) {
            const main = ALL_CONTENTS.find(c => c.section === current.section && c.page_id == current.pageId && c.key === editingContentBaseKey);
            submitValue = (main && main.value) ? main.value : '\u00A0';
        } else if (!submitValue) return;

        const form = new FormData();
        form.append('_token', CSRF);
        form.append('page_id', current.pageId);
        form.append('section', current.section);
        form.append('base_key', editingContentBaseKey);
        form.append('value', submitValue);
        if (fontName) form.append('font', fontName);
        if (fontSize) form.append('font_size', fontSize);

        fetch(ROUTES.contentUpdateField, {
            method: 'POST',
            body: form,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(r => {
                if (!r.ok) {
                    return r.text().then(t => {
                        let msg = r.status === 419 ? 'Session expired — please refresh the page and try again.' : (r.status + ' ' + r.statusText);
                        try { const d = JSON.parse(t); if (d.message) msg = d.message; } catch (_) {}
                        throw new Error(msg);
                    });
                }
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    // Update in-memory ALL_CONTENTS for this field (use submitValue in case value was empty in edit)
                    const section = current.section;
                    const pageId  = current.pageId;
                    const baseKey = editingContentBaseKey;
                    const main = ALL_CONTENTS.find(c => c.section === section && c.page_id == pageId && c.key === baseKey);
                    if (main) main.value = submitValue;
                    else ALL_CONTENTS.push({ id: 0, page_id: pageId, section, key: baseKey, value: submitValue });
                    const fontRow = ALL_CONTENTS.find(c => c.section === section && c.page_id == pageId && c.key === baseKey + '_font');
                    if (fontName) {
                        if (fontRow) fontRow.value = fontName;
                        else ALL_CONTENTS.push({ id: 0, page_id: pageId, section, key: baseKey + '_font', value: fontName });
                    } else if (fontRow) ALL_CONTENTS.splice(ALL_CONTENTS.indexOf(fontRow), 1);
                    const sizeRow = ALL_CONTENTS.find(c => c.section === section && c.page_id == pageId && c.key === baseKey + '_font_size');
                    if (fontSize) {
                        if (sizeRow) sizeRow.value = fontSize;
                        else ALL_CONTENTS.push({ id: 0, page_id: pageId, section, key: baseKey + '_font_size', value: fontSize });
                    } else if (sizeRow) ALL_CONTENTS.splice(ALL_CONTENTS.indexOf(sizeRow), 1);

                    showMsg('contentMsg', 'Updated!', 'green');
                    editingContentBaseKey = null;
                    clearContentForm();
                    updateContentFormState();
                    renderExistingContents(section, pageId);
                } else {
                    showMsg('contentMsg', data.message || 'Update failed.', 'red');
                }
            })
            .catch(err => showMsg('contentMsg', err.message || 'Network error — please retry.', 'red'));
        return;
    }

    // Add new: require key and value
    if (!key || !value) {
        showMsg('contentMsg', 'Key and a value are required. Use Content/Value or Font Options (Family or Size) for nav font/size.', 'red');
        return;
    }

    // Add new: POST each entry (key, key_font, key_font_size)
    const postEntry = (k, v) => {
        const form = new FormData();
        form.append('_token',   CSRF);
        form.append('page_id',  current.pageId);
        form.append('section',  current.section);
        form.append('key',   k);
        form.append('value', v);
        return fetch(ROUTES.contentSave, {
            method: 'POST',
            body: form,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        }).then(r => {
            if (!r.ok) return r.text().then(t => { throw new Error(r.status + ': ' + (t && t.length < 200 ? t : r.statusText)); });
            return r.json();
        });
    };

    const entries = [{ k: key, v: value }];
    // Don't add _font/_font_size for nav style-only keys (they store the font/size in the key itself)
    const isNavStyleKey = (key === NAV_STYLE_KEYS.font || key === NAV_STYLE_KEYS.size);
    if (!isNavStyleKey) {
        if (fontName) entries.push({ k: key + '_font',      v: fontName });
        if (fontSize) entries.push({ k: key + '_font_size', v: fontSize });
    }

    Promise.all(entries.map(e => postEntry(e.k, e.v)))
        .then(results => {
            if (results.every(r => r && r.success)) {
                results.forEach(r => ALL_CONTENTS.push(r.content));
                showMsg('contentMsg', fontName ? `Saved with font "${fontName}"!` : 'Saved!', 'green');
                clearContentForm();
                document.getElementById('contentSection').value = '';
                document.getElementById('contentField').value = '';
                document.getElementById('contentField').disabled = true;
                renderExistingContents(current.section, current.pageId);
            } else {
                showMsg('contentMsg', 'One or more entries failed to save.', 'red');
            }
        })
        .catch(err => showMsg('contentMsg', err.message || 'Network error — please retry.', 'red'));
}

function deleteContent(id, section) {
    if (!confirm('Delete this entry?')) return;
    fetch(ROUTES.contentDestroy + '/' + id + '?section=' + section, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const idx = ALL_CONTENTS.findIndex(c => c.id === id);
            if (idx !== -1) ALL_CONTENTS.splice(idx, 1);
            document.getElementById('content-' + id)?.remove();
        }
    });
}

// ── Contact Panel ─────────────────────────────────────────────────────
let editingContactId = null;

function updateContactFormState() {
    const btn  = document.getElementById('contactSaveBtn');
    const hint = document.getElementById('contactEditHint');
    if (editingContactId) {
        if (btn)  btn.textContent = 'Update';
        if (hint) { hint.classList.remove('hidden'); }
    } else {
        if (btn)  btn.textContent = 'Save';
        if (hint) hint.classList.add('hidden');
    }
}

function showContactPanel(section, pageId, pageTitle) {
    document.getElementById('contactSectionLabel').textContent = section.replace(/_/g, ' ').toUpperCase();
    document.getElementById('contactPageTitle').textContent = pageTitle;
    document.getElementById('contactPanel').classList.remove('hidden');
    editingContactId = null;
    document.getElementById('contactLabel').value = '';
    document.getElementById('contactValue').value = '';
    document.getElementById('contactMsg').classList.add('hidden');
    updateContactFormState();
    renderExistingContacts(section, pageId);
}

function editContact(id) {
    const c = ALL_CONTACTS.find(x => x.id === id);
    if (!c) return;
    editingContactId = id;
    document.getElementById('contactLabel').value = c.label;
    document.getElementById('contactValue').value = c.value;
    document.getElementById('contactMsg').classList.add('hidden');
    updateContactFormState();
    document.getElementById('contactLabel').focus();
}

function renderExistingContacts(type, pageId) {
    const container = document.getElementById('existingContacts');
    const items = ALL_CONTACTS.filter(c => c.type === type && c.page_id == pageId);

    if (!items.length) {
        container.innerHTML = '<p class="text-sm text-gray-400">No entries yet.</p>';
        return;
    }

    container.innerHTML = items.map(c => `
        <div class="flex items-center justify-between gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200" id="contact-${c.id}">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-600">${escHtml(c.label)}</p>
                <p class="text-sm text-gray-700">${escHtml(c.value)}</p>
            </div>
            <div class="flex gap-1 shrink-0">
                <button type="button" onclick='editContact(${c.id})'
                        class="text-gray-600 hover:text-gray-800 text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-100">Edit</button>
                <button type="button" onclick='deleteContact(${c.id}, ${JSON.stringify(c.type)})'
                        class="text-red-400 hover:text-red-600 text-xs px-2 py-1 border border-red-200 rounded hover:bg-red-50">Delete</button>
            </div>
        </div>
    `).join('');
}

function saveContact() {
    const label = document.getElementById('contactLabel').value.trim();
    const value = document.getElementById('contactValue').value.trim();
    if (!label || !value) { showMsg('contactMsg', 'Both label and value are required.', 'red'); return; }

    // Edit mode — update existing entry
    if (editingContactId) {
        const form = new FormData();
        form.append('_token', CSRF);
        form.append('_method', 'PUT');
        form.append('label', label);
        form.append('value', value);
        form.append('type', current.section);

        fetch(ROUTES.contactUpdate + '/' + editingContactId, {
            method: 'POST',
            body: form,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .then(r => {
            if (!r.ok) return r.text().then(t => {
                let msg = r.status === 419 ? 'Session expired — please refresh the page.' : (r.status + ' ' + r.statusText);
                try { const d = JSON.parse(t); if (d.message) msg = d.message; } catch (_) {}
                throw new Error(msg);
            });
            return r.json();
        })
        .then(data => {
            if (data.success) {
                const c = ALL_CONTACTS.find(x => x.id === editingContactId);
                if (c) { c.label = label; c.value = value; }
                showMsg('contactMsg', 'Updated!', 'green');
                editingContactId = null;
                document.getElementById('contactLabel').value = '';
                document.getElementById('contactValue').value = '';
                updateContactFormState();
                renderExistingContacts(current.section, current.pageId);
            } else {
                showMsg('contactMsg', data.message || 'Update failed.', 'red');
            }
        })
        .catch(err => showMsg('contactMsg', err.message || 'Network error.', 'red'));
        return;
    }

    // Add new
    const form = new FormData();
    form.append('_token', CSRF);
    form.append('page_id', current.pageId);
    form.append('type', current.section);
    form.append('label', label);
    form.append('value', value);

    fetch(ROUTES.contactSave, { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                ALL_CONTACTS.push(data.contact);
                showMsg('contactMsg', 'Saved!', 'green');
                document.getElementById('contactLabel').value = '';
                document.getElementById('contactValue').value = '';
                renderExistingContacts(current.section, current.pageId);
            } else {
                showMsg('contactMsg', 'Save failed.', 'red');
            }
        });
}

function deleteContact(id, type) {
    if (!confirm('Delete this entry?')) return;
    fetch(ROUTES.contactDestroy + '/' + id + '?type=' + type, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const idx = ALL_CONTACTS.findIndex(c => c.id === id);
            if (idx !== -1) ALL_CONTACTS.splice(idx, 1);
            document.getElementById('contact-' + id)?.remove();
        }
    });
}

// ── Upload / Replace Modal (Change in left panel, or Replace on each image card) ─────
let modalSection   = null;
let modalPageId    = null;
let replaceMediaId = null;  // when set, modal is in "replace" mode for this media id

function openUploadModal(event, section, pageId, pageTitle) {
    event.stopPropagation();
    replaceMediaId = null;
    modalSection = section;
    modalPageId  = pageId;
    document.getElementById('modalTitle').textContent = 'Upload Image';
    document.getElementById('modalSubmitBtn').textContent = 'Upload';
    document.getElementById('modalLabel').textContent = section.replace(/_/g,' ').toUpperCase() + ' – ' + pageTitle;
    document.getElementById('uploadModal').classList.add('open');
    document.getElementById('previewArea').classList.add('hidden');
    document.getElementById('dropZone').classList.remove('hidden');
    document.getElementById('fileInput').value = '';
    document.getElementById('modalMsg').classList.add('hidden');
}

function openReplaceModal(mediaId, section, pageId) {
    const media = ALL_MEDIA.find(m => m.id === mediaId);
    replaceMediaId = mediaId;
    modalSection   = section;
    modalPageId    = pageId;
    document.getElementById('modalTitle').textContent = 'Replace Image';
    document.getElementById('modalSubmitBtn').textContent = 'Replace';
    document.getElementById('modalLabel').textContent = (media && media.original_name) ? media.original_name : 'Image #' + mediaId;
    document.getElementById('uploadModal').classList.add('open');
    document.getElementById('previewArea').classList.add('hidden');
    document.getElementById('dropZone').classList.remove('hidden');
    document.getElementById('fileInput').value = '';
    document.getElementById('modalMsg').classList.add('hidden');
}

function closeModal() {
    document.getElementById('uploadModal').classList.remove('open');
    replaceMediaId = null;
}

function closeModalOnOverlay(event) {
    if (event.target === document.getElementById('uploadModal')) closeModal();
}

function previewModalFile(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewName').textContent = file.name;
        document.getElementById('previewArea').classList.remove('hidden');
        document.getElementById('dropZone').classList.add('hidden');
    };
    reader.readAsDataURL(file);
}

async function submitModalUpload() {
    const file = document.getElementById('fileInput').files[0];
    if (!file) { showMsg('modalMsg', 'Please select a file.', 'red'); return; }

    if (replaceMediaId) {
        // Replace existing image (keeps same id, only file changes — media_key stays valid)
        const form = new FormData();
        form.append('_token', CSRF);
        form.append('_method', 'PUT');
        form.append('section', modalSection);
        form.append('image', file);

        try {
            const r    = await fetch(ROUTES.mediaUpdate + '/' + replaceMediaId, { method: 'POST', body: form });
            const data = await r.json();
            if (data.success) {
                const m = ALL_MEDIA.find(x => x.id === replaceMediaId);
                if (m) {
                    m.filename      = data.media.filename;
                    m.original_name = data.media.original_name;
                }
                showMsg('modalMsg', 'Image replaced successfully!', 'green');
                setTimeout(closeModal, 1200);
                if (current.section === modalSection && current.pageId == modalPageId) {
                    renderExistingImages(current.section, current.pageId);
                }
            } else {
                showMsg('modalMsg', data.message || 'Replace failed.', 'red');
            }
        } catch {
            showMsg('modalMsg', 'Replace failed. Try again.', 'red');
        }
        return;
    }

    // New upload — check slot availability for slot-bound sections
    const slot = resolveUploadSlot(modalSection, modalPageId);
    if (slot && slot.isFull) {
        showMsg('modalMsg', 'All image slots are filled — use the Replace button on existing images.', 'red');
        return;
    }

    const form = new FormData();
    form.append('_token',   CSRF);
    form.append('page_id',  modalPageId);
    form.append('section',  modalSection);
    form.append('image',    file);

    try {
        const r    = await fetch(ROUTES.mediaUpload, { method: 'POST', body: form });
        const data = await r.json();
        if (data.success) {
            ALL_MEDIA.push({
                id:            data.media.id,
                page_id:       modalPageId,
                section:       modalSection,
                filename:      data.media.filename,
                original_name: data.media.original_name != null ? data.media.original_name : file.name,
            });
            // Link the new image to its slot in text_content
            if (slot && slot.mediaKey) {
                await saveMediaKey(slot.mediaKey, data.media.id, modalPageId);
            }
            showMsg('modalMsg', 'Uploaded successfully!', 'green');
            setTimeout(closeModal, 1200);
            if (current.section === modalSection && current.pageId == modalPageId) {
                renderExistingImages(current.section, current.pageId);
            }
        } else {
            showMsg('modalMsg', 'Upload failed.', 'red');
        }
    } catch {
        showMsg('modalMsg', 'Upload failed. Try again.', 'red');
    }
}

// ── Drag/Drop helpers ─────────────────────────────────────────────────
function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('border-blue-400', 'bg-blue-50');
}

function handleDrop(e) {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('imgFileInput').files = dt.files;
        previewFile({ target: { files: [file] } });
    }
}

function handleDropModal(e) {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('fileInput').files = dt.files;
        previewModalFile({ target: { files: [file] } });
    }
}

// ── Utility ───────────────────────────────────────────────────────────
function showMsg(id, text, color) {
    const el = document.getElementById(id);
    el.textContent = text;
    el.className = 'text-sm mt-1 ' + (color === 'green' ? 'text-green-600' : 'text-red-600');
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 4000);
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
