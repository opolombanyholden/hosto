<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Core\Models\ImpersonationSession;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use App\Modules\Core\Services\ImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

final class ImpersonationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImpersonationService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new ImpersonationService();
    }

    public function test_start_persists_session(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $req = Request::create('/x', 'POST', server: ['REMOTE_ADDR' => '1.2.3.4', 'HTTP_USER_AGENT' => 'Test/1.0']);

        $session = $this->svc->start($admin, $target, 'Support', $req);
        $this->assertNotNull($session->id);
        $this->assertSame($admin->id, $session->admin_user_id);
        $this->assertSame($target->id, $session->target_user_id);
        $this->assertSame('Support', $session->reason);
    }

    public function test_start_refuses_super_admin_target(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $role = Role::create(['slug' => 'super_admin', 'name_fr' => 'SA', 'name_en' => 'SA', 'environment' => 'admin', 'is_system' => true]);
        UserRoleAssignment::create(['user_id' => $target->id, 'role_id' => $role->id]);

        $this->expectException(\DomainException::class);
        $this->svc->start($admin, $target, 'X', Request::create('/x'));
    }

    public function test_start_refuses_self(): void
    {
        $u = User::factory()->create();
        $this->expectException(\DomainException::class);
        $this->svc->start($u, $u, 'X', Request::create('/x'));
    }

    public function test_stop_marks_ended_at(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $session = $this->svc->start($admin, $target, 'X', Request::create('/x'));
        session(['impersonation_session_id' => $session->uuid, 'impersonator_id' => $admin->id]);

        $this->svc->stop();

        $this->assertNotNull(ImpersonationSession::find($session->id)->ended_at);
    }
}
