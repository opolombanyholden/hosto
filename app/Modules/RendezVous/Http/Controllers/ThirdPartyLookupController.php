<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Http\Controllers;

use App\Modules\RendezVous\Services\ThirdPartyResolverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ThirdPartyLookupController
{
    public function __construct(private readonly ThirdPartyResolverService $svc) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $request->user()) {
            abort(401);
        }
        $phone = trim((string) $request->query('phone', ''));
        if ($phone === '') {
            return response()->json(['matched' => false]);
        }
        $normalized = $this->svc->normalizePhone($phone, 'GA');
        if ($normalized === null) {
            return response()->json(['error' => 'invalid_phone'], 400);
        }
        $user = $this->svc->findUserByPhone($normalized);
        if (! $user) {
            return response()->json(['matched' => false]);
        }
        return response()->json([
            'matched' => true,
        ]);
    }
}
