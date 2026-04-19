@php
  $slug = strtolower((string) ($mailData['page_slug'] ?? 'ultrafood'));
  $brand = str_contains($slug, 'menu') ? 'menu' : (str_contains($slug, 'nordic') ? 'nordic' : 'ultrafood');

  $themes = [
    'ultrafood' => [
      'brand_name' => 'Ultrafood Distributors Inc.',
      'logo' => 'images/logo/65480775-6edc-4e3e-b8c4-f989fe69c17c.png',
      'green_dark' => '#1f4d15',
      'green_base' => '#2d6a1f',
      'green_mid' => '#3d8a2f',
      'green_light' => '#a8d88a',
      'green_pale' => '#e6f3de',
      'green_surf' => '#f5f9f2',
      'green_border' => '#d4e6c8',
      'text_dark' => '#1a2e14',
      'text_mid' => '#3a5430',
      'text_muted' => '#5a7a50',
      'text_faint' => '#7a9a6a',
    ],
    'menu' => [
      'brand_name' => 'Menu Food',
      'logo' => 'images/logo/Menu-menutong-Sarap.png',
      'green_dark' => '#5b160f',
      'green_base' => '#7f1d1d',
      'green_mid' => '#9f3a2a',
      'green_light' => '#f1c96b',
      'green_pale' => '#fff2dc',
      'green_surf' => '#fff8f1',
      'green_border' => '#efd4b5',
      'text_dark' => '#3c1409',
      'text_mid' => '#5f2615',
      'text_muted' => '#74401f',
      'text_faint' => '#8b5e37',
    ],
    'nordic' => [
      'brand_name' => 'Korpala Nordic',
      'logo' => 'images/logo/Korpala-Nordic-Logo.png',
      'green_dark' => '#16242d',
      'green_base' => '#223947',
      'green_mid' => '#38576a',
      'green_light' => '#e6ba40',
      'green_pale' => '#f8edd0',
      'green_surf' => '#f4f6f8',
      'green_border' => '#d6dee5',
      'text_dark' => '#1b2e3b',
      'text_mid' => '#334b5a',
      'text_muted' => '#4f6675',
      'text_faint' => '#6a7f8d',
    ],
  ];
  $theme = $themes[$brand];
  $logoPath = public_path($theme['logo']);
  $logoRelative = '/' . ltrim($theme['logo'], '/');
  $appUrl = trim((string) config('app.url', ''));
  $appUrl = preg_replace('#^(https?://)+#i', '$1', $appUrl);
  $appUrl = str_replace(['://http//', '://https//'], '://', $appUrl);
  $hasValidAppUrl = $appUrl !== '' && filter_var($appUrl, FILTER_VALIDATE_URL);
  $logoUrl = $hasValidAppUrl ? rtrim($appUrl, '/') . $logoRelative : $logoRelative;
  $logoSrc = $logoUrl;
  if (isset($message) && is_object($message) && method_exists($message, 'embed') && is_file($logoPath)) {
      try {
          $logoSrc = $message->embed($logoPath);
      } catch (\Throwable $e) {
          $logoSrc = $logoUrl;
      }
  }
  $submissionNumber = (int) ($mailData['submission_id'] ?? 0);
  $inquiryRef = (string) ($mailData['inquiry_ref'] ?? ('INQ-' . str_pad((string) $submissionNumber, 6, '0', STR_PAD_LEFT)));
  $submittedAtText = trim((string) ($mailData['submitted_at'] ?? ''));
  $submittedAtTz = trim((string) ($mailData['submitted_at_tz'] ?? ''));
  $submittedAtDisplay = trim($submittedAtText . ($submittedAtTz !== '' ? ' ' . $submittedAtTz : ''));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="color-scheme" content="light dark">
  <meta name="supported-color-schemes" content="light dark">
  <title>Message Inquiry</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green-dark:   {{ $theme['green_dark'] }};
      --green-base:   {{ $theme['green_base'] }};
      --green-mid:    {{ $theme['green_mid'] }};
      --green-light:  {{ $theme['green_light'] }};
      --green-pale:   {{ $theme['green_pale'] }};
      --green-surf:   {{ $theme['green_surf'] }};
      --green-border: {{ $theme['green_border'] }};
      --text-dark:    {{ $theme['text_dark'] }};
      --text-mid:     {{ $theme['text_mid'] }};
      --text-muted:   {{ $theme['text_muted'] }};
      --text-faint:   {{ $theme['text_faint'] }};
      --white:        #ffffff;
      --font-display: "Inter", "Segoe UI", "Helvetica Neue", Arial, sans-serif;
      --font-body:    "Inter", "Segoe UI", "Helvetica Neue", Arial, sans-serif;
    }

    body {
      font-family: var(--font-body);
      background: #eef4ea;
      min-height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 28px 14px;
    }

    .wrap {
      width: 100%;
      max-width: 680px;
      background: var(--white);
      border: 0.5px solid var(--green-border);
      border-radius: 6px;
      overflow: hidden;
    }

    .hdr {
      background: #ffffff !important;
      border-bottom: 1px solid rgba(0, 0, 0, 0.12);
      padding: 20px 28px;
      display: block;
    }
    .logo-row { display: block; }
    .brand-logo {
      width: auto;
      max-width: 132px;
      height: 30px;
      object-fit: contain;
      object-position: left center;
      display: block;
      filter: none;
    }
    .brand-logo--light {
      filter: none;
    }
    .brand-text {
      color: var(--text-dark);
      font-size: 13px;
      font-weight: 600;
      letter-spacing: .09em;
      line-height: 1.35;
      text-transform: uppercase;
    }
    .brand-text small {
      display: block;
      font-size: 10px;
      font-weight: 400;
      color: var(--text-muted);
      letter-spacing: .14em;
    }
    .hdr-badge {
      background: var(--green-dark);
      color: #ffffff;
      font-size: 10px;
      font-weight: 600;
      letter-spacing: .1em;
      padding: 5px 13px;
      border-radius: 2px;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .eyebrow {
      background: var(--green-surf);
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
      padding: 18px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .eyebrow-title {
      font-family: var(--font-display);
      font-size: 23px;
      font-weight: 700;
      color: var(--text-dark);
      letter-spacing: 0;
    }
    .eyebrow-sub {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 3px;
      letter-spacing: .03em;
    }
    .status-pill {
      background: var(--green-pale);
      color: var(--green-base);
      font-size: 10.5px;
      font-weight: 600;
      letter-spacing: .09em;
      padding: 5px 13px;
      border-radius: 2px;
      border: 0.5px solid var(--green-light);
      text-transform: uppercase;
      white-space: nowrap;
      flex-shrink: 0;
    }

    .section {
      padding: 24px 28px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }
    .section:last-of-type { border-bottom: none; }

    .sec-label {
      font-size: 12px;
      font-weight: 700;
      color: #1f3524;
      letter-spacing: .12em;
      text-transform: uppercase;
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .sec-label::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(0, 0, 0, 0.12);
    }

    .avatar-row {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      margin-bottom: 18px;
    }
    .avatar {
      width: 56px;
      height: 56px;
      min-width: 56px;
      border-radius: 50%;
      background: var(--green-base);
      display: inline-block;
      color: var(--green-pale);
      font-size: 17px;
      font-weight: 700;
      flex-shrink: 0;
      letter-spacing: .04em;
      text-transform: uppercase;
      line-height: 52px;
      text-align: center;
      vertical-align: middle;
      margin: 0;
    }
    .avatar-name {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-dark);
      line-height: 1.3;
    }
    .avatar-email {
      font-size: 13.5px;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .field-row {
      display: grid;
      grid-template-columns: 130px 1fr;
      gap: 8px;
      padding: 7px 0;
      border-bottom: 0;
    }
    .field-row:last-child { border-bottom: none; }
    .field-label {
      font-size: 13px;
      color: var(--text-faint);
      font-weight: 500;
      letter-spacing: .02em;
      padding-top: 1px;
    }
    .field-value {
      font-size: 15.5px;
      color: var(--text-dark);
      font-weight: 400;
      word-break: break-word;
    }

    .msg-box {
      background: transparent;
      border: 0;
      border-bottom: 0;
      border-radius: 0;
      padding: 8px 0 0;
    }
    .msg-box p {
      font-size: 15.5px;
      color: var(--text-mid);
      line-height: 1.85;
      font-weight: 400;
      white-space: pre-line;
    }

    .meta-grid {
      display: block;
    }
    .meta-card {
      background: transparent;
      border: 0;
      border-bottom: 0;
      border-radius: 0;
      padding: 8px 0 10px;
    }
    .meta-card-label {
      font-size: 12px;
      color: var(--text-faint);
      font-weight: 600;
      letter-spacing: .12em;
      text-transform: uppercase;
      margin-bottom: 5px;
    }
    .meta-card-value {
      font-size: 15px;
      color: var(--text-dark);
      font-weight: 500;
      line-height: 1.4;
      word-break: break-word;
    }
    .ftr {
      background: var(--green-dark);
      padding: 14px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .ftr-text {
      font-size: 11px;
      color: #7ab86a;
      letter-spacing: .04em;
    }
    .ftr-ref {
      font-size: 11px;
      color: var(--green-light);
      font-weight: 500;
      letter-spacing: .06em;
      white-space: nowrap;
    }

    @media (max-width: 700px) {
      body { padding: 14px 8px; }
      .hdr, .eyebrow, .section, .ftr { padding-left: 16px; padding-right: 16px; }
      .meta-grid { grid-template-columns: 1fr; }
      .header-main,
      .header-side {
        display: block !important;
        width: 100% !important;
        text-align: left !important;
      }
      .header-side { padding-top: 10px !important; }
    }
    @media (max-width: 520px) {
      .eyebrow-title { font-size: 20px; }
      .eyebrow { flex-direction: column; align-items: flex-start; }
      .field-row { grid-template-columns: 1fr; gap: 4px; padding: 10px 0; }
      .field-label { padding-top: 0; }
      .ftr { flex-direction: column; align-items: flex-start; }
    }
    @media (prefers-color-scheme: dark) {
      body { background: #111812 !important; color: #eaf5df !important; }
      .wrap { background: #1a2218 !important; border-color: #4f6742 !important; }
      .eyebrow, .section, .meta-card, .msg-box { background: #1f2a1c !important; border-color: #4f6742 !important; }
      .eyebrow-title, .field-value, .meta-card-value, .avatar-name { color: #f1f8ea !important; }
      .field-label, .meta-card-label, .eyebrow-sub, .avatar-email { color: #b7ccaa !important; }
      .hdr-badge { color: #f5f7f4 !important; }
      .ftr-text, .ftr-ref { color: #e3eee0 !important; }
    }
  </style>
</head>
<body>
@php
  $name = trim((string) ($mailData['name'] ?? 'Unknown Sender'));
  $nameParts = preg_split('/\s+/', $name) ?: [];
  $initials = strtoupper(substr(($nameParts[0] ?? 'U'), 0, 1) . substr(($nameParts[1] ?? ''), 0, 1));
  if ($initials === '') $initials = 'U';
@endphp

<div class="wrap">
  <div class="hdr">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td class="header-main" valign="middle" width="72%" style="padding:0;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0">
            <tr>
              <td valign="middle" style="padding:0 10px 0 0;">
                <img
                  src="{{ $logoSrc }}"
                  alt="{{ $theme['brand_name'] }} logo"
                  class="brand-logo {{ $brand === 'ultrafood' ? 'brand-logo--light' : '' }}"
                >
              </td>
              <td valign="middle" style="padding:0;">
                <div class="brand-text">{{ $theme['brand_name'] }}<small>{{ strtoupper($brand) }}</small></div>
              </td>
            </tr>
          </table>
        </td>
        <td class="header-side" align="right" valign="middle" width="28%" style="padding:0;">
          <span class="hdr-badge" style="background:{{ $theme['green_dark'] }};color:#ffffff;">New Message Inquiry</span>
        </td>
      </tr>
    </table>
  </div>

  <div class="eyebrow">
    <div>
      <div class="eyebrow-title">Message Inquiry</div>
      <div class="eyebrow-sub">Received &middot; {{ $submittedAtDisplay }} &middot; Ref #{{ $inquiryRef }}</div>
    </div>
    <div class="status-pill">Unread</div>
  </div>

  <div class="section">
    <div class="sec-label">Sender Information</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;">
      <tr>
        <td width="62" valign="top" style="padding:0;">
          <div class="avatar" style="background:{{ $theme['green_dark'] }};border:2px solid {{ $theme['green_light'] }};color:#ffffff;">{{ $initials }}</div>
        </td>
        <td valign="top" style="padding:4px 0 0;">
          <div class="avatar-name">{{ $mailData['name'] }}</div>
          <div class="avatar-email">{{ $mailData['email'] }}</div>
        </td>
      </tr>
    </table>
    <div class="field-row">
      <span class="field-label">Full Name</span>
      <span class="field-value">{{ $mailData['name'] }}</span>
    </div>
    <div class="field-row">
      <span class="field-label">Email Address</span>
      <span class="field-value">{{ $mailData['email'] }}</span>
    </div>
    <div class="field-row">
      <span class="field-label">Date Sent</span>
      <span class="field-value">{{ $submittedAtDisplay }}</span>
    </div>
  </div>

  <div class="section">
    <div class="sec-label">Inquiry Message</div>
    <div class="msg-box">
      <p>{{ $mailData['message'] }}</p>
    </div>
  </div>

  <div class="section">
    <div class="sec-label">Company Details</div>
    <div class="meta-grid">
      <div class="meta-card">
        <div class="meta-card-label">Company Name</div>
        <div class="meta-card-value">{{ $mailData['company'] }}</div>
      </div>
      <div class="meta-card">
        <div class="meta-card-label">Industry</div>
        <div class="meta-card-value">{{ $mailData['industry'] !== '' ? $mailData['industry'] : 'Not specified' }}</div>
      </div>
    </div>
  </div>

  <div class="ftr" style="background:{{ $theme['green_dark'] }};">
    <span class="ftr-text">{{ $theme['brand_name'] }} &middot; Automated Inquiry Notification</span>
    <span class="ftr-ref">#{{ $inquiryRef }}</span>
  </div>
</div>
</body>
</html>
