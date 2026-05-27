<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_record_grants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained('practitioners')->cascadeOnDelete();
            $table->foreignId('source_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->jsonb('scope')->nullable();
            $table->timestampTz('granted_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->unsignedInteger('access_count')->default(0);
            $table->timestampTz('last_accessed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        DB::statement('
            CREATE UNIQUE INDEX medical_record_grants_active_unique
            ON medical_record_grants (patient_id, practitioner_id)
            WHERE revoked_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS medical_record_grants_active_unique');
        Schema::dropIfExists('medical_record_grants');
    }
};
