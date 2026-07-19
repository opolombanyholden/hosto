<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rolls monthly partitions of audit_logs forward.
 *
 * The audit_logs table is partitioned by RANGE on occurred_at. If no
 * partition covers the row being inserted, the write fails and — since
 * audits are synchronous by design (ADR 0004) — user-facing actions
 * (login, DPE reads, ...) break.
 *
 * Scheduled daily as a safety net; idempotent thanks to
 * CREATE TABLE IF NOT EXISTS.
 *
 * @see docs/adr/0004-audit-trail-global.md
 */
final class EnsureAuditPartitions extends Command
{
    protected $signature = 'audit:ensure-partitions
        {--months=3 : Number of months ahead to guarantee (0 = current only)}';

    protected $description = 'Ensures monthly partitions of audit_logs exist for the current month and N months ahead.';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->info('Non-PostgreSQL driver; audit_logs is not partitioned. Nothing to do.');

            return self::SUCCESS;
        }

        $months = (int) $this->option('months');
        if ($months < 0) {
            $this->error('--months must be >= 0.');

            return self::FAILURE;
        }

        $created = [];
        $skipped = [];

        for ($i = 0; $i <= $months; $i++) {
            $start = now()->addMonths($i)->startOfMonth();
            $end = $start->copy()->addMonth();
            $name = 'audit_logs_'.$start->format('Y_m');

            $exists = DB::selectOne(
                'SELECT to_regclass(?) AS r',
                ['public.'.$name],
            )->r !== null;

            if ($exists) {
                $skipped[] = $name;

                continue;
            }

            // DDL cannot use bound parameters; values come from Carbon
            // format() and are safe (no user input).
            DB::statement(sprintf(
                "CREATE TABLE IF NOT EXISTS %s PARTITION OF audit_logs FOR VALUES FROM ('%s') TO ('%s')",
                $name,
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
            ));
            $created[] = $name;
        }

        Log::info('audit.partitions.ensured', [
            'created' => $created,
            'skipped' => $skipped,
            'months_ahead' => $months,
        ]);

        $this->info(sprintf(
            'audit_logs partitions — created: %d, already present: %d.',
            count($created),
            count($skipped),
        ));
        foreach ($created as $name) {
            $this->line("  + {$name}");
        }

        return self::SUCCESS;
    }
}
