<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  @php
    use App\Models\Page;
    use Illuminate\Support\Facades\DB;

    $selectedProduct = $product ?? request('product', 'whole-grain-oats');
    $sharedNordicPageId = Page::where('slug', 'nordic')->value('id');
    $detailPageId = Page::where('slug', 'nordic_product_details')->value('id');
    $productsPageId = Page::where('slug', 'nordic_products')->value('id');

    if (!isset($allContents)) {
      $pageIds = array_values(array_filter([$detailPageId, $sharedNordicPageId, $productsPageId]));

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

    $contentValue = function (string $key, string $default = '', ?array $preferredPageIds = null) use ($allContents): string {
      $matches = $allContents->where('key', $key)->values();
      if ($matches->isEmpty()) {
        return $default;
      }

      foreach (array_values(array_filter($preferredPageIds ?? [])) as $pageId) {
        $match = $matches->firstWhere('page_id', (int) $pageId);
        if ($match && array_key_exists('value', $match)) {
          return (string) $match['value'];
        }
      }

      return (string) (($matches->first()['value'] ?? null) ?? $default);
    };

    $assetPath = function (string $path): string {
      $trimmed = trim($path);
      if ($trimmed === '') return '';
      if (preg_match('/^https?:\/\//i', $trimmed)) return $trimmed;
      return asset(ltrim($trimmed, '/'));
    };

    $imgUrlById = function (string $mediaKey, string $section, string $fallback, ?array $preferredPageIds = null) use ($contentValue): string {
      $mediaId = trim($contentValue($mediaKey, '', $preferredPageIds));
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

    $productConfig = config('cms.nordic_product_details.zones.nordic_product_details_selected_product.products', []);
    $productOverridePageOrder = array_values(array_filter([$detailPageId, $productsPageId, $sharedNordicPageId]));
    $nordicPageBgUrl = $imgUrlById(
      'media_id_nordic_page_bg',
      'product',
      'https://images.unsplash.com/photo-1517673400267-0251440c45dc?w=2200&q=80&auto=format&fit=crop',
      [$sharedNordicPageId]
    );

    $detailProductOverrides = collect($productConfig)->mapWithKeys(function ($product) use ($contentValue, $imgUrlById, $assetPath, $productOverridePageOrder) {
      $sizeImages = [
        '1kg' => $imgUrlById(
          (string) ($product['size_1kg_front_media_key'] ?? $product['front_media_key'] ?? ''),
          'product',
          $assetPath((string) ($product['size_1kg_front_fallback'] ?? $product['front_fallback'] ?? '')),
          $productOverridePageOrder
        ),
        '500g' => $imgUrlById(
          (string) ($product['size_500g_front_media_key'] ?? ''),
          'product',
          $assetPath((string) ($product['size_500g_front_fallback'] ?? '')),
          $productOverridePageOrder
        ),
        '250g' => $imgUrlById(
          (string) ($product['size_250g_front_media_key'] ?? ''),
          'product',
          $assetPath((string) ($product['size_250g_front_fallback'] ?? '')),
          $productOverridePageOrder
        ),
      ];

      $sizeBackImages = [
        '1kg' => $imgUrlById(
          (string) ($product['size_1kg_back_media_key'] ?? $product['back_media_key'] ?? ''),
          'product',
          $assetPath((string) ($product['size_1kg_back_fallback'] ?? $product['back_fallback'] ?? '')),
          $productOverridePageOrder
        ),
        '500g' => $imgUrlById(
          (string) ($product['size_500g_back_media_key'] ?? ''),
          'product',
          $assetPath((string) ($product['size_500g_back_fallback'] ?? $product['size_500g_front_fallback'] ?? '')),
          $productOverridePageOrder
        ),
        '250g' => $imgUrlById(
          (string) ($product['size_250g_back_media_key'] ?? ''),
          'product',
          $assetPath((string) ($product['size_250g_back_fallback'] ?? $product['size_250g_front_fallback'] ?? '')),
          $productOverridePageOrder
        ),
      ];

      $data = [
        'name' => $contentValue((string) ($product['title_key'] ?? ''), (string) ($product['title_default'] ?? ''), $productOverridePageOrder),
        'description' => $contentValue((string) ($product['description_key'] ?? ''), (string) ($product['description_default'] ?? ''), $productOverridePageOrder),
        'sizeTitle' => $contentValue((string) ($product['size_title_key'] ?? ''), (string) ($product['size_title_default'] ?? 'Available Sizes'), $productOverridePageOrder),
        'sizeLabels' => [
          '1kg' => $contentValue((string) ($product['size_1kg_label_key'] ?? ''), (string) ($product['size_1kg_label_default'] ?? '1kg'), $productOverridePageOrder),
          '500g' => $contentValue((string) ($product['size_500g_label_key'] ?? ''), (string) ($product['size_500g_label_default'] ?? '500g'), $productOverridePageOrder),
          '250g' => $contentValue((string) ($product['size_250g_label_key'] ?? ''), (string) ($product['size_250g_label_default'] ?? '250g'), $productOverridePageOrder),
        ],
        'features' => [
          $contentValue((string) ($product['feature_1_key'] ?? ''), (string) ($product['feature_1_default'] ?? ''), $productOverridePageOrder),
          $contentValue((string) ($product['feature_2_key'] ?? ''), (string) ($product['feature_2_default'] ?? ''), $productOverridePageOrder),
          $contentValue((string) ($product['feature_3_key'] ?? ''), (string) ($product['feature_3_default'] ?? ''), $productOverridePageOrder),
        ],
        'buyTitle' => $contentValue((string) ($product['buy_title_key'] ?? ''), (string) ($product['buy_title_default'] ?? 'Buy Online'), $productOverridePageOrder),
        'buyLabels' => [
          'shopee' => $contentValue((string) ($product['buy_shopee_label_key'] ?? ''), (string) ($product['buy_shopee_label_default'] ?? 'Shopee'), $productOverridePageOrder),
          'lazada' => $contentValue((string) ($product['buy_lazada_label_key'] ?? ''), (string) ($product['buy_lazada_label_default'] ?? 'Lazada'), $productOverridePageOrder),
          'tiktok' => $contentValue((string) ($product['buy_tiktok_label_key'] ?? ''), (string) ($product['buy_tiktok_label_default'] ?? 'TikTok Shop'), $productOverridePageOrder),
        ],
        'sizeImages' => $sizeImages,
        'sizeBackImages' => $sizeBackImages,
        'sizeLinks' => [
          '1kg' => [
            'shopee' => $contentValue((string) ($product['size_1kg_shopee_key'] ?? ''), (string) ($product['size_1kg_shopee_default'] ?? ''), $productOverridePageOrder),
            'lazada' => $contentValue((string) ($product['size_1kg_lazada_key'] ?? ''), (string) ($product['size_1kg_lazada_default'] ?? ''), $productOverridePageOrder),
            'tiktok' => $contentValue((string) ($product['size_1kg_tiktok_key'] ?? ''), (string) ($product['size_1kg_tiktok_default'] ?? ''), $productOverridePageOrder),
          ],
          '500g' => [
            'shopee' => $contentValue((string) ($product['size_500g_shopee_key'] ?? ''), (string) ($product['size_500g_shopee_default'] ?? ''), $productOverridePageOrder),
            'lazada' => $contentValue((string) ($product['size_500g_lazada_key'] ?? ''), (string) ($product['size_500g_lazada_default'] ?? ''), $productOverridePageOrder),
            'tiktok' => $contentValue((string) ($product['size_500g_tiktok_key'] ?? ''), (string) ($product['size_500g_tiktok_default'] ?? ''), $productOverridePageOrder),
          ],
          '250g' => [
            'shopee' => $contentValue((string) ($product['size_250g_shopee_key'] ?? ''), (string) ($product['size_250g_shopee_default'] ?? ''), $productOverridePageOrder),
            'lazada' => $contentValue((string) ($product['size_250g_lazada_key'] ?? ''), (string) ($product['size_250g_lazada_default'] ?? ''), $productOverridePageOrder),
            'tiktok' => $contentValue((string) ($product['size_250g_tiktok_key'] ?? ''), (string) ($product['size_250g_tiktok_default'] ?? ''), $productOverridePageOrder),
          ],
        ],
      ];

      return [(string) ($product['slug'] ?? 'whole-grain-oats') => $data];
    })->all();

    $selectedProductConfig = collect($productConfig)->first(fn ($p) => (string) ($p['slug'] ?? '') === $selectedProduct)
      ?? (collect($productConfig)->first() ?? []);

    $selectedProductData = $detailProductOverrides[$selectedProduct] ?? ($detailProductOverrides['whole-grain-oats'] ?? [
      'name' => 'Whole Grain Oats',
      'description' => 'A hearty oat option with rich texture and naturally high fiber for balanced meals.',
      'sizeTitle' => 'Available Sizes',
      'sizeLabels' => ['1kg' => '1kg', '500g' => '500g', '250g' => '250g'],
      'features' => ['High in dietary fiber', 'Great for hot meals and baking', 'No artificial colorants'],
      'buyTitle' => 'Buy Online',
      'buyLabels' => ['shopee' => 'Shopee', 'lazada' => 'Lazada', 'tiktok' => 'TikTok Shop'],
      'sizeImages' => [
        '1kg' => asset('images/nordic/Nordic-Oats-Whole-Grain-Rolled-Oats-1kg.png'),
        '500g' => asset('images/nordic/product details/Nordic-Oats-Whole-Grain-Rolled-Oats-500g.png'),
        '250g' => asset('images/nordic/product details/Nordic-Oats-Whole-Grain-Rolled-Oats-250g.png'),
      ],
      'sizeBackImages' => [
        '1kg' => asset('images/nordic/Nordic-Oats-Whole-Grain-Rolled-Oats-1kg-Back.png'),
        '500g' => asset('images/nordic/product details/Nordic-Oats-Whole-Grain-Rolled-Oats-500g-Back.png'),
        '250g' => asset('images/nordic/product details/Nordic-Oats-Whole-Grain-Rolled-Oats-250g-Back.png'),
      ],
      'sizeLinks' => ['1kg' => ['shopee' => '#', 'lazada' => '#', 'tiktok' => '#'], '500g' => ['shopee' => '#', 'lazada' => '#', 'tiktok' => '#'], '250g' => ['shopee' => '#', 'lazada' => '#', 'tiktok' => '#']],
    ]);

    $pageLabelOrder = array_values(array_filter([$detailPageId, $sharedNordicPageId]));
    $breadcrumbHome = $contentValue('nordic_product_details_breadcrumb_home', 'Home', $pageLabelOrder);
    $breadcrumbList = $contentValue('nordic_product_details_breadcrumb_list', 'Product List', $pageLabelOrder);
    $breadcrumbCurrent = trim((string) ($selectedProductData['name'] ?? '')) !== ''
      ? (string) $selectedProductData['name']
      : $contentValue('nordic_product_details_breadcrumb_current', 'Product Details', $pageLabelOrder);
    $imageHint = $contentValue('nordic_product_details_image_hint', 'Click or tap image to flip and view the back', $pageLabelOrder);
  @endphp
  <title>Korpala Nordic - Product Details</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo/Korpala-Nordic-Logo.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Farro:wght@700;800&family=Fira+Sans:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/nordic/nordic.css') }}?v={{ filemtime(public_path('css/nordic/nordic.css')) ?: 0 }}" />
  <link rel="stylesheet" href="{{ asset('css/nordic/nordic-product-details.css') }}?v={{ filemtime(public_path('css/nordic/nordic-product-details.css')) ?: 0 }}" />
  <style>
    :root {
      --nordic-page-bg: url("{{ $nordicPageBgUrl }}");
    }
  </style>
</head>
<body data-product="{{ $selectedProduct }}">
  @include('frontend.partials.nordic-header')

  <main class="nordic-product-details-page">
    <section class="product-details-head">
      <p class="product-details-breadcrumb">
        <a href="{{ route('nordic') }}" data-ve-field="nordic_product_details_breadcrumb_home">{{ $breadcrumbHome }}</a>
        <span aria-hidden="true">&gt;</span>
        <a href="{{ route('nordic.products', ['product' => $selectedProduct]) }}" data-ve-field="nordic_product_details_breadcrumb_list">{{ $breadcrumbList }}</a>
        <span aria-hidden="true">&gt;</span>
        <span @if(!empty($selectedProductConfig['title_key'])) data-ve-field="{{ $selectedProductConfig['title_key'] }}" @else data-ve-field="nordic_product_details_breadcrumb_current" @endif>{{ $breadcrumbCurrent }}</span>
      </p>
    </section>

    <section class="product-details-layout">
      <aside class="product-image-gallery" aria-label="Product image gallery">
        <div class="product-main-image" id="detailsMainFlipCard" role="button" tabindex="0" aria-label="Flip product image front and back" aria-pressed="false">
          <div class="product-main-image__inner">
            <img id="detailsMainImageFront" class="product-main-image__face is-front" src="{{ $selectedProductData['sizeImages']['1kg'] ?? '' }}" alt="{{ $selectedProductData['name'] }} front" />
            <img id="detailsMainImageBack" class="product-main-image__face is-back" src="{{ $selectedProductData['sizeBackImages']['1kg'] ?? ($selectedProductData['sizeImages']['1kg'] ?? '') }}" alt="{{ $selectedProductData['name'] }} back" />
          </div>
        </div>
        <p class="product-main-image-hint" data-ve-field="nordic_product_details_image_hint">{{ $imageHint }}</p>
        <div class="product-variant-row" id="detailsVariants">
          <button type="button" class="variant-thumb is-active" data-size="1kg" aria-label="{{ $selectedProductData['sizeLabels']['1kg'] ?? '1kg' }} variant">
            <img id="detailsVariantImage1kg" src="{{ $selectedProductData['sizeImages']['1kg'] ?? '' }}" alt="{{ $selectedProductData['name'] }} {{ $selectedProductData['sizeLabels']['1kg'] ?? '1kg' }}" />
            <span @if(!empty($selectedProductConfig['size_1kg_label_key'])) data-ve-field="{{ $selectedProductConfig['size_1kg_label_key'] }}" @endif>{{ strtoupper($selectedProductData['sizeLabels']['1kg'] ?? '1kg') }}</span>
          </button>
          <button type="button" class="variant-thumb" data-size="500g" aria-label="{{ $selectedProductData['sizeLabels']['500g'] ?? '500g' }} variant">
            <img id="detailsVariantImage500g" src="{{ $selectedProductData['sizeImages']['500g'] ?? '' }}" alt="{{ $selectedProductData['name'] }} {{ $selectedProductData['sizeLabels']['500g'] ?? '500g' }}" />
            <span @if(!empty($selectedProductConfig['size_500g_label_key'])) data-ve-field="{{ $selectedProductConfig['size_500g_label_key'] }}" @endif>{{ strtoupper($selectedProductData['sizeLabels']['500g'] ?? '500g') }}</span>
          </button>
          <button type="button" class="variant-thumb" data-size="250g" aria-label="{{ $selectedProductData['sizeLabels']['250g'] ?? '250g' }} variant">
            <img id="detailsVariantImage250g" src="{{ $selectedProductData['sizeImages']['250g'] ?? '' }}" alt="{{ $selectedProductData['name'] }} {{ $selectedProductData['sizeLabels']['250g'] ?? '250g' }}" />
            <span @if(!empty($selectedProductConfig['size_250g_label_key'])) data-ve-field="{{ $selectedProductConfig['size_250g_label_key'] }}" @endif>{{ strtoupper($selectedProductData['sizeLabels']['250g'] ?? '250g') }}</span>
          </button>
        </div>
      </aside>

      <article class="product-info-card">
        <h1 id="detailsCardTitle" @if(!empty($selectedProductConfig['title_key'])) data-ve-field="{{ $selectedProductConfig['title_key'] }}" @endif>{{ $selectedProductData['name'] }}</h1>
        <p id="detailsCardDescription" @if(!empty($selectedProductConfig['description_key'])) data-ve-field="{{ $selectedProductConfig['description_key'] }}" @endif>{{ $selectedProductData['description'] }}</p>
        <div class="product-size-block" aria-label="Available sizes">
          <p class="product-size-title" @if(!empty($selectedProductConfig['size_title_key'])) data-ve-field="{{ $selectedProductConfig['size_title_key'] }}" @endif>{{ $selectedProductData['sizeTitle'] ?? 'Available Sizes' }}</p>
          <div class="product-size-list" id="detailsSizes">
            <span class="size-pill" data-size="1kg" @if(!empty($selectedProductConfig['size_1kg_label_key'])) data-ve-field="{{ $selectedProductConfig['size_1kg_label_key'] }}" @endif>{{ $selectedProductData['sizeLabels']['1kg'] ?? '1kg' }}</span>
            <span class="size-pill" data-size="500g" @if(!empty($selectedProductConfig['size_500g_label_key'])) data-ve-field="{{ $selectedProductConfig['size_500g_label_key'] }}" @endif>{{ $selectedProductData['sizeLabels']['500g'] ?? '500g' }}</span>
            <span class="size-pill" data-size="250g" @if(!empty($selectedProductConfig['size_250g_label_key'])) data-ve-field="{{ $selectedProductConfig['size_250g_label_key'] }}" @endif>{{ $selectedProductData['sizeLabels']['250g'] ?? '250g' }}</span>
          </div>
        </div>
        <ul id="detailsFeatures">
          <li @if(!empty($selectedProductConfig['feature_1_key'])) data-ve-field="{{ $selectedProductConfig['feature_1_key'] }}" @endif>{{ $selectedProductData['features'][0] ?? '' }}</li>
          <li @if(!empty($selectedProductConfig['feature_2_key'])) data-ve-field="{{ $selectedProductConfig['feature_2_key'] }}" @endif>{{ $selectedProductData['features'][1] ?? '' }}</li>
          <li @if(!empty($selectedProductConfig['feature_3_key'])) data-ve-field="{{ $selectedProductConfig['feature_3_key'] }}" @endif>{{ $selectedProductData['features'][2] ?? '' }}</li>
        </ul>

        <div class="product-buy-links" aria-label="Buy on e-commerce">
          <p class="product-buy-title" @if(!empty($selectedProductConfig['buy_title_key'])) data-ve-field="{{ $selectedProductConfig['buy_title_key'] }}" @endif>{{ $selectedProductData['buyTitle'] ?? 'Buy Online' }}</p>
          <div class="product-buy-grid">
            <a href="{{ $selectedProductData['sizeLinks']['1kg']['shopee'] ?? '#' }}" class="buy-link-card js-link-shopee" target="_blank" rel="noopener noreferrer" aria-label="Buy on Shopee">
              <img src="{{ asset('images/icons/ecommerce/shopee-logo.svg') }}" alt="Shopee logo" loading="lazy" />
              <span @if(!empty($selectedProductConfig['buy_shopee_label_key'])) data-ve-field="{{ $selectedProductConfig['buy_shopee_label_key'] }}" @endif>{{ $selectedProductData['buyLabels']['shopee'] ?? 'Shopee' }}</span>
            </a>
            <a href="{{ $selectedProductData['sizeLinks']['1kg']['lazada'] ?? '#' }}" class="buy-link-card js-link-lazada" target="_blank" rel="noopener noreferrer" aria-label="Buy on Lazada">
              <img src="{{ asset('images/icons/ecommerce/lazada-logo.svg') }}" alt="Lazada logo" loading="lazy" />
              <span @if(!empty($selectedProductConfig['buy_lazada_label_key'])) data-ve-field="{{ $selectedProductConfig['buy_lazada_label_key'] }}" @endif>{{ $selectedProductData['buyLabels']['lazada'] ?? 'Lazada' }}</span>
            </a>
            <a href="{{ $selectedProductData['sizeLinks']['1kg']['tiktok'] ?? '#' }}" class="buy-link-card js-link-tiktok" target="_blank" rel="noopener noreferrer" aria-label="Buy on TikTok Shop">
              <img src="{{ asset('images/icons/ecommerce/tiktok-shop-logo.svg') }}" alt="TikTok Shop logo" loading="lazy" />
              <span @if(!empty($selectedProductConfig['buy_tiktok_label_key'])) data-ve-field="{{ $selectedProductConfig['buy_tiktok_label_key'] }}" @endif>{{ $selectedProductData['buyLabels']['tiktok'] ?? 'TikTok Shop' }}</span>
            </a>
          </div>
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

  <script>
    window.NORDIC_PRODUCT_DETAIL_OVERRIDES = @json($detailProductOverrides);
  </script>
  <script src="{{ asset('js/nordic/nordic-product-details.js') }}"></script>
</body>
</html>
