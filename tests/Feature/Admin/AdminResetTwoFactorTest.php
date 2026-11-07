<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use App\Modules\Core\Services\TwoFactorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class AdminResetTwoFactorTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->adminWith(['users.reset_2fa', 'users.view']);
        $this->doctor = User::factory()->create(['email' => 'docteur.perdu@example.org']);
        $this->doctor->roles()->attach(Role::firstOrCreate(['slug' => 'doctor'], ['name_fr' => 'Medecin', 'name_en' => 'Doctor', 'environment' => 'pro']));
        Practitioner::factory()->create(['user_id' => $this->doctor->id]);
        app(TwoFactorService::class)->enable($this->doctor, app(TwoFactorService::class)->generateSecret());
        $this->doctor->forceFill(['remember_token' => 'ancien-jeton-de-session'])->save();
        $this->doctor->createToken('mobile');
    }

    public function test_admin_resets_the_second_factor_and_the_user_must_enrol_again(): void
    {
        Mail::fake();

        $this->reset($this->admin, $this->doctor)->assertRedirect()->assertSessionHas('success');

        $this->doctor->refresh();
        $this->assertNull($this->doctor->two_factor_secret);
        $this->assertNull($this->doctor->two_factor_confirmed_at);
        $this->assertFalse(app(TwoFactorService::class)->isEnabled($this->doctor));

        config(['hosto.security.two_factor_required' => ['admin', 'pro']]);
        $this->actingAs($this->doctor)->get('/pro')->assertRedirect(route('2fa.setup'));
    }

    public function test_the_user_is_logged_out_everywhere(): void
    {
        $this->reset($this->admin, $this->doctor);

        $this->assertNotSame('ancien-jeton-de-session', $this->doctor->refresh()->remember_token);
        $this->assertSame(0, $this->doctor->tokens()->count());
    }

    public function test_the_reset_is_audited_with_its_reason_and_author(): void
    {
        $this->reset($this->admin, $this->doctor);

        $entry = DB::table('audit_logs')->where('action', 'user.2fa_reset')->where('resource_uuid', $this->doctor->uuid)->first();
        $this->assertNotNull($entry);
        $this->assertStringContainsString('Identite verifiee en visio', (string) $entry->payload);
        $this->assertSame($this->admin->uuid, $entry->actor_uuid);
    }

    public function test_the_user_is_told_by_email(): void
    {
        $transport = app('mail.manager')->mailer('array')->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);
        $transport->flush();

        $this->reset($this->admin, $this->doctor);

        $sent = $transport->messages()
            ->filter(fn ($message) => str_contains($message->toString(), 'docteur.perdu@example.org'));
        $this->assertCount(1, $sent);
        $this->assertStringContainsString('Double authentification reinitialisee', $sent->first()->toString());
    }

    public function test_a_reason_is_required(): void
    {
        $this->reset($this->admin, $this->doctor, '')->assertSessionHasErrors('reason');

        $this->assertNotNull($this->doctor->refresh()->two_factor_secret);
    }

    public function test_permission_is_required(): void
    {
        $other = $this->adminWith(['users.view']);

        $this->reset($other, $this->doctor)->assertForbidden();
        $this->assertNotNull($this->doctor->refresh()->two_factor_secret);
    }

    public function test_an_admin_cannot_reset_their_own_second_factor(): void
    {
        app(TwoFactorService::class)->enable($this->admin, app(TwoFactorService::class)->generateSecret());

        $this->reset($this->admin, $this->admin)->assertSessionHas('error');

        $this->assertNotNull($this->admin->refresh()->two_factor_secret);
    }

    public function test_reset_link_is_not_written_to_logs_outside_local(): void
    {
        $log = Log::spy();
        Mail::fake();

        $this->post('/mot-de-passe/oublie', ['email' => $this->doctor->email]);

        $log->shouldNotHaveReceived('info', [Mockery::pattern('/reinitialiser|token=/i')]);
    }

    /**
     * @param  list<string>  $perms
     */
    private function adminWith(array $perms): User
    {
        $user = User::factory()->create();
        $role = Role::create(['slug' => 'ad_'.uniqid(), 'name_fr' => 'A', 'name_en' => 'A', 'environment' => 'admin']);
        foreach ($perms as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['scope' => explode('.', $slug)[0], 'name_fr' => $slug]);
            $role->permissions()->attach($permission->id);
        }
        UserRoleAssignment::create(['user_id' => $user->id, 'role_id' => $role->id]);

        return $user;
    }

    /**
     * @return TestResponse<Response>
     */
    private function reset(User $as, User $target, string $reason = 'Identite verifiee en visio avec piece d\'identite'): TestResponse
    {
        return $this->actingAs($as)->post("/admin/utilisateurs/{$target->uuid}/reset-2fa", ['reason' => $reason]);
    }
}
