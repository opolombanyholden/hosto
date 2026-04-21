<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pharmacy module tables: stocks, dispensations, orders.
 *
 * A pharmacy (hosto with type=pharmacie) manages:
 *   - Stock per medication (pharmacy_stocks)
 *   - Dispensation of prescriptions (dispensations + items)
 *   - Sales with/without prescription
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Pharmacy stock (per medication per pharmacy) ---
        Schema::create('pharmacy_stocks', function (Blueprint $table): void {
            SchemaBuilder::base($table);
            SchemaBuilder::syncable($table);

            $table->foreignId('hosto_id')->constrained('hostos')->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained('medications');

            $table->unsignedInteger('quantity_in_stock')->default(0);
            $table->unsignedInteger('quantity_min_alert')->default(5); // seuil alerte rupture
            $table->unsignedInteger('unit_price')->nullable(); // XAF
            $table->string('currency_code', 3)->default('XAF');
            $table->boolean('is_available')->default(true)->index();
            $table->date('expiry_date')->nullable();

            $table->unique(['hosto_id', 'medication_id']);
            $table->index(['hosto_id', 'is_available']);
        });

        SchemaBuilder::installUpdatedAtTrigger('pharmacy_stocks');

        // --- Dispensation (ordonnance dispensee par une pharmacie) ---
        Schema::create('dispensations', function (Blueprint $table): void {
            SchemaBuilder::base($table);
            SchemaBuilder::syncable($table);

            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->nullOnDelete();
            $table->foreignId('hosto_id')->constrained('hostos'); // la pharmacie
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('dispensed_by_id')->nullable()->constrained('users')->nullOnDelete(); // pharmacien

            $table->string('reference', 30)->unique(); // DISP-2026-000001
            $table->string('status', 20)->default('pending')->index();
            // pending → dispensed → delivered → cancelled

            $table->unsignedInteger('total_amount')->default(0); // XAF
            $table->string('payment_method', 30)->nullable(); // cash, mobile_money, card, insurance
            $table->boolean('is_paid')->default(false);
            $table->string('delivery_code', 10)->nullable(); // code secret pour livraison
            $table->text('notes')->nullable();

            $table->timestampTz('dispensed_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();

            $table->index(['patient_id', 'status']);
        });

        SchemaBuilder::installUpdatedAtTrigger('dispensations');

        // --- Dispensation items ---
        Schema::create('dispensation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dispensation_id')->constrained('dispensations')->cascadeOnDelete();
            $table->foreignId('medication_id')->nullable()->constrained('medications')->nullOnDelete();

            $table->string('medication_name');
            $table->string('dosage')->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price')->default(0); // XAF
            $table->unsignedInteger('total_price')->default(0);
            $table->boolean('is_substituted')->default(false); // generic substitution
            $table->string('substitution_reason')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensation_items');
        SchemaBuilder::dropUpdatedAtTrigger('dispensations');
        Schema::dropIfExists('dispensations');
        SchemaBuilder::dropUpdatedAtTrigger('pharmacy_stocks');
        Schema::dropIfExists('pharmacy_stocks');
    }
};
