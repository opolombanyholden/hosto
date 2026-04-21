<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Treatments (plans therapeutiques) and care acts (soins).
 *
 * Both are optional steps in the consultation workflow:
 *   - Step 5: care_acts (soins ponctuels ou en series)
 *   - Step 6: treatments (plans therapeutiques sur la duree)
 *
 * @see docs/adr/0013-workflow-consultation-dpe.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Care acts (soins) ---
        Schema::create('care_acts', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained('practitioners'); // prescripteur
            $table->foreignId('performed_by_id')->nullable()->constrained('practitioners')->nullOnDelete(); // executant
            $table->foreignId('patient_id')->constrained('users');

            $table->string('care_type', 50); // injection, perfusion, pansement, suture, kine, dialyse...
            $table->text('description');
            $table->text('instructions')->nullable();

            $table->string('status', 20)->default('prescribed')->index();
            // prescribed → scheduled → performed → cancelled

            $table->timestampTz('scheduled_at')->nullable();
            $table->timestampTz('performed_at')->nullable();
            $table->text('notes')->nullable(); // notes post-acte

            $table->index(['patient_id', 'status']);
            $table->index(['consultation_id']);
        });

        SchemaBuilder::installUpdatedAtTrigger('care_acts');

        // --- Treatments (traitements / plans therapeutiques) ---
        Schema::create('treatments', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained('practitioners');
            $table->foreignId('patient_id')->constrained('users');

            $table->string('type', 30); // medication, diet, rest, rehabilitation, follow_up, lifestyle, other
            $table->text('description');
            $table->text('instructions')->nullable();
            $table->string('frequency')->nullable(); // ex: 3 fois/jour, 1 seance/semaine
            $table->string('duration')->nullable(); // ex: 7 jours, 3 mois, a vie

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->string('status', 20)->default('prescribed')->index();
            // prescribed → in_progress → completed → cancelled

            $table->index(['patient_id', 'status']);
            $table->index(['consultation_id']);
        });

        SchemaBuilder::installUpdatedAtTrigger('treatments');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('treatments');
        Schema::dropIfExists('treatments');
        SchemaBuilder::dropUpdatedAtTrigger('care_acts');
        Schema::dropIfExists('care_acts');
    }
};
