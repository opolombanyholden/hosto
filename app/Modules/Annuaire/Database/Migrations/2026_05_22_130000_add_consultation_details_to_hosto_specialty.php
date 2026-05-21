<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosto_specialty', function (Blueprint $table): void {
            $table->text('consultation_conditions')->nullable()->after('display_order');
            $table->string('consultation_hours', 255)->nullable()->after('consultation_conditions');
            $table->string('consultation_location', 255)->nullable()->after('consultation_hours');
            $table->unsignedInteger('tarif_min')->nullable()->after('consultation_location');
            $table->unsignedInteger('tarif_max')->nullable()->after('tarif_min');
            $table->string('currency_code', 3)->default('XAF')->after('tarif_max');
        });
    }

    public function down(): void
    {
        Schema::table('hosto_specialty', function (Blueprint $table): void {
            $table->dropColumn([
                'consultation_conditions', 'consultation_hours', 'consultation_location',
                'tarif_min', 'tarif_max', 'currency_code',
            ]);
        });
    }
};
