<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Media gallery for health structures.
 *
 * Types:
 *   - profile : image de profil (1 par structure, comme un avatar)
 *   - cover   : image de couverture (1 par structure, bandeau horizontal)
 *   - gallery : photos secondaires (N par structure)
 *
 * Uniqueness: one profile + one cover per structure (enforced by partial
 * unique index). Gallery items are unlimited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosto_media', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->foreignId('hosto_id')->constrained('hostos')->cascadeOnDelete();
            $table->string('type', 20)->index(); // profile, cover, gallery
            $table->text('url'); // relative path, full URL or data URI
            $table->string('alt_text')->nullable();
            $table->string('mime_type', 50)->nullable();
            $table->unsignedInteger('file_size')->nullable(); // bytes
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->boolean('is_primary')->default(false); // primary gallery image
            $table->unsignedSmallInteger('display_order')->default(0);

            $table->index(['hosto_id', 'type', 'display_order']);
        });

        SchemaBuilder::installUpdatedAtTrigger('hosto_media');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('hosto_media');
        Schema::dropIfExists('hosto_media');
    }
};
