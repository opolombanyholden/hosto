<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generic reference data table for all enum-type dropdown values.
 *
 * Categories: id_document_type, security_question, blood_group,
 * contact_relation, publication_type, care_type, treatment_type,
 * urgency_level, insurance_provider, country_code, gender
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_data', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 30)->index();
            $table->string('code', 50);
            $table->string('label_fr');
            $table->string('label_en')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['category', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_data');
    }
};
