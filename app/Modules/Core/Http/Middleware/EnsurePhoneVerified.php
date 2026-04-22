<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to sensitive features (RDV, teleconsultation, medication purchase)
 * if the user has not confirmed their phone number.
 *
 * Returns JSON 403 for AJAX requests, redirect for standard requests.
 */
final class EnsurePhoneVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $request->expectsJson()
                ? response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Connexion requise.']], 401)
                : redirect('/compte/connexion');
        }

        if ($user->phone_verified_at === null) {
            $message = 'Vous devez verifier votre numero de telephone pour utiliser cette fonctionnalite.';

            return $request->expectsJson()
                ? response()->json(['error' => ['code' => 'PHONE_NOT_VERIFIED', 'message' => $message]], 403)
                : redirect()->route('compte.complete-profile')
                    ->with('warning', $message);
        }

        return $next($request);
    }
}
