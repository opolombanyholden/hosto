<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lab module: results, panels, sample tracking.
 *
 * Extends exam_requests (Phase 5) with lab-side workflow:
 *   - Lab receives the request
 *   - Schedules and collects the sample
 *   - Runs analyses and enters results
 *   - Validates and transmits to the prescribing doctor
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Lab results (linked to exam_requests) ---
        Schema::create('lab_results', function (Blueprint $table): void {
            SchemaBuilder::base($table);
            SchemaBuilder::syncable($table);

            $table->foreignId('exam_request_id')->constrained('exam_requests');
            $table->foreignId('hosto_id')->constrained('hostos'); // the lab
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('performed_by_id')->nullable()->constrained('users')->nullOnDelete(); // lab tech

            $table->string('reference', 30)->unique(); // LAB-2026-000001
            $table->string('status', 20)->default('received')->index();
            // received → sample_collected → in_progress → completed → validated → transmitted

            $table->timestampTz('sample_collected_at')->nullable();
            $table->timestampTz('analysis_started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('validated_at')->nullable();
            $table->uuid('validated_by')->nullable(); // biologist who validated

            $table->text('conclusion')->nullable(); // overall interpretation
            $table->text('notes')->nullable();

            $table->index(['patient_id', 'status']);
            $table->index(['hosto_id', 'status']);
        });

        SchemaBuilder::installUpdatedAtTrigger('lab_results');

        // --- Lab result items (individual test values) ---
        Schema::create('lab_result_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lab_result_id')->constrained('lab_results')->cascadeOnDelete();

            $table->string('test_name'); // ex: Glycemie, Hemoglobine, VGM
            $table->string('test_code', 30)->nullable(); // LOINC code if available
            $table->string('value'); // the measured value
            $table->string('unit', 30)->nullable(); // g/L, mmol/L, %
            $table->string('reference_range')->nullable(); // ex: 0.70 - 1.10
            $table->string('flag', 10)->nullable(); // N (normal), H (high), L (low), C (critical)
            $table->text('comment')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_result_items');
        SchemaBuilder::dropUpdatedAtTrigger('lab_results');
        Schema::dropIfExists('lab_results');
    }
};
