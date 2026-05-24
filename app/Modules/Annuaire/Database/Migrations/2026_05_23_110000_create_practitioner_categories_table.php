<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practitioner_categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 40)->unique();
            $table->string('name_fr', 120);
            $table->string('name_en', 120)->nullable();
            $table->text('description_fr')->nullable();
            $table->string('icon_name', 40)->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->foreignId('parent_category_id')->nullable()
                ->constrained('practitioner_categories')->nullOnDelete();
            $table->boolean('is_medical')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletesTz();
            $table->index(['is_active', 'display_order']);
            $table->index('parent_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner_categories');
    }
};
