<?php
declare(strict_types=1);

namespace App\Modules\EVax\Http\Controllers;

use App\Modules\EVax\Models\Dependent;
use App\Modules\EVax\Models\VaccinationRecord;
use App\Modules\EVax\Services\CarnetQrService;
use App\Modules\EVax\Services\VaccinationCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class EVaxPatientController
{
    public function myCarnet(Request $request, CarnetQrService $qr, VaccinationCatalogService $catalog): View
    {
        $user = $request->user();
        $records = VaccinationRecord::where('patient_id', $user->id)
            ->orderBy('administered_at', 'desc')
            ->with(['vaccine', 'hosto', 'administeredBy'])
            ->get();

        $schedule = $user->date_of_birth ? $catalog->pevSchedule($user->date_of_birth) : [];

        return view('evax::patient.carnet', [
            'subject' => $user,
            'subjectType' => 'user',
            'records' => $records,
            'qrSvg' => $qr->verificationQrSvg($user),
            'verifyUrl' => $qr->verificationUrl($user),
            'pdfUrl' => url('/compte/carnet-vaccination/me/pdf'),
            'pevSchedule' => $schedule,
            'dependents' => Dependent::where('user_id', $user->id)->get(),
        ]);
    }

    public function dependentsIndex(Request $request): View
    {
        $user = $request->user();
        $dependents = Dependent::where('user_id', $user->id)->orderBy('first_name')->get();
        return view('evax::patient.dependents', ['dependents' => $dependents]);
    }

    public function storeDependent(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'required|date|before_or_equal:today',
            'gender' => 'nullable|in:male,female',
            'nip' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:500',
        ]);
        $data['user_id'] = $user->id;
        Dependent::create($data);
        return redirect()->route('evax.patient.dependents.index')->with('success', 'Dependant cree.');
    }

    public function updateDependent(Request $request, string $uuid): RedirectResponse
    {
        $dep = Dependent::where('uuid', $uuid)->firstOrFail();
        abort_unless($dep->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'required|date|before_or_equal:today',
            'gender' => 'nullable|in:male,female',
            'nip' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:500',
        ]);
        $dep->update($data);
        return redirect()->route('evax.patient.dependents.index')->with('success', 'Dependant mis a jour.');
    }

    public function destroyDependent(Request $request, string $uuid): RedirectResponse
    {
        $dep = Dependent::where('uuid', $uuid)->firstOrFail();
        abort_unless($dep->user_id === $request->user()->id, 403);
        $dep->delete();
        return redirect()->route('evax.patient.dependents.index')->with('success', 'Dependant supprime.');
    }

    public function dependentCarnet(Request $request, string $uuid, CarnetQrService $qr, VaccinationCatalogService $catalog): View
    {
        $dep = Dependent::where('uuid', $uuid)->firstOrFail();
        abort_unless($dep->user_id === $request->user()->id, 403);

        $records = VaccinationRecord::where('dependent_id', $dep->id)
            ->orderBy('administered_at', 'desc')
            ->with(['vaccine', 'hosto', 'administeredBy'])
            ->get();

        return view('evax::patient.carnet', [
            'subject' => $dep,
            'subjectType' => 'dependent',
            'records' => $records,
            'qrSvg' => $qr->verificationQrSvg($dep),
            'verifyUrl' => $qr->verificationUrl($dep),
            'pdfUrl' => url('/compte/carnet-vaccination/dep-'.$dep->uuid.'/pdf'),
            'pevSchedule' => $catalog->pevSchedule($dep->date_of_birth),
            'dependents' => collect(),
        ]);
    }

    public function downloadPdf(Request $request, string $target): Response
    {
        // Implemented in T10.t18 (CarnetPdfService).
        abort(501, 'Not implemented yet (T10.t18)');
    }
}
