<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Time slots and appointments.
 *
 * Flow:
 *   1. A practitioner defines recurring or one-off time slots
 *   2. A patient books an appointment on an available slot
 *   3. The practitioner confirms, reschedules or cancels
 *   4. Reminders are sent (J-1, H-2)
 *
 * Only partner structures allow online booking (cf ADR 0010).
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Time slots (creneaux) ---
        Schema::create('time_slots', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('practitioner_id')->constrained('practitioners')->cascadeOnDelete();
            $table->foreignId('hosto_id')->constrained('hostos')->cascadeOnDelete();

            $table->date('date');
            $table->time('start_time'); // 08:00
            $table->time('end_time');   // 08:30
            $table->unsignedSmallInteger('duration_minutes')->default(30);

            $table->boolean('is_available')->default(true)->index();
            $table->boolean('is_teleconsultation')->default(false);
            $table->unsignedInteger('fee')->nullable(); // XAF, overrides practitioner default

            $table->index(['practitioner_id', 'date', 'is_available']);
            $table->index(['hosto_id', 'date', 'is_available']);
        });

        SchemaBuilder::installUpdatedAtTrigger('time_slots');

        // --- Appointments (rendez-vous) ---
        Schema::create('appointments', function (Blueprint $table): void {
            SchemaBuilder::base($table);
            SchemaBuilder::syncable($table);

            $table->foreignId('time_slot_id')->constrained('time_slots');
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('practitioner_id')->constrained('practitioners');
            $table->foreignId('hosto_id')->constrained('hostos');

            $table->string('status', 20)->default('pending')->index();
            // pending → confirmed → completed
            // pending → cancelled_by_patient | cancelled_by_practitioner
            // confirmed → rescheduled → confirmed

            $table->string('reason', 255)->nullable(); // motif de consultation
            $table->text('notes')->nullable(); // notes du patient
            $table->boolean('is_teleconsultation')->default(false);

            // Cancellation / reschedule
            $table->text('cancellation_reason')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->uuid('cancelled_by')->nullable();

            // Confirmation
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('completed_at')->nullable();

            // Reminders
            $table->boolean('reminder_j1_sent')->default(false);
            $table->boolean('reminder_h2_sent')->default(false);

            $table->index(['patient_id', 'status']);
            $table->index(['practitioner_id', 'status']);
        });

        SchemaBuilder::installUpdatedAtTrigger('appointments');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('appointments');
        Schema::dropIfExists('appointments');
        SchemaBuilder::dropUpdatedAtTrigger('time_slots');
        Schema::dropIfExists('time_slots');
    }
};
