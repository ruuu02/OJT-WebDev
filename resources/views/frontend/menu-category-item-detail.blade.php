<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Menu - {{ $item->title ?? 'Product Details' }}</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo/e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Glory:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=ZCOOL+XiaoWei&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/menu/menu.css') }}" />
  <style>
    /* Page-only override: remove the white rounded shell card on item detail */
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

    .menu-product-detail-shell {
      background: transparent;
      box-shadow: none;
      border-radius: 0;
      --menu-item-info-offset: 0px;
    }

    /* Page-only override: make product image look like a cut-out */
    .menu-product-gallery-main {
      border: 0;
      background: transparent;
      box-shadow: none;
      border-radius: 0;
      overflow: visible;
      aspect-ratio: auto;
      perspective: 1400px;
      cursor: pointer;
      display: flex;
      justify-content: center;
      width: min(100%, 320px);
      margin: 0 auto;
    }

    .menu-product-gallery-main img:not(.menu-product-flip-face) {
      object-fit: contain;
      filter: drop-shadow(0 16px 24px rgba(15, 23, 42, 0.22));
      transform: none;
      background: transparent;
    }

    .menu-product-detail-screen .menu-product-gallery-main:hover,
    .menu-product-detail-screen .menu-product-gallery-main:focus-visible {
      transform: none !important;
      box-shadow: none !important;
    }

    .menu-product-detail-screen .menu-product-gallery-main:hover img:not(.menu-product-flip-face),
    .menu-product-detail-screen .menu-product-gallery-main:focus-visible img:not(.menu-product-flip-face) {
      transform: none !important;
      filter: drop-shadow(0 16px 24px rgba(15, 23, 42, 0.22)) !important;
    }

    .menu-product-flip-card {
      --menu-detail-image-height: 360px;
      position: relative;
      display: flex;
      justify-content: center;
      width: min(100%, 320px);
      margin: 0 auto;
      perspective: 1200px;
      -webkit-perspective: 1200px;
    }

    .menu-product-flip-card__inner {
      position: relative;
      width: 100%;
      height: var(--menu-detail-image-height);
      margin: 0 auto;
      transform-style: preserve-3d;
      -webkit-transform-style: preserve-3d;
      transition: transform 700ms cubic-bezier(0.22, 0.7, 0.18, 1);
      will-change: transform;
      transform-origin: center center;
    }

    .menu-product-flip-face {
      position: absolute;
      inset: 0;
      width: 100% !important;
      height: 100% !important;
      object-fit: contain !important;
      background: transparent;
      border-radius: 10px;
      display: block;
      backface-visibility: hidden;
      -webkit-backface-visibility: hidden;
      transform: rotateY(0deg) translateZ(1px);
      -webkit-transform: rotateY(0deg) translateZ(1px);
      box-shadow: none;
      opacity: 1;
    }

    .menu-product-flip-face.is-back {
      transform: rotateY(180deg) translateZ(1px);
      -webkit-transform: rotateY(180deg) translateZ(1px);
    }

    .menu-product-flip-card.is-flipped .menu-product-flip-card__inner {
      transform: rotateY(180deg);
      -webkit-transform: rotateY(180deg);
    }

    .menu-product-flip-hint {
      margin: 8px 0 0;
      text-align: center;
      font-size: 12px;
      color: #820c0c;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    /* Center image block so it doesn't sit to the left */
    .menu-product-gallery {
      justify-content: center;
      padding-top: 0;
    }

    .menu-product-info {
      margin-top: calc(-1 * var(--menu-item-info-offset));
    }

    .menu-product-info__title {
      white-space: normal;
      overflow-wrap: anywhere;
    }

    @media (min-width: 901px) {
      .menu-product-info__title {
        font-size: clamp(34px, 3vw, 48px);
        line-height: 1.1;
      }

      .menu-product-info__desc {
        font-size: 17px;
        line-height: 1.6;
      }

      .menu-product-buy-strip {
        padding: 14px 18px 16px;
        border-radius: 28px;
      }

      .menu-product-buy-link {
        min-height: 56px;
        font-size: 13px;
        border-radius: 16px;
      }

      .menu-product-buy-link img {
        width: 22px;
        height: 22px;
      }
    }

    .menu-product-gallery-stage {
      width: 100%;
      display: grid;
      grid-template-columns: minmax(0, 320px);
      justify-content: center;
      justify-items: center;
      margin: 0 auto;
      gap: 0;
      transform: none;
    }

    .menu-product-flip-trigger {
      cursor: default;
      border: 0;
      padding: 0;
      background: transparent;
      display: block;
      width: min(100%, 320px);
      margin: 0 auto;
    }

    .menu-product-flip-trigger.is-flippable,
    .menu-product-flip-trigger.is-flippable:hover {
      cursor: pointer;
    }

    @media (max-width: 900px) {
      .menu-product-flip-card {
        --menu-detail-image-height: 270px;
      }

      /* menu-products.css uses display:contents for the info panel on small screens,
         so add spacing on the first info row instead. */
      .menu-product-info__row--title {
        margin-top: calc(-1 * var(--menu-item-info-offset));
      }
    }

    @media (max-width: 640px) {
      .menu-product-flip-card {
        --menu-detail-image-height: 240px;
      }
    }

    .menu-product-flip-trigger:focus-visible {
      outline: 2px solid #f37321;
      outline-offset: 6px;
    }

  </style>
</head>
<body class="menu-page menu-product-detail-screen">
  @include('frontend.menu-bg-image')
  @php
    use Illuminate\Support\Facades\DB;
    use App\Models\Page;

    $pageId = Page::where('slug', 'menu')->value('id');
    $allContents = DB::table('text_content')
        ->when($pageId, fn ($query) => $query->where('page_id', $pageId))
        ->get()
        ->map(fn($r) => ['key' => $r->key, 'value' => $r->value, 'section' => 'text_content']);

    $cv = fn(string $key, string $default = '') =>
      ($allContents->firstWhere('key', $key)['value'] ?? null) ?? $default;

    $id = (string) ($item->id ?? '');
    $nameKey = 'mcli_'.$id.'_name';
    $displayTitle = $cv($nameKey, $item->title ?? 'Product Details');
    $nameStyle = collect([
      $cv($nameKey.'_font') ? "font-family:'".$cv($nameKey.'_font')."',sans-serif" : null,
      $cv($nameKey.'_font_size') ? 'font-size:'.$cv($nameKey.'_font_size') : null,
      $cv($nameKey.'_color') ? 'color:'.$cv($nameKey.'_color') : null,
    ])->filter()->implode(';');
    $imageUrl = method_exists($item, 'imageUrl') ? $item->imageUrl() : null;
    $frontImageUrl = $imageUrl;
    $backImageUrl = method_exists($item, 'backImageUrl') ? $item->backImageUrl() : null;
    $hasFlipImages = !empty($frontImageUrl) && !empty($backImageUrl);
    $categorySlug = $categorySlug ?? '';
  @endphp

  @include('frontend.menu-header')

  <div class="page-content">
    <main class="menu-product-detail-page">
      <section class="menu-product-detail-shell">
        <p class="menu-product-detail-breadcrumbs">
          <a href="{{ route('menu') }}">Home</a>
          <span aria-hidden="true">&gt;</span>
          <a href="{{ route('menu.products.category', ['category' => $categorySlug]) }}">{{ $categoryTitle ?? ucfirst(str_replace('-', ' ', $categorySlug)) }}</a>
          <span aria-hidden="true">&gt;</span>
          <span class="is-current">{{ $displayTitle }}</span>
        </p>

        <div class="menu-product-detail-layout">
          <section class="menu-product-gallery" aria-label="{{ $displayTitle }} image">
            <div class="menu-product-gallery-stage">
              <figure class="menu-product-gallery-main" aria-label="{{ $displayTitle }} main image">
                @if ($imageUrl)
                  <button
                    type="button"
                    class="menu-product-flip-trigger{{ $hasFlipImages ? ' is-flippable' : '' }}"
                    id="menuProductZoomTrigger"
                    aria-label="{{ $hasFlipImages ? 'Flip ' : '' }}{{ $displayTitle }} image"
                  >
                    <span class="menu-product-flip-card" id="menuProductFlipCard" @if($hasFlipImages) aria-pressed="false" @endif>
                      <span class="menu-product-flip-card__inner">
                        <img src="{{ $frontImageUrl }}" alt="{{ $displayTitle }} front image" class="menu-product-flip-face is-front" loading="lazy" />
                        @if ($hasFlipImages)
                          <img src="{{ $backImageUrl }}" alt="{{ $displayTitle }} back image" class="menu-product-flip-face is-back" loading="lazy" />
                        @endif
                      </span>
                    </span>
                  </button>
                @endif
              </figure>
              @if ($hasFlipImages)
                <p class="menu-product-flip-hint">Click image to flip and view the back</p>
              @endif
            </div>
          </section>

          <aside class="menu-product-info" data-category-slug="{{ $categorySlug }}">
            @php $isVisualEditor = !empty($isItemDetailsPage ?? false); @endphp
            <div class="menu-product-info__panel">
              <div class="menu-product-info__row menu-product-info__row--title">
                <h1 class="menu-product-info__title" data-ve-field="{{ $nameKey }}" @if($nameStyle !== '') style="{{ $nameStyle }}" @endif>{{ $displayTitle }}</h1>
              </div>

              @php
                $descDefault = $isVisualEditor ? 'Click to add item description' : '';
                $descKey = 'menu_item_' . $id . '_description';
                $desc = trim((string) $cv($descKey, $descDefault));
              @endphp
              @if ($isVisualEditor || $desc !== '')
                <div class="menu-product-info__row menu-product-info__row--desc">
                  <p
                    class="menu-product-info__desc"
                    data-ve-field="{{ $descKey }}"
                  >
                    <span class="menu-product-info__desc-text">{{ $desc }}</span><button
                      type="button"
                      class="menu-product-info__desc-toggle"
                      aria-expanded="false"
                      hidden
                    >
                      See more
                    </button>
                  </p>
                </div>
              @endif
            </div>

            @php
              $shopeeMallUrl = trim((string) $cv('menu_item_' . $id . '_shopee_mall_url', ''));
            @endphp
            <div class="menu-product-buy-strip" aria-label="Buy {{ $displayTitle }}">
              <a
                href="{{ $cv('menu_item_' . $id . '_lazada_url', '#') }}"
                class="menu-product-buy-link"
                aria-label="Buy on Lazada"
                target="_blank"
                rel="noopener noreferrer"
                data-ve-field="menu_item_{{ $id }}_lazada_url"
              >
                <img src="{{ asset('images/icons/ecommerce/lazada-logo.svg') }}" alt="Lazada logo" loading="lazy" />
                <span>Lazada</span>
              </a>
              <a
                href="{{ $cv('menu_item_' . $id . '_shopee_url', '#') }}"
                class="menu-product-buy-link"
                aria-label="Buy on Shopee"
                target="_blank"
                rel="noopener noreferrer"
                data-ve-field="menu_item_{{ $id }}_shopee_url"
              >
                <img src="{{ asset('images/icons/ecommerce/shopee-logo.svg') }}" alt="Shopee logo" loading="lazy" />
                <span>Shopee</span>
              </a>
              @if ($shopeeMallUrl !== '' && $shopeeMallUrl !== '#')
                <a
                  href="{{ $shopeeMallUrl }}"
                  class="menu-product-buy-link"
                  aria-label="Buy on Shopee Mall"
                  target="_blank"
                  rel="noopener noreferrer"
                  data-ve-field="menu_item_{{ $id }}_shopee_mall_url"
                >
                  <img src="{{ asset('images/icons/ecommerce/shopee-mall-logo.svg') }}" alt="Shopee Mall logo" loading="lazy" />
                  <span>Shopee Mall</span>
                </a>
              @elseif ($isVisualEditor)
                <a
                  href="#"
                  class="menu-product-buy-link"
                  aria-label="Buy on Shopee Mall"
                  target="_blank"
                  rel="noopener noreferrer"
                  data-ve-field="menu_item_{{ $id }}_shopee_mall_url"
                >
                  <img src="{{ asset('images/icons/ecommerce/shopee-mall-logo.svg') }}" alt="Shopee Mall logo" loading="lazy" />
                  <span>Shopee Mall</span>
                </a>
              @endif
              <a
                href="{{ $cv('menu_item_' . $id . '_tiktok_url', '#') }}"
                class="menu-product-buy-link"
                aria-label="Buy on TikTok Shop"
                target="_blank"
                rel="noopener noreferrer"
                data-ve-field="menu_item_{{ $id }}_tiktok_url"
              >
                <img src="{{ asset('images/icons/ecommerce/tiktok-shop-logo.svg') }}" alt="TikTok Shop logo" loading="lazy" />
                <span>TikTok Shop</span>
              </a>
            </div>
          </aside>
        </div>
      </section>
    </main>
  </div>

  @include('frontend.menu-footer')
  <button id="menuScrollTopBtn" class="menu-scroll-top" type="button" aria-label="Back to top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });">
    <span class="menu-scroll-top-icon" aria-hidden="true">&uarr;</span>
  </button>
  <script>
    (function () {
      const trigger = document.getElementById('menuProductZoomTrigger');
      const flipCard = document.getElementById('menuProductFlipCard');
      const hasFlipImages = {{ $hasFlipImages ? 'true' : 'false' }};

      if (!trigger || !flipCard) return;

      const setFlipState = (isFlipped) => {
        if (!flipCard || !hasFlipImages) return;
        flipCard.classList.toggle('is-flipped', isFlipped);
        flipCard.setAttribute('aria-pressed', isFlipped ? 'true' : 'false');
      };

      trigger.addEventListener('click', () => {
        if (!hasFlipImages) return;
        setFlipState(!flipCard.classList.contains('is-flipped'));
      });

      trigger.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          if (!hasFlipImages) return;
          setFlipState(!flipCard.classList.contains('is-flipped'));
        }
      });
    })();
  </script>
</body>
</html>
