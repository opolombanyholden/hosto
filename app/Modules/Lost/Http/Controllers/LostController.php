<?php

declare(strict_types=1);

namespace App\Modules\Lost\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class LostController
{
    public function status(): JsonResponse
    {
        return response()->json([
            'data' => ['module' => 'lost', 'status' => 'active'],
        ]);
    }
}
