<?php
declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Services\RoleAssignmentService;
use App\Modules\Core\Services\UserAdminService;
use App\Modules\Core\Services\UserExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminUsersController
{
    public function index(Request $request): View
    {
        $q = $request->query('q');
        $roleSlug = $request->query('role');
        $env = $request->query('env');
        $status = $request->query('status', 'active');

        $query = User::query()->with('roles');

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status === 'locked') {
            $query->where('locked_until', '>', now());
        } elseif ($status === 'active') {
            $query->where(function ($w) {
                $w->whereNull('locked_until')->orWhere('locked_until', '<=', now());
            });
        }

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'ILIKE', "%{$q}%")
                  ->orWhere('email', 'ILIKE', "%{$q}%")
                  ->orWhere('nip', 'ILIKE', "%{$q}%")
                  ->orWhere('phone', 'ILIKE', "%{$q}%");
            });
        }

        if ($roleSlug) {
            $query->whereHas('roles', fn ($r) => $r->where('slug', $roleSlug));
        }

        if ($env) {
            $query->whereHas('roles', fn ($r) => $r->where('environment', $env));
        }

        $users = $query->orderByDesc('created_at')->paginate(30)->withQueryString();
        $roles = Role::orderBy('environment')->orderBy('display_order')->get();

        return view('admin.users.index', compact('users', 'roles', 'q', 'roleSlug', 'env', 'status'));
    }

    public function show(string $uuid): View
    {
        $user = User::withTrashed()->where('uuid', $uuid)->with('roles')->firstOrFail();
        return view('admin.users.show', compact('user'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request, UserAdminService $svc): RedirectResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8',
        ]);
        $user = $svc->createUser($data);
        $msg = "Compte créé : {$user->email}";
        if ($user->must_change_password) {
            $msg .= " (mot de passe temporaire transmis à l'admin)";
        }
        return redirect()->route('admin.users.show', $user->uuid)->with('success', $msg);
    }

    public function edit(string $uuid): View
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, string $uuid, UserAdminService $svc): RedirectResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:30',
            'nip'   => 'nullable|string|max:30',
        ]);
        $svc->updateUser($user, $data);
        return redirect()->route('admin.users.show', $user->uuid)->with('success', 'Utilisateur mis à jour');
    }

    public function destroy(string $uuid, Request $request, UserAdminService $svc): RedirectResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['delete' => 'Impossible de se supprimer soi-même']);
        }
        try {
            $svc->softDelete($user);
            return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé');
        } catch (\DomainException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }
    }

    public function restoreUser(string $uuid, UserAdminService $svc): RedirectResponse
    {
        $user = User::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $svc->restore($user);
        return redirect()->route('admin.users.show', $user->uuid)->with('success', 'Utilisateur restauré');
    }

    public function suspend(Request $request, string $uuid, UserAdminService $svc): RedirectResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['suspend' => 'Impossible de se suspendre soi-même']);
        }
        $data = $request->validate(['reason' => 'required|string|max:500']);
        $svc->suspend($user, $data['reason']);
        return back()->with('success', 'Compte suspendu');
    }

    public function reactivate(string $uuid, UserAdminService $svc): RedirectResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        $svc->reactivate($user);
        return back()->with('success', 'Compte réactivé');
    }

    public function resetPassword(string $uuid, UserAdminService $svc): RedirectResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        $plain = $svc->resetPassword($user);
        return back()->with('success', 'Mot de passe réinitialisé')->with('temp_password', $plain);
    }

    public function validatePro(Request $request, string $uuid, UserAdminService $svc): RedirectResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'action'           => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:500',
        ]);
        try {
            $svc->validatePro($user, $data['action'] === 'approve', $data['rejection_reason'] ?? null);
            return back()->with('success', 'Statut pro mis à jour');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }
    }

    public function export(Request $request, UserExportService $svc): StreamedResponse
    {
        $query = User::query();
        if ($q = $request->query('q')) {
            $query->where('name', 'ILIKE', "%{$q}%");
        }
        return $svc->exportCsv($query);
    }

    public function bulk(Request $request, UserAdminService $svc): RedirectResponse
    {
        $data = $request->validate([
            'user_uuids'   => 'required|array|min:1|max:100',
            'user_uuids.*' => 'string|exists:users,uuid',
            'action'       => 'required|in:suspend,reactivate,delete,restore',
            'params'       => 'nullable|array',
        ]);
        $users = User::whereIn('uuid', $data['user_uuids'])->get();
        try {
            $count = $svc->bulkAction($users, $data['action'], $data['params'] ?? []);
            return back()->with('success', "{$count} utilisateur(s) traité(s)");
        } catch (\DomainException $e) {
            return back()->withErrors(['bulk' => $e->getMessage()]);
        }
    }

    public function updateRoles(Request $request, string $uuid, RoleAssignmentService $svc): RedirectResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'global_role_ids'              => 'nullable|array',
            'global_role_ids.*'            => 'integer|exists:roles,id',
            'scoped'                       => 'nullable|array',
            'scoped.*.structure_uuid'      => 'required|string|exists:hostos,uuid',
            'scoped.*.role_ids'            => 'required|array',
            'scoped.*.role_ids.*'          => 'integer|exists:roles,id',
            'scoped.*.expires_at'          => 'nullable|date|after:now',
        ]);

        $globalRoles = Role::whereIn('id', $data['global_role_ids'] ?? [])->get()->all();
        $svc->syncRolesForScope($user, $globalRoles, null, $request->user());

        foreach ($data['scoped'] ?? [] as $entry) {
            $hosto = Hosto::where('uuid', $entry['structure_uuid'])->firstOrFail();
            $roles = Role::whereIn('id', $entry['role_ids'])->get()->all();
            $svc->syncRolesForScope($user, $roles, $hosto, $request->user());
        }

        return back()->with('success', 'Rôles mis à jour');
    }

    public function sessions(string $uuid): View
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        $tokens = $user->tokens()->orderByDesc('last_used_at')->get();
        return view('admin.users.sessions', compact('user', 'tokens'));
    }

    public function revokeSession(string $uuid, int $tokenId): RedirectResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        $user->tokens()->where('id', $tokenId)->delete();
        return back()->with('success', 'Session révoquée');
    }
}
