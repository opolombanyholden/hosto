<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Database\Seeders;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Referentiel\Models\Specialty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds representative practitioners in Libreville structures.
 */
final class PractitionersSeeder extends Seeder
{
    public function run(): void
    {
        $chu = Hosto::where('slug', 'chu-de-libreville')->first();
        $elRapha = Hosto::where('slug', 'clinique-el-rapha')->first();
        $chambrier = Hosto::where('slug', 'polyclinique-chambrier')->first();

        foreach ($this->practitioners() as $data) {
            $slug = Str::slug($data['title'].'-'.$data['first_name'].'-'.$data['last_name']);
            $prac = Practitioner::firstOrCreate(['slug' => $slug], [
                'title' => $data['title'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'practitioner_type' => $data['type'] ?? 'doctor',
                'phone' => $data['phone'] ?? null,
                'bio_fr' => $data['bio'] ?? null,
                'languages' => $data['languages'] ?? ['fr'],
                'consultation_fee_min' => $data['fee_min'] ?? null,
                'consultation_fee_max' => $data['fee_max'] ?? null,
                'accepts_new_patients' => $data['accepts_new'] ?? true,
                'does_teleconsultation' => $data['teleconsult'] ?? false,
                'is_active' => true,
                'is_verified' => true,
            ]);

            foreach ($data['specialties'] ?? [] as $i => $specCode) {
                $spec = Specialty::where('code', $specCode)->first();
                if ($spec && ! $prac->specialties()->where('specialty_id', $spec->id)->exists()) {
                    $prac->specialties()->attach($spec->id, ['is_primary' => $i === 0, 'display_order' => $i]);
                }
            }

            foreach ($data['structures'] ?? [] as $i => $structSlug) {
                $struct = match ($structSlug) {
                    'chu' => $chu, 'elrapha' => $elRapha, 'chambrier' => $chambrier, default => null,
                };
                if ($struct && ! $prac->structures()->where('hosto_id', $struct->id)->exists()) {
                    $prac->structures()->attach($struct->id, ['is_primary' => $i === 0, 'display_order' => $i]);
                }
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function practitioners(): array
    {
        return [
            ['title' => 'Dr', 'first_name' => 'Jean-Paul', 'last_name' => 'Ndong', 'gender' => 'male', 'specialties' => ['CARD', 'MED-INT'], 'structures' => ['chu', 'chambrier'], 'phone' => '+24177001001', 'fee_min' => 25000, 'fee_max' => 50000, 'teleconsult' => true, 'bio' => 'Cardiologue avec 15 ans d\'experience au CHU de Libreville.', 'languages' => ['fr', 'en']],
            ['title' => 'Dr', 'first_name' => 'Marie', 'last_name' => 'Obame', 'gender' => 'female', 'specialties' => ['PED', 'PED-NEO'], 'structures' => ['chu'], 'phone' => '+24177001002', 'fee_min' => 15000, 'fee_max' => 30000, 'bio' => 'Pediatre specialisee en neonatalogie.', 'languages' => ['fr']],
            ['title' => 'Pr', 'first_name' => 'Pierre', 'last_name' => 'Mba', 'gender' => 'male', 'specialties' => ['CHIR', 'CHIR-GEN'], 'structures' => ['chu'], 'phone' => '+24177001003', 'fee_min' => 50000, 'fee_max' => 100000, 'bio' => 'Professeur de chirurgie generale. Chef de service au CHU.', 'languages' => ['fr', 'en', 'fang']],
            ['title' => 'Dr', 'first_name' => 'Aissatou', 'last_name' => 'Bongo', 'gender' => 'female', 'specialties' => ['GYN', 'GYN-OBS'], 'structures' => ['elrapha', 'chu'], 'phone' => '+24177001004', 'fee_min' => 20000, 'fee_max' => 45000, 'teleconsult' => true, 'languages' => ['fr']],
            ['title' => 'Dr', 'first_name' => 'Emmanuel', 'last_name' => 'Ondo', 'gender' => 'male', 'specialties' => ['MG'], 'structures' => ['elrapha'], 'phone' => '+24177001005', 'fee_min' => 10000, 'fee_max' => 20000, 'accepts_new' => true, 'teleconsult' => true, 'languages' => ['fr', 'en']],
            ['title' => 'Dr', 'first_name' => 'Sylvie', 'last_name' => 'Nzue', 'gender' => 'female', 'specialties' => ['DERM'], 'structures' => ['chambrier'], 'phone' => '+24177001006', 'fee_min' => 20000, 'fee_max' => 40000, 'languages' => ['fr']],
            ['title' => 'Dr', 'first_name' => 'Rodrigue', 'last_name' => 'Moussavou', 'gender' => 'male', 'specialties' => ['OPHT'], 'structures' => ['chambrier'], 'fee_min' => 25000, 'fee_max' => 50000, 'languages' => ['fr', 'en']],
            ['title' => 'Dr', 'first_name' => 'Claudine', 'last_name' => 'Ngoua', 'gender' => 'female', 'specialties' => ['NEUR'], 'structures' => ['chu', 'chambrier'], 'fee_min' => 30000, 'fee_max' => 60000, 'bio' => 'Neurologue. Consultations CHU et Polyclinique Chambrier.', 'teleconsult' => true, 'languages' => ['fr']],
        ];
    }
}
