<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add visibility settings to practitioners and create publications system.
 *
 * Visibility: jsonb column storing which fields and services are public.
 * Publications: activity posts, research, tips, videos with social interactions.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Visibility + services settings on practitioners ---
        Schema::table('practitioners', function (Blueprint $table): void {
            // Which profile fields are visible publicly (jsonb).
            // Default: all visible. Keys: phone, email, bio, languages,
            // registration_number, fees, photo
            $table->jsonb('visibility_settings')->nullable()->after('is_verified');

            // Which services the practitioner offers.
            // Keys: appointment, teleconsultation, chat
            $table->jsonb('offered_services')->nullable()->after('visibility_settings');

            // Cover image for the profile page (Facebook-style).
            $table->string('cover_image_url')->nullable()->after('profile_image_url');
        });

        // --- Publications ---
        Schema::create('practitioner_publications', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('practitioner_id')->constrained('practitioners')->cascadeOnDelete();
            $table->string('type', 20); // activity, research, tip, video
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('video_url')->nullable(); // YouTube, Vimeo, or uploaded
            $table->jsonb('images')->nullable(); // array of image paths
            $table->boolean('is_published')->default(true);
            $table->boolean('allow_comments')->default(true);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestampTz('published_at')->nullable();

            $table->index(['practitioner_id', 'is_published', 'published_at']);
        });

        SchemaBuilder::installUpdatedAtTrigger('practitioner_publications');

        // --- Publication likes ---
        Schema::create('publication_likes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('publication_id')->constrained('practitioner_publications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['publication_id', 'user_id']);
        });

        // --- Publication comments ---
        Schema::create('publication_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('publication_id')->constrained('practitioner_publications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_approved')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['publication_id', 'is_approved', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_comments');
        Schema::dropIfExists('publication_likes');
        SchemaBuilder::dropUpdatedAtTrigger('practitioner_publications');
        Schema::dropIfExists('practitioner_publications');

        Schema::table('practitioners', function (Blueprint $table): void {
            $table->dropColumn(['visibility_settings', 'offered_services', 'cover_image_url']);
        });
    }
};
