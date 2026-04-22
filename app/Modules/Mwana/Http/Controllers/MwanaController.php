<?php

declare(strict_types=1);

namespace App\Modules\Mwana\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class MwanaController
{
    public function status(): JsonResponse
    {
        return response()->json([
            'data' => ['module' => 'mwana', 'status' => 'active'],
        ]);
    }
}
