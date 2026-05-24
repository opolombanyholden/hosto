<?php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Annuaire\Models\PractitionerCategory;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PractitionerCategoriesAdminTest extends TestCase
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

    public function test_list_categories(): void
    {
        $admin = $this->adminWith(['pro_categories.view']);
        PractitionerCategory::create(['code' => 'doctor', 'name_fr' => 'Médecin', 'display_order' => 1]);
        $resp = $this->actingAs($admin)->get('/admin/practitioner-categories');
        $resp->assertOk();
        $resp->assertSeeText('Médecin');
    }

    public function test_create_category(): void
    {
        $admin = $this->adminWith(['pro_categories.manage']);
        $resp = $this->actingAs($admin)->post('/admin/practitioner-categories', [
            'code' => 'cardio', 'name_fr' => 'Cardiologue',
            'color_hex' => '#C62828', 'is_medical' => '1',
        ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('practitioner_categories', ['code' => 'cardio']);
    }

    public function test_delete_refuses_when_practitioners_attached(): void
    {
        $admin = $this->adminWith(['pro_categories.manage']);
        $cat = PractitionerCategory::create(['code' => 'doc', 'name_fr' => 'Doc', 'display_order' => 1]);
        \App\Modules\Annuaire\Models\Practitioner::factory()->create(['practitioner_category_id' => $cat->id]);

        $resp = $this->actingAs($admin)->delete('/admin/practitioner-categories/'.$cat->uuid);
        $resp->assertRedirect();
        $resp->assertSessionHasErrors('delete');
        $this->assertNotNull(PractitionerCategory::find($cat->id));
    }
}
