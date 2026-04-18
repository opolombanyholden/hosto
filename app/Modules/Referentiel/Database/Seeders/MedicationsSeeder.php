<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Database\Seeders;

use App\Modules\Referentiel\Models\Medication;
use App\Modules\Referentiel\Models\MedicationBrand;
use Illuminate\Database\Seeder;

/**
 * Seeds common medications used in Gabon.
 */
final class MedicationsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->medications() as $data) {
            $med = Medication::firstOrCreate(['dci' => $data['dci'], 'strength' => $data['strength'] ?? null], [
                'dci_en' => $data['dci_en'] ?? null,
                'therapeutic_class' => $data['class'],
                'therapeutic_class_en' => $data['class_en'] ?? null,
                'dosage_form' => $data['form'] ?? 'comprime',
                'dosage_form_en' => $data['form_en'] ?? 'tablet',
                'strength' => $data['strength'] ?? null,
                'prescription_required' => $data['rx'] ?? false,
            ]);

            foreach ($data['brands'] ?? [] as $brand) {
                MedicationBrand::firstOrCreate(
                    ['medication_id' => $med->id, 'brand_name' => $brand['name']],
                    ['manufacturer' => $brand['manufacturer'] ?? null, 'country_origin' => $brand['country'] ?? null],
                );
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function medications(): array
    {
        return [
            ['dci' => 'Paracetamol', 'dci_en' => 'Paracetamol', 'class' => 'Antalgique / Antipyretique', 'class_en' => 'Analgesic / Antipyretic', 'strength' => '500mg', 'brands' => [
                ['name' => 'Doliprane', 'manufacturer' => 'Sanofi', 'country' => 'FR'],
                ['name' => 'Efferalgan', 'manufacturer' => 'UPSA', 'country' => 'FR'],
                ['name' => 'Dafalgan', 'manufacturer' => 'UPSA', 'country' => 'FR'],
            ]],
            ['dci' => 'Paracetamol', 'dci_en' => 'Paracetamol', 'class' => 'Antalgique / Antipyretique', 'strength' => '1g', 'brands' => [
                ['name' => 'Doliprane 1000', 'manufacturer' => 'Sanofi'],
            ]],
            ['dci' => 'Amoxicilline', 'dci_en' => 'Amoxicillin', 'class' => 'Antibiotique', 'class_en' => 'Antibiotic', 'strength' => '500mg', 'rx' => true, 'brands' => [
                ['name' => 'Clamoxyl', 'manufacturer' => 'GSK', 'country' => 'GB'],
                ['name' => 'Amoxil', 'manufacturer' => 'GSK'],
            ]],
            ['dci' => 'Ibuprofene', 'dci_en' => 'Ibuprofen', 'class' => 'Anti-inflammatoire', 'class_en' => 'Anti-inflammatory', 'strength' => '400mg', 'brands' => [
                ['name' => 'Advil', 'manufacturer' => 'Pfizer', 'country' => 'US'],
                ['name' => 'Nurofen', 'manufacturer' => 'Reckitt', 'country' => 'GB'],
            ]],
            ['dci' => 'Artemether-Lumefantrine', 'dci_en' => 'Artemether-Lumefantrine', 'class' => 'Antipaludeen', 'class_en' => 'Antimalarial', 'strength' => '20/120mg', 'rx' => true, 'brands' => [
                ['name' => 'Coartem', 'manufacturer' => 'Novartis', 'country' => 'CH'],
            ]],
            ['dci' => 'Metformine', 'dci_en' => 'Metformin', 'class' => 'Antidiabetique', 'class_en' => 'Antidiabetic', 'strength' => '500mg', 'rx' => true, 'brands' => [
                ['name' => 'Glucophage', 'manufacturer' => 'Merck', 'country' => 'DE'],
            ]],
            ['dci' => 'Omeprazole', 'dci_en' => 'Omeprazole', 'class' => 'Anti-ulcereux', 'class_en' => 'Proton pump inhibitor', 'form' => 'gelule', 'form_en' => 'capsule', 'strength' => '20mg', 'rx' => true, 'brands' => [
                ['name' => 'Mopral', 'manufacturer' => 'AstraZeneca', 'country' => 'GB'],
            ]],
            ['dci' => 'Amlodipine', 'dci_en' => 'Amlodipine', 'class' => 'Antihypertenseur', 'class_en' => 'Antihypertensive', 'strength' => '5mg', 'rx' => true, 'brands' => [
                ['name' => 'Amlor', 'manufacturer' => 'Pfizer'],
            ]],
            ['dci' => 'Salbutamol', 'dci_en' => 'Salbutamol', 'class' => 'Bronchodilatateur', 'class_en' => 'Bronchodilator', 'form' => 'inhalateur', 'form_en' => 'inhaler', 'strength' => '100mcg', 'rx' => true, 'brands' => [
                ['name' => 'Ventoline', 'manufacturer' => 'GSK'],
            ]],
            ['dci' => 'Cotrimoxazole', 'dci_en' => 'Co-trimoxazole', 'class' => 'Antibiotique', 'class_en' => 'Antibiotic', 'strength' => '480mg', 'rx' => true, 'brands' => [
                ['name' => 'Bactrim', 'manufacturer' => 'Roche', 'country' => 'CH'],
            ]],
        ];
    }
}
