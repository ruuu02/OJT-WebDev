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

  if (!isset($allMedia)) {
    $nordicPageId = $nordicPageId ?? Page::where('slug', 'nordic')->value('id');
    $allMedia = DB::table('logos')
      ->when($nordicPageId, fn ($query) => $query->where('page_id', $nordicPageId))
      ->get()
      ->map(fn ($row) => array_merge((array) $row, ['section' => 'logo']));
  }

  $cv = $cv ?? fn (string $key, string $default = '') =>
    ($allContents->firstWhere('key', $key)['value'] ?? null) ?? $default;

  $resolveLogoById = function (string $contentKey, string $fallback) use ($cv, $allMedia): string {
    $mediaId = trim((string) $cv($contentKey, ''));
    if ($mediaId === '') {
      return asset($fallback);
    }

    $row = collect($allMedia)->first(fn ($item) =>
      ($item['section'] ?? $item->section ?? '') === 'logo'
      && (string) ($item['id'] ?? $item->id ?? '') === $mediaId
    );

    $filename = is_array($row) ? ($row['filename'] ?? null) : ($row->filename ?? null);
    return $filename ? asset('images/logo/'.$filename) : asset($fallback);
  };

  $footerLogoSrc = $resolveLogoById('media_id_nordic_footer_logo', 'images/logo/Korpala-Nordic-Logo.png');
  $footerUltrafoodLogoSrc = $resolveLogoById('media_id_nordic_footer_ultrafood_logo', 'images/logo/65480775-6edc-4e3e-b8c4-f989fe69c17c.png');
  $footerMenuLogoSrc = $resolveLogoById('media_id_nordic_footer_menu_logo', 'images/logo/e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png');
@endphp

<footer class="nordic-footer">
  <div class="footer-inner">
    <div class="footer-left">
      <a href="{{ route('nordic') }}" class="footer-logo" aria-label="Korpala Nordic">
        <img src="{{ $footerLogoSrc }}" alt="Korpala Nordic" />
      </a>
      <div class="footer-contact">
        <p class="footer-title" data-ve-field="nordic_footer_contact_title">{{ $cv('nordic_footer_contact_title', 'Contact Us') }}</p>
        <p class="contact-item">
          <svg class="contact-item-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor" d="M22 16.9v3a2 2 0 0 1-2.2 2A19.8 19.8 0 0 1 11.2 19a19.5 19.5 0 0 1-6.1-6.1A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6.1 6.1l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>
          </svg>
          <span><span data-ve-field="nordic_footer_contact_label">{{ $cv('nordic_footer_contact_label', 'Contact Number:') }}</span> <a href="tel:{{ preg_replace('/\s+/', '', $cv('nordic_footer_contact_number', '+1 234 567 890')) }}" data-ve-field="nordic_footer_contact_number">{{ $cv('nordic_footer_contact_number', '+1 234 567 890') }}</a></span>
        </p>
        <p class="contact-item">
          <svg class="contact-item-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor" d="M12 2a7 7 0 0 0-7 7v4.2A2.8 2.8 0 0 0 7.8 16H9v-6H7v-1a5 5 0 1 1 10 0v1h-2v6h1.2A2.8 2.8 0 0 0 19 13.2V9a7 7 0 0 0-7-7zM9 18h6v2H9z"/>
          </svg>
          <span><span data-ve-field="nordic_footer_customer_service_label">{{ $cv('nordic_footer_customer_service_label', 'Customer Service:') }}</span> <a href="tel:{{ preg_replace('/\s+/', '', $cv('nordic_footer_customer_service_number', '+1 234 000 111')) }}" data-ve-field="nordic_footer_customer_service_number">{{ $cv('nordic_footer_customer_service_number', '+1 234 000 111') }}</a></span>
        </p>
        <p class="contact-item">
          <svg class="contact-item-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor" d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm0 2v.2L12 13l9-5.8V7H3z"/>
          </svg>
          <span><span data-ve-field="nordic_footer_email_label">{{ $cv('nordic_footer_email_label', 'Email:') }}</span> <a href="mailto:{{ $cv('nordic_footer_email', 'customercare@nordic-foods.com') }}" data-ve-field="nordic_footer_email">{{ $cv('nordic_footer_email', 'customercare@nordic-foods.com') }}</a></span>
        </p>
        <p class="contact-item">
          <svg class="contact-item-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor" d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm0 2v.2L12 13l9-5.8V7H3z"/>
          </svg>
          <span><span data-ve-field="nordic_footer_customer_email_label">{{ $cv('nordic_footer_customer_email_label', 'Customer Service Email:') }}</span> <a href="mailto:{{ $cv('nordic_footer_customer_email', 'customercare@nordic-foods.com') }}" data-ve-field="nordic_footer_customer_email">{{ $cv('nordic_footer_customer_email', 'customercare@nordic-foods.com') }}</a></span>
        </p>
      </div>
    </div>         

    <div class="footer-middle">
      <p class="footer-title" data-ve-field="nordic_footer_follow_title">{{ $cv('nordic_footer_follow_title', 'Follow Us') }}</p>
      <div class="footer-social">
        <a href="{{ $cv('nordic_footer_facebook_url', 'https://www.facebook.com/nordicfoodsphilippines') }}" class="social-link" aria-label="Facebook" title="Facebook" target="_blank" rel="noopener noreferrer">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor" d="M13.5 21v-8h2.7l.4-3h-3.1V8.2c0-.9.3-1.5 1.6-1.5H16V4a14 14 0 0 0-2.1-.2c-2.1 0-3.5 1.3-3.5 3.7V10H8v3h2.4v8h3.1z"/>
          </svg>
          <span>Facebook</span>
        </a>
        <a href="{{ $cv('nordic_footer_instagram_url', 'https://www.instagram.com/nordicfoodsph/reels/') }}" class="social-link" aria-label="Instagram" title="Instagram" target="_blank" rel="noopener noreferrer">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor" d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7zm10.8 1.5a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4zM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/>
          </svg>
          <span>Instagram</span>
        </a>
        <a href="{{ $cv('nordic_footer_tiktok_url', 'https://www.tiktok.com/@nordicfoods_ph') }}" class="social-link" aria-label="TikTok" title="TikTok" target="_blank" rel="noopener noreferrer">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor" d="M15 3c.6 1.8 1.8 3.2 3.6 4v2.6c-1.2 0-2.3-.4-3.3-1V15a5.5 5.5 0 1 1-4.7-5.4V12a2.9 2.9 0 1 0 2.1 2.8V3H15z"/>
          </svg>
          <span>TikTok</span>
        </a>
      </div>
    </div>

    <div class="footer-right">
      <p class="footer-title" data-ve-field="nordic_footer_brands_title">{{ $cv('nordic_footer_brands_title', 'OUR BRANDS') }}</p>
      <a href="{{ route('home') }}" class="footer-brand-logo" aria-label="Ultrafood">
        <img src="{{ $footerUltrafoodLogoSrc }}" alt="Ultrafood" />
      </a>
      <a href="{{ route('menu') }}" class="footer-brand-logo" aria-label="Menu Food">
        <img src="{{ $footerMenuLogoSrc }}" alt="Menu Food" />
      </a>
    </div>
  </div>
</footer>
