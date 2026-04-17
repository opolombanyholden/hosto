<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Database\Seeders;

use App\Modules\Referentiel\Models\City;
use App\Modules\Referentiel\Models\Country;
use App\Modules\Referentiel\Models\Region;
use Illuminate\Database\Seeder;

/**
 * Seeds Gabon with its 9 provinces and principal cities.
 *
 * Sources:
 *   - ISO 3166-2:GA (subdivisions)
 *   - Coordinates from GeoNames (CC-BY)
 *   - Population estimates from Institut National de la Statistique du Gabon
 */
final class GabonSeeder extends Seeder
{
    public function run(): void
    {
        $gabon = Country::firstOrCreate(['iso2' => 'GA'], [
            'iso3' => 'GAB',
            'iso_numeric' => 266,
            'name_fr' => 'Gabon',
            'name_en' => 'Gabon',
            'phone_prefix' => '+241',
            'currency_code' => 'XAF',
            'default_language' => 'fr',
            'display_order' => 1,
        ]);

        $provinces = $this->provinces();

        foreach ($provinces as $index => $province) {
            $region = Region::firstOrCreate(
                ['country_id' => $gabon->id, 'code' => $province['code']],
                [
                    'kind' => 'province',
                    'name_fr' => $province['name_fr'],
                    'name_en' => $province['name_en'],
                    'name_local' => $province['name_local'] ?? null,
                    'display_order' => $index + 1,
                ],
            );

            foreach ($province['cities'] as $cityIndex => $cityData) {
                $city = City::firstOrCreate(
                    ['region_id' => $region->id, 'name_fr' => $cityData['name_fr']],
                    [
                        'name_en' => $cityData['name_en'],
                        'name_local' => $cityData['name_local'] ?? null,
                        'is_capital' => $cityData['is_capital'] ?? false,
                        'population' => $cityData['population'] ?? null,
                        'display_order' => $cityIndex + 1,
                    ],
                );

                if (isset($cityData['lat'], $cityData['lng'])) {
                    $city->setCoordinates($cityData['lat'], $cityData['lng']);
                }

                if ($city->is_capital) {
                    $region->update(['capital_city_id' => $city->id]);
                }
            }
        }
    }

    /**
     * @return list<array{
     *     code: string,
     *     name_fr: string,
     *     name_en: string,
     *     name_local?: string,
     *     cities: list<array{
     *         name_fr: string,
     *         name_en: string,
     *         name_local?: string,
     *         is_capital?: bool,
     *         population?: int,
     *         lat?: float,
     *         lng?: float,
     *     }>
     * }>
     */
    private function provinces(): array
    {
        return [
            [
                'code' => 'GA-1',
                'name_fr' => 'Estuaire',
                'name_en' => 'Estuaire',
                'cities' => [
                    ['name_fr' => 'Libreville', 'name_en' => 'Libreville', 'is_capital' => true, 'population' => 870000, 'lat' => 0.3924, 'lng' => 9.4536],
                    ['name_fr' => 'Owendo', 'name_en' => 'Owendo', 'population' => 79000, 'lat' => 0.2965, 'lng' => 9.5002],
                    ['name_fr' => 'Akanda', 'name_en' => 'Akanda', 'population' => 55000, 'lat' => 0.4500, 'lng' => 9.4700],
                    ['name_fr' => 'Ntoum', 'name_en' => 'Ntoum', 'population' => 15000, 'lat' => 0.3900, 'lng' => 9.7600],
                    ['name_fr' => 'Kango', 'name_en' => 'Kango', 'population' => 11000, 'lat' => 0.1700, 'lng' => 10.1400],
                ],
            ],
            [
                'code' => 'GA-2',
                'name_fr' => 'Haut-Ogooué',
                'name_en' => 'Haut-Ogooué',
                'cities' => [
                    ['name_fr' => 'Franceville', 'name_en' => 'Franceville', 'name_local' => 'Masuku', 'is_capital' => true, 'population' => 110000, 'lat' => -1.6333, 'lng' => 13.5833],
                    ['name_fr' => 'Moanda', 'name_en' => 'Moanda', 'population' => 50000, 'lat' => -1.5500, 'lng' => 13.2000],
                    ['name_fr' => 'Mounana', 'name_en' => 'Mounana', 'population' => 12000, 'lat' => -1.4000, 'lng' => 13.1500],
                    ['name_fr' => 'Okondja', 'name_en' => 'Okondja', 'population' => 11000, 'lat' => -0.6500, 'lng' => 13.6800],
                ],
            ],
            [
                'code' => 'GA-3',
                'name_fr' => 'Moyen-Ogooué',
                'name_en' => 'Moyen-Ogooué',
                'cities' => [
                    ['name_fr' => 'Lambaréné', 'name_en' => 'Lambaréné', 'is_capital' => true, 'population' => 39000, 'lat' => -0.7000, 'lng' => 10.2333],
                    ['name_fr' => 'Ndjolé', 'name_en' => 'Ndjolé', 'population' => 7000, 'lat' => -0.1833, 'lng' => 10.7667],
                ],
            ],
            [
                'code' => 'GA-4',
                'name_fr' => 'Ngounié',
                'name_en' => 'Ngounié',
                'cities' => [
                    ['name_fr' => 'Mouila', 'name_en' => 'Mouila', 'is_capital' => true, 'population' => 30000, 'lat' => -1.8667, 'lng' => 11.0500],
                    ['name_fr' => 'Ndendé', 'name_en' => 'Ndendé', 'population' => 7000, 'lat' => -2.4000, 'lng' => 11.3500],
                    ['name_fr' => 'Fougamou', 'name_en' => 'Fougamou', 'population' => 6000, 'lat' => -1.2167, 'lng' => 10.5833],
                    ['name_fr' => 'Mimongo', 'name_en' => 'Mimongo', 'population' => 4000, 'lat' => -1.6167, 'lng' => 11.6167],
                ],
            ],
            [
                'code' => 'GA-5',
                'name_fr' => 'Nyanga',
                'name_en' => 'Nyanga',
                'cities' => [
                    ['name_fr' => 'Tchibanga', 'name_en' => 'Tchibanga', 'is_capital' => true, 'population' => 31000, 'lat' => -2.9333, 'lng' => 11.0333],
                    ['name_fr' => 'Mayumba', 'name_en' => 'Mayumba', 'population' => 5000, 'lat' => -3.4333, 'lng' => 10.6500],
                ],
            ],
            [
                'code' => 'GA-6',
                'name_fr' => 'Ogooué-Ivindo',
                'name_en' => 'Ogooué-Ivindo',
                'cities' => [
                    ['name_fr' => 'Makokou', 'name_en' => 'Makokou', 'is_capital' => true, 'population' => 18000, 'lat' => 0.5667, 'lng' => 12.8667],
                    ['name_fr' => 'Mékambo', 'name_en' => 'Mékambo', 'population' => 5000, 'lat' => 1.0167, 'lng' => 13.9333],
                    ['name_fr' => 'Booué', 'name_en' => 'Booué', 'population' => 8000, 'lat' => -0.1000, 'lng' => 11.9333],
                ],
            ],
            [
                'code' => 'GA-7',
                'name_fr' => 'Ogooué-Lolo',
                'name_en' => 'Ogooué-Lolo',
                'cities' => [
                    ['name_fr' => 'Koulamoutou', 'name_en' => 'Koulamoutou', 'is_capital' => true, 'population' => 20000, 'lat' => -1.1333, 'lng' => 12.4833],
                    ['name_fr' => 'Lastoursville', 'name_en' => 'Lastoursville', 'population' => 8000, 'lat' => -0.8167, 'lng' => 12.7167],
                ],
            ],
            [
                'code' => 'GA-8',
                'name_fr' => 'Ogooué-Maritime',
                'name_en' => 'Ogooué-Maritime',
                'cities' => [
                    ['name_fr' => 'Port-Gentil', 'name_en' => 'Port-Gentil', 'is_capital' => true, 'population' => 136000, 'lat' => -0.7193, 'lng' => 8.7815],
                    ['name_fr' => 'Gamba', 'name_en' => 'Gamba', 'population' => 11000, 'lat' => -2.6500, 'lng' => 10.0000],
                    ['name_fr' => 'Omboué', 'name_en' => 'Omboué', 'population' => 4000, 'lat' => -1.5667, 'lng' => 9.2500],
                ],
            ],
            [
                'code' => 'GA-9',
                'name_fr' => 'Woleu-Ntem',
                'name_en' => 'Woleu-Ntem',
                'cities' => [
                    ['name_fr' => 'Oyem', 'name_en' => 'Oyem', 'is_capital' => true, 'population' => 60000, 'lat' => 1.6000, 'lng' => 11.5833],
                    ['name_fr' => 'Bitam', 'name_en' => 'Bitam', 'population' => 15000, 'lat' => 2.0833, 'lng' => 11.5000],
                    ['name_fr' => 'Minvoul', 'name_en' => 'Minvoul', 'population' => 4000, 'lat' => 2.1500, 'lng' => 12.1333],
                    ['name_fr' => 'Mitzic', 'name_en' => 'Mitzic', 'population' => 8000, 'lat' => 0.7833, 'lng' => 11.5500],
                ],
            ],
        ];
    }
}
