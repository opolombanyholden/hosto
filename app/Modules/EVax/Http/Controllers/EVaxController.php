<?php

declare(strict_types=1);

namespace App\Modules\EVax\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class EVaxController
{
    public function status(): JsonResponse
    {
        return response()->json([
            'data' => ['module' => 'evax', 'status' => 'active'],
        ]);
    }
}
