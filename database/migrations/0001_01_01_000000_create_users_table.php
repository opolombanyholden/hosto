<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('phone_verified_at')->nullable();
            $table->string('password');

            // 2FA (Sanctum / custom)
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestampTz('two_factor_confirmed_at')->nullable();

            // Lockout tracking (progressive backoff on failed logins)
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestampTz('locked_until')->nullable();

            $table->rememberToken();
        });

        SchemaBuilder::installUpdatedAtTrigger('users');

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('users');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
