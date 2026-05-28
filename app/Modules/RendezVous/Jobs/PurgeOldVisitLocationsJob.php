<?php

declare(strict_types=1);

namespace App\Modules\RendezVous\Jobs;

use App\Modules\RendezVous\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class PurgeOldVisitLocationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $cutoff = now()->subDays(30);
        $count = Appointment::whereHas('timeSlot', function ($q) use ($cutoff) {
                $q->where('date', '<', $cutoff->toDateString());
            })
            ->whereNotNull('visit_lat')
            ->update([
                'visit_lat' => null,
                'visit_lng' => null,
                'visit_location_accuracy_m' => null,
            ]);
        Log::info('rdv.purge.visit_locations', ['cleared' => $count, 'cutoff' => $cutoff->toIso8601String()]);
    }
}
