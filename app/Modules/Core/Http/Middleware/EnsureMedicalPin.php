<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires the user to have verified their medical PIN
 * before accessing their medical record (DPE).
 *
 * PIN verification is stored in session with a 15-minute TTL.
 * If expired or never set, the user is prompted to enter their PIN.
 */
final class EnsureMedicalPin
{
    private const SESSION_KEY = 'medical_pin_verified_at';

    private const TTL_SECONDS = 900; // 15 minutes

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $request->expectsJson()
                ? response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Connexion requise.']], 401)
                : redirect('/compte/connexion');
        }

        // If user hasn't set a PIN yet, redirect to profile to create one.
        if (! $user->hasMedicalPin()) {
            $message = 'Vous devez definir un PIN medical avant d\'acceder a votre dossier.';

            return $request->expectsJson()
                ? response()->json(['error' => ['code' => 'NO_MEDICAL_PIN', 'message' => $message]], 403)
                : redirect()->route('compte.complete-profile')
                    ->with('warning', $message);
        }

        // Check session for valid PIN verification.
        $verifiedAt = session(self::SESSION_KEY);

        if ($verifiedAt === null || (now()->timestamp - $verifiedAt) > self::TTL_SECONDS) {
            // Clear expired verification.
            session()->forget(self::SESSION_KEY);

            return $request->expectsJson()
                ? response()->json(['error' => ['code' => 'PIN_REQUIRED', 'message' => 'Veuillez saisir votre PIN medical.']], 403)
                : redirect()->route('compte.dossier')->with('pin_required', true);
        }

        return $next($request);
    }
}
