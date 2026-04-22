<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Connect module tables: forum posts for professional health intranet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_posts', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('specialty_id')->nullable()->constrained('specialties')->nullOnDelete();

            $table->string('title');
            $table->text('content');
            $table->string('category', 30)->index(); // discussion, case_study, best_practice, question
            $table->boolean('is_pinned')->default(false);
            $table->unsignedInteger('replies_count')->default(0);

            $table->index(['user_id', 'category']);
        });

        SchemaBuilder::installUpdatedAtTrigger('forum_posts');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('forum_posts');
        Schema::dropIfExists('forum_posts');
    }
};
