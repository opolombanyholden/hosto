<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires PIN verification to access the profile completion page
 * if the user has already set a PIN.
 *
 * First-time visitors (no PIN set) are allowed through so they can
 * create their PIN. PIN verification is stored in session (15 min TTL).
 */
final class EnsureProfilePin
{
    private const SESSION_KEY = 'profile_pin_verified_at';

    private const TTL_SECONDS = 900; // 15 minutes

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect('/compte/connexion');
        }

        // No PIN set yet → allow through (user needs to create it).
        if (! $user->hasMedicalPin()) {
            return $next($request);
        }

        // Check session for valid PIN verification.
        $verifiedAt = session(self::SESSION_KEY);

        if ($verifiedAt === null || (now()->timestamp - $verifiedAt) > self::TTL_SECONDS) {
            session()->forget(self::SESSION_KEY);

            return $request->expectsJson()
                ? response()->json(['error' => ['code' => 'PIN_REQUIRED', 'message' => 'Veuillez saisir votre PIN pour acceder a votre profil.']], 403)
                : redirect()->route('compte.profile-pin');
        }

        return $next($request);
    }
}
