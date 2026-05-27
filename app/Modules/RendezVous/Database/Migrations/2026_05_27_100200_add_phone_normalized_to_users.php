<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone_normalized', 20)->nullable()->unique();
        });

        // Naive backfill (ThirdPartyResolverService will properly normalize new entries later)
        DB::table('users')
            ->whereNotNull('phone')
            ->whereNull('phone_normalized')
            ->orderBy('id')
            ->chunk(500, function ($users) {
                foreach ($users as $u) {
                    $raw = preg_replace('/[^0-9+]/', '', $u->phone);
                    if (! str_starts_with($raw, '+')) {
                        $raw = '+241'.ltrim($raw, '0');
                    }
                    if (strlen($raw) <= 20) {
                        try {
                            DB::table('users')->where('id', $u->id)
                                ->update(['phone_normalized' => $raw]);
                        } catch (\Throwable $e) {
                            // Duplicate or invalid format — skip silently
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('phone_normalized');
        });
    }
};
