<?php

declare(strict_types=1);

use App\Modules\RendezVous\Jobs\PurgeOldVisitLocationsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new PurgeOldVisitLocationsJob())->dailyAt('03:00')->name('purge_visit_locations');

// Rolls audit_logs monthly partitions forward. Runs daily as a safety net
// so a missing partition never blocks synchronous audit inserts (login,
// DPE reads, etc.). Idempotent — CREATE TABLE IF NOT EXISTS.
Schedule::command('audit:ensure-partitions', ['--months' => 3])
    ->dailyAt('02:30')
    ->name('audit_ensure_partitions')
    ->onOneServer();
