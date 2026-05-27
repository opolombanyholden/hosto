<?php
declare(strict_types=1);

namespace Tests\Feature\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\AppointmentDocument;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AppointmentDocumentsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;
    private Appointment $apt;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private_appointments');
        $this->patient = User::factory()->create(['phone_verified_at' => now()]);
        $hosto = Hosto::factory()->create();
        $prac = Practitioner::factory()->create();
        $slot = TimeSlot::create([
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30,
        ]);
        $this->apt = Appointment::create([
            'time_slot_id' => $slot->id, 'patient_id' => $this->patient->id,
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
        ]);
    }

    public function test_patient_can_upload_document(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 100, 'application/pdf');
        $resp = $this->actingAs($this->patient)
            ->post('/web/rdv/'.$this->apt->uuid.'/documents', [
                'file' => $file, 'category' => 'ordonnance',
            ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('appointment_documents', [
            'appointment_id' => $this->apt->id, 'original_name' => 'o.pdf',
        ]);
    }

    public function test_patient_cannot_upload_to_others_appointment(): void
    {
        $other = User::factory()->create();
        $file = UploadedFile::fake()->create('o.pdf', 100, 'application/pdf');
        $resp = $this->actingAs($other)
            ->post('/web/rdv/'.$this->apt->uuid.'/documents', ['file' => $file]);
        $resp->assertForbidden();
    }

    public function test_patient_can_download_own_document(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 100, 'application/pdf');
        app(\App\Modules\RendezVous\Services\DocumentUploadService::class)
            ->store($this->apt, $file, $this->patient, null);
        $doc = AppointmentDocument::first();
        $resp = $this->actingAs($this->patient)
            ->get('/web/rdv/documents/'.$doc->uuid.'/download');
        $resp->assertOk();
        $resp->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_random_user_cannot_download(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 100, 'application/pdf');
        $doc = app(\App\Modules\RendezVous\Services\DocumentUploadService::class)
            ->store($this->apt, $file, $this->patient, null);
        $other = User::factory()->create();
        $resp = $this->actingAs($other)
            ->get('/web/rdv/documents/'.$doc->uuid.'/download');
        $resp->assertForbidden();
    }

    public function test_owner_can_delete_document(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 100, 'application/pdf');
        $doc = app(\App\Modules\RendezVous\Services\DocumentUploadService::class)
            ->store($this->apt, $file, $this->patient, null);
        $resp = $this->actingAs($this->patient)
            ->delete('/web/rdv/documents/'.$doc->uuid);
        $resp->assertRedirect();
        $this->assertSoftDeleted($doc);
    }
}
