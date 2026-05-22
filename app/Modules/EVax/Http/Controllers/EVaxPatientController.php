<?php

declare(strict_types=1);

namespace App\Modules\EVax\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Patient-side endpoints for viewing and printing their own carnet de vaccination.
 * Implementation lands across T10.t16 (myCarnet), T10.t17 (dependents CRUD), T10.t18 (PDF).
 */
final class EVaxPatientController
{
    public function myCarnet(Request $request): mixed
    {
        abort(501, 'Not implemented yet (T10.t16)');
    }

    public function dependentsIndex(Request $request): mixed
    {
        abort(501, 'Not implemented yet (T10.t17)');
    }

    public function storeDependent(Request $request): RedirectResponse
    {
        abort(501, 'Not implemented yet (T10.t17)');
    }

    public function updateDependent(Request $request, string $uuid): RedirectResponse
    {
        abort(501, 'Not implemented yet (T10.t17)');
    }

    public function destroyDependent(Request $request, string $uuid): RedirectResponse
    {
        abort(501, 'Not implemented yet (T10.t17)');
    }

    public function dependentCarnet(Request $request, string $uuid): mixed
    {
        abort(501, 'Not implemented yet (T10.t17)');
    }

    public function downloadPdf(Request $request, string $target): Response
    {
        abort(501, 'Not implemented yet (T10.t18)');
    }
}
