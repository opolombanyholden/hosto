<?php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminUsersIndexTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithPermissions(array $permSlugs): User
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'admin_test_'.uniqid(), 'name_fr' => 'AT', 'name_en' => 'AT', 'environment' => 'admin']);
        foreach ($permSlugs as $slug) {
            $p = Permission::firstOrCreate(['slug' => $slug], ['scope' => explode('.', $slug)[0], 'name_fr' => $slug]);
            $role->permissions()->attach($p->id);
        }
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);
        return $u;
    }

    public function test_guest_redirects_to_login(): void
    {
        $this->get('/admin/utilisateurs')->assertRedirect();
    }

    public function test_user_without_view_perm_returns_403(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->get('/admin/utilisateurs')->assertForbidden();
    }

    public function test_admin_with_view_perm_sees_list(): void
    {
        $admin = $this->adminWithPermissions(['users.view']);
        User::factory()->count(5)->create();
        $resp = $this->actingAs($admin)->get('/admin/utilisateurs');
        $resp->assertOk();
        $resp->assertSee('utilisateur');
    }

    public function test_filter_by_q_matches_name(): void
    {
        $admin = $this->adminWithPermissions(['users.view']);
        User::factory()->create(['name' => 'Marie NDONG']);
        User::factory()->create(['name' => 'Jean MBAYE']);
        $resp = $this->actingAs($admin)->get('/admin/utilisateurs?q=ndong');
        $resp->assertOk();
        $resp->assertSeeText('Marie NDONG');
        $resp->assertDontSeeText('Jean MBAYE');
    }

    public function test_show_user_detail(): void
    {
        $admin = $this->adminWithPermissions(['users.view']);
        $target = User::factory()->create(['name' => 'Cible Test']);
        $resp = $this->actingAs($admin)->get('/admin/utilisateurs/'.$target->uuid);
        $resp->assertOk();
        $resp->assertSeeText('Cible Test');
    }
}
