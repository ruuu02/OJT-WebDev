<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Menu - Products</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo/e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Glory:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=ZCOOL+XiaoWei&display=swap" rel="stylesheet">
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
  </style>
</head>
<body class="menu-page">
  @include('frontend.menu-bg-image')
  @php
    if (!isset($products)) {
      $productImages = config('menu-images.products', []);
      $makeCatalogProduct = function (string $slug, string $title, string $description, array $highlights, array $classifications = []) use ($productImages) {
        $images = array_values($productImages[$slug] ?? []);

        return [
          'title' => $title,
          'description' => $description,
          'highlights' => $highlights,
          'classifications' => $classifications,
          'showcase_slides' => collect($images)->values()->map(function ($image, $index) use ($title) {
            return [
              'type' => $title . ' ' . ($index + 1),
              'image' => $image,
              'alt' => $title . ' showcase image ' . ($index + 1),
            ];
          })->all(),
        ];
      };

      $catalog = [
        'jelly-mixes' => $makeCatalogProduct(
          'jelly-mixes',
          'Jelly Mixes',
          'Bright dessert staples that are easy to prepare and easy to sell.',
          ['Fun dessert option', 'Easy prep', 'Strong repeat-buy appeal'],
          [
            [
              'type' => 'Flavored',
              'items' => ['Mango', 'Coffee', 'Cucumber', 'Four Season', 'Passion Fruit', 'Buko Strips', 'Pandan', 'Ube', 'Leche Flan'],
            ],
            [
              'type' => 'Unflavored',
              'items' => ['Clear', 'Red', 'Green', 'Yellow', 'Black'],
            ],
          ]
        ),
        'breading-mixes' => $makeCatalogProduct(
          'breading-mixes',
          'Breading Mixes',
          'Crunch-focused coatings designed for reliable frying results.',
          ['Crispy finish', 'Kitchen friendly', 'Good for quick-service menus']
        ),
        'powder-mixes' => $makeCatalogProduct(
          'powder-mixes',
          'Powder Mixes',
          'Versatile mixes that support faster prep and dependable flavor.',
          ['Flexible usage', 'Fast prep support', 'Daily kitchen staple']
        ),
        'bouillon-cubes' => $makeCatalogProduct(
          'bouillon-cubes',
          'Bouillon Cubes',
          'Compact flavor boosters for soups, sauces, and savory dishes.',
          ['Easy seasoning', 'Shelf-friendly', 'Trade-ready format']
        ),
        'noodles-and-pastas' => $makeCatalogProduct(
          'noodles-and-pastas',
          'Noodles and Pastas',
          'Comfort-food staples suited for both home and business kitchens.',
          ['Quick cooking', 'Broad recipe fit', 'High everyday demand']
        ),
        'powdered-drinks' => $makeCatalogProduct(
          'powdered-drinks',
          'Powdered Drinks',
          'Convenient refreshment mixes with easy storage and portioning.',
          ['Easy to mix', 'Event-friendly', 'Good shelf life']
        ),
        'professional-series' => $makeCatalogProduct(
          'professional-series',
          'Professional Series',
          'Business-ready solutions made for higher-volume operations.',
          ['Foodservice ready', 'Volume packs', 'Built for repeat orders']
        ),
      ];

      $products = collect($catalog)->map(function ($product, $slug) {
        return $product + ['slug' => $slug];
      })->values()->all();
    }
  @endphp

  @include('frontend.menu-header')

  <main class="menu-product-list-wrap">
    <p class="menu-product-list-breadcrumbs">
      <a href="{{ route('menu') }}">Home</a>
      <span aria-hidden="true">&gt;</span>
      <span class="is-current">Products</span>
    </p>

    <section class="menu-product-list-grid" aria-label="Product cards">
      @foreach ($products as $product)
        @php
          $preview = $product['showcase_slides'][0] ?? null;
          $image = $preview['image'] ?? null;
          $alt = $preview['alt'] ?? ($product['title'] . ' image');
        @endphp
        <a
          href="{{ route('menu.products.category', ['category' => $product['slug']]) }}"
          class="menu-product-list-card"
          aria-label="Open {{ $product['title'] }}"
        >
          <figure class="menu-product-list-thumb">
            @if ($image)
              <img src="{{ $image }}" alt="{{ $alt }}" loading="lazy" />
            @endif
          </figure>
          <p class="menu-product-list-name">{{ $product['title'] }}</p>
        </a>
      @endforeach
    </section>
  </main>

  @include('frontend.menu-footer')

  <button id="menuScrollTopBtn" class="menu-scroll-top" type="button" aria-label="Back to top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });">
    <span class="menu-scroll-top-icon" aria-hidden="true">↑</span>
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
</body>
</html>
