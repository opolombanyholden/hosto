<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sync queue and sync log for offline/online synchronization.
 *
 * @see docs/adr/0014-synchronisation-offline.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Sync queue: pending changes to push to cloud ---
        Schema::create('sync_queue', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('entity_type', 100); // App\Modules\Pro\Models\Consultation
            $table->uuid('entity_uuid');
            $table->string('action', 20); // create, update, delete
            $table->jsonb('payload')->nullable(); // serialized entity data
            $table->string('origin', 50); // structure UUID
            $table->bigInteger('sync_version');
            $table->string('status', 20)->default('pending')->index(); // pending, synced, failed, conflict
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('synced_at')->nullable();

            $table->index(['status', 'created_at']);
            $table->index(['entity_type', 'entity_uuid']);
        });

        // --- Sync log: history of synchronization runs ---
        Schema::create('sync_log', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('direction', 10); // push, pull
            $table->string('status', 20); // started, completed, failed, partial
            $table->unsignedInteger('entities_pushed')->default(0);
            $table->unsignedInteger('entities_pulled')->default(0);
            $table->unsignedInteger('conflicts')->default(0);
            $table->unsignedInteger('errors')->default(0);
            $table->text('details')->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_log');
        Schema::dropIfExists('sync_queue');
    }
};
