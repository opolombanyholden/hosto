<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add identity, health, residence, security and PIN fields to users.
 *
 * The registration form stays minimal (name, email, password).
 * These fields are filled progressively via the "complete profile" flow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // --- Identity ---
            $table->string('nip', 30)->nullable()->unique()->after('phone');
            $table->string('id_document_type', 30)->nullable()->after('nip');
            // cni, passeport, carte_sejour, permis_conduire
            $table->string('id_document_number', 50)->nullable()->after('id_document_type');
            $table->date('date_of_birth')->nullable()->after('id_document_number');
            $table->string('gender', 10)->nullable()->after('date_of_birth');
            // male, female

            // --- Health ---
            $table->string('blood_group', 5)->nullable()->after('gender');
            // A+, A-, B+, B-, AB+, AB-, O+, O-

            // --- Residence ---
            $table->string('country_of_residence', 3)->nullable()->after('blood_group');
            // ISO 3166-1 alpha-2 (GA, CM, CG, etc.)
            $table->string('city_of_residence')->nullable()->after('country_of_residence');
            $table->string('address_of_residence')->nullable()->after('city_of_residence');

            // --- Photo ---
            $table->string('profile_photo_path')->nullable()->after('address_of_residence');

            // --- Security question ---
            $table->string('security_question')->nullable()->after('profile_photo_path');
            $table->string('security_answer')->nullable()->after('security_question');
            // stored hashed

            // --- Medical record PIN (4-6 digits, hashed) ---
            $table->string('medical_pin')->nullable()->after('security_answer');
            $table->timestampTz('medical_pin_set_at')->nullable()->after('medical_pin');

            // --- Profile completion tracking ---
            $table->timestampTz('profile_completed_at')->nullable()->after('medical_pin_set_at');
        });

        // --- Emergency contacts ---
        Schema::create('emergency_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('relation', 30)->nullable();
            // enfant, parent, conjoint, frere_soeur, ami, autre
            $table->boolean('can_access_medical_record')->default(false);
            $table->unsignedTinyInteger('priority')->default(1);
            // 1 = primary, 2 = secondary, etc.
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'nip', 'id_document_type', 'id_document_number',
                'date_of_birth', 'gender', 'blood_group',
                'country_of_residence', 'city_of_residence', 'address_of_residence',
                'profile_photo_path',
                'security_question', 'security_answer',
                'medical_pin', 'medical_pin_set_at',
                'profile_completed_at',
            ]);
        });
    }
};
