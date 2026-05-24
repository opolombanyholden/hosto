<?php
declare(strict_types=1);

namespace App\Modules\Annuaire\Database\Seeders;

use App\Modules\Annuaire\Models\PractitionerCategory;
use Illuminate\Database\Seeder;

class PractitionerCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $cats = [
            ['code' => 'doctor',                  'name_fr' => 'Médecin généraliste',          'icon' => 'stethoscope', 'color' => '#1565C0', 'medical' => true],
            ['code' => 'specialist',              'name_fr' => 'Médecin spécialiste',          'icon' => 'doctor',      'color' => '#0277BD', 'medical' => true],
            ['code' => 'dentist',                 'name_fr' => 'Dentiste',                     'icon' => 'tooth',       'color' => '#00838F', 'medical' => true],
            ['code' => 'midwife',                 'name_fr' => 'Sage-femme',                   'icon' => 'baby',        'color' => '#C2185B', 'medical' => true],
            ['code' => 'nurse',                   'name_fr' => 'Infirmier(ère)',               'icon' => 'first-aid',   'color' => '#388E3C', 'medical' => true],
            ['code' => 'pharmacist',              'name_fr' => 'Pharmacien',                   'icon' => 'pill',        'color' => '#7B1FA2', 'medical' => true],
            ['code' => 'lab_technician',          'name_fr' => 'Technicien laboratoire',       'icon' => 'flask',       'color' => '#5E35B1', 'medical' => true],
            ['code' => 'radiologist_tech',        'name_fr' => 'Manipulateur radio',           'icon' => 'scan',        'color' => '#455A64', 'medical' => true],
            ['code' => 'kinesitherapist',         'name_fr' => 'Kinésithérapeute',             'icon' => 'activity',    'color' => '#F57C00', 'medical' => true],
            ['code' => 'psychologist',            'name_fr' => 'Psychologue',                  'icon' => 'brain',       'color' => '#6A1B9A', 'medical' => true],
            ['code' => 'nutritionist',            'name_fr' => 'Nutritionniste',               'icon' => 'apple',       'color' => '#558B2F', 'medical' => true],
            ['code' => 'optometrist',             'name_fr' => 'Optométriste',                 'icon' => 'eye',         'color' => '#00695C', 'medical' => true],
            ['code' => 'vet_public_health',       'name_fr' => 'Vétérinaire santé publique',   'icon' => 'paw',         'color' => '#795548', 'medical' => true],
            ['code' => 'community_health_worker', 'name_fr' => 'Agent santé communautaire',    'icon' => 'users',       'color' => '#00897B', 'medical' => false],
            ['code' => 'traditional_healer',      'name_fr' => 'Tradipraticien',               'icon' => 'leaf',        'color' => '#9E9D24', 'medical' => false],
        ];

        foreach ($cats as $i => $c) {
            PractitionerCategory::updateOrCreate(
                ['code' => $c['code']],
                [
                    'name_fr' => $c['name_fr'],
                    'icon_name' => $c['icon'],
                    'color_hex' => $c['color'],
                    'is_medical' => $c['medical'],
                    'display_order' => $i + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
