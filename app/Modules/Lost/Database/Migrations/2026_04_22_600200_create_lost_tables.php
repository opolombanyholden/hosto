<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lost module tables: vital declarations (birth / death).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vital_declarations', function (Blueprint $table): void {
            SchemaBuilder::base($table);
            SchemaBuilder::syncable($table);

            $table->string('type', 10)->index(); // birth, death
            $table->foreignId('patient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('declared_by_id')->constrained('users');
            $table->foreignId('hosto_id')->nullable()->constrained('hostos')->nullOnDelete();

            $table->date('declaration_date');
            $table->string('person_name');
            $table->string('person_gender', 10)->nullable();
            $table->date('person_birth_date')->nullable();
            $table->date('person_death_date')->nullable();
            $table->text('cause_of_death')->nullable();
            $table->string('certificate_number')->unique()->nullable();
            $table->string('status', 20)->default('declared')->index();
            // declared, registered, certified
            $table->text('notes')->nullable();

            $table->index(['declared_by_id', 'type']);
        });

        SchemaBuilder::installUpdatedAtTrigger('vital_declarations');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('vital_declarations');
        Schema::dropIfExists('vital_declarations');
    }
};
