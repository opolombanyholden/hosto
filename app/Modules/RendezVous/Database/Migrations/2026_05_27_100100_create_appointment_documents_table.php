<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('uploaded_by_id')->constrained('users');
            $table->string('original_name', 255);
            $table->string('stored_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->string('category', 40)->nullable();
            $table->boolean('keep_in_dpe')->default(false);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletesTz();
            $table->index('appointment_id');
            $table->index('uploaded_by_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_documents');
    }
};
