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
