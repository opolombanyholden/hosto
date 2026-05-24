<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Modules\Core\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PermissionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_can_be_persisted_and_retrieved(): void
    {
        $p = Permission::create([
            'slug' => 'users.create',
            'scope' => 'users',
            'name_fr' => 'Créer un utilisateur',
            'description_fr' => 'Permet de créer un nouveau compte',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $this->assertNotNull($p->uuid);
        $this->assertSame('users.create', $p->slug);
        $this->assertSame('users', $p->scope);
        $this->assertTrue($p->is_active);
    }

    public function test_slug_is_unique(): void
    {
        Permission::create(['slug' => 'users.view', 'scope' => 'users', 'name_fr' => 'Voir users']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        Permission::create(['slug' => 'users.view', 'scope' => 'users', 'name_fr' => 'Doublon']);
    }
}
