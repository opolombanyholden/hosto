<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EVax module tables: vaccination records.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccination_records', function (Blueprint $table): void {
            SchemaBuilder::base($table);
            SchemaBuilder::syncable($table);

            $table->foreignId('patient_id')->constrained('users');
            $table->string('vaccine_name');
            $table->string('vaccine_code', 30)->nullable();
            $table->unsignedSmallInteger('dose_number')->default(1);
            $table->date('administered_at');
            $table->foreignId('administered_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('hosto_id')->nullable()->constrained('hostos')->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('next_dose_date')->nullable();
            $table->text('notes')->nullable();

            $table->index(['patient_id', 'vaccine_name']);
        });

        SchemaBuilder::installUpdatedAtTrigger('vaccination_records');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('vaccination_records');
        Schema::dropIfExists('vaccination_records');
    }
};
