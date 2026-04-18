<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Medications referential catalog.
 *
 * Two-level structure:
 *   - medications       : generic drugs by DCI (Dénomination Commune Internationale)
 *   - medication_brands : commercial brand names for each DCI
 *
 * This is a READ-ONLY referential. Actual stock/pricing per pharmacy
 * will be in the Pharma module (Phase 6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->string('dci'); // Dénomination Commune Internationale (generic name)
            $table->string('dci_en')->nullable(); // English INN
            $table->string('therapeutic_class')->nullable(); // ex: Antalgique, Antibiotique
            $table->string('therapeutic_class_en')->nullable();
            $table->string('dosage_form', 50)->nullable(); // comprimé, gélule, sirop, injectable
            $table->string('dosage_form_en', 50)->nullable();
            $table->string('strength')->nullable(); // ex: 500mg, 1g
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('prescription_required')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('display_order')->default(0);
        });

        SchemaBuilder::installUpdatedAtTrigger('medications');

        // Trigram index for fuzzy search on DCI name.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX medications_dci_trgm ON medications USING GIN (dci gin_trgm_ops)');
        }

        Schema::create('medication_brands', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('medication_id')->constrained('medications')->cascadeOnDelete();
            $table->string('brand_name');
            $table->string('manufacturer')->nullable();
            $table->string('country_origin', 2)->nullable(); // ISO 3166-1
            $table->boolean('is_active')->default(true);

            $table->index('brand_name');
        });

        SchemaBuilder::installUpdatedAtTrigger('medication_brands');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('medication_brands');
        Schema::dropIfExists('medication_brands');
        SchemaBuilder::dropUpdatedAtTrigger('medications');
        Schema::dropIfExists('medications');
    }
};
