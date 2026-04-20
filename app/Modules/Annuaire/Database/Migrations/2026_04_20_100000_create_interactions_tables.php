<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Social interactions on health structures.
 *
 * Only available for partner structures (is_partner = true, cf ADR 0010).
 *
 * - Likes : one per user per structure (toggle)
 * - Recommendations : public text (moderated)
 * - Shares : no table (URL + meta OG + Web Share API)
 *
 * Evaluations are in a separate ADR (0009) — private, Phase 3.5.
 *
 * @see docs/adr/0009-workflow-enregistrement-structure-interactions.md
 * @see docs/adr/0010-structures-partenaires-et-layout-detail.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add is_partner + likes_count to hostos.
        Schema::table('hostos', function (Blueprint $table): void {
            $table->boolean('is_partner')->default(false)->after('is_verified');
            $table->unsignedInteger('likes_count')->default(0)->after('is_partner');
        });

        // Likes (one per user per structure, toggle).
        Schema::create('hosto_likes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hosto_id')->constrained('hostos')->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['user_id', 'hosto_id']);
        });

        // Recommendations (public text, moderated).
        Schema::create('hosto_recommendations', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hosto_id')->constrained('hostos')->cascadeOnDelete();
            $table->text('content'); // max 500 chars enforced in validation
            $table->boolean('is_approved')->default(false)->index();
            $table->timestampTz('approved_at')->nullable();
            $table->uuid('approved_by')->nullable();

            $table->index(['hosto_id', 'is_approved']);
        });

        SchemaBuilder::installUpdatedAtTrigger('hosto_recommendations');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('hosto_recommendations');
        Schema::dropIfExists('hosto_recommendations');
        Schema::dropIfExists('hosto_likes');

        Schema::table('hostos', function (Blueprint $table): void {
            $table->dropColumn(['is_partner', 'likes_count']);
        });
    }
};
