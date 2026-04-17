<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Types of health structures (hospital, clinic, pharmacy, lab, etc.).
 *
 * Used to categorize structures in the Annuaire module (Phase 1.3).
 * Bilingual FR/EN following the convention established in ADR 0008.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structure_types', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->string('slug', 50)->unique();
            $table->string('name_fr');
            $table->string('name_en');
            $table->string('icon', 50)->nullable();
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('display_order')->default(0);
        });

        SchemaBuilder::installUpdatedAtTrigger('structure_types');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('structure_types');
        Schema::dropIfExists('structure_types');
    }
};
