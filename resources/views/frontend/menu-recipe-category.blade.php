<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Menu — Recipes — {{ $category->name ?? 'Category' }}</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo/e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Glory:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=ZCOOL+XiaoWei&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/menu/menu-recipelist.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/menu/menu-category-grid.css') }}" />
  <style>
        .menu-bg-image {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: -1;
  pointer-events: none;
}
    .recipe-list-wrap {
      position: relative;
      z-index: 2;
      max-width: 1200px;
      margin: 0 auto;
      padding: 22px var(--content-inline-pad) 56px;
    }
    .menu-product-list-breadcrumbs {
      margin: 4px 0 20px;
      padding: 0 0 12px;
      border-bottom: 1px solid rgba(130, 12, 12, 0.22);
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      font-size: 20px;
      color: var(--muted, #64748b);
      font-family: var(--font-body, "Asap", sans-serif);
    }

    @media (max-width: 640px) {
      .menu-product-list-breadcrumbs { font-size: 18px; }
    }
    .menu-product-list-breadcrumbs a {
      color: var(--primary, #820c0c);
      text-decoration: none;
    }
    .menu-product-list-breadcrumbs a:hover { text-decoration: underline; }
    .menu-product-list-breadcrumbs .is-current {
      color: var(--primary, #820c0c);
      font-weight: 700;
    }
    .recipe-page-head { margin: 0 0 26px; max-width: 700px; }
    .recipe-page-label {
      margin: 0 0 10px;
      font-size: 12px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      font-weight: 700;
      color: var(--primary, #820c0c);
    }
    .recipe-title-row { display: flex; align-items: center; gap: 12px; margin: 0 0 10px; }
    .recipe-title-row img {
      width: clamp(50px, 5vw, 72px);
      height: clamp(50px, 5vw, 72px);
      object-fit: contain;
      border-radius: 0;
      border: 0;
      background: transparent;
      box-shadow: none;
      flex: 0 0 auto;
    }
    .recipe-title-row h1 {
      margin: 0;
      font-family: "Glory", sans-serif;
      font-size: clamp(2.15rem, 4.2vw, 3.4rem);
      line-height: 1.1;
      text-transform: uppercase;
      color: var(--text, #0f172a);
      font-weight: 700;
    }
    .recipe-page-subtitle {
      margin: 0;
      font-size: 16px;
      color: var(--muted, #64748b);
    }
    .ve-body .recipe-title-row > .ve-edit-zone[data-zone-key="menu_recipe_category_title"] {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      width: fit-content;
      max-width: 100%;
      z-index: 3;
    }
    .ve-body .recipe-title-row > .ve-edit-zone[data-zone-key="menu_recipe_category_title"] > .ve-pencil-btn {
      top: -10px;
      right: -10px;
    }
    .menu-recipe-category-page .menu-category-grid {
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      justify-content: start;
      gap: 1.5rem;
      align-items: stretch;
      padding-top: 0.35rem;
    }
    .recipe-list-wrap .menu-category-grid-cell {
      padding-top: 0;
      justify-content: stretch;
      position: relative;
    }
    .menu-recipe-category-page .menu-category-card {
      background: var(--menu-category-card-bg, #ffffff);
      border: 1px solid var(--menu-category-card-border, #e2e8f0);
      border-radius: 24px;
      height: 100%;
      min-height: 100%;
      position: relative;
      isolation: isolate;
      overflow: hidden;
      box-shadow: var(--menu-category-card-shadow, 0 10px 28px rgba(0, 0, 0, 0.08));
      transition: transform 0.35s ease, box-shadow 0.35s ease;
      text-decoration: none;
      color: inherit;
      padding: 0;
    }
    .menu-recipe-category-page .menu-category-card::before {
      content: "";
      position: absolute;
      inset: 0;
      border-radius: inherit;
      background: linear-gradient(180deg, hsl(0 72% 45% / 0.04), transparent 52%);
      opacity: 1;
      transition: opacity 220ms ease;
      pointer-events: none;
      z-index: 0;
    }
    .menu-recipe-category-page .menu-category-card::after {
      content: "";
      position: absolute;
      inset: -10px;
      border-radius: inherit;
      background: radial-gradient(circle at 50% 28%, hsl(0 72% 45% / 0.16), transparent 58%);
      opacity: 0.5;
      filter: blur(20px);
      transform: scale(0.94);
      transition: opacity 220ms ease, transform 220ms ease;
      pointer-events: none;
      z-index: 0;
    }
    .menu-recipe-category-page .menu-category-card:hover {
      transform: translateY(-4px);
      box-shadow:
        0 30px 60px -24px hsl(0 72% 45% / 0.34),
        0 16px 30px -18px hsl(20 20% 15% / 0.22);
      border-color: hsl(0 72% 45% / 0.22);
    }
    .menu-recipe-category-page .menu-category-card-showcase {
      position: relative;
      width: 100%;
      aspect-ratio: 1 / 1;
      overflow: hidden;
      display: block;
      background: #f3f4f6;
      min-height: 320px;
      z-index: 1;
      margin-bottom: -1px;
    }
    .menu-recipe-category-page .menu-category-card-thumb {
      width: 100% !important;
      height: 100% !important;
      margin: 0;
      max-width: none;
      aspect-ratio: auto;
      border-radius: 0;
      position: relative;
      background: #f3f4f6;
      border: 0;
      box-shadow: none;
      display: block;
      transform: translateY(0);
      transition: transform 0.7s ease;
      overflow: hidden !important;
      z-index: 1;
      min-height: 0;
    }
    .menu-recipe-category-page .menu-category-card-thumb::after {
      display: none;
    }
    /* Nordic-style: keep media fully inside the card (no cut-out glow / overlay layers). */
    .menu-recipe-category-page .menu-category-card::before,
    .menu-recipe-category-page .menu-category-card::after {
      content: none;
    }
    .menu-recipe-category-page .menu-category-card-showcase .menu-category-card-glow {
      display: none;
    }
    .menu-recipe-category-page .menu-category-card-thumb img {
      transform: scale(1);
      width: 100% !important;
      height: 100% !important;
      max-width: none;
      max-height: none;
      border-radius: 0;
      object-fit: cover !important;
      object-position: center 120%;
      display: block;
      transition: transform 0.7s ease;
      box-shadow: none;
      filter: none;
      position: relative;
      z-index: 1;
    }
    .menu-recipe-category-page .menu-category-card:hover .menu-category-card-thumb img {
      transform: scale(1);
    }
    .menu-recipe-category-page .menu-category-card:hover .menu-category-card-thumb,
    .menu-recipe-category-page .menu-category-card:focus-visible .menu-category-card-thumb {
      transform: none;
    }
    .menu-recipe-category-page .menu-category-card:focus-visible .menu-category-card-thumb img {
      transform: scale(1);
    }
    .menu-recipe-category-page .menu-category-card-copy {
      display: flex;
      flex-direction: column;
      align-items: stretch;
      justify-content: flex-start;
      gap: 0.12rem;
      padding: 0.9rem 1.25rem 1.1rem;
      text-align: left;
      width: 100%;
      min-height: 0;
      position: relative;
      z-index: 1;
    }
    .menu-recipe-category-page .menu-category-card-name {
      margin: 0;
      font-family: "Asap", "Glory", sans-serif;
      font-size: 1.08rem;
      line-height: 1.18;
      color: var(--menu-category-name, #111) !important;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      font-weight: 700;
      transition: color 220ms ease;
      min-height: 0;
    }
    .menu-recipe-category-page .menu-category-card:hover .menu-category-card-name { color: var(--menu-category-primary, #820c0c) !important; }
    .recipe-card-desc {
      margin: 0;
      font-size: 0.8rem;
      line-height: 1.35;
      color: var(--menu-category-muted, #6b7280);
    }
    .recipe-card-link {
      margin-top: 0;
      font-size: 0.95rem;
      font-weight: 600;
      font-family: var(--font-body, "Asap", sans-serif);
      color: var(--menu-category-primary, #820c0c);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      width: 100%;
      line-height: 1.15;
    }
    .recipe-card-link span[aria-hidden="true"] {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      min-width: 32px;
      border-radius: 999px;
      background: hsl(0 72% 45% / 0.08);
      transition: transform 0.25s ease, background-color 0.25s ease;
    }
    .menu-recipe-category-page .menu-category-card:hover .recipe-card-link span[aria-hidden="true"] {
      transform: translateX(4px);
      background: hsl(0 72% 45% / 0.14);
    }
    .recipe-list-wrap .menu-category-card-glow {
      display: block;
      position: absolute;
      inset: 3rem;
      border-radius: 999px;
      background: hsl(0 72% 45% / 0.08);
      filter: blur(36px);
      opacity: 1;
      z-index: 0;
      transition: transform 0.7s ease, background-color 0.7s ease, opacity 0.7s ease;
    }
    .menu-recipe-category-page .menu-category-card:hover .menu-category-card-glow { transform: scale(1.25); background: hsl(0 72% 45% / 0.12); }
    .recipe-list-wrap .menu-category-add-tile {
      min-height: 280px;
    }
    @media (max-width: 900px) {
      .menu-recipe-category-page .menu-category-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
      }
      .menu-recipe-category-page .menu-category-card {
        border-radius: 14px;
      }
      .menu-recipe-category-page .menu-category-card-showcase {
        aspect-ratio: 1 / 1;
        min-height: 0;
      }
      .menu-recipe-category-page .menu-category-card-thumb {
        height: 100%;
        aspect-ratio: auto;
      }
      .menu-recipe-category-page .menu-category-card-thumb img {
        width: 100%;
        min-width: 100%;
        max-width: none;
        height: 100%;
        min-height: 100%;
        max-height: none;
        object-fit: cover;
        object-position: center 120%;
        background: transparent;
        display: block;
        transform: scale(1);
      }
      .menu-recipe-category-page .menu-category-card:hover .menu-category-card-thumb img {
        transform: scale(1);
      }
      .menu-recipe-category-page .menu-category-card-copy {
        padding: 0.6rem 0.55rem 0.65rem;
        min-height: auto;
        gap: 0.1rem;
        align-items: flex-start;
      }
      .menu-recipe-category-page .menu-category-card-name {
        font-size: 24px !important;
        line-height: 1.05;
        letter-spacing: 0.03em;
        width: 100%;
        align-self: stretch;
        text-align: left !important;
        position: relative;
        top: 0.3rem;
        left: -0.45rem;
      }
      .recipe-card-desc {
        display: none;
      }
      .recipe-card-link {
        margin-top: 0;
        font-size: 0.78rem;
        gap: 10px;
      }
      .recipe-card-link span[aria-hidden="true"] {
        width: 28px;
        height: 28px;
        min-width: 28px;
      }
    }
    @media (max-width: 640px) {
      .menu-recipe-category-page .menu-category-card-name {
        font-size: 18px !important;
        line-height: 1.05;
      }
      .recipe-card-link {
        font-size: 15px;
      }
      .recipe-title-row h1 { font-size: 2.1rem; }
    }
  </style>
</head>
<body class="menu-page menu-category-page">
  @include('frontend.menu-bg-image')
  @include('frontend.menu-header')

  @php
    $cv = fn (string $key, string $default = '') =>
      (isset($allContents) ? (($allContents->firstWhere('key', $key)['value'] ?? null) ?? $default) : $default);
    $icon = $category?->iconUrl();
    $recipes = $recipes ?? collect();
    $categoryLabelKey = $category?->id ? 'menu_nav_recipe_category_'.$category->id.'_label' : '';
    $categoryName = $categoryLabelKey !== '' ? $cv($categoryLabelKey, $category->name) : $category->name;
    $headerLabelKey = 'menu_recipe_category_header_label';
    $headerLabel = $cv($headerLabelKey, 'Browse by category');
    $headerSubtitleKey = 'menu_recipe_category_header_subtitle';
    $headerSubtitle = $cv($headerSubtitleKey, 'Handpicked Menu recipes in this collection.');
    $cardCtaKey = 'menu_recipe_category_card_cta';
    $cardCta = $cv($cardCtaKey, 'Explore recipe');
  @endphp

  <div class="menu-page menu-category-page menu-recipe-category-page">
  <main class="recipe-list-wrap">
    <p class="menu-product-list-breadcrumbs">
      <a href="{{ route('menu') }}">Home</a>
      <span aria-hidden="true">&gt;</span>
      <span class="is-current">{{ $categoryName }}</span>
    </p>

    <div class="recipe-page-head">
      <div class="recipe-title-row">
        @if ($icon)
          <img src="{{ $icon }}" alt="" aria-hidden="true" />
        @endif
        <h1 @if($categoryLabelKey !== '') data-ve-field="{{ $categoryLabelKey }}" @endif>{{ $categoryName }}</h1>
      </div>
    </div>

    <section
      class="menu-category-grid"
      id="menuRecipeGrid"
      data-category-slug="{{ $category->slug }}"
      data-is-visual="{{ !empty($isVisualEditor) ? '1' : '0' }}"
      aria-label="Recipes"
    >
      @foreach ($recipes as $recipe)
        @php
          $recipeNameKey = 'mr_'.$recipe->id.'_name';
          $recipeName = $cv($recipeNameKey, $recipe->name);
        @endphp
        <div class="menu-category-grid-cell">
          @if (!empty($isVisualEditor))
            <button type="button" class="menu-category-card-del ve-recipe-del-recipe" data-id="{{ $recipe->id }}" aria-label="Delete recipe">&times;</button>
          @endif
          <a
            href="{{ route('menu.recipes.detail', ['category' => $category->slug, 'recipe' => $recipe->slug]) }}"
            class="menu-category-card"
            data-recipe-id="{{ $recipe->id }}"
            aria-label="Open {{ $recipeName }}"
          >
            <div class="menu-category-card-showcase">
              <span class="menu-category-card-glow" aria-hidden="true"></span>
              <figure class="menu-category-card-thumb">
                <img src="{{ $recipe->imageUrl() ?: asset('images/menu/Menu-Gallery-1.jpg') }}" alt="{{ $recipeName }} image" loading="lazy" />
              </figure>
            </div>
            <div class="menu-category-card-copy">
              <h3 class="menu-category-card-name" data-ve-field="{{ $recipeNameKey }}">{{ $recipeName }}</h3>
              <span class="recipe-card-link"><span data-ve-field="{{ $cardCtaKey }}">{{ $cardCta }}</span><span aria-hidden="true">&rarr;</span></span>
            </div>
          </a>
        </div>
      @endforeach

      @if (!empty($isVisualEditor))
        <div class="menu-category-add-tile ve-recipe-add-recipe" role="button" tabindex="0" aria-label="Add recipe">
          <span class="menu-category-add-icon" aria-hidden="true">+</span>
          <span class="menu-category-add-label">Add recipe</span>
        </div>
      @endif
    </section>
  </main>

  @include('frontend.menu-footer')

  <button id="menuScrollTopBtn" class="menu-scroll-top" type="button" aria-label="Back to top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });">
    <span class="menu-scroll-top-icon" aria-hidden="true">↑</span>
  </button>
  </div>
  <script src="{{ asset('js/menu.js') }}"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const btn = document.getElementById('menuScrollTopBtn');
      const footer = document.querySelector('.menu-footer');
      if (!btn) return;

      const syncMenuTopBtn = function () {
        btn.classList.toggle('is-visible', window.scrollY > 400);
        let footerOffset = 0;
        if (footer) {
          const footerRect = footer.getBoundingClientRect();
          const overlap = window.innerHeight - footerRect.top;
          footerOffset = overlap > 0 ? overlap + 16 : 0;
        }
        btn.style.setProperty('--scroll-top-footer-offset', `${Math.max(0, footerOffset)}px`);
        const atPageEnd = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
        btn.style.setProperty('--scroll-top-end-shift', atPageEnd ? '20px' : '0px');
      };

      window.addEventListener('scroll', syncMenuTopBtn, { passive: true });
      window.addEventListener('resize', syncMenuTopBtn, { passive: true });
      syncMenuTopBtn();
    });
  </script>
</body>
</html>
