<?php
declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Modules\Annuaire\Database\Seeders\PractitionerCategoriesSeeder;
use App\Modules\Annuaire\Models\PractitionerCategory;
use App\Modules\Core\Database\Seeders\AdditionalRolesSeeder;
use App\Modules\Core\Database\Seeders\PermissionsSeeder;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_seeder_creates_42_permissions(): void
    {
        $this->seed(PermissionsSeeder::class);
        $this->assertSame(42, Permission::count());
        $this->assertNotNull(Permission::where('slug', 'users.create')->first());
        $this->assertNotNull(Permission::where('slug', 'self.medical_record')->first());
        $this->assertNotNull(Permission::where('slug', 'self.carnet_vaccination')->first());
    }

    public function test_additional_roles_seeder_adds_compta_and_stat_and_relabels(): void
    {
        Role::create(['slug' => 'structure_owner', 'name_fr' => 'Owner', 'name_en' => 'Owner', 'environment' => 'pro']);
        Role::create(['slug' => 'admin_staff', 'name_fr' => 'Staff', 'name_en' => 'Staff', 'environment' => 'pro']);
        Role::create(['slug' => 'ministry', 'name_fr' => 'Ministère', 'name_en' => 'Ministry', 'environment' => 'admin']);
        Role::create(['slug' => 'super_admin', 'name_fr' => 'Super', 'name_en' => 'Super', 'environment' => 'admin']);

        $this->seed(AdditionalRolesSeeder::class);

        $this->assertNotNull(Role::where('slug', 'compta')->first());
        $this->assertNotNull(Role::where('slug', 'stat')->first());
        $this->assertSame('Gestionnaire de compte', Role::where('slug', 'structure_owner')->value('name_fr'));
        $this->assertSame('Administratif', Role::where('slug', 'admin_staff')->value('name_fr'));
        $this->assertSame('Gouvernement / Ministère santé', Role::where('slug', 'ministry')->value('name_fr'));
        $this->assertTrue((bool) Role::where('slug', 'super_admin')->value('is_system'));
    }

    public function test_practitioner_categories_seeder_creates_15_categories(): void
    {
        $this->seed(PractitionerCategoriesSeeder::class);
        $this->assertSame(15, PractitionerCategory::count());
        $this->assertNotNull(PractitionerCategory::where('code', 'doctor')->first());
        $this->assertNotNull(PractitionerCategory::where('code', 'traditional_healer')->first());
    }
}
