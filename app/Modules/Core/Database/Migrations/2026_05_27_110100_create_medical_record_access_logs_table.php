<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_record_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('grant_id')->constrained('medical_record_grants')->cascadeOnDelete();
            $table->foreignId('practitioner_user_id')->constrained('users');
            $table->timestampTz('accessed_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->jsonb('sections_accessed')->nullable();
            $table->index(['grant_id', 'accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_record_access_logs');
    }
};
