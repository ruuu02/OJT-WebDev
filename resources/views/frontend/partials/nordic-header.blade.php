@php
  use App\Models\Page;
  use Illuminate\Support\Facades\DB;

  if (!isset($allContents)) {
    $nordicPageId = Page::where('slug', 'nordic')->value('id');
    $allContents = DB::table('text_content')
      ->when($nordicPageId, fn ($query) => $query->where('page_id', $nordicPageId))
      ->get()
      ->map(fn ($row) => ['key' => $row->key, 'value' => $row->value, 'section' => 'text_content']);
  }

  $cv = $cv ?? fn (string $key, string $default = '') =>
    ($allContents->firstWhere('key', $key)['value'] ?? null) ?? $default;

  $nordicLogoUrl = function (string $mediaKey, string $fallback) use ($cv): string {
    $mediaId = trim((string) $cv($mediaKey, ''));
    if ($mediaId === '') {
      return asset(ltrim($fallback, '/'));
    }

    $filename = DB::table('logos')
      ->where('id', $mediaId)
      ->value('filename');

    if (!$filename) {
      return asset(ltrim($fallback, '/'));
    }

    return asset('images/logo/'.$filename);
  };
@endphp
@php
  $headerButtonTextStyle = 'font-size:1.08rem;font-weight:700;line-height:1.2;';
  $fieldStyle = function (string $baseKey) use ($cv): string {
    return collect([
      $cv($baseKey.'_font') ? "font-family:'".$cv($baseKey.'_font')."',sans-serif" : null,
      $cv($baseKey.'_font_size') ? 'font-size:'.$cv($baseKey.'_font_size') : null,
      $cv($baseKey.'_color') ? 'color:'.$cv($baseKey.'_color') : null,
    ])->filter()->implode(';');
  };
  $resolveIconSrc = function (?string $value) {
    $value = is_string($value) ? trim($value) : '';
    if ($value === '') return '';
    if (preg_match('#^https?://#i', $value)) return $value;
    if (str_starts_with($value, '/')) return $value;
    return asset($value);
  };
  $nordicBuyNowLinks = [
    ['label_key' => 'nordic_nav_buy_now_shopee', 'default' => 'Shopee', 'href_key' => 'nordic_nav_buy_now_shopee_url', 'href_default' => 'https://shopee.ph/nordicfoodsphilippines?entryPoint=ShopBySearch&searchKeyword=nordicfoods', 'icon_key' => 'nordic_nav_buy_now_shopee_icon', 'icon_svg' => '<svg viewBox="0 0 24 24" fill="none"><path d="M7 7.5A1.5 1.5 0 0 1 8.5 6h7A1.5 1.5 0 0 1 17 7.5v9A1.5 1.5 0 0 1 15.5 18h-7A1.5 1.5 0 0 1 7 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/><path d="M10 9.5h4M10 12h4M10 14.5h2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9.2 4.8h5.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>'],
    ['label_key' => 'nordic_nav_buy_now_lazada', 'default' => 'Lazada', 'href_key' => 'nordic_nav_buy_now_lazada_url', 'href_default' => 'https://www.lazada.com.ph/shop/nordicfoodsphilippines/?spm=a2o4l.pdp_revamp.seller.1.57443f100i8Nd6&itemId=4541244341&channelSource=pdp', 'icon_key' => 'nordic_nav_buy_now_lazada_icon', 'icon_svg' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4.5 7.5h15v9h-15z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7.5 11h3m-3 3h5m2-6v9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>'],
    ['label_key' => 'nordic_nav_buy_now_tiktok_shop', 'default' => 'Tiktok Shop', 'href_key' => 'nordic_nav_buy_now_tiktok_shop_url', 'href_default' => 'https://www.tiktok.com/@nordicfoods_ph?_t=ZS-8v31aZJkFnO&_r=1', 'icon_key' => 'nordic_nav_buy_now_tiktok_shop_icon', 'icon_svg' => '<svg viewBox="0 0 24 24" fill="none"><path d="M14.5 4.5c.7 1.6 1.9 2.8 3.5 3.4v2.4a7.3 7.3 0 0 1-3.2-.8v5.2a4.8 4.8 0 1 1-4.1-4.8v2.3a2.4 2.4 0 1 0 1.8 2.3V4.5h2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
  ];
@endphp

<header class="nordic-header" id="nordicHeader">
  <div class="nordic-header__inner">
    <a href="{{ route('nordic') }}" class="nordic-logo" aria-label="Korpala Nordic Home">
      <img
        src="{{ $nordicLogoUrl('media_id_nordic_header_logo', 'images/logo/Korpala-Nordic-Logo.png') }}"
        alt="Korpala Nordic"
        class="nordic-logo__mark"
      />
      <img
        src="{{ $nordicLogoUrl('media_id_nordic_header_wordmark', 'images/nordic/Taste-the-Difference-White (1).png') }}"
        alt="Taste the Difference"
        class="nordic-logo__title-image"
      />
    </a>

    <div class="nordic-header__nav-group">
      <nav class="nordic-nav" aria-label="Main navigation">
        <a href="{{ route('nordic') }}" class="{{ request()->routeIs('nordic') ? 'active' : '' }}" data-ve-field="nordic_nav_home" style="{{ $headerButtonTextStyle }}">{{ $cv('nordic_nav_home', 'Home') }}</a>
        <a href="{{ route('nordic.products') }}" class="{{ request()->routeIs('nordic.products') ? 'active' : '' }}" data-ve-field="nordic_nav_products" style="{{ $headerButtonTextStyle }}">{{ $cv('nordic_nav_products', 'Products') }}</a>
        <a href="{{ route('nordic.recipes') }}" class="{{ request()->routeIs('nordic.recipes*') ? 'active' : '' }}" data-ve-field="nordic_nav_recipes" style="{{ $headerButtonTextStyle }}">{{ $cv('nordic_nav_recipes', 'Recipes') }}</a>
      </nav>

      <div class="buy-dropdown" id="buyDropdown">
        <button type="button" class="buy-btn" id="buyToggle" aria-haspopup="true" aria-expanded="false" aria-controls="buyMenu">
          <span data-ve-field="nordic_nav_buy_now" style="{{ $headerButtonTextStyle }}@if($fieldStyle('nordic_nav_buy_now') !== ''){{ ';'.$fieldStyle('nordic_nav_buy_now') }}@endif">{{ $cv('nordic_nav_buy_now', 'Buy Now') }}</span> <span class="arrow">&#9662;</span>
        </button>
        <div class="buy-menu" id="buyMenu">
          @foreach ($nordicBuyNowLinks as $link)
            @php
              $iconSrc = $resolveIconSrc($cv($link['icon_key'], ''));
            @endphp
            <a href="{{ $cv($link['href_key'], $link['href_default']) }}" target="_blank" rel="noopener noreferrer">
              <span class="nordic-buy-icon" aria-hidden="true">
                @if ($iconSrc !== '')
                  <img src="{{ $iconSrc }}" alt="" />
                @else
                  {!! $link['icon_svg'] !!}
                @endif
              </span>
              <span class="nordic-buy-label" data-ve-field="{{ $link['label_key'] }}" @if($fieldStyle($link['label_key']) !== '') style="{{ $fieldStyle($link['label_key']) }}" @endif>{{ $cv($link['label_key'], $link['default']) }}</span>
            </a>
          @endforeach
        </div>
      </div>
    </div>

    <button class="burger" id="nordicBurger" aria-label="Toggle menu" aria-expanded="false" aria-controls="nordicMobileMenu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <nav class="nordic-mobile" id="nordicMobileMenu" aria-label="Mobile navigation">
      <a href="{{ route('nordic') }}" data-ve-field="nordic_nav_home" style="{{ $headerButtonTextStyle }}">{{ $cv('nordic_nav_home', 'Home') }}</a>
      <a href="{{ route('nordic.products') }}" data-ve-field="nordic_nav_products" style="{{ $headerButtonTextStyle }}">{{ $cv('nordic_nav_products', 'Products') }}</a>
      <a href="{{ route('nordic.recipes') }}" data-ve-field="nordic_nav_recipes" style="{{ $headerButtonTextStyle }}">{{ $cv('nordic_nav_recipes', 'Recipes') }}</a>
      <details class="nordic-mobile-dropdown">
        <summary class="nordic-mobile-summary" data-ve-field="nordic_nav_buy_now" style="{{ $headerButtonTextStyle }}@if($fieldStyle('nordic_nav_buy_now') !== ''){{ ';'.$fieldStyle('nordic_nav_buy_now') }}@endif">
          {{ $cv('nordic_nav_buy_now', 'Buy Now') }}
        </summary>
        <div class="nordic-mobile-dropdown-menu">
          @foreach ($nordicBuyNowLinks as $link)
            @php
              $iconSrc = $resolveIconSrc($cv($link['icon_key'], ''));
            @endphp
            <a href="{{ $cv($link['href_key'], $link['href_default']) }}" target="_blank" rel="noopener noreferrer">
              <span class="nordic-buy-icon" aria-hidden="true">
                @if ($iconSrc !== '')
                  <img src="{{ $iconSrc }}" alt="" />
                @else
                  {!! $link['icon_svg'] !!}
                @endif
              </span>
              <span class="nordic-buy-label" data-ve-field="{{ $link['label_key'] }}" @if($fieldStyle($link['label_key']) !== '') style="{{ $fieldStyle($link['label_key']) }}" @endif>{{ $cv($link['label_key'], $link['default']) }}</span>
            </a>
          @endforeach
        </div>
      </details>
    </nav>
  </div>
</header>
