<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Database\Seeders;

use App\Modules\Referentiel\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Seeds the catalogue of medical services, treatments and exams.
 */
final class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->services() as $service) {
            Service::firstOrCreate(['code' => $service['code']], $service);
        }
    }

    /**
     * @return list<array{code: string, category: string, name_fr: string, name_en: string}>
     */
    private function services(): array
    {
        return [
            // === Prestations ===
            ['code' => 'CONSULT-GEN', 'category' => 'prestation', 'name_fr' => 'Consultation générale', 'name_en' => 'General consultation'],
            ['code' => 'CONSULT-SPE', 'category' => 'prestation', 'name_fr' => 'Consultation spécialisée', 'name_en' => 'Specialist consultation'],
            ['code' => 'HOSPIT', 'category' => 'prestation', 'name_fr' => 'Hospitalisation', 'name_en' => 'Hospitalization'],
            ['code' => 'URGENCE', 'category' => 'prestation', 'name_fr' => 'Urgences', 'name_en' => 'Emergency'],
            ['code' => 'CHIRURGIE', 'category' => 'prestation', 'name_fr' => 'Chirurgie', 'name_en' => 'Surgery'],
            ['code' => 'MATERNITE', 'category' => 'prestation', 'name_fr' => 'Maternité / Accouchement', 'name_en' => 'Maternity / Delivery'],
            ['code' => 'TELECONSULT', 'category' => 'prestation', 'name_fr' => 'Téléconsultation', 'name_en' => 'Teleconsultation'],
            ['code' => 'DOMICILE', 'category' => 'prestation', 'name_fr' => 'Soins à domicile', 'name_en' => 'Home care'],
            ['code' => 'GARDE', 'category' => 'prestation', 'name_fr' => 'Service de garde', 'name_en' => 'On-call service'],
            ['code' => 'VACCINATION', 'category' => 'prestation', 'name_fr' => 'Vaccination', 'name_en' => 'Vaccination'],

            // === Soins ===
            ['code' => 'INJECTION', 'category' => 'soin', 'name_fr' => 'Injection', 'name_en' => 'Injection'],
            ['code' => 'PERFUSION', 'category' => 'soin', 'name_fr' => 'Perfusion', 'name_en' => 'Infusion'],
            ['code' => 'PANSEMENT', 'category' => 'soin', 'name_fr' => 'Pansement', 'name_en' => 'Dressing'],
            ['code' => 'SUTURE', 'category' => 'soin', 'name_fr' => 'Suture', 'name_en' => 'Suture'],
            ['code' => 'KINE', 'category' => 'soin', 'name_fr' => 'Kinésithérapie', 'name_en' => 'Physiotherapy'],
            ['code' => 'DIALYSE', 'category' => 'soin', 'name_fr' => 'Dialyse', 'name_en' => 'Dialysis'],

            // === Examens ===
            ['code' => 'RADIO', 'category' => 'examen', 'name_fr' => 'Radiographie', 'name_en' => 'X-ray'],
            ['code' => 'ECHO', 'category' => 'examen', 'name_fr' => 'Échographie', 'name_en' => 'Ultrasound'],
            ['code' => 'SCANNER', 'category' => 'examen', 'name_fr' => 'Scanner / TDM', 'name_en' => 'CT scan'],
            ['code' => 'IRM', 'category' => 'examen', 'name_fr' => 'IRM', 'name_en' => 'MRI'],
            ['code' => 'ECG', 'category' => 'examen', 'name_fr' => 'Électrocardiogramme', 'name_en' => 'Electrocardiogram'],
            ['code' => 'BILAN-SANG', 'category' => 'examen', 'name_fr' => 'Bilan sanguin', 'name_en' => 'Blood test'],
            ['code' => 'ANALYSE-URINE', 'category' => 'examen', 'name_fr' => 'Analyse d\'urine', 'name_en' => 'Urine test'],
            ['code' => 'DEPIST-PALU', 'category' => 'examen', 'name_fr' => 'Dépistage paludisme (TDR)', 'name_en' => 'Malaria rapid test'],
            ['code' => 'DEPIST-VIH', 'category' => 'examen', 'name_fr' => 'Dépistage VIH', 'name_en' => 'HIV test'],
            ['code' => 'MAMMO', 'category' => 'examen', 'name_fr' => 'Mammographie', 'name_en' => 'Mammography'],
        ];
    }
}
