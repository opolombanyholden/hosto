<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use App\Modules\Core\Services\UserAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserAdminServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserAdminService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new UserAdminService();
    }

    public function test_create_user_with_explicit_password(): void
    {
        $u = $this->svc->createUser([
            'name' => 'Jean', 'email' => 'j@h.com', 'password' => 'SecretPass123!',
        ]);
        $this->assertNotNull($u->id);
        $this->assertTrue(Hash::check('SecretPass123!', $u->password));
        $this->assertFalse((bool) $u->must_change_password);
    }

    public function test_create_user_with_auto_password_marks_must_change(): void
    {
        $u = $this->svc->createUser(['name' => 'X', 'email' => 'x@h.com']);
        $this->assertTrue((bool) $u->must_change_password);
    }

    public function test_suspend_sets_locked_until_far_future(): void
    {
        $u = User::factory()->create();
        $this->svc->suspend($u, 'Compte frauduleux');
        $this->assertNotNull($u->fresh()->locked_until);
        $this->assertTrue($u->fresh()->locked_until->isAfter(now()->addYears(100)));
    }

    public function test_reactivate_clears_locked_until(): void
    {
        $u = User::factory()->create(['locked_until' => '9999-12-31']);
        $this->svc->reactivate($u);
        $this->assertNull($u->fresh()->locked_until);
    }

    public function test_reset_password_returns_plain_text_and_sets_must_change(): void
    {
        $u = User::factory()->create();
        $plain = $this->svc->resetPassword($u);
        $this->assertIsString($plain);
        $this->assertSame(16, strlen($plain));
        $this->assertTrue((bool) $u->fresh()->must_change_password);
        $this->assertTrue(Hash::check($plain, $u->fresh()->password));
    }

    public function test_soft_delete_user(): void
    {
        $u = User::factory()->create();
        $this->svc->softDelete($u);
        $this->assertSoftDeleted($u);
    }

    public function test_restore_user(): void
    {
        $u = User::factory()->create();
        $this->svc->softDelete($u);
        $this->svc->restore($u);
        $this->assertNull($u->fresh()->deleted_at);
    }

    public function test_cannot_delete_last_super_admin(): void
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'super_admin', 'name_fr' => 'SA', 'name_en' => 'SA', 'environment' => 'admin', 'is_system' => true]);
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);

        $this->expectException(\DomainException::class);
        $this->svc->softDelete($u);
    }

    public function test_validate_pro_approve(): void
    {
        $u = User::factory()->create();
        $this->svc->validatePro($u, true, null);
        $this->assertNotNull($u->fresh()->pro_validated_at);
        $this->assertSame('validated', $u->fresh()->pro_validation_status);
    }

    public function test_validate_pro_reject_requires_reason(): void
    {
        $u = User::factory()->create();
        $this->expectException(\InvalidArgumentException::class);
        $this->svc->validatePro($u, false, null);
    }

    public function test_validate_pro_reject_with_reason(): void
    {
        $u = User::factory()->create();
        $this->svc->validatePro($u, false, 'Documents invalides');
        $this->assertSame('rejected', $u->fresh()->pro_validation_status);
        $this->assertSame('Documents invalides', $u->fresh()->pro_rejection_reason);
    }

    public function test_bulk_action_returns_success_count(): void
    {
        $users = collect([
            User::factory()->create(),
            User::factory()->create(),
            User::factory()->create(),
        ]);
        $count = $this->svc->bulkAction($users, 'suspend', ['reason' => 'Test bulk']);
        $this->assertSame(3, $count);
        foreach ($users as $u) {
            $this->assertNotNull($u->fresh()->locked_until);
        }
    }

    public function test_bulk_action_refuses_above_100(): void
    {
        $users = User::factory()->count(101)->create();
        $this->expectException(\DomainException::class);
        $this->svc->bulkAction($users, 'suspend', ['reason' => 'Too many']);
    }
}
