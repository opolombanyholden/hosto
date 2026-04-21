<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\HostoEvaluation;
use App\Modules\Annuaire\Models\StructureClaim;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClaimsWebController
{
    // ---------------------------------------------------------------
    // Structure claims (pro environment)
    // ---------------------------------------------------------------

    public function claimForm(): View
    {
        return view('pro.claim-form');
    }

    public function submitClaim(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'structure_name' => 'required|string|max:255',
            'structure_type' => 'nullable|string|max:50',
            'structure_city' => 'nullable|string|max:255',
            'structure_address' => 'nullable|string|max:255',
            'structure_phone' => 'nullable|string|max:30',
            'representative_name' => 'required|string|max:255',
            'representative_role' => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
        ]);

        $claim = StructureClaim::create([
            ...$data,
            'user_id' => $request->user()->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $audit->record(AuditLogger::ACTION_CREATE, 'structure_claim', $claim->uuid);

        return redirect('/pro')->with('success', 'Demande d\'enregistrement soumise. Nos equipes examineront votre dossier.');
    }

    public function myClaims(Request $request): View
    {
        $claims = StructureClaim::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return view('pro.my-claims', compact('claims'));
    }

    // ---------------------------------------------------------------
    // Private evaluations (patient, partner structures only)
    // ---------------------------------------------------------------

    public function submitEvaluation(Request $request, string $uuid, AuditLogger $audit): JsonResponse
    {
        $hosto = Hosto::where('uuid', $uuid)->where('is_partner', true)->firstOrFail();

        $data = $request->validate([
            'score_accueil' => 'nullable|integer|between:1,5',
            'score_proprete' => 'nullable|integer|between:1,5',
            'score_competence' => 'nullable|integer|between:1,5',
            'score_delai' => 'nullable|integer|between:1,5',
            'score_global' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $existing = HostoEvaluation::where('user_id', $request->user()->id)->where('hosto_id', $hosto->id)->first();
        if ($existing) {
            return response()->json(['error' => ['code' => 'ALREADY_EVALUATED', 'message' => 'Vous avez deja evalue cette structure.']], 409);
        }

        $eval = HostoEvaluation::create([...$data, 'user_id' => $request->user()->id, 'hosto_id' => $hosto->id]);

        $audit->record(AuditLogger::ACTION_CREATE, 'evaluation', $eval->uuid, ['hosto' => $hosto->uuid]);

        return response()->json(['data' => ['message' => 'Merci pour votre evaluation. Elle est transmise au responsable de la structure.']], 201);
    }
}
