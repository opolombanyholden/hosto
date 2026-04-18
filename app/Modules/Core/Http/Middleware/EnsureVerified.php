<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureVerified.
 *
 * Checks that the authenticated user has verified their email
 * and phone number. Redirects to a verification page if not.
 *
 * Usage:
 *   ->middleware('verified')           // email + phone
 *   ->middleware('verified:email')     // email only
 *
 * @see docs/adr/0012-verification-compte-workflow.md
 */
final class EnsureVerified
{
    public function handle(Request $request, Closure $next, string $level = 'full'): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect('/compte/connexion');
        }

        if ($user->email_verified_at === null) {
            return redirect()->route('verification.notice')
                ->with('warning', 'Veuillez verifier votre adresse email.');
        }

        if ($level === 'full' && $user->phone_verified_at === null) {
            return redirect()->route('verification.notice')
                ->with('warning', 'Veuillez verifier votre numero de telephone.');
        }

        return $next($request);
    }
}
