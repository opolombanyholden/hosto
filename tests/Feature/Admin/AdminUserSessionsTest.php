<?php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminUserSessionsTest extends TestCase
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

    public function test_list_user_sessions(): void
    {
        $admin = $this->adminWith(['users.sessions']);
        $target = User::factory()->create();
        $target->createToken('test-device');

        $resp = $this->actingAs($admin)->get('/admin/utilisateurs/'.$target->uuid.'/sessions');
        $resp->assertOk();
        $resp->assertSeeText('test-device');
    }

    public function test_revoke_session(): void
    {
        $admin = $this->adminWith(['users.sessions']);
        $target = User::factory()->create();
        $token = $target->createToken('to-revoke');
        $tokenId = $token->accessToken->id;

        $resp = $this->actingAs($admin)->delete('/admin/utilisateurs/'.$target->uuid.'/sessions/'.$tokenId);
        $resp->assertRedirect();
        $this->assertSame(0, $target->tokens()->count());
    }
}
