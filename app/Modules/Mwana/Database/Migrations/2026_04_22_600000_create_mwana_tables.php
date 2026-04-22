<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mwana module tables: pregnancies, prenatal visits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pregnancies', function (Blueprint $table): void {
            SchemaBuilder::base($table);
            SchemaBuilder::syncable($table);

            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('practitioner_id')->nullable()->constrained('practitioners')->nullOnDelete();
            $table->foreignId('hosto_id')->nullable()->constrained('hostos')->nullOnDelete();

            $table->string('status', 20)->default('active')->index();
            // active, delivered, complicated, lost
            $table->date('due_date');
            $table->date('actual_delivery_date')->nullable();
            $table->string('delivery_type')->nullable(); // natural, cesarean, assisted
            $table->unsignedInteger('baby_weight_grams')->nullable();
            $table->string('baby_gender')->nullable();
            $table->text('notes')->nullable();

            $table->index(['patient_id', 'status']);
        });

        SchemaBuilder::installUpdatedAtTrigger('pregnancies');

        Schema::create('prenatal_visits', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('pregnancy_id')->constrained('pregnancies')->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained('practitioners');

            $table->date('visit_date');
            $table->unsignedSmallInteger('week_of_pregnancy');
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->string('blood_pressure')->nullable();
            $table->unsignedInteger('baby_heartbeat')->nullable();
            $table->text('notes')->nullable();

            $table->index(['pregnancy_id', 'visit_date']);
        });

        SchemaBuilder::installUpdatedAtTrigger('prenatal_visits');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('prenatal_visits');
        Schema::dropIfExists('prenatal_visits');
        SchemaBuilder::dropUpdatedAtTrigger('pregnancies');
        Schema::dropIfExists('pregnancies');
    }
};
