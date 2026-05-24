<?php
declare(strict_types=1);

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Models\Role;
use Illuminate\Database\Seeder;

class AdditionalRolesSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(['slug' => 'compta'], [
            'name_fr' => 'Comptable',
            'name_en' => 'Accountant',
            'environment' => 'admin',
            'description_fr' => 'Gère les paiements, factures et reporting financier',
            'is_system' => false,
            'display_order' => 50,
        ]);
        Role::updateOrCreate(['slug' => 'stat'], [
            'name_fr' => 'Statisticien / Data analyst',
            'name_en' => 'Statistician',
            'environment' => 'admin',
            'description_fr' => 'Accès lecture aux données stats et exports',
            'is_system' => false,
            'display_order' => 51,
        ]);

        $relabels = [
            'structure_owner' => 'Gestionnaire de compte',
            'admin_staff' => 'Administratif',
            'ministry' => 'Gouvernement / Ministère santé',
        ];
        foreach ($relabels as $slug => $label) {
            Role::where('slug', $slug)->update(['name_fr' => $label]);
        }

        Role::where('slug', 'super_admin')->update(['is_system' => true]);
    }
}
