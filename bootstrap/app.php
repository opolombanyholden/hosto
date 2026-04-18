<?php

declare(strict_types=1);

use App\Modules\Core\Http\Middleware\AssignRequestId;
use App\Modules\Core\Http\Middleware\EnsureEnvironment;
use App\Modules\Core\Http\Middleware\EnsureProValidated;
use App\Modules\Core\Http\Middleware\EnsureVerified;
use App\Modules\Core\Http\Middleware\ForceJsonResponse;
use App\Modules\Core\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignRequestId::class);
        $middleware->append(SecurityHeaders::class);

        $middleware->prependToGroup('api', ForceJsonResponse::class);

        $middleware->alias([
            'env' => EnsureEnvironment::class,
            'verified' => EnsureVerified::class,
            'pro.validated' => EnsureProValidated::class,
        ]);

        // Redirect unauthenticated users based on the URL prefix.
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('admin*')) {
                return '/admin/connexion';
            }
            if ($request->is('pro*')) {
                return '/pro/connexion';
            }

            return '/compte/connexion';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Uniform JSON error envelope on API routes.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = match (true) {
                $e instanceof ValidationException => 422,
                $e instanceof AuthenticationException => 401,
                $e instanceof AuthorizationException => 403,
                $e instanceof NotFoundHttpException => 404,
                $e instanceof MethodNotAllowedHttpException => 405,
                $e instanceof ThrottleRequestsException => 429,
                method_exists($e, 'getStatusCode') => $e->getStatusCode(),
                default => 500,
            };

            $code = match ($status) {
                400 => 'BAD_REQUEST',
                401 => 'UNAUTHENTICATED',
                403 => 'FORBIDDEN',
                404 => 'NOT_FOUND',
                405 => 'METHOD_NOT_ALLOWED',
                422 => 'VALIDATION_FAILED',
                429 => 'RATE_LIMITED',
                default => 'SERVER_ERROR',
            };

            $payload = [
                'error' => [
                    'code' => $code,
                    'message' => $status === 500 && ! config('app.debug')
                        ? 'Une erreur interne est survenue.'
                        : $e->getMessage(),
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ];

            if ($e instanceof ValidationException) {
                $payload['error']['details'] = collect($e->errors())
                    ->flatMap(fn ($messages, $field) => array_map(
                        fn ($msg) => ['field' => $field, 'reason' => $msg],
                        (array) $messages,
                    ))
                    ->values()
                    ->all();
            }

            if (config('app.debug') && $status >= 500) {
                $payload['error']['exception'] = [
                    'class' => $e::class,
                    'file' => $e->getFile().':'.$e->getLine(),
                ];
            }

            return response()->json($payload, $status);
        });
    })->create();
