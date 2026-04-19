<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Korpala Nordic</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo/Korpala-Nordic-Logo.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Farro:wght@700;800&family=Fira+Sans:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/nordic/nordic.css') }}?v={{ filemtime(public_path('css/nordic/nordic.css')) ?: 0 }}" />
  <link rel="stylesheet" href="{{ asset('css/shared/connect-with-us.css') }}" />

</head>
<body class="nordic-page">
  @php
    use Illuminate\Support\Facades\DB;
    use App\Models\Page;

    if (!isset($allContents)) {
      $pageId = Page::where('slug', 'nordic')->value('id');
      $allContents = DB::table('text_content')
          ->when($pageId, fn ($query) => $query->where('page_id', $pageId))
          ->get()
          ->map(fn($r) => ['key' => $r->key, 'value' => $r->value, 'section' => 'text_content']);
    }

    if (!isset($allMedia)) {
      $pageId = $pageId ?? Page::where('slug', 'nordic')->value('id');
      $allMedia = collect()
        ->merge(DB::table('carousel_images')
          ->when($pageId, fn ($query) => $query->where('page_id', $pageId))
          ->get()
          ->map(fn($r) => array_merge((array) $r, ['section' => 'carousel'])))
        ->merge(DB::table('product_images')
          ->when($pageId, fn ($query) => $query->where('page_id', $pageId))
          ->get()
          ->map(fn($r) => array_merge((array) $r, ['section' => 'product'])))
        ->merge(DB::table('logos')
          ->when($pageId, fn ($query) => $query->where('page_id', $pageId))
          ->get()
          ->map(fn($r) => array_merge((array) $r, ['section' => 'logo'])))
        ->merge(DB::table('recipe_images')
          ->when($pageId, fn ($query) => $query->where('page_id', $pageId))
          ->get()
          ->map(fn($r) => array_merge((array) $r, ['section' => 'recipe'])));
    }

    $cv = fn(string $key, string $default = '') =>
      ($allContents->firstWhere('key', $key)['value'] ?? null) ?? $default;

    $imgUrlById = function (string $mediaKey, string $section, string $fallback) use ($cv, $allMedia): string {
      $mediaId = trim((string) $cv($mediaKey, ''));
      if ($mediaId === '') {
        return asset($fallback);
      }

      $row = collect($allMedia)->first(fn ($item) =>
        ($item['section'] ?? $item->section ?? '') === $section
        && (string) ($item['id'] ?? $item->id ?? '') === $mediaId
      );

      $filename = is_array($row) ? ($row['filename'] ?? null) : ($row->filename ?? null);
      if (! $filename) {
        return asset($fallback);
      }

      $dirMap = [
        'carousel' => 'banner',
        'logo' => 'logo',
        'product' => 'banner',
        'recipe' => 'icons',
      ];

      return asset('images/'.($dirMap[$section] ?? $section).'/'.$filename);
    };

    $mediaUrlById = function (string $mediaKey, string $section, string $fallback) use ($cv, $allMedia): string {
      $mediaId = trim((string) $cv($mediaKey, ''));
      if ($mediaId === '') {
        return $fallback;
      }

      $row = collect($allMedia)->first(fn ($item) =>
        ($item['section'] ?? $item->section ?? '') === $section
        && (string) ($item['id'] ?? $item->id ?? '') === $mediaId
      );

      $filename = is_array($row) ? ($row['filename'] ?? null) : ($row->filename ?? null);
      if (! $filename) {
        return $fallback;
      }

      $dirMap = [
        'carousel' => 'banner',
        'logo' => 'logo',
        'product' => 'banner',
        'recipe' => 'icons',
      ];

      return asset('images/'.($dirMap[$section] ?? $section).'/'.$filename);
    };

    $nordicPageBgUrl = $mediaUrlById(
      'media_id_nordic_page_bg',
      'product',
      'https://images.unsplash.com/photo-1517673400267-0251440c45dc?w=2200&q=80&auto=format&fit=crop'
    );

    $fontsToLoad = collect($allContents)
      ->filter(fn($item) => str_ends_with($item['key'] ?? '', '_font'))
      ->pluck('value')
      ->filter()
      ->unique()
      ->values();
  @endphp

  @foreach ($fontsToLoad as $fontFamily)
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $fontFamily) }}:wght@400;500;600;700&display=swap">
  @endforeach

  @php
    $productCarouselCopy = [
      [
        'title_field' => 'nordic_product_copy_title_1',
        'text_field' => 'nordic_product_copy_text_1',
        'title' => $cv('nordic_product_copy_title_1', $cv('nordic_product_copy_title', 'Whole Grain Oats')),
        'description' => $cv('nordic_product_copy_text_1', $cv('nordic_product_copy_text', 'Made from whole oat groats for a hearty texture and naturally rich fiber, ideal for filling breakfasts and wholesome recipes.')),
      ],
      [
        'title_field' => 'nordic_product_copy_title_2',
        'text_field' => 'nordic_product_copy_text_2',
        'title' => $cv('nordic_product_copy_title_2', 'Instant Oats'),
        'description' => $cv('nordic_product_copy_text_2', 'Pre-processed for fast preparation while keeping a creamy oat taste, perfect for busy mornings when you need a quick meal.'),
      ],
      [
        'title_field' => 'nordic_product_copy_title_3',
        'text_field' => 'nordic_product_copy_text_3',
        'title' => $cv('nordic_product_copy_title_3', 'Quick Cook Oats'),
        'description' => $cv('nordic_product_copy_text_3', 'Cut finer than whole oats so they cook in minutes with a soft bite, great for porridge, smoothies, and baking mixes.'),
      ],
    ];

    $oatsBenefitsCopy = [
      [
        'title_field' => 'nordic_oats_benefits_copy_title_1',
        'text_field' => 'nordic_oats_benefits_body_1',
        'title' => $cv('nordic_oats_benefits_copy_title_1', $cv('nordic_oats_benefits_copy_title', 'Heart Health Support')),
        'description' => $cv('nordic_oats_benefits_body_1', $cv('nordic_oats_benefits_body', 'Oats are rich in fiber, support heart health, and help keep you full longer. They are a good source of nutrients for a balanced daily meal.')),
      ],
      [
        'title_field' => 'nordic_oats_benefits_copy_title_2',
        'text_field' => 'nordic_oats_benefits_body_2',
        'title' => $cv('nordic_oats_benefits_copy_title_2', 'Digestive Wellness'),
        'description' => $cv('nordic_oats_benefits_body_2', 'Their soluble and insoluble fiber blend supports gut health and helps maintain smoother digestion through the day.'),
      ],
      [
        'title_field' => 'nordic_oats_benefits_copy_title_3',
        'text_field' => 'nordic_oats_benefits_body_3',
        'title' => $cv('nordic_oats_benefits_copy_title_3', 'Steady Energy'),
        'description' => $cv('nordic_oats_benefits_body_3', 'Complex carbohydrates release energy gradually, helping you feel fueled and satisfied longer after meals.'),
      ],
    ];

    $zoneStyles = [];
    $fieldStyles = [];
    foreach (config('cms.nordic.zones', []) as $zone) {
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

  @include('frontend.partials.nordic-header')

  <section class="hero-carousel" aria-label="Nordic banners">
    <div class="carousel-shell">
      <div class="carousel-track" id="carouselTrack">
        <div class="carousel-slide">
          <img
            src="{{ $imgUrlById('media_id_nordic_hero_slide_1', 'carousel', 'images/banner/nordic-ve-hero-1.png') }}"
            alt="Oat field and harvest"
          />
        </div>
        <div class="carousel-slide">
          <img
            src="{{ $imgUrlById('media_id_nordic_hero_slide_2', 'carousel', 'images/banner/nordic-ve-hero-2.png') }}"
            alt="Nordic oats in a healthy breakfast bowl"
          />
        </div>
        <div class="carousel-slide">
          <img
            src="{{ $imgUrlById('media_id_nordic_hero_slide_3', 'carousel', 'images/banner/nordic-ve-hero-3.png') }}"
            alt="Rolled oats and grains close-up"
          />
        </div>
      </div>

      <div class="carousel-dots" id="carouselDots">
        <button class="dot active" data-slide="0" aria-label="Go to banner 1"></button>
        <button class="dot" data-slide="1" aria-label="Go to banner 2"></button>
        <button class="dot" data-slide="2" aria-label="Go to banner 3"></button>
      </div>
    </div>
  </section>

  <section class="nordic-section why-nordic" id="why-nordic">
    <div class="section-inner reverse">
      <div class="section-content">
        <h2 id="nordicWhyHeading" data-ve-field="nordic_why_heading">{{ $cv('nordic_why_heading', 'Why Nordic Oats?') }}</h2>
        <div class="text-card text-card2">
          <p class="product-copy-title" id="productCopyTitle" data-ve-field="{{ $productCarouselCopy[0]['title_field'] }}">{{ $productCarouselCopy[0]['title'] }}</p>
          <p class="product-copy-text" id="productCopyText" data-ve-field="{{ $productCarouselCopy[0]['text_field'] }}">
            {{ $productCarouselCopy[0]['description'] }}
          </p>
        </div>
      </div>
      <div class="section-photo">
        <div class="product-carousel" id="productCarousel" aria-label="Nordic oats product showcase" data-copy='@json($productCarouselCopy)'>
          <div class="product-carousel-track" id="productCarouselTrack">
            <figure class="product-slide">
              <img
                src="{{ $imgUrlById('media_id_nordic_product_slide_1', 'product', 'images/banner/nordic-ve-product-1.png') }}"
                alt="Whole Grain Oats"
                loading="lazy"
              />
              <figcaption id="nordicProductCaption1" data-ve-field="nordic_product_caption_1">{{ $cv('nordic_product_caption_1', 'Whole Grain Oats') }}</figcaption>
            </figure>
            <figure class="product-slide">
              <img
                src="{{ $imgUrlById('media_id_nordic_product_slide_2', 'product', 'images/banner/nordic-ve-product-2.png') }}"
                alt="Instant Oats"
                loading="lazy"
              />
              <figcaption id="nordicProductCaption2" data-ve-field="nordic_product_caption_2">{{ $cv('nordic_product_caption_2', 'Instant Oats') }}</figcaption>
            </figure>
            <figure class="product-slide">
              <img
                src="{{ $imgUrlById('media_id_nordic_product_slide_3', 'product', 'images/banner/nordic-ve-product-3.png') }}"
                alt="Quick Cook Oats"
                loading="lazy"
              />
              <figcaption id="nordicProductCaption3" data-ve-field="nordic_product_caption_3">{{ $cv('nordic_product_caption_3', 'Quick Cook Oats') }}</figcaption>
            </figure>
          </div>
          <button class="product-carousel-arrow prev" id="productCarouselPrev" aria-label="Previous Nordic oats image">&#8249;</button>
          <button class="product-carousel-arrow next" id="productCarouselNext" aria-label="Next Nordic oats image">&#8250;</button>
          <div class="product-carousel-dots" id="productCarouselDots">
            <button class="dot active" data-slide="0" aria-label="Show Whole Grain Oats"></button>
            <button class="dot" data-slide="1" aria-label="Show Instant Oats"></button>
            <button class="dot" data-slide="2" aria-label="Show Quick Cook Oats"></button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="nordic-section oats-benefits" id="oats-benefits">
    <div class="oats-benefits-bg" aria-hidden="true"></div>
    <div class="section-inner reverse">
      <div class="section-content">
        <h2 id="nordicOatsBenefitsHeading" data-ve-field="nordic_oats_benefits_heading">{{ $cv('nordic_oats_benefits_heading', 'Oats Benefits') }}</h2>
        <div class="text-card text-card2">
          <p class="product-copy-title" id="oatsBenefitsCopyTitle" data-ve-field="{{ $oatsBenefitsCopy[0]['title_field'] }}">{{ $oatsBenefitsCopy[0]['title'] }}</p>
          <p class="product-copy-text" id="oatsBenefitsCopyText" data-ve-field="{{ $oatsBenefitsCopy[0]['text_field'] }}">
            {{ $oatsBenefitsCopy[0]['description'] }}
          </p>
        </div>
      </div>
      <div class="section-photo">
        <div class="product-carousel" id="oatsBenefitsCarousel" aria-label="Nordic oats benefits showcase" data-copy='@json($oatsBenefitsCopy)'>
          <div class="product-carousel-track" id="oatsBenefitsCarouselTrack">
            <figure class="product-slide">
              <img
                src="{{ $imgUrlById('media_id_nordic_oats_benefits_slide_1', 'product', 'images/banner/nordic-ve-oats-benefits.png') }}"
                alt="Oats benefit highlight one"
                loading="lazy"
              />
              <figcaption id="nordicOatsBenefitsCaption1" data-ve-field="nordic_oats_benefits_caption_1">{{ $cv('nordic_oats_benefits_caption_1', 'Heart Health') }}</figcaption>
            </figure>
            <figure class="product-slide">
              <img
                src="{{ $imgUrlById('media_id_nordic_oats_benefits_slide_2', 'product', 'images/banner/nordic-ve-product-2.png') }}"
                alt="Oats benefit highlight two"
                loading="lazy"
              />
              <figcaption id="nordicOatsBenefitsCaption2" data-ve-field="nordic_oats_benefits_caption_2">{{ $cv('nordic_oats_benefits_caption_2', 'Digestive Wellness') }}</figcaption>
            </figure>
            <figure class="product-slide">
              <img
                src="{{ $imgUrlById('media_id_nordic_oats_benefits_slide_3', 'product', 'images/banner/nordic-ve-product-3.png') }}"
                alt="Oats benefit highlight three"
                loading="lazy"
              />
              <figcaption id="nordicOatsBenefitsCaption3" data-ve-field="nordic_oats_benefits_caption_3">{{ $cv('nordic_oats_benefits_caption_3', 'Steady Energy') }}</figcaption>
            </figure>
          </div>
          <button class="product-carousel-arrow prev" id="oatsBenefitsCarouselPrev" aria-label="Previous oats benefit image">&#8249;</button>
          <button class="product-carousel-arrow next" id="oatsBenefitsCarouselNext" aria-label="Next oats benefit image">&#8250;</button>
          <div class="product-carousel-dots" id="oatsBenefitsCarouselDots">
            <button class="dot active" data-slide="0" aria-label="Show oats benefit 1"></button>
            <button class="dot" data-slide="1" aria-label="Show oats benefit 2"></button>
            <button class="dot" data-slide="2" aria-label="Show oats benefit 3"></button>
          </div>
        </div>
      </div>
    </div>
  </section>

  @include('frontend.partials.connect-with-us', [
    'contactHeadingKey' => 'contact_heading',
    'contactSubtextKey' => 'nordic_contact_subtext',
    'contactPageSlug' => 'nordic',
    'contactBgValue' => $cv('contact_bg', ''),
    'contactSubtext' => $cv(
      'nordic_contact_subtext',
      "Curious about Nordic Oats? Let's talk recipes, nutrition, and how to bring hearty oat goodness to your table."
    ),
  ])

  @include('frontend.partials.nordic-footer')

  <button id="backToTop" aria-label="Back to top"
    style="
      --btt-bg: {{ $cv('back_to_top_bg', '#e6ba40') }};
      --btt-fg: {{ $cv('back_to_top_fg', '#223947') }};
      --btt-hover-bg: {{ $cv('back_to_top_hover_bg', '#f0cf71') }};
    ">
    &#8593;
  </button>

  @php
    $cmsTextStylesNordic = [];
    foreach ($allContents as $row) {
      $k = $row['key'] ?? '';
      if (! is_string($k) || ! preg_match('/^(.+)_(color|font|font_size)$/', $k, $m)) {
        continue;
      }
      $base = $m[1];
      $suffix = $m[2];
      if (! isset($cmsTextStylesNordic[$base])) {
        $cmsTextStylesNordic[$base] = ['color' => '', 'font' => '', 'font_size' => ''];
      }
      $cmsTextStylesNordic[$base][$suffix] = (string) ($row['value'] ?? '');
    }
  @endphp
  @if(! empty($cmsTextStylesNordic))
  <style id="nordic-cms-text-styles">
  @foreach($cmsTextStylesNordic as $base => $st)
  @php
    $partsN = [];
    if (($st['color'] ?? '') !== '') {
      $partsN[] = 'color:' . e($st['color']) . ' !important';
    }
    if (($st['font'] ?? '') !== '') {
      $partsN[] = "font-family:'" . e(str_replace("'", "\\'", $st['font'])) . "',sans-serif";
    }
    if (($st['font_size'] ?? '') !== '') {
      $partsN[] = 'font-size:' . e($st['font_size']);
    }
  @endphp
  @if(count($partsN))
  @php $cssN = implode(';', $partsN); @endphp
    [data-ve-field="{{ e($base) }}"] { {{ $cssN }} }
  @endif
  @endforeach
  </style>
  @endif

  <script src="{{ asset('js/nordic.js') }}"></script>
</body>
</html>
