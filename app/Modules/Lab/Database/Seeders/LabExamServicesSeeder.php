<?php

declare(strict_types=1);

namespace App\Modules\Lab\Database\Seeders;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Referentiel\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Ensures all laboratories and hospitals with lab capabilities
 * have exam services associated with realistic pricing.
 */
final class LabExamServicesSeeder extends Seeder
{
    /**
     * Typical exam pricing in XAF (Central Africa).
     *
     * @var array<string, array{min: int, max: int}>
     */
    private const PRICING = [
        'BILAN-SANG' => ['min' => 5000, 'max' => 15000],
        'ANALYSE-URINE' => ['min' => 3000, 'max' => 8000],
        'DEPIST-PALU' => ['min' => 2000, 'max' => 5000],
        'DEPIST-VIH' => ['min' => 3000, 'max' => 10000],
        'RADIO' => ['min' => 10000, 'max' => 25000],
        'ECHO' => ['min' => 15000, 'max' => 35000],
        'ECG' => ['min' => 8000, 'max' => 20000],
        'SCANNER' => ['min' => 50000, 'max' => 150000],
        'IRM' => ['min' => 80000, 'max' => 250000],
        'MAMMO' => ['min' => 20000, 'max' => 50000],
    ];

    public function run(): void
    {
        // All labs + hospitals + clinics can propose exams.
        $structures = Hosto::whereHas('structureTypes', fn ($q) => $q->whereIn('slug', [
            'laboratoire', 'centre-imagerie', 'hopital-general',
            'chu', 'clinique', 'polyclinique',
        ]))->get();

        $examServices = Service::where('category', 'examen')->get();

        foreach ($structures as $hosto) {
            foreach ($examServices as $service) {
                $pricing = self::PRICING[$service->code] ?? ['min' => 5000, 'max' => 20000];

                // Add some variation per structure.
                $variation = random_int(-20, 30) / 100;
                $min = (int) round($pricing['min'] * (1 + $variation));
                $max = (int) round($pricing['max'] * (1 + $variation));

                $hosto->services()->syncWithoutDetaching([
                    $service->id => [
                        'tarif_min' => $min,
                        'tarif_max' => $max,
                        'is_available' => random_int(0, 10) > 1, // 90% dispo
                    ],
                ]);
            }
        }
    }
}
