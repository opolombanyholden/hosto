<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

/**
 * SchemaBuilder.
 *
 * Helpers to enforce the HOSTO schema conventions on every table.
 *
 * @see docs/adr/0003-uuid-conventions-schema.md
 */
final class SchemaBuilder
{
    /**
     * Apply the mandatory base columns to a table Blueprint.
     *
     * Must be called at the top of every migration for business entities.
     */
    public static function base(Blueprint $table): void
    {
        $table->id(); // bigint auto-increment, internal use only
        $table->uuid('uuid')->unique(); // exposed to the API
        $table->timestampsTz(); // created_at, updated_at (with tz)
        $table->softDeletesTz(); // deleted_at
        $table->uuid('created_by')->nullable()->index();
        $table->uuid('updated_by')->nullable()->index();
    }

    /**
     * Apply sync-related columns for entities that participate in the
     * cloud/local synchronization (most medical entities).
     */
    public static function syncable(Blueprint $table): void
    {
        $table->string('origin', 50)->default('cloud')->index();
        $table->bigInteger('sync_version')->default(0);
        $table->string('sync_status', 20)->default('synced')->index();
    }

    /**
     * Install the PostgreSQL trigger that automatically updates
     * "updated_at" on row modification.
     *
     * Prefer this over model hooks: no application-level bypass possible.
     */
    public static function installUpdatedAtTrigger(string $table): void
    {
        DB::unprepared(
            <<<SQL
            DROP TRIGGER IF EXISTS set_updated_at ON {$table};

            CREATE TRIGGER set_updated_at
            BEFORE UPDATE ON {$table}
            FOR EACH ROW
            EXECUTE FUNCTION hosto_set_updated_at();
            SQL
        );
    }

    /**
     * Drop the updated_at trigger (for migration rollback).
     */
    public static function dropUpdatedAtTrigger(string $table): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS set_updated_at ON {$table};");
    }
}
