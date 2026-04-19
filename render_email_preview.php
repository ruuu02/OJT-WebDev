<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$pdfUrl = Illuminate\Support\Facades\URL::temporarySignedRoute(
  'contact.submission.pdf',
  now()->addDays(30),
  ['submission' => 1024]
);

$html = view('frontend.contact-form-submission-email', [
  'mailData' => [
    'submission_id' => 1024,
    'page_title' => 'Ultrafood',
    'page_slug' => 'ultrafood',
    'company' => 'Acme Foods Corporation',
    'industry' => 'Food & Beverage',
    'name' => 'Maria Santos',
    'email' => 'maria.santos@acmefoods.com',
    'message' => 'Good day. We are interested in your product line for nationwide distribution. Please share your wholesale pricing, MOQ, and lead times.',
    'submitted_at' => 'April 7, 2026 10:30 AM',
    'pdf_download_url' => $pdfUrl,
  ],
])->render();

$file = __DIR__ . '/storage/app/public/contact-email-preview.html';
file_put_contents($file, $html);
echo "Saved: $file\n";
