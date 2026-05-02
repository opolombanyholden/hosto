<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\StructureClaim;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdminWebController
{
    public function users(): View
    {
        $users = User::with('roles')->orderByDesc('created_at')->paginate(30);

        return view('admin.users', compact('users'));
    }

    public function structures(): View
    {
        $structures = Hosto::with('structureTypes', 'city')
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('admin.structures', compact('structures'));
    }

    public function claims(): View
    {
        $claims = StructureClaim::with('user')
            ->orderByRaw("CASE WHEN status IN ('submitted','under_review') THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('admin.claims', compact('claims'));
    }

    public function reviewClaim(Request $request, string $uuid, AuditLogger $audit): RedirectResponse
    {
        $claim = StructureClaim::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'action' => 'required|in:approve,reject,suspend',
            'rejection_reason' => 'required_if:action,reject|nullable|string',
        ]);

        $status = match ((string) $data['action']) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'suspend' => 'suspended',
            default => 'submitted',
        };

        $claim->update([
            'status' => $status,
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'reviewed_by' => $request->user()->uuid,
            'reviewed_at' => now(),
        ]);

        // If approved and linked to an existing hosto, mark it as verified.
        if ($status === 'approved' && $claim->hosto_id) {
            Hosto::where('id', $claim->hosto_id)->update([
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by' => $request->user()->uuid,
            ]);
        }

        $audit->record(AuditLogger::ACTION_UPDATE, 'structure_claim', $claim->uuid, [
            'action' => $data['action'],
        ]);

        $messages = ['approve' => 'Demande approuvee.', 'reject' => 'Demande rejetee.', 'suspend' => 'Demande suspendue.'];

        return redirect('/admin/demandes')->with('success', $messages[$data['action']]);
    }

    /**
     * Show structure config page (featured sections).
     */
    public function structureConfig(string $uuid): View
    {
        $structure = Hosto::where('uuid', $uuid)->with('structureTypes')->firstOrFail();
        $availableSections = Hosto::availableFeaturedSections();

        return view('admin.structure-config', compact('structure', 'availableSections'));
    }

    /**
     * Update featured sections for a structure.
     */
    public function updateFeaturedSections(Request $request, string $uuid, AuditLogger $audit): JsonResponse
    {
        $structure = Hosto::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'featured_sections' => 'present|array',
            'featured_sections.*' => 'string|max:50',
        ]);

        $structure->update(['featured_sections' => $data['featured_sections'] ?: null]);

        $audit->record(AuditLogger::ACTION_UPDATE, 'hosto', $structure->uuid, [
            'section' => 'featured_sections',
            'values' => $data['featured_sections'],
        ]);

        return response()->json(['data' => ['message' => 'Sections mises en avant mises a jour.']]);
    }
}
