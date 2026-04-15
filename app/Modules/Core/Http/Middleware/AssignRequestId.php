<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * AssignRequestId.
 *
 * Attaches a unique UUID to every incoming request and echoes it back
 * in the "X-Request-Id" response header.
 *
 * Used to correlate logs, audit entries and error reports.
 */
final class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id');

        if (! $requestId || ! Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid7();
        }

        $request->attributes->set('request_id', $requestId);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
