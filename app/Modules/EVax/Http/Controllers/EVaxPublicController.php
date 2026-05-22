<?php

declare(strict_types=1);

namespace App\Modules\EVax\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public (no-auth) endpoints for QR verification and identity resolution.
 * Implementation lands across T10.t19 (verify + identity) and T10.t20 (jwks).
 */
final class EVaxPublicController
{
    public function verify(Request $request, string $secret): mixed
    {
        abort(501, 'Not implemented yet (T10.t19)');
    }

    public function identity(string $secret): mixed
    {
        abort(501, 'Not implemented yet (T10.t19)');
    }

    public function jwks(): JsonResponse
    {
        abort(501, 'Not implemented yet (T10.t19)');
    }
}
