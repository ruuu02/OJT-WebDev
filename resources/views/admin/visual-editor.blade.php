<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Visual Editor — Admin</title>
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  @php
    $visualEditorFavicon = 'images/favicon/Ultrafood-Favicon.png';
    $visualEditorFaviconVersion = file_exists(public_path($visualEditorFavicon))
      ? filemtime(public_path($visualEditorFavicon))
      : time();
  @endphp
  <link rel="icon" type="image/png" href="{{ asset($visualEditorFavicon) }}?v={{ $visualEditorFaviconVersion }}" />

  {{-- Fonts: same as frontend --}}
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Anek+Latin:wght@300;400;500;600;700;800&family=Spline+Sans:wght@300;400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />

  {{-- Tailwind (same config as frontend) --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      corePlugins: {
        // Prevent Tailwind preflight from overriding frontend page CSS
        // inside the Visual Editor canvas (notably the Menu header/nav).
        preflight: false,
      },
      theme: {
        extend: {
          colors: {
            forest: '#4B7A08',
            olive:  '#79CE1B',
            sage:   '#95F125',
            tan:    '#E5FA38',
            cream:  '#F4FAE8',
            dark:   '#1a2e04',
          },
          fontFamily: {
            display: ['"Anek Latin"', 'sans-serif'],
            body:    ['"Spline Sans"', 'sans-serif'],
          },
        }
      }
    }
  </script>

  {{-- Frontend styles (so the included page renders correctly) --}}
  <link rel="stylesheet" href="{{ asset('css/ultrafood/base.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/reveal.css') }}">
  @if (!\Illuminate\Support\Str::startsWith($activePage, 'menu'))
    <link rel="stylesheet" href="{{ asset('css/ultrafood/header.css') }}">
  @endif
  <link rel="stylesheet" href="{{ asset('css/ultrafood/hero.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/about.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/mission.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/strengths.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/brands.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/history.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/contact.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/footer.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/backtotop.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/responsive.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/map.css') }}">
  <link rel="stylesheet" href="{{ asset('css/menu/menu.css') }}">
  <link rel="stylesheet" href="{{ asset('css/menu/menu-connect.css') }}">
  <link rel="stylesheet" href="{{ asset('css/shared/connect-with-us.css') }}">
  @if (in_array($activePage, ['menu_recipe_category'], true))
    <link rel="stylesheet" href="{{ asset('css/menu/menu-recipelist.css') }}">
  @endif
  @if (in_array($activePage, ['menu_recipe_detail'], true))
    <link rel="stylesheet" href="{{ asset('css/menu/menu-recipe-detail.css') }}">
  @endif
  <link rel="stylesheet" href="{{ asset('css/nordic/nordic.css') }}?v={{ filemtime(public_path('css/nordic/nordic.css')) ?: 0 }}">
  @if (in_array($activePage, ['nordic_products'], true))
    <link rel="stylesheet" href="{{ asset('css/nordic/nordic-products.css') }}">
  @endif
  @if (in_array($activePage, ['nordic_product_details'], true))
    <link rel="stylesheet" href="{{ asset('css/nordic/nordic-product-details.css') }}?v={{ filemtime(public_path('css/nordic/nordic-product-details.css')) ?: 0 }}">
  @endif
  @if (in_array($activePage, ['nordic_recipes'], true))
    <link rel="stylesheet" href="{{ asset('css/nordic/nordic-recipes.css') }}?v={{ filemtime(public_path('css/nordic/nordic-recipes.css')) ?: 0 }}">
  @endif
  @if (in_array($activePage, ['nordic_recipe_details'], true))
    <link rel="stylesheet" href="{{ asset('css/nordic/nordic-recipe-details.css') }}?v={{ filemtime(public_path('css/nordic/nordic-recipe-details.css')) ?: 0 }}">
  @endif
  @if (!empty($isVisualEditorPage))
    <link rel="stylesheet" href="{{ asset('css/menu/menu-footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/menu/menu-category-grid.css') }}">
  @endif

  {{-- Visual editor styles --}}
  <link rel="stylesheet" href="{{ asset('css/admin/visual-editor.css') }}">
  {{-- Injected by JS from ALL_CONTENTS so field colors/fonts survive canvas innerHTML refresh (nested full-page include drops <style>) --}}
  <style id="ve-cms-field-styles"></style>
</head>
@php
  $veBodyClasses = collect([
    've-body',
    'font-body',
    've-page-' . $activePage,
  ]);

  if (\Illuminate\Support\Str::startsWith($activePage, 'menu')) {
    $veBodyClasses->push('ve-page-menu-family');
    $veBodyClasses->push('menu-page');
  }

  if (\Illuminate\Support\Str::startsWith($activePage, 'nordic')) {
    $veBodyClasses->push('nordic-page');
  }
@endphp
<body class="{{ $veBodyClasses->implode(' ') }}">

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- ADMIN BAR (fixed, above the page)                                      --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div id="ve-admin-bar" role="toolbar" aria-label="Visual Editor Admin Bar">

  {{-- Left: logo mark + page switcher --}}
  <div class="ve-bar-left">

    <span class="ve-bar-brand" title="Visual Editor">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
      </svg>
      <span>Visual Editor</span>
    </span>

    <div class="ve-separator"></div>

    {{-- Page switcher: label and dropdown side by side --}}
    <form method="GET" action="{{ route('admin.visual-editor') }}" id="ve-page-form" class="ve-page-form">
      <label for="ve-page-select" class="ve-bar-label">Page</label>
      <div class="ve-select-wrap">
        <select id="ve-page-select" name="page" onchange="document.getElementById('ve-page-form').submit()">
          @foreach ($pageGroups as $group => $groupPages)
            <option value="" disabled>-- {{ $group }} --</option>
            @foreach ($groupPages as $p)
              <option value="{{ $p['slug'] }}" {{ $activePage === $p['slug'] ? 'selected' : '' }}>
                {{ $p['label'] }}
              </option>
            @endforeach
          @endforeach
        </select>
        <svg class="ve-select-chevron" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
      </div>
    </form>

  </div>

  {{-- Right: live badge, old dashboard, account --}}
  <div class="ve-bar-right">

    <span class="ve-live-badge">
      <span class="ve-live-dot"></span>
      <span>Live</span>
    </span>

    <div class="ve-account-dropdown" id="ve-account-dropdown">
      <button
        type="button"
        class="ve-bar-btn ve-bar-btn--ghost ve-account-btn"
        id="ve-account-btn"
        aria-haspopup="menu"
        aria-expanded="false"
        aria-controls="ve-account-menu"
      >
        <span>Account</span>
        <svg class="ve-account-chevron" id="ve-account-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div class="ve-account-menu" id="ve-account-menu" role="menu" aria-labelledby="ve-account-btn">
        <a href="{{ route('admin.settings') }}" class="ve-account-menu-link" role="menuitem">
          Settings
        </a>
        <form method="POST" action="{{ route('admin.logout') }}" onsubmit="return window.confirm('Are you sure you want to log out?')">
          @csrf
          <button type="submit" class="ve-account-menu-logout" role="menuitem">
            Sign out
          </button>
        </form>
      </div>
    </div>

  </div>

</div>{{-- /#ve-admin-bar --}}


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- PAGE CANVAS — the actual frontend page rendered inside the editor      --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div id="ve-canvas">
  @if (!empty($isVisualEditorPage) && \Illuminate\Support\Str::startsWith($activePage, 'menu_cat_'))
    @include('frontend.menu-category-landing', [
      'categorySlug' => $categorySlug,
      'cmsPageSlug' => $cmsPageSlug,
      'lineItems' => $lineItems,
      'isVisualEditor' => true,
      'allContents' => $allContents,
      'categoryTitle' => $categoryTitle ?? '',
    ])
  @elseif ($activePage === 'menu_recipe_category')
    @include('frontend.menu-recipe-category', [
      'isVisualEditor' => true,
      'category' => $activeRecipeCategory,
      'recipes' => $recipes ?? collect(),
    ])
  @elseif ($activePage === 'menu_recipe_detail')
    @include('frontend.menu-recipe-detail', [
      'isVisualEditor' => true,
      'category' => $activeRecipeCategory,
      'recipe' => $activeRecipe,
    ])
  @elseif ($activePage === 'nordic_recipes')
    @include('frontend.nordic-recipes', [
      'isVisualEditor' => true,
      'recipes' => $nordicRecipes ?? collect(),
    ])
  @elseif ($activePage === 'nordic_recipe_details')
    @include('frontend.nordic-recipe-details', [
      'isVisualEditor' => true,
      'recipe' => $activeNordicRecipe,
    ])
  @else
    @include($cmsSchema[$activePage]['view'], ['isVisualEditor' => true])
  @endif
</div>


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL: TEXT EDITOR                                                      --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div id="ve-modal-text" class="ve-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="ve-text-modal-title" hidden>
  <div class="ve-modal">

    <div class="ve-modal-header">
      <h2 id="ve-text-modal-title" class="ve-modal-title">Edit Text</h2>
      <button class="ve-modal-close" data-modal="ve-modal-text" aria-label="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <div class="ve-modal-body">

      {{-- Dynamic field rows injected by JS --}}
      <div id="ve-text-fields"></div>

      {{-- Per-field style controls are injected dynamically by JS --}}

    </div>

    <div class="ve-modal-footer">
      <button class="ve-btn ve-btn--ghost" data-modal="ve-modal-text">Cancel</button>
      <button class="ve-btn ve-btn--outline-warn" id="ve-text-undo"
        title="Revert all fields to the values they had when you opened this modal">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="1 4 1 10 7 10"/>
          <path d="M3.51 15a9 9 0 1 0 .49-3.34"/>
        </svg>
        Undo Changes
      </button>
      <button class="ve-btn ve-btn--primary" id="ve-text-save">Save Changes</button>
    </div>

  </div>
</div>


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL: IMAGE UPLOAD                                                     --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL: STYLE (COLOR PICKER)                                             --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div id="ve-modal-style" class="ve-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="ve-style-modal-title" hidden>
  <div class="ve-modal">

    <div class="ve-modal-header">
      <h2 id="ve-style-modal-title" class="ve-modal-title">Edit Styles</h2>
      <button class="ve-modal-close" data-modal="ve-modal-style" aria-label="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <div class="ve-modal-body">
      <p class="ve-section-label">Colors</p>
      <div id="ve-style-fields" class="ve-fields"></div>
    </div>

    <div class="ve-modal-footer">
      <button class="ve-btn ve-btn--ghost" id="ve-style-cancel">Cancel</button>
      <button class="ve-btn ve-btn--outline-warn" id="ve-style-undo" title="Revert all fields to the values they had when you opened this modal">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.34"/></svg>
        Undo Changes
      </button>
      <button class="ve-btn ve-btn--primary" id="ve-style-save">Save Changes</button>
    </div>

  </div>
</div>

<div id="ve-modal-image" class="ve-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="ve-image-modal-title" hidden>
  <div class="ve-modal ve-modal--wide">

    <div class="ve-modal-header">
      <h2 id="ve-image-modal-title" class="ve-modal-title">Edit Image</h2>
      <button class="ve-modal-close" data-modal="ve-modal-image" aria-label="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <div class="ve-modal-body">

      {{-- Existing images grid --}}
      <p class="ve-section-label">Existing images</p>
      <div id="ve-image-grid" class="ve-image-grid">
        <span class="ve-image-grid-empty">Loading…</span>
      </div>

      {{-- Upload zone (hidden by JS for replace-only zones that already have an image) --}}
      <div id="ve-upload-section">
        <p class="ve-section-label" style="margin-top:1.25rem;">Upload new image</p>
        <div id="ve-drop-zone" class="ve-drop-zone" role="button" tabindex="0" aria-label="Drop an image here or click to select">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:#94a3b8;">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
          </svg>
          <span class="ve-drop-zone-text">Drag &amp; drop an image here, or <u>click to browse</u></span>
          <span id="ve-drop-zone-hint" class="ve-drop-zone-hint">JPG, PNG, WEBP or SVG &middot; up to 30 MB</span>
          <input type="file" id="ve-file-input" accept="image/jpeg,image/png,image/webp,image/svg+xml,.svg" class="ve-file-input" />
        </div>

        {{-- Preview before upload --}}
        <div id="ve-upload-preview" class="ve-upload-preview" hidden>
          <img id="ve-upload-preview-img" src="" alt="Preview" />
          <div class="ve-upload-preview-info">
            <span id="ve-upload-preview-name" class="ve-upload-preview-name"></span>
            <button id="ve-upload-clear" class="ve-upload-clear" aria-label="Remove selected file">&#x2715; Remove</button>
          </div>
        </div>
      </div>

      <div id="ve-image-dimensions-section" hidden>
        <p class="ve-section-label" style="margin-top:1.25rem;">Floater size</p>
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
          <div class="ve-field-group">
            <label for="ve-image-width-input">Width</label>
            <input type="text" id="ve-image-width-input" class="ve-input" placeholder="Default, e.g. 920px or 65vw" />
          </div>
          <div class="ve-field-group">
            <label for="ve-image-height-input">Height</label>
            <input type="text" id="ve-image-height-input" class="ve-input" placeholder="Default, e.g. 260px or auto" />
          </div>
        </div>
        <button class="ve-btn ve-btn--secondary" id="ve-image-dimensions-save" type="button">Save Size</button>
      </div>

    </div>

    <div class="ve-modal-footer">
      <button class="ve-btn ve-btn--ghost" data-modal="ve-modal-image">Cancel</button>
      <button class="ve-btn ve-btn--primary" id="ve-image-upload-btn" disabled>Upload &amp; Set Active</button>
    </div>

  </div>
</div>


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL: CONTACT INFO                                                     --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div id="ve-modal-contact" class="ve-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="ve-contact-modal-title" hidden>
  <div class="ve-modal">

    <div class="ve-modal-header">
      <h2 id="ve-contact-modal-title" class="ve-modal-title">Edit Contact Info</h2>
      <button class="ve-modal-close" data-modal="ve-modal-contact" aria-label="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <div class="ve-modal-body">

      {{-- Existing entries list --}}
      <p class="ve-section-label">Current entries</p>
      <div id="ve-contact-list" class="ve-contact-list">
        <span class="ve-contact-empty">No entries yet.</span>
      </div>

      {{-- Add / edit entry --}}
      <p class="ve-section-label" style="margin-top:1.25rem;">Add new entry</p>
      <div class="ve-contact-add-row">
        <div class="ve-field-group ve-field-group--grow">
          <label for="ve-contact-label-input">Label</label>
          <input type="text" id="ve-contact-label-input" placeholder="e.g. Main Office" class="ve-input" />
        </div>
        <div class="ve-field-group ve-field-group--grow">
          <label for="ve-contact-value-input">Value</label>
          <input type="text" id="ve-contact-value-input" placeholder="e.g. +63 2 8123 4567" class="ve-input" />
        </div>
        <button class="ve-btn ve-btn--primary ve-contact-add-btn" id="ve-contact-add">Add</button>
      </div>

    </div>

    <div class="ve-modal-footer">
      <button class="ve-btn ve-btn--ghost" data-modal="ve-modal-contact">Close</button>
    </div>

  </div>
</div>


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- TOAST NOTIFICATION                                                      --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div id="ve-toast" class="ve-toast" role="status" aria-live="polite" hidden></div>

<div id="ve-modal-menu-item" class="ve-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="ve-menu-item-title" hidden>
  <div class="ve-modal">
    <div class="ve-modal-header">
      <h2 id="ve-menu-item-title" class="ve-modal-title">Add product</h2>
      <button type="button" class="ve-modal-close" data-modal="ve-modal-menu-item" aria-label="Close">&times;</button>
    </div>
    <div class="ve-modal-body">
      <form id="ve-menu-item-form">
        <input type="hidden" name="category_slug" id="ve-menu-item-category-slug" value="" />
        <p class="ve-section-label">Product name</p>
        <textarea id="ve-menu-item-name-input" name="title" class="ve-textarea" rows="2" placeholder="Product name" required></textarea>
        <div id="ve-menu-item-flavor-wrap" hidden>
          <p class="ve-section-label" style="margin-top:1rem">Product type</p>
          <select id="ve-menu-item-flavor-type" name="flavor_type" class="ve-select" style="width:100%;">
            <option value="unflavored">Unflavored</option>
            <option value="flavored">Flavored</option>
          </select>
        </div>
        <div class="ve-field-style is-open" data-field-key="ve_menu_item_draft">
          <button type="button" class="ve-field-style-toggle" aria-expanded="true">Style options</button>
          <div class="ve-field-style-body">
            <div class="ve-style-row">
              <select class="ve-select" id="ve-menu-item-font" data-style-type="font" aria-label="Font"></select>
              <select class="ve-select" id="ve-menu-item-size" data-style-type="size" aria-label="Size"></select>
              <div class="ve-color-row">
                <input type="color" class="ve-color-input ve-field-color" id="ve-menu-item-color" value="#111111" aria-label="Color" />
                <span class="ve-color-value" id="ve-menu-item-color-val">#111111</span>
              </div>
            </div>
          </div>
        </div>
        <p class="ve-section-label" style="margin-top:1rem">Front image</p>
        <input type="file" name="image" id="ve-menu-item-image-input" accept="image/*" style="width:100%;" />
        <p class="ve-section-label" style="margin-top:1rem">Back image</p>
        <input type="file" name="back_image" id="ve-menu-item-back-image-input" accept="image/*" style="width:100%;" />
        <p class="ve-section-label" style="margin-top:1rem">Shopee Mall URL (optional)</p>
        <input
          type="url"
          id="ve-menu-item-shopee-mall-url"
          class="ve-input"
          placeholder="https://shopee.ph/..."
          style="width:100%;"
        />
      </form>
    </div>
    <div class="ve-modal-footer">
      <button type="button" class="ve-btn ve-btn--ghost" data-modal="ve-modal-menu-item">Cancel</button>
      <button type="button" class="ve-btn ve-btn--primary" id="ve-menu-item-save">Save</button>
    </div>
  </div>
</div>

<div id="ve-modal-recipe-category" class="ve-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="ve-recipe-cat-title" hidden>
  <div class="ve-modal">
    <div class="ve-modal-header">
      <h2 id="ve-recipe-cat-title" class="ve-modal-title">Recipe category</h2>
      <button type="button" class="ve-modal-close" data-modal="ve-modal-recipe-category" aria-label="Close">&times;</button>
    </div>
    <div class="ve-modal-body">
      <form id="ve-recipe-cat-form">
        <input type="hidden" id="ve-recipe-cat-id" value="" />
        <p class="ve-section-label">Name</p>
        <input type="text" id="ve-recipe-cat-name" class="ve-input" placeholder="e.g. Appetizers" required />
        <p class="ve-section-label" style="margin-top:1rem;">Slug (optional)</p>
        <input type="text" id="ve-recipe-cat-slug" class="ve-input" placeholder="e.g. appetizers" />
        <p class="ve-section-label" style="margin-top:1rem;">Icon (optional)</p>
        <input type="file" id="ve-recipe-cat-icon" accept="image/*" style="width:100%;" />
      </form>
    </div>
    <div class="ve-modal-footer">
      <button type="button" class="ve-btn ve-btn--ghost" data-modal="ve-modal-recipe-category">Cancel</button>
      <button type="button" class="ve-btn ve-btn--primary" id="ve-recipe-cat-save">Save</button>
    </div>
  </div>
</div>

<div id="ve-modal-recipe" class="ve-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="ve-recipe-title" hidden>
  <div class="ve-modal ve-modal--wide">
    <div class="ve-modal-header">
      <h2 id="ve-recipe-title" class="ve-modal-title">Recipe</h2>
      <button type="button" class="ve-modal-close" data-modal="ve-modal-recipe" aria-label="Close">&times;</button>
    </div>
    <div class="ve-modal-body">
      <form id="ve-recipe-form">
        <input type="hidden" id="ve-recipe-id" value="" />
        <input type="hidden" id="ve-recipe-category-id" value="" />
        <div class="ve-field-group" id="ve-recipe-name-group">
          <label for="ve-recipe-name">Name</label>
          <input type="text" id="ve-recipe-name" class="ve-input" placeholder="Recipe name" required />
        </div>
        <div class="ve-field-group" id="ve-recipe-slug-group">
          <label for="ve-recipe-slug">Slug (optional)</label>
          <input type="text" id="ve-recipe-slug" class="ve-input" placeholder="auto-generated if empty" />
        </div>
        <div class="ve-field-group" id="ve-recipe-image-group">
          <label for="ve-recipe-image">Image (optional)</label>
          <input type="file" id="ve-recipe-image" accept="image/*" style="width:100%;" />
        </div>
        <div class="ve-field-group">
          <label for="ve-recipe-description">Description</label>
          <textarea id="ve-recipe-description" class="ve-textarea" rows="3" placeholder="Short description"></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="ve-field-group">
            <label for="ve-recipe-servings">Servings</label>
            <input type="text" id="ve-recipe-servings" class="ve-input" placeholder="e.g. 2 servings" />
          </div>
          <div class="ve-field-group">
            <label for="ve-recipe-difficulty">Level</label>
            <select id="ve-recipe-difficulty" class="ve-select" style="width:100%;">
              <option value="Basic">Basic</option>
              <option value="Average">Average</option>
              <option value="Pro">Pro</option>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr;gap:12px;">
          <div class="ve-field-group" id="ve-recipe-calories-group">
            <label for="ve-recipe-calories">Calories</label>
            <input type="text" id="ve-recipe-calories" class="ve-input" placeholder="e.g. 310 kcal / serving" />
          </div>
        </div>
        <div class="ve-field-group">
          <label for="ve-recipe-ingredients">Ingredients (one per line)</label>
          <textarea id="ve-recipe-ingredients" class="ve-textarea" rows="5"></textarea>
          <div id="ve-recipe-ingredient-suggest" class="ve-mention-suggest" hidden></div>
          <div class="ve-recipe-ingredient-tools">
            <p class="ve-recipe-ingredient-tools-help">Type <code>@</code> in Ingredients to get product suggestions.</p>
          </div>
        </div>
        <div class="ve-field-group">
          <label for="ve-recipe-procedures">Procedures (one per line)</label>
          <textarea id="ve-recipe-procedures" class="ve-textarea" rows="5"></textarea>
          <div id="ve-recipe-procedure-suggest" class="ve-mention-suggest" hidden></div>
          <div class="ve-recipe-ingredient-tools">
            <p class="ve-recipe-ingredient-tools-help">Type <code>@</code> in Procedures to get product suggestions.</p>
          </div>
        </div>
      </form>
    </div>
    <div class="ve-modal-footer">
      <button type="button" class="ve-btn ve-btn--ghost" data-modal="ve-modal-recipe">Cancel</button>
      <button type="button" class="ve-btn ve-btn--primary" id="ve-recipe-save">Save</button>
    </div>
  </div>
</div>

<div id="ve-modal-nordic-recipe" class="ve-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="ve-nordic-recipe-title" hidden>
  <div class="ve-modal ve-modal--wide">
    <div class="ve-modal-header">
      <h2 id="ve-nordic-recipe-title" class="ve-modal-title">Nordic recipe</h2>
      <button type="button" class="ve-modal-close" data-modal="ve-modal-nordic-recipe" aria-label="Close">&times;</button>
    </div>
    <div class="ve-modal-body">
      <form id="ve-nordic-recipe-form">
        <input type="hidden" id="ve-nordic-recipe-id" value="" />
        <div class="ve-field-group">
          <label for="ve-nordic-recipe-name">Name</label>
          <input type="text" id="ve-nordic-recipe-name" class="ve-input" placeholder="Recipe name" required />
        </div>
        <div class="ve-field-group">
          <label for="ve-nordic-recipe-slug">Slug (optional)</label>
          <input type="text" id="ve-nordic-recipe-slug" class="ve-input" placeholder="auto-generated if empty" />
        </div>
        <div class="ve-field-group">
          <label for="ve-nordic-recipe-image">Image (optional)</label>
          <input type="file" id="ve-nordic-recipe-image" accept="image/*" style="width:100%;" />
        </div>
        <div class="ve-field-group">
          <label for="ve-nordic-recipe-description">Description</label>
          <textarea id="ve-nordic-recipe-description" class="ve-textarea" rows="3" placeholder="Short description"></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="ve-field-group">
            <label for="ve-nordic-recipe-servings">Servings</label>
            <input type="text" id="ve-nordic-recipe-servings" class="ve-input" placeholder="e.g. 2 servings" />
          </div>
          <div class="ve-field-group">
            <label for="ve-nordic-recipe-calories">Calories</label>
            <input type="text" id="ve-nordic-recipe-calories" class="ve-input" placeholder="e.g. 310 kcal / serving" />
          </div>
        </div>
        <div class="ve-field-group">
          <label for="ve-nordic-recipe-ingredients">Ingredients (one per line)</label>
          <textarea id="ve-nordic-recipe-ingredients" class="ve-textarea" rows="5"></textarea>
          <div id="ve-nordic-recipe-ingredient-suggest" class="ve-mention-suggest" hidden></div>
          <div class="ve-recipe-ingredient-tools">
            <p class="ve-recipe-ingredient-tools-help">Type <code>@</code> in Ingredients to get product suggestions.</p>
          </div>
        </div>
        <div class="ve-field-group">
          <label for="ve-nordic-recipe-procedures">Procedures (one per line)</label>
          <textarea id="ve-nordic-recipe-procedures" class="ve-textarea" rows="5"></textarea>
          <div id="ve-nordic-recipe-procedure-suggest" class="ve-mention-suggest" hidden></div>
          <div class="ve-recipe-ingredient-tools">
            <p class="ve-recipe-ingredient-tools-help">Type <code>@</code> in Procedures to get product suggestions.</p>
          </div>
        </div>
      </form>
    </div>
    <div class="ve-modal-footer">
      <button type="button" class="ve-btn ve-btn--ghost" data-modal="ve-modal-nordic-recipe">Cancel</button>
      <button type="button" class="ve-btn ve-btn--primary" id="ve-nordic-recipe-save">Save</button>
    </div>
  </div>
</div>


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- DATA PASSED TO JAVASCRIPT                                                --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@php
  $vePageIds = [
    'menu' => (int) (\App\Models\Page::where('slug', 'menu')->value('id') ?? 0),
    'nordic' => (int) (\App\Models\Page::where('slug', 'nordic')->value('id') ?? 0),
    'nordic_products' => (int) (\App\Models\Page::where('slug', 'nordic_products')->value('id') ?? 0),
    'nordic_product_details' => (int) (\App\Models\Page::where('slug', 'nordic_product_details')->value('id') ?? 0),
    'nordic_recipes' => (int) (\App\Models\Page::where('slug', 'nordic_recipes')->value('id') ?? 0),
    'nordic_recipe_details' => (int) (\App\Models\Page::where('slug', 'nordic_recipe_details')->value('id') ?? 0),
  ];
@endphp
<script>
  const CMS_SCHEMA   = @json($cmsSchema);
  const ACTIVE_PAGE  = @json($activePage);
  const PAGE_ID      = {{ $pageId }};
  const PAGE_IDS     = @json($vePageIds);
  const ALL_MEDIA    = @json($allMedia);
  const ALL_CONTENTS = @json($allContents);
  const ALL_CONTACTS = @json($allContacts);

  // Stable DB IDs for each history background image (keyed 0-6).
  // Populated by ExistingResourcesSeeder; immune to positional-index drift.
  const HIST_BG_IDS = @json(
    collect(range(0, 6))->mapWithKeys(
      fn ($i) => [$i => (int) ($allContents->firstWhere('key', 'history_bg_id_' . $i)['value'] ?? 0)]
    )
  );

  const ROUTES = {
    mediaUpload:        "{{ route('admin.media.upload') }}",
    mediaUpdate:        "{{ url('admin/media') }}",
    mediaSetActive:     "{{ url('admin/media') }}",
    mediaDestroy:       "{{ url('admin/media') }}",
    contentSave:        "{{ route('admin.content.save') }}",
    contentUpdateField: "{{ route('admin.content.updateField') }}",
    contentUploadVideoField: "{{ route('admin.content.uploadVideoField') }}",
    visualEditorContents: "{{ route('admin.visual-editor.contents') }}",
    contentDestroy:        "{{ url('admin/content') }}",
    contentDestroyByKey:   "{{ route('admin.content.destroyByKey') }}",
    contactSave:        "{{ route('admin.contact.save') }}",
    contactUpdate:      "{{ url('admin/contact') }}",
    contactDestroy:     "{{ url('admin/contact') }}",
    menuCategoryItemsStore:  "{{ route('admin.menu-category-items.store') }}",
    menuCategoryItemsUpdate: "{{ url('admin/menu-category-items') }}",
    menuCategoryItemsMove: "{{ url('admin/menu-category-items') }}",
    menuCategoryItemsDestroy: "{{ url('admin/menu-category-items') }}",
    menuRecipeCategoriesStore:  "{{ route('admin.menu-recipe-categories.store') }}",
    menuRecipeCategoriesUpdate: "{{ url('admin/menu-recipe-categories') }}",
    menuRecipeCategoriesDestroy: "{{ url('admin/menu-recipe-categories') }}",
    menuRecipesStore:  "{{ route('admin.menu-recipes.store') }}",
    menuRecipesUpdate: "{{ url('admin/menu-recipes') }}",
    menuRecipesDestroy: "{{ url('admin/menu-recipes') }}",
    nordicRecipesStore:  "{{ route('admin.nordic-recipes.store') }}",
    nordicRecipesUpdate: "{{ url('admin/nordic-recipes') }}",
    nordicRecipesDestroy: "{{ url('admin/nordic-recipes') }}",
  };

  const VISUAL_EDITOR_BASE = "{{ route('admin.visual-editor') }}";
  const VISUAL_PAGE_URLS = {
    ultrafood: "{{ route('home') }}",
    menu: "{{ route('menu') }}",
    nordic: "{{ route('nordic') }}",
    menu_recipe_category: "{{ route('menu.recipes.category', ['category' => 'appetizers']) }}",
    menu_recipe_detail: "{{ route('menu.recipes.detail', ['category' => 'appetizers', 'recipe' => 'sample']) }}",
    nordic_products: "{{ route('nordic.products') }}",
    nordic_product_details: "{{ route('nordic.products.details', ['product' => 'whole-grain-oats']) }}",
    nordic_recipes: "{{ route('nordic.recipes') }}",
    nordic_recipe_details: "{{ route('nordic.recipes.details', ['recipe' => 'oat-congee']) }}",
  };

  @php
    $veRecipeState = [
      'categories' => ($recipeCategories ?? collect())->map(function ($c) {
        return [
          'id' => $c->id,
          'name' => $c->name,
          'slug' => $c->slug,
          'icon_filename' => $c->icon_filename,
          'icon_url' => $c->iconUrl(),
          'sort_order' => $c->sort_order,
        ];
      })->values(),
      'activeCategory' => $activeRecipeCategory ? [
        'id' => $activeRecipeCategory->id,
        'name' => $activeRecipeCategory->name,
        'slug' => $activeRecipeCategory->slug,
        'icon_filename' => $activeRecipeCategory->icon_filename,
        'icon_url' => $activeRecipeCategory->iconUrl(),
        'sort_order' => $activeRecipeCategory->sort_order,
      ] : null,
      'recipes' => ($recipes ?? collect())->map(function ($r) {
        return [
          'id' => $r->id,
          'category_id' => $r->category_id,
          'name' => $r->name,
          'slug' => $r->slug,
          'image_filename' => $r->image_filename,
          'image_url' => $r->imageUrl(),
          'description' => $r->description,
          'servings' => $r->servings,
          'difficulty' => $r->difficulty,
          'calories' => $r->calories,
          'ingredients' => $r->ingredients ?? [],
          'procedures' => $r->procedures ?? [],
          'sort_order' => $r->sort_order,
        ];
      })->values(),
      'activeRecipe' => $activeRecipe ? [
        'id' => $activeRecipe->id,
        'category_id' => $activeRecipe->category_id,
        'name' => $activeRecipe->name,
        'slug' => $activeRecipe->slug,
        'image_filename' => $activeRecipe->image_filename,
        'image_url' => $activeRecipe->imageUrl(),
        'description' => $activeRecipe->description,
        'servings' => $activeRecipe->servings,
        'difficulty' => $activeRecipe->difficulty,
        'calories' => $activeRecipe->calories,
        'ingredients' => $activeRecipe->ingredients ?? [],
        'procedures' => $activeRecipe->procedures ?? [],
        'sort_order' => $activeRecipe->sort_order,
      ] : null,
    ];
  @endphp

  const VE_RECIPE_STATE = @json($veRecipeState);
  const VE_MENU_PRODUCT_REFERENCES = @json(($menuProductReferences ?? collect())->values());
  @php
    $nordicReferencePageOrder = array_values(array_filter([
      (int) ($vePageIds['nordic_products'] ?? 0),
      (int) ($vePageIds['nordic_product_details'] ?? 0),
      (int) ($vePageIds['nordic'] ?? 0),
    ]));
    $resolveNordicRefTitle = function (string $key, string $fallback) use ($allContents, $nordicReferencePageOrder): string {
      if ($key === '') {
        return $fallback;
      }

      foreach ($nordicReferencePageOrder as $pid) {
        $match = $allContents->first(function ($row) use ($key, $pid) {
          return (string) ($row['key'] ?? '') === $key
            && (int) ($row['page_id'] ?? 0) === (int) $pid;
        });
        if ($match && trim((string) ($match['value'] ?? '')) !== '') {
          return trim((string) $match['value']);
        }
      }

      $firstMatch = $allContents->firstWhere('key', $key);
      if ($firstMatch && trim((string) ($firstMatch['value'] ?? '')) !== '') {
        return trim((string) $firstMatch['value']);
      }

      return $fallback;
    };
    $veNordicProductReferences = collect(config('cms.nordic_products.zones.nordic_products_selected_product.products', []))
      ->map(function ($product) use ($resolveNordicRefTitle) {
        $slug = trim((string) ($product['slug'] ?? ''));
        $titleKey = (string) ($product['title_key'] ?? '');
        $fallbackTitle = trim((string) ($product['title_default'] ?? ''));
        $title = $resolveNordicRefTitle($titleKey, $fallbackTitle);
        return [
          'slug' => $slug,
          'title' => $title,
        ];
      })
      ->filter(fn ($row) => !empty($row['slug']) && !empty($row['title']))
      ->values();
  @endphp
  const VE_NORDIC_PRODUCT_REFERENCES = @json($veNordicProductReferences);

  @php
    $veNordicRecipeState = [
      'recipes' => ($nordicRecipes ?? collect())->map(function ($r) {
        return [
          'id' => $r->id,
          'name' => $r->name,
          'slug' => $r->slug,
          'image_filename' => $r->image_filename,
          'image_url' => $r->imageUrl(),
          'description' => $r->description,
          'servings' => $r->servings,
          'calories' => $r->calories,
          'ingredients' => $r->ingredients ?? [],
          'procedures' => $r->procedures ?? [],
          'sort_order' => $r->sort_order,
        ];
      })->values(),
      'activeRecipe' => $activeNordicRecipe ? [
        'id' => $activeNordicRecipe->id,
        'name' => $activeNordicRecipe->name,
        'slug' => $activeNordicRecipe->slug,
        'image_filename' => $activeNordicRecipe->image_filename,
        'image_url' => $activeNordicRecipe->imageUrl(),
        'description' => $activeNordicRecipe->description,
        'servings' => $activeNordicRecipe->servings,
        'calories' => $activeNordicRecipe->calories,
        'ingredients' => $activeNordicRecipe->ingredients ?? [],
        'procedures' => $activeNordicRecipe->procedures ?? [],
        'sort_order' => $activeNordicRecipe->sort_order,
      ] : null,
    ];
  @endphp

  const VE_NORDIC_RECIPE_STATE = @json($veNordicRecipeState);
</script>

{{-- Frontend JS (page-specific) --}}
@if ($activePage === 'ultrafood')
  <script src="{{ asset('js/ultrafood.js') }}"></script>
@elseif (in_array($activePage, ['menu', 'menu_item_details', 'menu_recipe_category', 'menu_recipe_detail'], true))
  <script src="{{ asset('js/menu.js') }}"></script>
@elseif (!empty($isVisualEditorPage))
  <script src="{{ asset('js/menu.js') }}"></script>
@elseif ($activePage === 'nordic')
  <script src="{{ asset('js/nordic.js') }}"></script>
@elseif ($activePage === 'nordic_products')
  <script src="{{ asset('js/nordic/nordic-products.js') }}"></script>
@elseif ($activePage === 'nordic_product_details')
  <script src="{{ asset('js/nordic/nordic-product-details.js') }}"></script>
@elseif ($activePage === 'nordic_recipes')
  <script src="{{ asset('js/nordic/nordic-recipes.js') }}"></script>
@elseif ($activePage === 'nordic_recipe_details')
  <script src="{{ asset('js/nordic/nordic-recipe-details.js') }}"></script>
@endif

{{-- Visual editor JS --}}
<script src="{{ asset('js/admin/visual-editor.js') }}?v={{ @filemtime(public_path('js/admin/visual-editor.js')) }}"></script>

</body>
</html>
