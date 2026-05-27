<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_links', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('inviter_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('context', 40);
            $table->unsignedBigInteger('context_id')->nullable();
            $table->string('phone_normalized', 20);
            $table->string('token', 64)->unique();
            $table->string('sent_via', 20)->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('accepted_at')->nullable();
            $table->foreignId('accepted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('expires_at');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index('phone_normalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_links');
    }
};
