<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedSmallInteger('height_cm')->nullable()->after('blood_group');
            $table->decimal('weight_kg', 5, 2)->nullable()->after('height_cm');
            $table->text('allergies')->nullable()->after('weight_kg');
            $table->text('chronic_conditions')->nullable()->after('allergies');
            $table->text('current_medications')->nullable()->after('chronic_conditions');
            $table->text('surgical_history')->nullable()->after('current_medications');
            $table->text('family_history')->nullable()->after('surgical_history');
            $table->text('disabilities')->nullable()->after('family_history');
            $table->boolean('organ_donor')->nullable()->after('disabilities');
            $table->string('smoking_status', 20)->nullable()->after('organ_donor');
            $table->string('alcohol_consumption', 20)->nullable()->after('smoking_status');
            $table->timestampTz('medical_bio_updated_at')->nullable()->after('alcohol_consumption');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'height_cm', 'weight_kg', 'allergies', 'chronic_conditions',
                'current_medications', 'surgical_history', 'family_history',
                'disabilities', 'organ_donor', 'smoking_status', 'alcohol_consumption',
                'medical_bio_updated_at',
            ]);
        });
    }
};
