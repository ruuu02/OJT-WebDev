<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  @php
    use App\Models\Page;
    use Illuminate\Support\Facades\DB;

    $pageId = $pageId ?? Page::where('slug', 'nordic_products')->value('id');
    $sharedPageId = $sharedPageId ?? Page::where('slug', 'nordic')->value('id');
    $pageIds = array_values(array_filter([$pageId, $sharedPageId]));

    if (!isset($allContents)) {
      $allContents = DB::table('text_content')
        ->when(!empty($pageIds), fn ($query) => $query->whereIn('page_id', $pageIds))
        ->get()
        ->map(fn ($row) => [
          'key' => $row->key,
          'value' => $row->value,
          'section' => 'text_content',
          'page_id' => (int) $row->page_id,
        ]);
    }

    $pageLabelOrder = array_values(array_filter([
      (int) ($pageId ?? 0),
      (int) ($sharedPageId ?? 0),
    ]));

    $contentValue = function (string $key, string $default = '', ?array $preferredPageIds = null) use ($allContents): string {
      $matches = $allContents->where('key', $key)->values();
      if ($matches->isEmpty()) {
        return $default;
      }

      foreach (array_values(array_filter($preferredPageIds ?? [])) as $pid) {
        $match = $matches->firstWhere('page_id', (int) $pid);
        if ($match && array_key_exists('value', $match)) {
          return (string) $match['value'];
        }
      }

      return (string) (($matches->first()['value'] ?? null) ?? $default);
    };

    $cv = fn(string $key, string $default = '') =>
      $contentValue($key, $default, $pageLabelOrder);

    $imgUrlById = function (string $mediaKey, string $section, string $fallback) use ($cv): string {
      $mediaId = trim((string) $cv($mediaKey, ''));
      if ($mediaId === '') {
        return $fallback;
      }

      $tableMap = [
        'carousel' => 'carousel_images',
        'logo' => 'logos',
        'product' => 'product_images',
        'recipe' => 'recipe_images',
      ];
      $dirMap = [
        'carousel' => 'banner',
        'logo' => 'logo',
        'product' => 'banner',
        'recipe' => 'icons',
      ];

      $filename = DB::table($tableMap[$section] ?? 'product_images')
        ->where('id', $mediaId)
        ->value('filename');

      if (!$filename) {
        return $fallback;
      }

      return asset('images/'.($dirMap[$section] ?? $section).'/'.$filename);
    };

    $fontsToLoad = collect($allContents)
      ->filter(fn($item) => str_ends_with($item['key'] ?? '', '_font'))
      ->pluck('value')
      ->filter()
      ->unique()
      ->values();

    $nordicPageBgUrl = $imgUrlById(
      'media_id_nordic_page_bg',
      'product',
      'https://images.unsplash.com/photo-1517673400267-0251440c45dc?w=2200&q=80&auto=format&fit=crop'
    );

    $productConfig = config('cms.nordic_products.zones.nordic_products_selected_product.products', []);
    $productCatalog = collect($productConfig)->map(function ($product) use ($cv, $imgUrlById) {
      return [
        'slug' => $product['slug'],
        'title_key' => $product['title_key'],
        'title' => $cv($product['title_key'], $product['title_default']),
        'front_image' => $imgUrlById(
          $product['front_media_key'],
          'product',
          asset(ltrim($product['front_fallback'], '/'))
        ),
        'back_image' => $imgUrlById(
          $product['back_media_key'],
          'product',
          asset(ltrim($product['back_fallback'], '/'))
        ),
      ];
    })->values()->all();

    $defaultProduct = $productCatalog[0];
    $selectedProductSlug = (string) request('product', $defaultProduct['slug']);
    $selectedProduct = collect($productCatalog)->firstWhere('slug', $selectedProductSlug) ?? $defaultProduct;
  @endphp
  <title>Korpala Nordic - Products</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo/Korpala-Nordic-Logo.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Farro:wght@700;800&family=Fira+Sans:wght@400;500&display=swap" rel="stylesheet">
  @foreach ($fontsToLoad as $fontFamily)
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $fontFamily) }}:wght@400;500;600;700&display=swap">
  @endforeach
  <link rel="stylesheet" href="{{ asset('css/nordic/nordic.css') }}?v={{ filemtime(public_path('css/nordic/nordic.css')) ?: 0 }}" />
  <link rel="stylesheet" href="{{ asset('css/nordic/nordic-products.css') }}" />
  @php
    $zoneStyles = [];
    $fieldStyles = [];
    foreach (config('cms.nordic_products.zones', []) as $zone) {
      if (($zone['type'] ?? '') !== 'text') continue;

      $firstKey = ($zone['fields'] ?? [])[0] ?? null;
      if ($firstKey) {
        $parts = [];
        $color = $cv($firstKey . '_color', '');
        $font  = $cv($firstKey . '_font', '');
        $size  = $cv($firstKey . '_font_size', '');
        if ($color) $parts[] = "color:{$color} !important";
        if ($font)  $parts[] = "font-family:'{$font}',sans-serif";
        if ($size)  $parts[] = "font-size:{$size}";
        if ($parts) $zoneStyles[$zone['selector']] = implode(';', $parts);
      }

      foreach (($zone['fields'] ?? []) as $fieldKey) {
        $parts = [];
        $color = $cv($fieldKey . '_color', '');
        $font  = $cv($fieldKey . '_font', '');
        $size  = $cv($fieldKey . '_font_size', '');
        if ($color) $parts[] = "color:{$color} !important";
        if ($font)  $parts[] = "font-family:'{$font}',sans-serif";
        if ($size)  $parts[] = "font-size:{$size}";
        if ($parts) {
          $fieldStyles[($zone['selector'] ?? '') . ' [data-ve-field="' . $fieldKey . '"]'] = implode(';', $parts);
        }
      }
    }
  @endphp
  @if(!empty($zoneStyles) || !empty($fieldStyles))
  <style>
  @foreach($zoneStyles as $selector => $css)
    {{ $selector }} { {{ $css }} }
  @endforeach
  @foreach($fieldStyles as $selector => $css)
    {{ $selector }} { {{ $css }} }
  @endforeach
  </style>
  @endif
  <style>
    :root {
      --nordic-page-bg: url("{{ $nordicPageBgUrl }}");
    }
  </style>
</head>
<body class="nordic-products-screen">
  @include('frontend.partials.nordic-header')

  <main class="nordic-products-page">
    <div class="nordic-products-display">
    <section class="nordic-products-list">
      <p class="nordic-products-list-text">
        <a href="{{ route('nordic') }}" data-ve-field="nordic_products_breadcrumb_home">{{ $cv('nordic_products_breadcrumb_home', 'Home') }}</a>
        <span aria-hidden="true">&gt;</span>
        <span class="is-current" data-ve-field="{{ $selectedProduct['title_key'] }}">{{ $selectedProduct['title'] }}</span>
      </p>
      <h2 class="nordic-products-list-title" data-ve-field="{{ $selectedProduct['title_key'] }}">{{ $selectedProduct['title'] }}</h2>
    </section>

    <section class="nordic-products-carousel" aria-label="Nordic Products Carousel" data-details-url="{{ route('nordic.products.details', ['product' => '__SLUG__']) }}">
      <!--
        Standby block: Left-side product image gallery (multiple images).
        Add/remove <img> items inside .product-gallery-thumbs when ready to enable this layout.
      -->
      <aside class="product-gallery-left is-standby" aria-label="Product image gallery standby">
        <div class="product-gallery-main">
          <img src="{{ $selectedProduct['front_image'] }}" alt="{{ $selectedProduct['title'] }} image" loading="lazy" />
        </div>
        <div class="product-gallery-thumbs">
          @foreach ($productCatalog as $thumbIndex => $thumbProduct)
            <img src="{{ $thumbProduct['front_image'] }}" alt="{{ $thumbProduct['title'] }} thumbnail {{ $thumbIndex + 1 }}" loading="lazy" />
          @endforeach
        </div>
      </aside>

      <div class="product-3d-stage" id="product3dStage">
        @foreach ($productCatalog as $index => $catalogProduct)
          <figure class="product-3d-card" data-index="{{ $index }}" data-product="{{ $catalogProduct['slug'] }}" data-title="{{ $catalogProduct['title'] }}" data-title-key="{{ $catalogProduct['title_key'] }}" aria-label="{{ $catalogProduct['title'] }}">
            <div class="product-flip-inner">
              <div class="product-flip-face is-front">
                <img src="{{ $catalogProduct['front_image'] }}" alt="{{ $catalogProduct['title'] }} front" loading="lazy" />
              </div>
              <div class="product-flip-face is-back">
                <img src="{{ $catalogProduct['back_image'] }}" alt="{{ $catalogProduct['title'] }} back" loading="lazy" />
              </div>
            </div>
            <button class="product-flip-toggle" type="button" aria-pressed="false">Show Back</button>
          </figure>
        @endforeach
      </div>

    </section>
    </div>

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

  <script src="{{ asset('js/nordic/nordic-products.js') }}"></script>
</body>
</html>
