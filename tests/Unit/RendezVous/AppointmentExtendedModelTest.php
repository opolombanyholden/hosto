<?php

declare(strict_types=1);

namespace Tests\Unit\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AppointmentExtendedModelTest extends TestCase
{
    use RefreshDatabase;

    private function buildContext(): array
    {
        $patient = User::factory()->create();
        $hosto = Hosto::factory()->create();
        $practitioner = Practitioner::factory()->create();
        $slot = TimeSlot::create([
            'practitioner_id' => $practitioner->id, 'hosto_id' => $hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30, 'is_available' => true,
        ]);
        return compact('patient', 'hosto', 'practitioner', 'slot');
    }

    public function test_is_teleconsultation_accessor_true_when_mode_telecon(): void
    {
        ['patient' => $p, 'hosto' => $h, 'practitioner' => $pr, 'slot' => $s] = $this->buildContext();
        $apt = Appointment::create([
            'time_slot_id' => $s->id, 'patient_id' => $p->id,
            'practitioner_id' => $pr->id, 'hosto_id' => $h->id,
            'appointment_type' => 'ordinaire', 'consultation_mode' => 'telecon',
        ]);
        $this->assertTrue($apt->is_teleconsultation);
    }

    public function test_is_teleconsultation_accessor_false_when_mode_in_hospital(): void
    {
        ['patient' => $p, 'hosto' => $h, 'practitioner' => $pr, 'slot' => $s] = $this->buildContext();
        $apt = Appointment::create([
            'time_slot_id' => $s->id, 'patient_id' => $p->id,
            'practitioner_id' => $pr->id, 'hosto_id' => $h->id,
            'appointment_type' => 'ordinaire', 'consultation_mode' => 'in_hospital',
        ]);
        $this->assertFalse($apt->is_teleconsultation);
    }

    public function test_is_urgent_helper(): void
    {
        ['patient' => $p, 'hosto' => $h, 'practitioner' => $pr, 'slot' => $s] = $this->buildContext();
        $apt = Appointment::create([
            'time_slot_id' => $s->id, 'patient_id' => $p->id,
            'practitioner_id' => $pr->id, 'hosto_id' => $h->id,
            'appointment_type' => 'urgence', 'consultation_mode' => 'in_hospital',
        ]);
        $this->assertTrue($apt->isUrgent());
        $this->assertFalse($apt->isHomeVisit());
    }

    public function test_is_home_visit_helper(): void
    {
        ['patient' => $p, 'hosto' => $h, 'practitioner' => $pr, 'slot' => $s] = $this->buildContext();
        $apt = Appointment::create([
            'time_slot_id' => $s->id, 'patient_id' => $p->id,
            'practitioner_id' => $pr->id, 'hosto_id' => $h->id,
            'appointment_type' => 'ordinaire', 'consultation_mode' => 'home',
            'visit_address' => 'BP 1234 Libreville',
        ]);
        $this->assertTrue($apt->isHomeVisit());
        $this->assertFalse($apt->is_teleconsultation);
    }
}
