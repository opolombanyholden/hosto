<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add accepted_insurances (jsonb) to hostos.
 *
 * Stores the list of insurance providers accepted by each structure.
 * Example: ["CNAMGS", "ASCOMA", "OGAR", "AXA"]
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostos', function (Blueprint $table): void {
            $table->jsonb('accepted_insurances')->nullable()->after('is_partner');
        });
    }

    public function down(): void
    {
        Schema::table('hostos', function (Blueprint $table): void {
            $table->dropColumn('accepted_insurances');
        });
    }
};
