<?php
declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        if (! $user->can($permission)) {
            abort(403, "Permission requise : {$permission}");
        }
        return $next($request);
    }
}
