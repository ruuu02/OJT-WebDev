<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ULTRAFOOD</title>
  <link rel="icon" type="image/png" href="{{ asset('images/favicon/Ultrafood-Favicon.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

  {{-- Anek Latin for headings, Spline Sans for body --}}
  <link href="https://fonts.googleapis.com/css2?family=Anek+Latin:wght@300;400;500;600;700;800&family=Spline+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
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

  <link rel="stylesheet" href="{{ asset('css/ultrafood/base.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/reveal.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/header.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/hero.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/about.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/mission.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/strengths.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/brands.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/floaters.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/history.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/contact.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/footer.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/backtotop.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/responsive.css') }}">
  <link rel="stylesheet" href="{{ asset('css/ultrafood/map.css') }}">


</head>
<body class="font-body" data-is-visual-editor="{{ !empty($isVisualEditor) ? '1' : '0' }}">

{{-- ══════════════ DB HELPERS ══════════════ --}}
@php
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\File;
  use App\Models\Page;

  // ── Text content ──────────────────────────────────────────────────────────
  // When included by the visual editor, $allContents is already passed (scoped to active page).
  // Standalone '/' must load only this page's rows — keys like menu_footer_email_color exist per page_id;
  // a global query would mix Menu vs Ultrafood and break saved colors/copy for visitors.
  if (!isset($allContents)) {
      $pageId = Page::where('slug', 'ultrafood')->value('id');
      $allContents = DB::table('text_content')
          ->when($pageId, fn ($q) => $q->where('page_id', $pageId))
          ->get()
          ->map(fn($r) => ['key' => $r->key, 'value' => $r->value, 'section' => 'text_content']);
  }
  $cv = fn(string $key, string $default = '') =>
      ($allContents->firstWhere('key', $key)['value'] ?? null) ?? $default;

  // ── Media ─────────────────────────────────────────────────────────────────
  // Same lazy-load pattern: use passed $allMedia or query DB.
  if (!isset($allMedia)) {
      $allMedia = collect()
          ->merge(DB::table('carousel_images')->where('is_active', true)->get()->map(fn($r) => array_merge((array)$r, ['section' => 'carousel'])))
          ->merge(DB::table('logos')->where('is_active', true)->get()->map(fn($r) => array_merge((array)$r, ['section' => 'logo'])))
          ->merge(DB::table('product_images')->where('is_active', true)->get()->map(fn($r) => array_merge((array)$r, ['section' => 'product'])))
          ->merge(DB::table('recipe_images')->where('is_active', true)->get()->map(fn($r) => array_merge((array)$r, ['section' => 'recipe'])));
  }

  // ── Contacts (phone/email/links) ──────────────────────────────────────────
  if (!isset($allContacts)) {
      $allContacts = collect()
          ->merge(DB::table('contact_numbers')->get()->map(fn($r) => array_merge((array)$r, ['type' => 'phone'])))
          ->merge(DB::table('email_addresses')->get()->map(fn($r) => array_merge((array)$r, ['type' => 'email'])))
          ->merge(DB::table('links')->get()->map(fn($r) => array_merge((array)$r, ['type' => 'link'])));
  }
  $allContacts = collect($allContacts);

  $firstContactValue = function (string $type, string $fallback) use ($allContacts): string {
      $row = $allContacts->firstWhere('type', $type);
      return (string) (($row['value'] ?? $row['number'] ?? $row['email'] ?? '') ?: $fallback);
  };

  $telHref = function (string $raw): string {
      $clean = preg_replace('/[^0-9+]/', '', $raw);
      return 'tel:' . ($clean ?: $raw);
  };

  // Returns the public asset URL for image at $idx (0-based, ordered by id)
  // within $section. Uses fallback if no DB row or if the DB file is missing on disk
  // (e.g. uploaded image was deleted). Uses a transparent 1x1 pixel if fallback file also missing.
  $imgUrl = function(string $section, int $idx, string $fallback) use ($allMedia): string {
      $dirs = ['carousel' => 'banner', 'logo' => 'logo', 'product' => 'banner', 'recipe' => 'icons'];
      $dir  = $dirs[$section] ?? $section;
      $row  = $allMedia->filter(fn($r) => $r['section'] === $section)
                       ->sortBy('id')->values()->get($idx);
      if ($row) {
          $dbPath = "images/{$dir}/{$row['filename']}";
          if (File::exists(public_path($dbPath))) {
              return asset($dbPath);
          }
      }
      if (File::exists(public_path($fallback))) {
          return asset($fallback);
      }
      // Placeholder so the banner always shows something (no broken image icon)
      $w = 1200; $h = 400;
      $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$w.'" height="'.$h.'" viewBox="0 0 '.$w.' '.$h.'"><rect width="100%" height="100%" fill="#f4fae8"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="32" fill="#4B7A08">Welcome</text></svg>';
      return 'data:image/svg+xml;base64,' . base64_encode($svg);
  };

  // Looks up a single-image zone by its stable DB ID stored in text_content under $mediaKey.
  // Falls back to $fallback if the key or file is missing.
  $imgUrlById = function(string $mediaKey, string $section, string $fallback) use ($cv, $allMedia): string {
      $dirs = ['carousel' => 'banner', 'logo' => 'logo', 'product' => 'banner', 'recipe' => 'icons'];
      $dir  = $dirs[$section] ?? $section;
      $id   = $cv($mediaKey, '');
      if ($id !== '') {
          $row = $allMedia->first(fn($r) => $r['section'] === $section && (string)$r['id'] === $id);
          if ($row) {
              $dbPath = "images/{$dir}/{$row['filename']}";
              if (File::exists(public_path($dbPath))) {
                  return asset($dbPath);
              }
          }
      }
      if (File::exists(public_path($fallback))) {
          return asset($fallback);
      }
      return asset($fallback);
  };

  // ── Nav links ─────────────────────────────────────────────────────────────
  $navLinks = [
    ['#about',     'nav_about',     'About Us'],
    ['#mission',   'nav_mission',   'Purpose'],
    ['#strengths', 'nav_strengths', 'Strengths'],
    ['#brands',    'nav_brands',    'Brands'],
    ['#history',   'nav_history',   'History'],
    ['#clients',   'nav_clients',   'Clients'],
    ['#contact',   'nav_contact',   'Contact'],
  ];

  // ── History background images ─────────────────────────────────────────────
  // Looked up by stable DB ID stored in text_content (key = history_bg_id_N).
  // This is immune to positional-index drift if other carousel images are added.
  $histBgUrls = [];
  for ($i = 0; $i < 7; $i++) {
    $bgId      = $cv('history_bg_id_' . $i, '');
    $fallback  = asset('images/banner/UFDI-History-Timeline-BG-' . ($i + 1) . '.png');
    if ($bgId) {
      $bgRow = $allMedia->filter(fn($r) => $r['section'] === 'carousel' && (string)$r['id'] === $bgId)->first();
      $histBgUrls[$i] = $bgRow ? asset("images/banner/{$bgRow['filename']}") : $fallback;
    } else {
      $histBgUrls[$i] = $fallback;
    }
  }

  $floaterAboutBg     = $imgUrlById('media_id_about_floater_image', 'product', 'images/ultrafood/Ultrafood-Floater-1.png');
  $floaterStrengthsBg = $imgUrlById('media_id_strengths_floater_image', 'product', 'images/ultrafood/Ultrafood-Floater-2.png');
  $floaterBrandsBg    = $imgUrlById('media_id_brands_floater_image', 'product', 'images/ultrafood/Ultrafood-Floater-3.png');
  $cssLengthValue = function (string $key) use ($cv): string {
      $value = trim($cv($key, ''));
      if ($value === '') return '';
      return preg_match('/^(auto|[0-9]+(?:\.[0-9]+)?(?:px|rem|em|vw|vh|%)|calc\([0-9a-zA-Z.%+\-*\/\s]+\))$/', $value)
          ? $value
          : '';
  };
  $floaterStyle = function (string $widthKey, string $heightKey) use ($cssLengthValue): string {
      $width = $cssLengthValue($widthKey);
      $height = $cssLengthValue($heightKey);
      $style = [];
      if ($width !== '') {
          $style[] = "width: {$width}";
          $style[] = 'max-width: none';
      }
      if ($height !== '') {
          $style[] = "height: {$height}";
          $style[] = 'object-fit: contain';
      }
      return implode('; ', $style);
  };
  $floaterAboutStyle     = $floaterStyle('about_floater_width', 'about_floater_height');
  $floaterStrengthsStyle = $floaterStyle('strengths_floater_width', 'strengths_floater_height');
  $floaterBrandsStyle    = $floaterStyle('brands_floater_width', 'brands_floater_height');
@endphp

{{-- ══════════════ DB-DRIVEN CSS VARIABLES ══════════════ --}}
{{-- Only output when the admin has saved custom colors via the style modal. --}}
{{-- The CSS files already use var(--btt-bg) / var(--contact-bg) with fallbacks, --}}
{{-- so omitting this block when no values are stored is safe. --}}
@php
  $bttBg       = $cv('back_to_top_bg', '');
  $bttFg       = $cv('back_to_top_fg', '');
  $bttHoverBg  = $cv('back_to_top_hover_bg', '');
  $headerBg    = $cv('header_bg', '');
  $heroBg      = $cv('hero_bg', '');
  $aboutBg     = $cv('about_bg', '');
  $missionBg   = $cv('mission_bg', '');
  $strengthsBg = $cv('strengths_bg', '');
  $brandsBg    = $cv('brands_bg', '');
  $historyBg   = $cv('history_bg', '');
  $clientsBg   = $cv('clients_bg', '');
  $contactBg   = $cv('contact_bg', '');
  $footerBg    = $cv('footer_bg', '');
@endphp
@if($bttBg || $bttFg || $bttHoverBg || $headerBg || $heroBg || $aboutBg || $missionBg || $strengthsBg || $brandsBg || $historyBg || $clientsBg || $contactBg || $footerBg)
<style>
  #backToTop  {
    {{ $bttBg      ? "--btt-bg:{$bttBg};"            : '' }}
    {{ $bttFg      ? "--btt-fg:{$bttFg};"            : '' }}
    {{ $bttHoverBg ? "--btt-hover-bg:{$bttHoverBg};" : '' }}
  }
  #header     { {{ $headerBg    ? "--header-bg:{$headerBg};"       : '' }} }
  #hero       { {{ $heroBg      ? "--hero-bg:{$heroBg};"           : '' }} }
  #about      { {{ $aboutBg     ? "--about-bg:{$aboutBg};"         : '' }} }
  #mission    { {{ $missionBg   ? "--mission-bg:{$missionBg};"     : '' }} }
  #strengths  { {{ $strengthsBg ? "--strengths-bg:{$strengthsBg};" : '' }} }
  #brands     { {{ $brandsBg    ? "--brands-bg:{$brandsBg};"       : '' }} }
  #history    { {{ $historyBg   ? "--history-bg:{$historyBg};"     : '' }} }
  #clients    { {{ $clientsBg   ? "--clients-bg:{$clientsBg};"     : '' }} }
  #contact    { {{ $contactBg   ? "--contact-bg:{$contactBg};"     : '' }} }
  footer      { {{ $footerBg    ? "--footer-bg:{$footerBg};"       : '' }} }
</style>
@endif

{{-- ══════════════ DB-DRIVEN TEXT ZONE STYLES ══════════════ --}}
{{-- Zone-level rules (first-field color/font as section fallback) plus     --}}
{{-- per-field rules targeting [data-ve-field] attribute selectors.         --}}
@php
  $zoneStyles  = [];
  $fieldStyles = [];
  foreach (config('cms.ultrafood.zones', []) as $zone) {
      if (($zone['type'] ?? '') !== 'text') continue;

      // Zone-level fallback: first field's saved style → zone selector
      $firstKey = ($zone['fields'] ?? [])[0] ?? null;
      if ($firstKey) {
          $parts = [];
          $color = $cv($firstKey . '_color', '');
          $font  = $cv($firstKey . '_font',  '');
          $size  = $cv($firstKey . '_font_size', '');
          if ($color) $parts[] = "color:{$color} !important";
          if ($font)  $parts[] = "font-family:'{$font}',sans-serif";
          if ($size)  $parts[] = "font-size:{$size}";
          if ($parts) $zoneStyles[$zone['selector']] = implode(';', $parts);
      }

      // Per-field rules: [data-ve-field="key"] selectors override zone-level
      foreach (($zone['fields'] ?? []) as $fieldKey) {
          $parts = [];
          $color = $cv($fieldKey . '_color', '');
          $font  = $cv($fieldKey . '_font',  '');
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

{{-- ══════════════ HEADER ══════════════ --}}
<header id="header" class="fixed top-0 z-50 px-6 lg:px-10">
  <div class="max-w-7xl mx-auto flex items-center justify-between h-24">

    <a href="#hero" class="flex items-center flex-shrink-0">
      <img src="{{ $imgUrlById('media_id_header_logo', 'logo', 'images/logo/Ultrafood-Distributors-Inc-Logo.png') }}" alt="Ultrafood Distributors Inc" class="h-20 w-auto" />
    </a>

    <nav id="desktopNav" class="hidden md:flex items-center gap-1 ml-auto">
      @foreach ($navLinks as [$href, $key, $default])
        <a href="{{ $href }}" data-ve-field="{{ $key }}" class="nav-link text-sm font-medium tracking-wide px-4 py-2 rounded-lg uppercase">{{ $cv($key, $default) }}</a>
      @endforeach
    </nav>

    <button id="burger" class="flex md:hidden flex-col gap-[5px] p-2 bg-transparent border-0 cursor-pointer ml-auto" aria-label="Menu">
      <span class="burger-bar"></span>
      <span class="burger-bar"></span>
      <span class="burger-bar"></span>
    </button>

  </div>

  <nav id="mobileNav">
    @foreach ($navLinks as [$href, $key, $default])
      <a href="{{ $href }}" class="mobile-nav-link">{{ $cv($key, $default) }}</a>
    @endforeach
  </nav>

</header>


{{-- ══════════════ HERO ══════════════ --}}
<div id="hero-wrapper">
<section id="hero" class="relative overflow-hidden">

  <div class="slide active">
    <img src="{{ $imgUrl('carousel', 0, 'images/banner/UFDI-Welcome.png') }}" alt="Welcome Banner" class="slide-img" fetchpriority="high" decoding="async" />
    <div class="slide-overlay"></div>
  </div>

  <div class="slide">
    <img src="{{ $imgUrl('carousel', 1, 'images/banner/UFDI-Our-Brands-Banner.png') }}" alt="Our Brands Banner" class="slide-img" loading="lazy" decoding="async" />
    <div class="slide-overlay"></div>
  </div>

  <div class="slide">
    <img src="{{ $imgUrl('carousel', 2, 'images/banner/UFDI-Accreditations-Banner.png') }}" alt="Accreditations Banner" class="slide-img" loading="lazy" decoding="async" />
    <div class="slide-overlay"></div>
  </div>

  <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-3 z-20">
    <button class="carousel-dot active" aria-label="Slide 1"></button>
    <button class="carousel-dot" aria-label="Slide 2"></button>
    <button class="carousel-dot" aria-label="Slide 3"></button>
  </div>

</section>
</div>


{{-- ══════════════ ABOUT ══════════════ --}}
<section id="about" class="py-20 bg-white">
  <div class="about-inner">

    <div class="about-img-wrap reveal-left img-zoom">
      <img src="{{ $imgUrlById('media_id_about_image', 'product', 'images/banner/UFDI-Intro-Image.png') }}" alt="About UltraFood" class="w-full h-full object-cover" />
    </div>

    <div class="about-text reveal-right">
      <h2>{{ $cv('about_heading', 'ULTRAFOOD DISTRIBUTORS INC.') }}</h2>
      <p class="about-desc">{{ $cv('about_para1', 'Has steadily gained recognition as a professional and reputable organization in the Fast-Moving Consumer Goods (FMCG) sector. Our commitment to excellence, combined with our dedication to building strong and long-term partnerships, made us a trusted brand in this fast-paced industry.') }}</p>
      <p class="about-desc">{{ $cv('about_para2', 'We are driven by a desire to meet the needs of our customers, and this inspires us to continually strive for product creation and innovation while remaining dedicated to delivering high-quality food solutions.') }}</p>
    </div>

  </div>
</section>

<div class="uf-floater-band uf-floater-band--about" aria-hidden="true">
  <div class="uf-floater uf-floater--right">
    <img
      src="{{ $floaterAboutBg }}"
      alt=""
      class="uf-floater-media reveal-right td1"
      @if($floaterAboutStyle) style="{{ $floaterAboutStyle }}" @endif
      loading="lazy"
      decoding="async"
    />
  </div>
</div>

{{-- ══════════════ MISSION & VISION ══════════════ --}}
<section id="mission" class="overflow-hidden py-12 sm:py-16">

  <div class="mv-two-col-wrap reveal-up">

    <div class="mv-card">
      <div class="mv-card-icon-wrap">
        <img src="{{ $imgUrlById('media_id_mission_icon', 'recipe', 'images/icons/Mission-Icon.png') }}" alt="Mission icon" class="mv-card-icon" />
      </div>
      <span class="mv-card-label">MISSION</span>
      <p class="mv-card-desc">{{ $cv('mission_description', 'To provide high-quality food products that meet the diverse needs of our consumers. We are committed to innovation and providing our customers the greatest opportunity for success in their marketplace.') }}</p>
    </div>

    <div class="mv-card">
      <div class="mv-card-icon-wrap">
        <img src="{{ $imgUrlById('media_id_vision_icon', 'recipe', 'images/icons/Vision-Icon.png') }}" alt="Vision icon" class="mv-card-icon" />
      </div>
      <span class="mv-card-label">VISION</span>
      <p class="mv-card-desc">{{ $cv('vision_description', 'To be an industry leader in providing high-quality, innovative food products to our customers and to become a major player in the Philippine manufacturing and distribution business as a professional and reputable organization.') }}</p>
    </div>

  </div>

</section>

<div class="uf-floater-band uf-floater-band--strengths" aria-hidden="true">
  <div class="uf-floater uf-floater--left">
    <img
      src="{{ $floaterStrengthsBg }}"
      alt=""
      class="uf-floater-media reveal-left td2"
      @if($floaterStrengthsStyle) style="{{ $floaterStrengthsStyle }}" @endif
      loading="lazy"
      decoding="async"
    />
  </div>
</div>

{{-- ══════════════ STRENGTHS ══════════════ --}}
<section id="strengths" class="strengths-section">
  <div class="max-w-6xl mx-auto px-6 sm:px-8">

    <div class="reveal-up strengths-heading text-center flex flex-col items-center gap-2">
      <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-black">
        {{ $cv('strengths_heading', 'WHAT SET US APART') }}
      </h2>
    </div>

    @php
      $strengthCards = [
        [
          'title' => $cv('strength_1_title', 'WIDE DISTRIBUTION'),
          'desc'  => $cv('strength_1_desc',  'Extensive distribution network reaching both rural and urban areas, modern trade, and traditional retail stores across the Philippines.'),
          'icon'  => $imgUrlById('media_id_strength_1_icon', 'recipe', 'images/icons/Wide-Distribution-Icon.png'),
        ],
        [
          'title' => $cv('strength_2_title', 'PRODUCT DIVERSITY'),
          'desc'  => $cv('strength_2_desc',  'Expanding product offerings to meet the diverse needs of our consumers.'),
          'icon'  => $imgUrlById('media_id_strength_2_icon', 'recipe', 'images/icons/Product-Diversity-Icon.png'),
        ],
        [
          'title' => $cv('strength_3_title', 'INNOVATIVE'),
          'desc'  => $cv('strength_3_desc',  'Continuous product innovation and development maintaining market relevance and trends.'),
          'icon'  => $imgUrlById('media_id_strength_3_icon', 'recipe', 'images/icons/Innovative-Icon.png'),
        ],
        [
          'title' => $cv('strength_4_title', 'LOGISTIC EFFICIENCY'),
          'desc'  => $cv('strength_4_desc',  'Ensures our valued consumers access our products anywhere and anytime by optimizing product shipments timely and effectively.'),
          'icon'  => $imgUrlById('media_id_strength_4_icon', 'recipe', 'images/icons/Logistic-Efficiency-Icon.png'),
        ],
      ];
    @endphp

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 strengths-grid">
  @foreach ($strengthCards as $idx => $item)
    <div class="strength-card-wrapper">
      <div class="strength-card" id="strengthCard{{ $idx + 1 }}">
        <img
          class="strength-icon"
          src="{{ $item['icon'] }}"
          alt="{{ $item['title'] }} icon"
          loading="lazy"
        />
        <h4>{{ $item['title'] }}</h4>
        <p>{{ $item['desc'] }}</p>
      </div>
    </div>
  @endforeach
</div>

  </div>
</section>

{{-- ══════════════ BRANDS ══════════════ --}}
<div class="uf-floater-band uf-floater-band--brands" aria-hidden="true">
  <div class="uf-floater uf-floater--right">
    <img
      src="{{ $floaterBrandsBg }}"
      alt=""
      class="uf-floater-media reveal-right td3"
      @if($floaterBrandsStyle) style="{{ $floaterBrandsStyle }}" @endif
      loading="lazy"
      decoding="async"
    />
  </div>
</div>

<section id="brands" class="py-14 sm:py-16 lg:py-20">
  <div class="max-w-5xl mx-auto px-6 sm:px-8">

    <div class="reveal-up text-center mb-10 sm:mb-12 flex flex-col items-center gap-2">
      <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-black brands-heading">
        {{ $cv('brands_heading', 'DISTINCT BRANDS, ONE VISION') }}
      </h2>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-2 gap-3 sm:gap-8">

      <a href="{{ route('menu') }}" id="brandMenuCard"
         class="brand-card reveal-up rounded-xl overflow-hidden hover:-translate-y-1.5 hover:shadow-xl transition-all duration-400">
        <div class="brand-img-wrap relative flex items-center justify-center min-h-[200px] sm:min-h-[220px]">
          <img src="{{ $imgUrlById('media_id_brand_menu_logo', 'logo', 'images/logo/Menu-menutong-Sarap.png') }}"
               alt="Menu Food"
               class="brand-menu-logo w-auto" />
        </div>
        <div class="px-6 py-5">
          <h3 class="font-display text-xl font-bold mb-1.5 brand-card-title">{{ $cv('brand_menu_title', 'Menu Food Solutions') }}</h3>
          <p class="text-xs leading-relaxed font-bold italic brand-card-tagline">
            &ldquo;{{ $cv('brand_menu_tagline', 'Sarap-saya sa bawat bite!') }}&rdquo;
          </p>
          <p class="text-xs leading-relaxed brand-card-desc">
            {{ $cv('brand_menu_desc', 'Ginagawang masarap at unforgettable ang bawat recipe. Halina at tikman ang next big taste na siguradong magpapasaya at magpapabusog sa lahat!') }}
          </p>
        </div>
      </a>

      <a href="{{ route('nordic') }}" id="brandNordicCard"
         class="brand-card reveal-up td1 rounded-xl overflow-hidden hover:-translate-y-1.5 hover:shadow-xl transition-all duration-400">
        <div class="brand-img-wrap relative flex items-center justify-center min-h-[200px] sm:min-h-[220px]">
          <img src="{{ $imgUrlById('media_id_brand_nordic_logo', 'logo', 'images/logo/Korpala-Nordic-Logo.png') }}"
               alt="Korpala Nordic"
               class="brand-nordic-logo w-auto" />
        </div>
        <div class="px-6 py-5">
          <h3 class="font-display text-xl font-bold mb-1.5 brand-card-title">{{ $cv('brand_nordic_title', 'Nordic Foods PH') }}</h3>
          <p class="text-xs leading-relaxed font-bold italic brand-card-tagline">
            &ldquo;{{ $cv('brand_nordic_tagline', 'Finland straight to your taste buds.') }}&rdquo;
          </p>
          <p class="text-xs leading-relaxed brand-card-desc">
            {{ $cv('brand_nordic_desc', 'Wholesome goodness, premium ingredients — all thoughtfully combined to create a bowl experience that is both satisfying and nourishing.') }}
          </p>
        </div>
      </a>

    </div>
  </div>
</section>


{{-- ══════════════ HISTORY ══════════════ --}}
<section id="history" class="history-section py-14 sm:py-16 lg:py-20">
  <div class="history-overlay"></div>

  <div class="max-w-5xl mx-auto px-6 sm:px-8 relative z-10">

    <div class="reveal-up text-center mb-8 flex flex-col items-center gap-2">
      <h2 class="font-display text-3xl sm:text-4xl lg:text-5xl font-black">{{ $cv('history_heading', 'OUR JOURNEY THROUGH TIME') }}</h2>
    </div>

    @php
      $histItems = [
        [$cv('history_2005_year', '2005'), $cv('history_2005_desc', 'Founded in Sep tember 2005 as Appenzell Inc., ULTRAFOOD DISTRIBUTORS INC. specialized in the distribution of powdered and liquid condiment products catering to both the General Trade and HORECA. Following the initial launch, broth cubes were quickly developed and gained popularity in the market.')],
        [$cv('history_2006_year', '2006'), $cv('history_2006_desc', 'Offering a wide variety of delicious jelly flavors, MENU Jelly Powder Mixes earned the trust of consumers across different markets with its consistency and taste. Following this, the MENU Soups and Mixes was officially launched, and the brand continued to expand, gaining recognition for delivering rich flavors, convenience, and quality that satisfy consumer needs.')],
        [$cv('history_2010_year', '2010'), $cv('history_2010_desc', 'Secured partnerships with major supermarkets and grocery stores nationwide. Through consistent quality and consumer trust, the brand earned a strong presence in key retail accounts and became a trusted choice in the market.')],
        [$cv('history_2011_year', '2011'), $cv('history_2011_desc', 'Participated in food expos, livelihood activities, and events to showcase varieties of products. These participations broadened the market reach and growing demand in the industry. ULTRAFOOD DISTRIBUTORS INC. has been a proud member of HRAP, which allows them to stay connected with industry developments, best practices, and new trends.')],
        [$cv('history_2017_year', '2017'), $cv('history_2017_desc', 'Embraced digital transformation by introducing and selling our products through e-commerce platforms. This provides greater convenience for customers to access our products anytime and anywhere.')],
        [$cv('history_2022_year', '2022'), $cv('history_2022_desc', 'Established a new office building and warehouse designed to improve operational efficiency and support the growing team. This enhances our services, streamlines processes, and allows us to respond quickly to the needs of our clients and partners.')],
        [$cv('history_2024_year', '2024'), $cv('history_2024_desc', "Our company is proud to hold the HALAL Registration Certificate issued by the Islamic Da'wah Council of the Philippines (IDCP), reflecting our commitment to the highest standards of quality and ethical compliance. This affirms that our products comply with HALAL requirements, ensuring integrity and trust to our valued clients.")],
      ];
    @endphp

    <div class="hist-center-col">
 
      <div class="hist-carousel-viewport">
        <div class="hist-carousel-track" id="histCarouselTrack">
          <div class="hist-cards-container" id="histCardsContainer">
            @foreach ($histItems as $i => [$year, $desc])
            <div class="history-card {{ $i === 0 ? 'active' : '' }}" data-idx="{{ $i }}" id="histCard{{ $i }}">
              <div class="history-card-inner">
                <img id="histBgRef{{ $i }}" class="hist-bg-ref"
                     src="{{ $histBgUrls[$i] }}"
                     style="display:none" aria-hidden="true" alt="" />
                
                <div class="hist-peek-label">
                  <span class="hist-peek-year">{{ $year }}</span>
                </div>
                
                <div class="hist-card-content {{ $i === 0 ? 'active' : '' }}">
                  <span class="hist-watermark">{{ $year }}</span>
                  <p class="hist-description-text">{{ $desc }}</p>
                </div>
              </div>
            </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="hist-timeline-wrap" id="histTimelineWrap">
        <button id="histPrev" aria-label="Previous">&#8592;</button>

        <div class="hist-page active-page" data-page="0">
          <div class="hist-page-line"></div>
          @foreach (array_slice($histItems, 0, 4) as $i => [$year,])
            <button class="hist-dot {{ $i === 0 ? 'active' : '' }}" data-idx="{{ $i }}" aria-label="{{ $year }}">
              <span class="yr">{{ $year }}</span>
            </button>
          @endforeach
        </div>

        <div class="hist-page" data-page="1">
          <div class="hist-page-line"></div>
          @foreach (array_slice($histItems, 4) as $j => [$year,])
            <button class="hist-dot" data-idx="{{ $j + 4 }}" aria-label="{{ $year }}">
              <span class="yr">{{ $year }}</span>
            </button>
          @endforeach
        </div>

        <button id="histNext" aria-label="Next">&#8594;</button>
      </div>

    </div>

  </div>
</section>

{{-- ══════════════ CLIENTS ══════════════ --}}
<section id="clients" class="uf-clients">
  <div class="uf-clients-inner">

    <div class="uf-clients-title reveal-up" id="clientsTitle">{{ $cv('clients_title', 'CLIENTS MAP') }}</div>

    <div class="uf-grid reveal-up">

      {{-- ── Legend ── --}}
      <div class="uf-legend" id="clientsLegend">

        <div class="uf-legend-title" id="legendTitle">{{ $cv('legend_title', 'LEGEND') }}</div>

        {{-- Exporters — blue --}}
        <div class="uf-legend-item" id="legendItemExporters">
          <div class="uf-legend-row">
            <span class="uf-legend-pin">
              <svg viewBox="0 0 24 24" fill="#4a90d9"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </span>
            <span class="uf-legend-label" id="legendLabelExporters">{{ $cv('legend_exporters', 'Exporters') }}</span>
          </div>
        </div>

        {{-- Commissaries & Restaurants — yellow --}}
        <div class="uf-legend-item" id="legendItemCommissaries">
          <div class="uf-legend-row">
            <span class="uf-legend-pin">
              <svg viewBox="0 0 24 24" fill="#f5c800"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </span>
            <span class="uf-legend-label" id="legendLabelCommissaries">{{ $cv('legend_commissaries', 'Commissaries &amp; Restaurants') }}</span>
          </div>
        </div>

        {{-- Institutional — red --}}
        <div class="uf-legend-item" id="legendItemInstitutional">
          <div class="uf-legend-row">
            <span class="uf-legend-pin">
              <svg viewBox="0 0 24 24" fill="#e02020"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </span>
            <span class="uf-legend-label" id="legendLabelInstitutional">{{ $cv('legend_institutional', 'Institutional') }}</span>
          </div>
          <div class="uf-legend-subs">
            <span>• Health Institutions</span>
            <span>• Schools and Universities</span>
            <span>• Government Agencies</span>
          </div>
        </div>

        {{-- Franchising — green --}}
        <div class="uf-legend-item" id="legendItemFranchising">
          <div class="uf-legend-row">
            <span class="uf-legend-pin">
              <svg viewBox="0 0 24 24" fill="#5ecb3e"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </span>
            <span class="uf-legend-label" id="legendLabelFranchising">{{ $cv('legend_franchising', 'Franchising') }}</span>
          </div>
        </div>

        {{-- Caterings — orange --}}
        <div class="uf-legend-item" id="legendItemCaterings">
          <div class="uf-legend-row">
            <span class="uf-legend-pin">
              <svg viewBox="0 0 24 24" fill="#f5820a"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </span>
            <span class="uf-legend-label" id="legendLabelCaterings">{{ $cv('legend_caterings', 'Caterings') }}</span>
          </div>
        </div>

        {{-- Supermarkets — purple --}}
        <div class="uf-legend-item" id="legendItemSupermarkets">
          <div class="uf-legend-row">
            <span class="uf-legend-pin">
              <svg viewBox="0 0 24 24" fill="#c966e0"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </span>
            <span class="uf-legend-label" id="legendLabelSupermarkets">{{ $cv('legend_supermarkets', 'Supermarkets') }}</span>
          </div>
          <div class="uf-legend-subs">
            <span>• National Key Accounts</span>
            <span>&nbsp;&nbsp;– SM</span>
            <span>&nbsp;&nbsp;– Puregold</span>
            <span>• Local Key Accounts</span>
            <span>&nbsp;&nbsp;– Cash &amp; Carry</span>
            <span>&nbsp;&nbsp;– Unimart</span>
            <span>• Other Leading Supermarkets</span>
          </div>
        </div>

        {{-- General Trade — teal --}}
        <div class="uf-legend-item" id="legendItemGeneralTrade">
          <div class="uf-legend-row">
            <span class="uf-legend-pin">
              <svg viewBox="0 0 24 24" fill="#1fc8c8"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </span>
            <span class="uf-legend-label" id="legendLabelGeneralTrade">{{ $cv('legend_general_trade', 'General Trade') }}</span>
          </div>
        </div>

        {{-- Distributors — grey --}}
        <div class="uf-legend-item" id="legendItemDistributors">
          <div class="uf-legend-row">
            <span class="uf-legend-pin">
              <svg viewBox="0 0 24 24" fill="#cccccc" stroke="#888" stroke-width="0.5"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </span>
            <span class="uf-legend-label" id="legendLabelDistributors">{{ $cv('legend_distributors', 'Distributors') }}</span>
          </div>
          <div class="uf-legend-subs">
            <span>• Pharmacies</span>
            <span>• Sari-Sari Stores</span>
            <span>• Market Stalls</span>
            <span>• Specialty Shops</span>
            <span>• Cooperatives</span>
            <span>• Canteens</span>
          </div>
        </div>

        {{-- Online Shops — BLACK --}}
        <div class="uf-legend-item" id="legendItemOnlineShops">
          <div class="uf-legend-row">
            <span class="uf-legend-pin">
              <svg viewBox="0 0 24 24" fill="#111111"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </span>
            <span class="uf-legend-label" id="legendLabelOnlineShops">{{ $cv('legend_online_shops', 'Online Shops') }}</span>
          </div>
          <div class="uf-legend-subs">
            <span>• Shopee</span>
            <span>• Lazada</span>
            <span>• TikTok Shop</span>
          </div>
        </div>

      </div>{{-- /uf-legend --}}

      {{-- ── Map ── --}}
      <div class="uf-map-wrap">
        <img
          src="{{ $imgUrlById('media_id_clients_map', 'product', 'images/banner/UFDI-Customer-and-Clients-Map.png') }}"
          alt="Customer and Clients Map"
        />
      </div>

    </div>{{-- /uf-grid --}}
  </div>{{-- /uf-clients-inner --}}
</section>

{{-- ══════════════ CONTACT ══════════════ --}}
<section id="contact" class="relative overflow-hidden"
  style="--contact-bg: {{ $cv('contact_bg', '#79CE1B') }};">

  <div class="absolute bottom-0 left-0 w-80 h-80 rounded-full blur-[70px] -translate-x-1/3 translate-y-1/3 pointer-events-none" style="background:rgba(26,46,4,0.08);"></div>

  <div class="reveal-up px-6 sm:px-10 lg:px-16" style="padding-top: 1px;">
    <div class="relative overflow-hidden rounded-2xl shadow-2xl">

      <div class="map-placeholder absolute inset-0 flex flex-col items-center justify-center gap-3 z-0 pointer-events-none select-none">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="rgba(26,46,4,0.5)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
          <circle cx="12" cy="9" r="2.5"/>
        </svg>
        <span style="color:rgba(26,46,4,0.50); font-size:0.75rem; font-weight:600; letter-spacing:.1em; text-transform:uppercase;">Loading map&hellip;</span>
      </div>
    </div>
  </div>

  <div class="max-w-4xl mx-auto px-6 sm:px-8 relative z-10" style="padding-top: 16px; padding-bottom: 16px;">

    <div class="reveal-up text-center mb-2 flex flex-col items-center gap-2">
      <h2 class="contact-heading font-display text-2xl sm:text-3xl font-black">{{ $cv('contact_heading', 'CONNECT WITH US!') }}</h2>
      <p class="contact-subtext text-sm">{{ $cv('contact_subtext', "Hungry for answers? Let's cook something up together — send us a bite of your thoughts!") }}</p>
    </div>

    <form
      id="contactForm"
      class="contact-form-card reveal-up td1 p-6 sm:p-8"
      method="POST"
      action="{{ route('contact.send') }}"
      data-submit-url="{{ route('contact.send') }}"
      data-page-slug="ultrafood"
    >
      @csrf
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div class="flex flex-col gap-1.5">
          <label class="contact-label">{{ $cv('contact_form_company_label', 'Company Name') }}</label>
          <input type="text" name="company" placeholder="{{ $cv('contact_form_company_ph', 'e.g. Acme Corp') }}" required class="contact-input" />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="contact-label">{{ $cv('contact_form_industry_label', 'Industry') }}</label>
          <input type="text" name="industry" placeholder="{{ $cv('contact_form_industry_ph', 'e.g. Food & Beverage') }}" class="contact-input" />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="contact-label">{{ $cv('contact_form_name_label', 'Full Name') }}</label>
          <input type="text" name="name" placeholder="{{ $cv('contact_form_name_ph', 'Your name') }}" required class="contact-input" />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="contact-label">{{ $cv('contact_form_email_label', 'Email Address') }}</label>
          <input type="email" name="email" placeholder="{{ $cv('contact_form_email_ph', 'you@company.com') }}" required class="contact-input" />
        </div>

        <div class="sm:col-span-2 flex flex-col gap-1.5">
          <label class="contact-label">{{ $cv('contact_form_message_label', 'Message') }}</label>
          <textarea name="message" placeholder="{{ $cv('contact_form_message_ph', 'Tell us about your inquiry...') }}" required rows="4" class="contact-input" style="resize:none;"></textarea>
        </div>

      </div>

      <div class="mt-5 flex justify-end">
        <button type="submit"
          class="contact-submit-btn inline-flex items-center gap-2 font-bold text-xs tracking-wide px-6 py-2.5 rounded-full transition-all duration-200 cursor-pointer border-0 hover:-translate-y-0.5">
          {{ $cv('contact_form_submit', 'Send Message') }} &#8594;
        </button>
      </div>
    </form>

    <div id="formError" class="font-semibold text-sm mt-4 p-4 rounded-xl text-center" style="display:none; color:#8a1f11; background:rgba(180,44,22,0.10); border:1px solid rgba(180,44,22,0.25);">
      Something went wrong. Please try again.
    </div>

    <div id="formSuccess" class="font-semibold text-sm mt-4 p-4 rounded-xl text-center" style="color:#1a2e04; background:rgba(26,46,4,0.10); border:1px solid rgba(26,46,4,0.22);">
      {{ $cv('contact_form_success', "Thank you! We'll be in touch soon.") }}
    </div>

  </div>
</section>


{{-- ══════════════ FOOTER ══════════════ --}}
<footer class="ultrafood-footer">
  <div class="ultrafood-footer-inner">
    <div class="ultrafood-footer-contact">
      <div class="ultrafood-footer-contact-row">
        <div class="ultrafood-footer-brand">
          <img src="{{ asset('images/logo/65480775-6edc-4e3e-b8c4-f989fe69c17c.png') }}" alt="Ultrafood logo" class="ultrafood-footer-logo" />
        </div>
        <div class="ultrafood-footer-contact-copy">
          <h2 class="ultrafood-footer-contact-heading" data-ve-field="ultrafood_footer_contact_us_title">{{ $cv('ultrafood_footer_contact_us_title', 'Contact Us') }}</h2>
          <p><strong data-ve-field="ultrafood_footer_contact_label">{{ $cv('ultrafood_footer_contact_label', 'Contact Number:') }}</strong> <span data-ve-field="ultrafood_footer_contact_number">{{ $cv('ultrafood_footer_contact_number', '+1 234 567 890') }}</span></p>
          <p><strong data-ve-field="ultrafood_footer_customer_service_label">{{ $cv('ultrafood_footer_customer_service_label', 'Customer Service:') }}</strong> <span data-ve-field="ultrafood_footer_customer_service_number">{{ $cv('ultrafood_footer_customer_service_number', '+1 234 000 111') }}</span></p>
          <p><strong data-ve-field="ultrafood_footer_email_label">{{ $cv('ultrafood_footer_email_label', 'Email:') }}</strong> <a data-ve-field="ultrafood_footer_email" href="mailto:{{ $cv('ultrafood_footer_email', 'ultrafood05@gmail.com') }}">{{ $cv('ultrafood_footer_email', 'ultrafood05@gmail.com') }}</a></p>
          <p><strong data-ve-field="ultrafood_footer_customer_email_label">{{ $cv('ultrafood_footer_customer_email_label', 'Customer Service Email:') }}</strong> <a data-ve-field="ultrafood_footer_customer_email" href="mailto:{{ $cv('ultrafood_footer_customer_email', 'customerservice@menufood.com') }}">{{ $cv('ultrafood_footer_customer_email', 'customerservice@menufood.com') }}</a></p>
        </div>
        <div class="ultrafood-footer-copy-wrap">
          <p class="ultrafood-footer-copy">{{ $cv('footer_copyright_text', '© 2026 Ultrafood Distributors Inc. All rights reserved.') }}</p>
        </div>
      </div>
    </div>
  </div>
</footer>


{{-- Back to top --}}
<button id="backToTop" aria-label="Back to top"
  class="fixed bottom-6 right-6 z-50 w-10 h-10 rounded-full shadow-lg flex items-center justify-center transition-all cursor-pointer border-0 text-sm font-bold"
  style="
    --btt-bg: {{ $cv('back_to_top_bg', '#4B7A08') }};
    --btt-fg: {{ $cv('back_to_top_fg', '#ffffff') }};
    --btt-hover-bg: {{ $cv('back_to_top_hover_bg', '#1a2e04') }};
  ">
  &#8593;
</button>

{{-- Last in document so CMS text styles beat footer.css / Tailwind; includes every saved *_color/_font/_font_size --}}
@php
  $cmsTextStyles = [];
  foreach ($allContents as $row) {
    $k = $row['key'] ?? '';
    if (! is_string($k) || ! preg_match('/^(.+)_(color|font|font_size)$/', $k, $m)) {
      continue;
    }
    $base = $m[1];
    $suffix = $m[2];
    if (! isset($cmsTextStyles[$base])) {
      $cmsTextStyles[$base] = ['color' => '', 'font' => '', 'font_size' => ''];
    }
    $cmsTextStyles[$base][$suffix] = (string) ($row['value'] ?? '');
  }
@endphp
@if(! empty($cmsTextStyles))
<style id="ultrafood-cms-text-styles">
@foreach($cmsTextStyles as $base => $st)
@php
  $parts = [];
  if (($st['color'] ?? '') !== '') {
    $parts[] = 'color:' . e($st['color']) . ' !important';
  }
  if (($st['font'] ?? '') !== '') {
    $parts[] = "font-family:'" . e(str_replace("'", "\\'", $st['font'])) . "',sans-serif";
  }
  if (($st['font_size'] ?? '') !== '') {
    $parts[] = 'font-size:' . e($st['font_size']);
  }
@endphp
@if(count($parts))
@php $css = implode(';', $parts); @endphp
  [data-ve-field="{{ e($base) }}"] { {{ $css }} }
  .ultrafood-footer-contact a[data-ve-field="{{ e($base) }}"] { {{ $css }} }
@endif
@endforeach
</style>
@endif

<script src="{{ asset('js/ultrafood.js') }}"></script>
<script>
  (function () {
    var cards = document.querySelectorAll('#brands .brand-card');
    if (!cards.length) return;

    cards.forEach(function (card) {
      var clearTap = function () { card.classList.remove('is-tapped'); };
      card.addEventListener('touchstart', function () { card.classList.add('is-tapped'); }, { passive: true });
      card.addEventListener('touchend', function () { setTimeout(clearTap, 180); }, { passive: true });
      card.addEventListener('touchcancel', clearTap, { passive: true });
    });
  })();
</script>
</body>
</html>
