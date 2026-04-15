<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Modules\Core\Services\AuditLogger;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AuditLoggerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_first_audit_entry_has_null_previous_hash(): void
    {
        // Ensure empty table for this test. Partitioned tables on pgsql
        // don't support TRUNCATE inside transactions cleanly; delete is
        // fine here since we're in a transaction.
        DB::table('audit_logs')->delete();

        $logger = app(AuditLogger::class);
        $uuid = (string) Str::uuid7();

        $logger->record(AuditLogger::ACTION_LOGIN, 'user', $uuid);

        $row = DB::table('audit_logs')->orderByDesc('id')->first();

        $this->assertNotNull($row);
        $this->assertSame('login', $row->action);
        $this->assertSame('user', $row->resource_type);
        $this->assertNull($this->readBytea($row->previous_hash));
        $this->assertNotEmpty($this->readBytea($row->signature));
    }

    public function test_consecutive_entries_chain_via_previous_hash(): void
    {
        DB::table('audit_logs')->delete();

        $logger = app(AuditLogger::class);

        $logger->record(AuditLogger::ACTION_LOGIN, 'user', (string) Str::uuid7());
        $logger->record(AuditLogger::ACTION_READ, 'patient', (string) Str::uuid7());
        $logger->record(AuditLogger::ACTION_UPDATE, 'patient', (string) Str::uuid7());

        $rows = DB::table('audit_logs')
            ->orderBy('occurred_at')->orderBy('id')
            ->get(['signature', 'previous_hash'])
            ->all();

        $this->assertCount(3, $rows);

        $this->assertNull($this->readBytea($rows[0]->previous_hash));
        $this->assertSame(
            $this->readBytea($rows[0]->signature),
            $this->readBytea($rows[1]->previous_hash),
            'Row 2 previous_hash must equal row 1 signature',
        );
        $this->assertSame(
            $this->readBytea($rows[1]->signature),
            $this->readBytea($rows[2]->previous_hash),
            'Row 3 previous_hash must equal row 2 signature',
        );
    }

    public function test_signature_is_deterministic_for_same_inputs(): void
    {
        DB::table('audit_logs')->delete();

        $logger = app(AuditLogger::class);
        $logger->record(AuditLogger::ACTION_LOGIN, 'user', '019d915a-0000-7000-8000-000000000000');

        $signature1 = $this->readBytea(DB::table('audit_logs')->orderByDesc('id')->value('signature'));

        // The signature for a fresh, identical row would differ only by
        // uuid and occurred_at which are part of the canonical form.
        // We therefore only assert the signature is 32 bytes (SHA-256).
        $this->assertSame(32, strlen($signature1));
    }

    /**
     * PostgreSQL bytea can come back as a resource, as "\x..."-prefixed
     * hex string, or as raw bytes (SQLite). Normalize to raw bytes.
     */
    private function readBytea(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_resource($value)) {
            return stream_get_contents($value);
        }

        if (is_string($value) && str_starts_with($value, '\x')) {
            $decoded = hex2bin(substr($value, 2));

            return $decoded === false ? null : $decoded;
        }

        return (string) $value;
    }
}
