<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserEmailIsApproved
{
    /**
     * This private family deployment intentionally uses one shared notebook:
     * David and Dad share all records, with access limited by approved emails.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedEmails = config('opportunity.allowed_emails', []);

        if ($allowedEmails === []) {
            return $next($request);
        }

        $userEmail = strtolower((string) $request->user()?->email);

        if (in_array($userEmail, $allowedEmails, true)) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        abort(403, 'This account is not approved for this private family notebook.');
    }
}
