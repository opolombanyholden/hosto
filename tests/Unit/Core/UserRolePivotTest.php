<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserRolePivotTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_pivot_supports_global_assignment(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['slug' => 'r1', 'name_fr' => 'R1', 'name_en' => 'R1', 'environment' => 'admin']);

        UserRoleAssignment::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => null,
            'scope_id' => null,
        ]);

        $row = UserRoleAssignment::where('user_id', $user->id)->first();
        $this->assertNull($row->scope_type);
        $this->assertNull($row->scope_id);
    }

    public function test_user_role_pivot_supports_scoped_assignment(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['slug' => 'doctor_test', 'name_fr' => 'Doc', 'name_en' => 'Doc', 'environment' => 'pro']);
        $hosto = Hosto::factory()->create();

        UserRoleAssignment::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => Hosto::class,
            'scope_id' => $hosto->id,
        ]);

        $row = UserRoleAssignment::where('user_id', $user->id)->first();
        $this->assertSame(Hosto::class, $row->scope_type);
        $this->assertSame($hosto->id, $row->scope_id);
    }

    public function test_user_role_pivot_unique_per_scope(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['slug' => 'r2', 'name_fr' => 'R2', 'name_en' => 'R2', 'environment' => 'admin']);
        UserRoleAssignment::create([
            'user_id' => $user->id, 'role_id' => $role->id,
            'scope_type' => null, 'scope_id' => null,
        ]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        UserRoleAssignment::create([
            'user_id' => $user->id, 'role_id' => $role->id,
            'scope_type' => null, 'scope_id' => null,
        ]);
    }

    public function test_user_role_pivot_supports_expires_at(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['slug' => 'temp', 'name_fr' => 'Temp', 'name_en' => 'Temp', 'environment' => 'pro']);
        $expiry = now()->addDays(7);
        UserRoleAssignment::create([
            'user_id' => $user->id, 'role_id' => $role->id,
            'expires_at' => $expiry,
        ]);
        $row = UserRoleAssignment::where('user_id', $user->id)->first();
        $this->assertNotNull($row->expires_at);
    }
}
