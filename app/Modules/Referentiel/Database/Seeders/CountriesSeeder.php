<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Database\Seeders;

use App\Modules\Referentiel\Models\Country;
use Illuminate\Database\Seeder;

/**
 * Seeds the countries referential.
 *
 * Phase 1 focuses on Gabon. Neighbouring CEMAC countries are seeded
 * in "inactive" state as placeholders so UI dropdowns are consistent
 * from day one, without exposing unmaintained data.
 */
final class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<int, array<string, mixed>> $countries */
        $countries = [
            [
                'iso2' => 'GA',
                'iso3' => 'GAB',
                'iso_numeric' => 266,
                'name_fr' => 'Gabon',
                'name_en' => 'Gabon',
                'name_local' => null,
                'phone_prefix' => '+241',
                'currency_code' => 'XAF',
                'default_language' => 'fr',
                'is_active' => true,
                'display_order' => 1,
            ],
            // CEMAC neighbours — inactive until Phase 10+ rollout.
            ['iso2' => 'CM', 'iso3' => 'CMR', 'iso_numeric' => 120, 'name_fr' => 'Cameroun', 'name_en' => 'Cameroon', 'phone_prefix' => '+237', 'currency_code' => 'XAF', 'default_language' => 'fr', 'is_active' => false, 'display_order' => 10],
            ['iso2' => 'CG', 'iso3' => 'COG', 'iso_numeric' => 178, 'name_fr' => 'Congo', 'name_en' => 'Congo', 'phone_prefix' => '+242', 'currency_code' => 'XAF', 'default_language' => 'fr', 'is_active' => false, 'display_order' => 11],
            ['iso2' => 'CF', 'iso3' => 'CAF', 'iso_numeric' => 140, 'name_fr' => 'République centrafricaine', 'name_en' => 'Central African Republic', 'phone_prefix' => '+236', 'currency_code' => 'XAF', 'default_language' => 'fr', 'is_active' => false, 'display_order' => 12],
            ['iso2' => 'TD', 'iso3' => 'TCD', 'iso_numeric' => 148, 'name_fr' => 'Tchad', 'name_en' => 'Chad', 'phone_prefix' => '+235', 'currency_code' => 'XAF', 'default_language' => 'fr', 'is_active' => false, 'display_order' => 13],
            ['iso2' => 'GQ', 'iso3' => 'GNQ', 'iso_numeric' => 226, 'name_fr' => 'Guinée équatoriale', 'name_en' => 'Equatorial Guinea', 'phone_prefix' => '+240', 'currency_code' => 'XAF', 'default_language' => 'es', 'is_active' => false, 'display_order' => 14],
        ];

        foreach ($countries as $country) {
            Country::query()->updateOrCreate(
                ['iso2' => $country['iso2']],
                $country,
            );
        }
    }
}
