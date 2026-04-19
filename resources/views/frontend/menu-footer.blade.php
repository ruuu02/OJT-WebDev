<footer class="menu-footer">
  @php
    use Illuminate\Support\Facades\DB;
    use App\Models\Page;

    // Always resolve footer content from the main "menu" page so every menu screen shares one footer palette/content.
    $menuFooterPageId = Page::where('slug', 'menu')->value('id');
    $menuFooterContents = DB::table('text_content')
      ->when($menuFooterPageId, fn ($query) => $query->where('page_id', $menuFooterPageId))
      ->get()
      ->map(fn($r) => ['key' => $r->key, 'value' => $r->value, 'section' => 'text_content']);
    $footerCv = fn(string $key, string $default = '') =>
      ($menuFooterContents->firstWhere('key', $key)['value'] ?? null) ?? $default;

    // Visual Editor provides $allMedia; public routes do not. Default to an empty array.
    $allMedia = $allMedia ?? [];

    $menuPageIdForMedia = $menuFooterPageId;
    $footerLogoFallback = asset('images/logo/e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png');
    $footerLogoId = $footerCv('media_id_menu_footer_logo', '');
    $footerLogoId = is_scalar($footerLogoId) ? trim((string) $footerLogoId) : '';
    $footerLogoSrc = $footerLogoFallback;
    if ($footerLogoId !== '') {
      if (isset($allMedia) && $allMedia) {
        $row = collect($allMedia)->first(fn ($r) =>
          ($r['section'] ?? null) === 'logo' && (string) ($r['id'] ?? '') === $footerLogoId
        );
        if ($row && !empty($row['filename'])) {
          $footerLogoSrc = asset('images/logo/' . $row['filename']);
        }
      }
      if ($footerLogoSrc === $footerLogoFallback) {
        $filename = DB::table('logos')
          ->when($menuPageIdForMedia, fn ($q) => $q->where('page_id', $menuPageIdForMedia))
          ->where('id', $footerLogoId)
          ->value('filename');
        if ($filename) $footerLogoSrc = asset('images/logo/' . $filename);
      }
    }

    $resolveFooterBrandLogo = function (string $mediaKey, string $fallback) use ($footerCv, $allMedia, $menuPageIdForMedia) {
      $id = trim((string) $footerCv($mediaKey, ''));
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

    $ultrafoodOtherFallback = asset('images/logo/65480775-6edc-4e3e-b8c4-f989fe69c17c.png');
    $nordicOtherFallback = asset('images/logo/Korpala-Nordic-Logo.png');
    $ultrafoodOtherSrc = $resolveFooterBrandLogo('media_id_menu_footer_ultrafood_logo', $ultrafoodOtherFallback);
    $nordicOtherSrc = $resolveFooterBrandLogo('media_id_menu_footer_nordic_logo', $nordicOtherFallback);
  @endphp

  <div class="menu-footer-inner">
    <div class="menu-footer-contact">
      <div class="menu-footer-contact-row">
        <div class="menu-footer-brand">
          <a href="{{ route('menu') }}" aria-label="Go to Menu landing page">
            <img src="{{ $footerLogoSrc }}" alt="Menu Food logo" class="menu-footer-logo" />
          </a>
        </div>
        <div class="menu-footer-contact-copy">
          <p class="menu-footer-social-title menu-footer-contact-title" data-ve-field="menu_footer_contact_title">{{ $footerCv('menu_footer_contact_title', 'Contact Us') }}</p>
          <p><strong data-ve-field="menu_footer_contact_label">{{ $footerCv('menu_footer_contact_label', 'Contact Number:') }}</strong> <span data-ve-field="menu_footer_contact_number">{{ $footerCv('menu_footer_contact_number', '+1 234 567 890') }}</span></p>
          <p><strong data-ve-field="menu_footer_customer_service_label">{{ $footerCv('menu_footer_customer_service_label', 'Customer Service:') }}</strong> <span data-ve-field="menu_footer_customer_service_number">{{ $footerCv('menu_footer_customer_service_number', '+1 234 000 111') }}</span></p>
          <p><strong data-ve-field="menu_footer_email_label">{{ $footerCv('menu_footer_email_label', 'Email:') }}</strong> <a href="mailto:{{ $footerCv('menu_footer_email', 'ultrafood05@gmail.com') }}" data-ve-field="menu_footer_email">{{ $footerCv('menu_footer_email', 'ultrafood05@gmail.com') }}</a></p>
          <p><strong data-ve-field="menu_footer_customer_email_label">{{ $footerCv('menu_footer_customer_email_label', 'Customer Service Email:') }}</strong> <a href="mailto:{{ $footerCv('menu_footer_customer_email', 'customerservice@menufood.com') }}" data-ve-field="menu_footer_customer_email">{{ $footerCv('menu_footer_customer_email', 'customerservice@menufood.com') }}</a></p>
        </div>
      </div>
    </div>

    <div class="menu-footer-social">
      <p class="menu-footer-social-title" id="menuFooterFollowTitle" data-ve-field="menu_footer_follow_title">{{ $footerCv('menu_footer_follow_title', 'Follow Us') }}</p>
      <div class="menu-social-icons">
        <a href="{{ $footerCv('menu_footer_facebook_url', 'https://www.facebook.com/nordicfoodsphilippines') }}" aria-label="Facebook" class="menu-social-link" title="Facebook" target="_blank" rel="noopener noreferrer">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.5-3.89 3.78-3.89 1.09 0 2.23.2 2.23.2v2.46H15.2c-1.24 0-1.63.77-1.63 1.56V12h2.77l-.44 2.89h-2.33v6.99A10 10 0 0 0 22 12Z"/></svg>
        </a>
        <a href="{{ $footerCv('menu_footer_instagram_url', 'https://www.instagram.com/nordicfoodsph/reels/') }}" aria-label="Instagram" class="menu-social-link" title="Instagram" target="_blank" rel="noopener noreferrer">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2Zm0 1.8A3.95 3.95 0 0 0 3.8 7.75v8.5a3.95 3.95 0 0 0 3.95 3.95h8.5a3.95 3.95 0 0 0 3.95-3.95v-8.5a3.95 3.95 0 0 0-3.95-3.95h-8.5Zm8.9 1.35a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 1.8a3.2 3.2 0 1 0 0 6.4 3.2 3.2 0 0 0 0-6.4Z"/></svg>
        </a>
        <a href="{{ $footerCv('menu_footer_tiktok_url', 'https://www.tiktok.com/@nordicfoods_ph') }}" aria-label="TikTok" class="menu-social-link" title="TikTok" target="_blank" rel="noopener noreferrer">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14.5 3h2.3a4.8 4.8 0 0 0 3.2 3.2v2.34a7.05 7.05 0 0 1-3.2-.78v6.02a5.8 5.8 0 1 1-5.03-5.76v2.4a3.45 3.45 0 1 0 2.7 3.36V3Z"/></svg>
        </a>
      </div>
    </div>

    <div class="menu-footer-links">
      <p class="menu-footer-social-title menu-footer-brands-title" id="menuFooterBrandsTitle" data-ve-field="menu_footer_brands_title">{{ $footerCv('menu_footer_brands_title', 'Our Brands') }}</p>
      <div class="menu-other-logos">
        <a href="{{ route('home') }}" class="menu-other-logo-link" aria-label="Ultrafood page">
          <img src="{{ $ultrafoodOtherSrc }}" alt="Ultrafood logo" class="menu-other-logo" />
        </a>
        <a href="{{ route('nordic') }}" class="menu-other-logo-link" aria-label="Nordic page">
          <img src="{{ $nordicOtherSrc }}" alt="Nordic logo" class="menu-other-logo" />
        </a>
      </div>
    </div>
  </div>
</footer>
@once
  @php
    $menuFooterTextStyles = [];
    foreach ($menuFooterContents as $row) {
      $k = (string) ($row['key'] ?? '');
      if (!str_starts_with($k, 'menu_footer_')) {
        continue;
      }
      if (!preg_match('/^(.+)_(color|font|font_size)$/', $k, $m)) {
        continue;
      }
      $base = $m[1];
      $suffix = $m[2];
      if (!isset($menuFooterTextStyles[$base])) {
        $menuFooterTextStyles[$base] = ['color' => '', 'font' => '', 'font_size' => ''];
      }
      $menuFooterTextStyles[$base][$suffix] = (string) ($row['value'] ?? '');
    }
  @endphp
  @if(!empty($menuFooterTextStyles))
    <style id="menu-footer-cms-text-styles">
    @foreach($menuFooterTextStyles as $base => $st)
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
      .menu-footer [data-ve-field="{{ e($base) }}"] { {{ $css }} }
      .menu-footer a[data-ve-field="{{ e($base) }}"] { {{ $css }} }
    @endif
    @endforeach
    </style>
  @endif
@endonce
@once
  <script src="{{ asset('js/menu.js') }}"></script>
@endonce
