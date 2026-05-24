<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use App\Modules\Core\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PermissionResolverTest extends TestCase
{
    use RefreshDatabase;

    private PermissionResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new PermissionResolver();
    }

    public function test_super_admin_has_any_permission(): void
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'super_admin', 'name_fr' => 'SA', 'name_en' => 'SA', 'environment' => 'admin', 'is_system' => true]);
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);

        $this->assertTrue($this->resolver->userCan($u, 'anything.you.want'));
        $this->assertTrue($this->resolver->userCan($u, 'users.delete'));
    }

    public function test_user_without_role_has_no_permission(): void
    {
        $u = User::factory()->create();
        $this->assertFalse($this->resolver->userCan($u, 'users.view'));
    }

    public function test_role_grants_inherited_permission(): void
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'r', 'name_fr' => 'R', 'name_en' => 'R', 'environment' => 'admin']);
        $perm = Permission::create(['slug' => 'users.view', 'scope' => 'users', 'name_fr' => 'V']);
        $role->permissions()->attach($perm->id);
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);

        $this->assertTrue($this->resolver->userCan($u, 'users.view'));
        $this->assertFalse($this->resolver->userCan($u, 'users.delete'));
    }

    public function test_scoped_role_grants_only_for_that_scope(): void
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'doctor_t', 'name_fr' => 'D', 'name_en' => 'D', 'environment' => 'pro']);
        $perm = Permission::create(['slug' => 'consultations.manage', 'scope' => 'consultations', 'name_fr' => 'CM']);
        $role->permissions()->attach($perm->id);
        $h1 = Hosto::factory()->create();
        $h2 = Hosto::factory()->create();

        UserRoleAssignment::create([
            'user_id' => $u->id, 'role_id' => $role->id,
            'scope_type' => Hosto::class, 'scope_id' => $h1->id,
        ]);

        $this->assertTrue($this->resolver->userCan($u, 'consultations.manage', $h1));
        $this->assertFalse($this->resolver->userCan($u, 'consultations.manage', $h2));
        // Sans scope passé : on autorise (global semantics)
        $this->assertTrue($this->resolver->userCan($u, 'consultations.manage'));
    }

    public function test_expired_assignment_does_not_grant(): void
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'tmp', 'name_fr' => 'T', 'name_en' => 'T', 'environment' => 'admin']);
        $perm = Permission::create(['slug' => 'users.view', 'scope' => 'users', 'name_fr' => 'V']);
        $role->permissions()->attach($perm->id);
        UserRoleAssignment::create([
            'user_id' => $u->id, 'role_id' => $role->id,
            'expires_at' => now()->subDay(),
        ]);
        $this->assertFalse($this->resolver->userCan($u, 'users.view'));
    }

    public function test_global_role_grants_for_any_scope_query(): void
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'mod', 'name_fr' => 'M', 'name_en' => 'M', 'environment' => 'admin']);
        $perm = Permission::create(['slug' => 'structures.view', 'scope' => 'structures', 'name_fr' => 'SV']);
        $role->permissions()->attach($perm->id);
        $h = Hosto::factory()->create();
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]); // global

        $this->assertTrue($this->resolver->userCan($u, 'structures.view', $h));
        $this->assertTrue($this->resolver->userCan($u, 'structures.view'));
    }

    public function test_user_permissions_returns_collection(): void
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'r1', 'name_fr' => 'R', 'name_en' => 'R', 'environment' => 'admin']);
        $p1 = Permission::create(['slug' => 'users.view', 'scope' => 'users', 'name_fr' => 'V']);
        $p2 = Permission::create(['slug' => 'roles.view', 'scope' => 'roles', 'name_fr' => 'RV']);
        $role->permissions()->attach([$p1->id, $p2->id]);
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);

        $perms = $this->resolver->userPermissions($u);
        $this->assertCount(2, $perms);
    }
}
