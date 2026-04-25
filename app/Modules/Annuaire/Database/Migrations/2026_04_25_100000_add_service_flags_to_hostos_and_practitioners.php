<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add service flags: urgence, evacuation, soins a domicile.
 *
 * - Structures can offer emergency, evacuation, and home care services.
 * - Practitioners can independently offer home care services.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostos', function (Blueprint $table): void {
            $table->boolean('is_emergency_service')->default(false)->after('is_guard_service');
            $table->boolean('is_evacuation_service')->default(false)->after('is_emergency_service');
            $table->boolean('is_home_care_service')->default(false)->after('is_evacuation_service');
        });

        Schema::table('practitioners', function (Blueprint $table): void {
            $table->boolean('does_home_care')->default(false)->after('does_teleconsultation');
        });
    }

    public function down(): void
    {
        Schema::table('hostos', function (Blueprint $table): void {
            $table->dropColumn(['is_emergency_service', 'is_evacuation_service', 'is_home_care_service']);
        });

        Schema::table('practitioners', function (Blueprint $table): void {
            $table->dropColumn('does_home_care');
        });
    }
};
