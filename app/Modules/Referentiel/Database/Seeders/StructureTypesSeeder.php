<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Database\Seeders;

use App\Modules\Referentiel\Models\StructureType;
use Illuminate\Database\Seeder;

/**
 * Seeds the standard health structure types.
 *
 * These categories are stable and universal (not country-specific).
 * The slug is the stable API identifier — never renamed once published.
 */
final class StructureTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['slug' => 'hopital', 'name_fr' => 'Hôpital', 'name_en' => 'Hospital', 'icon' => 'icon-hopitaux', 'display_order' => 1],
            ['slug' => 'clinique', 'name_fr' => 'Clinique', 'name_en' => 'Clinic', 'icon' => 'icon-hopitaux', 'display_order' => 2],
            ['slug' => 'centre-de-sante', 'name_fr' => 'Centre de santé', 'name_en' => 'Health center', 'icon' => 'icon-hopitaux', 'display_order' => 3],
            ['slug' => 'cabinet-medical', 'name_fr' => 'Cabinet médical', 'name_en' => 'Medical office', 'icon' => 'icon-doctor', 'display_order' => 4],
            ['slug' => 'pharmacie', 'name_fr' => 'Pharmacie', 'name_en' => 'Pharmacy', 'icon' => 'icon-pharmacie', 'display_order' => 5],
            ['slug' => 'laboratoire', 'name_fr' => 'Laboratoire', 'name_en' => 'Laboratory', 'icon' => 'icon-laboratoire', 'display_order' => 6],
            ['slug' => 'centre-imagerie', 'name_fr' => 'Centre d\'imagerie', 'name_en' => 'Imaging center', 'icon' => 'icon-examens', 'display_order' => 7],
            ['slug' => 'maternite', 'name_fr' => 'Maternité', 'name_en' => 'Maternity', 'icon' => 'icon-soindomicile', 'display_order' => 8],
            ['slug' => 'centre-vaccination', 'name_fr' => 'Centre de vaccination', 'name_en' => 'Vaccination center', 'icon' => 'icon-medicament', 'display_order' => 9],
            ['slug' => 'dentiste', 'name_fr' => 'Cabinet dentaire', 'name_en' => 'Dental office', 'icon' => 'icon-doctor', 'display_order' => 10],
            ['slug' => 'opticien', 'name_fr' => 'Opticien', 'name_en' => 'Optician', 'icon' => 'icon-doctor', 'display_order' => 11],
        ];

        foreach ($types as $type) {
            StructureType::firstOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
