<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureEnvironment.
 *
 * Verifies that the authenticated user has at least one role belonging
 * to the required environment (admin, pro, usager).
 *
 * Usage in routes:
 *   ->middleware('env:admin')
 *   ->middleware('env:pro')
 *   ->middleware('env:usager')
 *
 * @see docs/adr/0011-trois-environnements-authentification.md
 */
final class EnsureEnvironment
{
    public function handle(Request $request, Closure $next, string $environment): Response
    {
        $user = $request->user();

        if ($user === null) {
            $loginRoutes = [
                'admin' => '/admin/connexion',
                'pro' => '/pro/connexion',
                'usager' => '/compte/connexion',
            ];

            return redirect($loginRoutes[$environment] ?? '/compte/connexion');
        }

        // Eager load roles if not yet loaded.
        if (! $user->relationLoaded('roles')) {
            $user->load('roles');
        }

        if (! $user->canAccessEnvironment($environment)) {
            abort(403, 'Acces non autorise a cet environnement.');
        }

        return $next($request);
    }
}
