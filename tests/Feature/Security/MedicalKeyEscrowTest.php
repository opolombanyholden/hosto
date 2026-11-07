<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\MedicalEncryptionKey;
use App\Modules\Core\Services\KeyEscrow\MedicalKeyEscrow;
use App\Modules\Core\Services\MedicalCipher;
use App\Modules\Pro\Models\Consultation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class MedicalKeyEscrowTest extends TestCase
{
    use DatabaseTransactions;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/hosto-escrow-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_escrow_writes_one_private_sheet_per_custodian_and_registers_the_key(): void
    {
        $this->useKey($this->key('prod'));

        $shares = $this->escrow();

        $files = File::files($this->dir);
        $this->assertCount(5, $files);
        foreach ($files as $file) {
            $this->assertSame('0600', substr(sprintf('%o', fileperms($file->getPathname())), -4));
            $content = (string) file_get_contents($file->getPathname());
            $this->assertStringNotContainsString(substr($this->key('prod'), 7), $content, 'La cle elle-meme ne doit apparaitre nulle part.');
            $this->assertStringContainsString('3 fragments sur 5', $content);
        }

        $record = MedicalEncryptionKey::where('key_id', app(MedicalKeyEscrow::class)->currentKeyId())->firstOrFail();
        $this->assertSame(5, $record->escrow_shares);
        $this->assertSame(3, $record->escrow_threshold);
        $this->assertNotNull($record->escrowed_at);
        $this->assertCount(5, $shares);
    }

    public function test_the_registry_never_stores_the_key(): void
    {
        $this->useKey($this->key('prod'));
        $this->escrow();

        $row = (array) DB::table('medical_encryption_keys')->first();

        foreach ($row as $value) {
            $this->assertStringNotContainsString(substr($this->key('prod'), 7), (string) $value);
        }
    }

    public function test_any_three_custodians_recover_the_exact_key(): void
    {
        $this->useKey($this->key('prod'));
        $shares = $this->escrow();

        $this->artisan('hosto:medical-key-recover')
            ->expectsQuestion('Fragment 1', $shares[4])
            ->expectsQuestion('Fragment 2', $shares[0])
            ->expectsQuestion('Fragment 3', $shares[2])
            ->expectsOutputToContain($this->key('prod'))
            ->assertSuccessful();
    }

    public function test_drill_checks_shares_without_revealing_the_key(): void
    {
        $this->useKey($this->key('prod'));
        $shares = $this->escrow();

        $this->artisan('hosto:medical-key-recover', ['--verify' => true])
            ->expectsQuestion('Fragment 1', $shares[1])
            ->expectsQuestion('Fragment 2', $shares[3])
            ->expectsQuestion('Fragment 3', $shares[4])
            ->doesntExpectOutputToContain(substr($this->key('prod'), 7))
            ->expectsOutputToContain('correspondent a la cle en service')
            ->assertSuccessful();
    }

    public function test_a_mistyped_share_is_detected(): void
    {
        $this->useKey($this->key('prod'));
        $shares = $this->escrow();
        $typo = substr_replace($shares[1], strtoupper($shares[1][20]) === 'A' ? 'B' : 'A', 20, 1);

        $this->artisan('hosto:medical-key-recover')
            ->expectsQuestion('Fragment 1', $typo)
            ->expectsOutputToContain('Fragment illisible')
            ->assertFailed();
    }

    public function test_shares_of_another_key_cannot_be_mixed(): void
    {
        $this->useKey($this->key('ancienne'));
        $old = $this->escrow();
        File::deleteDirectory($this->dir);
        $this->useKey($this->key('nouvelle'));
        $new = $this->escrow();

        $this->artisan('hosto:medical-key-recover')
            ->expectsQuestion('Fragment 1', $new[0])
            ->expectsQuestion('Fragment 2', $old[1])
            ->expectsOutputToContain('autre cle')
            ->assertFailed();
    }

    public function test_recovered_key_is_checked_against_the_registry(): void
    {
        $this->useKey($this->key('prod'));
        $shares = $this->escrow();
        MedicalEncryptionKey::query()->delete();

        $this->artisan('hosto:medical-key-recover')
            ->expectsQuestion('Fragment 1', $shares[0])
            ->expectsQuestion('Fragment 2', $shares[1])
            ->expectsQuestion('Fragment 3', $shares[2])
            ->expectsOutputToContain('inconnue du registre')
            ->assertFailed();
    }

    public function test_recovery_works_on_a_server_that_lost_its_key(): void
    {
        $this->useKey($this->key('prod'));
        $shares = $this->escrow();
        $consultation = $this->encryptedConsultation();
        config(['hosto.medical_encryption.key' => null]);
        $this->app->instance(MedicalCipher::class, new MedicalCipher(null));

        $this->artisan('hosto:medical-key-recover')
            ->expectsQuestion('Fragment 1', $shares[0])
            ->expectsQuestion('Fragment 2', $shares[3])
            ->expectsQuestion('Fragment 3', $shares[4])
            ->expectsOutputToContain('dechiffre')
            ->assertSuccessful();

        $this->useKey($this->key('prod'));
        $this->assertSame('Paludisme', Consultation::findOrFail($consultation->id)->motif);
    }

    public function test_escrow_requires_a_key(): void
    {
        $this->useKey('');
        $this->app->instance(MedicalCipher::class, new MedicalCipher(null));

        $this->artisan('hosto:medical-key-escrow', ['--output' => $this->dir])->assertFailed();
        $this->assertDirectoryDoesNotExist($this->dir);
    }

    public function test_new_key_is_refused_when_data_is_already_encrypted(): void
    {
        $this->useKey($this->key('prod'));
        $this->encryptedConsultation();

        $this->artisan('hosto:medical-key')
            ->expectsOutputToContain('hosto:medical-key-recover')
            ->assertFailed();
    }

    public function test_health_check_flags_data_encrypted_with_an_unavailable_key(): void
    {
        $this->useKey($this->key('ancienne'));
        $this->encryptedConsultation();
        $this->escrow();
        File::deleteDirectory($this->dir);
        $this->useKey($this->key('nouvelle'));
        $this->escrow();

        $this->artisan('hosto:medical-key-check')
            ->expectsOutputToContain('illisible')
            ->assertFailed();
    }

    public function test_health_check_flags_a_key_without_escrow(): void
    {
        $this->useKey($this->key('prod'));
        $this->encryptedConsultation();

        $this->artisan('hosto:medical-key-check')
            ->expectsOutputToContain('sequestre')
            ->assertFailed();
    }

    public function test_health_check_passes_when_everything_is_safe(): void
    {
        $this->useKey($this->key('prod'));
        $this->encryptedConsultation();
        $this->escrow();

        $this->artisan('hosto:medical-key-check')->assertSuccessful();
    }

    private function key(string $seed): string
    {
        return 'base64:'.base64_encode(hash('sha256', $seed, true));
    }

    private function useKey(string $key, array $previous = []): void
    {
        config(['hosto.medical_encryption.key' => $key, 'hosto.medical_encryption.previous_keys' => $previous]);
        $this->app->instance(MedicalCipher::class, new MedicalCipher($key, $previous));
    }

    private function escrow(int $shares = 5, int $threshold = 3): array
    {
        $this->artisan('hosto:medical-key-escrow', ['--shares' => $shares, '--threshold' => $threshold, '--output' => $this->dir])
            ->assertSuccessful();

        return collect(File::files($this->dir))
            ->sortBy(fn ($f) => $f->getFilename())
            ->map(fn ($f) => app(MedicalKeyEscrow::class)->extractShare((string) file_get_contents($f->getPathname())))
            ->values()
            ->all();
    }

    private function encryptedConsultation(): Consultation
    {
        return Consultation::create([
            'practitioner_id' => Practitioner::factory()->create()->id, 'patient_id' => User::factory()->create()->id,
            'hosto_id' => Hosto::factory()->create()->id, 'status' => 'completed', 'motif' => 'Paludisme',
        ]);
    }
}
