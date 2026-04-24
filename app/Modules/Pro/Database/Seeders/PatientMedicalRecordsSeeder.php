<?php

declare(strict_types=1);

namespace App\Modules\Pro\Database\Seeders;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Pro\Models\CareAct;
use App\Modules\Pro\Models\Consultation;
use App\Modules\Pro\Models\ExamRequest;
use App\Modules\Pro\Models\Prescription;
use App\Modules\Pro\Models\PrescriptionItem;
use App\Modules\Pro\Models\Treatment;
use App\Modules\Referentiel\Models\Medication;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic medical records for the demo patient (patient@hosto.ga)
 * to demonstrate the "Mon dossier" interface.
 */
final class PatientMedicalRecordsSeeder extends Seeder
{
    public function run(): void
    {
        $patient = User::where('email', 'patient@hosto.ga')->firstOrFail();
        $practitioners = Practitioner::with('structures')->get();
        $medications = Medication::all();

        if ($practitioners->isEmpty()) {
            return;
        }

        // --- Appointments ---
        $this->seedAppointments($patient, $practitioners);

        // --- Consultations with full medical records ---
        $this->seedConsultations($patient, $practitioners, $medications);
    }

    /**
     * @param  Collection<int, Practitioner>  $practitioners
     */
    private function seedAppointments(User $patient, Collection $practitioners): void
    {
        $statuses = ['confirmed', 'pending', 'completed', 'completed', 'completed'];

        foreach ($statuses as $i => $status) {
            $prac = $practitioners->random();
            $structure = $prac->structures->first();
            if (! $structure) {
                continue;
            }

            $slot = TimeSlot::where('practitioner_id', $prac->id)
                ->where('hosto_id', $structure->id)
                ->where('is_available', true)
                ->first();

            if (! $slot) {
                continue;
            }

            Appointment::firstOrCreate(
                ['patient_id' => $patient->id, 'time_slot_id' => $slot->id],
                [
                    'practitioner_id' => $prac->id,
                    'hosto_id' => $structure->id,
                    'status' => $status,
                    'reason' => $this->reasons()[$i] ?? 'Consultation de suivi',
                    'is_teleconsultation' => $slot->is_teleconsultation,
                    'confirmed_at' => $status !== 'pending' ? now()->subDays(10 - $i) : null,
                    'completed_at' => $status === 'completed' ? now()->subDays(8 - $i) : null,
                ],
            );

            $slot->update(['is_available' => false]);
        }
    }

    /**
     * @param  Collection<int, Practitioner>  $practitioners
     * @param  Collection<int, Medication>  $medications
     */
    private function seedConsultations(User $patient, Collection $practitioners, Collection $medications): void
    {
        $records = $this->medicalRecords();

        foreach ($records as $idx => $record) {
            $prac = $practitioners[$idx % $practitioners->count()];
            $structure = $prac->structures->first();
            if (! $structure) {
                continue;
            }

            $consultation = Consultation::firstOrCreate(
                ['patient_id' => $patient->id, 'practitioner_id' => $prac->id, 'motif' => $record['motif']],
                [
                    'hosto_id' => $structure->id,
                    'status' => 'completed',
                    'anamnesis' => $record['anamnesis'],
                    'examen_clinique' => $record['examen'],
                    'diagnostic' => $record['diagnostic'],
                    'diagnostic_code' => $record['code_cim'] ?? null,
                    'conduite_a_tenir' => $record['conduite'],
                    'vitals' => $record['vitals'],
                    'started_at' => now()->subDays(30 - $idx * 5),
                    'completed_at' => now()->subDays(30 - $idx * 5)->addHour(),
                ],
            );

            // Prescriptions
            if (! empty($record['prescriptions'])) {
                $prescription = Prescription::firstOrCreate(
                    ['consultation_id' => $consultation->id],
                    [
                        'practitioner_id' => $prac->id,
                        'patient_id' => $patient->id,
                        'status' => 'active',
                        'reference' => Prescription::generateReference(),
                        'valid_until' => now()->addMonths(3),
                    ],
                );

                foreach ($record['prescriptions'] as $order => $rx) {
                    $med = $medications->where('dci', $rx['dci'])->first();
                    PrescriptionItem::firstOrCreate(
                        ['prescription_id' => $prescription->id, 'medication_name' => $rx['dci']],
                        [
                            'medication_id' => $med?->id,
                            'dosage' => $rx['dosage'],
                            'posology' => $rx['posology'],
                            'duration' => $rx['duration'],
                            'quantity' => $rx['quantity'] ?? null,
                            'instructions' => $rx['instructions'] ?? null,
                            'display_order' => $order,
                        ],
                    );
                }
            }

            // Exam requests
            if (! empty($record['exams'])) {
                foreach ($record['exams'] as $exam) {
                    ExamRequest::firstOrCreate(
                        ['consultation_id' => $consultation->id, 'exam_type' => $exam['type']],
                        [
                            'practitioner_id' => $prac->id,
                            'patient_id' => $patient->id,
                            'status' => $exam['status'] ?? 'completed',
                            'urgency' => $exam['urgency'] ?? 'normal',
                            'clinical_info' => $exam['info'] ?? null,
                            'results' => $exam['results'] ?? null,
                            'completed_at' => ($exam['status'] ?? 'completed') === 'completed' ? now()->subDays(25 - $idx * 5) : null,
                        ],
                    );
                }
            }

            // Care acts
            if (! empty($record['soins'])) {
                foreach ($record['soins'] as $soin) {
                    CareAct::firstOrCreate(
                        ['consultation_id' => $consultation->id, 'care_type' => $soin['type']],
                        [
                            'practitioner_id' => $prac->id,
                            'patient_id' => $patient->id,
                            'description' => $soin['description'],
                            'status' => 'completed',
                            'performed_at' => now()->subDays(28 - $idx * 5),
                        ],
                    );
                }
            }

            // Treatments
            if (! empty($record['traitements'])) {
                foreach ($record['traitements'] as $trait) {
                    Treatment::firstOrCreate(
                        ['consultation_id' => $consultation->id, 'type' => $trait['type']],
                        [
                            'practitioner_id' => $prac->id,
                            'patient_id' => $patient->id,
                            'description' => $trait['description'],
                            'status' => $trait['status'] ?? 'active',
                            'instructions' => $trait['instructions'] ?? null,
                            'frequency' => $trait['frequency'] ?? null,
                            'duration' => $trait['duration'] ?? null,
                            'start_date' => now()->subDays(28 - $idx * 5),
                            'end_date' => isset($trait['duration']) ? now()->addDays(14) : null,
                        ],
                    );
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function reasons(): array
    {
        return [
            'Douleur thoracique depuis 3 jours',
            'Controle annuel',
            'Fievre et maux de tete persistants',
            'Suivi traitement hypertension',
            'Douleur articulaire genou droit',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function medicalRecords(): array
    {
        return [
            // Consultation 1 — Paludisme
            [
                'motif' => 'Fievre elevee, frissons, maux de tete depuis 2 jours',
                'anamnesis' => 'Patient de 35 ans, se plaint de fievre a 39.5°C depuis 48h, frissons, cephalees frontales, courbatures diffuses. Pas de voyage recent. Vit a Libreville.',
                'examen' => 'Temperature 39.2°C, FC 98/min, TA 120/75. Abdomen souple, rate palpable. Pas de raideur de nuque.',
                'diagnostic' => 'Paludisme a Plasmodium falciparum',
                'code_cim' => 'B50.9',
                'conduite' => 'Traitement antipaludeen ACT (Artemether-Lumefantrine). Repos. Hydratation. Controle J3.',
                'vitals' => ['temperature' => '39.2', 'heart_rate' => '98', 'blood_pressure' => '120/75', 'weight' => '78'],
                'prescriptions' => [
                    ['dci' => 'Artemether-Lumefantrine', 'dosage' => '80/480mg', 'posology' => '4 comprimes 2x/jour', 'duration' => '3 jours', 'quantity' => 24],
                    ['dci' => 'Paracetamol', 'dosage' => '1000mg', 'posology' => '1-1-1', 'duration' => '5 jours', 'quantity' => 15, 'instructions' => 'En cas de fievre ou douleur'],
                ],
                'exams' => [
                    ['type' => 'Goutte epaisse', 'status' => 'completed', 'info' => 'Suspicion paludisme', 'results' => 'Positif — Plasmodium falciparum, densite 12000/uL'],
                    ['type' => 'NFS', 'status' => 'completed', 'info' => 'Bilan complementaire', 'results' => 'Hb 11.2g/dL, plaquettes 98000'],
                ],
                'soins' => [],
                'traitements' => [
                    ['type' => 'rest', 'description' => 'Repos strict pendant 3 jours', 'instructions' => 'Eviter tout effort physique', 'duration' => '3 jours'],
                    ['type' => 'diet', 'description' => 'Hydratation abondante', 'instructions' => 'Boire au moins 2L d\'eau par jour', 'frequency' => 'quotidien', 'duration' => '7 jours'],
                ],
            ],

            // Consultation 2 — Hypertension
            [
                'motif' => 'Cephalees matinales recurrentes, vertiges',
                'anamnesis' => 'Patient connu hypertendu depuis 2 ans. Traitement irregulier. Cephalees occipitales matinales depuis 2 semaines. Vertiges a la station debout prolongee.',
                'examen' => 'TA 165/100 mmHg, FC 82/min. Fond d\'oeil : retrecissement arteriole. Auscultation cardiaque : B1B2 reguliers, souffle systolique 2/6.',
                'diagnostic' => 'Hypertension arterielle essentielle stade 2, mal controlee',
                'code_cim' => 'I10',
                'conduite' => 'Ajustement traitement antihypertenseur. Regime hyposode. Controle a 1 mois. ECG de controle.',
                'vitals' => ['temperature' => '36.8', 'heart_rate' => '82', 'blood_pressure' => '165/100', 'weight' => '85', 'height' => '175'],
                'prescriptions' => [
                    ['dci' => 'Amlodipine', 'dosage' => '10mg', 'posology' => '1 comprime le matin', 'duration' => '30 jours', 'quantity' => 30],
                ],
                'exams' => [
                    ['type' => 'ECG', 'status' => 'completed', 'info' => 'Bilan cardiaque HTA', 'results' => 'Rythme sinusal, HVG electrique moderee'],
                    ['type' => 'Bilan renal', 'status' => 'completed', 'info' => 'Fonction renale', 'results' => 'Creatinine 12mg/L, DFG 85 mL/min — normal'],
                ],
                'soins' => [],
                'traitements' => [
                    ['type' => 'diet', 'description' => 'Regime hyposode strict', 'instructions' => 'Moins de 6g de sel par jour. Eviter les aliments transformes.', 'frequency' => 'quotidien'],
                    ['type' => 'lifestyle', 'description' => 'Activite physique reguliere', 'instructions' => 'Marche rapide 30 minutes, 5 fois par semaine', 'frequency' => '5x/semaine'],
                    ['type' => 'follow_up', 'description' => 'Controle tensionnel a domicile', 'instructions' => 'Mesurer la TA matin et soir, noter les valeurs', 'frequency' => '2x/jour'],
                ],
            ],

            // Consultation 3 — Infection urinaire
            [
                'motif' => 'Brulures mictionnelles, pollakiurie depuis 3 jours',
                'anamnesis' => 'Brulures mictionnelles intenses depuis 3 jours. Pollakiurie diurne et nocturne. Urines troubles. Pas de fievre. Pas d\'antecedent urinaire.',
                'examen' => 'Temperature 37.1°C, TA 118/72. Abdomen souple, sensibilite sus-pubienne. Fosses lombaires libres.',
                'diagnostic' => 'Infection urinaire basse non compliquee (cystite)',
                'code_cim' => 'N30.0',
                'conduite' => 'Antibiotherapie adaptee. Hydratation abondante. ECBU de controle si persistance des symptomes a J5.',
                'vitals' => ['temperature' => '37.1', 'heart_rate' => '76', 'blood_pressure' => '118/72', 'weight' => '78'],
                'prescriptions' => [
                    ['dci' => 'Cotrimoxazole', 'dosage' => '800/160mg', 'posology' => '1 comprime 2x/jour', 'duration' => '5 jours', 'quantity' => 10],
                ],
                'exams' => [
                    ['type' => 'ECBU', 'status' => 'completed', 'info' => 'Suspicion infection urinaire', 'results' => 'E. coli > 10^5, sensible cotrimoxazole'],
                    ['type' => 'Bandelette urinaire', 'status' => 'completed', 'info' => 'Depistage rapide', 'results' => 'Leucocytes +++, Nitrites +'],
                ],
                'soins' => [
                    ['type' => 'injection', 'description' => 'Injection antalgique IM (Ketoprofene 100mg)'],
                ],
                'traitements' => [
                    ['type' => 'diet', 'description' => 'Hydratation abondante', 'instructions' => 'Boire au moins 2L d\'eau par jour', 'duration' => '10 jours'],
                ],
            ],

            // Consultation 4 — Bilan annuel
            [
                'motif' => 'Bilan de sante annuel',
                'anamnesis' => 'Patient sans plainte particuliere. Vient pour un bilan de sante annuel. ATCD : paludisme (il y a 3 mois), HTA sous traitement. Pas de tabac, alcool occasionnel.',
                'examen' => 'Bon etat general. TA 135/85 (amelioree). IMC 27.8 (surpoids). Auscultation cardio-pulmonaire normale. Examen cutane sans particularite.',
                'diagnostic' => 'Bilan annuel satisfaisant. Surpoids a surveiller. HTA mieux controlee.',
                'code_cim' => 'Z00.0',
                'conduite' => 'Poursuite traitement HTA. Conseils hygiene-dietetiques. Bilan sanguin complet. Prochain controle dans 6 mois.',
                'vitals' => ['temperature' => '36.6', 'heart_rate' => '72', 'blood_pressure' => '135/85', 'weight' => '82', 'height' => '175'],
                'prescriptions' => [
                    ['dci' => 'Amlodipine', 'dosage' => '10mg', 'posology' => '1 comprime le matin', 'duration' => '90 jours', 'quantity' => 90],
                ],
                'exams' => [
                    ['type' => 'Bilan sanguin complet', 'status' => 'completed', 'info' => 'Bilan annuel', 'results' => 'Glycemie 0.95g/L, Cholesterol total 2.1g/L, HDL 0.55, LDL 1.30, Triglycerides 1.2g/L'],
                    ['type' => 'Hemogramme', 'status' => 'completed', 'info' => 'NFS de controle', 'results' => 'Hb 14.2g/dL, GB 6800, plaquettes 245000 — normal'],
                    ['type' => 'Bilan hepatique', 'status' => 'completed', 'info' => 'Fonction hepatique', 'results' => 'ASAT 25 UI/L, ALAT 28 UI/L — normal'],
                ],
                'soins' => [],
                'traitements' => [
                    ['type' => 'lifestyle', 'description' => 'Programme de perte de poids', 'instructions' => 'Objectif : perdre 5kg en 6 mois. Activite physique reguliere + regime equilibre.', 'frequency' => 'quotidien', 'duration' => '6 mois'],
                ],
            ],
        ];
    }
}
