<?php

declare(strict_types=1);

namespace App\Modules\Pharma\Database\Seeders;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Pharma\Models\PharmacyStock;
use App\Modules\Referentiel\Models\Medication;
use Illuminate\Database\Seeder;

/**
 * Seeds stock for pharmacies with random prices and quantities.
 */
final class PharmacyStockSeeder extends Seeder
{
    public function run(): void
    {
        $pharmacies = Hosto::whereHas('structureTypes', fn ($q) => $q->where('slug', 'pharmacie'))->get();
        $medications = Medication::all();

        foreach ($pharmacies as $pharmacy) {
            foreach ($medications as $med) {
                PharmacyStock::firstOrCreate(
                    ['hosto_id' => $pharmacy->id, 'medication_id' => $med->id],
                    [
                        'quantity_in_stock' => random_int(0, 200),
                        'quantity_min_alert' => 10,
                        'unit_price' => random_int(500, 15000),
                        'is_available' => random_int(0, 10) > 1, // 90% available
                        'expiry_date' => now()->addMonths(random_int(3, 24)),
                    ],
                );
            }
        }
    }
}
