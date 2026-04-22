<?php

declare(strict_types=1);

namespace App\Modules\HostoPlus\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class HostoPlusController
{
    public function status(): JsonResponse
    {
        return response()->json([
            'data' => ['module' => 'hosto-plus', 'status' => 'active'],
        ]);
    }
}
