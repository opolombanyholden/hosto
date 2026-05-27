<?php

declare(strict_types=1);

namespace Tests\Unit\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\AppointmentDocument;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AppointmentDocumentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_can_be_persisted_and_retrieved(): void
    {
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
        ]);

        $doc = AppointmentDocument::create([
            'appointment_id' => $apt->id,
            'uploaded_by_id' => $patient->id,
            'original_name' => 'ordonnance.pdf',
            'stored_path' => 'appointments/'.$apt->uuid.'/abc123.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'category' => 'ordonnance',
            'keep_in_dpe' => false,
        ]);

        $this->assertNotNull($doc->uuid);
        $this->assertSame($apt->id, $doc->appointment->id);
        $this->assertSame($patient->id, $doc->uploadedBy->id);
        $this->assertFalse($doc->keep_in_dpe);
    }
}
