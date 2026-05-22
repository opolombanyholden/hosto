<?php

declare(strict_types=1);

namespace App\Modules\EVax\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pro-side endpoints for managing vaccination records.
 * Implementation lands across T10.t11 (search), T10.t12 (store), T10.t13 (resolve-qr), T10.t14 (form view).
 */
final class EVaxProController
{
    public function searchPatient(Request $request): JsonResponse
    {
        abort(501, 'Not implemented yet (T10.t11)');
    }

    public function resolvePatientFromQr(Request $request): JsonResponse
    {
        abort(501, 'Not implemented yet (T10.t13)');
    }

    public function showAddForm(Request $request): mixed
    {
        abort(501, 'Not implemented yet (T10.t14)');
    }

    public function storeVaccination(Request $request): JsonResponse
    {
        abort(501, 'Not implemented yet (T10.t12)');
    }
}
