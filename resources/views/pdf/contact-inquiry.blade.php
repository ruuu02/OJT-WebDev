<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Inquiry #{{ $mailData['submission_id'] }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; color: #1a2e14; font-size: 12px; margin: 24px; }
    .header { border-bottom: 2px solid #d4e6c8; padding-bottom: 10px; margin-bottom: 16px; }
    .title { font-size: 22px; font-weight: 700; margin: 0; color: #2d6a1f; }
    .sub { margin-top: 4px; color: #5a7a50; font-size: 11px; }
    .section { margin-bottom: 14px; }
    .section-title { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #2d6a1f; margin-bottom: 8px; }
    .box { border: 1px solid #d4e6c8; border-radius: 4px; padding: 10px; background: #f5f9f2; }
    .row { margin-bottom: 5px; }
    .label { font-weight: 700; }
    .message { white-space: pre-line; line-height: 1.65; }
    .footer { margin-top: 18px; padding-top: 10px; border-top: 1px solid #d4e6c8; color: #6b7f60; font-size: 10px; }
  </style>
</head>
<body>
  <div class="header">
    <p class="title">New Message Inquiry</p>
    <p class="sub">Ref #INQ-{{ $mailData['submission_id'] }} | {{ $mailData['submitted_at'] }}</p>
  </div>

  <div class="section">
    <div class="section-title">Sender Information</div>
    <div class="box">
      <div class="row"><span class="label">Name:</span> {{ $mailData['name'] }}</div>
      <div class="row"><span class="label">Email:</span> {{ $mailData['email'] }}</div>
      <div class="row"><span class="label">Date Sent:</span> {{ $mailData['submitted_at'] }}</div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Company Details</div>
    <div class="box">
      <div class="row"><span class="label">Company Name:</span> {{ $mailData['company'] }}</div>
      <div class="row"><span class="label">Industry:</span> {{ $mailData['industry'] !== '' ? $mailData['industry'] : 'Not specified' }}</div>
      <div class="row"><span class="label">Page:</span> {{ $mailData['page_title'] }} ({{ $mailData['page_slug'] }})</div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Inquiry Message</div>
    <div class="box">
      <div class="message">{{ $mailData['message'] }}</div>
    </div>
  </div>

  <div class="footer">
    Ultrafood Distributors Inc. automated inquiry document
  </div>
</body>
</html>
