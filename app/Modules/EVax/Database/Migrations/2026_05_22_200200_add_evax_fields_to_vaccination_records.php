<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vaccination_records', function (Blueprint $table): void {
            $table->foreignId('dependent_id')->nullable()->after('patient_id')
                ->constrained('dependents')->cascadeOnDelete();
            $table->foreignId('vaccine_id')->nullable()->after('vaccine_code')
                ->constrained('vaccines')->nullOnDelete();
            $table->boolean('is_standardized')->default(true)->after('vaccine_id');
            $table->timestampTz('signed_at')->nullable()->after('hosto_id');
            $table->text('signed_by_signature')->nullable()->after('signed_at');
            $table->unsignedInteger('carnet_revision')->default(1)->after('signed_by_signature');
        });

        DB::statement('ALTER TABLE vaccination_records ALTER COLUMN patient_id DROP NOT NULL');

        DB::statement('
            ALTER TABLE vaccination_records
            ADD CONSTRAINT vaccination_target_xor
            CHECK (
                (patient_id IS NOT NULL AND dependent_id IS NULL)
                OR (patient_id IS NULL AND dependent_id IS NOT NULL)
            )
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE vaccination_records DROP CONSTRAINT IF EXISTS vaccination_target_xor');

        Schema::table('vaccination_records', function (Blueprint $table): void {
            $table->dropForeign(['dependent_id']);
            $table->dropForeign(['vaccine_id']);
            $table->dropColumn([
                'dependent_id', 'vaccine_id', 'is_standardized',
                'signed_at', 'signed_by_signature', 'carnet_revision',
            ]);
        });

        DB::statement('ALTER TABLE vaccination_records ALTER COLUMN patient_id SET NOT NULL');
    }
};
