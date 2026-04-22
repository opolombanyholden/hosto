<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Billing, payments and insurance claims.
 *
 * Supports:
 *   - Multi-method payments (cash, Mobile Money, Visa/Mastercard, eBanking)
 *   - Insurance cards (CNAMGS, ASCOMA, OGAR, etc.)
 *   - Insurance claims (feuilles de soins, remboursements)
 *   - Invoices linked to consultations or dispensations
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Insurance cards (per patient) ---
        Schema::create('insurance_cards', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider'); // CNAMGS, ASCOMA, OGAR, AXA, etc.
            $table->string('card_number');
            $table->string('holder_name');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedSmallInteger('coverage_percent')->default(80); // % pris en charge
            $table->boolean('is_active')->default(true)->index();

            $table->index(['user_id', 'is_active']);
        });

        SchemaBuilder::installUpdatedAtTrigger('insurance_cards');

        // --- Invoices (factures) ---
        Schema::create('invoices', function (Blueprint $table): void {
            SchemaBuilder::base($table);
            SchemaBuilder::syncable($table);

            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('hosto_id')->constrained('hostos');
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->foreignId('dispensation_id')->nullable()->constrained('dispensations')->nullOnDelete();

            $table->string('reference', 30)->unique(); // FAC-2026-000001
            $table->string('status', 20)->default('draft')->index();
            // draft → issued → partially_paid → paid → cancelled → refunded

            $table->unsignedInteger('subtotal')->default(0); // XAF
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('insurance_amount')->default(0); // part assurance
            $table->unsignedInteger('patient_amount')->default(0); // part patient (ticket moderateur)
            $table->unsignedInteger('total_amount')->default(0);
            $table->string('currency_code', 3)->default('XAF');

            $table->text('notes')->nullable();
            $table->timestampTz('issued_at')->nullable();
            $table->timestampTz('paid_at')->nullable();

            $table->index(['patient_id', 'status']);
            $table->index(['hosto_id', 'status']);
        });

        SchemaBuilder::installUpdatedAtTrigger('invoices');

        // --- Invoice items ---
        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->string('description');
            $table->string('category', 30)->nullable(); // consultation, exam, medication, care, other
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price')->default(0);
            $table->unsignedInteger('total_price')->default(0);
            $table->unsignedSmallInteger('display_order')->default(0);
        });

        // --- Payments (multi-method per invoice) ---
        Schema::create('payments', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users');

            $table->string('reference', 50)->unique(); // PAY-2026-000001 or provider ref
            $table->string('method', 30); // cash, mobile_money, visa, mastercard, ebanking, insurance
            $table->string('provider')->nullable(); // Airtel Money, Moov Money, Visa, Mastercard, BGFI, UBA...

            $table->unsignedInteger('amount'); // XAF
            $table->string('currency_code', 3)->default('XAF');
            $table->string('status', 20)->default('pending')->index();
            // pending → completed → failed → refunded

            $table->string('transaction_id')->nullable(); // ID from payment gateway
            $table->jsonb('gateway_response')->nullable(); // raw response from provider
            $table->text('notes')->nullable();

            $table->timestampTz('completed_at')->nullable();

            $table->index(['patient_id', 'status']);
        });

        SchemaBuilder::installUpdatedAtTrigger('payments');

        // --- Insurance claims (feuilles de soins) ---
        Schema::create('insurance_claims', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('insurance_card_id')->constrained('insurance_cards');
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('hosto_id')->constrained('hostos');

            $table->string('reference', 30)->unique(); // CLM-2026-000001
            $table->string('status', 20)->default('submitted')->index();
            // submitted → under_review → approved → rejected → paid → contested

            $table->unsignedInteger('claimed_amount'); // montant demande
            $table->unsignedInteger('approved_amount')->nullable();
            $table->unsignedInteger('paid_amount')->nullable();

            $table->text('rejection_reason')->nullable();
            $table->timestampTz('submitted_at')->useCurrent();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('paid_at')->nullable();

            $table->index(['patient_id', 'status']);
            $table->index(['hosto_id', 'status']);
        });

        SchemaBuilder::installUpdatedAtTrigger('insurance_claims');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('insurance_claims');
        Schema::dropIfExists('insurance_claims');
        SchemaBuilder::dropUpdatedAtTrigger('payments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        SchemaBuilder::dropUpdatedAtTrigger('invoices');
        Schema::dropIfExists('invoices');
        SchemaBuilder::dropUpdatedAtTrigger('insurance_cards');
        Schema::dropIfExists('insurance_cards');
    }
};
