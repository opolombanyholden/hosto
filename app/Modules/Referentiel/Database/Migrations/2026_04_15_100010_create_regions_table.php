<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Regions referential (1st-level subdivision).
 *
 * "Region" is the generic term covering:
 *   - Gabon       : provinces (9)
 *   - Cameroon    : régions
 *   - Côte d'Ivoire : districts
 *   - etc.
 *
 * The `kind` column allows the UI to display the correct label per country.
 *
 * @see docs/adr/0008-referentiels-geographiques.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('code', 10); // ex: "G1" for Estuaire (Gabon)
            $table->string('kind', 30)->default('province'); // province, region, district, departement...

            $table->string('name_fr');
            $table->string('name_en');
            $table->string('name_local')->nullable();

            // Optional: capital city (set AFTER cities table seeded to avoid cyclic FK).
            $table->foreignId('capital_city_id')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('display_order')->default(0);

            $table->unique(['country_id', 'code']);
        });

        SchemaBuilder::installUpdatedAtTrigger('regions');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('regions');
        Schema::dropIfExists('regions');
    }
};
