<?php
declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminRolesController
{
    public function index(): View
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->orderBy('environment')
            ->orderBy('display_order')
            ->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function showPermissions(string $slug): View
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        $permissions = Permission::orderBy('scope')->orderBy('display_order')->get()
            ->groupBy('scope');
        $assigned = $role->permissions->pluck('id')->all();
        return view('admin.roles.permissions', compact('role', 'permissions', 'assigned'));
    }

    public function updatePermissions(Request $request, string $slug): RedirectResponse
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        if ($role->is_system) {
            abort(422, "Le rôle système {$role->slug} ne peut pas être modifié.");
        }
        $data = $request->validate([
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);
        $role->permissions()->sync($data['permission_ids'] ?? []);
        return back()->with('success', 'Permissions mises à jour');
    }

    public function destroy(string $slug): RedirectResponse
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        if ($role->is_system) {
            return back()->withErrors(['delete' => 'Rôle système non supprimable']);
        }
        if ($role->users()->count() > 0) {
            return back()->withErrors(['delete' => 'Détachez tous les utilisateurs avant de supprimer']);
        }
        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Rôle supprimé');
    }
}
