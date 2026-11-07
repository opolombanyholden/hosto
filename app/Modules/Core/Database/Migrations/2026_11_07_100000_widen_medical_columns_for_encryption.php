<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Encrypted values are longer than the original text and are not JSON, so the
 * length-limited and jsonb columns holding medical data become text.
 *
 * Run before hosto:medical-encrypt. Not reversible: once encrypted, a value no
 * longer fits the former column types.
 *
 * @see docs/adr/0016-chiffrement-donnees-medicales.md
 */
return new class extends Migration
{
    private const COLUMNS = [
        'consultations' => ['motif', 'diagnostic_code', 'vitals'],
        'prescription_items' => ['medication_name', 'dosage', 'posology', 'duration'],
        'exam_requests' => ['exam_type'],
        'treatments' => ['frequency', 'duration'],
        'lab_result_items' => ['test_name', 'value', 'reference_range'],
        'dispensation_items' => ['medication_name', 'dosage', 'substitution_reason'],
        'appointments' => ['reason'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE text USING {$column}::text");
            }
        }
    }

    public function down(): void
    {
        // Irreversible on purpose: see the class comment.
    }
};
