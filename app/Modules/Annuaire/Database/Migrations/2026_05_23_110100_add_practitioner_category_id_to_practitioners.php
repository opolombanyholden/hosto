<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practitioners', function (Blueprint $table): void {
            $table->foreignId('practitioner_category_id')->nullable()
                ->after('practitioner_type')
                ->constrained('practitioner_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('practitioners', function (Blueprint $table): void {
            $table->dropForeign(['practitioner_category_id']);
            $table->dropColumn('practitioner_category_id');
        });
    }
};
