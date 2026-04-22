<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Annuaire\Models\PractitionerPublication;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Practitioner profile management (visibility, publications).
 *
 * Used from the pro environment.
 */
final class PractitionerProfileController
{
    // ---------------------------------------------------------------
    // Visibility settings
    // ---------------------------------------------------------------

    public function visibilityPage(Request $request): View
    {
        $practitioner = $this->getPractitioner($request);

        return view('pro.visibility', compact('practitioner'));
    }

    public function updateVisibility(Request $request, AuditLogger $audit): JsonResponse
    {
        $practitioner = $this->getPractitioner($request);

        $data = $request->validate([
            'visibility_settings' => 'required|array',
            'visibility_settings.phone' => 'boolean',
            'visibility_settings.email' => 'boolean',
            'visibility_settings.bio' => 'boolean',
            'visibility_settings.languages' => 'boolean',
            'visibility_settings.registration_number' => 'boolean',
            'visibility_settings.fees' => 'boolean',
            'visibility_settings.photo' => 'boolean',
        ]);

        $practitioner->update(['visibility_settings' => $data['visibility_settings']]);

        $audit->record(AuditLogger::ACTION_UPDATE, 'practitioner', $practitioner->uuid, [
            'section' => 'visibility_settings',
        ]);

        return response()->json(['data' => ['message' => 'Parametres de visibilite mis a jour.']]);
    }

    public function updateServices(Request $request, AuditLogger $audit): JsonResponse
    {
        $practitioner = $this->getPractitioner($request);

        $data = $request->validate([
            'offered_services' => 'required|array',
            'offered_services.appointment' => 'boolean',
            'offered_services.teleconsultation' => 'boolean',
            'offered_services.chat' => 'boolean',
        ]);

        $practitioner->update(['offered_services' => $data['offered_services']]);

        $audit->record(AuditLogger::ACTION_UPDATE, 'practitioner', $practitioner->uuid, [
            'section' => 'offered_services',
        ]);

        return response()->json(['data' => ['message' => 'Services mis a jour.']]);
    }

    // ---------------------------------------------------------------
    // Publications CRUD
    // ---------------------------------------------------------------

    public function publicationsPage(Request $request): View
    {
        $practitioner = $this->getPractitioner($request);
        $publications = $practitioner->publications()->withCount(['likes', 'comments'])->paginate(20);

        return view('pro.publications', compact('practitioner', 'publications'));
    }

    public function storePublication(Request $request, AuditLogger $audit): JsonResponse
    {
        $practitioner = $this->getPractitioner($request);

        $data = $request->validate([
            'type' => 'required|in:activity,research,tip,video',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:5000',
            'video_url' => 'nullable|url|max:500',
            'allow_comments' => 'nullable|boolean',
        ]);

        $publication = PractitionerPublication::create([
            'practitioner_id' => $practitioner->id,
            'type' => $data['type'],
            'title' => $data['title'] ?? null,
            'content' => $data['content'],
            'video_url' => $data['video_url'] ?? null,
            'allow_comments' => (bool) ($data['allow_comments'] ?? true),
            'is_published' => true,
            'published_at' => now(),
        ]);

        $audit->record(AuditLogger::ACTION_CREATE, 'publication', $publication->uuid, [
            'practitioner' => $practitioner->uuid,
            'type' => $data['type'],
        ]);

        return response()->json(['data' => ['message' => 'Publication creee.', 'uuid' => $publication->uuid]], 201);
    }

    public function updatePublication(Request $request, string $uuid, AuditLogger $audit): JsonResponse
    {
        $practitioner = $this->getPractitioner($request);
        $publication = PractitionerPublication::where('practitioner_id', $practitioner->id)
            ->whereUuid($uuid)->firstOrFail();

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:5000',
            'video_url' => 'nullable|url|max:500',
            'allow_comments' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
        ]);

        $publication->update([
            'title' => $data['title'] ?? $publication->title,
            'content' => $data['content'],
            'video_url' => $data['video_url'] ?? $publication->video_url,
            'allow_comments' => (bool) ($data['allow_comments'] ?? $publication->allow_comments),
            'is_published' => (bool) ($data['is_published'] ?? $publication->is_published),
        ]);

        $audit->record(AuditLogger::ACTION_UPDATE, 'publication', $publication->uuid);

        return response()->json(['data' => ['message' => 'Publication modifiee.']]);
    }

    public function deletePublication(Request $request, string $uuid, AuditLogger $audit): JsonResponse
    {
        $practitioner = $this->getPractitioner($request);
        $publication = PractitionerPublication::where('practitioner_id', $practitioner->id)
            ->whereUuid($uuid)->firstOrFail();

        $publication->delete();

        $audit->record(AuditLogger::ACTION_DELETE, 'publication', $publication->uuid);

        return response()->json(['data' => ['message' => 'Publication supprimee.']]);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function getPractitioner(Request $request): Practitioner
    {
        return Practitioner::where('user_id', $request->user()->id)->firstOrFail();
    }
}
