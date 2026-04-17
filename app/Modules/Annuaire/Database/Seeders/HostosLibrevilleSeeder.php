<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Database\Seeders;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Referentiel\Models\City;
use App\Modules\Referentiel\Models\Service;
use App\Modules\Referentiel\Models\Specialty;
use App\Modules\Referentiel\Models\StructureType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds representative health structures in Libreville and surroundings.
 *
 * These are real or realistic structures to demonstrate the platform.
 * Data sourced from public directories; coordinates approximate.
 */
final class HostosLibrevilleSeeder extends Seeder
{
    public function run(): void
    {
        $libreville = City::where('name_fr', 'Libreville')->firstOrFail();
        $owendo = City::where('name_fr', 'Owendo')->first();

        foreach ($this->structures() as $data) {
            $city = ($data['city'] ?? 'Libreville') === 'Owendo' && $owendo
                ? $owendo
                : $libreville;

            $hosto = Hosto::firstOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'city_id' => $city->id,
                    'address' => $data['address'] ?? null,
                    'quarter' => $data['quarter'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'is_public' => $data['is_public'] ?? true,
                    'is_guard_service' => $data['is_guard_service'] ?? false,
                    'is_active' => true,
                    'is_verified' => true,
                    'verified_at' => now(),
                    'opening_hours' => $data['opening_hours'] ?? $this->defaultHours(),
                ],
            );

            if (isset($data['lat'], $data['lng'])) {
                $hosto->setCoordinates($data['lat'], $data['lng']);
            }

            // Attach types
            foreach ($data['types'] ?? [] as $i => $typeSlug) {
                $type = StructureType::where('slug', $typeSlug)->first();
                if ($type && ! $hosto->structureTypes()->where('structure_type_id', $type->id)->exists()) {
                    $hosto->structureTypes()->attach($type->id, [
                        'is_primary' => $i === 0,
                        'display_order' => $i,
                    ]);
                }
            }

            // Attach specialties
            foreach ($data['specialties'] ?? [] as $i => $specCode) {
                $spec = Specialty::where('code', $specCode)->first();
                if ($spec && ! $hosto->specialties()->where('specialty_id', $spec->id)->exists()) {
                    $hosto->specialties()->attach($spec->id, ['display_order' => $i]);
                }
            }

            // Attach services
            foreach ($data['services'] ?? [] as $i => $svcData) {
                $svc = Service::where('code', $svcData['code'])->first();
                if ($svc && ! $hosto->services()->where('service_id', $svc->id)->exists()) {
                    $hosto->services()->attach($svc->id, [
                        'tarif_min' => $svcData['tarif_min'] ?? null,
                        'tarif_max' => $svcData['tarif_max'] ?? null,
                        'is_available' => true,
                        'display_order' => $i,
                    ]);
                }
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function structures(): array
    {
        return [
            [
                'name' => 'CHU de Libreville',
                'address' => 'Boulevard Léon Mba',
                'quarter' => 'Centre-ville',
                'phone' => '+24101762244',
                'is_public' => true,
                'lat' => 0.3901,
                'lng' => 9.4544,
                'types' => ['hopital'],
                'specialties' => ['MG', 'CARD', 'CHIR', 'PED', 'GYN', 'NEUR', 'ONCO', 'MED-URG', 'ANES', 'RADI'],
                'services' => [
                    ['code' => 'CONSULT-GEN', 'tarif_min' => 5000, 'tarif_max' => 10000],
                    ['code' => 'CONSULT-SPE', 'tarif_min' => 15000, 'tarif_max' => 50000],
                    ['code' => 'HOSPIT', 'tarif_min' => 25000, 'tarif_max' => 100000],
                    ['code' => 'URGENCE', 'tarif_min' => 10000, 'tarif_max' => 50000],
                    ['code' => 'CHIRURGIE', 'tarif_min' => 100000, 'tarif_max' => 2000000],
                    ['code' => 'MATERNITE', 'tarif_min' => 50000, 'tarif_max' => 300000],
                    ['code' => 'RADIO', 'tarif_min' => 15000, 'tarif_max' => 25000],
                    ['code' => 'ECHO', 'tarif_min' => 20000, 'tarif_max' => 40000],
                    ['code' => 'BILAN-SANG', 'tarif_min' => 5000, 'tarif_max' => 25000],
                ],
            ],
            [
                'name' => 'Hôpital d\'Instruction des Armées Omar Bongo Ondimba',
                'address' => 'Camp de Gaulle',
                'phone' => '+24101762501',
                'is_public' => true,
                'lat' => 0.3830,
                'lng' => 9.4490,
                'types' => ['hopital'],
                'specialties' => ['MG', 'CHIR', 'CARD', 'PED', 'GYN', 'MED-URG'],
                'services' => [
                    ['code' => 'CONSULT-GEN', 'tarif_min' => 5000, 'tarif_max' => 15000],
                    ['code' => 'HOSPIT'],
                    ['code' => 'URGENCE'],
                    ['code' => 'CHIRURGIE'],
                ],
            ],
            [
                'name' => 'Clinique El Rapha',
                'quarter' => 'Nzeng-Ayong',
                'phone' => '+24177123456',
                'is_public' => false,
                'lat' => 0.4150,
                'lng' => 9.4800,
                'types' => ['clinique'],
                'specialties' => ['MG', 'GYN', 'PED', 'DERM'],
                'services' => [
                    ['code' => 'CONSULT-GEN', 'tarif_min' => 15000, 'tarif_max' => 25000],
                    ['code' => 'CONSULT-SPE', 'tarif_min' => 25000, 'tarif_max' => 50000],
                    ['code' => 'ECHO', 'tarif_min' => 25000, 'tarif_max' => 45000],
                    ['code' => 'MATERNITE', 'tarif_min' => 200000, 'tarif_max' => 500000],
                ],
            ],
            [
                'name' => 'Polyclinique Chambrier',
                'address' => 'Boulevard Triomphal',
                'quarter' => 'Centre-ville',
                'phone' => '+24101724512',
                'is_public' => false,
                'lat' => 0.3870,
                'lng' => 9.4520,
                'types' => ['clinique', 'laboratoire'],
                'specialties' => ['MG', 'CARD', 'GAST', 'NEUR', 'OPHT', 'ORL', 'BIOL'],
                'services' => [
                    ['code' => 'CONSULT-GEN', 'tarif_min' => 20000, 'tarif_max' => 30000],
                    ['code' => 'CONSULT-SPE', 'tarif_min' => 30000, 'tarif_max' => 75000],
                    ['code' => 'BILAN-SANG', 'tarif_min' => 8000, 'tarif_max' => 30000],
                    ['code' => 'ECG', 'tarif_min' => 15000, 'tarif_max' => 25000],
                    ['code' => 'SCANNER', 'tarif_min' => 80000, 'tarif_max' => 150000],
                ],
            ],
            [
                'name' => 'Pharmacie du Charbonnage',
                'address' => 'Carrefour Nzeng-Ayong',
                'quarter' => 'Nzeng-Ayong',
                'phone' => '+24177654321',
                'is_public' => false,
                'is_guard_service' => true,
                'lat' => 0.4100,
                'lng' => 9.4750,
                'types' => ['pharmacie'],
                'services' => [
                    ['code' => 'GARDE'],
                    ['code' => 'INJECTION', 'tarif_min' => 1000, 'tarif_max' => 3000],
                ],
            ],
            [
                'name' => 'Laboratoire National de Santé Publique',
                'address' => 'Quartier Nkembo',
                'phone' => '+24101762300',
                'is_public' => true,
                'lat' => 0.3950,
                'lng' => 9.4480,
                'types' => ['laboratoire'],
                'specialties' => ['BIOL'],
                'services' => [
                    ['code' => 'BILAN-SANG', 'tarif_min' => 3000, 'tarif_max' => 15000],
                    ['code' => 'ANALYSE-URINE', 'tarif_min' => 2000, 'tarif_max' => 8000],
                    ['code' => 'DEPIST-PALU', 'tarif_min' => 2000, 'tarif_max' => 5000],
                    ['code' => 'DEPIST-VIH', 'tarif_min' => 3000, 'tarif_max' => 5000],
                ],
            ],
            [
                'name' => 'Centre Hospitalier d\'Owendo',
                'city' => 'Owendo',
                'phone' => '+24101700500',
                'is_public' => true,
                'lat' => 0.2965,
                'lng' => 9.5002,
                'types' => ['hopital', 'maternite'],
                'specialties' => ['MG', 'PED', 'GYN', 'CHIR', 'MED-URG'],
                'services' => [
                    ['code' => 'CONSULT-GEN', 'tarif_min' => 5000, 'tarif_max' => 10000],
                    ['code' => 'URGENCE'],
                    ['code' => 'MATERNITE', 'tarif_min' => 30000, 'tarif_max' => 150000],
                    ['code' => 'HOSPIT', 'tarif_min' => 15000, 'tarif_max' => 50000],
                    ['code' => 'VACCINATION', 'tarif_min' => 0, 'tarif_max' => 5000],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{open: string, close: string}>
     */
    private function defaultHours(): array
    {
        return [
            'lun' => ['open' => '07:30', 'close' => '17:00'],
            'mar' => ['open' => '07:30', 'close' => '17:00'],
            'mer' => ['open' => '07:30', 'close' => '17:00'],
            'jeu' => ['open' => '07:30', 'close' => '17:00'],
            'ven' => ['open' => '07:30', 'close' => '17:00'],
            'sam' => ['open' => '08:00', 'close' => '12:00'],
        ];
    }
}
