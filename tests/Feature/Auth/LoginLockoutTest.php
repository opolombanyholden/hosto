<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class LoginLockoutTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);
        $this->user = User::factory()->create(['email' => 'lock@example.org', 'password' => 'Secret2026!']);
        $this->user->roles()->attach($role);
    }

    public function test_account_locks_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->login('mauvais');
        }

        $this->assertTrue($this->user->refresh()->locked_until?->isFuture());

        $this->login('Secret2026!');
        $this->assertGuest();
    }

    public function test_successful_login_resets_the_counter(): void
    {
        $this->login('mauvais');
        $this->login('mauvais');
        $this->login('Secret2026!');

        $this->assertAuthenticated();
        $this->assertSame(0, $this->user->refresh()->failed_login_attempts);
    }

    public function test_account_blocked_by_an_admin_cannot_log_in(): void
    {
        $this->user->forceFill(['locked_until' => '9999-12-31 23:59:59'])->save();

        $this->login('Secret2026!')->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_expired_lock_no_longer_blocks(): void
    {
        $this->user->forceFill(['locked_until' => now()->subMinute()])->save();

        $this->login('Secret2026!');

        $this->assertAuthenticated();
    }

    private function login(string $password): TestResponse
    {
        return $this->post('/compte/connexion', ['login_mode' => 'email', 'email' => 'lock@example.org', 'password' => $password]);
    }
}
