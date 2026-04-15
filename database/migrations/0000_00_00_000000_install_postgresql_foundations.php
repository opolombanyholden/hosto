<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Installs PostgreSQL extensions and helper functions needed
 * everywhere in the HOSTO platform.
 *
 * Extensions:
 *   - postgis        : geospatial queries (structures proximity)
 *   - uuid-ossp      : UUID generators if needed outside PHP
 *   - pg_trgm        : fuzzy search on names (medicines, structures)
 *
 * Functions:
 *   - hosto_set_updated_at()  : used by per-table triggers
 *
 * Superuser rights were granted to the "hosto" role in Phase 0 setup.
 *
 * @see docs/adr/0002-postgresql-sgbdr-principal.md
 * @see docs/adr/0003-uuid-conventions-schema.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip everything when not on PostgreSQL (SQLite testing fallback
        // for Core-only tests; real integration tests use the pgsql
        // testing database).
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION hosto_set_updated_at()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = now();
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP FUNCTION IF EXISTS hosto_set_updated_at() CASCADE');
        // We keep extensions (they may be used by other databases / modules).
    }
};
