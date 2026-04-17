<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cities referential.
 *
 * Indexed by UUID on the API side. Geo-located via a PostGIS
 * geography(Point, 4326) column for efficient proximity queries
 * (introduced in Phase 1.5).
 *
 * @see docs/adr/0008-referentiels-geographiques.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();

            $table->string('name_fr');
            $table->string('name_en');
            $table->string('name_local')->nullable();

            $table->boolean('is_capital')->default(false)->index();
            $table->unsignedBigInteger('population')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('display_order')->default(0);

            $table->index('name_fr');
            $table->index(['region_id', 'is_active']);
        });

        SchemaBuilder::installUpdatedAtTrigger('cities');

        // PostGIS geography column. Added via raw SQL because Laravel Schema
        // does not expose a native helper for PostGIS types.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE cities ADD COLUMN location geography(Point, 4326) NULL');
            DB::statement('CREATE INDEX cities_location_gist ON cities USING GIST (location)');
        }
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('cities');
        Schema::dropIfExists('cities');
    }
};
