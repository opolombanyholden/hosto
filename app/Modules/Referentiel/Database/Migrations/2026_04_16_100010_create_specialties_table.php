<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Medical specialties referential.
 *
 * Organized in a self-referencing tree (parent_id) to support:
 *   - Top-level categories (e.g. "Chirurgie")
 *   - Sub-specialties (e.g. "Chirurgie cardiaque")
 *
 * Used by:
 *   - Annuaire: specialties offered by a structure (Phase 1.3)
 *   - Pro: multi-specialty per practitioner (Phase 5)
 *   - Referential browsing: public API (this phase)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialties', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->string('code', 20)->unique();
            $table->string('name_fr');
            $table->string('name_en');
            $table->string('name_local')->nullable();
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('specialties')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('display_order')->default(0);

            $table->index('parent_id');
        });

        SchemaBuilder::installUpdatedAtTrigger('specialties');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('specialties');
        Schema::dropIfExists('specialties');
    }
};
