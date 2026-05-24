<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Core\Models\ImpersonationSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ImpersonationSessionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_persists_with_admin_target_and_reason(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $session = ImpersonationSession::create([
            'admin_user_id' => $admin->id,
            'target_user_id' => $target->id,
            'reason' => 'Support ticket #42',
            'started_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $this->assertNotNull($session->uuid);
        $this->assertSame($admin->id, $session->admin->id);
        $this->assertSame($target->id, $session->target->id);
        $this->assertNull($session->ended_at);
    }

    public function test_user_must_change_password_defaults_to_false(): void
    {
        $u = User::factory()->create();
        $this->assertFalse((bool) $u->must_change_password);
    }
}
