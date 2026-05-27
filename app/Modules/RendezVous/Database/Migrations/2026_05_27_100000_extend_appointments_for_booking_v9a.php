<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('appointment_type', 20)->default('ordinaire');
            $table->string('consultation_mode', 20)->default('in_hospital');
            $table->boolean('share_medical_record')->default(false);
            $table->foreignId('third_party_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('visit_address')->nullable();
            $table->decimal('visit_lat', 10, 7)->nullable();
            $table->decimal('visit_lng', 10, 7)->nullable();
            $table->timestampTz('visit_geocoded_at')->nullable();
            $table->unsignedSmallInteger('visit_location_accuracy_m')->nullable();
            $table->timestampTz('requested_at')->nullable();
        });

        DB::statement("UPDATE appointments SET consultation_mode = 'telecon' WHERE is_teleconsultation = true");

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('is_teleconsultation');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->boolean('is_teleconsultation')->default(false);
        });
        DB::statement("UPDATE appointments SET is_teleconsultation = (consultation_mode = 'telecon')");
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['third_party_user_id']);
            $table->dropColumn([
                'appointment_type', 'consultation_mode', 'share_medical_record',
                'third_party_user_id', 'visit_address',
                'visit_lat', 'visit_lng', 'visit_geocoded_at', 'visit_location_accuracy_m',
                'requested_at',
            ]);
        });
    }
};
