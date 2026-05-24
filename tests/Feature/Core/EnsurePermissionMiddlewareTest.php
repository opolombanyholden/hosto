<?php
declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Models\User;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class EnsurePermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware(['web', 'auth', 'perm:users.view'])
            ->get('/test-perm-route', fn () => 'OK')
            ->name('test.perm');
    }

    public function test_guest_returns_401_or_redirect(): void
    {
        $resp = $this->get('/test-perm-route');
        $this->assertContains($resp->getStatusCode(), [302, 401]);
    }

    public function test_user_without_perm_returns_403(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->get('/test-perm-route')->assertForbidden();
    }

    public function test_user_with_perm_via_role_passes(): void
    {
        $u = User::factory()->create();
        $r = Role::create(['slug' => 'r', 'name_fr' => 'R', 'name_en' => 'R', 'environment' => 'admin']);
        $p = Permission::create(['slug' => 'users.view', 'scope' => 'users', 'name_fr' => 'V']);
        $r->permissions()->attach($p->id);
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $r->id]);

        $this->actingAs($u)->get('/test-perm-route')->assertOk()->assertSeeText('OK');
    }
}
