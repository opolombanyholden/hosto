<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStaffStructure;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Lab\Models\LabResult;
use App\Modules\Lab\Models\LabResultItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Laboratory space: received -> sample_collected -> completed -> transmitted.
 *
 * A result reaches the patient and the prescriber only once validated, and
 * can no longer be changed afterwards.
 */
final class LabWebController
{
    use ResolvesStaffStructure;

    public function index(Request $request): View
    {
        $laboratory = $this->staffStructure($request, 'lab_tech');

        return view('pro.laboratoire.index', [
            'laboratory' => $laboratory,
            'analyses' => LabResult::where('hosto_id', $laboratory->id)
                ->where('status', '!=', 'transmitted')
                ->with(['patient', 'examRequest'])
                ->orderBy('created_at')
                ->get(),
            'recent' => LabResult::where('hosto_id', $laboratory->id)
                ->where('status', 'transmitted')
                ->with(['patient', 'examRequest'])
                ->orderByDesc('validated_at')
                ->limit(10)
                ->get(),
        ]);
    }

    public function show(Request $request, string $uuid): View
    {
        $laboratory = $this->staffStructure($request, 'lab_tech');

        return view('pro.laboratoire.analysis', [
            'analysis' => $this->analysis($laboratory, $uuid)->load(['patient', 'examRequest.practitioner', 'items']),
        ]);
    }

    public function collectSample(Request $request, string $uuid, AuditLogger $audit): RedirectResponse
    {
        $analysis = $this->analysis($this->staffStructure($request, 'lab_tech'), $uuid);

        if ($analysis->status !== 'received') {
            return $this->back($analysis)->with('error', 'Le prélèvement est déjà enregistré.');
        }

        $analysis->update([
            'status' => 'sample_collected',
            'sample_collected_at' => now(),
            'performed_by_id' => $request->user()->id,
        ]);
        $audit->record('lab.sample.collected', 'lab_result', $analysis->uuid);

        return $this->back($analysis)->with('success', 'Prélèvement enregistré.');
    }

    public function saveResults(Request $request, string $uuid, AuditLogger $audit): RedirectResponse
    {
        $analysis = $this->analysis($this->staffStructure($request, 'lab_tech'), $uuid);

        // The form offers spare rows: those left blank are not results.
        $request->merge(['items' => array_values(array_filter(
            (array) $request->input('items', []),
            fn ($item) => filled($item['test_name'] ?? null) || filled($item['value'] ?? null),
        ))]);

        $data = $request->validate([
            'conclusion' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1|max:50',
            'items.*.test_name' => 'required|string|max:255',
            'items.*.value' => 'required|string|max:255',
            'items.*.unit' => 'nullable|string|max:30',
            'items.*.reference_range' => 'nullable|string|max:255',
            'items.*.flag' => 'nullable|in:N,H,L,C',
        ]);

        if (! in_array($analysis->status, ['sample_collected', 'completed'], true)) {
            return $this->back($analysis)->with('error', 'Ces résultats ne peuvent pas être modifiés.');
        }

        DB::transaction(function () use ($analysis, $data): void {
            $analysis->items()->delete();

            foreach (array_values($data['items']) as $index => $item) {
                LabResultItem::create($item + ['lab_result_id' => $analysis->id, 'display_order' => $index]);
            }

            $analysis->update([
                'status' => 'completed',
                'completed_at' => now(),
                'conclusion' => $data['conclusion'] ?? null,
            ]);
        });
        $audit->record('lab.results.saved', 'lab_result', $analysis->uuid);

        return $this->back($analysis)->with('success', 'Résultats enregistrés. Ils restent à valider.');
    }

    public function validateResults(Request $request, string $uuid, AuditLogger $audit): RedirectResponse
    {
        $analysis = $this->analysis($this->staffStructure($request, 'lab_tech'), $uuid);

        if ($analysis->status !== 'completed' || $analysis->items()->doesntExist()) {
            return $this->back($analysis)->with('error', 'Saisissez les résultats avant de valider.');
        }

        DB::transaction(function () use ($analysis, $request): void {
            $analysis->update([
                'status' => 'transmitted',
                'validated_at' => now(),
                'validated_by' => $request->user()->uuid,
            ]);

            $analysis->examRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
                'results' => $analysis->conclusion,
            ]);
        });
        $audit->record('lab.results.validated', 'lab_result', $analysis->uuid);

        return $this->back($analysis)->with('success', 'Résultats validés et transmis.');
    }

    private function analysis(Hosto $laboratory, string $uuid): LabResult
    {
        return LabResult::where('hosto_id', $laboratory->id)->where('uuid', $uuid)->firstOrFail();
    }

    private function back(LabResult $analysis): RedirectResponse
    {
        return redirect("/pro/laboratoire/analyses/{$analysis->uuid}");
    }
}
