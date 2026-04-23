<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\Referentiel\Models\ReferenceData;
use App\Modules\Referentiel\Models\Service;
use App\Modules\Referentiel\Models\Specialty;
use App\Modules\Referentiel\Models\StructureType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for all reference data and referentiel entities.
 */
final class AdminReferenceController
{
    // ---------------------------------------------------------------
    // Structure Types
    // ---------------------------------------------------------------

    public function structureTypes(): View
    {
        $items = StructureType::orderBy('display_order')->get();

        return view('admin.crud.structure-types', compact('items'));
    }

    public function storeStructureType(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'slug' => 'required|string|max:50|unique:structure_types,slug',
            'name_fr' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $item = StructureType::create($data);
        $audit->record(AuditLogger::ACTION_CREATE, 'structure_type', $item->uuid);

        return response()->json(['data' => ['message' => 'Type de structure cree.', 'id' => $item->id]], 201);
    }

    public function updateStructureType(Request $request, int $id, AuditLogger $audit): JsonResponse
    {
        $item = StructureType::findOrFail($id);

        $data = $request->validate([
            'name_fr' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $item->update($data);
        $audit->record(AuditLogger::ACTION_UPDATE, 'structure_type', $item->uuid);

        return response()->json(['data' => ['message' => 'Type de structure modifie.']]);
    }

    // ---------------------------------------------------------------
    // Specialties
    // ---------------------------------------------------------------

    public function specialties(): View
    {
        $items = Specialty::whereNull('parent_id')->with('children')->orderBy('display_order')->get();

        return view('admin.crud.specialties', compact('items'));
    }

    public function storeSpecialty(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:specialties,code',
            'name_fr' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:specialties,id',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $item = Specialty::create($data);
        $audit->record(AuditLogger::ACTION_CREATE, 'specialty', $item->uuid);

        return response()->json(['data' => ['message' => 'Specialite creee.', 'id' => $item->id]], 201);
    }

    public function updateSpecialty(Request $request, int $id, AuditLogger $audit): JsonResponse
    {
        $item = Specialty::findOrFail($id);

        $data = $request->validate([
            'name_fr' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:specialties,id',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $item->update($data);
        $audit->record(AuditLogger::ACTION_UPDATE, 'specialty', $item->uuid);

        return response()->json(['data' => ['message' => 'Specialite modifiee.']]);
    }

    // ---------------------------------------------------------------
    // Services
    // ---------------------------------------------------------------

    public function services(): View
    {
        $items = Service::orderBy('category')->orderBy('display_order')->get();

        return view('admin.crud.services', compact('items'));
    }

    public function storeService(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:20|unique:services,code',
            'category' => 'required|in:prestation,soin,examen',
            'name_fr' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $item = Service::create($data);
        $audit->record(AuditLogger::ACTION_CREATE, 'service', $item->uuid);

        return response()->json(['data' => ['message' => 'Service cree.', 'id' => $item->id]], 201);
    }

    public function updateService(Request $request, int $id, AuditLogger $audit): JsonResponse
    {
        $item = Service::findOrFail($id);

        $data = $request->validate([
            'name_fr' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'category' => 'required|in:prestation,soin,examen',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $item->update($data);
        $audit->record(AuditLogger::ACTION_UPDATE, 'service', $item->uuid);

        return response()->json(['data' => ['message' => 'Service modifie.']]);
    }

    // ---------------------------------------------------------------
    // Reference Data (generic enums)
    // ---------------------------------------------------------------

    public function referenceData(string $category): View
    {
        $categories = ReferenceData::categoryLabels();

        if (! isset($categories[$category])) {
            abort(404);
        }

        $items = ReferenceData::where('category', $category)->orderBy('display_order')->orderBy('label_fr')->get();
        $categoryLabel = $categories[$category];

        return view('admin.crud.reference-data', compact('items', 'category', 'categoryLabel', 'categories'));
    }

    public function storeReferenceData(Request $request, string $category, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
            'label_fr' => 'required|string|max:255',
            'label_en' => 'nullable|string|max:255',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $exists = ReferenceData::where('category', $category)->where('code', $data['code'])->exists();
        if ($exists) {
            return response()->json(['error' => ['message' => 'Ce code existe deja dans cette categorie.']], 422);
        }

        ReferenceData::create([...$data, 'category' => $category]);

        $audit->record(AuditLogger::ACTION_CREATE, 'reference_data', null, [
            'category' => $category,
            'code' => $data['code'],
        ]);

        return response()->json(['data' => ['message' => 'Entree creee.']], 201);
    }

    public function updateReferenceData(Request $request, int $id, AuditLogger $audit): JsonResponse
    {
        $item = ReferenceData::findOrFail($id);

        $data = $request->validate([
            'label_fr' => 'required|string|max:255',
            'label_en' => 'nullable|string|max:255',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $item->update($data);

        $audit->record(AuditLogger::ACTION_UPDATE, 'reference_data', null, [
            'category' => $item->category,
            'code' => $item->code,
        ]);

        return response()->json(['data' => ['message' => 'Entree modifiee.']]);
    }

    public function deleteReferenceData(int $id, AuditLogger $audit): JsonResponse
    {
        $item = ReferenceData::findOrFail($id);

        $audit->record(AuditLogger::ACTION_DELETE, 'reference_data', null, [
            'category' => $item->category,
            'code' => $item->code,
        ]);

        $item->delete();

        return response()->json(['data' => ['message' => 'Entree supprimee.']]);
    }
}
