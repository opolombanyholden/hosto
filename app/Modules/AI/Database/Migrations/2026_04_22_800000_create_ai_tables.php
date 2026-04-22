<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI module tables: OCR results, chatbot conversations, predictions.
 *
 * All AI processing is on-premise (data sovereignty).
 * Models run locally — no external API calls with patient data.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- OCR processing results ---
        Schema::create('ocr_results', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('user_id')->constrained('users');
            $table->string('source_type', 30); // prescription, exam_result, medical_certificate
            $table->string('file_path');
            $table->string('mime_type', 50)->nullable();

            $table->text('raw_text')->nullable(); // extracted text
            $table->jsonb('structured_data')->nullable(); // parsed fields
            $table->float('confidence_score')->nullable(); // 0.0 - 1.0
            $table->string('status', 20)->default('pending')->index();
            // pending → processing → completed → failed

            $table->text('error_message')->nullable();
            $table->unsignedInteger('processing_time_ms')->nullable();
        });

        SchemaBuilder::installUpdatedAtTrigger('ocr_results');

        // --- Chatbot conversations ---
        Schema::create('chatbot_conversations', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('user_id')->constrained('users');
            $table->string('status', 20)->default('active'); // active, closed
            $table->string('topic')->nullable(); // symptom_check, medication_info, appointment_help
            $table->unsignedSmallInteger('messages_count')->default(0);
        });

        SchemaBuilder::installUpdatedAtTrigger('chatbot_conversations');

        // --- Chatbot messages ---
        Schema::create('chatbot_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();
            $table->string('role', 10); // user, assistant, system
            $table->text('content');
            $table->jsonb('metadata')->nullable(); // intent, entities, confidence
            $table->timestampTz('created_at')->useCurrent();
        });

        // --- Epidemiological predictions ---
        Schema::create('epi_predictions', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->string('model_name', 50); // malaria_forecast, dengue_risk, etc.
            $table->string('model_version', 20)->nullable();
            $table->string('diagnostic_code', 20)->nullable(); // CIM-10
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();

            $table->date('prediction_date');
            $table->unsignedSmallInteger('horizon_days'); // 7, 14, 30
            $table->unsignedInteger('predicted_cases');
            $table->float('confidence_lower')->nullable(); // lower bound
            $table->float('confidence_upper')->nullable(); // upper bound
            $table->float('accuracy_score')->nullable(); // historical accuracy

            $table->jsonb('features_used')->nullable(); // input features
            $table->text('interpretation')->nullable(); // human-readable

            $table->index(['diagnostic_code', 'prediction_date']);
            $table->index(['region_id', 'prediction_date']);
        });

        SchemaBuilder::installUpdatedAtTrigger('epi_predictions');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('epi_predictions');
        Schema::dropIfExists('epi_predictions');
        Schema::dropIfExists('chatbot_messages');
        SchemaBuilder::dropUpdatedAtTrigger('chatbot_conversations');
        Schema::dropIfExists('chatbot_conversations');
        SchemaBuilder::dropUpdatedAtTrigger('ocr_results');
        Schema::dropIfExists('ocr_results');
    }
};
