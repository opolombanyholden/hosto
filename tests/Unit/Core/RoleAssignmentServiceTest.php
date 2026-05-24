<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use App\Modules\Core\Services\RoleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RoleAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleAssignmentService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new RoleAssignmentService();
    }

    public function test_assign_global_role(): void
    {
        $u = User::factory()->create();
        $r = Role::create(['slug' => 'r', 'name_fr' => 'R', 'name_en' => 'R', 'environment' => 'admin']);
        $this->svc->assign($u, $r);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $u->id, 'role_id' => $r->id,
            'scope_type' => null, 'scope_id' => null,
        ]);
    }

    public function test_assign_scoped_role(): void
    {
        $u = User::factory()->create();
        $r = Role::create(['slug' => 'doc', 'name_fr' => 'D', 'name_en' => 'D', 'environment' => 'pro']);
        $h = Hosto::factory()->create();
        $this->svc->assign($u, $r, $h);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $u->id, 'role_id' => $r->id,
            'scope_type' => Hosto::class, 'scope_id' => $h->id,
        ]);
    }

    public function test_assign_is_idempotent(): void
    {
        $u = User::factory()->create();
        $r = Role::create(['slug' => 'r', 'name_fr' => 'R', 'name_en' => 'R', 'environment' => 'admin']);
        $this->svc->assign($u, $r);
        $this->svc->assign($u, $r); // ne crée pas de doublon
        $this->assertSame(1, UserRoleAssignment::where('user_id', $u->id)->count());
    }

    public function test_revoke_removes_only_targeted_scope(): void
    {
        $u = User::factory()->create();
        $r = Role::create(['slug' => 'doc', 'name_fr' => 'D', 'name_en' => 'D', 'environment' => 'pro']);
        $h1 = Hosto::factory()->create();
        $h2 = Hosto::factory()->create();
        $this->svc->assign($u, $r, $h1);
        $this->svc->assign($u, $r, $h2);

        $this->svc->revoke($u, $r, $h1);

        $this->assertSame(1, UserRoleAssignment::where('user_id', $u->id)->count());
        $this->assertTrue(
            UserRoleAssignment::where('user_id', $u->id)
                ->where('scope_id', $h2->id)->exists()
        );
    }

    public function test_assign_records_assigned_by_and_assigned_at(): void
    {
        $u = User::factory()->create();
        $admin = User::factory()->create();
        $r = Role::create(['slug' => 'r', 'name_fr' => 'R', 'name_en' => 'R', 'environment' => 'admin']);
        $this->svc->assign($u, $r, null, $admin);

        $row = UserRoleAssignment::where('user_id', $u->id)->first();
        $this->assertSame($admin->id, $row->assigned_by);
        $this->assertNotNull($row->assigned_at);
    }

    public function test_assign_with_expiry(): void
    {
        $u = User::factory()->create();
        $r = Role::create(['slug' => 'r', 'name_fr' => 'R', 'name_en' => 'R', 'environment' => 'admin']);
        $expiry = now()->addDays(7);
        $this->svc->assign($u, $r, null, null, $expiry);

        $row = UserRoleAssignment::where('user_id', $u->id)->first();
        $this->assertNotNull($row->expires_at);
    }

    public function test_sync_replaces_role_set_for_scope(): void
    {
        $u = User::factory()->create();
        $r1 = Role::create(['slug' => 'r1', 'name_fr' => 'R1', 'name_en' => 'R1', 'environment' => 'admin']);
        $r2 = Role::create(['slug' => 'r2', 'name_fr' => 'R2', 'name_en' => 'R2', 'environment' => 'admin']);
        $r3 = Role::create(['slug' => 'r3', 'name_fr' => 'R3', 'name_en' => 'R3', 'environment' => 'admin']);

        $this->svc->assign($u, $r1);
        $this->svc->assign($u, $r2);

        $this->svc->syncRolesForScope($u, [$r2, $r3], null);

        $slugs = UserRoleAssignment::where('user_id', $u->id)
            ->with('role')->get()->pluck('role.slug')->all();
        sort($slugs);
        $this->assertSame(['r2', 'r3'], $slugs);
    }
}
