<?php
declare(strict_types=1);

namespace Tests\Feature\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Jobs\PurgeOldVisitLocationsJob;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PurgeOldVisitLocationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_purges_gps_for_appointments_older_than_30_days(): void
    {
        $apt = $this->makeApt(now()->subDays(35)->toDateString());
        $apt->update(['visit_lat' => 0.41, 'visit_lng' => 9.46, 'visit_location_accuracy_m' => 10]);

        (new PurgeOldVisitLocationsJob())->handle();

        $apt->refresh();
        $this->assertNull($apt->visit_lat);
        $this->assertNull($apt->visit_lng);
        $this->assertNull($apt->visit_location_accuracy_m);
    }

    public function test_keeps_gps_for_recent_appointments(): void
    {
        $apt = $this->makeApt(now()->subDays(5)->toDateString());
        $apt->update(['visit_lat' => 0.41, 'visit_lng' => 9.46]);

        (new PurgeOldVisitLocationsJob())->handle();

        $apt->refresh();
        $this->assertNotNull($apt->visit_lat);
    }

    public function test_purges_regardless_of_status(): void
    {
        // Even cancelled/no-show RDVs should have their GPS purged after 30 days.
        $apt = $this->makeApt(now()->subDays(35)->toDateString());
        $apt->update(['visit_lat' => 0.41, 'visit_lng' => 9.46, 'status' => 'cancelled_by_patient']);

        (new PurgeOldVisitLocationsJob())->handle();

        $this->assertNull($apt->fresh()->visit_lat);
    }

    private function makeApt(string $date): Appointment
    {
        $patient = User::factory()->create();
        $hosto = Hosto::factory()->create();
        $prac = Practitioner::factory()->create();
        $slot = TimeSlot::create([
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
            'date' => $date, 'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30,
        ]);
        return Appointment::create([
            'time_slot_id' => $slot->id, 'patient_id' => $patient->id,
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
        ]);
    }
}
