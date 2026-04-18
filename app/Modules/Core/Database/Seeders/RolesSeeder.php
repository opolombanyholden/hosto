<?php

declare(strict_types=1);

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seeds the default roles for the three environments.
 *
 * @see docs/adr/0011-trois-environnements-authentification.md
 */
final class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            // Admin environment
            ['slug' => 'super_admin', 'name_fr' => 'Super administrateur', 'name_en' => 'Super administrator', 'environment' => 'admin', 'display_order' => 1],
            ['slug' => 'moderator', 'name_fr' => 'Moderateur', 'name_en' => 'Moderator', 'environment' => 'admin', 'display_order' => 2],
            ['slug' => 'ministry', 'name_fr' => 'Ministere de la Sante', 'name_en' => 'Ministry of Health', 'environment' => 'admin', 'display_order' => 3],

            // Pro environment
            ['slug' => 'structure_owner', 'name_fr' => 'Responsable de structure', 'name_en' => 'Structure owner', 'environment' => 'pro', 'display_order' => 1],
            ['slug' => 'doctor', 'name_fr' => 'Medecin', 'name_en' => 'Doctor', 'environment' => 'pro', 'display_order' => 2],
            ['slug' => 'pharmacist', 'name_fr' => 'Pharmacien', 'name_en' => 'Pharmacist', 'environment' => 'pro', 'display_order' => 3],
            ['slug' => 'lab_tech', 'name_fr' => 'Laborantin / Biologiste', 'name_en' => 'Lab technician', 'environment' => 'pro', 'display_order' => 4],
            ['slug' => 'nurse', 'name_fr' => 'Infirmier / Sage-femme', 'name_en' => 'Nurse / Midwife', 'environment' => 'pro', 'display_order' => 5],
            ['slug' => 'admin_staff', 'name_fr' => 'Agent administratif', 'name_en' => 'Administrative staff', 'environment' => 'pro', 'display_order' => 6],

            // Usager environment
            ['slug' => 'patient', 'name_fr' => 'Patient / Usager', 'name_en' => 'Patient / User', 'environment' => 'usager', 'display_order' => 1],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
