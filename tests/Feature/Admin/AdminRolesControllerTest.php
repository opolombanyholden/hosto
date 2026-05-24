<?php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminRolesControllerTest extends TestCase
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

    public function test_list_roles(): void
    {
        $admin = $this->adminWith(['roles.view']);
        Role::create(['slug' => 'r_test', 'name_fr' => 'R Test', 'name_en' => 'R Test', 'environment' => 'admin']);
        $resp = $this->actingAs($admin)->get('/admin/roles');
        $resp->assertOk();
        $resp->assertSeeText('R Test');
    }

    public function test_assign_permissions_via_matrix(): void
    {
        $admin = $this->adminWith(['permissions.assign']);
        $role = Role::create(['slug' => 'r_p', 'name_fr' => 'RP', 'name_en' => 'RP', 'environment' => 'admin']);
        $p1 = Permission::firstOrCreate(['slug' => 'users.view'], ['scope' => 'users', 'name_fr' => 'V']);
        $p2 = Permission::firstOrCreate(['slug' => 'users.delete'], ['scope' => 'users', 'name_fr' => 'D']);

        $resp = $this->actingAs($admin)->put('/admin/roles/'.$role->slug.'/permissions', [
            'permission_ids' => [$p1->id, $p2->id],
        ]);
        $resp->assertRedirect();
        $this->assertCount(2, $role->fresh()->permissions);
    }

    public function test_super_admin_matrix_refuses_modification(): void
    {
        $admin = $this->adminWith(['permissions.assign']);
        $sa = Role::create(['slug' => 'super_admin', 'name_fr' => 'SA', 'name_en' => 'SA', 'environment' => 'admin', 'is_system' => true]);
        $p = Permission::firstOrCreate(['slug' => 'users.view'], ['scope' => 'users', 'name_fr' => 'V']);

        $resp = $this->actingAs($admin)->put('/admin/roles/'.$sa->slug.'/permissions', [
            'permission_ids' => [$p->id],
        ]);
        $resp->assertStatus(422);
        $this->assertCount(0, $sa->fresh()->permissions);
    }

    public function test_permissions_index_lists_catalog(): void
    {
        $admin = $this->adminWith(['permissions.view']);
        Permission::firstOrCreate(['slug' => 'users.view'], ['scope' => 'users', 'name_fr' => 'V']);
        $resp = $this->actingAs($admin)->get('/admin/permissions');
        $resp->assertOk();
    }
}
