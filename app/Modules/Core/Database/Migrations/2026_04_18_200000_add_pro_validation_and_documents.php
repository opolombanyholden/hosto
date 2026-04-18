<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds professional validation columns to users and creates
 * user_documents table for legal document uploads.
 *
 * @see docs/adr/0012-verification-compte-workflow.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestampTz('pro_validated_at')->nullable()->after('locked_until');
            $table->uuid('pro_validated_by')->nullable()->after('pro_validated_at');
            $table->string('pro_validation_status', 20)->nullable()->index()->after('pro_validated_by');
            $table->text('pro_rejection_reason')->nullable()->after('pro_validation_status');
        });

        Schema::create('user_documents', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50); // diploma, license, id_card, order_registration, other
            $table->string('name');
            $table->string('path');
            $table->string('mime_type', 50)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->string('status', 20)->default('uploaded'); // uploaded, verified, rejected
            $table->text('note')->nullable();
            $table->timestampTz('uploaded_at')->useCurrent();
            $table->timestampTz('reviewed_at')->nullable();
            $table->uuid('reviewed_by')->nullable();

            $table->index(['user_id', 'type']);
        });

        SchemaBuilder::installUpdatedAtTrigger('user_documents');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('user_documents');
        Schema::dropIfExists('user_documents');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['pro_validated_at', 'pro_validated_by', 'pro_validation_status', 'pro_rejection_reason']);
        });
    }
};
