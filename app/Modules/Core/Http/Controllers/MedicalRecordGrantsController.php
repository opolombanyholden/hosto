<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Models\MedicalRecordGrant;
use App\Modules\Core\Services\MedicalRecordGrantService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MedicalRecordGrantsController
{
    public function __construct(private readonly MedicalRecordGrantService $svc) {}

    public function index(Request $request): View
    {
        $grants = MedicalRecordGrant::where('patient_id', $request->user()->id)
            ->with('practitioner')
            ->orderByDesc('granted_at')
            ->get();

        return view('compte.dossier.partages', compact('grants'));
    }

    public function show(Request $request, string $uuid): View
    {
        $grant = MedicalRecordGrant::where('uuid', $uuid)->firstOrFail();
        if ($grant->patient_id !== $request->user()->id) {
            abort(403);
        }
        $logs = $grant->accessLogs()->with('practitionerUser')->limit(50)->get();

        return view('compte.dossier.partage-historique', compact('grant', 'logs'));
    }

    public function revoke(Request $request, string $uuid): RedirectResponse
    {
        $grant = MedicalRecordGrant::where('uuid', $uuid)->firstOrFail();
        if ($grant->patient_id !== $request->user()->id) {
            abort(403);
        }
        $this->svc->revoke($grant, $request->user());

        return back()->with('success', 'Accès révoqué.');
    }
}
