<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccines', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 30)->unique();
            $table->string('oms_code', 30)->nullable();
            $table->string('name_fr');
            $table->string('name_en')->nullable();
            $table->string('manufacturer')->nullable();
            $table->jsonb('diseases')->nullable();
            $table->unsignedInteger('schedule_age_days')->nullable();
            $table->unsignedSmallInteger('doses_total')->default(1);
            $table->boolean('is_standardized')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index(['is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccines');
    }
};
