<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Services\MedicalCipher;
use App\Modules\Pro\Models\Consultation;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\AppointmentDocument;
use App\Modules\RendezVous\Models\TimeSlot;
use App\Modules\RendezVous\Services\DocumentUploadService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MedicalEncryptCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dry_run_changes_nothing(): void
    {
        $consultation = $this->plainConsultation('Texte en clair');

        $this->artisan('hosto:medical-encrypt', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('Texte en clair', $this->rawMotif($consultation));
    }

    public function test_command_encrypts_existing_rows_and_keeps_them_readable(): void
    {
        $consultation = $this->plainConsultation('Texte en clair');

        $this->artisan('hosto:medical-encrypt')->assertSuccessful();

        $this->assertStringStartsWith('hme1:', $this->rawMotif($consultation));
        $fresh = Consultation::findOrFail($consultation->id);
        $this->assertSame('Texte en clair', $fresh->motif);
        $this->assertSame(['t' => '38'], $fresh->vitals);
    }

    public function test_command_is_idempotent(): void
    {
        $consultation = $this->plainConsultation('Texte en clair');
        $this->artisan('hosto:medical-encrypt')->assertSuccessful();
        $first = $this->rawMotif($consultation);

        $this->artisan('hosto:medical-encrypt')->assertSuccessful();

        $this->assertSame($first, $this->rawMotif($consultation));
    }

    public function test_command_rotates_data_to_the_current_key(): void
    {
        $this->app->instance(MedicalCipher::class, new MedicalCipher($this->key('ancienne')));
        $consultation = $this->plainConsultation('Texte a tourner');
        $this->artisan('hosto:medical-encrypt')->assertSuccessful();
        $before = $this->rawMotif($consultation);

        $rotated = new MedicalCipher($this->key('nouvelle'), [$this->key('ancienne')]);
        $this->app->instance(MedicalCipher::class, $rotated);
        $this->artisan('hosto:medical-encrypt')->assertSuccessful();

        $after = $this->rawMotif($consultation);
        $this->assertNotSame($before, $after);
        $this->assertTrue($rotated->isCurrent($after));

        $this->app->instance(MedicalCipher::class, new MedicalCipher($this->key('nouvelle')));
        $this->assertSame('Texte a tourner', Consultation::findOrFail($consultation->id)->motif);
    }

    public function test_command_refuses_to_run_without_key(): void
    {
        $consultation = $this->plainConsultation('Texte en clair');
        $this->app->instance(MedicalCipher::class, new MedicalCipher(null));

        $this->artisan('hosto:medical-encrypt')->assertFailed();

        $this->assertSame('Texte en clair', $this->rawMotif($consultation));
    }

    public function test_uploaded_document_is_encrypted_on_disk(): void
    {
        $document = $this->document();

        $onDisk = Storage::disk('private_appointments')->get($this->path($document));

        $this->assertStringStartsWith('hme1:', $onDisk);
        $this->assertStringNotContainsString('CONTENU-MEDICAL', $onDisk);
    }

    public function test_download_returns_the_original_file(): void
    {
        $document = $this->document();
        $owner = User::findOrFail($document->appointment->patient_id);

        $response = app(DocumentUploadService::class)->download($document, $owner);

        ob_start();
        $response->sendContent();
        $this->assertSame('%PDF-1.4 CONTENU-MEDICAL', ob_get_clean());
    }

    public function test_command_encrypts_existing_files_on_disk(): void
    {
        $document = $this->document();
        Storage::disk('private_appointments')->put($this->path($document), '%PDF-1.4 ANCIEN-FICHIER');

        $this->artisan('hosto:medical-encrypt')->assertSuccessful();

        $onDisk = Storage::disk('private_appointments')->get($this->path($document));
        $this->assertStringStartsWith('hme1:', $onDisk);
        $this->assertSame('%PDF-1.4 ANCIEN-FICHIER', app(MedicalCipher::class)->decrypt($onDisk));
    }

    public function test_key_command_prints_a_valid_key_without_touching_env(): void
    {
        $before = file_get_contents(base_path('.env'));

        $this->artisan('hosto:medical-key')->assertSuccessful();

        $this->assertSame($before, file_get_contents(base_path('.env')));
    }

    private function plainConsultation(string $motif): Consultation
    {
        $consultation = Consultation::create([
            'practitioner_id' => Practitioner::factory()->create()->id, 'patient_id' => User::factory()->create()->id,
            'hosto_id' => Hosto::factory()->create()->id, 'status' => 'completed', 'motif' => 'temp',
        ]);
        DB::table('consultations')->where('id', $consultation->id)->update(['motif' => $motif, 'vitals' => '{"t":"38"}']);

        return $consultation;
    }

    private function rawMotif(Consultation $consultation): string
    {
        return (string) DB::table('consultations')->where('id', $consultation->id)->value('motif');
    }

    private function key(string $seed): string
    {
        return 'base64:'.base64_encode(hash('sha256', $seed, true));
    }

    private function document(): AppointmentDocument
    {
        Storage::fake('private_appointments');
        $patient = User::factory()->create();
        $practitioner = Practitioner::factory()->create();
        $hosto = Hosto::factory()->create();
        $slot = TimeSlot::create([
            'practitioner_id' => $practitioner->id, 'hosto_id' => $hosto->id, 'date' => now()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30', 'duration_minutes' => 30,
        ]);
        $appointment = Appointment::create([
            'time_slot_id' => $slot->id, 'patient_id' => $patient->id, 'practitioner_id' => $practitioner->id, 'hosto_id' => $hosto->id,
        ]);

        return app(DocumentUploadService::class)->store(
            $appointment,
            UploadedFile::fake()->createWithContent('ordonnance.pdf', '%PDF-1.4 CONTENU-MEDICAL')->mimeType('application/pdf'),
            $patient,
        );
    }

    private function path(AppointmentDocument $document): string
    {
        return str_replace('appointments/', '', $document->stored_path);
    }
}
