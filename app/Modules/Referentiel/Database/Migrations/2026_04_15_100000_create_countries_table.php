<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Countries referential.
 *
 * Indexed by ISO 3166-1 alpha-2 code (e.g. "GA" for Gabon).
 * API routes use iso2 as the public identifier rather than uuid.
 *
 * @see docs/adr/0008-referentiels-geographiques.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->string('iso2', 2)->unique();
            $table->string('iso3', 3)->unique();
            $table->unsignedSmallInteger('iso_numeric')->nullable();

            $table->string('name_fr');
            $table->string('name_en');
            $table->string('name_local')->nullable();

            $table->string('phone_prefix', 10)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('default_language', 5)->default('fr');

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('display_order')->default(0);
        });

        SchemaBuilder::installUpdatedAtTrigger('countries');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('countries');
        Schema::dropIfExists('countries');
    }
};
