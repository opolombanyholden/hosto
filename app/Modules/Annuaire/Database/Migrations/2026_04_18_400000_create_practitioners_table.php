<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Practitioners (médecins, spécialistes, infirmiers, etc.)
 *
 * A practitioner can:
 *   - work in multiple structures (via practitioner_hosto pivot)
 *   - have multiple specialties  (via practitioner_specialty pivot)
 *   - be linked to a user account (user_id, optional until Phase 3)
 *
 * Public profile visible in the annuaire without authentication.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practitioners', function (Blueprint $table): void {
            SchemaBuilder::base($table);
            SchemaBuilder::syncable($table);

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title', 20)->nullable(); // Dr, Pr, Me
            $table->string('first_name');
            $table->string('last_name');
            $table->string('slug')->unique();
            $table->string('gender', 10)->nullable(); // male, female

            // Professional info
            $table->string('registration_number')->nullable(); // numéro ordre des médecins
            $table->string('practitioner_type', 30); // doctor, pharmacist, nurse, lab_tech, midwife, dentist

            // Contact
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();

            // Bio
            $table->text('bio_fr')->nullable();
            $table->text('bio_en')->nullable();
            $table->string('profile_image_url')->nullable();

            // Languages spoken
            $table->jsonb('languages')->nullable(); // ["fr", "en", "fang"]

            // Consultation details
            $table->unsignedInteger('consultation_fee_min')->nullable(); // XAF
            $table->unsignedInteger('consultation_fee_max')->nullable();
            $table->boolean('accepts_new_patients')->default(true);
            $table->boolean('does_teleconsultation')->default(false);

            // Status
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_verified')->default(false)->index();

            $table->index('last_name');
            $table->index('practitioner_type');
        });

        SchemaBuilder::installUpdatedAtTrigger('practitioners');

        // Practitioner ↔ Structure (multi-structure)
        Schema::create('practitioner_hosto', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('practitioner_id')->constrained('practitioners')->cascadeOnDelete();
            $table->foreignId('hosto_id')->constrained('hostos')->cascadeOnDelete();
            $table->string('role_in_structure', 50)->nullable(); // titulaire, vacataire, consultant
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);

            $table->unique(['practitioner_id', 'hosto_id']);
        });

        // Practitioner ↔ Specialty (multi-spécialité)
        Schema::create('practitioner_specialty', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('practitioner_id')->constrained('practitioners')->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained('specialties')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);

            $table->unique(['practitioner_id', 'specialty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner_specialty');
        Schema::dropIfExists('practitioner_hosto');
        SchemaBuilder::dropUpdatedAtTrigger('practitioners');
        Schema::dropIfExists('practitioners');
    }
};
