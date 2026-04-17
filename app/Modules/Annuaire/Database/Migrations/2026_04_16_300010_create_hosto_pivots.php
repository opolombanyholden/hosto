<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot tables for the N:N relationships of a health structure.
 *
 * A structure can simultaneously be:
 *   - hospital AND laboratory (multiple types)
 *   - offer cardiology AND pediatrics (multiple specialties)
 *   - provide consultation, hospitalization, injections (multiple services)
 *
 * Pricing is stored on hosto_service (not on services) because
 * tariffs vary per structure.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Types multiples par structure ---
        Schema::create('hosto_structure_type', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hosto_id')->constrained('hostos')->cascadeOnDelete();
            $table->foreignId('structure_type_id')->constrained('structure_types')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false); // one type is the "main" display type
            $table->unsignedSmallInteger('display_order')->default(0);

            $table->unique(['hosto_id', 'structure_type_id']);
        });

        // --- Spécialités proposées ---
        Schema::create('hosto_specialty', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hosto_id')->constrained('hostos')->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained('specialties')->cascadeOnDelete();
            $table->unsignedSmallInteger('display_order')->default(0);

            $table->unique(['hosto_id', 'specialty_id']);
        });

        // --- Services/prestations/soins proposés + tarifs ---
        Schema::create('hosto_service', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hosto_id')->constrained('hostos')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();

            // Tarification par structure (les tarifs varient d'une structure à l'autre)
            $table->unsignedInteger('tarif_min')->nullable(); // en unité monétaire (XAF centimes)
            $table->unsignedInteger('tarif_max')->nullable();
            $table->string('currency_code', 3)->default('XAF');

            $table->boolean('is_available')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);

            $table->unique(['hosto_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosto_service');
        Schema::dropIfExists('hosto_specialty');
        Schema::dropIfExists('hosto_structure_type');
    }
};
