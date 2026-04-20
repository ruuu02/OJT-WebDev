<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Menu - {{ $product['title'] ?? 'Product Details' }}</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo/e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Glory:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=ZCOOL+XiaoWei&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/menu/menu.css') }}" />
</head>
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
</style>
<body class="menu-page menu-product-detail-screen">
  @include('frontend.menu-bg-image')
  @php
    use Illuminate\Support\Facades\DB;
    use App\Models\Page;

    // Fallback when the controller is not providing a product payload (e.g. visual editor preview)
    if (!isset($product)) {
      $productSlug = 'jelly-mixes';
      $productImages = config("menu-images.products.{$productSlug}") ?? [];
      if (!is_array($productImages) || empty($productImages)) {
        $productImages = [
          'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=900&q=80&auto=format&fit=crop',
          'https://images.unsplash.com/photo-1517673132405-a56a62b18caf?w=900&q=80&auto=format&fit=crop',
          'https://images.unsplash.com/photo-1515543904379-3d757afe72e3?w=900&q=80&auto=format&fit=crop',
        ];
      }
      $product = [
        'slug'        => $productSlug,
        'title'       => 'Jelly Mixes',
        'category'    => 'Products',
        'description' => 'Bright, fun dessert essentials designed for easy preparation, repeat purchase, and everyday menu variety.',
        'images'      => array_values($productImages),
      ];
    } else {
      // Ensure we always have a slug available for CMS key building
      $product['slug'] = $product['slug'] ?? request()->route('product');
    }

    // ── CMS helpers for per-product copy and links ──
    $pageId = Page::where('slug', 'menu')->value('id');
    $allContents = DB::table('text_content')
        ->when($pageId, fn ($query) => $query->where('page_id', $pageId))
        ->get()
        ->map(fn($r) => ['key' => $r->key, 'value' => $r->value, 'section' => 'text_content']);

    $cv = fn(string $key, string $default = '') =>
      ($allContents->firstWhere('key', $key)['value'] ?? null) ?? $default;

    $productSlug = $product['slug'] ?? 'jelly-mixes';
    $productImages = array_values(array_filter($product['images'] ?? []));
    $frontImageUrl = $productImages[0] ?? null;
    $backImageUrl = $productImages[1] ?? null;
    $hasFlipImages = !empty($frontImageUrl) && !empty($backImageUrl);
  @endphp

  @include('frontend.menu-header')

  <div class="page-content">
    <main class="menu-product-detail-page">
      <section class="menu-product-detail-shell">
        <p class="menu-product-detail-breadcrumbs">
          <a href="{{ route('menu') }}">Home</a>
          <span aria-hidden="true">&gt;</span>
          <span class="is-current">{{ $product['title'] }}</span>
        </p>

        <div class="menu-product-detail-layout">
          <section
            class="menu-product-gallery"
            aria-label="{{ $product['title'] }} gallery"
          >
            <div class="menu-product-gallery-stage">
              <figure class="menu-product-gallery-main" aria-label="{{ $product['title'] }} main image">
                @if ($frontImageUrl)
                  <button
                    type="button"
                    class="menu-product-flip-trigger{{ $hasFlipImages ? ' is-flippable' : '' }}"
                    id="menuProductZoomTrigger"
                    aria-label="{{ $hasFlipImages ? 'Flip ' : '' }}{{ $product['title'] }} image"
                  >
                    <span class="menu-product-flip-card" id="menuProductFlipCard" @if($hasFlipImages) aria-pressed="false" @endif>
                      <span class="menu-product-flip-card__inner">
                        <img src="{{ $frontImageUrl }}" alt="{{ $product['title'] }} front image" class="menu-product-flip-face is-front" loading="lazy" />
                        @if ($hasFlipImages)
                          <img src="{{ $backImageUrl }}" alt="{{ $product['title'] }} back image" class="menu-product-flip-face is-back" loading="lazy" />
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

          <aside class="menu-product-info">
            <div class="menu-product-info__panel">
              <div class="menu-product-info__row menu-product-info__row--title">
                <h1 class="menu-product-info__title">{{ $product['title'] }}</h1>
              </div>

              @php
                $descKey = 'menu_product_' . $productSlug . '_description';
                $desc = trim((string) $cv($descKey, ''));
              @endphp
              @if ($desc !== '')
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

            <div class="menu-product-buy-strip" aria-label="Buy {{ $product['title'] }}">
              <a href="{{ $cv('menu_product_' . $productSlug . '_lazada_url', '#') }}" class="menu-product-buy-link" aria-label="Buy on Lazada" target="_blank" rel="noopener noreferrer">
                <img src="{{ asset('images/icons/ecommerce/lazada-logo.svg') }}" alt="Lazada logo" loading="lazy" />
                <span>Lazada</span>
              </a>
              <a href="{{ $cv('menu_product_' . $productSlug . '_shopee_url', '#') }}" class="menu-product-buy-link" aria-label="Buy on Shopee" target="_blank" rel="noopener noreferrer">
                <img src="{{ asset('images/icons/ecommerce/shopee-logo.svg') }}" alt="Shopee logo" loading="lazy" />
                <span>Shopee</span>
              </a>
              <a href="{{ $cv('menu_product_' . $productSlug . '_tiktok_url', '#') }}" class="menu-product-buy-link" aria-label="Buy on TikTok Shop" target="_blank" rel="noopener noreferrer">
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
