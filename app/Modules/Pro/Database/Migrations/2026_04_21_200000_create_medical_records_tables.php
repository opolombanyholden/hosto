<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core medical records: consultations, prescriptions, exam requests.
 *
 * A consultation is the atomic unit of medical activity:
 *   - Created by a practitioner for a patient in a structure
 *   - Contains motif, examen clinique, diagnostic (CIM-10), conduite
 *   - Can generate prescriptions (ordonnances) and exam requests
 *
 * Access rules:
 *   - Practitioner sees only consultations at their active structure
 *   - Patient sees their own across all structures
 *   - Cross-structure access requires patient consent (Phase 3 consentement)
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Consultations ---
        Schema::create('consultations', function (Blueprint $table): void {
            SchemaBuilder::base($table);
            SchemaBuilder::syncable($table);

            $table->foreignId('practitioner_id')->constrained('practitioners');
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('hosto_id')->constrained('hostos');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();

            $table->string('status', 20)->default('in_progress')->index();
            // in_progress → completed → signed

            // Medical content
            $table->string('motif')->nullable(); // reason for visit
            $table->text('anamnesis')->nullable(); // patient history
            $table->text('examen_clinique')->nullable(); // clinical examination
            $table->text('diagnostic')->nullable(); // diagnostic conclusion
            $table->string('diagnostic_code', 20)->nullable(); // CIM-10 code
            $table->text('conduite_a_tenir')->nullable(); // treatment plan
            $table->text('notes_internes')->nullable(); // private notes (practitioner only)

            // Vitals (optional, captured during consultation)
            $table->jsonb('vitals')->nullable();
            // {poids_kg, taille_cm, temperature, tension_systolique, tension_diastolique, pouls, saturation_o2}

            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();

            $table->index(['patient_id', 'created_at']);
            $table->index(['practitioner_id', 'created_at']);
        });

        SchemaBuilder::installUpdatedAtTrigger('consultations');

        // --- Prescriptions (ordonnances) ---
        Schema::create('prescriptions', function (Blueprint $table): void {
            SchemaBuilder::base($table);
            SchemaBuilder::syncable($table);

            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained('practitioners');
            $table->foreignId('patient_id')->constrained('users');

            $table->string('status', 20)->default('active')->index();
            // active → dispensed → expired → cancelled

            $table->string('reference', 30)->unique(); // ORD-2026-000001
            $table->timestampTz('valid_until')->nullable(); // expiration date
            $table->text('notes')->nullable(); // general notes on the prescription

            $table->index(['patient_id', 'status']);
        });

        SchemaBuilder::installUpdatedAtTrigger('prescriptions');

        // --- Prescription items (lignes d'ordonnance) ---
        Schema::create('prescription_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->foreignId('medication_id')->nullable()->constrained('medications')->nullOnDelete();

            $table->string('medication_name'); // free text (in case not in catalog)
            $table->string('dosage')->nullable(); // ex: 500mg
            $table->string('posology')->nullable(); // ex: 1 comprime 3 fois/jour
            $table->string('duration')->nullable(); // ex: 7 jours
            $table->unsignedSmallInteger('quantity')->nullable();
            $table->text('instructions')->nullable(); // ex: a prendre pendant les repas
            $table->unsignedSmallInteger('display_order')->default(0);
        });

        // --- Exam requests (demandes d'examens) ---
        Schema::create('exam_requests', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained('practitioners');
            $table->foreignId('patient_id')->constrained('users');

            $table->string('status', 20)->default('requested')->index();
            // requested → scheduled → completed → cancelled

            $table->string('exam_type'); // ex: Bilan sanguin, Echographie
            $table->text('clinical_info')->nullable(); // clinical context for the lab
            $table->string('urgency', 20)->default('normal'); // normal, urgent
            $table->text('results')->nullable(); // filled by lab (Phase 7)
            $table->timestampTz('scheduled_at')->nullable();
            $table->timestampTz('completed_at')->nullable();

            $table->index(['patient_id', 'status']);
        });

        SchemaBuilder::installUpdatedAtTrigger('exam_requests');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('exam_requests');
        Schema::dropIfExists('exam_requests');
        Schema::dropIfExists('prescription_items');
        SchemaBuilder::dropUpdatedAtTrigger('prescriptions');
        Schema::dropIfExists('prescriptions');
        SchemaBuilder::dropUpdatedAtTrigger('consultations');
        Schema::dropIfExists('consultations');
    }
};
