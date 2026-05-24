<?php
declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class RoleAssignmentService
{
    public function assign(
        User $user,
        Role $role,
        ?Model $scope = null,
        ?User $assignedBy = null,
        ?\DateTimeInterface $expiresAt = null,
    ): void {
        $scopeType = $scope ? $scope::class : null;
        $scopeId = $scope?->getKey();

        UserRoleAssignment::updateOrCreate(
            [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
            ],
            [
                'assigned_by' => $assignedBy?->id,
                'assigned_at' => now(),
                'expires_at' => $expiresAt,
            ]
        );
    }

    public function revoke(User $user, Role $role, ?Model $scope = null): void
    {
        $q = UserRoleAssignment::where('user_id', $user->id)
            ->where('role_id', $role->id);

        if ($scope) {
            $q->where('scope_type', $scope::class)
                ->where('scope_id', $scope->getKey());
        } else {
            $q->whereNull('scope_type')->whereNull('scope_id');
        }

        $q->delete();
    }

    /**
     * Sync the full set of roles for a given scope.
     * Removes any existing role on that scope that's not in $roles.
     *
     * @param  array<Role>  $roles
     */
    public function syncRolesForScope(User $user, array $roles, ?Model $scope, ?User $assignedBy = null): void
    {
        $scopeType = $scope ? $scope::class : null;
        $scopeId = $scope?->getKey();
        $roleIds = collect($roles)->pluck('id')->all();

        DB::transaction(function () use ($user, $roles, $scope, $scopeType, $scopeId, $roleIds, $assignedBy) {
            // Delete existing assignments on this scope not in the new set
            UserRoleAssignment::where('user_id', $user->id)
                ->where(function ($q) use ($scopeType, $scopeId) {
                    if ($scopeType === null) {
                        $q->whereNull('scope_type')->whereNull('scope_id');
                    } else {
                        $q->where('scope_type', $scopeType)->where('scope_id', $scopeId);
                    }
                })
                ->whereNotIn('role_id', $roleIds)
                ->delete();

            // Upsert each role with the correct scope
            foreach ($roles as $role) {
                $this->assign($user, $role, $scope, $assignedBy);
            }
        });
    }

    /** @return Builder<User> */
    public function usersHavingRole(Role $role, ?Model $scope = null): Builder
    {
        $q = User::query()->whereExists(function ($sub) use ($role, $scope) {
            $sub->from('user_roles')
                ->whereColumn('user_roles.user_id', 'users.id')
                ->where('user_roles.role_id', $role->id);
            if ($scope) {
                $sub->where('scope_type', $scope::class)
                    ->where('scope_id', $scope->getKey());
            }
        });
        return $q;
    }
}
