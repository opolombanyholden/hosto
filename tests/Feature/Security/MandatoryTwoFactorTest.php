<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Services\TwoFactorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Admin and pro spaces give access to medical data of many patients:
 * a second factor is mandatory there, and must be proven in each session.
 */
final class MandatoryTwoFactorTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hosto.security.two_factor_required' => ['admin', 'pro']]);
        $this->app['cache']->store()->flush();
    }

    public function test_pro_without_second_factor_is_sent_to_enrolment(): void
    {
        $doctor = $this->userIn('pro', 'doctor');

        $this->actingAs($doctor)->get('/pro')->assertRedirect(route('2fa.setup'));
        $this->actingAs($doctor)->followingRedirects()->get('/pro')->assertOk()->assertSee('obligatoire');
    }

    public function test_admin_without_second_factor_is_sent_to_enrolment(): void
    {
        $admin = $this->userIn('admin', 'super_admin');

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('2fa.setup'));
    }

    public function test_pharmacist_and_lab_staff_are_covered_as_pro(): void
    {
        $pharmacist = $this->userIn('pro', 'pharmacist');

        $this->actingAs($pharmacist)->get('/pro/pharmacie')->assertRedirect(route('2fa.setup'));
    }

    public function test_patients_are_not_forced(): void
    {
        $patient = $this->userIn('usager', 'patient');

        $this->actingAs($patient)->get('/compte')->assertOk();
    }

    public function test_api_calls_get_a_clear_error(): void
    {
        $doctor = $this->userIn('pro', 'doctor');

        $this->actingAs($doctor)->getJson('/pro/patients')->assertForbidden()->assertJsonPath('error.code', 'TWO_FACTOR_REQUIRED');
    }

    public function test_enrolment_then_access_and_return_to_the_pro_space(): void
    {
        $doctor = $this->userIn('pro', 'doctor');
        Practitioner::factory()->create(['user_id' => $doctor->id]);
        $this->actingAs($doctor)->get('/pro');
        $this->actingAs($doctor)->get(route('2fa.setup'));
        $secret = (string) session('2fa_secret');

        $this->actingAs($doctor)->post(route('2fa.confirm'), ['code' => $this->code($secret)])->assertRedirect(route('2fa.recovery'));
        $this->actingAs($doctor)->get(route('2fa.recovery'))->assertOk()->assertSee('/pro', false);

        $this->actingAs($doctor)->get('/pro')->assertOk();
    }

    public function test_full_login_with_challenge_opens_the_pro_space(): void
    {
        [$doctor, $secret] = $this->enrolledDoctor();
        RateLimiter::clear('');

        $this->post('/pro/connexion', ['login_mode' => 'email', 'email' => $doctor->email, 'password' => 'Secret2026!'])
            ->assertRedirect(route('2fa.challenge'));
        $this->assertGuest();

        $this->post('/2fa/verify', ['code' => $this->code($secret)])->assertRedirect('/pro');
        $this->get('/pro')->assertOk();
    }

    public function test_a_session_reopened_without_the_code_is_challenged(): void
    {
        [$doctor] = $this->enrolledDoctor();

        // Same as a "remember me" cookie: authenticated, but no code given in this session.
        $this->actingAs($doctor)->get('/pro')->assertRedirect(route('2fa.challenge'));

        $this->assertGuest();
        $this->assertSame($doctor->id, session('2fa_user_id'));
        $this->assertSame('pro', session('2fa_environment'));
    }

    public function test_a_session_where_the_code_was_given_passes(): void
    {
        [$doctor] = $this->enrolledDoctor();

        $this->actingAs($doctor)->withSession(['two_factor_passed_for' => $doctor->id])->get('/pro')->assertOk();
    }

    public function test_the_proof_belongs_to_one_user(): void
    {
        [$doctor] = $this->enrolledDoctor();

        $this->actingAs($doctor)->withSession(['two_factor_passed_for' => $doctor->id + 1])->get('/pro')
            ->assertRedirect(route('2fa.challenge'));
    }

    public function test_enforcement_can_be_switched_off_by_configuration(): void
    {
        config(['hosto.security.two_factor_required' => []]);
        $doctor = $this->userIn('pro', 'doctor');
        Practitioner::factory()->create(['user_id' => $doctor->id]);

        $this->actingAs($doctor)->get('/pro')->assertOk();
    }

    private function userIn(string $environment, string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name_fr' => $slug, 'name_en' => $slug, 'environment' => $environment]);
        $user = User::factory()->create(['password' => 'Secret2026!', 'email_verified_at' => now(), 'phone_verified_at' => now()]);
        $user->roles()->attach($role);

        return $user;
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function enrolledDoctor(): array
    {
        $doctor = $this->userIn('pro', 'doctor');
        Practitioner::factory()->create(['user_id' => $doctor->id]);
        $secret = app(TwoFactorService::class)->generateSecret();
        app(TwoFactorService::class)->enable($doctor, $secret);

        return [$doctor->refresh(), $secret];
    }

    private function code(string $secret): string
    {
        return (new Google2FA)->getCurrentOtp($secret);
    }
}
