<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Three tables predate the uuid convention (ADR 0003). Existing rows get a
 * uuid, then the column becomes mandatory and unique.
 */
return new class extends Migration
{
    private const TABLES = ['publication_comments', 'emergency_contacts', 'reference_data'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS uuid uuid");
            DB::statement("UPDATE {$table} SET uuid = gen_random_uuid() WHERE uuid IS NULL");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN uuid SET NOT NULL");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS {$table}_uuid_unique ON {$table} (uuid)");
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            DB::statement("DROP INDEX IF EXISTS {$table}_uuid_unique");
            DB::statement("ALTER TABLE {$table} DROP COLUMN IF EXISTS uuid");
        }
    }
};
