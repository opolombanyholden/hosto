<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the "audit_logs" table, partitioned by month.
 *
 * Rules:
 *   - INSERT only. No UPDATE, no DELETE (enforced via GRANT in prod).
 *   - Each row is HMAC-signed and chained to the previous row's hash.
 *   - Partitions are created for the current month and the next one.
 *     A scheduled job (Phase 0.5) will roll partitions forward.
 *
 * @see docs/adr/0004-audit-trail-global.md
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            // SQLite fallback: single table for Core unit tests only.
            // Real audit integration tests run against pgsql.
            $this->createSqliteFallback();

            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE TABLE audit_logs (
                id             bigserial NOT NULL,
                uuid           uuid NOT NULL DEFAULT gen_random_uuid(),
                occurred_at    timestamptz NOT NULL DEFAULT now(),

                actor_uuid     uuid NULL,
                actor_type     varchar(50) NULL,
                actor_ip       inet NULL,
                actor_ua       text NULL,
                session_uuid   uuid NULL,

                action         varchar(100) NOT NULL,
                resource_type  varchar(100) NULL,
                resource_uuid  uuid NULL,

                structure_uuid uuid NULL,

                payload        jsonb NULL,
                metadata       jsonb NULL,

                previous_hash  bytea NULL,
                signature      bytea NOT NULL,

                PRIMARY KEY (id, occurred_at)
            ) PARTITION BY RANGE (occurred_at);

            CREATE UNIQUE INDEX audit_logs_uuid_unique
                ON audit_logs (uuid, occurred_at);

            CREATE INDEX audit_logs_actor_idx
                ON audit_logs (actor_uuid, occurred_at DESC);

            CREATE INDEX audit_logs_resource_idx
                ON audit_logs (resource_type, resource_uuid, occurred_at DESC);

            CREATE INDEX audit_logs_action_idx
                ON audit_logs (action, occurred_at DESC);
        SQL);

        // Create partitions for current and next month.
        $this->createMonthlyPartition(now()->startOfMonth());
        $this->createMonthlyPartition(now()->addMonth()->startOfMonth());
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS audit_logs CASCADE');
    }

    private function createMonthlyPartition(DateTimeInterface $start): void
    {
        $startDate = $start->format('Y-m-d');
        $endDate = (new DateTime($startDate))->modify('+1 month')->format('Y-m-d');
        $suffix = (new DateTime($startDate))->format('Y_m');

        DB::statement(<<<SQL
            CREATE TABLE IF NOT EXISTS audit_logs_{$suffix}
            PARTITION OF audit_logs
            FOR VALUES FROM ('{$startDate}') TO ('{$endDate}')
        SQL);
    }

    private function createSqliteFallback(): void
    {
        Schema::create('audit_logs', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->timestampTz('occurred_at')->useCurrent();
            $t->uuid('actor_uuid')->nullable()->index();
            $t->string('actor_type', 50)->nullable();
            $t->string('actor_ip', 45)->nullable();
            $t->text('actor_ua')->nullable();
            $t->uuid('session_uuid')->nullable();
            $t->string('action', 100);
            $t->string('resource_type', 100)->nullable();
            $t->uuid('resource_uuid')->nullable();
            $t->uuid('structure_uuid')->nullable();
            $t->json('payload')->nullable();
            $t->json('metadata')->nullable();
            $t->binary('previous_hash')->nullable();
            $t->binary('signature');
            $t->index(['resource_type', 'resource_uuid', 'occurred_at']);
            $t->index(['action', 'occurred_at']);
        });
    }
};
