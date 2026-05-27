<?php
declare(strict_types=1);

namespace Tests\Feature\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Jobs\GeocodeAppointmentAddressJob;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class GeocodingJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_geocodes_address_and_updates_appointment(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                ['lat' => '0.41', 'lon' => '9.46'],
            ], 200),
        ]);

        $patient = User::factory()->create();
        $hosto = Hosto::factory()->create();
        $prac = Practitioner::factory()->create();
        $slot = TimeSlot::create([
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30,
        ]);
        $apt = Appointment::create([
            'time_slot_id' => $slot->id, 'patient_id' => $patient->id,
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
            'consultation_mode' => 'home',
            'visit_address' => 'Libreville, Gabon',
        ]);

        (new GeocodeAppointmentAddressJob($apt->id))->handle(app(\App\Modules\RendezVous\Services\GeocodingService::class));

        $apt->refresh();
        $this->assertEqualsWithDelta(0.41, (float) $apt->visit_lat, 0.01);
        $this->assertEqualsWithDelta(9.46, (float) $apt->visit_lng, 0.01);
        $this->assertNotNull($apt->visit_geocoded_at);
    }
}
