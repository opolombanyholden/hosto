<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Humanity module tables: aid requests (blood donation, financial aid, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aid_requests', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('user_id')->constrained('users');
            $table->string('type', 30)->index(); // blood_donation, financial_aid, organ, other
            $table->string('title');
            $table->text('description');
            $table->string('urgency', 20)->default('normal'); // normal, urgent, critical
            $table->unsignedInteger('target_amount')->nullable(); // for financial
            $table->unsignedInteger('collected_amount')->default(0);
            $table->string('status', 20)->default('open')->index();
            // open, in_progress, fulfilled, closed, cancelled
            $table->string('blood_type', 5)->nullable(); // for blood

            $table->index(['user_id', 'status']);
        });

        SchemaBuilder::installUpdatedAtTrigger('aid_requests');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('aid_requests');
        Schema::dropIfExists('aid_requests');
    }
};
