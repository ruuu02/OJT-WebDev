<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class ContactController extends Controller
{
    private array $tableMap = [
        'phone' => 'contact_numbers',
        'email' => 'email_addresses',
        'link'  => 'links',
    ];

    public function save(Request $request)
    {
        $request->validate([
            'page_id' => 'required|exists:pages,id',
            'type'    => 'required|in:phone,email,link',
            'label'   => 'required|string|max:255',
            'value'   => 'required|string|max:255',
        ]);

        $table = $this->tableMap[$request->type];

        $id = DB::table($table)->insertGetId([
            'page_id'    => $request->page_id,
            'label'      => strip_tags($request->label),
            'value'      => strip_tags($request->value),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'contact' => [
                'id'      => $id,
                'page_id' => (int) $request->page_id,
                'type'    => $request->type,
                'label'   => strip_tags($request->label),
                'value'   => strip_tags($request->value),
            ],
        ]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'type'  => 'required|in:phone,email,link',
            'label' => 'required|string|max:255',
            'value' => 'required|string|max:255',
        ]);

        $table = $this->tableMap[$request->type] ?? null;
        if (!$table) {
            return response()->json(['success' => false, 'message' => 'Invalid type'], 422);
        }

        if (!DB::table($table)->where('id', $id)->exists()) {
            abort(404);
        }

        DB::table($table)->where('id', $id)->update([
            'label'      => strip_tags($request->label),
            'value'      => strip_tags($request->value),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, int $id)
    {
        $type  = $request->query('type');
        $table = $this->tableMap[$type] ?? null;

        if (!$table) {
            return response()->json(['success' => false, 'message' => 'Invalid type'], 422);
        }

        if (!DB::table($table)->where('id', $id)->exists()) {
            abort(404);
        }

        DB::table($table)->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    public function send(Request $request)
    {
        $originalMailConfig = config('mail');
        $data = $request->validate([
            'page_slug' => 'nullable|string|max:100',
            'company'   => 'required|string|max:255',
            'industry'  => 'nullable|string|max:255',
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255',
            'message'   => 'required|string|max:5000',
        ]);

        $pageSlug = trim((string) ($data['page_slug'] ?? 'ultrafood')) ?: 'ultrafood';
        $page = DB::table('pages')->where('slug', $pageSlug)->first();
        $pageId = $page?->id ? (int) $page->id : null;

        $sendToEmail = $this->resolveSendToEmail($pageId);
        $runtimeMailer = $this->resolveAndApplyRuntimeMailer($pageId);
        $activeMailer = $runtimeMailer ?: (string) config('mail.default', 'log');
        $isNonDeliveringMailer = in_array($activeMailer, ['log', 'array'], true);

        $submissionId = DB::table('contact_form_submissions')->insertGetId([
            'page_id'       => $pageId,
            'page_slug'     => $pageSlug,
            'company'       => strip_tags($data['company']),
            'industry'      => isset($data['industry']) ? strip_tags((string) $data['industry']) : null,
            'name'          => strip_tags($data['name']),
            'email'         => strip_tags($data['email']),
            'message'       => strip_tags($data['message']),
            'send_to_email' => $sendToEmail,
            'mail_status'   => 'skipped',
            'ip_address'    => $request->ip(),
            'user_agent'    => (string) $request->userAgent(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $mailStatus = 'skipped';
        $mailError = null;
        $mailedAt = null;

        if ($sendToEmail && !$isNonDeliveringMailer) {
            try {
                $subject = 'New Business Inquiry: ' . (string) $data['company'];
                $submittedAt = Carbon::now(config('app.timezone', 'Asia/Manila'));
                $inquiryRef = 'INQ-' . str_pad((string) $submissionId, 6, '0', STR_PAD_LEFT);
                $pdfData = [
                    'submission_id' => $submissionId,
                    'inquiry_ref'   => $inquiryRef,
                    'page_title'    => (string) ($page->title ?? 'Ultrafood'),
                    'page_slug'     => $pageSlug,
                    'company'       => (string) $data['company'],
                    'industry'      => (string) ($data['industry'] ?? ''),
                    'name'          => (string) $data['name'],
                    'email'         => (string) $data['email'],
                    'message'       => (string) $data['message'],
                    'submitted_at'  => $submittedAt->format('F j, Y h:i:s A'),
                    'submitted_at_tz' => 'UTC' . $submittedAt->format('P'),
                ];

                $pdfDownloadUrl = URL::temporarySignedRoute(
                    'contact.submission.pdf',
                    now()->addDays(30),
                    ['submission' => $submissionId]
                );

                // Prefer the current request host in generated links so Gmail recipients don't get localhost URLs.
                $reqHost = (string) $request->getHost();
                if ($reqHost !== '' && !in_array($reqHost, ['localhost', '127.0.0.1'], true)) {
                    $pdfDownloadUrl = preg_replace(
                        '#^https?://[^/]+#i',
                        rtrim($request->getSchemeAndHttpHost(), '/'),
                        $pdfDownloadUrl
                    ) ?: $pdfDownloadUrl;
                }

                $mailData = [
                    ...$pdfData,
                    'pdf_download_url' => $pdfDownloadUrl,
                ];

                $sendWithMailer = function (string $mailerName) use ($sendToEmail, $subject, $data, $mailData): void {
                    Mail::mailer($mailerName)->send('frontend.contact-form-submission-email', ['mailData' => $mailData], function ($message) use ($sendToEmail, $subject, $data) {
                        $message->to($sendToEmail)->subject($subject);
                        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                            $message->replyTo($data['email'], $data['name']);
                        }
                    });
                };

                $sendWithMailer($activeMailer);
                $mailStatus = 'sent';
                $mailedAt = now();
            } catch (Throwable $e) {
                // If runtime SMTP from CMS fails, attempt a one-time fallback to the app's original mail config.
                if ($runtimeMailer) {
                    try {
                        config(['mail' => $originalMailConfig]);
                        app('mail.manager')->forgetMailers();
                        $fallbackMailer = (string) ($originalMailConfig['default'] ?? 'smtp');
                        if (!in_array($fallbackMailer, ['log', 'array'], true)) {
                            Mail::mailer($fallbackMailer)->send('frontend.contact-form-submission-email', ['mailData' => $mailData], function ($message) use ($sendToEmail, $subject, $data) {
                                $message->to($sendToEmail)->subject($subject);
                                if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                                    $message->replyTo($data['email'], $data['name']);
                                }
                            });
                            $mailStatus = 'sent';
                            $mailedAt = now();
                            $mailError = null;
                        } else {
                            throw $e;
                        }
                    } catch (Throwable $fallbackException) {
                        $mailStatus = 'failed';
                        $mailError = substr($fallbackException->getMessage(), 0, 1000);
                        try {
                            Log::error('Contact form email send failed after fallback', [
                                'submission_id' => $submissionId,
                                'page_slug' => $pageSlug,
                                'runtime_error' => $e->getMessage(),
                                'fallback_error' => $fallbackException->getMessage(),
                            ]);
                        } catch (Throwable) {
                            // Keep request successful even if local logging has filesystem issues.
                        }
                    }
                } else {
                    $mailStatus = 'failed';
                    $mailError = substr($e->getMessage(), 0, 1000);
                    try {
                        Log::error('Contact form email send failed', [
                            'submission_id' => $submissionId,
                            'page_slug' => $pageSlug,
                            'error' => $e->getMessage(),
                        ]);
                    } catch (Throwable) {
                        // Keep request successful even if local logging has filesystem issues.
                    }
                }
            }
        } elseif ($sendToEmail && $isNonDeliveringMailer) {
            $mailStatus = 'skipped';
            $mailError = "Email not delivered because active mailer is '{$activeMailer}'. Configure SMTP in Visual Editor Contact Form fields.";
        }

        DB::table('contact_form_submissions')->where('id', $submissionId)->update([
            'mail_status' => $mailStatus,
            'mailed_at' => $mailedAt,
            'mail_error' => $mailError,
            'updated_at' => now(),
        ]);

        $responseMessage = "Thank you! We'll be in touch soon.";
        if ($mailStatus === 'skipped' && $mailError) {
            $responseMessage = 'Message saved, but email delivery is not configured yet. Please set SMTP fields in Visual Editor.';
        } elseif ($mailStatus === 'failed') {
            $responseMessage = 'Message saved, but email delivery failed. Please verify SMTP settings.';
        }

        return response()->json([
            'success' => true,
            'message' => $responseMessage,
            'submission_id' => $submissionId,
            'mail_status' => $mailStatus,
        ]);
    }

    public function submissionPdf(Request $request, int $submission)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $row = DB::table('contact_form_submissions')->where('id', $submission)->first();
        if (! $row) {
            abort(404);
        }

        $pdfData = [
            'submission_id' => (int) $row->id,
            'page_title'    => (string) ($row->page_slug ?: 'Inquiry'),
            'page_slug'     => (string) ($row->page_slug ?: ''),
            'company'       => (string) ($row->company ?: ''),
            'industry'      => (string) ($row->industry ?: ''),
            'name'          => (string) ($row->name ?: ''),
            'email'         => (string) ($row->email ?: ''),
            'message'       => (string) ($row->message ?: ''),
            'submitted_at'  => optional($row->created_at ? Carbon::parse($row->created_at) : null)?->format('F j, Y g:i A')
                ?? now()->format('F j, Y g:i A'),
        ];

        $safeCompany = Str::slug($pdfData['company'] ?: 'submission');
        $filename = "inquiry-{$pdfData['submission_id']}-{$safeCompany}.pdf";

        $facadeClass = \Barryvdh\DomPDF\Facade\Pdf::class;
        if (class_exists($facadeClass)) {
            $pdf = $facadeClass::loadView('pdf.contact-inquiry', ['mailData' => $pdfData])->setPaper('a4');
            return $pdf->download($filename);
        }

        $txtFilename = str_replace('.pdf', '.txt', $filename);
        $text = $this->buildInquiryTextReport($pdfData);
        return response($text, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $txtFilename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function buildInquiryTextReport(array $data): string
    {
        $lines = [
            'NEW MESSAGE INQUIRY',
            '-------------------',
            'Submission ID: #' . ($data['submission_id'] ?? ''),
            'Page: ' . ($data['page_title'] ?? '') . ' (' . ($data['page_slug'] ?? '') . ')',
            'Date Sent: ' . ($data['submitted_at'] ?? ''),
            '',
            'SENDER INFORMATION',
            'Name: ' . ($data['name'] ?? ''),
            'Email: ' . ($data['email'] ?? ''),
            '',
            'COMPANY DETAILS',
            'Company: ' . ($data['company'] ?? ''),
            'Industry: ' . (($data['industry'] ?? '') !== '' ? $data['industry'] : 'Not specified'),
            '',
            'INQUIRY MESSAGE',
            (string) ($data['message'] ?? ''),
        ];

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function resolveSendToEmail(?int $pageId): ?string
    {
        if ($pageId) {
            $configured = DB::table('text_content')
                ->where('page_id', $pageId)
                ->where('key', 'contact_form_send_to_email')
                ->value('value');
            $configured = $this->normalizeRecipientEmail($configured);
            if ($this->isValidEmail($configured)) {
                return $configured;
            }

            $footer = DB::table('text_content')
                ->where('page_id', $pageId)
                ->where('key', 'ultrafood_footer_email')
                ->value('value');
            $footer = $this->normalizeRecipientEmail($footer);
            if ($this->isValidEmail($footer)) {
                return $footer;
            }

            $firstEmail = DB::table('email_addresses')
                ->where('page_id', $pageId)
                ->orderBy('id')
                ->value('value');
            $firstEmail = $this->normalizeRecipientEmail($firstEmail);
            if ($this->isValidEmail($firstEmail)) {
                return $firstEmail;
            }
        }

        $global = DB::table('text_content')
            ->where('key', 'contact_form_send_to_email')
            ->orderByDesc('id')
            ->value('value');
        $global = $this->normalizeRecipientEmail($global);

        return $this->isValidEmail($global) ? $global : null;
    }

    private function isValidEmail(mixed $value): bool
    {
        return filter_var(trim((string) $value), FILTER_VALIDATE_EMAIL) !== false;
    }

    private function normalizeRecipientEmail(mixed $value): ?string
    {
        $email = trim((string) $value);
        if ($email === '') {
            return null;
        }

        // Common typo from existing seeded defaults.
        $email = preg_replace('/@google\.com$/i', '@gmail.com', $email);

        return strtolower($email);
    }

    private function resolveAndApplyRuntimeMailer(?int $pageId): ?string
    {
        $host = $this->resolveTextContentValue($pageId, 'contact_form_smtp_host');
        $port = $this->resolveTextContentValue($pageId, 'contact_form_smtp_port');
        $encryption = strtolower((string) $this->resolveTextContentValue($pageId, 'contact_form_smtp_encryption'));
        $username = $this->resolveTextContentValue($pageId, 'contact_form_smtp_username');
        $passwordRaw = $this->resolveTextContentValue($pageId, 'contact_form_smtp_password');
        $password = $passwordRaw ? preg_replace('/\s+/', '', $passwordRaw) : null;
        $fromEmail = $this->resolveTextContentValue($pageId, 'contact_form_mail_from_email');
        $fromName = $this->resolveTextContentValue($pageId, 'contact_form_mail_from_name');

        if (!$host || !$username || !$password) {
            return null;
        }

        $smtpPort = (int) ($port ?: 587);
        if ($smtpPort <= 0) {
            $smtpPort = 587;
        }
        if ($encryption === 'none' || $encryption === '') {
            $encryption = null;
        }

        $fromAddress = $this->isValidEmail($fromEmail) ? trim($fromEmail) : trim($username);
        if (!$this->isValidEmail($fromAddress)) {
            return null;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => trim((string) $host),
            'mail.mailers.smtp.port' => $smtpPort,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.mailers.smtp.username' => trim((string) $username),
            'mail.mailers.smtp.password' => (string) $password,
            'mail.from.address' => $fromAddress,
            'mail.from.name' => trim((string) ($fromName ?: 'Ultrafood Contact')),
        ]);

        return 'smtp';
    }

    private function resolveTextContentValue(?int $pageId, string $key): ?string
    {
        if ($pageId) {
            $value = DB::table('text_content')
                ->where('page_id', $pageId)
                ->where('key', $key)
                ->value('value');
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        $global = DB::table('text_content')
            ->where('key', $key)
            ->orderByDesc('id')
            ->value('value');

        return ($global !== null && trim((string) $global) !== '') ? trim((string) $global) : null;
    }

}
