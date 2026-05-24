<?php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminRoleAssignmentEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(array $perms): User
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'admin_tt_'.uniqid(), 'name_fr' => 'AT', 'name_en' => 'AT', 'environment' => 'admin']);
        foreach ($perms as $slug) {
            $p = Permission::firstOrCreate(['slug' => $slug], ['scope' => explode('.', $slug)[0], 'name_fr' => $slug]);
            $role->permissions()->attach($p->id);
        }
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);
        return $u;
    }

    public function test_assign_global_roles_to_user(): void
    {
        $admin = $this->adminWith(['users.view', 'roles.assign']);
        $target = User::factory()->create();
        $r1 = Role::create(['slug' => 'patient_t', 'name_fr' => 'P', 'name_en' => 'P', 'environment' => 'usager']);
        $r2 = Role::create(['slug' => 'compta_t', 'name_fr' => 'C', 'name_en' => 'C', 'environment' => 'admin']);

        $resp = $this->actingAs($admin)->put('/admin/utilisateurs/'.$target->uuid.'/roles', [
            'global_role_ids' => [$r1->id, $r2->id],
            'scoped' => [],
        ]);
        $resp->assertRedirect();
        $this->assertSame(2, UserRoleAssignment::where('user_id', $target->id)->whereNull('scope_type')->count());
    }

    public function test_assign_scoped_roles(): void
    {
        $admin = $this->adminWith(['users.view', 'roles.assign']);
        $target = User::factory()->create();
        $r = Role::create(['slug' => 'doctor_t', 'name_fr' => 'D', 'name_en' => 'D', 'environment' => 'pro']);
        $hosto = Hosto::factory()->create();

        $resp = $this->actingAs($admin)->put('/admin/utilisateurs/'.$target->uuid.'/roles', [
            'global_role_ids' => [],
            'scoped' => [['structure_uuid' => $hosto->uuid, 'role_ids' => [$r->id]]],
        ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $target->id, 'role_id' => $r->id,
            'scope_type' => Hosto::class, 'scope_id' => $hosto->id,
        ]);
    }
}
