<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Services\ImpersonationService;
use App\Modules\Core\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Second factor for the spaces listed in hosto.security.two_factor_required.
 *
 * Enrolment is mandatory there, and the code must be given in the current
 * session: a session reopened by the "remember me" cookie is challenged again.
 *
 * Usage: ->middleware('2fa:pro')
 */
final class EnsureTwoFactor
{
    public const SESSION_KEY = 'two_factor_passed_for';

    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly ImpersonationService $impersonation,
    ) {}

    public function handle(Request $request, Closure $next, string $environment): Response
    {
        $user = $request->user();

        if ($user === null
            || ! in_array($environment, (array) config('hosto.security.two_factor_required', []), true)
            || $this->impersonation->isImpersonating()) {
            return $next($request);
        }

        if (! $this->twoFactor->isEnabled($user)) {
            $request->session()->put('2fa_return_env', $environment);

            return $request->expectsJson()
                ? response()->json(['error' => ['code' => 'TWO_FACTOR_REQUIRED', 'message' => 'Activez la double authentification pour acceder a cet espace.']], 403)
                : redirect()->guest(route('2fa.setup'))
                    ->with('warning', 'La double authentification est obligatoire pour acceder a cet espace.');
        }

        if ($request->session()->get(self::SESSION_KEY) !== $user->id) {
            Auth::guard('web')->logout();
            $request->session()->put([
                '2fa_user_id' => $user->id,
                '2fa_environment' => $environment,
                '2fa_remember' => false,
                'url.intended' => $request->fullUrl(),
            ]);

            return $request->expectsJson()
                ? response()->json(['error' => ['code' => 'TWO_FACTOR_CHALLENGE', 'message' => 'Saisissez votre code de double authentification.']], 403)
                : redirect()->route('2fa.challenge');
        }

        return $next($request);
    }
}
