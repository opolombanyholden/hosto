<?php

declare(strict_types=1);

namespace App\Modules\Connect\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class ConnectController
{
    public function status(): JsonResponse
    {
        return response()->json([
            'data' => ['module' => 'connect', 'status' => 'active'],
        ]);
    }
}
