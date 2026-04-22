<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HostoPlus module tables: health savings subscriptions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('user_id')->constrained('users');
            $table->string('plan_type', 30)->index(); // basic, family, premium_light, pay_as_you_go
            $table->string('status', 20)->default('active')->index();
            // active, expired, cancelled, suspended
            $table->unsignedInteger('amount');
            $table->string('currency_code', 3)->default('XAF');
            $table->timestampTz('started_at');
            $table->timestampTz('expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);

            $table->index(['user_id', 'status']);
        });

        SchemaBuilder::installUpdatedAtTrigger('subscriptions');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('subscriptions');
        Schema::dropIfExists('subscriptions');
    }
};
