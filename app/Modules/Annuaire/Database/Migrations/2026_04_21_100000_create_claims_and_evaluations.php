<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structure claims (registration workflow) and private evaluations.
 *
 * @see docs/adr/0009-workflow-enregistrement-structure-interactions.md
 * @see docs/adr/0012-verification-compte-workflow.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Structure claims (registration by owner) ---
        Schema::create('structure_claims', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hosto_id')->nullable()->constrained('hostos')->nullOnDelete();

            $table->string('status', 20)->default('draft')->index();
            // draft → submitted → under_review → approved / rejected / suspended

            // Structure info (for new structures not yet in hostos)
            $table->string('structure_name');
            $table->string('structure_type')->nullable(); // slug of structure_type
            $table->string('structure_city')->nullable();
            $table->string('structure_address')->nullable();
            $table->string('structure_phone', 30)->nullable();

            // Legal representative
            $table->string('representative_name');
            $table->string('representative_role')->nullable(); // Directeur, Gerant, etc.
            $table->string('registration_number')->nullable(); // RCCM

            // Review
            $table->text('rejection_reason')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('submitted_at')->nullable();
        });

        SchemaBuilder::installUpdatedAtTrigger('structure_claims');

        // --- Private evaluations ---
        Schema::create('hosto_evaluations', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hosto_id')->constrained('hostos')->cascadeOnDelete();

            $table->unsignedSmallInteger('score_accueil')->nullable(); // 1-5
            $table->unsignedSmallInteger('score_proprete')->nullable();
            $table->unsignedSmallInteger('score_competence')->nullable();
            $table->unsignedSmallInteger('score_delai')->nullable();
            $table->unsignedSmallInteger('score_global'); // 1-5 required
            $table->text('comment')->nullable();

            $table->index(['hosto_id', 'created_at']);
            $table->unique(['user_id', 'hosto_id']); // one evaluation per user per structure
        });

        SchemaBuilder::installUpdatedAtTrigger('hosto_evaluations');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('hosto_evaluations');
        Schema::dropIfExists('hosto_evaluations');
        SchemaBuilder::dropUpdatedAtTrigger('structure_claims');
        Schema::dropIfExists('structure_claims');
    }
};
