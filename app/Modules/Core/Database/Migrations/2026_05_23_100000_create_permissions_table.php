<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug', 80)->unique();
            $table->string('scope', 40);
            $table->string('name_fr', 255);
            $table->string('name_en', 255)->nullable();
            $table->text('description_fr')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index('scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
