<?php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Core\Models\ImpersonationSession;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(array $perms): User
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'ad_'.uniqid(), 'name_fr' => 'A', 'name_en' => 'A', 'environment' => 'admin']);
        foreach ($perms as $slug) {
            $p = Permission::firstOrCreate(['slug' => $slug], ['scope' => explode('.', $slug)[0], 'name_fr' => $slug]);
            $role->permissions()->attach($p->id);
        }
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);
        return $u;
    }

    public function test_impersonate_creates_session(): void
    {
        $admin = $this->adminWith(['users.impersonate']);
        $target = User::factory()->create();
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/impersonate', [
            'reason' => 'Support test',
        ]);
        $resp->assertRedirect();
        $this->assertSame(1, ImpersonationSession::where('admin_user_id', $admin->id)->count());
        $this->assertSame($target->id, auth()->id());
    }

    public function test_impersonate_super_admin_returns_403(): void
    {
        $admin = $this->adminWith(['users.impersonate']);
        $target = User::factory()->create();
        $sa = Role::create(['slug' => 'super_admin', 'name_fr' => 'SA', 'name_en' => 'SA', 'environment' => 'admin', 'is_system' => true]);
        UserRoleAssignment::create(['user_id' => $target->id, 'role_id' => $sa->id]);

        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/impersonate', [
            'reason' => 'X',
        ]);
        $resp->assertStatus(403);
    }

    public function test_impersonate_self_returns_422(): void
    {
        $admin = $this->adminWith(['users.impersonate']);
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$admin->uuid.'/impersonate', [
            'reason' => 'X',
        ]);
        $resp->assertStatus(422);
    }

    public function test_stop_impersonate_restores_admin(): void
    {
        $admin = $this->adminWith(['users.impersonate']);
        $target = User::factory()->create();
        $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/impersonate', ['reason' => 'X']);

        $resp = $this->get('/stop-impersonate');
        $resp->assertRedirect();
        $this->assertSame($admin->id, auth()->id());

        $session = ImpersonationSession::where('admin_user_id', $admin->id)->first();
        $this->assertNotNull($session->ended_at);
    }
}
