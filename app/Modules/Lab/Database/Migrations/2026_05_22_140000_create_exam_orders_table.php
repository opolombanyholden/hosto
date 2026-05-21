<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostos', function (Blueprint $table): void {
            $table->boolean('accepts_online_payment')->default(false)->after('is_partner');
            $table->boolean('accepts_on_site_payment')->default(true)->after('accepts_online_payment');
        });

        Schema::create('exam_orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hosto_id')->constrained('hostos')->cascadeOnDelete();
            $table->jsonb('exam_items');
            // [{service_id, code, name, tarif_min, tarif_max}, ...]
            $table->unsignedInteger('total_amount')->nullable();
            $table->string('currency_code', 3)->default('XAF');
            $table->string('payment_method', 20)->nullable();
            // 'online', 'on_site'
            $table->string('payment_status', 20)->default('pending');
            // 'pending', 'paid', 'failed', 'refunded'
            $table->string('status', 20)->default('pending');
            // 'pending', 'accepted', 'rejected', 'completed', 'cancelled'
            $table->text('notes')->nullable();
            $table->string('prescription_file_path')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['user_id', 'status']);
            $table->index(['hosto_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_orders');

        Schema::table('hostos', function (Blueprint $table): void {
            $table->dropColumn(['accepts_online_payment', 'accepts_on_site_payment']);
        });
    }
};
