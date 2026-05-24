<?php
declare(strict_types=1);

namespace App\Modules\Annuaire\Http\Controllers\Admin;

use App\Modules\Annuaire\Models\PractitionerCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminPractitionerCategoriesController
{
    public function index(): View
    {
        $categories = PractitionerCategory::withCount('practitioners')
            ->orderBy('display_order')
            ->get();
        return view('admin.practitioner-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.practitioner-categories.form', [
            'category' => new PractitionerCategory(),
            'parents' => PractitionerCategory::whereNull('parent_category_id')->orderBy('name_fr')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:40|unique:practitioner_categories,code',
            'name_fr' => 'required|string|max:120',
            'name_en' => 'nullable|string|max:120',
            'description_fr' => 'nullable|string',
            'icon_name' => 'nullable|string|max:40',
            'color_hex' => 'nullable|string|max:7',
            'parent_category_id' => 'nullable|integer|exists:practitioner_categories,id',
            'is_medical' => 'nullable|boolean',
            'display_order' => 'nullable|integer',
        ]);
        $data['is_medical'] = (bool) ($data['is_medical'] ?? true);
        PractitionerCategory::create($data);
        return redirect()->route('admin.pro-cats.index')->with('success', 'Catégorie créée');
    }

    public function edit(string $uuid): View
    {
        $cat = PractitionerCategory::where('uuid', $uuid)->firstOrFail();
        return view('admin.practitioner-categories.form', [
            'category' => $cat,
            'parents' => PractitionerCategory::whereNull('parent_category_id')->where('id', '!=', $cat->id)->orderBy('name_fr')->get(),
        ]);
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        $cat = PractitionerCategory::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'code' => 'required|string|max:40|unique:practitioner_categories,code,'.$cat->id,
            'name_fr' => 'required|string|max:120',
            'name_en' => 'nullable|string|max:120',
            'description_fr' => 'nullable|string',
            'icon_name' => 'nullable|string|max:40',
            'color_hex' => 'nullable|string|max:7',
            'parent_category_id' => 'nullable|integer|exists:practitioner_categories,id',
            'is_medical' => 'nullable|boolean',
            'display_order' => 'nullable|integer',
        ]);
        $data['is_medical'] = (bool) ($data['is_medical'] ?? true);
        $cat->update($data);
        return redirect()->route('admin.pro-cats.index')->with('success', 'Catégorie mise à jour');
    }

    public function destroy(string $uuid): RedirectResponse
    {
        $cat = PractitionerCategory::where('uuid', $uuid)->firstOrFail();
        if ($cat->practitioners()->count() > 0) {
            return back()->withErrors(['delete' => 'Des praticiens sont rattachés à cette catégorie.']);
        }
        $cat->delete();
        return redirect()->route('admin.pro-cats.index')->with('success', 'Catégorie supprimée');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.uuid' => 'required|string|exists:practitioner_categories,uuid',
            'items.*.display_order' => 'required|integer|min:0',
        ]);
        foreach ($data['items'] as $item) {
            PractitionerCategory::where('uuid', $item['uuid'])->update(['display_order' => $item['display_order']]);
        }
        return back()->with('success', 'Ordre mis à jour');
    }
}
