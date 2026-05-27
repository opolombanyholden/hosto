<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Http\Controllers;

use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\AppointmentDocument;
use App\Modules\RendezVous\Services\DocumentUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AppointmentDocumentsController
{
    public function __construct(private readonly DocumentUploadService $svc) {}

    public function store(Request $request, string $appointmentUuid): RedirectResponse
    {
        $apt = Appointment::where('uuid', $appointmentUuid)->firstOrFail();
        $user = $request->user();
        if ($apt->patient_id !== $user->id && $apt->third_party_user_id !== $user->id) {
            abort(403, 'Vous ne pouvez pas uploader pour ce RDV.');
        }
        $validated = $request->validate([
            'file' => 'required|file|max:10240|mimetypes:application/pdf,image/jpeg,image/png,image/heic,image/heif',
            'category' => 'nullable|string|in:ordonnance,examen,autre',
        ]);
        try {
            $this->svc->store($apt, $validated['file'], $user, $validated['category'] ?? null);
        } catch (\DomainException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
        return back()->with('success', 'Document attaché.');
    }

    public function download(Request $request, string $uuid): StreamedResponse
    {
        $doc = AppointmentDocument::where('uuid', $uuid)->firstOrFail();
        return $this->svc->download($doc, $request->user());
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $doc = AppointmentDocument::where('uuid', $uuid)->firstOrFail();
        if ($doc->uploaded_by_id !== $request->user()->id) {
            abort(403, 'Seul l\'uploader peut supprimer.');
        }
        $this->svc->delete($doc, $request->user());
        return back()->with('success', 'Document supprimé.');
    }
}
