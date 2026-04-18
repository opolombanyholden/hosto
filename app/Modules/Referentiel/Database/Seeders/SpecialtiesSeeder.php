<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Database\Seeders;

use App\Modules\Referentiel\Models\Specialty;
use Illuminate\Database\Seeder;

/**
 * Seeds the medical specialties referential.
 *
 * Based on the commonly used medical specialties in Gabon and
 * francophone Africa, aligned with CIM-10 categories where applicable.
 *
 * Hierarchy: parent specialties → sub-specialties (one level deep).
 */
final class SpecialtiesSeeder extends Seeder
{
    public function run(): void
    {
        $specialties = $this->specialties();

        foreach ($specialties as $index => $spec) {
            $parent = Specialty::firstOrCreate(
                ['code' => $spec['code']],
                [
                    'name_fr' => $spec['name_fr'],
                    'name_en' => $spec['name_en'],
                    'display_order' => $index + 1,
                ],
            );

            foreach ($spec['children'] ?? [] as $childIndex => $child) {
                Specialty::firstOrCreate(
                    ['code' => $child['code']],
                    [
                        'name_fr' => $child['name_fr'],
                        'name_en' => $child['name_en'],
                        'parent_id' => $parent->id,
                        'display_order' => $childIndex + 1,
                    ],
                );
            }
        }
    }

    /**
     * @return list<array{
     *     code: string,
     *     name_fr: string,
     *     name_en: string,
     *     children?: list<array{code: string, name_fr: string, name_en: string}>
     * }>
     */
    private function specialties(): array
    {
        return [
            ['code' => 'MG', 'name_fr' => 'Médecine générale', 'name_en' => 'General medicine'],
            ['code' => 'CARD', 'name_fr' => 'Cardiologie', 'name_en' => 'Cardiology'],
            ['code' => 'DERM', 'name_fr' => 'Dermatologie', 'name_en' => 'Dermatology'],
            ['code' => 'ENDO', 'name_fr' => 'Endocrinologie', 'name_en' => 'Endocrinology'],
            ['code' => 'GAST', 'name_fr' => 'Gastro-entérologie', 'name_en' => 'Gastroenterology'],
            [
                'code' => 'GYN',
                'name_fr' => 'Gynécologie-obstétrique',
                'name_en' => 'Obstetrics and gynecology',
                'children' => [
                    ['code' => 'GYN-OBS', 'name_fr' => 'Obstétrique', 'name_en' => 'Obstetrics'],
                    ['code' => 'GYN-MED', 'name_fr' => 'Gynécologie médicale', 'name_en' => 'Medical gynecology'],
                ],
            ],
            ['code' => 'HEMA', 'name_fr' => 'Hématologie', 'name_en' => 'Hematology'],
            ['code' => 'INFE', 'name_fr' => 'Infectiologie', 'name_en' => 'Infectious diseases'],
            ['code' => 'NEPH', 'name_fr' => 'Néphrologie', 'name_en' => 'Nephrology'],
            ['code' => 'NEUR', 'name_fr' => 'Neurologie', 'name_en' => 'Neurology'],
            ['code' => 'ONCO', 'name_fr' => 'Oncologie', 'name_en' => 'Oncology'],
            ['code' => 'OPHT', 'name_fr' => 'Ophtalmologie', 'name_en' => 'Ophthalmology'],
            ['code' => 'ORL', 'name_fr' => 'ORL', 'name_en' => 'ENT (Ear, nose, throat)'],
            [
                'code' => 'PED',
                'name_fr' => 'Pédiatrie',
                'name_en' => 'Pediatrics',
                'children' => [
                    ['code' => 'PED-NEO', 'name_fr' => 'Néonatalogie', 'name_en' => 'Neonatology'],
                    ['code' => 'PED-GEN', 'name_fr' => 'Pédiatrie générale', 'name_en' => 'General pediatrics'],
                ],
            ],
            ['code' => 'PNEU', 'name_fr' => 'Pneumologie', 'name_en' => 'Pulmonology'],
            ['code' => 'PSY', 'name_fr' => 'Psychiatrie', 'name_en' => 'Psychiatry'],
            ['code' => 'RHUM', 'name_fr' => 'Rhumatologie', 'name_en' => 'Rheumatology'],
            ['code' => 'UROL', 'name_fr' => 'Urologie', 'name_en' => 'Urology'],
            [
                'code' => 'CHIR',
                'name_fr' => 'Chirurgie',
                'name_en' => 'Surgery',
                'children' => [
                    ['code' => 'CHIR-GEN', 'name_fr' => 'Chirurgie générale', 'name_en' => 'General surgery'],
                    ['code' => 'CHIR-ORT', 'name_fr' => 'Chirurgie orthopédique', 'name_en' => 'Orthopedic surgery'],
                    ['code' => 'CHIR-CAR', 'name_fr' => 'Chirurgie cardiaque', 'name_en' => 'Cardiac surgery'],
                    ['code' => 'CHIR-PED', 'name_fr' => 'Chirurgie pédiatrique', 'name_en' => 'Pediatric surgery'],
                    ['code' => 'CHIR-PLA', 'name_fr' => 'Chirurgie plastique', 'name_en' => 'Plastic surgery'],
                ],
            ],
            ['code' => 'ANES', 'name_fr' => 'Anesthésie-réanimation', 'name_en' => 'Anesthesiology'],
            ['code' => 'RADI', 'name_fr' => 'Radiologie', 'name_en' => 'Radiology'],
            ['code' => 'BIOL', 'name_fr' => 'Biologie médicale', 'name_en' => 'Medical biology'],
            ['code' => 'MED-INT', 'name_fr' => 'Médecine interne', 'name_en' => 'Internal medicine'],
            ['code' => 'MED-URG', 'name_fr' => 'Médecine d\'urgence', 'name_en' => 'Emergency medicine'],
            ['code' => 'MED-TRAV', 'name_fr' => 'Médecine du travail', 'name_en' => 'Occupational medicine'],
            ['code' => 'ODONT', 'name_fr' => 'Odontologie', 'name_en' => 'Dentistry'],
            ['code' => 'KINE', 'name_fr' => 'Kinésithérapie', 'name_en' => 'Physiotherapy'],
            ['code' => 'NUTR', 'name_fr' => 'Nutrition', 'name_en' => 'Nutrition'],
            ['code' => 'PHARM', 'name_fr' => 'Pharmacie', 'name_en' => 'Pharmacy'],
        ];
    }
}
