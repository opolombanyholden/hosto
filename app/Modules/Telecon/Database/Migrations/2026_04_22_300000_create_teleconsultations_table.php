<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teleconsultation sessions powered by Jitsi Meet.
 *
 * Each session links an appointment to a Jitsi room.
 * Room names are UUID-based (not guessable) for security.
 * JWT tokens can be generated for authenticated access (Phase 8+).
 *
 * Records: duration, who joined, chat messages (future).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teleconsultations', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->foreignId('practitioner_id')->constrained('practitioners');
            $table->foreignId('patient_id')->constrained('users');

            $table->string('room_name', 100)->unique(); // UUID-based Jitsi room
            $table->string('jitsi_domain')->default('meet.jit.si'); // public or self-hosted

            $table->string('status', 20)->default('scheduled')->index();
            // scheduled → in_progress → completed → cancelled → no_show

            $table->timestampTz('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->unsignedSmallInteger('actual_duration_minutes')->nullable();

            // Participants tracking
            $table->boolean('practitioner_joined')->default(false);
            $table->boolean('patient_joined')->default(false);

            // Recording consent (both must agree)
            $table->boolean('recording_consent_practitioner')->default(false);
            $table->boolean('recording_consent_patient')->default(false);

            $table->text('notes')->nullable(); // post-session notes

            $table->index(['practitioner_id', 'status']);
            $table->index(['patient_id', 'status']);
        });

        SchemaBuilder::installUpdatedAtTrigger('teleconsultations');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('teleconsultations');
        Schema::dropIfExists('teleconsultations');
    }
};
