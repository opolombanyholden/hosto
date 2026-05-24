<?php
declare(strict_types=1);

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = [
            'super_admin' => [],
            'moderator' => [
                'users.view', 'users.suspend', 'users.validate_pro',
                'structures.view', 'structures.validate', 'claims.review',
                'stats.view',
            ],
            'ministry' => [
                'users.view', 'structures.view', 'stats.view', 'stats.view.financial',
                'exports.users', 'exports.structures', 'exports.stats',
            ],
            'compta' => [
                'users.view', 'payments.view', 'payments.refund',
                'invoices.manage', 'stats.view.financial', 'exports.financial',
            ],
            'stat' => [
                'users.view', 'structures.view', 'stats.view', 'stats.view.financial',
                'exports.users', 'exports.structures', 'exports.stats',
            ],
            'structure_owner' => [
                'structures.edit', 'appointments.manage', 'users.view',
                'pro_categories.view', 'stats.view', 'self.profile',
            ],
            'admin_staff' => [
                'appointments.manage', 'users.view', 'consultations.view',
                'self.profile',
            ],
            'doctor' => [
                'consultations.manage', 'prescriptions.create', 'prescriptions.view',
                'appointments.manage', 'self.profile',
            ],
            'nurse' => [
                'consultations.view', 'prescriptions.view',
                'appointments.manage', 'self.profile',
            ],
            'pharmacist' => [
                'prescriptions.view', 'payments.view', 'self.profile',
            ],
            'lab_tech' => [
                'consultations.view', 'self.profile',
            ],
            'patient' => [
                'self.profile', 'self.medical_record', 'self.carnet_vaccination',
                'appointments.book.self',
            ],
        ];

        foreach ($matrix as $roleSlug => $permSlugs) {
            $role = Role::where('slug', $roleSlug)->first();
            if (! $role || empty($permSlugs)) {
                continue;
            }
            $ids = Permission::whereIn('slug', $permSlugs)->pluck('id')->all();
            $role->permissions()->sync($ids);
        }
    }
}
