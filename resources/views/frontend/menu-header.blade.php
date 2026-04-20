<header class="site-header">
  @php
    use Illuminate\Support\Facades\DB;
    use App\Models\Page;

    if (!isset($cv)) {
      if (!isset($allContents)) {
        $pageId = Page::where('slug', 'menu')->value('id');
        $allContents = DB::table('text_content')
            ->when($pageId, fn ($query) => $query->where('page_id', $pageId))
            ->get()
            ->map(fn($r) => ['key' => $r->key, 'value' => $r->value, 'section' => 'text_content']);
      }
      $cv = fn(string $key, string $default = '') =>
        ($allContents->firstWhere('key', $key)['value'] ?? null) ?? $default;
    }

    $logoFallback = asset('images/logo/e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png');
    $wordmarkFallback = asset('images/logo/Menu-menutong-Sarap.png');

    $menuPageIdForMedia = Page::where('slug', 'menu')->value('id');

    // Visual Editor provides $allMedia; public routes do not. Default to an empty array.
    $allMedia = $allMedia ?? [];

    $resolveLogoById = function ($id, $fallback) use ($allMedia, $menuPageIdForMedia) {
      $id = is_scalar($id) ? trim((string) $id) : '';
      if ($id === '') return $fallback;

      if (isset($allMedia) && $allMedia) {
        $row = collect($allMedia)->first(fn ($r) =>
          ($r['section'] ?? null) === 'logo' && (string) ($r['id'] ?? '') === $id
        );
        if ($row && !empty($row['filename'])) {
          return asset('images/logo/' . $row['filename']);
        }
      }

      $filename = DB::table('logos')
        ->when($menuPageIdForMedia, fn ($q) => $q->where('page_id', $menuPageIdForMedia))
        ->where('id', $id)
        ->value('filename');
      return $filename ? asset('images/logo/' . $filename) : $fallback;
    };

    $menuHeaderLogoSrc = $resolveLogoById($cv('media_id_menu_header_logo', ''), $logoFallback);
    $menuHeaderWordmarkSrc = $resolveLogoById($cv('media_id_menu_header_wordmark', ''), $wordmarkFallback);
    $fieldStyle = function (string $baseKey) use ($cv): string {
      return collect([
        $cv($baseKey.'_font') ? "font-family:'".$cv($baseKey.'_font')."',sans-serif" : null,
        $cv($baseKey.'_font_size') ? 'font-size:'.$cv($baseKey.'_font_size') : null,
        $cv($baseKey.'_color') ? 'color:'.$cv($baseKey.'_color') : null,
      ])->filter()->implode(';');
    };
    $productIconMap = [
      'jelly-mixes' => 'images/icons/Jelly-Mixes-Icon.svg',
      'breading-mixes' => 'images/icons/Breading-Mixes-Icon.svg',
      'powder-mixes' => 'images/icons/Powder-Mixes-Icon.svg',
      'bouillon-cubes' => 'images/icons/Bouillon-Cubes-Icon.svg',
      'noodles-and-pastas' => 'images/icons/Noodles-and-Pastas-Icon.svg',
      'powdered-drinks' => 'images/icons/Powdered-Drinks-Icon.svg',
      'professional-series' => 'images/icons/Professional-Series-Icon.svg',
    ];
    $resolveProductIcon = function (?string $href, ?string $label = null) use ($productIconMap) {
      $path = is_string($href) ? (parse_url(trim($href), PHP_URL_PATH) ?: '') : '';
      $prefix = '/brands/menu/products/category/';
      $slug = '';

      if (is_string($path) && $path !== '' && str_starts_with($path, $prefix)) {
        $slug = trim(substr($path, strlen($prefix)), '/');
      }

      if ($slug === '' && is_string($label) && trim($label) !== '') {
        $slug = \Illuminate\Support\Str::slug($label);
      }

      return $productIconMap[$slug] ?? null;
    };
    $resolveIconSrc = function (?string $value) {
      $value = is_string($value) ? trim($value) : '';
      if ($value === '') return '';
      if (preg_match('#^https?://#i', $value)) return $value;
      if (str_starts_with($value, '/')) return $value;
      return asset($value);
    };
    $normalizeMenuProductHref = function (string $href): string {
      return str_replace(
        '/brands/menu/products/category/noodles-pastas-and-sauces',
        '/brands/menu/products/category/noodles-and-pastas',
        $href
      );
    };

    $defaultProductLinks = [
      ['label_key' => 'menu_nav_products_jelly_mixes', 'default' => 'Jelly Mixes', 'href_key' => 'menu_nav_products_jelly_mixes_url', 'href_default' => route('menu.products.category', ['category' => 'jelly-mixes']), 'icon_key' => 'menu_nav_products_jelly_mixes_icon', 'icon' => 'images/icons/Jelly-Mixes-Icon.svg'],
      ['label_key' => 'menu_nav_products_breading_mixes', 'default' => 'Breading Mixes', 'href_key' => 'menu_nav_products_breading_mixes_url', 'href_default' => route('menu.products.category', ['category' => 'breading-mixes']), 'icon_key' => 'menu_nav_products_breading_mixes_icon', 'icon' => 'images/icons/Breading-Mixes-Icon.svg'],
      ['label_key' => 'menu_nav_products_powder_mixes', 'default' => 'Powder Mixes', 'href_key' => 'menu_nav_products_powder_mixes_url', 'href_default' => route('menu.products.category', ['category' => 'powder-mixes']), 'icon_key' => 'menu_nav_products_powder_mixes_icon', 'icon' => 'images/icons/Powder-Mixes-Icon.svg'],
      ['label_key' => 'menu_nav_products_bouillon_cubes', 'default' => 'Bouillon Cubes', 'href_key' => 'menu_nav_products_bouillon_cubes_url', 'href_default' => route('menu.products.category', ['category' => 'bouillon-cubes']), 'icon_key' => 'menu_nav_products_bouillon_cubes_icon', 'icon' => 'images/icons/Bouillon-Cubes-Icon.svg'],
      ['label_key' => 'menu_nav_products_noodles_pastas', 'default' => 'Noodles And Pastas', 'href_key' => 'menu_nav_products_noodles_pastas_url', 'href_default' => route('menu.products.category', ['category' => 'noodles-and-pastas']), 'icon_key' => 'menu_nav_products_noodles_pastas_icon', 'icon' => 'images/icons/Noodles-and-Pastas-Icon.svg'],
      ['label_key' => 'menu_nav_products_powdered_drinks', 'default' => 'Powdered Drinks', 'href_key' => 'menu_nav_products_powdered_drinks_url', 'href_default' => route('menu.products.category', ['category' => 'powdered-drinks']), 'icon_key' => 'menu_nav_products_powdered_drinks_icon', 'icon' => 'images/icons/Powdered-Drinks-Icon.svg'],
      ['label_key' => 'menu_nav_products_professional_series', 'default' => 'Professional Series', 'href_key' => 'menu_nav_products_professional_series_url', 'href_default' => route('menu.products.category', ['category' => 'professional-series']), 'icon_key' => 'menu_nav_products_professional_series_icon', 'icon' => 'images/icons/Professional-Series-Icon.svg'],
    ];
    $useDynamicProductLinks = trim((string) $cv('menu_nav_products_use_dynamic', '')) === '1';
    $dynamicProductLinks = collect($allContents ?? [])->map(function ($row) {
      return is_array($row) ? $row : (array) $row;
    })->unique('key')->filter(function ($row) {
      return preg_match('/^menu_nav_products_item_\d+_label$/', (string) ($row['key'] ?? ''));
    })->sortBy(function ($row) {
      preg_match('/^menu_nav_products_item_(\d+)_label$/', (string) ($row['key'] ?? ''), $m);
      return (int) ($m[1] ?? 0);
    })->map(function ($row) use ($cv, $resolveProductIcon) {
      preg_match('/^menu_nav_products_item_(\d+)_label$/', (string) ($row['key'] ?? ''), $m);
      $index = (int) ($m[1] ?? 0);
      $labelKey = 'menu_nav_products_item_'.$index.'_label';
      $hrefKey = 'menu_nav_products_item_'.$index.'_url';
      $label = (string) ($row['value'] ?? '');
      $href = $cv($hrefKey, '#');
      return [
        'label_key' => $labelKey,
        'default' => $label,
        'href_key' => $hrefKey,
        'href_default' => '#',
        'icon_key' => $labelKey.'_icon',
        'icon' => $resolveProductIcon($href, $label),
      ];
    })->filter(fn ($link) => trim((string) ($link['default'] ?? '')) !== '')->values();
    $productLinks = ($useDynamicProductLinks || $dynamicProductLinks->isNotEmpty())
      ? $dynamicProductLinks->all()
      : $defaultProductLinks;

    $buyNowLinks = [
      ['label_key' => 'menu_nav_buy_now_shopee', 'default' => 'Shopee', 'href_key' => 'menu_nav_buy_now_shopee_url', 'href_default' => 'https://shopee.ph/ultrafooddistributorsinc?categoryId=100629&entryPoint=ShopByPDP&itemId=1381695714&upstream=search', 'icon_key' => 'menu_nav_buy_now_shopee_icon', 'icon_svg' => '<svg viewBox="0 0 24 24" fill="none"><path d="M7 7.5A1.5 1.5 0 0 1 8.5 6h7A1.5 1.5 0 0 1 17 7.5v9A1.5 1.5 0 0 1 15.5 18h-7A1.5 1.5 0 0 1 7 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/><path d="M10 9.5h4M10 12h4M10 14.5h2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9.2 4.8h5.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>'],
      ['label_key' => 'menu_nav_buy_now_shopee_mall', 'default' => 'Shopee Mall', 'href_key' => 'menu_nav_buy_now_shopee_mall_url', 'href_default' => 'https://shopee.ph/menuphilippines?entryPoint=ShopBySearch&searchKeyword=menu%20store', 'icon_key' => 'menu_nav_buy_now_shopee_mall_icon', 'icon_svg' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 8.5 12 5l8 3.5v9L12 21l-8-3.5v-9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 5v16M4 8.5l8 3.5 8-3.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>'],
      ['label_key' => 'menu_nav_buy_now_lazada', 'default' => 'Lazada', 'href_key' => 'menu_nav_buy_now_lazada_url', 'href_default' => 'https://www.lazada.com.ph/tag/menu-food-solution/', 'icon_key' => 'menu_nav_buy_now_lazada_icon', 'icon_svg' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4.5 7.5h15v9h-15z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7.5 11h3m-3 3h5m2-6v9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>'],
      ['label_key' => 'menu_nav_buy_now_tiktok_shop', 'default' => 'TikTok Shop', 'href_key' => 'menu_nav_buy_now_tiktok_shop_url', 'href_default' => 'https://vt.tiktok.com/ZSuBR7RTA/?page=TikTokShop', 'icon_key' => 'menu_nav_buy_now_tiktok_shop_icon', 'icon_svg' => '<svg viewBox="0 0 24 24" fill="none"><path d="M14.5 4.5c.7 1.6 1.9 2.8 3.5 3.4v2.4a7.3 7.3 0 0 1-3.2-.8v5.2a4.8 4.8 0 1 1-4.1-4.8v2.3a2.4 2.4 0 1 0 1.8 2.3V4.5h2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
    ];
    $isMenuHome = request()->routeIs('menu');
    $isMenuProducts = request()->routeIs('menu.products.*', 'menu.products.category');
    $isMenuRecipes = request()->routeIs('menu.recipes.*', 'menu.category');
  @endphp

  @php
    $recipeCategories = [];
    try {
      $dbCats = DB::table('menu_recipe_categories')
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();
      if ($dbCats->count() > 0) {
        $recipeCategories = $dbCats->map(fn ($c) => [
          'id' => $c->id,
          'name' => $c->name,
          'slug' => $c->slug,
          'image' => $c->icon_filename ? ('/images/menu/'.$c->icon_filename) : null,
          'db' => true,
        ])->all();
      }
    } catch (\Throwable) {
      // Table may not exist yet during first migrate; fall back to config below.
    }
    if (empty($recipeCategories)) {
      $recipeCategories = config('menu-images.recipelist.dishes', []);
    }
  @endphp

  <div class="header-inner">
    <a href="{{ route('menu') }}" class="brand-block brand-link" aria-label="Go to Menu landing page">
      <img
        src="{{ $menuHeaderLogoSrc }}"
        alt="Menu logo"
        class="brand-logo"
      />
      <img
        src="{{ $menuHeaderWordmarkSrc }}"
        alt="Menu Menutong Sarap"
        class="brand-wordmark-image"
      />
    </a>

    <button id="burgerBtn" class="burger-btn" aria-label="Toggle menu" aria-expanded="false" aria-controls="mainNav">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <nav id="mainNav" class="main-nav">
      <a href="{{ route('menu') }}" class="nav-btn nav-btn-home{{ $isMenuHome ? ' is-active' : '' }}" data-ve-field="menu_nav_home" @if($fieldStyle('menu_nav_home') !== '') style="{{ $fieldStyle('menu_nav_home') }}" @endif>{{ $cv('menu_nav_home', 'Home') }}</a>

      <details class="dropdown{{ $isMenuProducts ? ' is-active' : '' }}">
        <summary class="nav-btn nav-btn-home nav-btn-dropdown{{ $isMenuProducts ? ' is-active' : '' }}" data-ve-field="menu_nav_products" @if($fieldStyle('menu_nav_products') !== '') style="{{ $fieldStyle('menu_nav_products') }}" @endif>{{ $cv('menu_nav_products', 'Products') }}</summary>
        <div class="dropdown-menu">
          @foreach ($productLinks as $link)
            @php
              $iconValue = $cv($link['icon_key'] ?? '', $link['icon'] ?? '');
              $iconSrc = $resolveIconSrc($iconValue);
              $hrefValue = $normalizeMenuProductHref((string) $cv($link['href_key'] ?? '', $link['href_default'] ?? '#'));
              $productLabelDisplay = (string) $cv($link['label_key'], $link['default']);

              $boostProductIconSlugs = [
                '/breading-mixes',
                '/powder-mixes',
                '/bouillon-cubes',
                '/noodles-and-pastas',
                '/powdered-drinks',
                '/professional-series',
              ];
              $boostProductIconKeys = [
                'menu_nav_products_breading_mixes',
                'menu_nav_products_powder_mixes',
                'menu_nav_products_bouillon_cubes',
                'menu_nav_products_noodles_pastas',
                'menu_nav_products_powdered_drinks',
                'menu_nav_products_professional_series',
              ];

              $boostProductIcon = in_array((string) ($link['label_key'] ?? ''), $boostProductIconKeys, true);
              if (!$boostProductIcon) {
                foreach ($boostProductIconSlugs as $slug) {
                  if ($hrefValue !== '' && str_contains($hrefValue, $slug)) {
                    $boostProductIcon = true;
                    break;
                  }
                }
              }
            @endphp
            <a href="{{ $hrefValue }}">
              <span class="dd-icon dd-icon-mono{{ $boostProductIcon ? ' dd-icon--boost' : '' }}" aria-hidden="true">
                @if ($iconSrc !== '')
                  <img src="{{ $iconSrc }}" alt="" />
                @endif
              </span>
              <span data-ve-field="{{ $link['label_key'] }}" @if($fieldStyle($link['label_key']) !== '') style="{{ $fieldStyle($link['label_key']) }}" @endif>{{ $productLabelDisplay }}</span>
            </a>
          @endforeach
        </div>
      </details>

      <details class="dropdown{{ $isMenuRecipes ? ' is-active' : '' }}">
        <summary class="nav-btn nav-btn-home nav-btn-dropdown{{ $isMenuRecipes ? ' is-active' : '' }}" data-ve-field="menu_nav_recipes" @if($fieldStyle('menu_nav_recipes') !== '') style="{{ $fieldStyle('menu_nav_recipes') }}" @endif>{{ $cv('menu_nav_recipes', 'Recipes') }}</summary>
        <div class="dropdown-menu">
          @foreach ($recipeCategories as $category)
            @php
              $recipeLabelKey = !empty($category['db']) && !empty($category['id'])
                ? 'menu_nav_recipe_category_'.$category['id'].'_label'
                : '';
              $recipeLabelStyle = $recipeLabelKey !== '' ? $fieldStyle($recipeLabelKey) : '';
            @endphp
            <a href="{{ route('menu.recipes.category', ['category' => $category['slug']]) }}">
              <span class="dd-icon dd-icon-mono" aria-hidden="true">
                @php
                  $img = $category['image'] ?? '';
                @endphp
                @if (is_string($img) && $img !== '')
                  <img src="{{ asset(ltrim($img, '/')) }}" alt="" />
                @endif
              </span><span @if($recipeLabelKey !== '') data-ve-field="{{ $recipeLabelKey }}" @endif @if($recipeLabelStyle !== '') style="{{ $recipeLabelStyle }}" @endif>{{ $recipeLabelKey !== '' ? $cv($recipeLabelKey, $category['name']) : $category['name'] }}</span>
            </a>
          @endforeach
        </div>
      </details>

      <details class="dropdown">
        <summary class="nav-btn nav-btn-home nav-btn-dropdown" data-ve-field="menu_nav_buy_now" @if($fieldStyle('menu_nav_buy_now') !== '') style="{{ $fieldStyle('menu_nav_buy_now') }}" @endif>{{ $cv('menu_nav_buy_now', 'Buy Now') }}</summary>
        <div class="dropdown-menu">
          @foreach ($buyNowLinks as $link)
            @php
              $iconValue = $cv($link['icon_key'] ?? '', '');
              $iconSrc = $resolveIconSrc($iconValue);
            @endphp
            <a href="{{ $cv($link['href_key'] ?? '', $link['href_default'] ?? '#') }}">
              <span class="dd-icon dd-icon-mono" aria-hidden="true">
                @if ($iconSrc !== '')
                  <img src="{{ $iconSrc }}" alt="" />
                @else
                  {!! $link['icon_svg'] ?? '' !!}
                @endif
              </span><span data-ve-field="{{ $link['label_key'] ?? '' }}" @if($fieldStyle($link['label_key'] ?? '') !== '') style="{{ $fieldStyle($link['label_key'] ?? '') }}" @endif>{{ $cv($link['label_key'] ?? '', $link['default'] ?? '') }}</span>
            </a>
          @endforeach
        </div>
      </details>
    </nav>
  </div>
</header>
