<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use App\Modules\AI\Models\ChatbotConversation;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use App\Modules\Telecon\Models\Teleconsultation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SecurityAuditFixesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('');
        $this->app['cache']->store()->flush();
    }

    public function test_teleconsultation_api_is_not_public(): void
    {
        $tc = $this->teleconsultation(User::factory()->create(), User::factory()->create());

        $this->getJson("/api/v1/telecon/sessions/{$tc->uuid}")->assertUnauthorized();
    }

    public function test_teleconsultation_api_hides_the_room_from_outsiders(): void
    {
        $tc = $this->teleconsultation(User::factory()->create(), User::factory()->create());
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/telecon/sessions/{$tc->uuid}")->assertNotFound();
    }

    public function test_teleconsultation_api_serves_its_participants(): void
    {
        $patient = User::factory()->create();
        $doctor = User::factory()->create();
        $tc = $this->teleconsultation($patient, $doctor);

        Sanctum::actingAs($patient);
        $this->getJson("/api/v1/telecon/sessions/{$tc->uuid}")->assertOk()->assertJsonPath('data.room_name', $tc->room_name);

        Sanctum::actingAs($doctor);
        $this->getJson("/api/v1/telecon/sessions/{$tc->uuid}")->assertOk();
    }

    public function test_an_old_reset_link_is_refused(): void
    {
        $user = User::factory()->create(['email' => 'reset-old@example.org']);
        $token = $this->resetRequest($user->email, 61);

        $this->post('/mot-de-passe/reinitialiser', [
            'email' => $user->email, 'token' => $token, 'password' => 'Nouveau2026!', 'password_confirmation' => 'Nouveau2026!',
        ])->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('Nouveau2026!', (string) $user->refresh()->password));
    }

    public function test_a_recent_reset_link_works(): void
    {
        $user = User::factory()->create(['email' => 'reset-new@example.org']);
        $token = $this->resetRequest($user->email, 10);

        $this->post('/mot-de-passe/reinitialiser', [
            'email' => $user->email, 'token' => $token, 'password' => 'Nouveau2026!', 'password_confirmation' => 'Nouveau2026!',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Nouveau2026!', (string) $user->refresh()->password));
    }

    public function test_login_attempts_are_rate_limited_per_client(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->post('/compte/connexion', ['login_mode' => 'email', 'email' => "inconnu{$i}@example.org", 'password' => 'x']);
        }

        $this->post('/compte/connexion', ['login_mode' => 'email', 'email' => 'inconnu11@example.org', 'password' => 'x'])
            ->assertStatus(429);
    }

    public function test_two_factor_codes_cannot_be_brute_forced(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->withSession(['2fa_user_id' => $user->id])->post('/2fa/verify', ['code' => '000000']);
        }

        $this->withSession(['2fa_user_id' => $user->id])->post('/2fa/verify', ['code' => '000000'])->assertStatus(429);
    }

    public function test_password_reset_requests_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/mot-de-passe/oublie', ['email' => "x{$i}@example.org"]);
        }

        $this->post('/mot-de-passe/oublie', ['email' => 'x9@example.org'])->assertStatus(429);
    }

    public function test_chat_conversation_of_another_user_is_not_reachable(): void
    {
        $owner = User::factory()->create();
        $conversation = ChatbotConversation::create(['user_id' => $owner->id, 'topic' => 'general', 'status' => 'active']);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/ai/chat/{$conversation->uuid}/message", ['message' => 'Bonjour'])->assertNotFound();
    }

    public function test_public_health_statistics_require_a_permission(): void
    {
        $this->getJson('/api/v1/analytic/dashboard')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/analytic/dashboard')->assertForbidden();

        Sanctum::actingAs($this->userWith(['stats.view']));
        $this->getJson('/api/v1/analytic/dashboard')->assertOk();
    }

    public function test_sync_is_reserved_to_local_instances(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/sync/pull')->assertForbidden();

        Sanctum::actingAs($this->userWith(['sync.manage']));
        $this->getJson('/api/v1/sync/pull')->assertOk();
    }

    /**
     * @param  list<string>  $perms
     */
    private function userWith(array $perms): User
    {
        $user = User::factory()->create();
        $role = Role::create(['slug' => 'r_'.uniqid(), 'name_fr' => 'R', 'name_en' => 'R', 'environment' => 'admin']);
        foreach ($perms as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['scope' => explode('.', $slug)[0], 'name_fr' => $slug]);
            $role->permissions()->attach($permission->id);
        }
        UserRoleAssignment::create(['user_id' => $user->id, 'role_id' => $role->id]);

        return $user;
    }

    private function teleconsultation(User $patient, User $doctor): Teleconsultation
    {
        return Teleconsultation::create([
            'practitioner_id' => Practitioner::factory()->create(['user_id' => $doctor->id])->id,
            'patient_id' => $patient->id, 'room_name' => Teleconsultation::generateRoomName(),
            'jitsi_domain' => 'visio.example.org', 'status' => 'scheduled', 'scheduled_at' => now(),
        ]);
    }

    private function resetRequest(string $email, int $minutesAgo): string
    {
        $token = 'jeton-de-test-'.bin2hex(random_bytes(8));
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email' => $email, 'token' => Hash::make($token), 'created_at' => now()->subMinutes($minutesAgo),
        ]);

        return $token;
    }
}
