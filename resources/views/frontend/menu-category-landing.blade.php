<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Menu — {{ $categoryTitle ?? 'Products' }}</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo/e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Glory:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=ZCOOL+XiaoWei&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/menu/menu.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/menu/menu-category-grid.css') }}" />
  <style>
    /* Match product category cards to the same layout behavior used by recipe category cards */
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
    .menu-category-main .menu-category-grid {
      gap: 1.5rem 1rem;
      align-items: end;
      padding-top: 2.25rem;
    }

    .menu-category-main .menu-category-group .menu-category-grid {
      padding-top: 0.6rem;
    }

    .menu-category-main .menu-category-grid-cell {
      padding-top: 0;
      justify-content: stretch;
      position: relative;
    }

    .menu-category-main .menu-category-card {
      height: auto;
      min-height: 0;
      position: relative;
      overflow: visible;
    }
  </style>
</head>
<body class="menu-page menu-category-page menu-category-page--{{ $categorySlug ?? 'default' }}">
  @include('frontend.menu-bg-image')
@php
  use App\Models\Page;
  use Illuminate\Support\Facades\DB;

  $cv = fn (string $key, string $default = '') =>
    ($allContents->firstWhere('key', $key)['value'] ?? null) ?? $default;
  $getSpecialMobileThumbScale = static function (string $displayName): ?string {
    $normalized = trim(strip_tags($displayName));
    if ($normalized === '') {
      return null;
    }

    if (
      preg_match('/\bbroth cube(?:s)?\b/i', $normalized) === 1
      && preg_match('/\b10\s*g\b/i', $normalized) === 1
    ) {
      return '0.5';
    }

    if (
      preg_match('/\bchicken powder mix\b/i', $normalized) === 1
      && preg_match('/\b8\s*g\b/i', $normalized) === 1
    ) {
      return '0.8';
    }

    if (
      preg_match('/\bmagic sinigang mix\b/i', $normalized) === 1
      && preg_match('/\b11\s*g\b/i', $normalized) === 1
    ) {
      return '0.8';
    }

    if (
      preg_match('/\bmagic sinigang sa gabi mix\b/i', $normalized) === 1
      && preg_match('/\b11\s*g\b/i', $normalized) === 1
    ) {
      return '0.8';
    }

    if (
      preg_match('/\bmagic breading mix\b/i', $normalized) === 1
      && preg_match('/\b35\s*g\b/i', $normalized) === 1
    ) {
      return '0.8';
    }

    return null;
  };
  $calloutMediaKeys = collect([
    'media_id_' . ($cmsPageSlug ?? 'menu_cat_jelly_mixes') . '_callout',
    ($categorySlug ?? '') === 'noodles-and-pastas' ? 'media_id_menu_cat_noodles_and_pastas_callout' : null,
  ])->filter()->values();
  $menuPageIdsForMedia = collect([
    Page::where('slug', $cmsPageSlug ?? 'menu_cat_jelly_mixes')->value('id'),
    ($categorySlug ?? '') === 'noodles-and-pastas' ? Page::where('slug', 'menu_cat_noodles_and_pastas')->value('id') : null,
    Page::where('slug', 'menu')->value('id'),
  ])->filter()->unique()->values();
  $menuImagesById = DB::table('menu_images')
    ->when($menuPageIdsForMedia->isNotEmpty(), fn ($query) => $query->whereIn('page_id', $menuPageIdsForMedia->all()))
    ->get()
    ->keyBy('id');
  $calloutImageId = $calloutMediaKeys
    ->map(fn (string $key) => trim((string) $cv($key, '')))
    ->first(fn (string $value) => $value !== '') ?? '';
  $calloutImageFilename = $calloutImageId !== '' ? ($menuImagesById->get((int) $calloutImageId)->filename ?? '') : '';
  $calloutImageSrc = $calloutImageFilename !== '' ? asset('images/menu/' . ltrim((string) $calloutImageFilename, '/')) : '';

  $normalizeCssLength = function (string $value): string {
    $v = trim($value);
    if ($v === '') return '';
    if (preg_match('/^\d+(\.\d+)?$/', $v) === 1) return $v . 'px';
    if (preg_match('/^\d+(\.\d+)?(px|%|rem|em|vw|vh|vmin|vmax)$/', $v) === 1) return $v;
    return '';
  };

  $calloutMaxWidth = $normalizeCssLength((string) $cv('menu_cat_callout_image_max_width', ''));
  $calloutHeight = $normalizeCssLength((string) $cv('menu_cat_callout_image_height', ''));
  $calloutFitRaw = strtolower(trim((string) $cv('menu_cat_callout_image_fit', '')));
  $calloutFit = in_array($calloutFitRaw, ['contain', 'cover'], true) ? $calloutFitRaw : '';
  $calloutImageStyle = collect([
    $calloutMaxWidth !== '' ? 'max-width:min(100%,' . $calloutMaxWidth . ')' : null,
    $calloutHeight !== '' ? 'height:' . $calloutHeight : null,
    $calloutFit !== '' ? 'object-fit:' . $calloutFit : null,
  ])->filter()->implode(';');
  $googleFonts = collect();
  foreach ($lineItems ?? [] as $_it) {
    $googleFonts->push($cv('mcli_'.$_it->id.'_name_font'));
  }
  $googleFonts = $googleFonts->map('trim')->filter()->unique()->reject(fn ($f) => in_array($f, ['', 'Asap', 'Glory', 'ZCOOL XiaoWei', 'DM Sans', 'Anek Latin', 'Spline Sans'], true))->values();
  $gfHref = $googleFonts->isNotEmpty()
    ? 'https://fonts.googleapis.com/css2?'.$googleFonts->map(fn ($f) => 'family='.str_replace('%20', '+', rawurlencode($f)).':wght@400;600;700')->implode('&').'&display=swap'
    : '';
  $showCallout = !empty($isVisualEditor) || ($lineItems && $lineItems->isNotEmpty());
  $hasItems = $lineItems && $lineItems->isNotEmpty();
  $isJellyCategory = ($categorySlug ?? '') === 'jelly-mixes';
  $isNoodlesCategory = ($categorySlug ?? '') === 'noodles-and-pastas';
  $jellyGroupMeta = collect([
    ['key' => 'flavored', 'label' => 'Flavored'],
    ['key' => 'unflavored', 'label' => 'Unflavored'],
  ]);
  $jellyGroupedItems = $isJellyCategory
    ? $jellyGroupMeta->mapWithKeys(function (array $group) use ($lineItems) {
        return [
          $group['key'] => collect($lineItems ?? [])->filter(function ($item) use ($group) {
            $itemFlavor = strtolower(trim((string) ($item->flavor_type ?? '')));
            $normalized = in_array($itemFlavor, ['flavored', 'unflavored'], true) ? $itemFlavor : 'unflavored';
            return $normalized === $group['key'];
          })->values(),
        ];
      })
    : collect();
  $noodleGroupMeta = collect([
    ['key' => 'mamma-mia', 'label' => 'Mamma Mia'],
    ['key' => 'vermicelli', 'label' => 'Vermicelli'],
  ]);
  $noodleGroupedItems = $isNoodlesCategory
    ? $noodleGroupMeta->mapWithKeys(function (array $group) use ($lineItems) {
        return [
          $group['key'] => collect($lineItems ?? [])->filter(function ($item) use ($group) {
            $title = strtolower(trim((string) ($item->title ?? '')));

            return match ($group['key']) {
              'mamma-mia' => str_contains($title, 'mamma mia'),
              'vermicelli' => str_contains($title, 'vermicelli'),
              default => false,
            };
          })->values(),
        ];
      })
    : collect();
  $ungroupedNoodleItems = $isNoodlesCategory
    ? collect($lineItems ?? [])->reject(function ($item) {
        $title = strtolower(trim((string) ($item->title ?? '')));

        return str_contains($title, 'mamma mia') || str_contains($title, 'vermicelli');
      })->values()
    : collect();
@endphp
@if($gfHref !== '')
  <link rel="stylesheet" href="{{ $gfHref }}" />
@endif

<div class="menu-page menu-category-page menu-category-page--{{ $categorySlug ?? 'default' }}">
@include('frontend.menu-header')

@if($showCallout)
  <section id="menuCategoryCallout" class="menu-category-callout">
    @if($calloutImageSrc !== '')
      <img
        src="{{ $calloutImageSrc }}"
        alt="{{ $categoryTitle ?? 'Category' }}"
        class="menu-category-callout-image"
        @if($calloutImageStyle !== '') style="{{ $calloutImageStyle }}" @endif
      />
    @else
      <h1>{{ $categoryTitle ?? 'Category' }}</h1>
    @endif
  </section>
@endif

@if(!empty($isVisualEditor) && !$hasItems)
  <main class="menu-category-main">
    <p class="menu-product-list-breadcrumbs" aria-label="Breadcrumb">
      <a href="{{ route('menu') }}">Home</a>
      <span aria-hidden="true">&gt;</span>
      <span class="is-current">{{ $categoryTitle ?? 'Category' }}</span>
    </p>
    @if($isJellyCategory)
      <div class="menu-category-groups">
        @foreach($jellyGroupMeta as $group)
          @php
            $groupHeadingKey = 'menu_cat_jelly_'.$group['key'].'_heading';
            $groupHeadingText = $cv($groupHeadingKey, $group['label']);
            $groupHeadingStyle = collect([
              $cv($groupHeadingKey.'_font') ? "font-family:'".$cv($groupHeadingKey.'_font')."',sans-serif" : null,
              $cv($groupHeadingKey.'_font_size') ? 'font-size:'.$cv($groupHeadingKey.'_font_size') : null,
              $cv($groupHeadingKey.'_color') ? 'color:'.$cv($groupHeadingKey.'_color') : null,
            ])->filter()->implode(';');
          @endphp
          <section class="menu-category-group menu-category-group--{{ $group['key'] }}">
            <div class="menu-category-group-heading">
              <h2 data-ve-field="{{ $groupHeadingKey }}" @if($groupHeadingStyle !== '') style="{{ $groupHeadingStyle }}" @endif>{{ $groupHeadingText }}</h2>
            </div>
            <div
              class="menu-category-grid"
              style="grid-template-columns: minmax(180px, 240px); justify-content: start;"
              data-category-slug="{{ $categorySlug }}"
              data-is-visual="1"
              data-flavor-group="{{ $group['key'] }}"
            >
              <div class="menu-category-add-tile ve-menu-add-product" data-default-flavor="{{ $group['key'] }}" role="button" tabindex="0" aria-label="Add {{ strtolower($group['label']) }} product">
                <span class="menu-category-add-icon" aria-hidden="true">+</span>
                <span class="menu-category-add-label">Add {{ strtolower($group['label']) }} product</span>
              </div>
            </div>
          </section>
        @endforeach
      </div>
    @elseif($isNoodlesCategory && $hasItems)
      <div class="menu-category-groups">
        @foreach($noodleGroupMeta as $group)
          @php
            $groupItems = $noodleGroupedItems->get($group['key'], collect());
            $groupHeadingKey = 'menu_cat_noodles_'.str_replace('-', '_', $group['key']).'_heading';
            $groupHeadingText = $cv($groupHeadingKey, $group['label']);
            $groupHeadingStyle = collect([
              $cv($groupHeadingKey.'_font') ? "font-family:'".$cv($groupHeadingKey.'_font')."',sans-serif" : null,
              $cv($groupHeadingKey.'_font_size') ? 'font-size:'.$cv($groupHeadingKey.'_font_size') : null,
              $cv($groupHeadingKey.'_color') ? 'color:'.$cv($groupHeadingKey.'_color') : null,
            ])->filter()->implode(';');
          @endphp
          @if($groupItems->isNotEmpty())
            <section class="menu-category-group menu-category-group--{{ $group['key'] }}">
              <div class="menu-category-group-heading">
                <h2 data-ve-field="{{ $groupHeadingKey }}" @if($groupHeadingStyle !== '') style="{{ $groupHeadingStyle }}" @endif>{{ $groupHeadingText }}</h2>
              </div>
              <div
                class="menu-category-grid"
                data-category-slug="{{ $categorySlug }}"
                data-is-visual="{{ !empty($isVisualEditor) ? '1' : '0' }}"
                data-product-family="{{ $group['key'] }}"
              >
                @foreach($groupItems as $item)
                  @php
                    $nameKey = 'mcli_'.$item->id.'_name';
                    $displayName = $cv($nameKey, $item->title);
                    $itemSlug = $item->id . '-' . \Illuminate\Support\Str::slug((string) $item->title);
                    $itemHref = route('menu.products.item', ['category' => $categorySlug, 'item' => $itemSlug]);
                    $flavorType = '';
                    $nameStyle = collect([
                      $cv($nameKey.'_font') ? "font-family:'".$cv($nameKey.'_font')."',sans-serif" : null,
                      $cv($nameKey.'_font_size') ? 'font-size:'.$cv($nameKey.'_font_size') : null,
                      $cv($nameKey.'_color') ? 'color:'.$cv($nameKey.'_color') : null,
                    ])->filter()->implode(';');
                    $visualWeightGrams = null;
                    if (preg_match('/(\d+(?:\.\d+)?)\s*(KG|G)\b/i', strip_tags((string) $displayName), $matches)) {
                      $weightValue = (float) ($matches[1] ?? 0);
                      $weightUnit = strtoupper((string) ($matches[2] ?? 'G'));
                      $visualWeightGrams = $weightUnit === 'KG' ? (int) round($weightValue * 1000) : (int) round($weightValue);
                    }
                    $visualScale = '1.000';
                    $visualLift = '0px';
                    if ($visualWeightGrams === 40) {
                      $visualScale = '0.72';
                      $visualLift = '0px';
                    } elseif ($visualWeightGrams === 80) {
                      $visualScale = '0.90';
                      $visualLift = '0px';
                    } elseif ($visualWeightGrams === 200) {
                      $visualScale = '1.18';
                      $visualLift = '0px';
                    } elseif ($visualWeightGrams === 400) {
                      $visualScale = '1.34';
                      $visualLift = '0px';
                    } elseif ($visualWeightGrams === 800) {
                      $visualScale = '1.52';
                      $visualLift = '0px';
                    }
                    $cardVisualStyle = $visualWeightGrams !== null
                      ? "--menu-pack-scale: {$visualScale}; --menu-pack-lift: {$visualLift};"
                      : '';
                  @endphp
                  <div class="menu-category-grid-cell">
                  @if(!empty($isVisualEditor))
                    <div class="menu-category-card-actions">
                      <button type="button" class="menu-category-card-move ve-menu-item-move" data-id="{{ $item->id }}" data-direction="up" aria-label="Move product up">&uarr;</button>
                      <button type="button" class="menu-category-card-move ve-menu-item-move" data-id="{{ $item->id }}" data-direction="down" aria-label="Move product down">&darr;</button>
                      <button type="button" class="menu-category-card-del ve-menu-item-del" data-id="{{ $item->id }}" aria-label="Delete product">&times;</button>
                    </div>
                  @endif
                  <a href="{{ $itemHref }}" class="menu-category-card" data-item-id="{{ $item->id }}" data-category-slug="{{ $categorySlug }}" data-item-flavor-type="{{ $flavorType }}" data-item-grams="{{ $visualWeightGrams ?? '' }}" data-back-image-url="{{ method_exists($item, 'backImageUrl') ? ($item->backImageUrl() ?? '') : '' }}" @if($cardVisualStyle !== '') style="{{ $cardVisualStyle }}" @endif aria-label="Open {{ strip_tags($displayName) }}">
                    <div class="menu-category-card-showcase">
                      <span class="menu-category-card-glow" aria-hidden="true"></span>
                      <figure class="menu-category-card-thumb">
                        @if($item->image_filename)
                          <img src="{{ asset('images/banner/'.$item->image_filename) }}" alt="{{ strip_tags($displayName) }}" loading="lazy" />
                        @endif
                      </figure>
                    </div>
                    <div class="menu-category-card-copy">
                      <div>
                        <p class="menu-category-card-name" data-ve-field="{{ $nameKey }}" @if($nameStyle !== '') style="{{ $nameStyle }}" @endif>{{ $displayName }}</p>
                      </div>
                    </div>
                  </a>
                  </div>
                @endforeach
              </div>
            </section>
          @endif
        @endforeach

        @if($ungroupedNoodleItems->isNotEmpty())
          <div
            class="menu-category-grid"
            data-category-slug="{{ $categorySlug }}"
            data-is-visual="{{ !empty($isVisualEditor) ? '1' : '0' }}"
            data-product-family="other"
          >
            @foreach($ungroupedNoodleItems as $item)
              @php
                $nameKey = 'mcli_'.$item->id.'_name';
                $displayName = $cv($nameKey, $item->title);
                $itemSlug = $item->id . '-' . \Illuminate\Support\Str::slug((string) $item->title);
                $itemHref = route('menu.products.item', ['category' => $categorySlug, 'item' => $itemSlug]);
                $flavorType = '';
                $nameStyle = collect([
                  $cv($nameKey.'_font') ? "font-family:'".$cv($nameKey.'_font')."',sans-serif" : null,
                  $cv($nameKey.'_font_size') ? 'font-size:'.$cv($nameKey.'_font_size') : null,
                  $cv($nameKey.'_color') ? 'color:'.$cv($nameKey.'_color') : null,
                ])->filter()->implode(';');
                $visualWeightGrams = null;
                if (preg_match('/(\d+(?:\.\d+)?)\s*(KG|G)\b/i', strip_tags((string) $displayName), $matches)) {
                  $weightValue = (float) ($matches[1] ?? 0);
                  $weightUnit = strtoupper((string) ($matches[2] ?? 'G'));
                  $visualWeightGrams = $weightUnit === 'KG' ? (int) round($weightValue * 1000) : (int) round($weightValue);
                }
                $visualScale = '1.000';
                $visualLift = '0px';
                if ($visualWeightGrams === 40) {
                  $visualScale = '0.72';
                  $visualLift = '0px';
                } elseif ($visualWeightGrams === 80) {
                  $visualScale = '0.90';
                  $visualLift = '0px';
                } elseif ($visualWeightGrams === 200) {
                  $visualScale = '1.18';
                  $visualLift = '0px';
                } elseif ($visualWeightGrams === 400) {
                  $visualScale = '1.34';
                  $visualLift = '0px';
                } elseif ($visualWeightGrams === 800) {
                  $visualScale = '1.52';
                  $visualLift = '0px';
                }
                $cardVisualStyle = $visualWeightGrams !== null
                  ? "--menu-pack-scale: {$visualScale}; --menu-pack-lift: {$visualLift};"
                  : '';
              @endphp
              <div class="menu-category-grid-cell">
              @if(!empty($isVisualEditor))
                <div class="menu-category-card-actions">
                  <button type="button" class="menu-category-card-move ve-menu-item-move" data-id="{{ $item->id }}" data-direction="up" aria-label="Move product up">&uarr;</button>
                  <button type="button" class="menu-category-card-move ve-menu-item-move" data-id="{{ $item->id }}" data-direction="down" aria-label="Move product down">&darr;</button>
                  <button type="button" class="menu-category-card-del ve-menu-item-del" data-id="{{ $item->id }}" aria-label="Delete product">&times;</button>
                </div>
              @endif
              <a href="{{ $itemHref }}" class="menu-category-card" data-item-id="{{ $item->id }}" data-category-slug="{{ $categorySlug }}" data-item-flavor-type="{{ $flavorType }}" data-item-grams="{{ $visualWeightGrams ?? '' }}" data-back-image-url="{{ method_exists($item, 'backImageUrl') ? ($item->backImageUrl() ?? '') : '' }}" @if($cardVisualStyle !== '') style="{{ $cardVisualStyle }}" @endif aria-label="Open {{ strip_tags($displayName) }}">
                <div class="menu-category-card-showcase">
                  <span class="menu-category-card-glow" aria-hidden="true"></span>
                  <figure class="menu-category-card-thumb">
                    @if($item->image_filename)
                      <img src="{{ asset('images/banner/'.$item->image_filename) }}" alt="{{ strip_tags($displayName) }}" loading="lazy" />
                    @endif
                  </figure>
                </div>
                <div class="menu-category-card-copy">
                  <div>
                    <p class="menu-category-card-name" data-ve-field="{{ $nameKey }}" @if($nameStyle !== '') style="{{ $nameStyle }}" @endif>{{ $displayName }}</p>
                  </div>
                </div>
              </a>
              </div>
            @endforeach
          </div>
        @endif

        @if(!empty($isVisualEditor))
          <div
            class="menu-category-grid"
            id="menuCategoryGrid"
            style="grid-template-columns: minmax(180px, 240px); justify-content: start;"
            data-category-slug="{{ $categorySlug }}"
            data-is-visual="1"
          >
            <div class="menu-category-add-tile ve-menu-add-product" role="button" tabindex="0" aria-label="Add product">
              <span class="menu-category-add-icon" aria-hidden="true">+</span>
              <span class="menu-category-add-label">Add product</span>
            </div>
          </div>
        @endif
      </div>
    @else
      <div
        class="menu-category-grid"
        id="menuCategoryGrid"
        style="grid-template-columns: minmax(180px, 240px); justify-content: start;"
        data-category-slug="{{ $categorySlug }}"
        data-is-visual="1"
      >
        <div class="menu-category-add-tile ve-menu-add-product" role="button" tabindex="0" aria-label="Add product">
          <span class="menu-category-add-icon" aria-hidden="true">+</span>
          <span class="menu-category-add-label">Add product</span>
        </div>
      </div>
    @endif
  </main>
@elseif($hasItems || !empty($isVisualEditor))
  <main class="menu-category-main">
    <p class="menu-product-list-breadcrumbs" aria-label="Breadcrumb">
      <a href="{{ route('menu') }}">Home</a>
      <span aria-hidden="true">&gt;</span>
      <span class="is-current">{{ $categoryTitle ?? 'Category' }}</span>
    </p>
    @if($isJellyCategory)
      <div class="menu-category-groups">
        @foreach($jellyGroupMeta as $group)
          @php
            $groupItems = $jellyGroupedItems->get($group['key'], collect());
            $showGroup = $groupItems->isNotEmpty() || !empty($isVisualEditor);
            $groupHeadingKey = 'menu_cat_jelly_'.$group['key'].'_heading';
            $groupHeadingText = $cv($groupHeadingKey, $group['label']);
            $groupHeadingStyle = collect([
              $cv($groupHeadingKey.'_font') ? "font-family:'".$cv($groupHeadingKey.'_font')."',sans-serif" : null,
              $cv($groupHeadingKey.'_font_size') ? 'font-size:'.$cv($groupHeadingKey.'_font_size') : null,
              $cv($groupHeadingKey.'_color') ? 'color:'.$cv($groupHeadingKey.'_color') : null,
            ])->filter()->implode(';');
          @endphp
          @if($showGroup)
            <section class="menu-category-group menu-category-group--{{ $group['key'] }}">
              <div class="menu-category-group-heading">
                <h2 data-ve-field="{{ $groupHeadingKey }}" @if($groupHeadingStyle !== '') style="{{ $groupHeadingStyle }}" @endif>{{ $groupHeadingText }}</h2>
              </div>
              <div
                class="menu-category-grid"
                data-category-slug="{{ $categorySlug }}"
                data-is-visual="{{ !empty($isVisualEditor) ? '1' : '0' }}"
                data-flavor-group="{{ $group['key'] }}"
              >
                @foreach($groupItems as $item)
                  @php
                    $nameKey = 'mcli_'.$item->id.'_name';
                    $displayName = $cv($nameKey, $item->title);
                    $itemSlug = $item->id . '-' . \Illuminate\Support\Str::slug((string) $item->title);
                    $itemHref = route('menu.products.item', ['category' => $categorySlug, 'item' => $itemSlug]);
                    $flavorTypeRaw = strtolower(trim((string) ($item->flavor_type ?? '')));
                    $flavorType = in_array($flavorTypeRaw, ['flavored', 'unflavored'], true) ? $flavorTypeRaw : 'unflavored';
                    $nameStyle = collect([
                      $cv($nameKey.'_font') ? "font-family:'".$cv($nameKey.'_font')."',sans-serif" : null,
                      $cv($nameKey.'_font_size') ? 'font-size:'.$cv($nameKey.'_font_size') : null,
                      $cv($nameKey.'_color') ? 'color:'.$cv($nameKey.'_color') : null,
                    ])->filter()->implode(';');
                    $visualWeightGrams = null;
                    if ($categorySlug === 'noodles-and-pastas' && preg_match('/(\d+(?:\.\d+)?)\s*(KG|G)\b/i', strip_tags((string) $displayName), $matches)) {
                      $weightValue = (float) ($matches[1] ?? 0);
                      $weightUnit = strtoupper((string) ($matches[2] ?? 'G'));
                      $visualWeightGrams = $weightUnit === 'KG' ? (int) round($weightValue * 1000) : (int) round($weightValue);
                    }
                    $visualScale = '1.000';
                    $visualLift = '0px';
                    if ($visualWeightGrams === 40) {
                      $visualScale = '0.72';
                      $visualLift = '0px';
                    } elseif ($visualWeightGrams === 80) {
                      $visualScale = '0.90';
                      $visualLift = '0px';
                    } elseif ($visualWeightGrams === 200) {
                      $visualScale = '1.18';
                      $visualLift = '0px';
                    } elseif ($visualWeightGrams === 400) {
                      $visualScale = '1.34';
                      $visualLift = '0px';
                    } elseif ($visualWeightGrams === 800) {
                      $visualScale = '1.52';
                      $visualLift = '0px';
                    }
                    $cardVisualStyle = $visualWeightGrams !== null
                      ? "--menu-pack-scale: {$visualScale}; --menu-pack-lift: {$visualLift};"
                      : '';
                  @endphp
                  <div class="menu-category-grid-cell">
                  @if(!empty($isVisualEditor))
                    <div class="menu-category-card-actions">
                      <button type="button" class="menu-category-card-move ve-menu-item-move" data-id="{{ $item->id }}" data-direction="up" aria-label="Move product up">&uarr;</button>
                      <button type="button" class="menu-category-card-move ve-menu-item-move" data-id="{{ $item->id }}" data-direction="down" aria-label="Move product down">&darr;</button>
                      <button type="button" class="menu-category-card-del ve-menu-item-del" data-id="{{ $item->id }}" aria-label="Delete product">&times;</button>
                    </div>
                  @endif
                  <a href="{{ $itemHref }}" class="menu-category-card" data-item-id="{{ $item->id }}" data-category-slug="{{ $categorySlug }}" data-item-flavor-type="{{ $flavorType }}" data-item-grams="{{ $visualWeightGrams ?? '' }}" data-back-image-url="{{ method_exists($item, 'backImageUrl') ? ($item->backImageUrl() ?? '') : '' }}" @if($cardVisualStyle !== '') style="{{ $cardVisualStyle }}" @endif aria-label="Open {{ strip_tags($displayName) }}">
                    <div class="menu-category-card-showcase">
                      <span class="menu-category-card-glow" aria-hidden="true"></span>
                      <figure class="menu-category-card-thumb">
                        @if($item->image_filename)
                          <img src="{{ asset('images/banner/'.$item->image_filename) }}" alt="{{ strip_tags($displayName) }}" loading="lazy" />
                        @endif
                      </figure>
                    </div>
                    <div class="menu-category-card-copy">
                      <div>
                        <p class="menu-category-card-name" data-ve-field="{{ $nameKey }}" @if($nameStyle !== '') style="{{ $nameStyle }}" @endif>{{ $displayName }}</p>
                      </div>
                    </div>
                  </a>
                  </div>
                @endforeach

                @if(!empty($isVisualEditor))
                  <div class="menu-category-add-tile ve-menu-add-product" data-default-flavor="{{ $group['key'] }}" role="button" tabindex="0" aria-label="Add {{ strtolower($group['label']) }} product">
                    <span class="menu-category-add-icon" aria-hidden="true">+</span>
                    <span class="menu-category-add-label">Add {{ strtolower($group['label']) }} product</span>
                  </div>
                @endif
              </div>
            </section>
          @endif
        @endforeach
      </div>
    @elseif($isNoodlesCategory)
      <div class="menu-category-groups">
        @foreach($noodleGroupMeta as $group)
          @php
            $groupItems = $noodleGroupedItems->get($group['key'], collect());
            $groupHeadingKey = 'menu_cat_noodles_'.str_replace('-', '_', $group['key']).'_heading';
            $groupHeadingText = $cv($groupHeadingKey, $group['label']);
            $groupHeadingStyle = collect([
              $cv($groupHeadingKey.'_font') ? "font-family:'".$cv($groupHeadingKey.'_font')."',sans-serif" : null,
              $cv($groupHeadingKey.'_font_size') ? 'font-size:'.$cv($groupHeadingKey.'_font_size') : null,
              $cv($groupHeadingKey.'_color') ? 'color:'.$cv($groupHeadingKey.'_color') : null,
            ])->filter()->implode(';');
          @endphp
          @if($groupItems->isNotEmpty())
            <section class="menu-category-group menu-category-group--{{ $group['key'] }}">
              <div class="menu-category-group-heading">
                <h2 data-ve-field="{{ $groupHeadingKey }}" @if($groupHeadingStyle !== '') style="{{ $groupHeadingStyle }}" @endif>{{ $groupHeadingText }}</h2>
              </div>
              <div
                class="menu-category-grid"
                data-category-slug="{{ $categorySlug }}"
                data-is-visual="{{ !empty($isVisualEditor) ? '1' : '0' }}"
                data-product-family="{{ $group['key'] }}"
              >
                @foreach($groupItems as $item)
                  @php
                    $nameKey = 'mcli_'.$item->id.'_name';
                    $displayName = $cv($nameKey, $item->title);
                    $itemSlug = $item->id . '-' . \Illuminate\Support\Str::slug((string) $item->title);
                    $itemHref = route('menu.products.item', ['category' => $categorySlug, 'item' => $itemSlug]);
                    $flavorType = '';
                    $nameStyle = collect([
                      $cv($nameKey.'_font') ? "font-family:'".$cv($nameKey.'_font')."',sans-serif" : null,
                      $cv($nameKey.'_font_size') ? 'font-size:'.$cv($nameKey.'_font_size') : null,
                      $cv($nameKey.'_color') ? 'color:'.$cv($nameKey.'_color') : null,
                    ])->filter()->implode(';');
                    $visualWeightGrams = null;
                    if (preg_match('/(\d+(?:\.\d+)?)\s*(KG|G)\b/i', strip_tags((string) $displayName), $matches)) {
                      $weightValue = (float) ($matches[1] ?? 0);
                      $weightUnit = strtoupper((string) ($matches[2] ?? 'G'));
                      $visualWeightGrams = $weightUnit === 'KG' ? (int) round($weightValue * 1000) : (int) round($weightValue);
                    }
                    $visualScale = '1.000';
                    $visualLift = '0px';
                    if ($visualWeightGrams === 40) {
                      $visualScale = '0.72';
                      $visualLift = '0px';
                    } elseif ($visualWeightGrams === 80) {
                      $visualScale = '0.90';
                      $visualLift = '0px';
                    } elseif ($visualWeightGrams === 200) {
                      $visualScale = '1.18';
                      $visualLift = '0px';
                    } elseif ($visualWeightGrams === 400) {
                      $visualScale = '1.34';
                      $visualLift = '0px';
                    } elseif ($visualWeightGrams === 800) {
                      $visualScale = '1.52';
                      $visualLift = '0px';
                    }
                    $cardVisualStyle = $visualWeightGrams !== null
                      ? "--menu-pack-scale: {$visualScale}; --menu-pack-lift: {$visualLift};"
                      : '';
                  @endphp
                  <div class="menu-category-grid-cell">
                  @if(!empty($isVisualEditor))
                    <div class="menu-category-card-actions">
                      <button type="button" class="menu-category-card-move ve-menu-item-move" data-id="{{ $item->id }}" data-direction="up" aria-label="Move product up">&uarr;</button>
                      <button type="button" class="menu-category-card-move ve-menu-item-move" data-id="{{ $item->id }}" data-direction="down" aria-label="Move product down">&darr;</button>
                      <button type="button" class="menu-category-card-del ve-menu-item-del" data-id="{{ $item->id }}" aria-label="Delete product">&times;</button>
                    </div>
                  @endif
                  <a href="{{ $itemHref }}" class="menu-category-card" data-item-id="{{ $item->id }}" data-category-slug="{{ $categorySlug }}" data-item-flavor-type="{{ $flavorType }}" data-item-grams="{{ $visualWeightGrams ?? '' }}" data-back-image-url="{{ method_exists($item, 'backImageUrl') ? ($item->backImageUrl() ?? '') : '' }}" @if($cardVisualStyle !== '') style="{{ $cardVisualStyle }}" @endif aria-label="Open {{ strip_tags($displayName) }}">
                    <div class="menu-category-card-showcase">
                      <span class="menu-category-card-glow" aria-hidden="true"></span>
                      <figure class="menu-category-card-thumb">
                        @if($item->image_filename)
                          <img src="{{ asset('images/banner/'.$item->image_filename) }}" alt="{{ strip_tags($displayName) }}" loading="lazy" />
                        @endif
                      </figure>
                    </div>
                    <div class="menu-category-card-copy">
                      <div>
                        <p class="menu-category-card-name" data-ve-field="{{ $nameKey }}" @if($nameStyle !== '') style="{{ $nameStyle }}" @endif>{{ $displayName }}</p>
                      </div>
                    </div>
                  </a>
                  </div>
                @endforeach

                @if(!empty($isVisualEditor))
                  <div class="menu-category-add-tile ve-menu-add-product" role="button" tabindex="0" aria-label="Add product">
                    <span class="menu-category-add-icon" aria-hidden="true">+</span>
                    <span class="menu-category-add-label">Add product</span>
                  </div>
                @endif
              </div>
            </section>
          @endif
        @endforeach

        @if($ungroupedNoodleItems->isNotEmpty())
          <div
            class="menu-category-grid"
            data-category-slug="{{ $categorySlug }}"
            data-is-visual="{{ !empty($isVisualEditor) ? '1' : '0' }}"
            data-product-family="other"
          >
            @foreach($ungroupedNoodleItems as $item)
              @php
                $nameKey = 'mcli_'.$item->id.'_name';
                $displayName = $cv($nameKey, $item->title);
                $itemSlug = $item->id . '-' . \Illuminate\Support\Str::slug((string) $item->title);
                $itemHref = route('menu.products.item', ['category' => $categorySlug, 'item' => $itemSlug]);
                $flavorType = '';
                $nameStyle = collect([
                  $cv($nameKey.'_font') ? "font-family:'".$cv($nameKey.'_font')."',sans-serif" : null,
                  $cv($nameKey.'_font_size') ? 'font-size:'.$cv($nameKey.'_font_size') : null,
                  $cv($nameKey.'_color') ? 'color:'.$cv($nameKey.'_color') : null,
                ])->filter()->implode(';');
                $visualWeightGrams = null;
                if (preg_match('/(\d+(?:\.\d+)?)\s*(KG|G)\b/i', strip_tags((string) $displayName), $matches)) {
                  $weightValue = (float) ($matches[1] ?? 0);
                  $weightUnit = strtoupper((string) ($matches[2] ?? 'G'));
                  $visualWeightGrams = $weightUnit === 'KG' ? (int) round($weightValue * 1000) : (int) round($weightValue);
                }
                $visualScale = '1.000';
                $visualLift = '0px';
                if ($visualWeightGrams === 40) {
                  $visualScale = '0.72';
                  $visualLift = '0px';
                } elseif ($visualWeightGrams === 80) {
                  $visualScale = '0.90';
                  $visualLift = '0px';
                } elseif ($visualWeightGrams === 200) {
                  $visualScale = '1.18';
                  $visualLift = '0px';
                } elseif ($visualWeightGrams === 400) {
                  $visualScale = '1.34';
                  $visualLift = '0px';
                } elseif ($visualWeightGrams === 800) {
                  $visualScale = '1.52';
                  $visualLift = '0px';
                }
                $cardVisualStyle = $visualWeightGrams !== null
                  ? "--menu-pack-scale: {$visualScale}; --menu-pack-lift: {$visualLift};"
                  : '';
              @endphp
              <div class="menu-category-grid-cell">
              @if(!empty($isVisualEditor))
                <div class="menu-category-card-actions">
                  <button type="button" class="menu-category-card-move ve-menu-item-move" data-id="{{ $item->id }}" data-direction="up" aria-label="Move product up">&uarr;</button>
                  <button type="button" class="menu-category-card-move ve-menu-item-move" data-id="{{ $item->id }}" data-direction="down" aria-label="Move product down">&darr;</button>
                  <button type="button" class="menu-category-card-del ve-menu-item-del" data-id="{{ $item->id }}" aria-label="Delete product">&times;</button>
                </div>
              @endif
              <a href="{{ $itemHref }}" class="menu-category-card" data-item-id="{{ $item->id }}" data-category-slug="{{ $categorySlug }}" data-item-flavor-type="{{ $flavorType }}" data-item-grams="{{ $visualWeightGrams ?? '' }}" data-back-image-url="{{ method_exists($item, 'backImageUrl') ? ($item->backImageUrl() ?? '') : '' }}" @if($cardVisualStyle !== '') style="{{ $cardVisualStyle }}" @endif aria-label="Open {{ strip_tags($displayName) }}">
                <div class="menu-category-card-showcase">
                  <span class="menu-category-card-glow" aria-hidden="true"></span>
                  <figure class="menu-category-card-thumb">
                    @if($item->image_filename)
                      <img src="{{ asset('images/banner/'.$item->image_filename) }}" alt="{{ strip_tags($displayName) }}" loading="lazy" />
                    @endif
                  </figure>
                </div>
                <div class="menu-category-card-copy">
                  <div>
                    <p class="menu-category-card-name" data-ve-field="{{ $nameKey }}" @if($nameStyle !== '') style="{{ $nameStyle }}" @endif>{{ $displayName }}</p>
                  </div>
                </div>
              </a>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    @else
      <div
        class="menu-category-grid"
        id="menuCategoryGrid"
        data-category-slug="{{ $categorySlug }}"
        data-is-visual="{{ !empty($isVisualEditor) ? '1' : '0' }}"
      >
        @foreach($lineItems as $item)
          @php
            $nameKey = 'mcli_'.$item->id.'_name';
            $displayName = $cv($nameKey, $item->title);
            $itemSlug = $item->id . '-' . \Illuminate\Support\Str::slug((string) $item->title);
            $itemHref = route('menu.products.item', ['category' => $categorySlug, 'item' => $itemSlug]);
            $flavorTypeRaw = strtolower(trim((string) ($item->flavor_type ?? '')));
            $flavorType = $categorySlug === 'jelly-mixes'
              ? (in_array($flavorTypeRaw, ['flavored', 'unflavored'], true) ? $flavorTypeRaw : 'unflavored')
              : '';
            $showFlavorBadge = false;
            $nameStyle = collect([
              $cv($nameKey.'_font') ? "font-family:'".$cv($nameKey.'_font')."',sans-serif" : null,
              $cv($nameKey.'_font_size') ? 'font-size:'.$cv($nameKey.'_font_size') : null,
              $cv($nameKey.'_color') ? 'color:'.$cv($nameKey.'_color') : null,
            ])->filter()->implode(';');
            $visualWeightGrams = null;
            if ($categorySlug === 'noodles-and-pastas' && preg_match('/(\d+(?:\.\d+)?)\s*(KG|G)\b/i', strip_tags((string) $displayName), $matches)) {
              $weightValue = (float) ($matches[1] ?? 0);
              $weightUnit = strtoupper((string) ($matches[2] ?? 'G'));
              $visualWeightGrams = $weightUnit === 'KG' ? (int) round($weightValue * 1000) : (int) round($weightValue);
            }
            $visualScale = '1.000';
            $visualLift = '0px';
            if ($visualWeightGrams === 40) {
              $visualScale = '0.72';
              $visualLift = '0px';
            } elseif ($visualWeightGrams === 80) {
              $visualScale = '0.90';
              $visualLift = '0px';
            } elseif ($visualWeightGrams === 200) {
              $visualScale = '1.18';
              $visualLift = '0px';
            } elseif ($visualWeightGrams === 400) {
              $visualScale = '1.34';
              $visualLift = '0px';
            } elseif ($visualWeightGrams === 800) {
              $visualScale = '1.52';
              $visualLift = '0px';
            }
            $cardVisualStyle = $visualWeightGrams !== null
              ? "--menu-pack-scale: {$visualScale}; --menu-pack-lift: {$visualLift};"
              : '';
            if (($specialMobileThumbScale = $getSpecialMobileThumbScale((string) $displayName)) !== null) {
              $cardVisualStyle = trim($cardVisualStyle . " --menu-card-mobile-thumb-scale: {$specialMobileThumbScale};");
            }
          @endphp
          <div class="menu-category-grid-cell">
          @if(!empty($isVisualEditor))
            <div class="menu-category-card-actions">
              <button type="button" class="menu-category-card-move ve-menu-item-move" data-id="{{ $item->id }}" data-direction="up" aria-label="Move product up">&uarr;</button>
              <button type="button" class="menu-category-card-move ve-menu-item-move" data-id="{{ $item->id }}" data-direction="down" aria-label="Move product down">&darr;</button>
              <button type="button" class="menu-category-card-del ve-menu-item-del" data-id="{{ $item->id }}" aria-label="Delete product">&times;</button>
            </div>
          @endif
          <a href="{{ $itemHref }}" class="menu-category-card" data-item-id="{{ $item->id }}" data-category-slug="{{ $categorySlug }}" data-item-flavor-type="{{ $flavorType }}" data-item-grams="{{ $visualWeightGrams ?? '' }}" data-back-image-url="{{ method_exists($item, 'backImageUrl') ? ($item->backImageUrl() ?? '') : '' }}" @if($cardVisualStyle !== '') style="{{ $cardVisualStyle }}" @endif aria-label="Open {{ strip_tags($displayName) }}">
            <div class="menu-category-card-showcase">
              @if($showFlavorBadge)
                <span class="menu-category-card-badge">{{ ucfirst($flavorType) }}</span>
              @endif
              <span class="menu-category-card-glow" aria-hidden="true"></span>
              <figure class="menu-category-card-thumb">
                @if($item->image_filename)
                  <img src="{{ asset('images/banner/'.$item->image_filename) }}" alt="{{ strip_tags($displayName) }}" loading="lazy" />
                @endif
              </figure>
            </div>
            <div class="menu-category-card-copy">
              <div>
                <p class="menu-category-card-name" data-ve-field="{{ $nameKey }}" @if($nameStyle !== '') style="{{ $nameStyle }}" @endif>{{ $displayName }}</p>
              </div>
            </div>
          </a>
          </div>
        @endforeach

        @if(!empty($isVisualEditor))
          <div class="menu-category-add-tile ve-menu-add-product" role="button" tabindex="0" aria-label="Add product">
            <span class="menu-category-add-icon" aria-hidden="true">+</span>
            <span class="menu-category-add-label">Add product</span>
          </div>
        @endif
      </div>
    @endif
  </main>
@endif

@include('frontend.menu-footer')
<button id="menuScrollTopBtn" class="menu-scroll-top" type="button" aria-label="Back to top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });">
  <span id="menuScrollTopIcon" class="menu-scroll-top-icon" aria-hidden="true">↑</span>
</button>
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
</div>
</body>
</html>
