<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-configurable featured sections per structure.
 *
 * Examples: ["catalogue_medicaments", "examens", "urgences", "teleconsultation",
 *            "prestations", "soins", "specialites", "medecins"]
 *
 * If null, fallback to automatic detection by structure type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostos', function (Blueprint $table): void {
            $table->jsonb('featured_sections')->nullable()->after('tiktok_url');
        });
    }

    public function down(): void
    {
        Schema::table('hostos', function (Blueprint $table): void {
            $table->dropColumn('featured_sections');
        });
    }
};
