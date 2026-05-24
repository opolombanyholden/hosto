<?php
declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Modules\Core\Database\Seeders\AdditionalRolesSeeder;
use App\Modules\Core\Database\Seeders\PermissionsSeeder;
use App\Modules\Core\Database\Seeders\RolePermissionsSeeder;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RolePermissionsMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['slug' => 'super_admin', 'name_fr' => 'Super admin', 'name_en' => 'Super admin', 'environment' => 'admin', 'is_system' => true]);
        Role::create(['slug' => 'moderator', 'name_fr' => 'Modérateur', 'name_en' => 'Moderator', 'environment' => 'admin']);
        Role::create(['slug' => 'ministry', 'name_fr' => 'Ministère', 'name_en' => 'Ministry', 'environment' => 'admin']);
        Role::create(['slug' => 'structure_owner', 'name_fr' => 'Owner', 'name_en' => 'Owner', 'environment' => 'pro']);
        Role::create(['slug' => 'admin_staff', 'name_fr' => 'Staff', 'name_en' => 'Staff', 'environment' => 'pro']);
        Role::create(['slug' => 'doctor', 'name_fr' => 'Médecin', 'name_en' => 'Doctor', 'environment' => 'pro']);
        Role::create(['slug' => 'nurse', 'name_fr' => 'Infirmier', 'name_en' => 'Nurse', 'environment' => 'pro']);
        Role::create(['slug' => 'pharmacist', 'name_fr' => 'Pharmacien', 'name_en' => 'Pharmacist', 'environment' => 'pro']);
        Role::create(['slug' => 'lab_tech', 'name_fr' => 'Lab', 'name_en' => 'Lab', 'environment' => 'pro']);
        Role::create(['slug' => 'patient', 'name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);
        $this->seed(PermissionsSeeder::class);
        $this->seed(AdditionalRolesSeeder::class);
    }

    public function test_super_admin_has_no_permissions_attached_in_db(): void
    {
        $this->seed(RolePermissionsSeeder::class);
        $count = Role::where('slug', 'super_admin')->first()->permissions->count();
        $this->assertSame(0, $count);
    }

    public function test_moderator_has_expected_permissions(): void
    {
        $this->seed(RolePermissionsSeeder::class);
        $slugs = Role::where('slug', 'moderator')->first()->permissions->pluck('slug')->all();
        $this->assertContains('users.view', $slugs);
        $this->assertContains('users.suspend', $slugs);
        $this->assertContains('claims.review', $slugs);
        $this->assertNotContains('users.delete', $slugs);
    }

    public function test_patient_has_self_permissions_only(): void
    {
        $this->seed(RolePermissionsSeeder::class);
        $slugs = Role::where('slug', 'patient')->first()->permissions->pluck('slug')->all();
        $this->assertContains('self.profile', $slugs);
        $this->assertContains('self.carnet_vaccination', $slugs);
        $this->assertContains('appointments.book.self', $slugs);
        $this->assertNotContains('users.view', $slugs);
    }

    public function test_compta_has_financial_permissions(): void
    {
        $this->seed(RolePermissionsSeeder::class);
        $slugs = Role::where('slug', 'compta')->first()->permissions->pluck('slug')->all();
        $this->assertContains('payments.view', $slugs);
        $this->assertContains('invoices.manage', $slugs);
        $this->assertContains('exports.financial', $slugs);
    }
}
