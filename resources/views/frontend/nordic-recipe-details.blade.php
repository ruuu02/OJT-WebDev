<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  @php
    use App\Models\Page;
    use Illuminate\Support\Facades\DB;

    $recipeData = $recipe ?? null;
    $isVisualEditor = !empty($isVisualEditor);
    $sharedNordicPageId = Page::where('slug', 'nordic')->value('id');
    $productsPageId = Page::where('slug', 'nordic_products')->value('id');
    $detailPageId = Page::where('slug', 'nordic_product_details')->value('id');
    $nordicPageBgUrl = 'https://images.unsplash.com/photo-1517673400267-0251440c45dc?w=2200&q=80&auto=format&fit=crop';

    if ($sharedNordicPageId) {
      $mediaId = trim((string) DB::table('text_content')
        ->where('page_id', $sharedNordicPageId)
        ->where('key', 'media_id_nordic_page_bg')
        ->value('value'));

      if ($mediaId !== '') {
        $filename = DB::table('product_images')
          ->where('id', $mediaId)
          ->value('filename');

        if ($filename) {
          $nordicPageBgUrl = asset('images/banner/'.$filename);
        }
      }
    }

    $rName = is_object($recipeData) ? ($recipeData->name ?? '') : ($recipeData['name'] ?? '');
    $rSlug = is_object($recipeData) ? ($recipeData->slug ?? '') : ($recipeData['slug'] ?? '');
    $rDesc = is_object($recipeData) ? ($recipeData->description ?? '') : ($recipeData['description'] ?? '');
    $rServ = is_object($recipeData) ? ($recipeData->servings ?? '') : ($recipeData['servings'] ?? '');
    $rCal  = is_object($recipeData) ? ($recipeData->calories ?? '') : ($recipeData['calories'] ?? '');
    $rSubtitle = $rDesc ? mb_strimwidth(strip_tags((string) $rDesc), 0, 70, '...') : 'Warm, hearty & endlessly customizable';
    $rIng = is_object($recipeData) ? ($recipeData->ingredients ?? []) : ($recipeData['ingredients'] ?? []);
    $rProc = is_object($recipeData) ? ($recipeData->procedures ?? []) : ($recipeData['procedures'] ?? []);

    if (is_string($rIng)) {
      $rIng = preg_split('/\r\n|\r|\n/', $rIng) ?: [];
    }
    if (is_string($rProc)) {
      $rProc = preg_split('/\r\n|\r|\n/', $rProc) ?: [];
    }

    $rIng = is_array($rIng) ? array_values(array_filter(array_map(static fn ($line) => trim((string) $line), $rIng), static fn ($line) => $line !== '')) : [];
    $rProc = is_array($rProc) ? array_values(array_filter(array_map(static fn ($line) => trim((string) $line), $rProc), static fn ($line) => $line !== '')) : [];

    $wholeGrainRolledOatRecipes = [
      'oat congee',
      'oat soup',
      'oat fried rice',
      'garlic mushroom oats',
      'egg & spinach oats',
      'chicken oat porridge',
      'tomato basil savory oats',
    ];
    $quickCookOatmealRecipes = [
      'basic overnight oats',
      'banana peanut butter oats',
      'apple cinnamon oatmeal',
      'chocolate cocoa oats',
      'mango coconut oats',
      'strawberry yogurt oats',
    ];
    $instantOatmealRecipes = [
      'oatmeal cookies',
      'oat brownies',
      'oat pancakes',
      'oat waffles',
    ];

    $normalizedRecipeName = mb_strtolower(trim((string) $rName));
    $oatIngredientLabel = null;
    $oatProductSlug = null;
    if (in_array($normalizedRecipeName, $wholeGrainRolledOatRecipes, true)) {
      $oatIngredientLabel = 'Whole Grain Rolled Oats';
      $oatProductSlug = 'whole-grain-oats';
    } elseif (in_array($normalizedRecipeName, $quickCookOatmealRecipes, true)) {
      $oatIngredientLabel = 'Quick Cook Oatmeal';
      $oatProductSlug = 'quick-cook-oats';
    } elseif (in_array($normalizedRecipeName, $instantOatmealRecipes, true)) {
      $oatIngredientLabel = 'Instant Oatmeal';
      $oatProductSlug = 'instant-oats';
    }

    $servingCount = null;
    if (preg_match_all('/\d+(?:\.\d+)?/', (string) $rServ, $servingMatches) > 0 && !empty($servingMatches[0])) {
      $servingValues = array_map(static fn ($value) => (float) $value, $servingMatches[0]);
      $servingCount = max($servingValues);
    }

    $variantSizeByServing = null;
    if ($servingCount !== null) {
      if ($servingCount <= 2) {
        $variantSizeByServing = '250g';
      } elseif ($servingCount <= 4) {
        $variantSizeByServing = '500g';
      } else {
        $variantSizeByServing = '1kg';
      }
    }

    $buildProductDetailsUrl = static function (string $productSlug) use ($variantSizeByServing): string {
      $params = ['product' => $productSlug];
      if (is_string($variantSizeByServing) && in_array($variantSizeByServing, ['1kg', '500g', '250g'], true)) {
        $params['size'] = $variantSizeByServing;
      }

      return route('nordic.products.details', $params);
    };

    $productPreviewCatalog = [
      'whole-grain-oats' => [
        '1kg' => asset('images/nordic/Nordic-Oats-Whole-Grain-Rolled-Oats-1kg.png'),
        '500g' => asset('images/nordic/product details/Nordic-Oats-Whole-Grain-Rolled-Oats-500g.png'),
        '250g' => asset('images/nordic/product details/Nordic-Oats-Whole-Grain-Rolled-Oats-250g.png'),
      ],
      'quick-cook-oats' => [
        '1kg' => asset('images/nordic/Nordic-Oats-Quick-Cook-Oatmeal-1kg.png'),
        '500g' => asset('images/nordic/product details/Nordic-Oats-Quick-Cook-Oatmeal-500g.png'),
        '250g' => asset('images/nordic/product details/Nordic-Oats-Quick-Cook-Oatmeal-250g.png'),
      ],
      'instant-oats' => [
        '1kg' => asset('images/nordic/Nordic-Oats-Instant-Oatmeal-1kg.png'),
        '500g' => asset('images/nordic/product details/Nordic-Oats-Instant-Oatmeal-500g.png'),
        '250g' => asset('images/nordic/product details/Nordic-Oats-Instant-Oatmeal-250g.png'),
      ],
    ];

    $productPageOrder = array_values(array_filter([
      (int) ($detailPageId ?? 0),
      (int) ($productsPageId ?? 0),
      (int) ($sharedNordicPageId ?? 0),
    ]));

    $productTitleRows = collect();
    $productTitleKeys = collect(config('cms.nordic_products.zones.nordic_products_selected_product.products', []))
      ->map(fn ($p) => (string) ($p['title_key'] ?? ''))
      ->filter()
      ->values()
      ->all();

    $productTitlePageIds = array_values(array_filter([$detailPageId, $productsPageId, $sharedNordicPageId]));
    if (!empty($productTitleKeys) && !empty($productTitlePageIds)) {
      $productTitleRows = collect(DB::table('text_content')
        ->whereIn('page_id', $productTitlePageIds)
        ->whereIn('key', $productTitleKeys)
        ->get(['page_id', 'key', 'value']))
        ->map(fn ($row) => [
          'page_id' => (int) ($row->page_id ?? 0),
          'key' => (string) ($row->key ?? ''),
          'value' => (string) ($row->value ?? ''),
        ]);
    }

    $productTitleValue = static function (string $key, string $default = '') use ($productTitleRows, $productPageOrder): string {
      $key = trim($key);
      if ($key === '') {
        return $default;
      }

      foreach ($productPageOrder as $pid) {
        $match = $productTitleRows->first(fn ($row) => (string) ($row['key'] ?? '') === $key && (int) ($row['page_id'] ?? 0) === (int) $pid);
        if ($match && trim((string) ($match['value'] ?? '')) !== '') {
          return trim((string) $match['value']);
        }
      }

      $first = $productTitleRows->firstWhere('key', $key);
      if ($first && trim((string) ($first['value'] ?? '')) !== '') {
        return trim((string) $first['value']);
      }

      return $default;
    };

    $productDisplayNames = collect(config('cms.nordic_products.zones.nordic_products_selected_product.products', []))
      ->mapWithKeys(static function ($product) use ($productTitleValue) {
        $slug = trim((string) ($product['slug'] ?? ''));
        if ($slug === '') {
          return [];
        }
        $key = (string) ($product['title_key'] ?? '');
        $fallback = trim((string) ($product['title_default'] ?? ''));
        $title = $productTitleValue($key, $fallback);
        return [$slug => ($title !== '' ? $title : $fallback)];
      })
      ->all();

    if (is_string($oatProductSlug) && $oatProductSlug !== '') {
      $resolvedOatLabel = trim((string) ($productDisplayNames[$oatProductSlug] ?? ''));
      if ($resolvedOatLabel !== '') {
        $oatIngredientLabel = $resolvedOatLabel;
      }
    }

    $buildMentionMetaAttrs = static function (string $productSlug, string $fallbackLabel = 'Product') use ($productPreviewCatalog, $productDisplayNames, $variantSizeByServing): string {
      $slug = trim($productSlug);
      if ($slug === '') {
        return '';
      }

      $size = in_array($variantSizeByServing, ['1kg', '500g', '250g'], true) ? $variantSizeByServing : null;
      $images = $productPreviewCatalog[$slug] ?? [];
      $image = is_array($images) && $size !== null
        ? (string) ($images[$size] ?? '')
        : '';
      $displayName = trim((string) ($productDisplayNames[$slug] ?? $fallbackLabel));
      if ($displayName === '') {
        $displayName = 'Product';
      }

      $attrs = ' data-product-slug="' . e($slug) . '"';
      $attrs .= ' data-product-name="' . e($displayName) . '"';
      if ($size !== null) {
        $attrs .= ' data-product-size="' . e(strtoupper($size)) . '"';
      }
      if ($image !== '') {
        $attrs .= ' data-product-image="' . e($image) . '"';
      }

      return $attrs;
    };

    $mentionPattern = '/\[@([^\]]+)\]\(nordic-product:([a-z0-9\-]+)\)/i';

    if ($oatIngredientLabel !== null && !empty($rIng)) {
      $oatPattern = '/\b(?:nordic\s+)?rolled\s+oats?\b|\bnordic\s+oats?\b|\bquick(?:\s|-)?cook\s+oatmeal\b|\binstant\s+oatmeal\b|\boat\s+flour\b|\boats?\b/i';
      $rIng = array_map(static function (string $line) use ($oatPattern, $oatIngredientLabel, $mentionPattern): string {
        if (preg_match($mentionPattern, $line) === 1) {
          return $line;
        }
        return (string) preg_replace($oatPattern, $oatIngredientLabel, $line, 1);
      }, $rIng);
    }

    $highlightProductNameToSlug = [];
    $registerHighlightName = static function (?string $name, string $slug) use (&$highlightProductNameToSlug): void {
      $name = is_string($name) ? trim($name) : '';
      $slug = trim($slug);
      if ($name === '' || $slug === '') {
        return;
      }
      $highlightProductNameToSlug[mb_strtolower($name)] = $slug;
    };

    foreach (config('cms.nordic_products.zones.nordic_products_selected_product.products', []) as $product) {
      $slug = trim((string) ($product['slug'] ?? ''));
      if ($slug === '') {
        continue;
      }
      $registerHighlightName((string) ($productDisplayNames[$slug] ?? ''), $slug);
      $registerHighlightName((string) ($product['title_default'] ?? ''), $slug);
    }

    $registerHighlightName('Whole Grain Oats', 'whole-grain-oats');
    $registerHighlightName('Whole Grain Rolled Oats', 'whole-grain-oats');
    $registerHighlightName('Quick Cook Oats', 'quick-cook-oats');
    $registerHighlightName('Quick Cook Oatmeal', 'quick-cook-oats');
    $registerHighlightName('Instant Oats', 'instant-oats');
    $registerHighlightName('Instant Oatmeal', 'instant-oats');

    $highlightNames = array_keys($highlightProductNameToSlug);
    usort($highlightNames, static fn (string $a, string $b) => strlen($b) <=> strlen($a));
    $highlightPattern = !empty($highlightNames)
      ? ('/\b(' . implode('|', array_map(static fn (string $name) => preg_quote($name, '/'), $highlightNames)) . ')\b/i')
      : null;

    $highlightOatProductHtml = static function (string $text, bool $shouldLinkProducts = false) use ($buildProductDetailsUrl, $buildMentionMetaAttrs, $productDisplayNames, $highlightProductNameToSlug, $highlightPattern): string {
      if (!is_string($highlightPattern) || $highlightPattern === '') {
        return e($text);
      }

      $parts = preg_split($highlightPattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
      if (!is_array($parts)) {
        return e($text);
      }

      $html = '';
      foreach ($parts as $index => $part) {
        if ($part === '') {
          continue;
        }
        if ($index % 2 === 1) {
          $needle = mb_strtolower(trim((string) $part));
          $slug = $highlightProductNameToSlug[$needle] ?? null;
          if (!is_string($slug) || $slug === '') {
            if (str_contains($needle, 'whole grain')) {
              $slug = 'whole-grain-oats';
            } elseif (str_contains($needle, 'quick cook')) {
              $slug = 'quick-cook-oats';
            } elseif (str_contains($needle, 'instant')) {
              $slug = 'instant-oats';
            }
          }

          $displayLabel = $part;
          if (is_string($slug) && $slug !== '') {
            $resolved = trim((string) ($productDisplayNames[$slug] ?? ''));
            if ($resolved !== '') {
              $displayLabel = $resolved;
            }
          }

          if ($shouldLinkProducts && is_string($slug) && $slug !== '') {
            $url = $buildProductDetailsUrl($slug);
            $metaAttrs = $buildMentionMetaAttrs($slug, $displayLabel);
            $html .= '<a class="nordic-product-mention ingredient-product-label nordic-product-preview-trigger"' . $metaAttrs . ' href="' . e($url) . '">' . e($displayLabel) . '</a>';
          } else {
            $html .= '<span class="ingredient-product-label">' . e($displayLabel) . '</span>';
          }
        } else {
          $html .= e($part);
        }
      }
      return $html;
    };
    $renderMentionedTextHtml = static function (string $line, bool $shouldLinkProducts = false) use ($mentionPattern, $highlightOatProductHtml, $buildProductDetailsUrl, $buildMentionMetaAttrs, $productDisplayNames): string {
      $cursor = 0;
      $html = '';
      $line = (string) $line;
      $lineLength = strlen($line);

      while (preg_match($mentionPattern, $line, $matches, PREG_OFFSET_CAPTURE, $cursor) === 1) {
        $full = $matches[0][0] ?? '';
        $start = (int) ($matches[0][1] ?? 0);
        $label = trim((string) ($matches[1][0] ?? ''));
        $productSlug = trim((string) ($matches[2][0] ?? ''));

        if ($start > $cursor) {
          $html .= $highlightOatProductHtml(substr($line, $cursor, $start - $cursor), $shouldLinkProducts);
        }

        $displayLabel = $label !== '' ? $label : 'Product';
        if ($productSlug !== '' && !empty($productDisplayNames[$productSlug] ?? '')) {
          $displayLabel = (string) $productDisplayNames[$productSlug];
        }
        if ($productSlug !== '') {
          $url = $buildProductDetailsUrl($productSlug);
          $metaAttrs = $buildMentionMetaAttrs($productSlug, $displayLabel);
          $html .= '<a class="nordic-product-mention nordic-product-preview-trigger"' . $metaAttrs . ' href="' . e($url) . '">' . e($displayLabel) . '</a>';
        } else {
          $html .= '<span class="nordic-product-mention nordic-product-mention--missing">' . e($displayLabel) . '</span>';
        }

        $cursor = $start + strlen($full);
      }

      if ($cursor < $lineLength) {
        $html .= $highlightOatProductHtml(substr($line, $cursor), $shouldLinkProducts);
      }

      return $html;
    };

    if (is_object($recipeData)) {
      $rImg = !empty($recipeData->image_filename) ? asset('images/nordic/recipes/'.$recipeData->image_filename) : null;
    } else {
      $rImg = $recipeData['image'] ?? null;
    }
  @endphp

  <title>Korpala Nordic - {{ $rName ?: 'Details' }}</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo/Korpala-Nordic-Logo.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Farro:wght@700;800&family=Fira+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/nordic/nordic.css') }}?v={{ filemtime(public_path('css/nordic/nordic.css')) ?: 0 }}" />
  <link rel="stylesheet" href="{{ asset('css/nordic/nordic-recipe-details.css') }}?v={{ filemtime(public_path('css/nordic/nordic-recipe-details.css')) ?: 0 }}" />
  <style>
    :root {
      --nordic-page-bg: url("{{ $nordicPageBgUrl }}");
    }
    .nordic-recipe-details-page {
      --rd-description-bg: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(243, 243, 243, 0.94) 100%);
      --rd-description-border: rgba(34, 57, 71, 0.12);
      --rd-description-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 8px 14px rgba(20, 34, 43, 0.04);
      --rd-description-text: color-mix(in srgb, var(--rd-brand-deep-2) 88%, var(--rd-brand-deep) 12%);
    }
    .recipe-details-right .ingredients-card .nordic-product-mention {
      display: inline;
      margin: 0;
      padding: 0 6px;
      border: 0;
      border-radius: 2px;
      background: transparent;
      color: inherit;
      text-decoration: none;
      font-family: "Fira Sans", sans-serif;
      font-weight: 800 !important;
      font-size: 1.14em;
      line-height: inherit;
      letter-spacing: 0;
      box-decoration-break: clone;
      -webkit-box-decoration-break: clone;
      transition: background 140ms ease, color 140ms ease;
    }
    .recipe-details-right .ingredients-card .nordic-product-mention:hover {
      background: color-mix(in srgb, #e6ba40 22%, white 78%);
      color: #4d2000;
    }
    .recipe-details-right .ingredients-card .nordic-product-mention:focus-visible {
      outline: 2px solid color-mix(in srgb, #f4d7a6 62%, #17405b 38%);
      outline-offset: 2px;
    }
    .recipe-details-right .ingredients-card .ingredient-product-label {
      display: inline;
      margin: 0;
      padding: 0 6px;
      border-radius: 2px;
      background: transparent;
      color: inherit;
      font-family: "Fira Sans", sans-serif;
      font-weight: 800 !important;
      font-size: 1.14em;
      line-height: inherit;
      letter-spacing: 0;
      text-decoration: none;
      box-decoration-break: clone;
      -webkit-box-decoration-break: clone;
    }
    @media (max-width: 700px) {
      .recipe-details-right .ingredients-card .nordic-product-mention,
      .recipe-details-right .ingredients-card .ingredient-product-label {
        background: #e6dfc8;
        color: #1f3f55;
      }

      .recipe-details-right .ingredients-card .nordic-product-mention:hover,
      .recipe-details-right .ingredients-card .nordic-product-mention:active {
        background: #ddd4b9;
        color: #173648;
      }
    }
    .recipe-details-right .ingredients-card .nordic-product-mention--missing {
      background: transparent;
      color: #5a6874;
    }
    .nordic-product-preview-tooltip {
      position: fixed;
      z-index: 1200;
      left: 0;
      top: 0;
      width: min(260px, calc(100vw - 24px));
      padding: 10px;
      border-radius: 12px;
      border: 1px solid rgba(34, 57, 71, 0.16);
      background: rgba(255, 255, 255, 0.98);
      box-shadow: 0 16px 28px rgba(20, 34, 43, 0.22);
      pointer-events: none;
      opacity: 0;
      visibility: hidden;
      transform: translateY(4px);
      transition: opacity 120ms ease, transform 120ms ease, visibility 120ms ease;
    }
    .nordic-product-preview-tooltip.is-visible {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    .nordic-product-preview-tooltip__image {
      display: none;
      width: 100%;
      aspect-ratio: 1 / 1;
      object-fit: contain;
      object-position: center center;
      border-radius: 10px;
      background: linear-gradient(145deg, #ffffff 0%, #f1f4f6 100%);
      border: 1px solid rgba(34, 57, 71, 0.1);
      margin-bottom: 8px;
    }
    .nordic-product-preview-tooltip.has-image .nordic-product-preview-tooltip__image {
      display: block;
    }
    .nordic-product-preview-tooltip__name {
      margin: 0;
      color: #223947;
      font-family: "Fira Sans", sans-serif;
      font-size: 15px;
      font-weight: 800;
      line-height: 1.25;
    }
    .nordic-product-preview-tooltip__meta {
      margin: 4px 0 0;
      color: #5b6a76;
      font-family: "Fira Sans", sans-serif;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }
    @media (max-width: 1024px), (hover: none), (pointer: coarse) {
      .nordic-product-preview-tooltip {
        display: none !important;
      }
    }
  </style>
</head>
<body>
  @include('frontend.partials.nordic-header')

  <main class="nordic-recipe-details-page">
    <section class="recipe-details-head" id="nordicRecipeDetailsHead" data-is-visual="{{ $isVisualEditor ? '1' : '0' }}">
      <nav aria-label="Breadcrumb">
        <p class="recipe-details-breadcrumb">
          <a href="{{ route('nordic') }}">Home</a>
          <span aria-hidden="true">&gt;</span>
          <a href="{{ route('nordic.recipes') }}">Recipes</a>
          <span aria-hidden="true">&gt;</span>
          <span class="recipe-details-breadcrumb-current">{{ $rName ?: 'Details' }}</span>
        </p>
      </nav>
      <h1>{{ $rName ?: 'Details' }}</h1>
    </section>

    <section class="recipe-details-layout" data-recipe-slug="{{ $rSlug }}">
      <aside class="recipe-details-left">
        <figure class="recipe-image-card">
          @if($rImg)
            <img src="{{ $rImg }}" alt="{{ $rName }}" loading="lazy" />
          @else
            <div class="recipe-image-card__fallback" role="img" aria-label="No recipe image available"></div>
          @endif
          <figcaption class="recipe-image-overlay">
            <h2>{{ $rName ?: 'Nordic Recipe' }}</h2>
            <p>{{ $rSubtitle }}</p>
          </figcaption>
        </figure>
        <section class="detail-card detail-card--description detail-card--image-description">
          <p>{{ $rDesc ?: 'No description available yet.' }}</p>
        </section>
        <div class="recipe-actions recipe-actions--standalone recipe-actions--desktop">
          <button type="button" class="recipe-action-btn recipe-action-btn--primary" data-recipe-action="pdf" data-pdf-url="{{ $rSlug ? route('nordic.recipes.pdf', ['recipe' => $rSlug]) : '' }}" @disabled(!$rSlug)>Download PDF</button>
          <button type="button" class="recipe-action-btn" data-recipe-action="share">
            <svg class="recipe-action-btn__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <circle cx="18" cy="5" r="3"/>
              <circle cx="6" cy="12" r="3"/>
              <circle cx="18" cy="19" r="3"/>
              <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
              <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
            </svg>
            <span data-share-label>Share</span>
          </button>
        </div>
      </aside>

      <article class="recipe-details-right">
        <section class="detail-card nutrition-card detail-card--nutrition">
          <h2>Nutrition Per Serving</h2>
          <div class="nutrition-grid">
            <p><span>Servings:</span> {{ $rServ ?: 'N/A' }}</p>
            <p><span>Calories:</span> {{ $rCal ?: 'N/A' }}</p>
          </div>
        </section>

        <article class="ingredients-card ingredients-card--main">
          <h2>Ingredients</h2>
          @if (!empty($rIng))
            <ul>
              @foreach ($rIng as $ingredient)
                <li>{!! $renderMentionedTextHtml($ingredient, true) !!}</li>
              @endforeach
            </ul>
          @else
            <p>No ingredients listed yet.</p>
          @endif
        </article>

        <section class="detail-card detail-card--procedure">
          <h2>Procedures</h2>
          @if (!empty($rProc))
            <ol>
              @foreach ($rProc as $procedure)
                <li>{!! $renderMentionedTextHtml($procedure, false) !!}</li>
              @endforeach
            </ol>
          @else
            <p>No procedures available yet.</p>
          @endif
        </section>
        <div class="recipe-actions recipe-actions--standalone recipe-actions--mobile">
          <button type="button" class="recipe-action-btn recipe-action-btn--primary" data-recipe-action="pdf" data-pdf-url="{{ $rSlug ? route('nordic.recipes.pdf', ['recipe' => $rSlug]) : '' }}" @disabled(!$rSlug)>Download PDF</button>
          <button type="button" class="recipe-action-btn" data-recipe-action="share">
            <svg class="recipe-action-btn__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <circle cx="18" cy="5" r="3"/>
              <circle cx="6" cy="12" r="3"/>
              <circle cx="18" cy="19" r="3"/>
              <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
              <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
            </svg>
            <span data-share-label>Share</span>
          </button>
        </div>
      </article>
    </section>
  </main>

  @include('frontend.partials.nordic-footer')

  <button id="backToTop" aria-label="Back to top"
    style="
      --btt-bg: #e6ba40;
      --btt-fg: #223947;
      --btt-hover-bg: #f0cf71;
    ">
    &#8593;
  </button>

  <div id="nordicIngredientProductPreview" class="nordic-product-preview-tooltip" aria-hidden="true">
    <img class="nordic-product-preview-tooltip__image" data-preview-image alt="" hidden />
    <p class="nordic-product-preview-tooltip__name" data-preview-name></p>
    <p class="nordic-product-preview-tooltip__meta" data-preview-meta></p>
  </div>

  <script src="{{ asset('js/nordic/nordic-recipe-details.js') }}"></script>
</body>
</html>
