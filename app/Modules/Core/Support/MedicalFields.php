<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

/**
 * Columns stored encrypted. Must stay in line with the EncryptedMedical casts:
 * hosto:medical-encrypt relies on this list to migrate and rotate existing data.
 */
final class MedicalFields
{
    /** @var array<string, list<string>> */
    public const COLUMNS = [
        'consultations' => ['motif', 'anamnesis', 'examen_clinique', 'diagnostic', 'diagnostic_code', 'conduite_a_tenir', 'notes_internes', 'vitals'],
        'prescriptions' => ['notes'],
        'prescription_items' => ['medication_name', 'dosage', 'posology', 'duration', 'instructions'],
        'exam_requests' => ['exam_type', 'clinical_info', 'results'],
        'treatments' => ['description', 'instructions', 'frequency', 'duration'],
        'care_acts' => ['description', 'instructions', 'notes'],
        'lab_results' => ['conclusion', 'notes'],
        'lab_result_items' => ['test_name', 'value', 'reference_range', 'comment'],
        'dispensations' => ['notes'],
        'dispensation_items' => ['medication_name', 'dosage', 'substitution_reason'],
        'appointments' => ['reason', 'notes', 'third_party_notes', 'cancellation_reason'],
        'users' => ['allergies', 'chronic_conditions', 'current_medications', 'surgical_history', 'family_history', 'disabilities'],
    ];
}
