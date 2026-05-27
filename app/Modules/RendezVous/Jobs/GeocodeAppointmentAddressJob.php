<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Jobs;

use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Services\GeocodingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GeocodeAppointmentAddressJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public readonly int $appointmentId) {}

    public function handle(GeocodingService $svc): void
    {
        $apt = Appointment::find($this->appointmentId);
        if (! $apt || ! $apt->visit_address || $apt->visit_lat !== null) {
            return;
        }

        $result = $svc->geocode($apt->visit_address);
        if (! $result) return;

        $apt->update([
            'visit_lat' => $result['lat'],
            'visit_lng' => $result['lng'],
            'visit_geocoded_at' => now(),
            'visit_location_accuracy_m' => $result['accuracy_m'],
        ]);
    }
}
