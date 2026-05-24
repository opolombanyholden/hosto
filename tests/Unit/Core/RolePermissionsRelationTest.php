<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RolePermissionsRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_can_attach_permissions(): void
    {
        $role = Role::create([
            'slug' => 'test_role',
            'name_fr' => 'Test',
            'name_en' => 'Test',
            'environment' => 'admin',
            'display_order' => 1,
        ]);
        $p1 = Permission::create(['slug' => 'users.view', 'scope' => 'users', 'name_fr' => 'Voir']);
        $p2 = Permission::create(['slug' => 'users.create', 'scope' => 'users', 'name_fr' => 'Créer']);

        $role->permissions()->attach([$p1->id, $p2->id]);

        $this->assertCount(2, $role->fresh()->permissions);
        $this->assertTrue($role->permissions->contains('slug', 'users.view'));
    }

    public function test_is_system_defaults_to_false(): void
    {
        $role = Role::create([
            'slug' => 'simple_role',
            'name_fr' => 'Simple',
            'name_en' => 'Simple',
            'environment' => 'pro',
        ]);
        $this->assertFalse($role->is_system);
    }
}
