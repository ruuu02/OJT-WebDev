<!DOCTYPE html>
<html lang="en">
<head>
  {{-- Page metadata and stylesheet imports for the Menu page --}}
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Menu</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo/e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Glory:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=ZCOOL+XiaoWei&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/menu/menu.css') }}" />
</head>
<body class="menu-page menu-home-page">
  @include('frontend.menu-bg-image')
  @php
    use Illuminate\Support\Facades\DB;
    use App\Models\Page;

    if (!isset($allContents)) {
      $pageId = Page::where('slug', 'menu')->value('id');
      $allContents = DB::table('text_content')
          ->when($pageId, fn ($query) => $query->where('page_id', $pageId))
          ->get()
          ->map(fn($r) => ['key' => $r->key, 'value' => $r->value, 'section' => 'text_content']);
    }

    $cv = fn(string $key, string $default = '') =>
      ($allContents->firstWhere('key', $key)['value'] ?? null) ?? $default;

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
    $zoneStyles = [];
    $fieldStyles = [];
    foreach (config('cms.menu.zones', []) as $zone) {
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

  /* Match Nordic-like spacing between Connect With Us and Footer on Menu home only. */
  body.menu-home-page .menu-footer { margin-top: 76px !important; }
  @media (max-width: 900px) {
    body.menu-home-page .menu-footer { margin-top: 46px !important; }
  }
  @media (max-width: 640px) {
    body.menu-home-page .menu-footer { margin-top: 40px !important; }
  }
  @media (max-width: 480px) {
    body.menu-home-page .menu-footer { margin-top: 50px !important; }
  }
  </style>
  @endif

  {{-- Sticky top header shared across Menu pages --}}
  @include('frontend.menu-header')

  @php
    $menuCategoriesHeading = 'images/menu/headings/menu-categories.png';
    $menuTodayHeading = "images/logo/What's-Our-Menu-for-Today-Text.png";
    $hasMenuCategoriesHeading = file_exists(public_path($menuCategoriesHeading));
    $hasMenuTodayHeading = file_exists(public_path($menuTodayHeading));
    $menuImages = config('menu-images');
    $homeCategories = $menuImages['home']['category_cards'] ?? [];
    $homeGallery = $menuImages['home']['gallery'] ?? [];

    $menuPageIdForMedia = Page::where('slug', 'menu')->value('id');
    $menuImagesById = DB::table('menu_images')
      ->when($menuPageIdForMedia, fn ($q) => $q->where('page_id', $menuPageIdForMedia))
      ->get()
      ->keyBy('id');

    $resolveMenuImgSrc = function (string $mediaKey, string $fallback) use ($cv, $menuImagesById) {
      $id = trim((string) $cv($mediaKey, ''));
      if ($id === '') return $fallback;
      $row = $menuImagesById->get((int) $id);
      if (!$row || empty($row->filename)) return $fallback;
      $filename = ltrim((string) $row->filename, '/');
      if (!file_exists(public_path('images/menu/' . $filename))) return $fallback;
      return asset('images/menu/' . $filename);
    };

    $menuCategoriesHeadingSrc = $resolveMenuImgSrc(
      'media_id_menu_categories_heading',
      asset($menuCategoriesHeading)
    );

    $catKeyMap = [
      'Appetizers' => 'appetizers',
      'Soups' => 'soups',
      'Main Dishes' => 'main_dishes',
      'Noodles and Pastas' => 'noodles_pastas',
      'Drinks' => 'drinks',
      'Desserts' => 'desserts',
    ];

    $galleryKeys = [
      'media_id_menu_gallery_slide_1',
      'media_id_menu_gallery_slide_2',
      'media_id_menu_gallery_slide_3',
      'media_id_menu_gallery_slide_4',
    ];

    $galleryResolved = [];
    foreach ($galleryKeys as $i => $k) {
      $fallback = $homeGallery[$i] ?? '';
      $fallback = is_string($fallback) && $fallback !== '' ? $fallback : '';
      $galleryResolved[$i] = $resolveMenuImgSrc($k, $fallback);
    }

    $menuVideoBannerRaw = trim((string) $cv('menu_home_video_banner_url', ''));
    $extractYouTubeId = static function (string $value): string {
      $value = trim($value);
      if ($value === '') {
        return '';
      }

      if (preg_match('~(?:youtube\.com/(?:shorts/|embed/)|youtu\.be/)([a-zA-Z0-9_-]{6,})~i', $value, $matches)) {
        return $matches[1];
      }

      if (preg_match('~[?&]v=([a-zA-Z0-9_-]{6,})~i', $value, $matches)) {
        return $matches[1];
      }

      return '';
    };

    $menuVideoBannerType = 'file';
    if ($menuVideoBannerRaw === '') {
      if (file_exists(public_path('videos/menu/menu-banner.mp4'))) {
        $menuVideoBannerSrc = asset('videos/menu/menu-banner.mp4');
      } else {
        $menuVideoBannerType = 'image';
        $menuVideoBannerSrc = '';
      }
    } else {
      $youtubeId = $extractYouTubeId($menuVideoBannerRaw);
      if ($youtubeId !== '') {
        $menuVideoBannerType = 'youtube';
        $menuVideoBannerSrc = 'https://www.youtube.com/embed/' . $youtubeId . '?rel=0&modestbranding=1&playsinline=1';
      } elseif (preg_match('/^https?:\/\//i', $menuVideoBannerRaw) || str_starts_with($menuVideoBannerRaw, '//')) {
        $menuVideoBannerSrc = $menuVideoBannerRaw;
      } else {
        $menuVideoBannerSrc = asset(ltrim($menuVideoBannerRaw, '/'));
      }
    }
    $menuVideoBannerPoster = trim((string) ($galleryResolved[0] ?? ($homeGallery[0] ?? '')));
  @endphp

  {{-- Main menu content area --}}
  <main class="page-content">
    <section class="menu-section" aria-label="Menu Categories">
      {{-- Food gallery carousel above the Menu heading --}}
      <div class="menu-section-shell menu-section-shell--gallery">
        <div class="image-carousel" role="region" aria-roledescription="carousel" aria-label="Food gallery images">
          {{-- Gallery slide images --}}
          <div id="imageTrack" class="image-track">
            @foreach (array_slice($homeGallery, 0, count($galleryKeys)) as $i => $image)
              <figure class="gallery-slide" data-gallery-slide="{{ $i + 1 }}">
                <img src="{{ $galleryResolved[$i] ?? $image }}" alt="Food gallery image" loading="lazy" />
              </figure>
            @endforeach
          </div>
          {{-- Pagination dots are injected by JavaScript --}}
          <div id="imageDots" class="image-dots" aria-label="Gallery positions"></div>
        </div>
      </div>

      <div class="menu-section-shell menu-section-shell--feature">
        {{-- Section title for product/category cards --}}
        <div class="menu-section-head">
          <h2 class="menu-section-title">
            @if ($hasMenuCategoriesHeading)
              <img
                src="{{ $menuCategoriesHeadingSrc }}"
                alt="Menu Categories"
                class="menu-section-title-image"
              />
            @elseif ($hasMenuTodayHeading)
              <img
                src="{{ asset($menuTodayHeading) }}"
                alt="What's Our Menu For Today"
                class="menu-section-title-image menu-section-title-image--menu-today"
              />
            @endif
          </h2>
        </div>

        <div id="categorySwiper" class="category-swiper" aria-label="Category cards">
          {{-- Scrollable row that holds all product cards --}}
          <div id="categorySwiperTrack" class="category-circle-wrap">
            {{-- Reusable card list partial --}}
            @foreach ($homeCategories as $category)
              @php
              $categorySlug = \Illuminate\Support\Str::slug((string) ($category['name'] ?? ''));
                $catName = (string) ($category['name'] ?? '');
                $slugKey = $catKeyMap[$catName] ?? null;
                $labelKey = $slugKey ? ('menu_home_cat_' . $slugKey . '_label') : '';
                $mediaKey = $slugKey ? ('media_id_menu_home_cat_' . $slugKey . '_img') : '';
                $displayLabel = $labelKey ? $cv($labelKey, $catName) : $catName;
                $fallbackImg = asset(ltrim((string) ($category['image'] ?? ''), '/'));
                $imgSrc = $mediaKey ? $resolveMenuImgSrc($mediaKey, $fallbackImg) : $fallbackImg;
              @endphp
              <article
                class="category-circle-card"
                data-category-name="{{ $catName }}"
                data-category-image="{{ $imgSrc }}"
              data-category-link="{{ route('menu.recipes.category', ['category' => $categorySlug]) }}"
                tabindex="0"
                role="button"
                aria-label="Open {{ $displayLabel }} details"
              >
                <figure class="category-circle-image">
                  <img src="{{ $imgSrc }}" alt="{{ $displayLabel }} image" loading="lazy" />
                </figure>
                <h3 class="category-circle-name" @if($labelKey) data-ve-field="{{ $labelKey }}" @endif>{{ $displayLabel }}</h3>
              </article>
            @endforeach
          </div>
        </div>

      </div>

      {{-- Lower highlight section: video on one side, description on the other --}}
      <div class="menu-section-shell menu-section-shell--story">
        <div class="menu-home-story" aria-label="Menu highlight section">
          <div class="menu-video-banner menu-home-story__media" aria-label="Menu highlight video">
            @if ($menuVideoBannerType === 'youtube')
              <iframe
                class="menu-video-banner-media"
                src="{{ $menuVideoBannerSrc }}"
                title="Menu highlight video"
                loading="lazy"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
                referrerpolicy="strict-origin-when-cross-origin"
              ></iframe>
            @elseif ($menuVideoBannerType === 'image')
              <img
                class="menu-video-banner-media"
                src="{{ $menuVideoBannerPoster }}"
                alt="Menu highlight"
                loading="lazy"
              />
            @else
              <video
                class="menu-video-banner-media"
                src="{{ $menuVideoBannerSrc }}"
                @if($menuVideoBannerPoster !== '') poster="{{ $menuVideoBannerPoster }}" @endif
                autoplay
                muted
                loop
                playsinline
                preload="metadata"
              >
                Your browser does not support the video tag.
              </video>
            @endif
          </div>

          <div class="menu-home-story__copy">
            <h3 class="menu-home-story__title" data-ve-field="menu_home_story_title">
              {{ $cv('menu_home_story_title', 'Menutong sarap for every craving.') }}
            </h3>
            <p class="menu-home-story__desc" data-ve-field="menu_home_story_desc">
              {{ $cv('menu_home_story_desc', 'From appetizers and soups to main dishes, noodles, drinks, and desserts, Menu brings comforting flavor and easy preparation to everyday meals, celebrations, and handaan moments.') }}
            </p>
          </div>
        </div>
      </div>

      {{-- Popup container kept for category overlay behavior --}}
      <div id="categoryPopup" class="category-popup" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Category details">
        {{-- Backdrop click closes popup --}}
        <div class="category-popup-backdrop" data-popup-close="true"></div>
        <div class="category-popup-panel">
          {{-- Popup close button --}}
          <button type="button" class="category-popup-close" id="categoryPopupClose" aria-label="Close popup">×</button>
          {{-- Dynamic popup image --}}
          <figure class="category-popup-image-wrap">
            <img id="categoryPopupImage" src="" alt="" class="category-popup-image" />
          </figure>
          {{-- Dynamic popup title + destination link --}}
          <h3 id="categoryPopupTitle" class="category-popup-title"></h3>
          <a id="categoryPopupLink" href="#" class="category-popup-link" data-ve-field="menu_popup_link_text">{{ $cv('menu_popup_link_text', 'Go to Page') }}</a>
        </div>
      </div>

    </section>
  </main>

  @include('frontend.partials.connect-with-us-menu')

  {{-- Footer shared across Menu pages --}}
  @include('frontend.menu-footer')

  {{-- Menu page interactions and animations --}}
  <button id="menuScrollTopBtn" class="menu-scroll-top" type="button" aria-label="{{ $cv('menu_scroll_top_aria', 'Back to top') }}" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });">
    <span id="menuScrollTopIcon" class="menu-scroll-top-icon" aria-hidden="true">{{ $cv('menu_scroll_top_icon', '↑') }}</span>
  </button>
  @php
    $cmsTextStylesMenu = [];
    foreach ($allContents as $row) {
      $k = $row['key'] ?? '';
      if (! is_string($k) || ! preg_match('/^(.+)_(color|font|font_size)$/', $k, $m)) {
        continue;
      }
      $base = $m[1];
      $suffix = $m[2];
      if (! isset($cmsTextStylesMenu[$base])) {
        $cmsTextStylesMenu[$base] = ['color' => '', 'font' => '', 'font_size' => ''];
      }
      $cmsTextStylesMenu[$base][$suffix] = (string) ($row['value'] ?? '');
    }
  @endphp
  @if(! empty($cmsTextStylesMenu))
  <style id="menu-cms-text-styles">
  @foreach($cmsTextStylesMenu as $base => $st)
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
  @php $cssM = implode(';', $parts); @endphp
    [data-ve-field="{{ e($base) }}"] { {{ $cssM }} }
    .menu-footer-contact a[data-ve-field="{{ e($base) }}"] { {{ $cssM }} }
  @endif
  @endforeach
  </style>
  @endif

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
