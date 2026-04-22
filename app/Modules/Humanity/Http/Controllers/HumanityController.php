<?php

declare(strict_types=1);

namespace App\Modules\Humanity\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class HumanityController
{
    public function status(): JsonResponse
    {
        return response()->json([
            'data' => ['module' => 'humanity', 'status' => 'active'],
        ]);
    }
}
