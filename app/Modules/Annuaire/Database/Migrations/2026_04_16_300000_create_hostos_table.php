<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Main health structures table.
 *
 * "hostos" is the central entity of the Annuaire module.
 * Each row represents a hospital, clinic, pharmacy, lab, etc.
 *
 * A hosto can have:
 *   - multiple types         (via hosto_structure_type pivot)
 *   - multiple specialties   (via hosto_specialty pivot)
 *   - multiple services      (via hosto_service pivot with pricing)
 *
 * Geolocation via PostGIS geography(Point, 4326) for proximity search.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostos', function (Blueprint $table): void {
            SchemaBuilder::base($table);
            SchemaBuilder::syncable($table);

            $table->string('name');
            $table->string('slug')->unique();

            // Location
            $table->foreignId('city_id')->constrained('cities');
            $table->string('address')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('quarter')->nullable(); // quartier

            // Contact
            $table->string('phone', 30)->nullable();
            $table->string('phone2', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Description
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();

            // Operating details
            $table->boolean('is_public')->default(true); // public vs private
            $table->boolean('is_guard_service')->default(false); // service de garde
            $table->jsonb('opening_hours')->nullable(); // {mon: {open: "08:00", close: "18:00"}, ...}
            $table->string('emergency_phone', 30)->nullable();

            // Media
            $table->string('logo_url')->nullable();
            $table->string('cover_image_url')->nullable();

            // Moderation
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_verified')->default(false)->index();
            $table->timestampTz('verified_at')->nullable();
            $table->uuid('verified_by')->nullable();

            // Search optimization
            $table->index('name');
            $table->index('city_id');
        });

        SchemaBuilder::installUpdatedAtTrigger('hostos');

        // PostGIS geography column for proximity search.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE hostos ADD COLUMN location geography(Point, 4326) NULL');
            DB::statement('CREATE INDEX hostos_location_gist ON hostos USING GIST (location)');

            // Trigram index for fuzzy name search.
            DB::statement('CREATE INDEX hostos_name_trgm ON hostos USING GIN (name gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('hostos');
        Schema::dropIfExists('hostos');
    }
};
