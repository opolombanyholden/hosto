<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds patient identity snapshot columns to appointments.
 *
 * Rationale — an appointment is a legal/medical record. The requester's
 * identity as submitted at booking time must remain immutable even if
 * the User profile is later updated or deleted. Symmetric to the
 * existing third_party_* snapshot columns.
 *
 * Populated by AppointmentBookingService::book(). Never updated afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('patient_name_snapshot')->nullable()->after('patient_id');
            $table->string('patient_email_snapshot')->nullable()->after('patient_name_snapshot');
            $table->string('patient_phone_snapshot')->nullable()->after('patient_email_snapshot');
            $table->string('patient_phone_normalized_snapshot', 20)->nullable()->after('patient_phone_snapshot');
            $table->date('patient_dob_snapshot')->nullable()->after('patient_phone_normalized_snapshot');
            $table->string('patient_gender_snapshot', 10)->nullable()->after('patient_dob_snapshot');
            $table->string('patient_city_snapshot')->nullable()->after('patient_gender_snapshot');
            $table->string('patient_address_snapshot')->nullable()->after('patient_city_snapshot');
            $table->string('patient_nip_snapshot', 30)->nullable()->after('patient_address_snapshot');
            $table->string('patient_id_document_type_snapshot', 30)->nullable()->after('patient_nip_snapshot');
            $table->string('patient_id_document_number_snapshot', 50)->nullable()->after('patient_id_document_type_snapshot');
            $table->string('patient_blood_group_snapshot', 5)->nullable()->after('patient_id_document_number_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn([
                'patient_name_snapshot',
                'patient_email_snapshot',
                'patient_phone_snapshot',
                'patient_phone_normalized_snapshot',
                'patient_dob_snapshot',
                'patient_gender_snapshot',
                'patient_city_snapshot',
                'patient_address_snapshot',
                'patient_nip_snapshot',
                'patient_id_document_type_snapshot',
                'patient_id_document_number_snapshot',
                'patient_blood_group_snapshot',
            ]);
        });
    }
};
