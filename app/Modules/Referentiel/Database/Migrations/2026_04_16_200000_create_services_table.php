<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue des prestations et soins proposables par une structure.
 *
 * Trois catégories dans une seule table :
 *   - prestation : consultation, hospitalisation, chirurgie, urgence...
 *   - soin       : injection, pansement, suture, perfusion...
 *   - examen     : radiographie, échographie, bilan sanguin...
 *
 * Les produits/médicaments sont un domaine distinct (Phase 6 — Pharma)
 * avec des attributs spécifiques (DCI, dosage, stock) et ne sont PAS
 * modélisés ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->string('code', 30)->unique();
            $table->string('category', 30)->index(); // prestation, soin, examen
            $table->string('name_fr');
            $table->string('name_en');
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('display_order')->default(0);
        });

        SchemaBuilder::installUpdatedAtTrigger('services');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('services');
        Schema::dropIfExists('services');
    }
};
