<?php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminUsersActionsTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(array $perms): User
    {
        $u = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => 'admin_test_'.uniqid()], ['name_fr' => 'AT', 'name_en' => 'AT', 'environment' => 'admin']);
        foreach ($perms as $slug) {
            $p = Permission::firstOrCreate(['slug' => $slug], ['scope' => explode('.', $slug)[0], 'name_fr' => $slug]);
            $role->permissions()->attach($p->id);
        }
        UserRoleAssignment::firstOrCreate([
            'user_id' => $u->id, 'role_id' => $role->id, 'scope_type' => null, 'scope_id' => null,
        ], ['assigned_at' => now()]);
        return $u;
    }

    public function test_create_user_via_post(): void
    {
        $admin = $this->adminWith(['users.view', 'users.create']);
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs', [
            'name' => 'Nouveau Test', 'email' => 'nouveau@test.com', 'phone' => '+24100000000',
        ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('users', ['name' => 'Nouveau Test', 'email' => 'nouveau@test.com']);
    }

    public function test_update_user(): void
    {
        $admin = $this->adminWith(['users.view', 'users.edit']);
        $target = User::factory()->create(['name' => 'Old Name']);
        $resp = $this->actingAs($admin)->put('/admin/utilisateurs/'.$target->uuid, ['name' => 'New Name', 'email' => $target->email]);
        $resp->assertRedirect();
        $this->assertSame('New Name', $target->fresh()->name);
    }

    public function test_suspend_user(): void
    {
        $admin = $this->adminWith(['users.view', 'users.suspend']);
        $target = User::factory()->create();
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/suspend', ['reason' => 'Test']);
        $resp->assertRedirect();
        $this->assertNotNull($target->fresh()->locked_until);
    }

    public function test_reactivate_user(): void
    {
        $admin = $this->adminWith(['users.view', 'users.suspend']);
        $target = User::factory()->create(['locked_until' => '9999-12-31']);
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/reactivate');
        $resp->assertRedirect();
        $this->assertNull($target->fresh()->locked_until);
    }

    public function test_reset_password_returns_temp_password(): void
    {
        $admin = $this->adminWith(['users.view', 'users.reset_password']);
        $target = User::factory()->create();
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/reset-password');
        $resp->assertRedirect();
        $resp->assertSessionHas('temp_password');
        $this->assertTrue((bool) $target->fresh()->must_change_password);
    }

    public function test_validate_pro_approve(): void
    {
        $admin = $this->adminWith(['users.view', 'users.validate_pro']);
        $target = User::factory()->create();
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/validate-pro', ['action' => 'approve']);
        $resp->assertRedirect();
        $this->assertNotNull($target->fresh()->pro_validated_at);
    }

    public function test_delete_and_restore(): void
    {
        $admin = $this->adminWith(['users.view', 'users.delete']);
        $target = User::factory()->create();
        $resp = $this->actingAs($admin)->delete('/admin/utilisateurs/'.$target->uuid);
        $resp->assertRedirect();
        $this->assertSoftDeleted($target);

        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/restore');
        $resp->assertRedirect();
        $this->assertNull($target->fresh()->deleted_at);
    }

    public function test_export_csv(): void
    {
        $admin = $this->adminWith(['users.view', 'exports.users']);
        User::factory()->count(3)->create();
        $resp = $this->actingAs($admin)->get('/admin/utilisateurs/export.csv');
        $resp->assertOk();
        $resp->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_bulk_suspend(): void
    {
        $admin = $this->adminWith(['users.view', 'users.bulk', 'users.suspend']);
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/bulk', [
            'user_uuids' => [$u1->uuid, $u2->uuid],
            'action' => 'suspend',
            'params' => ['reason' => 'Bulk'],
        ]);
        $resp->assertRedirect();
        $this->assertNotNull($u1->fresh()->locked_until);
        $this->assertNotNull($u2->fresh()->locked_until);
    }
}
