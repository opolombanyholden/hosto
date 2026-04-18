<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Services\TwoFactorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class TwoFactorTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);
    }

    public function test_authenticated_user_can_access_2fa_setup(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'patient')->first());

        $this->actingAs($user)
            ->get('/2fa/setup')
            ->assertOk()
            ->assertSee('Activer la verification');
    }

    public function test_2fa_setup_generates_qr_code(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'patient')->first());

        $response = $this->actingAs($user)->get('/2fa/setup');

        $response->assertOk()
            ->assertSee('svg') // QR code rendered as SVG
            ->assertSee('Cle manuelle');
    }

    public function test_2fa_service_generates_valid_secret(): void
    {
        $service = new TwoFactorService;
        $secret = $service->generateSecret();

        $this->assertNotEmpty($secret);
        $this->assertGreaterThanOrEqual(32, strlen($secret));
    }

    public function test_2fa_service_generates_recovery_codes(): void
    {
        $service = new TwoFactorService;
        $codes = $service->generateRecoveryCodes();

        $this->assertCount(8, $codes);
        $codes->each(fn ($code) => $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code));
    }

    public function test_2fa_service_can_enable_and_disable(): void
    {
        $service = new TwoFactorService;
        $user = User::factory()->create();

        $this->assertFalse($service->isEnabled($user));

        $secret = $service->generateSecret();
        $service->enable($user, $secret);
        $user->refresh();

        $this->assertTrue($service->isEnabled($user));

        $service->disable($user);
        $user->refresh();

        $this->assertFalse($service->isEnabled($user));
    }

    public function test_login_with_2fa_redirects_to_challenge(): void
    {
        $service = new TwoFactorService;
        $user = User::factory()->create(['password' => bcrypt('motdepasse12345')]);
        $user->roles()->attach(Role::where('slug', 'patient')->first());

        $secret = $service->generateSecret();
        $service->enable($user, $secret);

        $response = $this->post('/compte/connexion', [
            'email' => $user->email,
            'password' => 'motdepasse12345',
        ]);

        $response->assertRedirect(route('2fa.challenge'));
        $this->assertGuest(); // Not yet authenticated
    }
}
