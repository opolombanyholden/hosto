<?php
declare(strict_types=1);

namespace Tests\Unit\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\AppointmentDocument;
use App\Modules\RendezVous\Models\TimeSlot;
use App\Modules\RendezVous\Services\DocumentUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DocumentUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentUploadService $svc;
    private Appointment $apt;
    private User $patient;
    private Practitioner $practitioner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private_appointments');
        $this->svc = new DocumentUploadService();

        $this->patient = User::factory()->create();
        $hosto = Hosto::factory()->create();
        $this->practitioner = Practitioner::factory()->create();
        $slot = TimeSlot::create([
            'practitioner_id' => $this->practitioner->id, 'hosto_id' => $hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30,
        ]);
        $this->apt = Appointment::create([
            'time_slot_id' => $slot->id, 'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id, 'hosto_id' => $hosto->id,
        ]);
    }

    public function test_store_persists_document_under_appointment_uuid_folder(): void
    {
        $file = UploadedFile::fake()->create('ordonnance.pdf', 100, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, 'ordonnance');

        $this->assertNotNull($doc->id);
        $this->assertStringStartsWith('appointments/'.$this->apt->uuid.'/', $doc->stored_path);
        $this->assertSame('ordonnance.pdf', $doc->original_name);
        $this->assertSame('ordonnance', $doc->category);
        Storage::disk('private_appointments')->assertExists(
            str_replace('appointments/', '', $doc->stored_path)
        );
    }

    public function test_store_refuses_mime_not_allowed(): void
    {
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');
        $this->expectException(\InvalidArgumentException::class);
        $this->svc->store($this->apt, $file, $this->patient, null);
    }

    public function test_store_refuses_above_10mb(): void
    {
        $file = UploadedFile::fake()->create('big.pdf', 11 * 1024, 'application/pdf'); // 11 MB
        $this->expectException(\DomainException::class);
        $this->svc->store($this->apt, $file, $this->patient, null);
    }

    public function test_store_refuses_more_than_5_files_per_appointment(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $f = UploadedFile::fake()->create("f{$i}.pdf", 100, 'application/pdf');
            $this->svc->store($this->apt, $f, $this->patient, null);
        }
        $extra = UploadedFile::fake()->create('6.pdf', 100, 'application/pdf');
        $this->expectException(\DomainException::class);
        $this->svc->store($this->apt, $extra, $this->patient, null);
    }

    public function test_store_refuses_total_above_30mb(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $f = UploadedFile::fake()->create("f{$i}.pdf", 9 * 1024, 'application/pdf');
            $this->svc->store($this->apt, $f, $this->patient, null);
        }
        $extra = UploadedFile::fake()->create('big.pdf', 9 * 1024, 'application/pdf');
        $this->expectException(\DomainException::class);
        $this->svc->store($this->apt, $extra, $this->patient, null);
    }

    public function test_canAccess_allows_patient_owner(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 50, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, null);
        $this->assertTrue($this->svc->canAccess($doc, $this->patient));
    }

    public function test_canAccess_denies_random_user(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 50, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, null);
        $other = User::factory()->create();
        $this->assertFalse($this->svc->canAccess($doc, $other));
    }

    public function test_canAccess_allows_practitioner_user(): void
    {
        $procUser = User::factory()->create();
        $this->practitioner->update(['user_id' => $procUser->id]);
        $file = UploadedFile::fake()->create('o.pdf', 50, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, null);
        $this->assertTrue($this->svc->canAccess($doc, $procUser));
    }

    public function test_delete_soft_deletes(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 50, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, null);
        $this->svc->delete($doc, $this->patient);
        $this->assertSoftDeleted($doc);
    }

    public function test_promoteToDpe_marks_keep_in_dpe_true(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 50, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, null);
        $this->svc->promoteToDpe($doc);
        $this->assertTrue($doc->fresh()->keep_in_dpe);
    }

    public function test_store_hashes_filename(): void
    {
        $file = UploadedFile::fake()->create('orig name with spaces.pdf', 50, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, null);
        $this->assertStringNotContainsString('orig name with spaces', $doc->stored_path);
    }
}
