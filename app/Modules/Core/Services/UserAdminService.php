<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

final class UserAdminService
{
    public const BULK_LIMIT = 100;

    /** @param array<string, mixed> $data */
    public function createUser(array $data): User
    {
        $autoPwd = empty($data['password']);
        if ($autoPwd) {
            $data['password'] = $this->generateRandomPassword();
        }
        $data['password'] = Hash::make($data['password']);
        $data['must_change_password'] = $autoPwd;

        return User::create($data);
    }

    /** @param array<string, mixed> $data */
    public function updateUser(User $user, array $data): User
    {
        unset($data['password']);
        $user->update($data);

        return $user->fresh();
    }

    public function suspend(User $user, ?string $reason = null): void
    {
        $user->forceFill(['locked_until' => '9999-12-31 23:59:59'])->save();
        $user->tokens()->delete();
    }

    public function reactivate(User $user): void
    {
        $user->forceFill(['locked_until' => null])->save();
    }

    /**
     * Generates a new random password, hashes it, marks must_change_password,
     * revokes active tokens, and returns the plain-text password for admin transmission.
     */
    public function resetPassword(User $user): string
    {
        $plain = $this->generateRandomPassword();
        $user->update([
            'password' => Hash::make($plain),
            'must_change_password' => true,
        ]);
        $user->tokens()->delete();

        return $plain;
    }

    public function softDelete(User $user): void
    {
        $this->guardNotLastSuperAdmin($user);
        $user->tokens()->delete();
        $user->delete();
    }

    public function restore(User $user): void
    {
        $user->restore();
    }

    public function validatePro(User $user, bool $approve, ?string $rejectionReason): void
    {
        if (! $approve && empty($rejectionReason)) {
            throw new \InvalidArgumentException('rejection_reason est requis pour un rejet');
        }

        if ($approve) {
            $user->forceFill([
                'pro_validated_at' => now(),
                'pro_validation_status' => 'validated',
                'pro_rejection_reason' => null,
            ])->save();
        } else {
            $user->forceFill([
                'pro_validated_at' => null,
                'pro_validation_status' => 'rejected',
                'pro_rejection_reason' => $rejectionReason,
            ])->save();
        }
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  array<string, mixed>   $params
     */
    public function bulkAction(Collection $users, string $action, array $params = []): int
    {
        if ($users->count() > self::BULK_LIMIT) {
            throw new \DomainException('Bulk action limited to '.self::BULK_LIMIT.' targets');
        }

        $count = 0;
        foreach ($users as $u) {
            try {
                match ($action) {
                    'suspend' => $this->suspend($u, $params['reason'] ?? null),
                    'reactivate' => $this->reactivate($u),
                    'delete' => $this->softDelete($u),
                    'restore' => $this->restore($u),
                    default => throw new \InvalidArgumentException("Unknown bulk action: {$action}"),
                };
                $count++;
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $count;
    }

    private function guardNotLastSuperAdmin(User $user): void
    {
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        if (! $superAdminRole) {
            return;
        }

        $hasSuperAdmin = UserRoleAssignment::where('user_id', $user->id)
            ->where('role_id', $superAdminRole->id)
            ->exists();

        if (! $hasSuperAdmin) {
            return;
        }

        $count = UserRoleAssignment::where('role_id', $superAdminRole->id)
            ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
            ->count();

        if ($count <= 1) {
            throw new \DomainException('Cannot delete the last super_admin');
        }
    }

    private function generateRandomPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#%';
        $out = '';
        $len = strlen($alphabet);
        for ($i = 0; $i < 16; $i++) {
            $out .= $alphabet[random_int(0, $len - 1)];
        }

        return $out;
    }
}
