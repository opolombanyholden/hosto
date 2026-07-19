<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EnsureAuditPartitionsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('audit_logs partitions only exist on PostgreSQL.');
        }
    }

    public function test_creates_partitions_for_current_month_and_n_months_ahead(): void
    {
        $months = 5;

        $expected = [];
        for ($i = 0; $i <= $months; $i++) {
            $expected[] = 'audit_logs_'.now()->addMonths($i)->format('Y_m');
        }

        $this->artisan('audit:ensure-partitions', ['--months' => $months])
            ->assertSuccessful();

        $existing = $this->partitionNames();
        foreach ($expected as $name) {
            $this->assertContains($name, $existing, "Expected partition {$name} to exist");
        }
    }

    public function test_partition_has_correct_range_bounds(): void
    {
        $months = 2;

        $this->artisan('audit:ensure-partitions', ['--months' => $months])
            ->assertSuccessful();

        $target = now()->addMonths($months)->startOfMonth();
        $name = 'audit_logs_'.$target->format('Y_m');

        $bounds = DB::selectOne(
            'SELECT pg_get_expr(c.relpartbound, c.oid) AS bounds FROM pg_class c WHERE c.relname = ?',
            [$name],
        )->bounds ?? null;

        $this->assertNotNull($bounds, "Partition {$name} should exist");
        $this->assertStringContainsString($target->format('Y-m-01'), $bounds);
        $this->assertStringContainsString($target->copy()->addMonth()->format('Y-m-01'), $bounds);
    }

    public function test_is_idempotent(): void
    {
        $this->artisan('audit:ensure-partitions', ['--months' => 3])->assertSuccessful();
        $this->artisan('audit:ensure-partitions', ['--months' => 3])->assertSuccessful();
    }

    public function test_rejects_negative_months(): void
    {
        $this->artisan('audit:ensure-partitions', ['--months' => -1])
            ->assertFailed();
    }

    /**
     * @return list<string>
     */
    private function partitionNames(): array
    {
        $rows = DB::select(
            "SELECT c.relname
               FROM pg_inherits i
               JOIN pg_class c ON c.oid = i.inhrelid
              WHERE inhparent = 'audit_logs'::regclass",
        );

        return array_map(static fn ($r) => $r->relname, $rows);
    }
}
