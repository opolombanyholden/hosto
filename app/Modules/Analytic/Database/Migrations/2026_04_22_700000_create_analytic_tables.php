<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics tables for aggregated, anonymized health data.
 *
 * NO nominative data. All records are aggregated by:
 *   - Region/city
 *   - Structure
 *   - Time period (day/week/month)
 *   - Pathology (CIM-10 code)
 *
 * Used by: Ministry of Health, WHO partners, structure managers.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Daily health statistics per structure ---
        Schema::create('health_stats_daily', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('hosto_id')->nullable()->constrained('hostos')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();

            $table->unsignedInteger('consultations_count')->default(0);
            $table->unsignedInteger('teleconsultations_count')->default(0);
            $table->unsignedInteger('appointments_count')->default(0);
            $table->unsignedInteger('prescriptions_count')->default(0);
            $table->unsignedInteger('exams_count')->default(0);
            $table->unsignedInteger('hospitalizations_count')->default(0);
            $table->unsignedInteger('births_count')->default(0);
            $table->unsignedInteger('deaths_count')->default(0);
            $table->unsignedInteger('vaccinations_count')->default(0);

            $table->unique(['date', 'hosto_id']);
            $table->index(['date', 'region_id']);
        });

        // --- Pathology tracking (epidemiological surveillance) ---
        Schema::create('pathology_stats', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->index();
            $table->string('diagnostic_code', 20)->index(); // CIM-10
            $table->string('diagnostic_label')->nullable();
            $table->foreignId('hosto_id')->nullable()->constrained('hostos')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();

            $table->unsignedInteger('cases_count')->default(0);
            $table->string('age_group', 20)->nullable(); // 0-5, 6-15, 16-25, 26-45, 46-65, 65+
            $table->string('gender', 10)->nullable(); // male, female, all

            $table->index(['diagnostic_code', 'date']);
            $table->index(['region_id', 'date']);
        });

        // --- Epidemiological alerts ---
        Schema::create('epi_alerts', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->string('alert_type', 30); // threshold_exceeded, outbreak, anomaly
            $table->string('diagnostic_code', 20)->nullable();
            $table->string('diagnostic_label')->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();

            $table->string('severity', 20)->default('info')->index(); // info, warning, critical
            $table->text('description');
            $table->unsignedInteger('cases_count')->default(0);
            $table->unsignedInteger('threshold')->nullable();
            $table->string('status', 20)->default('active')->index(); // active, acknowledged, resolved

            $table->timestampTz('detected_at');
            $table->timestampTz('acknowledged_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->uuid('acknowledged_by')->nullable();
        });

        SchemaBuilder::installUpdatedAtTrigger('epi_alerts');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('epi_alerts');
        Schema::dropIfExists('epi_alerts');
        Schema::dropIfExists('pathology_stats');
        Schema::dropIfExists('health_stats_daily');
    }
};
