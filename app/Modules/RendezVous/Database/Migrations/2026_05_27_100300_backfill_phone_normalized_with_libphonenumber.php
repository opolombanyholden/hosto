<?php

declare(strict_types=1);

use App\Modules\RendezVous\Services\ThirdPartyResolverService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $svc = new ThirdPartyResolverService();
        $updated = 0;
        $skipped = 0;
        $collisions = 0;

        DB::table('users')
            ->whereNotNull('phone')
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($svc, &$updated, &$skipped, &$collisions): void {
                foreach ($users as $u) {
                    $normalized = $svc->normalizePhone($u->phone, 'GA');
                    if ($normalized === null) {
                        $skipped++;
                        Log::info('phone.backfill.skipped', ['user_id' => $u->id, 'raw' => $u->phone]);
                        continue;
                    }
                    try {
                        DB::table('users')
                            ->where('id', $u->id)
                            ->update(['phone_normalized' => $normalized]);
                        $updated++;
                    } catch (\Throwable $e) {
                        $collisions++;
                        Log::warning('phone.backfill.collision', [
                            'user_id' => $u->id, 'normalized' => $normalized, 'err' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('phone.backfill.summary', [
            'updated' => $updated, 'skipped' => $skipped, 'collisions' => $collisions,
        ]);
    }

    public function down(): void
    {
        // No-op: re-normalization is idempotent.
    }
};
