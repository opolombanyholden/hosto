<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Database\Seeders;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use Illuminate\Database\Seeder;

/**
 * Seeds service flags (urgence, evacuation, soins a domicile) on structures.
 */
final class HostoServiceFlagsSeeder extends Seeder
{
    public function run(): void
    {
        $hostos = Hosto::all();

        foreach ($hostos as $hosto) {
            $types = $hosto->structureTypes->pluck('slug')->toArray();
            $isHospital = array_intersect($types, ['hopital', 'clinique', 'chu', 'hopital-general']);

            $hosto->update([
                'is_emergency_service' => ! empty($isHospital) && random_int(0, 10) > 3,
                'is_evacuation_service' => ! empty($isHospital) && random_int(0, 10) > 6,
                'is_home_care_service' => in_array('cabinet-medical', $types, true) || random_int(0, 10) > 7,
            ]);
        }

        // Some practitioners do home care
        Practitioner::query()
            ->inRandomOrder()
            ->limit(3)
            ->update(['does_home_care' => true]);
    }
}
