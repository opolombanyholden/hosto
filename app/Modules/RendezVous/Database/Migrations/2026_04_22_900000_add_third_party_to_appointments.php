<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add third-party beneficiary columns + specialty to appointments.
 *
 * @see docs/adr/0015-workflow-prise-rendez-vous.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('specialty_code', 20)->nullable()->after('hosto_id');
            $table->boolean('is_for_third_party')->default(false)->after('notes');
            $table->string('third_party_name')->nullable()->after('is_for_third_party');
            $table->unsignedSmallInteger('third_party_age')->nullable()->after('third_party_name');
            $table->string('third_party_gender', 10)->nullable()->after('third_party_age');
            $table->string('third_party_relation', 30)->nullable()->after('third_party_gender');
            $table->string('third_party_address')->nullable()->after('third_party_relation');
            $table->string('third_party_city')->nullable()->after('third_party_address');
            $table->string('third_party_phone', 30)->nullable()->after('third_party_city');
            $table->text('third_party_notes')->nullable()->after('third_party_phone');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn([
                'specialty_code', 'is_for_third_party', 'third_party_name',
                'third_party_age', 'third_party_gender', 'third_party_relation',
                'third_party_address', 'third_party_city', 'third_party_phone', 'third_party_notes',
            ]);
        });
    }
};
