<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Menu - Recipe - {{ $recipe->name ?? 'Details' }}</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo/e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap:wght@400;500;600;700&family=Glory:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/menu/menu.css') }}" />
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
    *, *::before, *::after { box-sizing: border-box; }

    .menu-recipe-page {
      --rp-red: #820c0c;
      --rp-red-bright: #c70000;
      --rp-gold: #e6ba40;
      --rp-navy: #223947;
      --rp-bg: var(--bg, hsl(30 25% 97%));
      --rp-fg: var(--rp-navy);
      --rp-primary: var(--rp-red);
      --rp-primary-fg: var(--surface, hsl(30 25% 97%));
      --rp-muted: #5f6f86;
      --rp-border: var(--line, hsl(30 15% 88%));
      --rp-cream: var(--surface, hsl(38 40% 95%));
      --rp-warm: var(--rp-primary);
      --rp-link: var(--rp-primary);
      --rp-link-hover: var(--rp-red-bright);
      --rp-copy: hsl(20 14% 34%);
      --rp-radius: 0.75rem;
      --rp-display: "Playfair Display", serif;
      --rp-body: var(--font-glory, "Glory", sans-serif);
      --rp-nav-style: var(--font-glory, "Glory", sans-serif);
      --rp-card-bg-top: var(--menu-panel-bg-top, hsl(30 30% 98%));
      --rp-card-bg-bottom: var(--menu-panel-bg-bottom, hsl(36 42% 94%));
      --rp-card-stroke: color-mix(in srgb, var(--rp-gold) 38%, white 62%);
      --rp-heading: var(--rp-navy);
      --rp-text-soft: var(--menu-panel-text, hsl(216 21% 43%));
      --rp-accent-soft: var(--menu-panel-accent, hsl(24 45% 44%));
      color: var(--rp-fg);
      font-family: var(--rp-body);
    }

    .menu-recipe-page .menu-footer { margin-top: auto; }
    .menu-recipe-page .page-content { padding-top: 24px; padding-bottom: 44px; }

    .rp-container { max-width: 1200px; margin: 0 auto; padding: 0 var(--content-inline-pad); }

    .rp-breadcrumb {
      margin: 0 0 20px;
      padding: 0 0 12px;
      border-bottom: 1px solid color-mix(in srgb, var(--rp-red) 30%, white 70%);
      color: var(--rp-muted);
      font-size: 20px;
      font-family: var(--font-body, "Asap", sans-serif);
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
    }
    .rp-breadcrumb a { color: var(--rp-link); text-decoration: none; }
    .rp-breadcrumb a:hover { color: var(--rp-link-hover); text-decoration: underline; }
    .rp-breadcrumb .is-current { color: var(--rp-link); font-weight: 700; }

    .rp-hero { padding: 8px 0 10px; }
    .rp-hero-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      grid-template-areas:
        "image title"
        "image info";
      column-gap: 2.5rem;
      row-gap: 0.10in;
      align-items: stretch;
    }
    /* Prevent grid items (especially images) from overflowing and overlapping adjacent columns. */
    .rp-hero-grid > * { min-width: 0; }

    .rp-title-area {
      grid-area: title;
      display: grid;
      align-content: start;
      align-self: start;
      row-gap: 0.25in;
    }
    .rp-copy-desktop {
      display: grid;
      row-gap: 0.25in;
      align-content: start;
    }
    .rp-copy-mobile {
      display: none;
      grid-area: copy;
      align-content: start;
      align-self: start;
      row-gap: 0.14in;
    }

    .rp-image-wrap {
      grid-area: image;
      position: relative;
      border: 1px solid rgba(230, 186, 64, 0.28);
      border-radius: 24px;
      overflow: hidden;
      background: #000;
      box-shadow: 0 16px 34px rgba(20, 34, 43, 0.18);
      aspect-ratio: 1 / 1;
      max-width: 520px;
      height: auto;
      justify-self: start;
    }
    .rp-image-wrap img { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center center; background: transparent; }

    .rp-info { grid-area: info; display: flex; flex-direction: column; gap: 1.1rem; align-self: start; }
    .rp-category {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.15em;
      color: var(--rp-red-bright);
    }
    .rp-title {
      margin: 0;
      font-family: var(--rp-nav-style);
      font-weight: 800;
      letter-spacing: 0.01em;
      text-transform: uppercase;
      font-size: clamp(2.6rem, 4.9vw, 4.3rem);
      line-height: 1.02;
    }
    .rp-description {
      margin: 0;
      font-size: 1.2rem;
      color: var(--rp-text-soft);
      line-height: 1.72;
    }
    .rp-featured-ingredient-product {
      margin: 0;
      line-height: 1.45;
      font-size: 1.5rem;
    }
    .rp-meta-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.75rem;
    }
    .rp-meta-card {
      background: linear-gradient(180deg, var(--rp-card-bg-top) 0%, var(--rp-card-bg-bottom) 100%);
      border-radius: var(--rp-radius);
      padding: 0.95rem;
      text-align: center;
      border: 1px solid var(--rp-card-stroke);
    }
    .rp-meta-icon {
      width: 20px;
      height: 20px;
      margin: 0 auto 0.4rem;
      color: var(--rp-red);
    }
    .rp-meta-label {
      font-family: var(--rp-nav-style) !important;
      font-size: 1.24rem !important;
      font-weight: 700 !important;
      letter-spacing: 0.02em !important;
      text-transform: none !important;
      color: var(--rp-text-soft) !important;
    }
    .rp-meta-value,
    .rp-meta-value--level {
      font-family: var(--rp-nav-style) !important;
      font-size: 2.7rem !important;
      line-height: 1.1 !important;
      font-weight: 700 !important;
      letter-spacing: normal !important;
      color: var(--rp-heading) !important;
    }
    .rp-actions {
      display: flex;
      gap: 0.7rem;
      align-items: center;
      padding-top: 0.45rem;
    }
    .rp-actions--desktop-only { display: flex; }
    .rp-actions--mobile-only { display: none; }
    .rp-title-edit {
      position: relative;
      display: inline-flex;
      align-items: flex-start;
      max-width: 100%;
    }
    .rp-title-edit .rp-title {
      margin-right: 3.5rem;
    }
    .rp-title-edit .ve-pencil-btn {
      top: 0;
      right: 0;
    }
    .rp-title-edit:hover .ve-pencil-btn,
    .rp-title-edit:focus-within .ve-pencil-btn {
      opacity: 1;
      pointer-events: auto;
      transform: translateY(0);
    }
    .rp-btn-icon {
      width: 52px;
      height: 52px;
      border-radius: 0.75rem;
      border: 1px solid var(--rp-card-stroke);
      background: linear-gradient(180deg, var(--rp-card-bg-top) 0%, var(--rp-card-bg-bottom) 100%);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--rp-text-soft);
      font-family: var(--font-body, "Asap", sans-serif);
      cursor: pointer;
    }
    .rp-btn-icon:hover {
      background: color-mix(in srgb, var(--rp-gold) 18%, white 82%);
      color: var(--rp-red-bright);
      border-color: color-mix(in srgb, var(--rp-gold) 54%, white 46%);
    }
    .rp-btn-icon svg { width: 20px; height: 20px; }

    .rp-separator {
      border: 0;
      border-top: 1px solid color-mix(in srgb, var(--rp-red) 30%, white 70%);
      margin: 2rem 0;
    }

    .rp-content-grid {
      display: grid;
      grid-template-columns: 1fr 2fr;
      gap: 2.3rem;
      padding-bottom: 2.6rem;
    }

    .rp-ingredients-panel {
      background: linear-gradient(180deg, var(--rp-card-bg-top) 0%, var(--rp-card-bg-bottom) 100%);
      border-radius: 1rem;
      padding: 1.75rem;
      position: sticky;
      top: 2rem;
      align-self: start;
      border: 1px solid var(--rp-card-stroke);
    }
    .rp-ingredients-panel h2,
    .rp-instructions h2 {
      font-family: var(--rp-nav-style);
      font-weight: 800;
      letter-spacing: 0.01em;
      text-transform: uppercase;
      color: var(--rp-heading);
      font-size: 1.9rem;
    }
    .rp-ingredients-panel h2 { margin-bottom: 1rem; }

    .rp-ingredient-list { list-style: none; margin: 0; padding: 0; }
    .rp-ingredient-list li {
      display: block;
      padding: 0.45rem 0;
      font-size: 1.12rem;
      color: var(--rp-copy);
      line-height: 1.58;
    }
    .rp-ingredient-product {
      display: inline;
      margin: 0;
      padding: 0 0.08em;
      border-radius: 0;
      border: 0;
      background: color-mix(in srgb, var(--rp-gold) 22%, white 78%);
      color: var(--rp-heading);
      text-decoration: none;
      font-weight: 600;
      font-size: 1em;
      line-height: inherit;
      vertical-align: baseline;
      box-decoration-break: clone;
      -webkit-box-decoration-break: clone;
      transition: background 140ms ease, color 140ms ease;
    }
    .rp-ingredient-product:hover {
      background: color-mix(in srgb, var(--rp-gold) 34%, white 66%);
      color: var(--rp-red);
    }
    .rp-ingredient-product:focus-visible {
      outline: 2px solid color-mix(in srgb, var(--rp-gold) 58%, white 42%);
      outline-offset: 2px;
    }
    .rp-ingredient-product--missing {
      background: color-mix(in srgb, var(--rp-border) 16%, white 84%);
      color: var(--rp-muted);
    }
    .rp-product-preview-tooltip {
      position: fixed;
      z-index: 1200;
      left: 0;
      top: 0;
      width: min(260px, calc(100vw - 24px));
      padding: 10px;
      border-radius: 12px;
      border: 1px solid color-mix(in srgb, var(--rp-border) 75%, #223947 25%);
      background: rgba(255, 255, 255, 0.98);
      box-shadow: 0 16px 28px rgba(20, 34, 43, 0.22);
      pointer-events: none;
      opacity: 0;
      visibility: hidden;
      transform: translateY(4px);
      transition: opacity 120ms ease, transform 120ms ease, visibility 120ms ease;
    }
    .rp-product-preview-tooltip.is-visible {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    .rp-product-preview-tooltip__image {
      display: none;
      width: 100%;
      aspect-ratio: 1 / 1;
      object-fit: contain;
      object-position: center center;
      border-radius: 10px;
      background: linear-gradient(145deg, #ffffff 0%, #f1f4f6 100%);
      border: 1px solid color-mix(in srgb, var(--rp-border) 70%, #223947 30%);
      margin-bottom: 8px;
    }
    .rp-product-preview-tooltip.has-image .rp-product-preview-tooltip__image {
      display: block;
    }
    .rp-product-preview-tooltip__name {
      margin: 0;
      color: var(--rp-heading);
      font-family: var(--rp-body);
      font-size: 15px;
      font-weight: 700;
      line-height: 1.25;
    }
    .rp-product-preview-tooltip__meta {
      margin: 4px 0 0;
      color: var(--rp-text-soft);
      font-family: var(--rp-body);
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    .rp-panel-separator {
      border: 0;
      border-top: 1px solid color-mix(in srgb, var(--rp-red) 30%, white 70%);
      margin: 1.3rem 0;
    }

    .rp-nutrition-title {
      font-family: var(--rp-nav-style);
      font-size: 1.95rem;
      font-weight: 800;
      letter-spacing: 0.01em;
      text-transform: uppercase;
      margin-bottom: 1rem;
      line-height: 1.2;
      color: var(--rp-heading);
    }

    .rp-mini-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.75rem;
    }
    .rp-mini-card {
      background: linear-gradient(180deg, var(--rp-card-bg-top) 0%, var(--rp-card-bg-bottom) 100%);
      border-radius: var(--rp-radius);
      padding: 0.95rem;
      text-align: center;
      border: 1px solid var(--rp-card-stroke);
    }
    .rp-mini-icon {
      width: 20px;
      height: 20px;
      margin: 0 auto 0.4rem;
      color: var(--rp-red);
    }
    .rp-mini-label {
      font-family: var(--rp-nav-style) !important;
      font-size: 1rem !important;
      font-weight: 700 !important;
      letter-spacing: 0.02em !important;
      text-transform: none !important;
      color: var(--rp-text-soft) !important;
    }
    .rp-mini-value {
      font-family: var(--rp-nav-style) !important;
      font-size: 2rem !important;
      line-height: 1.1 !important;
      font-weight: 700 !important;
      letter-spacing: normal !important;
      color: var(--rp-heading) !important;
    }

    .rp-instructions h2 { margin-bottom: 1rem; }
    .rp-steps { list-style: none; margin: 0; padding: 0; }
    .rp-steps li { display: flex; align-items: flex-start; gap: 1.1rem; margin-bottom: 1.6rem; }

    .rp-step-num {
      width: 1.82rem;
      height: 1.82rem;
      flex-shrink: 0;
      margin-top: 0.14rem;
      border-radius: 50%;
      background: color-mix(in srgb, var(--rp-red) 92%, #000 8%);
      border: 1px solid color-mix(in srgb, var(--rp-red-bright) 68%, white 32%);
      box-shadow: 0 0 0 2px hsl(0 72% 45% / 0.14);
      color: #fff;
      display: grid;
      place-items: center;
      font-family: var(--font-body, "Asap", sans-serif);
      font-weight: 700;
      font-size: 0.82rem;
      line-height: 1;
      text-align: center;
    }
    .rp-step-num[data-step] { color: transparent; position: relative; }
    .rp-step-num[data-step]::before {
      content: attr(data-step);
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      color: #fff;
      font: inherit;
      line-height: 1;
      transform: translateY(0.04em);
    }
    .rp-step-text {
      padding-top: 0;
      line-height: 1.72;
      color: var(--rp-copy);
      font-size: 1.3rem;
    }

    @media (max-width: 1024px) {
      .rp-container { max-width: 960px; }
      .rp-hero-grid { column-gap: 1.8rem; row-gap: 0.25in; }
      .rp-title { font-size: clamp(2rem, 5vw, 3.2rem); }
      .rp-description { font-size: 0.98rem; line-height: 1.6; }
      .rp-featured-ingredient-product { font-size: 1.34rem; }
      .rp-content-grid { gap: 1.6rem; }
      .rp-product-preview-tooltip { display: none !important; }
    }

    @media (hover: none), (pointer: coarse) {
      .rp-product-preview-tooltip { display: none !important; }
    }

    @media (max-width: 768px) {
      .menu-recipe-page .page-content { padding-top: 18px; padding-bottom: 32px; }
      .rp-breadcrumb { margin-bottom: 14px; font-size: 18px; }
      .rp-hero-grid {
        grid-template-columns: 1fr;
        grid-template-areas:
          "title"
          "image"
          "copy"
          "info";
        gap: 1.2rem;
      }
      .rp-image-wrap {
        height: auto;
        aspect-ratio: 1 / 1;
        max-width: min(100%, 420px);
        margin-inline: auto;
      }
      .rp-image-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center center;
        display: block;
      }
      .rp-title { font-size: 1.85rem; line-height: 1.06; }
      .rp-copy-desktop { display: none; }
      .rp-copy-mobile { display: grid; row-gap: 0.14in; }
      .rp-featured-ingredient-product { font-size: 1.24rem; }
      .rp-meta-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.65rem; }
      .rp-meta-card { padding: 0.8rem; }
      .rp-meta-label { font-size: 0.88rem !important; }
      .rp-meta-value,
      .rp-meta-value--level { font-size: 1.45rem !important; }
      .rp-actions { flex-wrap: wrap; }
      .rp-actions--desktop-only { display: none; }
      .rp-actions--mobile-only {
        display: flex;
        margin-top: 0.12rem;
      }
      .rp-separator { margin: 1.4rem 0; }
      .rp-content-grid { grid-template-columns: 1fr; gap: 0.72rem; padding-bottom: 0; }
      .rp-ingredients-panel { position: static; padding: 1.1rem; }
      .rp-ingredients-panel .rp-panel-separator { display: none; }
      .rp-ingredients-panel h2,
      .rp-instructions h2,
      .rp-nutrition-title { font-size: 1.6rem; }
      .rp-steps li { gap: 0.85rem; margin-bottom: 1.1rem; }
      .rp-step-num { width: 1.64rem; height: 1.64rem; margin-top: 0.1rem; font-size: 0.74rem; }
      .rp-ingredient-list li { font-size: 1.04rem; }
      .rp-step-text { padding-top: 0; line-height: 1.62; font-size: 1.14rem; }
    }

    @media (max-width: 480px) {
      .menu-recipe-page .page-content { padding-top: 14px; padding-bottom: 24px; }
      .rp-container { padding-inline: 14px; }
      .rp-breadcrumb { gap: 6px; padding-bottom: 10px; font-size: 16px; }
      .rp-title { font-size: 1.5rem; }
      .rp-copy-mobile { row-gap: 0.12in; }
      .rp-description { font-size: 0.88rem; line-height: 1.55; }
      .rp-featured-ingredient-product { font-size: 1.14rem; line-height: 1.4; }
      .rp-meta-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.6rem; }
      .rp-btn-icon { width: 46px; height: 46px; }
      .rp-ingredients-panel { padding: 0.95rem; border-radius: 0.8rem; }
      .rp-ingredient-list li { font-size: 1rem; padding: 0.36rem 0; }
      .rp-steps li { margin-bottom: 0.56rem; }
      .rp-steps li:last-child { margin-bottom: 0; }
      .rp-step-num { width: 1.52rem; height: 1.52rem; margin-top: 0.08rem; font-size: 0.68rem; }
      .rp-step-text { font-size: 1.08rem; }
    }
  </style>
</head>
<body class="menu-page menu-category-page menu-recipe-detail-page menu-recipe-page">
  @include('frontend.menu-bg-image')
  <div class="menu-page menu-category-page menu-recipe-detail-page menu-recipe-page">
  @include('frontend.menu-header')

  @php
    use Illuminate\Support\Facades\DB;
    use App\Models\Page;

    if (!isset($allContents)) {
      $pageId = Page::where('slug', 'menu_recipe_detail')->value('id');
      $allContents = DB::table('text_content')
        ->when($pageId, fn ($query) => $query->where('page_id', $pageId))
        ->get()
        ->map(fn ($row) => ['key' => $row->key, 'value' => $row->value, 'section' => 'text_content']);
    }

    $cv = fn (string $key, string $default = '') =>
      ($allContents->firstWhere('key', $key)['value'] ?? null) ?? $default;

    $fontsToLoad = collect($allContents)
      ->filter(fn ($item) => str_ends_with($item['key'] ?? '', '_font'))
      ->pluck('value')
      ->filter()
      ->unique()
      ->values();

    $fieldStyles = [];
    foreach (config('cms.menu_recipe_detail.zones', []) as $zone) {
      if (($zone['type'] ?? '') !== 'text') continue;
      foreach (($zone['fields'] ?? []) as $fieldKey) {
        $parts = [];
        $color = $cv($fieldKey . '_color', '');
        $font = $cv($fieldKey . '_font', '');
        $size = $cv($fieldKey . '_font_size', '');
        if ($color) $parts[] = "color:{$color} !important";
        if ($font) $parts[] = "font-family:'{$font}',sans-serif";
        if ($size) $parts[] = "font-size:{$size}";
        if ($parts) {
          $fieldStyles[($zone['selector'] ?? '') . ' [data-ve-field="' . $fieldKey . '"]'] = implode(';', $parts);
        }
      }
    }

    $ingredientsRaw = $recipe?->ingredients ?? [];
    $proceduresRaw = $recipe?->procedures ?? [];

    if (is_string($ingredientsRaw)) {
      $ingredientsRaw = preg_split('/\r\n|\r|\n/', $ingredientsRaw) ?: [];
    }
    if (is_string($proceduresRaw)) {
      $proceduresRaw = preg_split('/\r\n|\r|\n/', $proceduresRaw) ?: [];
    }

    $ingredients = is_array($ingredientsRaw)
      ? array_values(array_filter(array_map(static fn ($line) => trim((string) $line), $ingredientsRaw), static fn ($line) => $line !== ''))
      : [];
    $procedures = is_array($proceduresRaw)
      ? array_values(array_filter(array_map(static fn ($line) => trim((string) $line), $proceduresRaw), static fn ($line) => $line !== ''))
      : [];

    $recipeImage = $recipe?->imageUrl() ?: asset('images/menu/Menu-Gallery-1.jpg');
    $recipeName = $recipe->name ?? 'Recipe';
    $categoryName = $category->name ?? 'Recipes';
    $categorySlug = $category->slug ?? 'recipes';
    $recipeDescription = (string) ($recipe->description ?? '');
    $recipeSlug = (string) ($recipe->slug ?? '');
    $recipePdfUrl = $recipeSlug !== '' ? route('menu.recipes.pdf', ['category' => $categorySlug, 'recipe' => $recipeSlug]) : '';
    $serves = (string) ($recipe->servings ?? '4');
    $difficulty = (string) ($recipe->difficulty ?? 'Easy');
    $nutritionCalories = (string) ($recipe->calories ?? '480 kcal');
    $metaIngredientsLabel = $cv('menu_recipe_detail_meta_ingredients_label', 'Ingredients');
    $metaStepsLabel = $cv('menu_recipe_detail_meta_steps_label', 'Steps');
    $metaServesLabel = $cv('menu_recipe_detail_meta_serves_label', 'Serves');
    $metaLevelLabel = $cv('menu_recipe_detail_meta_level_label', 'Level');
    $ingredientsTitle = $cv('menu_recipe_detail_ingredients_title', 'Ingredients');
    $instructionsTitle = $cv('menu_recipe_detail_instructions_title', 'Instructions');
    $nutritionProtein = '14g';
    $nutritionCarbs = '62g';
    $nutritionFat = '20g';
    $menuProductLinkMap = is_array($menuProductLinkMap ?? null) ? $menuProductLinkMap : [];
    $ingredientMentionPattern = '/\[@([^\]]+)\]\(menu-product:(\d+)\)/';
    $buildMenuMentionMetaAttrs = static function ($product, string $fallbackLabel = 'Product'): string {
      $displayName = trim((string) ($product['title'] ?? $fallbackLabel));
      if ($displayName === '') {
        $displayName = 'Product';
      }

      $categorySlug = trim((string) ($product['category_slug'] ?? ''));
      $categoryLabel = $categorySlug !== ''
        ? \Illuminate\Support\Str::headline(str_replace('-', ' ', $categorySlug))
        : '';
      $price = trim((string) ($product['price'] ?? ''));
      $meta = $price !== '' ? $price : $categoryLabel;
      $image = trim((string) ($product['image'] ?? ''));

      $attrs = ' data-product-name="' . e($displayName) . '"';
      if ($meta !== '') {
        $attrs .= ' data-product-meta="' . e($meta) . '"';
      }
      if ($image !== '') {
        $attrs .= ' data-product-image="' . e($image) . '"';
      }

      return $attrs;
    };
    $featuredIngredientProductHtml = '';
    foreach ($ingredients as $ingredientLine) {
      if (preg_match($ingredientMentionPattern, (string) $ingredientLine, $featuredMatch) === 1) {
        $featuredLabel = trim((string) ($featuredMatch[1] ?? ''));
        $featuredProductId = (int) ($featuredMatch[2] ?? 0);
        $featuredProduct = $menuProductLinkMap[$featuredProductId] ?? null;
        $featuredDisplayLabel = $featuredLabel !== ''
          ? $featuredLabel
          : trim((string) ($featuredProduct['title'] ?? 'Product #' . $featuredProductId));

        if (is_array($featuredProduct) && !empty($featuredProduct['category_slug'])) {
          $featuredUrl = route('menu.products.item', [
            'category' => (string) $featuredProduct['category_slug'],
            'item' => (string) $featuredProductId,
          ]);
          $metaAttrs = $buildMenuMentionMetaAttrs($featuredProduct, $featuredDisplayLabel);
          $featuredIngredientProductHtml = '<a class="rp-ingredient-product rp-product-preview-trigger"' . $metaAttrs . ' href="' . e($featuredUrl) . '">' . e($featuredDisplayLabel) . '</a>';
        } else {
          $featuredIngredientProductHtml = '<span class="rp-ingredient-product rp-ingredient-product--missing">' . e($featuredDisplayLabel) . '</span>';
        }
        break;
      }
    }
    $renderMentionedTextHtml = static function (string $line) use ($ingredientMentionPattern, $menuProductLinkMap, $buildMenuMentionMetaAttrs): string {
      $cursor = 0;
      $html = '';
      $line = (string) $line;
      $lineLength = strlen($line);

      while (preg_match($ingredientMentionPattern, $line, $matches, PREG_OFFSET_CAPTURE, $cursor) === 1) {
        $full = $matches[0][0] ?? '';
        $start = (int) ($matches[0][1] ?? 0);
        $label = trim((string) ($matches[1][0] ?? ''));
        $productId = (int) ($matches[2][0] ?? 0);

        if ($start > $cursor) {
          $html .= e(substr($line, $cursor, $start - $cursor));
        }

        $product = $menuProductLinkMap[$productId] ?? null;
        $displayLabel = $label !== ''
          ? $label
          : trim((string) ($product['title'] ?? 'Product #' . $productId));

        if (is_array($product) && !empty($product['category_slug'])) {
          $url = route('menu.products.item', [
            'category' => (string) $product['category_slug'],
            'item' => (string) $productId,
          ]);
          $metaAttrs = $buildMenuMentionMetaAttrs($product, $displayLabel);
          $html .= '<a class="rp-ingredient-product rp-product-preview-trigger"' . $metaAttrs . ' href="' . e($url) . '">' . e($displayLabel) . '</a>';
        } else {
          $html .= '<span class="rp-ingredient-product rp-ingredient-product--missing">' . e($displayLabel) . '</span>';
        }

        $cursor = $start + strlen($full);
      }

      if ($cursor < $lineLength) {
        $html .= e(substr($line, $cursor));
      }

      return $html;
    };
  @endphp

  @foreach ($fontsToLoad as $fontFamily)
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $fontFamily) }}:wght@400;500;600;700&display=swap">
  @endforeach

  @if (!empty($fieldStyles))
    <style id="menu-recipe-detail-cms-text-styles">
      @foreach ($fieldStyles as $selector => $css)
        {!! $selector !!} { {!! $css !!} }
      @endforeach
    </style>
  @endif

  <main class="page-content">
    <section class="rp-container">
      <p class="rp-breadcrumb">
        <a href="{{ route('menu') }}">Home</a>
        <span aria-hidden="true">&gt;</span>
        <a href="{{ route('menu.recipes.category', ['category' => $categorySlug]) }}">{{ $categoryName }}</a>
        <span aria-hidden="true">&gt;</span>
        <span class="is-current">{{ $recipeName }}</span>
      </p>

      <section class="rp-hero">
        <div class="rp-hero-grid">
          <div class="rp-image-wrap">
            <img src="{{ $recipeImage }}" alt="{{ $recipeName }}" loading="lazy" />
          </div>

          <div class="rp-title-area">
          @if (!empty($isVisualEditor) && !empty($recipe?->id))
            <div class="rp-title-edit ve-edit-target" data-recipe-id="{{ $recipe->id }}">
              <h1 class="rp-title">{{ $recipeName }}</h1>
              <button
                type="button"
                class="ve-pencil-btn ve-recipe-edit-recipe"
                data-id="{{ $recipe->id }}"
                aria-label="Edit recipe details for {{ $recipeName }}"
              >
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                <span>Edit</span>
              </button>
            </div>
          @else
            <h1 class="rp-title">{{ $recipeName }}</h1>
          @endif
          <div class="rp-copy-desktop">
            @if ($featuredIngredientProductHtml !== '')
              <p class="rp-featured-ingredient-product">{!! $featuredIngredientProductHtml !!}</p>
            @endif
            <p class="rp-description">{{ $recipeDescription }}</p>
          </div>
          </div>

          <div class="rp-copy-mobile">
            @if ($featuredIngredientProductHtml !== '')
              <p class="rp-featured-ingredient-product">{!! $featuredIngredientProductHtml !!}</p>
            @endif
            <p class="rp-description">{{ $recipeDescription }}</p>
          </div>

          <div class="rp-info">
            <div class="rp-meta-grid">
              <div class="rp-meta-card">
                <svg class="rp-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <div class="rp-meta-label" data-ve-field="menu_recipe_detail_meta_ingredients_label">{{ $metaIngredientsLabel }}</div>
                <div class="rp-meta-value">{{ count($ingredients) }}</div>
              </div>
              <div class="rp-meta-card">
                <svg class="rp-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                <div class="rp-meta-label" data-ve-field="menu_recipe_detail_meta_steps_label">{{ $metaStepsLabel }}</div>
                <div class="rp-meta-value">{{ count($procedures) }}</div>
              </div>
              <div class="rp-meta-card">
                <svg class="rp-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <div class="rp-meta-label" data-ve-field="menu_recipe_detail_meta_serves_label">{{ $metaServesLabel }}</div>
                <div class="rp-meta-value">{{ $serves }}</div>
              </div>
              <div class="rp-meta-card">
                <svg class="rp-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/><line x1="6" y1="17" x2="18" y2="17"/></svg>
                <div class="rp-meta-label" data-ve-field="menu_recipe_detail_meta_level_label">{{ $metaLevelLabel }}</div>
                <div class="rp-meta-value rp-meta-value--level">{{ $difficulty }}</div>
              </div>
            </div>

          </div>
        </div>
      </section>

      <hr class="rp-separator" />

      <section class="rp-content-grid">
        <aside class="rp-ingredients-panel">
          <h2 data-ve-field="menu_recipe_detail_ingredients_title">{{ $ingredientsTitle }}</h2>
          <ul class="rp-ingredient-list">
            @foreach ($ingredients as $ingredient)
              <li>{!! $renderMentionedTextHtml($ingredient) !!}</li>
            @endforeach
          </ul>
          <hr class="rp-panel-separator" />
          <div class="rp-actions rp-actions--desktop-only">
            <button type="button" class="rp-btn-icon" data-recipe-action="share" aria-label="Share Recipe">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </button>
            <button type="button" class="rp-btn-icon" data-recipe-action="pdf" data-pdf-url="{{ $recipePdfUrl }}" aria-label="Download Recipe PDF" @disabled($recipePdfUrl === '')>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            </button>
          </div>
        </aside>

        <article class="rp-instructions">
          <h2 data-ve-field="menu_recipe_detail_instructions_title">{{ $instructionsTitle }}</h2>
          <ol class="rp-steps">
            @foreach ($procedures as $index => $procedure)
              <li>
                <div class="rp-step-num" data-step="{{ $index + 1 }}">{{ $index + 1 }}</div>
                <div class="rp-step-text">{!! $renderMentionedTextHtml($procedure) !!}</div>
              </li>
            @endforeach
          </ol>

        </article>
      </section>

      <div class="rp-actions rp-actions--mobile-only">
        <button type="button" class="rp-btn-icon" data-recipe-action="share" aria-label="Share Recipe">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
        </button>
        <button type="button" class="rp-btn-icon" data-recipe-action="pdf" data-pdf-url="{{ $recipePdfUrl }}" aria-label="Download Recipe PDF" @disabled($recipePdfUrl === '')>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        </button>
      </div>

    </section>
  </main>

  @include('frontend.menu-footer')

  <button id="menuScrollTopBtn" class="menu-scroll-top" type="button" aria-label="Back to top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });">
    <span class="menu-scroll-top-icon" aria-hidden="true">&uarr;</span>
  </button>

  <div id="menuIngredientProductPreview" class="rp-product-preview-tooltip" aria-hidden="true">
    <img class="rp-product-preview-tooltip__image" data-preview-image alt="" hidden />
    <p class="rp-product-preview-tooltip__name" data-preview-name></p>
    <p class="rp-product-preview-tooltip__meta" data-preview-meta></p>
  </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const btn = document.getElementById('menuScrollTopBtn');
      const footer = document.querySelector('.menu-footer');
      const previewTooltip = document.getElementById('menuIngredientProductPreview');
      const previewImage = previewTooltip ? previewTooltip.querySelector('[data-preview-image]') : null;
      const previewName = previewTooltip ? previewTooltip.querySelector('[data-preview-name]') : null;
      const previewMeta = previewTooltip ? previewTooltip.querySelector('[data-preview-meta]') : null;
      const ingredientProductTriggers = Array.from(document.querySelectorAll('.rp-ingredient-list .rp-product-preview-trigger[data-product-name]'));
      const recipePdfBtns = Array.from(document.querySelectorAll('[data-recipe-action="pdf"]'));
      const recipeShareBtns = Array.from(document.querySelectorAll('[data-recipe-action="share"]'));

      const supportsDesktopHoverPreview = function () {
        if (!window.matchMedia('(min-width: 1025px)').matches) return false;
        if (!window.matchMedia('(hover: hover)').matches) return false;
        if (!window.matchMedia('(pointer: fine)').matches) return false;
        return true;
      };

      const hideIngredientPreview = function () {
        if (!previewTooltip) return;
        previewTooltip.classList.remove('is-visible');
        previewTooltip.classList.remove('has-image');
        previewTooltip.setAttribute('aria-hidden', 'true');
      };

      recipePdfBtns.forEach((button) => {
        button.addEventListener('click', function () {
          const pdfUrl = button.dataset.pdfUrl || '';
          if (!pdfUrl) return;
          window.location.href = pdfUrl;
        });
      });

      recipeShareBtns.forEach((button) => {
        button.addEventListener('click', async function () {
          const shareData = {
            title: document.title,
            text: 'Check out this Menu recipe.',
            url: window.location.href,
          };

          try {
            if (navigator.share) {
              await navigator.share(shareData);
              return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
              await navigator.clipboard.writeText(window.location.href);
              const previous = button.getAttribute('aria-label') || 'Share Recipe';
              button.setAttribute('aria-label', 'Link copied');
              window.setTimeout(function () {
                button.setAttribute('aria-label', previous);
              }, 1600);
            }
          } catch (error) {
            console.error('Menu recipe share failed:', error);
          }
        });
      });

      const positionIngredientPreview = function (clientX, clientY) {
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

      const showIngredientPreview = function (target, event) {
        if (!previewTooltip) return;
        if (!supportsDesktopHoverPreview()) return;

        const name = String(target.getAttribute('data-product-name') || target.textContent || 'Product').trim();
        const meta = String(target.getAttribute('data-product-meta') || '').trim();
        const image = String(target.getAttribute('data-product-image') || '').trim();

        if (previewName) {
          previewName.textContent = name || 'Product';
        }
        if (previewMeta) {
          previewMeta.textContent = meta;
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

      if (btn) {
        window.addEventListener('scroll', syncMenuTopBtn, { passive: true });
        window.addEventListener('resize', syncMenuTopBtn, { passive: true });
        syncMenuTopBtn();
      }

      if (previewTooltip && ingredientProductTriggers.length > 0) {
        ingredientProductTriggers.forEach(function (trigger) {
          trigger.addEventListener('mouseenter', function (event) {
            showIngredientPreview(trigger, event);
          });

          trigger.addEventListener('mousemove', function (event) {
            if (!previewTooltip.classList.contains('is-visible')) return;
            positionIngredientPreview(event.clientX, event.clientY);
          });

          trigger.addEventListener('mouseleave', hideIngredientPreview);
        });

        window.addEventListener('scroll', hideIngredientPreview, { passive: true });
        window.addEventListener('resize', hideIngredientPreview, { passive: true });
      }
    });
  </script>
</body>
</html>
