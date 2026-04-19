<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminSessionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        // In local dev using "php artisan serve", if the server process changes,
        // treat it as a server restart and invalidate the old login session.
        if (PHP_SAPI === 'cli-server') {
            $sessionPid = (int) session('admin_server_pid', 0);
            $currentPid = (int) getmypid();

            if ($sessionPid <= 0 || ($currentPid > 0 && $sessionPid !== $currentPid)) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('admin.login');
            }
        }

        return $next($request)
            // Prevent browser caching — back button forces a fresh server request
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0')
            // Prevent clickjacking — admin pages cannot be embedded in iframes
            ->header('X-Frame-Options', 'DENY')
            // Prevent MIME-type sniffing attacks
            ->header('X-Content-Type-Options', 'nosniff')
            // Enable browser's built-in XSS filter
            ->header('X-XSS-Protection', '1; mode=block')
            // Restrict referrer information leakage
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
