<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class PermissionResolver
{
    /** @var array<int, Collection<int, Permission>> */
    private array $cache = [];

    public function userCan(User $user, string $permission, ?Model $scope = null): bool
    {
        // Court-circuit super_admin.
        if ($this->hasGlobalRole($user, 'super_admin')) {
            return true;
        }

        // Récupère les assignations actives du user (expirées ignorées).
        $assignments = UserRoleAssignment::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('role.permissions')
            ->get();

        foreach ($assignments as $assignment) {
            $hasPerm = $assignment->role->permissions->contains('slug', $permission);
            if (! $hasPerm) {
                continue;
            }

            // Rôle global = autorise toujours.
            if ($assignment->scope_type === null) {
                return true;
            }

            // Rôle scopé : si pas de scope passé, on autorise (global semantics).
            if ($scope === null) {
                return true;
            }

            // Sinon, scope_type et scope_id doivent matcher.
            if ($assignment->scope_type === $scope::class
                && (int) $assignment->scope_id === (int) $scope->getKey()) {
                return true;
            }
        }

        return false;
    }

    /** @return Collection<int, Permission> */
    public function userPermissions(User $user): Collection
    {
        if (isset($this->cache[$user->id])) {
            return $this->cache[$user->id];
        }

        $assignments = UserRoleAssignment::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('role.permissions')
            ->get();

        $perms = collect();
        foreach ($assignments as $a) {
            $perms = $perms->merge($a->role->permissions);
        }

        $this->cache[$user->id] = $perms->unique('slug')->values();

        return $this->cache[$user->id];
    }

    public function hasGlobalRole(User $user, string $slug): bool
    {
        return UserRoleAssignment::where('user_id', $user->id)
            ->whereNull('scope_type')
            ->whereHas('role', fn ($q) => $q->where('slug', $slug))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function hasScopedRole(User $user, string $slug, Model $scope): bool
    {
        return UserRoleAssignment::where('user_id', $user->id)
            ->where('scope_type', $scope::class)
            ->where('scope_id', $scope->getKey())
            ->whereHas('role', fn ($q) => $q->where('slug', $slug))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }
}
