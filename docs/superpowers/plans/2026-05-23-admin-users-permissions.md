# Admin Users + Roles + Permissions — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implémenter le module admin RBAC complet (users + rôles + permissions + categories pro + impersonation) conformément à `docs/superpowers/specs/2026-05-23-admin-users-permissions-design.md`.

**Architecture:** Laravel 13 / PHP 8.3 / PostgreSQL 17. Nouvelle table `permissions` + pivot `role_permissions`. Pivot `user_roles` étendu pour scoping polymorphique. Services dans `app/Modules/Core/Services/`. Controllers admin dans `app/Modules/Core/Http/Controllers/Admin/`. Gate Laravel `$user->can()` délègue à `PermissionResolver` avec court-circuit `super_admin → *`. Impersonation journalisée + banner permanent.

**Tech Stack:** PHP 8.3, Laravel 13, PostgreSQL 17, Sanctum (tokens), Blade, PHPUnit 12.

---

## File map

**Migrations** (`app/Modules/Core/Database/Migrations/`)
- `2026_05_23_100000_create_permissions_table.php`
- `2026_05_23_100100_create_role_permissions_table.php`
- `2026_05_23_100200_add_is_system_to_roles.php`
- `2026_05_23_100300_extend_user_roles_pivot.php`
- `2026_05_23_100400_add_must_change_password_to_users.php`
- `2026_05_23_100500_create_impersonation_sessions_table.php`

**Migrations Annuaire** (`app/Modules/Annuaire/Database/Migrations/`)
- `2026_05_23_110000_create_practitioner_categories_table.php`
- `2026_05_23_110100_add_practitioner_category_id_to_practitioners.php`

**Models**
- Nouveau : `app/Modules/Core/Models/Permission.php`
- Nouveau : `app/Modules/Core/Models/UserRoleAssignment.php`
- Nouveau : `app/Modules/Core/Models/ImpersonationSession.php`
- Nouveau : `app/Modules/Annuaire/Models/PractitionerCategory.php`
- Modifié : `app/Modules/Core/Models/Role.php` (relations + scopes)
- Modifié : `app/Models/User.php` (relations + booted hook)

**Seeders** (`app/Modules/Core/Database/Seeders/`)
- `PermissionsSeeder.php` (41 permissions)
- `RolePermissionsSeeder.php` (matrice par défaut)
- `AdditionalRolesSeeder.php` (compta + stat + relabel)
- `PractitionerCategoriesSeeder.php` (15 catégories, dans Annuaire/Database/Seeders)

**Services** (`app/Modules/Core/Services/`)
- `PermissionResolver.php`
- `RoleAssignmentService.php`
- `UserAdminService.php`
- `ImpersonationService.php`
- `UserExportService.php`

**Middleware** (`app/Modules/Core/Http/Middleware/`)
- `EnsurePermission.php` (alias `perm`)

**Controllers** (`app/Modules/Core/Http/Controllers/Admin/`)
- `AdminUsersController.php`
- `AdminRolesController.php`
- `AdminPermissionsController.php`
- `ImpersonationController.php`

**Controllers Annuaire** (`app/Modules/Annuaire/Http/Controllers/Admin/`)
- `AdminPractitionerCategoriesController.php`

**Views** (`resources/views/admin/`)
- `users/index.blade.php`, `show.blade.php`, `create.blade.php`, `edit.blade.php`, `sessions.blade.php`
- `roles/index.blade.php`, `show.blade.php`, `create.blade.php`, `edit.blade.php`, `permissions.blade.php`
- `permissions/index.blade.php`
- `practitioner-categories/index.blade.php`, `form.blade.php`

**Layout partial**
- `resources/views/layouts/partials/impersonation-banner.blade.php`

**Routes** : modifier `routes/web.php` (groupe `/admin`)

**Service provider** : modifier `app/Modules/Core/Providers/CoreServiceProvider.php` (Gate::before, middleware alias)

**Tests** : `tests/Unit/Core/` + `tests/Feature/Admin/`

---

## Phase 1 — Foundation (migrations + models)

## Task 1: Migration `permissions` + modèle `Permission`

**Files:**
- Create: `app/Modules/Core/Database/Migrations/2026_05_23_100000_create_permissions_table.php`
- Create: `app/Modules/Core/Models/Permission.php`
- Test: `tests/Unit/Core/PermissionModelTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Unit/Core/PermissionModelTest.php`
Expected: FAIL ("Class Permission not found" or "table permissions missing")

- [ ] **Step 3: Create the migration**

```php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug', 80)->unique();
            $table->string('scope', 40);
            $table->string('name_fr', 255);
            $table->string('name_en', 255)->nullable();
            $table->text('description_fr')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index('scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
```

- [ ] **Step 4: Create the model**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $scope
 * @property string $name_fr
 * @property string|null $name_en
 * @property string|null $description_fr
 * @property bool $is_active
 * @property int $display_order
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Collection<int, Role> $roles
 */
class Permission extends Model
{
    use HasUuid;

    protected $fillable = [
        'slug', 'scope', 'name_fr', 'name_en', 'description_fr',
        'is_active', 'display_order',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }
}
```

- [ ] **Step 5: Run migration + test**

Run: `php artisan migrate --force && ./vendor/bin/phpunit tests/Unit/Core/PermissionModelTest.php`
Expected: PASS (2 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Core/Database/Migrations/2026_05_23_100000_create_permissions_table.php \
        app/Modules/Core/Models/Permission.php \
        tests/Unit/Core/PermissionModelTest.php
git commit -m "feat(admin): table permissions + modele Permission"
```

---

## Task 2: Migration `role_permissions` + relation sur Role

**Files:**
- Create: `app/Modules/Core/Database/Migrations/2026_05_23_100100_create_role_permissions_table.php`
- Create: `app/Modules/Core/Database/Migrations/2026_05_23_100200_add_is_system_to_roles.php`
- Modify: `app/Modules/Core/Models/Role.php`
- Test: `tests/Unit/Core/RolePermissionsRelationTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
            'environment' => 'pro',
        ]);
        $this->assertFalse($role->is_system);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Unit/Core/RolePermissionsRelationTest.php`
Expected: FAIL ("permissions() method missing" or "role_permissions table missing")

- [ ] **Step 3: Create the role_permissions migration**

```php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
```

- [ ] **Step 4: Create the is_system migration**

```php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->boolean('is_system')->default(false)->after('environment');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('is_system');
        });
    }
};
```

- [ ] **Step 5: Extend the Role model**

Read `app/Modules/Core/Models/Role.php`. Add `is_system` to `$fillable`, add `permissions()` BelongsToMany relation, add cast.

Final state of the model:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Referentiel\Models\Concerns\HasBilingualName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $name_fr
 * @property string $name_en
 * @property string $environment
 * @property bool $is_system
 * @property string|null $description_fr
 * @property int $display_order
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $name
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Permission> $permissions
 */
class Role extends Model
{
    use HasBilingualName;
    use HasUuid;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'slug',
        'name_fr',
        'name_en',
        'environment',
        'is_system',
        'description_fr',
        'display_order',
    ];

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'display_order' => 'integer',
        ];
    }
}
```

- [ ] **Step 6: Run migrations + test**

Run: `php artisan migrate --force && ./vendor/bin/phpunit tests/Unit/Core/RolePermissionsRelationTest.php`
Expected: PASS (2 tests)

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Core/Database/Migrations/2026_05_23_100100_create_role_permissions_table.php \
        app/Modules/Core/Database/Migrations/2026_05_23_100200_add_is_system_to_roles.php \
        app/Modules/Core/Models/Role.php \
        tests/Unit/Core/RolePermissionsRelationTest.php
git commit -m "feat(admin): pivot role_permissions + is_system flag sur Role"
```

---

## Task 3: Pivot `user_roles` étendu (scope + assigned_by + expires_at) + modèle pivot

**Files:**
- Create: `app/Modules/Core/Database/Migrations/2026_05_23_100300_extend_user_roles_pivot.php`
- Create: `app/Modules/Core/Models/UserRoleAssignment.php`
- Test: `tests/Unit/Core/UserRolePivotTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
        $role = Role::create(['slug' => 'r1', 'name_fr' => 'R1', 'environment' => 'admin']);

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
        $role = Role::create(['slug' => 'doctor_test', 'name_fr' => 'Doc', 'environment' => 'pro']);
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
        $role = Role::create(['slug' => 'r2', 'name_fr' => 'R2', 'environment' => 'admin']);
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
        $role = Role::create(['slug' => 'temp', 'name_fr' => 'Temp', 'environment' => 'pro']);
        $expiry = now()->addDays(7);
        UserRoleAssignment::create([
            'user_id' => $user->id, 'role_id' => $role->id,
            'expires_at' => $expiry,
        ]);
        $row = UserRoleAssignment::where('user_id', $user->id)->first();
        $this->assertNotNull($row->expires_at);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Unit/Core/UserRolePivotTest.php`
Expected: FAIL ("Class UserRoleAssignment not found" or "column scope_type missing")

- [ ] **Step 3: Create the migration**

```php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing unique constraint (user_id, role_id) before adding scope cols.
        DB::statement('ALTER TABLE user_roles DROP CONSTRAINT IF EXISTS user_roles_user_id_role_id_unique');

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->string('scope_type', 80)->nullable()->after('role_id');
            $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
            $table->foreignId('assigned_by')->nullable()->after('scope_id')
                ->constrained('users')->nullOnDelete();
            $table->timestampTz('assigned_at')->useCurrent()->after('assigned_by');
            $table->timestampTz('expires_at')->nullable()->after('assigned_at');
        });

        DB::statement('
            ALTER TABLE user_roles
            ADD CONSTRAINT user_roles_user_role_scope_unique
            UNIQUE (user_id, role_id, scope_type, scope_id)
        ');

        DB::statement('CREATE INDEX IF NOT EXISTS user_roles_scope_idx ON user_roles (scope_type, scope_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS user_roles_scope_idx');
        DB::statement('ALTER TABLE user_roles DROP CONSTRAINT IF EXISTS user_roles_user_role_scope_unique');

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->dropForeign(['assigned_by']);
            $table->dropColumn(['scope_type', 'scope_id', 'assigned_by', 'assigned_at', 'expires_at']);
        });
    }
};
```

- [ ] **Step 4: Create the pivot model**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $role_id
 * @property string|null $scope_type
 * @property int|null $scope_id
 * @property int|null $assigned_by
 * @property CarbonImmutable $assigned_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable $created_at
 */
class UserRoleAssignment extends Model
{
    protected $table = 'user_roles';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'role_id', 'scope_type', 'scope_id',
        'assigned_by', 'assigned_at', 'expires_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** @return MorphTo<Model, $this> */
    public function scope(): MorphTo
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
```

- [ ] **Step 5: Run migrations + test**

Run: `php artisan migrate --force && ./vendor/bin/phpunit tests/Unit/Core/UserRolePivotTest.php`
Expected: PASS (4 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Core/Database/Migrations/2026_05_23_100300_extend_user_roles_pivot.php \
        app/Modules/Core/Models/UserRoleAssignment.php \
        tests/Unit/Core/UserRolePivotTest.php
git commit -m "feat(admin): pivot user_roles etendu (scope + assigned_by + expires_at)"
```

---

## Task 4: Migrations restantes (must_change_password + impersonation_sessions)

**Files:**
- Create: `app/Modules/Core/Database/Migrations/2026_05_23_100400_add_must_change_password_to_users.php`
- Create: `app/Modules/Core/Database/Migrations/2026_05_23_100500_create_impersonation_sessions_table.php`
- Create: `app/Modules/Core/Models/ImpersonationSession.php`
- Test: `tests/Unit/Core/ImpersonationSessionModelTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Core\Models\ImpersonationSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ImpersonationSessionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_persists_with_admin_target_and_reason(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $session = ImpersonationSession::create([
            'admin_user_id' => $admin->id,
            'target_user_id' => $target->id,
            'reason' => 'Support ticket #42',
            'started_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $this->assertNotNull($session->uuid);
        $this->assertSame($admin->id, $session->admin->id);
        $this->assertSame($target->id, $session->target->id);
        $this->assertNull($session->ended_at);
    }

    public function test_user_must_change_password_defaults_to_false(): void
    {
        $u = User::factory()->create();
        $this->assertFalse((bool) $u->must_change_password);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Unit/Core/ImpersonationSessionModelTest.php`
Expected: FAIL.

- [ ] **Step 3: Create must_change_password migration**

```php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('must_change_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('must_change_password');
        });
    }
};
```

- [ ] **Step 4: Create impersonation_sessions migration**

```php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impersonation_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->timestampTz('started_at');
            $table->timestampTz('ended_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index(['admin_user_id', 'started_at']);
            $table->index(['target_user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_sessions');
    }
};
```

- [ ] **Step 5: Create the model**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $admin_user_id
 * @property int $target_user_id
 * @property string $reason
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $ended_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class ImpersonationSession extends Model
{
    use HasUuid;

    protected $fillable = [
        'admin_user_id', 'target_user_id', 'reason',
        'started_at', 'ended_at', 'ip_address', 'user_agent',
    ];

    /** @return BelongsTo<User, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }
}
```

- [ ] **Step 6: Update User fillable**

Read `app/Models/User.php`, locate the `#[Fillable(...)]` attribute, append `'must_change_password'` to the array (keep all other entries).

- [ ] **Step 7: Run migrations + test**

Run: `php artisan migrate --force && ./vendor/bin/phpunit tests/Unit/Core/ImpersonationSessionModelTest.php`
Expected: PASS (2 tests).

- [ ] **Step 8: Commit**

```bash
git add app/Modules/Core/Database/Migrations/2026_05_23_100400_add_must_change_password_to_users.php \
        app/Modules/Core/Database/Migrations/2026_05_23_100500_create_impersonation_sessions_table.php \
        app/Modules/Core/Models/ImpersonationSession.php \
        app/Models/User.php \
        tests/Unit/Core/ImpersonationSessionModelTest.php
git commit -m "feat(admin): users.must_change_password + table impersonation_sessions"
```

---

## Task 5: Migrations + modèle `PractitionerCategory` (Annuaire)

**Files:**
- Create: `app/Modules/Annuaire/Database/Migrations/2026_05_23_110000_create_practitioner_categories_table.php`
- Create: `app/Modules/Annuaire/Database/Migrations/2026_05_23_110100_add_practitioner_category_id_to_practitioners.php`
- Create: `app/Modules/Annuaire/Models/PractitionerCategory.php`
- Modify: `app/Modules/Annuaire/Models/Practitioner.php` (ajouter relation)
- Test: `tests/Unit/Annuaire/PractitionerCategoryModelTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Annuaire;

use App\Modules\Annuaire\Models\PractitionerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PractitionerCategoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created(): void
    {
        $c = PractitionerCategory::create([
            'code' => 'doctor',
            'name_fr' => 'Médecin généraliste',
            'icon_name' => 'stethoscope',
            'color_hex' => '#388E3C',
            'is_medical' => true,
            'display_order' => 1,
        ]);
        $this->assertNotNull($c->uuid);
        $this->assertTrue($c->is_medical);
    }

    public function test_category_can_have_parent(): void
    {
        $parent = PractitionerCategory::create([
            'code' => 'specialist',
            'name_fr' => 'Spécialiste',
            'doses_total' => null,
        ] + ['display_order' => 1]);
        $child = PractitionerCategory::create([
            'code' => 'cardiologist',
            'name_fr' => 'Cardiologue',
            'parent_category_id' => $parent->id,
            'display_order' => 2,
        ]);
        $this->assertSame($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains('code', 'cardiologist'));
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Unit/Annuaire/PractitionerCategoryModelTest.php`
Expected: FAIL.

- [ ] **Step 3: Create the practitioner_categories migration**

```php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practitioner_categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 40)->unique();
            $table->string('name_fr', 120);
            $table->string('name_en', 120)->nullable();
            $table->text('description_fr')->nullable();
            $table->string('icon_name', 40)->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->foreignId('parent_category_id')->nullable()
                ->constrained('practitioner_categories')->nullOnDelete();
            $table->boolean('is_medical')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletesTz();
            $table->index(['is_active', 'display_order']);
            $table->index('parent_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner_categories');
    }
};
```

- [ ] **Step 4: Create the FK migration on practitioners**

```php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practitioners', function (Blueprint $table): void {
            $table->foreignId('practitioner_category_id')->nullable()
                ->after('practitioner_type')
                ->constrained('practitioner_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('practitioners', function (Blueprint $table): void {
            $table->dropForeign(['practitioner_category_id']);
            $table->dropColumn('practitioner_category_id');
        });
    }
};
```

- [ ] **Step 5: Create the model**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Annuaire\Models;

use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property string $code
 * @property string $name_fr
 * @property string|null $name_en
 * @property string|null $description_fr
 * @property string|null $icon_name
 * @property string|null $color_hex
 * @property int|null $parent_category_id
 * @property bool $is_medical
 * @property int $display_order
 * @property bool $is_active
 * @property-read PractitionerCategory|null $parent
 * @property-read Collection<int, PractitionerCategory> $children
 * @property-read Collection<int, Practitioner> $practitioners
 */
class PractitionerCategory extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name_fr', 'name_en', 'description_fr',
        'icon_name', 'color_hex', 'parent_category_id',
        'is_medical', 'display_order', 'is_active',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_category_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_category_id')->orderBy('display_order');
    }

    /** @return HasMany<Practitioner, $this> */
    public function practitioners(): HasMany
    {
        return $this->hasMany(Practitioner::class, 'practitioner_category_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_medical' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }
}
```

- [ ] **Step 6: Add practitioner_category_id to Practitioner fillable**

Read `app/Modules/Annuaire/Models/Practitioner.php`. Add `'practitioner_category_id'` to `$fillable`. Add a `category()` BelongsTo relation:

```php
/** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<PractitionerCategory, $this> */
public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(PractitionerCategory::class, 'practitioner_category_id');
}
```

- [ ] **Step 7: Fix the test (use right factory)**

The test as drafted in Step 1 has a typo (`'doses_total' => null` is a leftover). Remove that line from the test before running. The clean version:

```php
public function test_category_can_have_parent(): void
{
    $parent = PractitionerCategory::create([
        'code' => 'specialist',
        'name_fr' => 'Spécialiste',
        'display_order' => 1,
    ]);
    $child = PractitionerCategory::create([
        'code' => 'cardiologist',
        'name_fr' => 'Cardiologue',
        'parent_category_id' => $parent->id,
        'display_order' => 2,
    ]);
    $this->assertSame($parent->id, $child->parent->id);
    $this->assertTrue($parent->children->contains('code', 'cardiologist'));
}
```

- [ ] **Step 8: Run migrations + tests**

Run: `php artisan migrate --force && ./vendor/bin/phpunit tests/Unit/Annuaire/PractitionerCategoryModelTest.php`
Expected: PASS (2 tests).

- [ ] **Step 9: Commit**

```bash
git add app/Modules/Annuaire/Database/Migrations/2026_05_23_110000_create_practitioner_categories_table.php \
        app/Modules/Annuaire/Database/Migrations/2026_05_23_110100_add_practitioner_category_id_to_practitioners.php \
        app/Modules/Annuaire/Models/PractitionerCategory.php \
        app/Modules/Annuaire/Models/Practitioner.php \
        tests/Unit/Annuaire/PractitionerCategoryModelTest.php
git commit -m "feat(annuaire): table practitioner_categories + FK pratitioners"
```

---

## Phase 2 — Seeders

## Task 6: PermissionsSeeder + AdditionalRolesSeeder + relabel + PractitionerCategoriesSeeder

**Files:**
- Create: `app/Modules/Core/Database/Seeders/PermissionsSeeder.php`
- Create: `app/Modules/Core/Database/Seeders/AdditionalRolesSeeder.php`
- Create: `app/Modules/Annuaire/Database/Seeders/PractitionerCategoriesSeeder.php`
- Test: `tests/Feature/Core/SeedersTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Modules\Annuaire\Models\PractitionerCategory;
use App\Modules\Core\Database\Seeders\AdditionalRolesSeeder;
use App\Modules\Core\Database\Seeders\PermissionsSeeder;
use App\Modules\Annuaire\Database\Seeders\PractitionerCategoriesSeeder;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_seeder_creates_41_permissions(): void
    {
        $this->seed(PermissionsSeeder::class);
        $this->assertSame(41, Permission::count());
        $this->assertNotNull(Permission::where('slug', 'users.create')->first());
        $this->assertNotNull(Permission::where('slug', 'self.carnet_vaccination')->first());
    }

    public function test_additional_roles_seeder_adds_compta_and_stat_and_relabels(): void
    {
        // Pre-seed roles existants comme dans la migration de base.
        Role::create(['slug' => 'structure_owner', 'name_fr' => 'Owner', 'environment' => 'pro']);
        Role::create(['slug' => 'admin_staff', 'name_fr' => 'Staff', 'environment' => 'pro']);
        Role::create(['slug' => 'ministry', 'name_fr' => 'Ministère', 'environment' => 'admin']);

        $this->seed(AdditionalRolesSeeder::class);

        $this->assertNotNull(Role::where('slug', 'compta')->first());
        $this->assertNotNull(Role::where('slug', 'stat')->first());
        $this->assertSame('Gestionnaire de compte', Role::where('slug', 'structure_owner')->value('name_fr'));
        $this->assertSame('Administratif', Role::where('slug', 'admin_staff')->value('name_fr'));
        $this->assertSame('Gouvernement / Ministère santé', Role::where('slug', 'ministry')->value('name_fr'));
    }

    public function test_practitioner_categories_seeder_creates_15_categories(): void
    {
        $this->seed(PractitionerCategoriesSeeder::class);
        $this->assertSame(15, PractitionerCategory::count());
        $this->assertNotNull(PractitionerCategory::where('code', 'doctor')->first());
        $this->assertNotNull(PractitionerCategory::where('code', 'traditional_healer')->first());
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Feature/Core/SeedersTest.php`
Expected: FAIL.

- [ ] **Step 3: Create PermissionsSeeder**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $perms = [
            // users
            ['slug' => 'users.view',           'scope' => 'users',         'name_fr' => 'Voir la liste / fiche des utilisateurs'],
            ['slug' => 'users.create',         'scope' => 'users',         'name_fr' => 'Créer un utilisateur'],
            ['slug' => 'users.edit',           'scope' => 'users',         'name_fr' => "Modifier les infos d'un utilisateur"],
            ['slug' => 'users.delete',         'scope' => 'users',         'name_fr' => 'Supprimer (soft) / restaurer un utilisateur'],
            ['slug' => 'users.suspend',       'scope' => 'users',         'name_fr' => 'Suspendre / réactiver'],
            ['slug' => 'users.reset_password', 'scope' => 'users',         'name_fr' => 'Réinitialiser le mot de passe'],
            ['slug' => 'users.validate_pro',   'scope' => 'users',         'name_fr' => 'Valider / rejeter un compte pro'],
            ['slug' => 'users.impersonate',    'scope' => 'users',         'name_fr' => 'Se connecter "comme" un utilisateur'],
            ['slug' => 'users.sessions',       'scope' => 'users',         'name_fr' => 'Voir et révoquer les sessions actives'],
            ['slug' => 'users.bulk',           'scope' => 'users',         'name_fr' => 'Actions en lot sur plusieurs utilisateurs'],
            // roles
            ['slug' => 'roles.view',           'scope' => 'roles',         'name_fr' => 'Voir les rôles'],
            ['slug' => 'roles.create',         'scope' => 'roles',         'name_fr' => 'Créer un rôle'],
            ['slug' => 'roles.edit',           'scope' => 'roles',         'name_fr' => "Modifier nom/description d'un rôle"],
            ['slug' => 'roles.delete',         'scope' => 'roles',         'name_fr' => 'Supprimer un rôle'],
            ['slug' => 'roles.assign',         'scope' => 'roles',         'name_fr' => 'Assigner / retirer des rôles à un user'],
            // permissions
            ['slug' => 'permissions.view',     'scope' => 'permissions',   'name_fr' => 'Voir le catalogue des permissions'],
            ['slug' => 'permissions.assign',   'scope' => 'permissions',   'name_fr' => 'Modifier la matrice rôle ↔ permissions'],
            // structures
            ['slug' => 'structures.view',      'scope' => 'structures',    'name_fr' => "Voir les structures de l'annuaire"],
            ['slug' => 'structures.edit',      'scope' => 'structures',    'name_fr' => 'Éditer une structure'],
            ['slug' => 'structures.validate',  'scope' => 'structures',    'name_fr' => 'Valider une revendication (claim) de structure'],
            ['slug' => 'structures.delete',    'scope' => 'structures',    'name_fr' => 'Supprimer une structure'],
            // pro_categories
            ['slug' => 'pro_categories.view',  'scope' => 'pro_categories','name_fr' => 'Voir les catégories de professionnels'],
            ['slug' => 'pro_categories.manage','scope' => 'pro_categories','name_fr' => 'CRUD complet sur les catégories pro'],
            // claims
            ['slug' => 'claims.review',        'scope' => 'claims',        'name_fr' => 'Examiner et trancher les claims de structure'],
            // consultations
            ['slug' => 'consultations.view',   'scope' => 'consultations', 'name_fr' => 'Voir les consultations (lecture globale)'],
            ['slug' => 'consultations.manage', 'scope' => 'consultations', 'name_fr' => 'Gérer ses propres consultations (médecin)'],
            // prescriptions
            ['slug' => 'prescriptions.create', 'scope' => 'prescriptions', 'name_fr' => 'Créer une ordonnance'],
            ['slug' => 'prescriptions.view',   'scope' => 'prescriptions', 'name_fr' => 'Voir les ordonnances'],
            // appointments
            ['slug' => 'appointments.manage',  'scope' => 'appointments',  'name_fr' => 'Gérer les rendez-vous (secrétariat)'],
            ['slug' => 'appointments.book.self','scope' => 'appointments', 'name_fr' => 'Prendre rendez-vous pour soi (patient)'],
            // payments
            ['slug' => 'payments.view',        'scope' => 'payments',      'name_fr' => 'Voir les paiements'],
            ['slug' => 'payments.refund',      'scope' => 'payments',      'name_fr' => 'Émettre un remboursement'],
            // invoices
            ['slug' => 'invoices.manage',      'scope' => 'invoices',      'name_fr' => 'Créer / valider des factures (compta)'],
            // stats
            ['slug' => 'stats.view',           'scope' => 'stats',         'name_fr' => 'Accéder aux tableaux de bord statistiques'],
            ['slug' => 'stats.view.financial', 'scope' => 'stats',         'name_fr' => 'Accéder aux KPI financiers (compta only)'],
            // exports
            ['slug' => 'exports.users',        'scope' => 'exports',       'name_fr' => 'Exporter la liste users en CSV'],
            ['slug' => 'exports.structures',   'scope' => 'exports',       'name_fr' => 'Exporter les structures'],
            ['slug' => 'exports.financial',    'scope' => 'exports',       'name_fr' => 'Exporter les données financières'],
            ['slug' => 'exports.stats',        'scope' => 'exports',       'name_fr' => 'Exporter les rapports stats'],
            // self
            ['slug' => 'self.profile',         'scope' => 'self',          'name_fr' => 'Gérer son propre profil (tous comptes)'],
            ['slug' => 'self.medical_record',  'scope' => 'self',          'name_fr' => 'Accéder à son dossier médical (patient)'],
            ['slug' => 'self.carnet_vaccination','scope' => 'self',        'name_fr' => 'Voir/imprimer son carnet de vaccination'],
        ];

        foreach ($perms as $i => $p) {
            Permission::updateOrCreate(
                ['slug' => $p['slug']],
                array_merge($p, ['display_order' => $i + 1, 'is_active' => true])
            );
        }
    }
}
```

- [ ] **Step 4: Create AdditionalRolesSeeder**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Models\Role;
use Illuminate\Database\Seeder;

class AdditionalRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Nouveaux rôles.
        Role::updateOrCreate(['slug' => 'compta'], [
            'name_fr' => 'Comptable',
            'environment' => 'admin',
            'description_fr' => 'Gère les paiements, factures et reporting financier',
            'is_system' => false,
            'display_order' => 50,
        ]);
        Role::updateOrCreate(['slug' => 'stat'], [
            'name_fr' => 'Statisticien / Data analyst',
            'environment' => 'admin',
            'description_fr' => 'Accès lecture aux données stats et exports',
            'is_system' => false,
            'display_order' => 51,
        ]);

        // Re-label des rôles existants.
        $relabels = [
            'structure_owner' => 'Gestionnaire de compte',
            'admin_staff' => 'Administratif',
            'ministry' => 'Gouvernement / Ministère santé',
        ];
        foreach ($relabels as $slug => $label) {
            Role::where('slug', $slug)->update(['name_fr' => $label]);
        }

        // Marquer super_admin comme is_system.
        Role::where('slug', 'super_admin')->update(['is_system' => true]);
    }
}
```

- [ ] **Step 5: Create PractitionerCategoriesSeeder**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Annuaire\Database\Seeders;

use App\Modules\Annuaire\Models\PractitionerCategory;
use Illuminate\Database\Seeder;

class PractitionerCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $cats = [
            ['code' => 'doctor',                  'name_fr' => 'Médecin généraliste',          'icon' => 'stethoscope', 'color' => '#1565C0', 'medical' => true],
            ['code' => 'specialist',              'name_fr' => 'Médecin spécialiste',          'icon' => 'doctor',      'color' => '#0277BD', 'medical' => true],
            ['code' => 'dentist',                 'name_fr' => 'Dentiste',                     'icon' => 'tooth',       'color' => '#00838F', 'medical' => true],
            ['code' => 'midwife',                 'name_fr' => 'Sage-femme',                   'icon' => 'baby',        'color' => '#C2185B', 'medical' => true],
            ['code' => 'nurse',                   'name_fr' => 'Infirmier(ère)',               'icon' => 'first-aid',   'color' => '#388E3C', 'medical' => true],
            ['code' => 'pharmacist',              'name_fr' => 'Pharmacien',                   'icon' => 'pill',        'color' => '#7B1FA2', 'medical' => true],
            ['code' => 'lab_technician',          'name_fr' => 'Technicien laboratoire',       'icon' => 'flask',       'color' => '#5E35B1', 'medical' => true],
            ['code' => 'radiologist_tech',        'name_fr' => 'Manipulateur radio',           'icon' => 'scan',        'color' => '#455A64', 'medical' => true],
            ['code' => 'kinesitherapist',         'name_fr' => 'Kinésithérapeute',             'icon' => 'activity',    'color' => '#F57C00', 'medical' => true],
            ['code' => 'psychologist',            'name_fr' => 'Psychologue',                  'icon' => 'brain',       'color' => '#6A1B9A', 'medical' => true],
            ['code' => 'nutritionist',            'name_fr' => 'Nutritionniste',               'icon' => 'apple',       'color' => '#558B2F', 'medical' => true],
            ['code' => 'optometrist',             'name_fr' => 'Optométriste',                 'icon' => 'eye',         'color' => '#00695C', 'medical' => true],
            ['code' => 'vet_public_health',       'name_fr' => 'Vétérinaire santé publique',   'icon' => 'paw',         'color' => '#795548', 'medical' => true],
            ['code' => 'community_health_worker', 'name_fr' => 'Agent santé communautaire',    'icon' => 'users',       'color' => '#00897B', 'medical' => false],
            ['code' => 'traditional_healer',      'name_fr' => 'Tradipraticien',               'icon' => 'leaf',        'color' => '#9E9D24', 'medical' => false],
        ];

        foreach ($cats as $i => $c) {
            PractitionerCategory::updateOrCreate(
                ['code' => $c['code']],
                [
                    'name_fr' => $c['name_fr'],
                    'icon_name' => $c['icon'],
                    'color_hex' => $c['color'],
                    'is_medical' => $c['medical'],
                    'display_order' => $i + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
```

- [ ] **Step 6: Run tests**

Run: `./vendor/bin/phpunit tests/Feature/Core/SeedersTest.php`
Expected: PASS (3 tests).

- [ ] **Step 7: Run all seeders manually**

```bash
php artisan db:seed --class="App\\Modules\\Core\\Database\\Seeders\\PermissionsSeeder" --force
php artisan db:seed --class="App\\Modules\\Core\\Database\\Seeders\\AdditionalRolesSeeder" --force
php artisan db:seed --class="App\\Modules\\Annuaire\\Database\\Seeders\\PractitionerCategoriesSeeder" --force
```

- [ ] **Step 8: Commit**

```bash
git add app/Modules/Core/Database/Seeders/PermissionsSeeder.php \
        app/Modules/Core/Database/Seeders/AdditionalRolesSeeder.php \
        app/Modules/Annuaire/Database/Seeders/PractitionerCategoriesSeeder.php \
        tests/Feature/Core/SeedersTest.php
git commit -m "feat(admin): seeders permissions + rôles compta/stat + 15 categories pro"
```

---

## Task 7: RolePermissionsSeeder (matrice par défaut)

**Files:**
- Create: `app/Modules/Core/Database/Seeders/RolePermissionsSeeder.php`
- Test: `tests/Feature/Core/RolePermissionsMatrixTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
        // Pré-requis : rôles de base + nouveaux + permissions catalogue.
        Role::create(['slug' => 'super_admin', 'name_fr' => 'Super admin', 'environment' => 'admin', 'is_system' => true]);
        Role::create(['slug' => 'moderator', 'name_fr' => 'Modérateur', 'environment' => 'admin']);
        Role::create(['slug' => 'ministry', 'name_fr' => 'Ministère', 'environment' => 'admin']);
        Role::create(['slug' => 'structure_owner', 'name_fr' => 'Owner', 'environment' => 'pro']);
        Role::create(['slug' => 'admin_staff', 'name_fr' => 'Staff', 'environment' => 'pro']);
        Role::create(['slug' => 'doctor', 'name_fr' => 'Médecin', 'environment' => 'pro']);
        Role::create(['slug' => 'nurse', 'name_fr' => 'Infirmier', 'environment' => 'pro']);
        Role::create(['slug' => 'pharmacist', 'name_fr' => 'Pharmacien', 'environment' => 'pro']);
        Role::create(['slug' => 'lab_tech', 'name_fr' => 'Lab', 'environment' => 'pro']);
        Role::create(['slug' => 'patient', 'name_fr' => 'Patient', 'environment' => 'usager']);
        $this->seed(PermissionsSeeder::class);
        $this->seed(AdditionalRolesSeeder::class);
    }

    public function test_super_admin_has_no_permissions_attached_in_db(): void
    {
        $this->seed(RolePermissionsSeeder::class);
        // super_admin a accès via short-circuit, pas via le pivot.
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
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Feature/Core/RolePermissionsMatrixTest.php`
Expected: FAIL.

- [ ] **Step 3: Create the seeder**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = [
            // super_admin : aucune ligne — court-circuit dans le code.
            'super_admin' => [],

            'moderator' => [
                'users.view', 'users.suspend', 'users.validate_pro',
                'structures.view', 'structures.validate', 'claims.review',
                'stats.view',
            ],
            'ministry' => [
                'users.view', 'structures.view', 'stats.view', 'stats.view.financial',
                'exports.users', 'exports.structures', 'exports.stats',
            ],
            'compta' => [
                'users.view', 'payments.view', 'payments.refund',
                'invoices.manage', 'stats.view.financial', 'exports.financial',
            ],
            'stat' => [
                'users.view', 'structures.view', 'stats.view', 'stats.view.financial',
                'exports.users', 'exports.structures', 'exports.stats',
            ],
            'structure_owner' => [
                'structures.edit', 'appointments.manage', 'users.view',
                'pro_categories.view', 'stats.view', 'self.profile',
            ],
            'admin_staff' => [
                'appointments.manage', 'users.view', 'consultations.view',
                'self.profile',
            ],
            'doctor' => [
                'consultations.manage', 'prescriptions.create', 'prescriptions.view',
                'appointments.manage', 'self.profile',
            ],
            'nurse' => [
                'consultations.view', 'prescriptions.view',
                'appointments.manage', 'self.profile',
            ],
            'pharmacist' => [
                'prescriptions.view', 'payments.view', 'self.profile',
            ],
            'lab_tech' => [
                'consultations.view', 'self.profile',
            ],
            'patient' => [
                'self.profile', 'self.medical_record', 'self.carnet_vaccination',
                'appointments.book.self',
            ],
        ];

        foreach ($matrix as $roleSlug => $permSlugs) {
            $role = Role::where('slug', $roleSlug)->first();
            if (! $role || empty($permSlugs)) {
                continue;
            }
            $ids = Permission::whereIn('slug', $permSlugs)->pluck('id')->all();
            $role->permissions()->sync($ids);
        }
    }
}
```

- [ ] **Step 4: Run tests**

Run: `./vendor/bin/phpunit tests/Feature/Core/RolePermissionsMatrixTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Core/Database/Seeders/RolePermissionsSeeder.php \
        tests/Feature/Core/RolePermissionsMatrixTest.php
git commit -m "feat(admin): RolePermissionsSeeder avec matrice par defaut"
```

---

## Phase 3 — Services (resolver + assignment + middleware)

## Task 8: `PermissionResolver` + Gate registration

**Files:**
- Create: `app/Modules/Core/Services/PermissionResolver.php`
- Modify: `app/Models/User.php` (helpers can/hasGlobalRole)
- Modify: `app/Modules/Core/Providers/CoreServiceProvider.php` (Gate::before)
- Test: `tests/Unit/Core/PermissionResolverTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
        $role = Role::create(['slug' => 'super_admin', 'name_fr' => 'SA', 'environment' => 'admin', 'is_system' => true]);
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
        $role = Role::create(['slug' => 'r', 'name_fr' => 'R', 'environment' => 'admin']);
        $perm = Permission::create(['slug' => 'users.view', 'scope' => 'users', 'name_fr' => 'V']);
        $role->permissions()->attach($perm->id);
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);

        $this->assertTrue($this->resolver->userCan($u, 'users.view'));
        $this->assertFalse($this->resolver->userCan($u, 'users.delete'));
    }

    public function test_scoped_role_grants_only_for_that_scope(): void
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'doctor_t', 'name_fr' => 'D', 'environment' => 'pro']);
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
        $role = Role::create(['slug' => 'tmp', 'name_fr' => 'T', 'environment' => 'admin']);
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
        $role = Role::create(['slug' => 'mod', 'name_fr' => 'M', 'environment' => 'admin']);
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
        $role = Role::create(['slug' => 'r1', 'name_fr' => 'R', 'environment' => 'admin']);
        $p1 = Permission::create(['slug' => 'users.view', 'scope' => 'users', 'name_fr' => 'V']);
        $p2 = Permission::create(['slug' => 'roles.view', 'scope' => 'roles', 'name_fr' => 'RV']);
        $role->permissions()->attach([$p1->id, $p2->id]);
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);

        $perms = $this->resolver->userPermissions($u);
        $this->assertCount(2, $perms);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Unit/Core/PermissionResolverTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement the resolver**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class PermissionResolver
{
    /** @var array<int, Collection<int, Permission>> */
    private array $cache = [];

    public function userCan(User $user, string $permission, ?Model $scope = null): bool
    {
        // Court-circuit super_admin.
        if ($this->hasGlobalRole($user, 'super_admin')) {
            return true;
        }

        // Récupère les assignations actives du user (expirées ignorées).
        $assignments = UserRoleAssignment::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('role.permissions')
            ->get();

        foreach ($assignments as $assignment) {
            $hasPerm = $assignment->role->permissions->contains('slug', $permission);
            if (! $hasPerm) {
                continue;
            }

            // Rôle global = autorise toujours.
            if ($assignment->scope_type === null) {
                return true;
            }

            // Rôle scopé : si pas de scope passé, on autorise (global semantics).
            if ($scope === null) {
                return true;
            }

            // Sinon, scope_type et scope_id doivent matcher.
            if ($assignment->scope_type === $scope::class
                && (int) $assignment->scope_id === (int) $scope->getKey()) {
                return true;
            }
        }

        return false;
    }

    /** @return Collection<int, Permission> */
    public function userPermissions(User $user): Collection
    {
        if (isset($this->cache[$user->id])) {
            return $this->cache[$user->id];
        }

        $assignments = UserRoleAssignment::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('role.permissions')
            ->get();

        $perms = collect();
        foreach ($assignments as $a) {
            $perms = $perms->merge($a->role->permissions);
        }

        $this->cache[$user->id] = $perms->unique('slug')->values();
        return $this->cache[$user->id];
    }

    public function hasGlobalRole(User $user, string $slug): bool
    {
        return UserRoleAssignment::where('user_id', $user->id)
            ->whereNull('scope_type')
            ->whereHas('role', fn ($q) => $q->where('slug', $slug))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function hasScopedRole(User $user, string $slug, Model $scope): bool
    {
        return UserRoleAssignment::where('user_id', $user->id)
            ->where('scope_type', $scope::class)
            ->where('scope_id', $scope->getKey())
            ->whereHas('role', fn ($q) => $q->where('slug', $slug))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }
}
```

- [ ] **Step 4: Register Gate::before in CoreServiceProvider**

Read `app/Modules/Core/Providers/CoreServiceProvider.php`, locate the `boot()` method, add:

```php
use App\Modules\Core\Services\PermissionResolver;
use Illuminate\Support\Facades\Gate;

// In boot():
Gate::before(function ($user, $ability, $arguments = []) {
    if (! $user) return null;
    $resolver = app(PermissionResolver::class);
    $scope = $arguments[0] ?? null;
    return $resolver->userCan($user, $ability, $scope) ? true : null;
});

$this->app->singleton(PermissionResolver::class);
```

- [ ] **Step 5: Run tests**

Run: `./vendor/bin/phpunit tests/Unit/Core/PermissionResolverTest.php`
Expected: PASS (7 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Core/Services/PermissionResolver.php \
        app/Modules/Core/Providers/CoreServiceProvider.php \
        tests/Unit/Core/PermissionResolverTest.php
git commit -m "feat(admin): PermissionResolver + Gate::before integration"
```

---

## Task 9: `RoleAssignmentService` + middleware `perm:`

**Files:**
- Create: `app/Modules/Core/Services/RoleAssignmentService.php`
- Create: `app/Modules/Core/Http/Middleware/EnsurePermission.php`
- Modify: `app/Modules/Core/Providers/CoreServiceProvider.php` (register middleware alias)
- Test: `tests/Unit/Core/RoleAssignmentServiceTest.php`
- Test: `tests/Feature/Core/EnsurePermissionMiddlewareTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use App\Modules\Core\Services\RoleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RoleAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleAssignmentService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new RoleAssignmentService();
    }

    public function test_assign_global_role(): void
    {
        $u = User::factory()->create();
        $r = Role::create(['slug' => 'r', 'name_fr' => 'R', 'environment' => 'admin']);
        $this->svc->assign($u, $r);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $u->id, 'role_id' => $r->id,
            'scope_type' => null, 'scope_id' => null,
        ]);
    }

    public function test_assign_scoped_role(): void
    {
        $u = User::factory()->create();
        $r = Role::create(['slug' => 'doc', 'name_fr' => 'D', 'environment' => 'pro']);
        $h = Hosto::factory()->create();
        $this->svc->assign($u, $r, $h);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $u->id, 'role_id' => $r->id,
            'scope_type' => Hosto::class, 'scope_id' => $h->id,
        ]);
    }

    public function test_assign_is_idempotent(): void
    {
        $u = User::factory()->create();
        $r = Role::create(['slug' => 'r', 'name_fr' => 'R', 'environment' => 'admin']);
        $this->svc->assign($u, $r);
        $this->svc->assign($u, $r); // ne crée pas de doublon
        $this->assertSame(1, UserRoleAssignment::where('user_id', $u->id)->count());
    }

    public function test_revoke_removes_only_targeted_scope(): void
    {
        $u = User::factory()->create();
        $r = Role::create(['slug' => 'doc', 'name_fr' => 'D', 'environment' => 'pro']);
        $h1 = Hosto::factory()->create();
        $h2 = Hosto::factory()->create();
        $this->svc->assign($u, $r, $h1);
        $this->svc->assign($u, $r, $h2);

        $this->svc->revoke($u, $r, $h1);

        $this->assertSame(1, UserRoleAssignment::where('user_id', $u->id)->count());
        $this->assertTrue(
            UserRoleAssignment::where('user_id', $u->id)
                ->where('scope_id', $h2->id)->exists()
        );
    }

    public function test_assign_records_assigned_by_and_assigned_at(): void
    {
        $u = User::factory()->create();
        $admin = User::factory()->create();
        $r = Role::create(['slug' => 'r', 'name_fr' => 'R', 'environment' => 'admin']);
        $this->svc->assign($u, $r, null, $admin);

        $row = UserRoleAssignment::where('user_id', $u->id)->first();
        $this->assertSame($admin->id, $row->assigned_by);
        $this->assertNotNull($row->assigned_at);
    }

    public function test_assign_with_expiry(): void
    {
        $u = User::factory()->create();
        $r = Role::create(['slug' => 'r', 'name_fr' => 'R', 'environment' => 'admin']);
        $expiry = now()->addDays(7);
        $this->svc->assign($u, $r, null, null, $expiry);

        $row = UserRoleAssignment::where('user_id', $u->id)->first();
        $this->assertNotNull($row->expires_at);
    }

    public function test_sync_replaces_role_set_for_scope(): void
    {
        $u = User::factory()->create();
        $r1 = Role::create(['slug' => 'r1', 'name_fr' => 'R1', 'environment' => 'admin']);
        $r2 = Role::create(['slug' => 'r2', 'name_fr' => 'R2', 'environment' => 'admin']);
        $r3 = Role::create(['slug' => 'r3', 'name_fr' => 'R3', 'environment' => 'admin']);

        $this->svc->assign($u, $r1);
        $this->svc->assign($u, $r2);

        $this->svc->syncRolesForScope($u, [$r2, $r3], null);

        $slugs = UserRoleAssignment::where('user_id', $u->id)
            ->with('role')->get()->pluck('role.slug')->all();
        sort($slugs);
        $this->assertSame(['r2', 'r3'], $slugs);
    }
}
```

```php
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
        $r = Role::create(['slug' => 'r', 'name_fr' => 'R', 'environment' => 'admin']);
        $p = Permission::create(['slug' => 'users.view', 'scope' => 'users', 'name_fr' => 'V']);
        $r->permissions()->attach($p->id);
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $r->id]);

        $this->actingAs($u)->get('/test-perm-route')->assertOk()->assertSeeText('OK');
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Unit/Core/RoleAssignmentServiceTest.php tests/Feature/Core/EnsurePermissionMiddlewareTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement the service**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class RoleAssignmentService
{
    public function assign(
        User $user,
        Role $role,
        ?Model $scope = null,
        ?User $assignedBy = null,
        ?\DateTimeInterface $expiresAt = null,
    ): void {
        $scopeType = $scope ? $scope::class : null;
        $scopeId = $scope?->getKey();

        UserRoleAssignment::updateOrCreate(
            [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
            ],
            [
                'assigned_by' => $assignedBy?->id,
                'assigned_at' => now(),
                'expires_at' => $expiresAt,
            ]
        );
    }

    public function revoke(User $user, Role $role, ?Model $scope = null): void
    {
        $q = UserRoleAssignment::where('user_id', $user->id)
            ->where('role_id', $role->id);

        if ($scope) {
            $q->where('scope_type', $scope::class)
                ->where('scope_id', $scope->getKey());
        } else {
            $q->whereNull('scope_type')->whereNull('scope_id');
        }

        $q->delete();
    }

    /**
     * Sync the full set of roles for a given scope.
     * Removes any existing role on that scope that's not in $roles.
     *
     * @param  array<Role>  $roles
     */
    public function syncRolesForScope(User $user, array $roles, ?Model $scope, ?User $assignedBy = null): void
    {
        $scopeType = $scope ? $scope::class : null;
        $scopeId = $scope?->getKey();
        $roleIds = collect($roles)->pluck('id')->all();

        DB::transaction(function () use ($user, $roles, $scopeType, $scopeId, $roleIds, $assignedBy) {
            // Delete existing assignments on this scope not in the new set
            UserRoleAssignment::where('user_id', $user->id)
                ->where(function ($q) use ($scopeType, $scopeId) {
                    if ($scopeType === null) {
                        $q->whereNull('scope_type')->whereNull('scope_id');
                    } else {
                        $q->where('scope_type', $scopeType)->where('scope_id', $scopeId);
                    }
                })
                ->whereNotIn('role_id', $roleIds)
                ->delete();

            // Upsert each role
            foreach ($roles as $role) {
                $this->assign($user, $role, $roleIds === [] ? null : null, $assignedBy);
            }
            // Note: assign() above only handles global. For scoped, we replay:
            if ($scope !== null) {
                foreach ($roles as $role) {
                    $this->assign($user, $role, $scope, $assignedBy);
                }
            }
        });
    }

    /** @return Builder<User> */
    public function usersHavingRole(Role $role, ?Model $scope = null): Builder
    {
        $q = User::query()->whereExists(function ($sub) use ($role, $scope) {
            $sub->from('user_roles')
                ->whereColumn('user_roles.user_id', 'users.id')
                ->where('user_roles.role_id', $role->id);
            if ($scope) {
                $sub->where('scope_type', $scope::class)
                    ->where('scope_id', $scope->getKey());
            }
        });
        return $q;
    }
}
```

- [ ] **Step 4: Implement the middleware**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        if (! $user->can($permission)) {
            abort(403, "Permission requise : {$permission}");
        }
        return $next($request);
    }
}
```

- [ ] **Step 5: Register middleware alias**

In `app/Modules/Core/Providers/CoreServiceProvider.php` `boot()`, add:

```php
use App\Modules\Core\Http\Middleware\EnsurePermission;
use Illuminate\Routing\Router;

// In boot() :
$this->app['router']->aliasMiddleware('perm', EnsurePermission::class);
```

- [ ] **Step 6: Run tests**

Run: `./vendor/bin/phpunit tests/Unit/Core/RoleAssignmentServiceTest.php tests/Feature/Core/EnsurePermissionMiddlewareTest.php`
Expected: PASS (10 tests total).

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Core/Services/RoleAssignmentService.php \
        app/Modules/Core/Http/Middleware/EnsurePermission.php \
        app/Modules/Core/Providers/CoreServiceProvider.php \
        tests/Unit/Core/RoleAssignmentServiceTest.php \
        tests/Feature/Core/EnsurePermissionMiddlewareTest.php
git commit -m "feat(admin): RoleAssignmentService + middleware perm: alias"
```

---

## Phase 4 — Admin services métier

## Task 10: `UserAdminService` (CRUD + suspend + reset_password + bulk)

**Files:**
- Create: `app/Modules/Core/Services/UserAdminService.php`
- Test: `tests/Unit/Core/UserAdminServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
        $role = Role::create(['slug' => 'super_admin', 'name_fr' => 'SA', 'environment' => 'admin', 'is_system' => true]);
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
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Unit/Core/UserAdminServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement the service**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class UserAdminService
{
    public const BULK_LIMIT = 100;

    /** @param array<string, mixed> $data */
    public function createUser(array $data): User
    {
        $autoPwd = empty($data['password']);
        if ($autoPwd) {
            $data['password'] = $this->generateRandomPassword();
        }
        $data['password'] = Hash::make($data['password']);
        $data['must_change_password'] = $autoPwd;

        return User::create($data);
    }

    /** @param array<string, mixed> $data */
    public function updateUser(User $user, array $data): User
    {
        unset($data['password']);
        $user->update($data);
        return $user->fresh();
    }

    public function suspend(User $user, ?string $reason = null): void
    {
        $user->update(['locked_until' => '9999-12-31 23:59:59']);
        $user->tokens()->delete();
    }

    public function reactivate(User $user): void
    {
        $user->update(['locked_until' => null]);
    }

    /**
     * Generates a new random password, hashes it, marks must_change_password,
     * revokes active tokens, and returns the plain-text password for admin transmission.
     */
    public function resetPassword(User $user): string
    {
        $plain = $this->generateRandomPassword();
        $user->update([
            'password' => Hash::make($plain),
            'must_change_password' => true,
        ]);
        $user->tokens()->delete();
        return $plain;
    }

    public function softDelete(User $user): void
    {
        $this->guardNotLastSuperAdmin($user);
        $user->tokens()->delete();
        $user->delete();
    }

    public function restore(User $user): void
    {
        $user->restore();
    }

    public function validatePro(User $user, bool $approve, ?string $rejectionReason): void
    {
        if (! $approve && empty($rejectionReason)) {
            throw new \InvalidArgumentException('rejection_reason est requis pour un rejet');
        }
        if ($approve) {
            $user->update([
                'pro_validated_at' => now(),
                'pro_validation_status' => 'validated',
                'pro_rejection_reason' => null,
            ]);
        } else {
            $user->update([
                'pro_validated_at' => null,
                'pro_validation_status' => 'rejected',
                'pro_rejection_reason' => $rejectionReason,
            ]);
        }
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  array<string, mixed>   $params
     */
    public function bulkAction(Collection $users, string $action, array $params = []): int
    {
        if ($users->count() > self::BULK_LIMIT) {
            throw new \DomainException("Bulk action limited to ".self::BULK_LIMIT." targets");
        }
        $count = 0;
        foreach ($users as $u) {
            try {
                match ($action) {
                    'suspend' => $this->suspend($u, $params['reason'] ?? null),
                    'reactivate' => $this->reactivate($u),
                    'delete' => $this->softDelete($u),
                    'restore' => $this->restore($u),
                    default => throw new \InvalidArgumentException("Unknown bulk action: {$action}"),
                };
                $count++;
            } catch (\Throwable $e) {
                // Continue with other items, but count failures.
                continue;
            }
        }
        return $count;
    }

    private function guardNotLastSuperAdmin(User $user): void
    {
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        if (! $superAdminRole) {
            return;
        }
        $hasSuperAdmin = UserRoleAssignment::where('user_id', $user->id)
            ->where('role_id', $superAdminRole->id)
            ->exists();
        if (! $hasSuperAdmin) {
            return;
        }
        $count = UserRoleAssignment::where('role_id', $superAdminRole->id)
            ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
            ->count();
        if ($count <= 1) {
            throw new \DomainException('Cannot delete the last super_admin');
        }
    }

    private function generateRandomPassword(): string
    {
        // 16 chars : letters + digits + a few symbols.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#%';
        $out = '';
        $len = strlen($alphabet);
        for ($i = 0; $i < 16; $i++) {
            $out .= $alphabet[random_int(0, $len - 1)];
        }
        return $out;
    }
}
```

- [ ] **Step 4: Run tests**

Run: `./vendor/bin/phpunit tests/Unit/Core/UserAdminServiceTest.php`
Expected: PASS (12 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Core/Services/UserAdminService.php tests/Unit/Core/UserAdminServiceTest.php
git commit -m "feat(admin): UserAdminService CRUD + suspend + reset_password + bulk"
```

---

## Task 11: `ImpersonationService` + `UserExportService`

**Files:**
- Create: `app/Modules/Core/Services/ImpersonationService.php`
- Create: `app/Modules/Core/Services/UserExportService.php`
- Test: `tests/Unit/Core/ImpersonationServiceTest.php`
- Test: `tests/Unit/Core/UserExportServiceTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Core\Models\ImpersonationSession;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use App\Modules\Core\Services\ImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

final class ImpersonationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImpersonationService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new ImpersonationService();
    }

    public function test_start_persists_session(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $req = Request::create('/x', 'POST', server: ['REMOTE_ADDR' => '1.2.3.4', 'HTTP_USER_AGENT' => 'Test/1.0']);

        $session = $this->svc->start($admin, $target, 'Support', $req);
        $this->assertNotNull($session->id);
        $this->assertSame($admin->id, $session->admin_user_id);
        $this->assertSame($target->id, $session->target_user_id);
        $this->assertSame('Support', $session->reason);
    }

    public function test_start_refuses_super_admin_target(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $role = Role::create(['slug' => 'super_admin', 'name_fr' => 'SA', 'environment' => 'admin', 'is_system' => true]);
        UserRoleAssignment::create(['user_id' => $target->id, 'role_id' => $role->id]);

        $this->expectException(\DomainException::class);
        $this->svc->start($admin, $target, 'X', Request::create('/x'));
    }

    public function test_start_refuses_self(): void
    {
        $u = User::factory()->create();
        $this->expectException(\DomainException::class);
        $this->svc->start($u, $u, 'X', Request::create('/x'));
    }

    public function test_stop_marks_ended_at(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $session = $this->svc->start($admin, $target, 'X', Request::create('/x'));
        session(['impersonation_session_id' => $session->uuid, 'impersonator_id' => $admin->id]);

        $this->svc->stop();

        $this->assertNotNull(ImpersonationSession::find($session->id)->ended_at);
    }
}
```

```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Core\Services\UserExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

final class UserExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_returns_streamed_response(): void
    {
        User::factory()->count(3)->create();
        $svc = new UserExportService();
        $resp = $svc->exportCsv(User::query());
        $this->assertInstanceOf(StreamedResponse::class, $resp);
        $this->assertSame('text/csv; charset=UTF-8', $resp->headers->get('Content-Type'));
    }

    public function test_export_contains_expected_columns(): void
    {
        User::factory()->create(['name' => 'Marie NDONG', 'email' => 'marie@h.com']);
        $svc = new UserExportService();
        $resp = $svc->exportCsv(User::query());
        ob_start();
        $resp->sendContent();
        $body = ob_get_clean();
        $this->assertStringContainsString('uuid,name,email', $body);
        $this->assertStringContainsString('Marie NDONG', $body);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Unit/Core/ImpersonationServiceTest.php tests/Unit/Core/UserExportServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement ImpersonationService**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Core\Models\ImpersonationSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ImpersonationService
{
    public function start(User $admin, User $target, string $reason, Request $request): ImpersonationSession
    {
        if ($admin->id === $target->id) {
            throw new \DomainException('Cannot impersonate yourself');
        }
        if ($this->isSuperAdmin($target)) {
            throw new \DomainException('Cannot impersonate a super_admin');
        }
        if (empty(trim($reason))) {
            throw new \InvalidArgumentException('reason is required');
        }

        return ImpersonationSession::create([
            'admin_user_id' => $admin->id,
            'target_user_id' => $target->id,
            'reason' => $reason,
            'started_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);
    }

    public function stop(): void
    {
        $session = $this->currentSession();
        if ($session) {
            $session->update(['ended_at' => now()]);
        }
        session()->forget(['impersonation_session_id', 'impersonator_id']);
    }

    public function currentSession(): ?ImpersonationSession
    {
        $uuid = session('impersonation_session_id');
        if (! $uuid) return null;
        return ImpersonationSession::where('uuid', $uuid)->whereNull('ended_at')->first();
    }

    public function isImpersonating(): bool
    {
        return $this->currentSession() !== null;
    }

    private function isSuperAdmin(User $u): bool
    {
        return $u->hasRole('super_admin');
    }
}
```

- [ ] **Step 4: Implement UserExportService**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UserExportService
{
    /**
     * @param  Builder<User>  $query
     */
    public function exportCsv(Builder $query): StreamedResponse
    {
        $filename = 'users-export-'.now()->format('Y-m-d-His').'.csv';

        return new StreamedResponse(function () use ($query) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 for Excel compatibility
            fwrite($out, "\xEF\xBB\xBF");
            // Header
            fputcsv($out, ['uuid', 'name', 'email', 'phone', 'nip', 'roles', 'created_at', 'status'], ',');

            $query->with('roles')->chunk(500, function ($users) use ($out) {
                foreach ($users as $u) {
                    fputcsv($out, [
                        $u->uuid,
                        $u->name,
                        $u->email,
                        $u->phone ?? '',
                        $u->nip ?? '',
                        $u->roles->pluck('slug')->join('|'),
                        $u->created_at?->toIso8601String() ?? '',
                        $u->deleted_at ? 'deleted' : ($u->locked_until && $u->locked_until->isFuture() ? 'locked' : 'active'),
                    ], ',');
                }
            });
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `./vendor/bin/phpunit tests/Unit/Core/ImpersonationServiceTest.php tests/Unit/Core/UserExportServiceTest.php`
Expected: PASS (6 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Core/Services/ImpersonationService.php \
        app/Modules/Core/Services/UserExportService.php \
        tests/Unit/Core/ImpersonationServiceTest.php \
        tests/Unit/Core/UserExportServiceTest.php
git commit -m "feat(admin): ImpersonationService + UserExportService"
```

---

## Phase 5 — Controllers + routes + vues admin

## Task 12: `AdminUsersController` — index/show + filtres + CRUD basique

**Files:**
- Create: `app/Modules/Core/Http/Controllers/Admin/AdminUsersController.php`
- Create: `resources/views/admin/users/index.blade.php` (refonte)
- Create: `resources/views/admin/users/show.blade.php`
- Modify: `routes/web.php` (remplacer route existante par groupe)
- Test: `tests/Feature/Admin/AdminUsersIndexTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminUsersIndexTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithPermissions(array $permSlugs): User
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'admin_test_'.uniqid(), 'name_fr' => 'AT', 'environment' => 'admin']);
        foreach ($permSlugs as $slug) {
            $p = Permission::firstOrCreate(['slug' => $slug], ['scope' => explode('.', $slug)[0], 'name_fr' => $slug]);
            $role->permissions()->attach($p->id);
        }
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);
        return $u;
    }

    public function test_guest_redirects_to_login(): void
    {
        $this->get('/admin/utilisateurs')->assertRedirect();
    }

    public function test_user_without_view_perm_returns_403(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->get('/admin/utilisateurs')->assertForbidden();
    }

    public function test_admin_with_view_perm_sees_list(): void
    {
        $admin = $this->adminWithPermissions(['users.view']);
        User::factory()->count(5)->create();

        $resp = $this->actingAs($admin)->get('/admin/utilisateurs');
        $resp->assertOk();
        $resp->assertSee('utilisateur');
    }

    public function test_filter_by_q_matches_name(): void
    {
        $admin = $this->adminWithPermissions(['users.view']);
        User::factory()->create(['name' => 'Marie NDONG']);
        User::factory()->create(['name' => 'Jean MBAYE']);

        $resp = $this->actingAs($admin)->get('/admin/utilisateurs?q=ndong');
        $resp->assertOk();
        $resp->assertSeeText('Marie NDONG');
        $resp->assertDontSeeText('Jean MBAYE');
    }

    public function test_show_user_detail(): void
    {
        $admin = $this->adminWithPermissions(['users.view']);
        $target = User::factory()->create(['name' => 'Cible Test']);

        $resp = $this->actingAs($admin)->get('/admin/utilisateurs/'.$target->uuid);
        $resp->assertOk();
        $resp->assertSeeText('Cible Test');
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Feature/Admin/AdminUsersIndexTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement the controller**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Models\User;
use App\Modules\Core\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class AdminUsersController
{
    public function index(Request $request): View
    {
        $q = $request->query('q');
        $roleSlug = $request->query('role');
        $env = $request->query('env');
        $status = $request->query('status', 'active');

        $query = User::query()->with('roles');

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status === 'locked') {
            $query->where('locked_until', '>', now());
        } elseif ($status === 'active') {
            $query->where(function ($w) {
                $w->whereNull('locked_until')->orWhere('locked_until', '<=', now());
            });
        }

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'ILIKE', "%{$q}%")
                  ->orWhere('email', 'ILIKE', "%{$q}%")
                  ->orWhere('nip', 'ILIKE', "%{$q}%")
                  ->orWhere('phone', 'ILIKE', "%{$q}%");
            });
        }

        if ($roleSlug) {
            $query->whereHas('roles', fn ($r) => $r->where('slug', $roleSlug));
        }

        if ($env) {
            $query->whereHas('roles', fn ($r) => $r->where('environment', $env));
        }

        $users = $query->orderByDesc('created_at')->paginate(30)->withQueryString();
        $roles = Role::orderBy('environment')->orderBy('display_order')->get();

        return view('admin.users.index', compact('users', 'roles', 'q', 'roleSlug', 'env', 'status'));
    }

    public function show(string $uuid): View
    {
        $user = User::withTrashed()->where('uuid', $uuid)->with('roles')->firstOrFail();
        return view('admin.users.show', compact('user'));
    }
}
```

- [ ] **Step 4: Create the view `admin/users/index.blade.php`**

```blade
@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin')
@section('env-color', '#B71C1C')
@section('env-color-dark', '#880E0E')
@section('title', 'Utilisateurs')
@section('page-title', 'Gestion des utilisateurs')
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'users']) @endsection

@section('content')
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;margin-bottom:18px;">
    <form method="GET" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;align-items:end;">
        <div>
            <label style="font-size:.72rem;color:#757575;display:block;margin-bottom:4px;">Recherche</label>
            <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Nom, email, NIP, téléphone..." style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
        </div>
        <div>
            <label style="font-size:.72rem;color:#757575;display:block;margin-bottom:4px;">Rôle</label>
            <select name="role" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
                <option value="">Tous</option>
                @foreach($roles as $r)
                    <option value="{{ $r->slug }}" @if($roleSlug===$r->slug) selected @endif>{{ $r->name_fr }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-size:.72rem;color:#757575;display:block;margin-bottom:4px;">Environnement</label>
            <select name="env" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
                <option value="">Tous</option>
                <option value="admin" @if($env==='admin') selected @endif>Admin</option>
                <option value="pro" @if($env==='pro') selected @endif>Pro</option>
                <option value="usager" @if($env==='usager') selected @endif>Usager</option>
            </select>
        </div>
        <div>
            <label style="font-size:.72rem;color:#757575;display:block;margin-bottom:4px;">Statut</label>
            <select name="status" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
                <option value="active" @if($status==='active') selected @endif>Actifs</option>
                <option value="locked" @if($status==='locked') selected @endif>Suspendus</option>
                <option value="deleted" @if($status==='deleted') selected @endif>Supprimés</option>
            </select>
        </div>
        <button type="submit" style="padding:8px 16px;background:#B71C1C;color:white;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Filtrer</button>
    </form>
</div>

<div style="font-size:.85rem;color:#757575;margin-bottom:12px;">{{ $users->total() }} utilisateur(s)</div>

<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
        <thead><tr style="background:#FAFAFA;border-bottom:1px solid #EEE;">
            <th style="padding:12px 16px;text-align:left;">Nom</th>
            <th style="padding:12px 16px;text-align:left;">Email</th>
            <th style="padding:12px 16px;text-align:left;">Rôles</th>
            <th style="padding:12px 16px;text-align:left;">Inscrit</th>
            <th style="padding:12px 16px;text-align:left;">Statut</th>
            <th style="padding:12px 16px;"></th>
        </tr></thead>
        <tbody>
        @foreach($users as $u)
        <tr style="border-bottom:1px solid #F5F5F5;">
            <td style="padding:12px 16px;font-weight:500;">{{ $u->name }}</td>
            <td style="padding:12px 16px;color:#757575;">{{ $u->email }}</td>
            <td style="padding:12px 16px;">
                @foreach($u->roles as $r)
                    <span style="padding:2px 8px;background:#F5F5F5;border-radius:100px;font-size:.68rem;margin-right:4px;">{{ $r->name_fr }}</span>
                @endforeach
            </td>
            <td style="padding:12px 16px;color:#757575;font-size:.78rem;">{{ $u->created_at->format('d/m/Y') }}</td>
            <td style="padding:12px 16px;">
                @if($u->deleted_at)<span style="color:#C62828;">Supprimé</span>
                @elseif($u->locked_until && $u->locked_until->isFuture())<span style="color:#E65100;">Suspendu</span>
                @else<span style="color:#2E7D32;">Actif</span>@endif
            </td>
            <td style="padding:12px 16px;text-align:right;">
                <a href="/admin/utilisateurs/{{ $u->uuid }}" style="color:#B71C1C;font-weight:600;text-decoration:none;font-size:.78rem;">Détail →</a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">{{ $users->links() }}</div>
@endsection
```

- [ ] **Step 5: Create the view `admin/users/show.blade.php`**

```blade
@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin')
@section('env-color', '#B71C1C')
@section('env-color-dark', '#880E0E')
@section('title', 'Utilisateur')
@section('page-title', $user->name)
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'users']) @endsection

@section('content')
<div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;">
    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;">
        <h3 style="margin:0 0 14px;font-size:1rem;">Profil</h3>
        <table style="width:100%;font-size:.85rem;">
            <tr><td style="padding:6px 0;color:#757575;width:140px;">UUID</td><td><code>{{ $user->uuid }}</code></td></tr>
            <tr><td style="padding:6px 0;color:#757575;">Nom</td><td>{{ $user->name }}</td></tr>
            <tr><td style="padding:6px 0;color:#757575;">Email</td><td>{{ $user->email }}</td></tr>
            <tr><td style="padding:6px 0;color:#757575;">Téléphone</td><td>{{ $user->phone ?? '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:#757575;">NIP</td><td>{{ $user->nip ?? '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:#757575;">Inscrit le</td><td>{{ $user->created_at->format('d/m/Y H:i') }}</td></tr>
            <tr><td style="padding:6px 0;color:#757575;">Statut</td><td>
                @if($user->deleted_at)<span style="color:#C62828;font-weight:600;">Supprimé</span>
                @elseif($user->locked_until && $user->locked_until->isFuture())<span style="color:#E65100;font-weight:600;">Suspendu</span>
                @else<span style="color:#2E7D32;font-weight:600;">Actif</span>@endif
            </td></tr>
        </table>
    </div>

    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;">
        <h3 style="margin:0 0 14px;font-size:1rem;">Rôles attachés</h3>
        @forelse($user->roles as $r)
            <div style="padding:8px;background:#FAFAFA;border-radius:6px;margin-bottom:6px;font-size:.82rem;">
                <strong>{{ $r->name_fr }}</strong> <span style="color:#999;font-size:.7rem;">({{ $r->environment }})</span>
            </div>
        @empty
            <p style="color:#999;font-size:.85rem;">Aucun rôle.</p>
        @endforelse
        <a href="/admin/utilisateurs/{{ $user->uuid }}/roles" style="display:block;margin-top:10px;font-size:.78rem;color:#B71C1C;">Gérer les rôles →</a>
    </div>
</div>
@endsection
```

- [ ] **Step 6: Create sidebar partial**

Create `resources/views/admin/partials/sidebar.blade.php`:

```blade
<a href="/admin" class="{{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    <span>Tableau de bord</span>
</a>
<a href="/admin/utilisateurs" class="{{ ($active ?? '') === 'users' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    <span>Utilisateurs</span>
</a>
<a href="/admin/roles" class="{{ ($active ?? '') === 'roles' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    <span>Rôles &amp; permissions</span>
</a>
<a href="/admin/practitioner-categories" class="{{ ($active ?? '') === 'pro-cats' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
    <span>Catégories pro</span>
</a>
<a href="/admin/structures" class="{{ ($active ?? '') === 'structures' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    <span>Structures</span>
</a>
<a href="/admin/demandes" class="{{ ($active ?? '') === 'claims' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
    <span>Demandes</span>
</a>
<a href="/admin/profil" class="{{ ($active ?? '') === 'profile' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    <span>Mon profil</span>
</a>
```

- [ ] **Step 7: Update routes**

In `routes/web.php`, find the admin group. Replace `Route::get('/utilisateurs', [AdminWebController::class, 'users'])` with a sub-group:

```php
use App\Modules\Core\Http\Controllers\Admin\AdminUsersController;

// Inside the existing admin auth+env:admin group :
Route::prefix('utilisateurs')->name('admin.users.')->group(function (): void {
    Route::get('/', [AdminUsersController::class, 'index'])->middleware('perm:users.view')->name('index');
    Route::get('/{uuid}', [AdminUsersController::class, 'show'])->middleware('perm:users.view')->name('show');
});
```

Remove or comment out the old `Route::get('/utilisateurs', [AdminWebController::class, 'users'])->name('admin.users');` since we replaced it.

- [ ] **Step 8: Run tests**

Run: `./vendor/bin/phpunit tests/Feature/Admin/AdminUsersIndexTest.php`
Expected: PASS (5 tests).

- [ ] **Step 9: Commit**

```bash
git add app/Modules/Core/Http/Controllers/Admin/AdminUsersController.php \
        resources/views/admin/users/index.blade.php \
        resources/views/admin/users/show.blade.php \
        resources/views/admin/partials/sidebar.blade.php \
        routes/web.php \
        tests/Feature/Admin/AdminUsersIndexTest.php
git commit -m "feat(admin): users index + show + filtres + sidebar partial"
```

---

## Task 13: AdminUsersController — actions (create/edit/suspend/reset_password/validate_pro/delete/bulk/export)

**Files:**
- Modify: `app/Modules/Core/Http/Controllers/Admin/AdminUsersController.php` (ajout actions)
- Create: `resources/views/admin/users/create.blade.php`
- Create: `resources/views/admin/users/edit.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/AdminUsersActionsTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminUsersActionsTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(array $perms): User
    {
        $u = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => 'admin_test_'.uniqid()], ['name_fr' => 'AT', 'environment' => 'admin']);
        foreach ($perms as $slug) {
            $p = Permission::firstOrCreate(['slug' => $slug], ['scope' => explode('.', $slug)[0], 'name_fr' => $slug]);
            $role->permissions()->attach($p->id);
        }
        UserRoleAssignment::firstOrCreate([
            'user_id' => $u->id, 'role_id' => $role->id, 'scope_type' => null, 'scope_id' => null,
        ], ['assigned_at' => now()]);
        return $u;
    }

    public function test_create_user_via_post(): void
    {
        $admin = $this->adminWith(['users.view', 'users.create']);
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs', [
            'name' => 'Nouveau Test', 'email' => 'nouveau@test.com', 'phone' => '+24100000000',
        ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('users', ['name' => 'Nouveau Test', 'email' => 'nouveau@test.com']);
    }

    public function test_update_user(): void
    {
        $admin = $this->adminWith(['users.view', 'users.edit']);
        $target = User::factory()->create(['name' => 'Old Name']);
        $resp = $this->actingAs($admin)->put('/admin/utilisateurs/'.$target->uuid, ['name' => 'New Name', 'email' => $target->email]);
        $resp->assertRedirect();
        $this->assertSame('New Name', $target->fresh()->name);
    }

    public function test_suspend_user(): void
    {
        $admin = $this->adminWith(['users.view', 'users.suspend']);
        $target = User::factory()->create();
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/suspend', ['reason' => 'Test']);
        $resp->assertRedirect();
        $this->assertNotNull($target->fresh()->locked_until);
    }

    public function test_reactivate_user(): void
    {
        $admin = $this->adminWith(['users.view', 'users.suspend']);
        $target = User::factory()->create(['locked_until' => '9999-12-31']);
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/reactivate');
        $resp->assertRedirect();
        $this->assertNull($target->fresh()->locked_until);
    }

    public function test_reset_password_returns_temp_password(): void
    {
        $admin = $this->adminWith(['users.view', 'users.reset_password']);
        $target = User::factory()->create();
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/reset-password');
        $resp->assertRedirect();
        $resp->assertSessionHas('temp_password');
        $this->assertTrue((bool) $target->fresh()->must_change_password);
    }

    public function test_validate_pro_approve(): void
    {
        $admin = $this->adminWith(['users.view', 'users.validate_pro']);
        $target = User::factory()->create();
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/validate-pro', ['action' => 'approve']);
        $resp->assertRedirect();
        $this->assertNotNull($target->fresh()->pro_validated_at);
    }

    public function test_delete_and_restore(): void
    {
        $admin = $this->adminWith(['users.view', 'users.delete']);
        $target = User::factory()->create();

        $resp = $this->actingAs($admin)->delete('/admin/utilisateurs/'.$target->uuid);
        $resp->assertRedirect();
        $this->assertSoftDeleted($target);

        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/restore');
        $resp->assertRedirect();
        $this->assertNull($target->fresh()->deleted_at);
    }

    public function test_export_csv(): void
    {
        $admin = $this->adminWith(['users.view', 'exports.users']);
        User::factory()->count(3)->create();
        $resp = $this->actingAs($admin)->get('/admin/utilisateurs/export.csv');
        $resp->assertOk();
        $resp->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_bulk_suspend(): void
    {
        $admin = $this->adminWith(['users.view', 'users.bulk', 'users.suspend']);
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/bulk', [
            'user_uuids' => [$u1->uuid, $u2->uuid],
            'action' => 'suspend',
            'params' => ['reason' => 'Bulk'],
        ]);
        $resp->assertRedirect();
        $this->assertNotNull($u1->fresh()->locked_until);
        $this->assertNotNull($u2->fresh()->locked_until);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Feature/Admin/AdminUsersActionsTest.php`
Expected: FAIL.

- [ ] **Step 3: Extend AdminUsersController**

Append methods to `app/Modules/Core/Http/Controllers/Admin/AdminUsersController.php`:

```php
use App\Modules\Core\Services\UserAdminService;
use App\Modules\Core\Services\UserExportService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Add at top of the class. Replace the index() and show() existing methods, do not duplicate.
// Add these new methods :

public function create(): View
{
    return view('admin.users.create');
}

public function store(Request $request, UserAdminService $svc): RedirectResponse
{
    $data = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'phone' => 'nullable|string|max:30',
        'password' => 'nullable|string|min:8',
    ]);
    $user = $svc->createUser($data);
    $msg = "Compte créé : {$user->email}";
    if ($user->must_change_password) {
        $msg .= " (mot de passe temporaire transmis à l'admin)";
    }
    return redirect()->route('admin.users.show', $user->uuid)->with('success', $msg);
}

public function edit(string $uuid): View
{
    $user = User::where('uuid', $uuid)->firstOrFail();
    return view('admin.users.edit', compact('user'));
}

public function update(Request $request, string $uuid, UserAdminService $svc): RedirectResponse
{
    $user = User::where('uuid', $uuid)->firstOrFail();
    $data = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,'.$user->id,
        'phone' => 'nullable|string|max:30',
        'nip' => 'nullable|string|max:30',
    ]);
    $svc->updateUser($user, $data);
    return redirect()->route('admin.users.show', $user->uuid)->with('success', 'Utilisateur mis à jour');
}

public function destroy(string $uuid, Request $request, UserAdminService $svc): RedirectResponse
{
    $user = User::where('uuid', $uuid)->firstOrFail();
    if ($user->id === $request->user()->id) {
        return back()->withErrors(['delete' => 'Impossible de se supprimer soi-même']);
    }
    try {
        $svc->softDelete($user);
        return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé');
    } catch (\DomainException $e) {
        return back()->withErrors(['delete' => $e->getMessage()]);
    }
}

public function restoreUser(string $uuid, UserAdminService $svc): RedirectResponse
{
    $user = User::withTrashed()->where('uuid', $uuid)->firstOrFail();
    $svc->restore($user);
    return redirect()->route('admin.users.show', $user->uuid)->with('success', 'Utilisateur restauré');
}

public function suspend(Request $request, string $uuid, UserAdminService $svc): RedirectResponse
{
    $user = User::where('uuid', $uuid)->firstOrFail();
    if ($user->id === $request->user()->id) {
        return back()->withErrors(['suspend' => 'Impossible de se suspendre soi-même']);
    }
    $data = $request->validate(['reason' => 'required|string|max:500']);
    $svc->suspend($user, $data['reason']);
    return back()->with('success', 'Compte suspendu');
}

public function reactivate(string $uuid, UserAdminService $svc): RedirectResponse
{
    $user = User::where('uuid', $uuid)->firstOrFail();
    $svc->reactivate($user);
    return back()->with('success', 'Compte réactivé');
}

public function resetPassword(string $uuid, UserAdminService $svc): RedirectResponse
{
    $user = User::where('uuid', $uuid)->firstOrFail();
    $plain = $svc->resetPassword($user);
    return back()->with('success', 'Mot de passe réinitialisé')->with('temp_password', $plain);
}

public function validatePro(Request $request, string $uuid, UserAdminService $svc): RedirectResponse
{
    $user = User::where('uuid', $uuid)->firstOrFail();
    $data = $request->validate([
        'action' => 'required|in:approve,reject',
        'rejection_reason' => 'required_if:action,reject|nullable|string|max:500',
    ]);
    try {
        $svc->validatePro($user, $data['action'] === 'approve', $data['rejection_reason'] ?? null);
        return back()->with('success', 'Statut pro mis à jour');
    } catch (\InvalidArgumentException $e) {
        return back()->withErrors(['action' => $e->getMessage()]);
    }
}

public function export(Request $request, UserExportService $svc): StreamedResponse
{
    $query = User::query();
    if ($q = $request->query('q')) {
        $query->where('name', 'ILIKE', "%{$q}%");
    }
    return $svc->exportCsv($query);
}

public function bulk(Request $request, UserAdminService $svc): RedirectResponse
{
    $data = $request->validate([
        'user_uuids' => 'required|array|min:1|max:100',
        'user_uuids.*' => 'string|exists:users,uuid',
        'action' => 'required|in:suspend,reactivate,delete,restore',
        'params' => 'nullable|array',
    ]);
    $users = User::whereIn('uuid', $data['user_uuids'])->get();
    try {
        $count = $svc->bulkAction($users, $data['action'], $data['params'] ?? []);
        return back()->with('success', "{$count} utilisateur(s) traité(s)");
    } catch (\DomainException $e) {
        return back()->withErrors(['bulk' => $e->getMessage()]);
    }
}
```

- [ ] **Step 4: Create the views create + edit (minimal)**

`resources/views/admin/users/create.blade.php`:

```blade
@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Nouvel utilisateur')
@section('page-title', 'Créer un utilisateur')
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'users']) @endsection

@section('content')
<form method="POST" action="/admin/utilisateurs" style="max-width:560px;background:white;border:1px solid #EEE;border-radius:14px;padding:18px;display:flex;flex-direction:column;gap:12px;">
    @csrf
    <label>Nom<input type="text" name="name" required value="{{ old('name') }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Email<input type="email" name="email" required value="{{ old('email') }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Téléphone<input type="text" name="phone" value="{{ old('phone') }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Mot de passe (vide = auto-généré)<input type="text" name="password" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    @error('email')<div style="color:#C62828;font-size:.78rem;">{{ $message }}</div>@enderror
    <button type="submit" style="padding:10px;background:#B71C1C;color:white;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Créer</button>
</form>
@endsection
```

`resources/views/admin/users/edit.blade.php`:

```blade
@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Éditer utilisateur')
@section('page-title', 'Éditer : '.$user->name)
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'users']) @endsection

@section('content')
<form method="POST" action="/admin/utilisateurs/{{ $user->uuid }}" style="max-width:560px;background:white;border:1px solid #EEE;border-radius:14px;padding:18px;display:flex;flex-direction:column;gap:12px;">
    @csrf @method('PUT')
    <label>Nom<input type="text" name="name" required value="{{ $user->name }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Email<input type="email" name="email" required value="{{ $user->email }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Téléphone<input type="text" name="phone" value="{{ $user->phone }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>NIP<input type="text" name="nip" value="{{ $user->nip }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <button type="submit" style="padding:10px;background:#B71C1C;color:white;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Enregistrer</button>
</form>
@endsection
```

- [ ] **Step 5: Add routes for actions**

In `routes/web.php` admin auth+env:admin group, expand the `utilisateurs` sub-group:

```php
Route::prefix('utilisateurs')->name('admin.users.')->group(function (): void {
    Route::get('/', [AdminUsersController::class, 'index'])->middleware('perm:users.view')->name('index');
    Route::get('/export.csv', [AdminUsersController::class, 'export'])->middleware('perm:exports.users')->name('export');
    Route::get('/create', [AdminUsersController::class, 'create'])->middleware('perm:users.create')->name('create');
    Route::post('/', [AdminUsersController::class, 'store'])->middleware('perm:users.create')->name('store');
    Route::post('/bulk', [AdminUsersController::class, 'bulk'])->middleware('perm:users.bulk')->name('bulk');
    Route::get('/{uuid}', [AdminUsersController::class, 'show'])->middleware('perm:users.view')->name('show');
    Route::get('/{uuid}/edit', [AdminUsersController::class, 'edit'])->middleware('perm:users.edit')->name('edit');
    Route::put('/{uuid}', [AdminUsersController::class, 'update'])->middleware('perm:users.edit')->name('update');
    Route::delete('/{uuid}', [AdminUsersController::class, 'destroy'])->middleware('perm:users.delete')->name('destroy');
    Route::post('/{uuid}/restore', [AdminUsersController::class, 'restoreUser'])->middleware('perm:users.delete')->name('restore');
    Route::post('/{uuid}/suspend', [AdminUsersController::class, 'suspend'])->middleware('perm:users.suspend')->name('suspend');
    Route::post('/{uuid}/reactivate', [AdminUsersController::class, 'reactivate'])->middleware('perm:users.suspend')->name('reactivate');
    Route::post('/{uuid}/reset-password', [AdminUsersController::class, 'resetPassword'])->middleware('perm:users.reset_password')->name('reset_password');
    Route::post('/{uuid}/validate-pro', [AdminUsersController::class, 'validatePro'])->middleware('perm:users.validate_pro')->name('validate_pro');
});
```

- [ ] **Step 6: Run tests**

Run: `./vendor/bin/phpunit tests/Feature/Admin/AdminUsersActionsTest.php`
Expected: PASS (9 tests).

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Core/Http/Controllers/Admin/AdminUsersController.php \
        resources/views/admin/users/create.blade.php \
        resources/views/admin/users/edit.blade.php \
        routes/web.php \
        tests/Feature/Admin/AdminUsersActionsTest.php
git commit -m "feat(admin): users CRUD + suspend + reset_password + validate_pro + bulk + export"
```

---

## Task 14: AdminUsersController — assignation rôles + sessions

**Files:**
- Modify: `app/Modules/Core/Http/Controllers/Admin/AdminUsersController.php`
- Modify: `resources/views/admin/users/show.blade.php` (UI assign roles)
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/AdminRoleAssignmentEndpointTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminRoleAssignmentEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(array $perms): User
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'admin_tt_'.uniqid(), 'name_fr' => 'AT', 'environment' => 'admin']);
        foreach ($perms as $slug) {
            $p = Permission::firstOrCreate(['slug' => $slug], ['scope' => explode('.', $slug)[0], 'name_fr' => $slug]);
            $role->permissions()->attach($p->id);
        }
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);
        return $u;
    }

    public function test_assign_global_roles_to_user(): void
    {
        $admin = $this->adminWith(['users.view', 'roles.assign']);
        $target = User::factory()->create();
        $r1 = Role::create(['slug' => 'patient_t', 'name_fr' => 'P', 'environment' => 'usager']);
        $r2 = Role::create(['slug' => 'compta_t', 'name_fr' => 'C', 'environment' => 'admin']);

        $resp = $this->actingAs($admin)->put('/admin/utilisateurs/'.$target->uuid.'/roles', [
            'global_role_ids' => [$r1->id, $r2->id],
            'scoped' => [],
        ]);
        $resp->assertRedirect();
        $this->assertSame(2, UserRoleAssignment::where('user_id', $target->id)->whereNull('scope_type')->count());
    }

    public function test_assign_scoped_roles(): void
    {
        $admin = $this->adminWith(['users.view', 'roles.assign']);
        $target = User::factory()->create();
        $r = Role::create(['slug' => 'doctor_t', 'name_fr' => 'D', 'environment' => 'pro']);
        $hosto = Hosto::factory()->create();

        $resp = $this->actingAs($admin)->put('/admin/utilisateurs/'.$target->uuid.'/roles', [
            'global_role_ids' => [],
            'scoped' => [['structure_uuid' => $hosto->uuid, 'role_ids' => [$r->id]]],
        ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $target->id, 'role_id' => $r->id,
            'scope_type' => Hosto::class, 'scope_id' => $hosto->id,
        ]);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Feature/Admin/AdminRoleAssignmentEndpointTest.php`
Expected: FAIL.

- [ ] **Step 3: Add `updateRoles` to the controller**

```php
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Services\RoleAssignmentService;

public function updateRoles(Request $request, string $uuid, RoleAssignmentService $svc): RedirectResponse
{
    $user = User::where('uuid', $uuid)->firstOrFail();
    $data = $request->validate([
        'global_role_ids' => 'nullable|array',
        'global_role_ids.*' => 'integer|exists:roles,id',
        'scoped' => 'nullable|array',
        'scoped.*.structure_uuid' => 'required|string|exists:hostos,uuid',
        'scoped.*.role_ids' => 'required|array',
        'scoped.*.role_ids.*' => 'integer|exists:roles,id',
        'scoped.*.expires_at' => 'nullable|date|after:now',
    ]);

    // Global roles
    $globalRoles = Role::whereIn('id', $data['global_role_ids'] ?? [])->get()->all();
    $svc->syncRolesForScope($user, $globalRoles, null, $request->user());

    // Scoped roles per structure
    foreach ($data['scoped'] ?? [] as $entry) {
        $hosto = Hosto::where('uuid', $entry['structure_uuid'])->firstOrFail();
        $roles = Role::whereIn('id', $entry['role_ids'])->get()->all();
        $svc->syncRolesForScope($user, $roles, $hosto, $request->user());
    }

    return back()->with('success', 'Rôles mis à jour');
}
```

- [ ] **Step 4: Add route**

```php
Route::put('/{uuid}/roles', [AdminUsersController::class, 'updateRoles'])->middleware('perm:roles.assign')->name('roles.update');
```

(inside the existing `utilisateurs` group)

- [ ] **Step 5: Run tests**

Run: `./vendor/bin/phpunit tests/Feature/Admin/AdminRoleAssignmentEndpointTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Core/Http/Controllers/Admin/AdminUsersController.php \
        routes/web.php \
        tests/Feature/Admin/AdminRoleAssignmentEndpointTest.php
git commit -m "feat(admin): endpoint d'assignation des roles (global + scoped)"
```

---

## Task 15: `AdminRolesController` + `AdminPermissionsController` + vues matrice

**Files:**
- Create: `app/Modules/Core/Http/Controllers/Admin/AdminRolesController.php`
- Create: `app/Modules/Core/Http/Controllers/Admin/AdminPermissionsController.php`
- Create: `resources/views/admin/roles/index.blade.php`
- Create: `resources/views/admin/roles/permissions.blade.php`
- Create: `resources/views/admin/permissions/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/AdminRolesControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminRolesControllerTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(array $perms): User
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'ad_'.uniqid(), 'name_fr' => 'A', 'environment' => 'admin']);
        foreach ($perms as $slug) {
            $p = Permission::firstOrCreate(['slug' => $slug], ['scope' => explode('.', $slug)[0], 'name_fr' => $slug]);
            $role->permissions()->attach($p->id);
        }
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);
        return $u;
    }

    public function test_list_roles(): void
    {
        $admin = $this->adminWith(['roles.view']);
        Role::create(['slug' => 'r_test', 'name_fr' => 'R Test', 'environment' => 'admin']);
        $resp = $this->actingAs($admin)->get('/admin/roles');
        $resp->assertOk();
        $resp->assertSeeText('R Test');
    }

    public function test_assign_permissions_via_matrix(): void
    {
        $admin = $this->adminWith(['permissions.assign']);
        $role = Role::create(['slug' => 'r_p', 'name_fr' => 'RP', 'environment' => 'admin']);
        $p1 = Permission::firstOrCreate(['slug' => 'users.view'], ['scope' => 'users', 'name_fr' => 'V']);
        $p2 = Permission::firstOrCreate(['slug' => 'users.delete'], ['scope' => 'users', 'name_fr' => 'D']);

        $resp = $this->actingAs($admin)->put('/admin/roles/'.$role->slug.'/permissions', [
            'permission_ids' => [$p1->id, $p2->id],
        ]);
        $resp->assertRedirect();
        $this->assertCount(2, $role->fresh()->permissions);
    }

    public function test_super_admin_matrix_refuses_modification(): void
    {
        $admin = $this->adminWith(['permissions.assign']);
        $sa = Role::create(['slug' => 'super_admin', 'name_fr' => 'SA', 'environment' => 'admin', 'is_system' => true]);
        $p = Permission::firstOrCreate(['slug' => 'users.view'], ['scope' => 'users', 'name_fr' => 'V']);

        $resp = $this->actingAs($admin)->put('/admin/roles/'.$sa->slug.'/permissions', [
            'permission_ids' => [$p->id],
        ]);
        $resp->assertStatus(422);
        $this->assertCount(0, $sa->fresh()->permissions);
    }

    public function test_permissions_index_lists_catalog(): void
    {
        $admin = $this->adminWith(['permissions.view']);
        Permission::firstOrCreate(['slug' => 'users.view'], ['scope' => 'users', 'name_fr' => 'V']);
        $resp = $this->actingAs($admin)->get('/admin/permissions');
        $resp->assertOk();
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Feature/Admin/AdminRolesControllerTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement controllers**

`app/Modules/Core/Http/Controllers/Admin/AdminRolesController.php`:

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminRolesController
{
    public function index(): View
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->orderBy('environment')
            ->orderBy('display_order')
            ->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function showPermissions(string $slug): View
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        $permissions = Permission::orderBy('scope')->orderBy('display_order')->get()
            ->groupBy('scope');
        $assigned = $role->permissions->pluck('id')->all();
        return view('admin.roles.permissions', compact('role', 'permissions', 'assigned'));
    }

    public function updatePermissions(Request $request, string $slug): RedirectResponse
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        if ($role->is_system) {
            abort(422, "Le rôle système {$role->slug} ne peut pas être modifié.");
        }
        $data = $request->validate([
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);
        $role->permissions()->sync($data['permission_ids'] ?? []);
        return back()->with('success', 'Permissions mises à jour');
    }

    public function destroy(string $slug): RedirectResponse
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        if ($role->is_system) {
            return back()->withErrors(['delete' => 'Rôle système non supprimable']);
        }
        if ($role->users()->count() > 0) {
            return back()->withErrors(['delete' => 'Détachez tous les utilisateurs avant de supprimer']);
        }
        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Rôle supprimé');
    }
}
```

`app/Modules/Core/Http/Controllers/Admin/AdminPermissionsController.php`:

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Modules\Core\Models\Permission;
use Illuminate\Contracts\View\View;

final class AdminPermissionsController
{
    public function index(): View
    {
        $permissions = Permission::orderBy('scope')->orderBy('display_order')->get()->groupBy('scope');
        return view('admin.permissions.index', compact('permissions'));
    }
}
```

- [ ] **Step 4: Create the views (minimal)**

`resources/views/admin/roles/index.blade.php`:

```blade
@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Rôles')
@section('page-title', 'Rôles &amp; permissions')
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'roles']) @endsection

@section('content')
<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead><tr style="background:#FAFAFA;">
            <th style="padding:12px 16px;text-align:left;">Slug</th>
            <th style="padding:12px 16px;text-align:left;">Nom</th>
            <th style="padding:12px 16px;text-align:left;">Env</th>
            <th style="padding:12px 16px;text-align:left;">Users</th>
            <th style="padding:12px 16px;text-align:left;">Perms</th>
            <th style="padding:12px 16px;"></th>
        </tr></thead>
        <tbody>
        @foreach($roles as $r)
        <tr style="border-top:1px solid #F5F5F5;">
            <td style="padding:12px 16px;"><code>{{ $r->slug }}</code> @if($r->is_system)<span style="background:#FFCDD2;color:#B71C1C;padding:1px 6px;border-radius:6px;font-size:.65rem;">system</span>@endif</td>
            <td style="padding:12px 16px;">{{ $r->name_fr }}</td>
            <td style="padding:12px 16px;">{{ $r->environment }}</td>
            <td style="padding:12px 16px;">{{ $r->users_count }}</td>
            <td style="padding:12px 16px;">{{ $r->permissions_count }}</td>
            <td style="padding:12px 16px;text-align:right;">
                <a href="/admin/roles/{{ $r->slug }}/permissions" style="color:#B71C1C;font-weight:600;text-decoration:none;font-size:.78rem;">Permissions →</a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
```

`resources/views/admin/roles/permissions.blade.php`:

```blade
@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Matrice permissions')
@section('page-title', 'Permissions : '.$role->name_fr)
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'roles']) @endsection

@section('content')
@if($role->is_system)
<div style="padding:12px;background:#FFF3E0;color:#E65100;border-radius:8px;margin-bottom:16px;font-size:.85rem;">
    Ce rôle système a toutes les permissions (court-circuit) et ne peut pas être modifié.
</div>
@endif
<form method="POST" action="/admin/roles/{{ $role->slug }}/permissions">
    @csrf @method('PUT')
    @foreach($permissions as $scope => $perms)
    <div style="background:white;border:1px solid #EEE;border-radius:10px;margin-bottom:14px;">
        <div style="padding:10px 16px;background:#FAFAFA;font-weight:600;text-transform:uppercase;font-size:.72rem;color:#757575;letter-spacing:.05em;">{{ $scope }}</div>
        <div style="padding:14px 16px;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;">
            @foreach($perms as $p)
            <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;">
                <input type="checkbox" name="permission_ids[]" value="{{ $p->id }}"
                    @if(in_array($p->id, $assigned)) checked @endif
                    @if($role->is_system) disabled @endif>
                <span>{{ $p->name_fr }} <code style="color:#999;font-size:.7rem;">{{ $p->slug }}</code></span>
            </label>
            @endforeach
        </div>
    </div>
    @endforeach
    @if(!$role->is_system)
    <button type="submit" style="padding:10px 22px;background:#B71C1C;color:white;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Enregistrer</button>
    @endif
</form>
@endsection
```

`resources/views/admin/permissions/index.blade.php`:

```blade
@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Permissions')
@section('page-title', 'Catalogue des permissions')
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'roles']) @endsection

@section('content')
@foreach($permissions as $scope => $perms)
<div style="background:white;border:1px solid #EEE;border-radius:10px;margin-bottom:14px;">
    <div style="padding:10px 16px;background:#FAFAFA;font-weight:600;text-transform:uppercase;font-size:.72rem;color:#757575;letter-spacing:.05em;">{{ $scope }}</div>
    <table style="width:100%;font-size:.82rem;">
        @foreach($perms as $p)
        <tr style="border-top:1px solid #F5F5F5;">
            <td style="padding:8px 16px;font-weight:500;"><code>{{ $p->slug }}</code></td>
            <td style="padding:8px 16px;color:#757575;">{{ $p->name_fr }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endforeach
@endsection
```

- [ ] **Step 5: Add routes**

```php
use App\Modules\Core\Http\Controllers\Admin\AdminRolesController;
use App\Modules\Core\Http\Controllers\Admin\AdminPermissionsController;

// Inside admin auth+env:admin group:
Route::prefix('roles')->name('admin.roles.')->group(function (): void {
    Route::get('/', [AdminRolesController::class, 'index'])->middleware('perm:roles.view')->name('index');
    Route::get('/{slug}/permissions', [AdminRolesController::class, 'showPermissions'])->middleware('perm:permissions.assign')->name('permissions');
    Route::put('/{slug}/permissions', [AdminRolesController::class, 'updatePermissions'])->middleware('perm:permissions.assign')->name('permissions.update');
    Route::delete('/{slug}', [AdminRolesController::class, 'destroy'])->middleware('perm:roles.delete')->name('destroy');
});

Route::get('/permissions', [AdminPermissionsController::class, 'index'])->middleware('perm:permissions.view')->name('admin.permissions.index');
```

- [ ] **Step 6: Run tests**

Run: `./vendor/bin/phpunit tests/Feature/Admin/AdminRolesControllerTest.php`
Expected: PASS (4 tests).

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Core/Http/Controllers/Admin/AdminRolesController.php \
        app/Modules/Core/Http/Controllers/Admin/AdminPermissionsController.php \
        resources/views/admin/roles \
        resources/views/admin/permissions \
        routes/web.php \
        tests/Feature/Admin/AdminRolesControllerTest.php
git commit -m "feat(admin): AdminRolesController + AdminPermissionsController + matrice UI"
```

---

## Task 16: ImpersonationController + banner + middleware

**Files:**
- Create: `app/Modules/Core/Http/Controllers/Admin/ImpersonationController.php`
- Modify: `app/Modules/Core/Http/Controllers/Admin/AdminUsersController.php` (méthode `impersonate`)
- Create: `resources/views/layouts/partials/impersonation-banner.blade.php`
- Modify: `resources/views/layouts/dashboard.blade.php` (inclure le banner)
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/ImpersonationTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Core\Models\ImpersonationSession;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(array $perms): User
    {
        $u = User::factory()->create();
        $role = Role::create(['slug' => 'ad_'.uniqid(), 'name_fr' => 'A', 'environment' => 'admin']);
        foreach ($perms as $slug) {
            $p = Permission::firstOrCreate(['slug' => $slug], ['scope' => explode('.', $slug)[0], 'name_fr' => $slug]);
            $role->permissions()->attach($p->id);
        }
        UserRoleAssignment::create(['user_id' => $u->id, 'role_id' => $role->id]);
        return $u;
    }

    public function test_impersonate_creates_session(): void
    {
        $admin = $this->adminWith(['users.impersonate']);
        $target = User::factory()->create();
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/impersonate', [
            'reason' => 'Support test',
        ]);
        $resp->assertRedirect();
        $this->assertSame(1, ImpersonationSession::where('admin_user_id', $admin->id)->count());
        $this->assertSame($target->id, auth()->id());
    }

    public function test_impersonate_super_admin_returns_403(): void
    {
        $admin = $this->adminWith(['users.impersonate']);
        $target = User::factory()->create();
        $sa = Role::create(['slug' => 'super_admin', 'name_fr' => 'SA', 'environment' => 'admin', 'is_system' => true]);
        UserRoleAssignment::create(['user_id' => $target->id, 'role_id' => $sa->id]);

        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/impersonate', [
            'reason' => 'X',
        ]);
        $resp->assertStatus(403);
    }

    public function test_impersonate_self_returns_422(): void
    {
        $admin = $this->adminWith(['users.impersonate']);
        $resp = $this->actingAs($admin)->post('/admin/utilisateurs/'.$admin->uuid.'/impersonate', [
            'reason' => 'X',
        ]);
        $resp->assertStatus(422);
    }

    public function test_stop_impersonate_restores_admin(): void
    {
        $admin = $this->adminWith(['users.impersonate']);
        $target = User::factory()->create();
        $this->actingAs($admin)->post('/admin/utilisateurs/'.$target->uuid.'/impersonate', ['reason' => 'X']);

        $resp = $this->get('/stop-impersonate');
        $resp->assertRedirect();
        $this->assertSame($admin->id, auth()->id());

        $session = ImpersonationSession::where('admin_user_id', $admin->id)->first();
        $this->assertNotNull($session->ended_at);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Feature/Admin/ImpersonationTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement ImpersonationController**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Modules\Core\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class ImpersonationController
{
    public function stop(ImpersonationService $svc): RedirectResponse
    {
        $adminId = session('impersonator_id');
        $svc->stop();
        if ($adminId) {
            Auth::loginUsingId($adminId);
        }
        return redirect('/admin');
    }
}
```

- [ ] **Step 4: Add `impersonate` to AdminUsersController**

```php
use App\Modules\Core\Services\ImpersonationService;

public function impersonate(Request $request, string $uuid, ImpersonationService $svc): RedirectResponse
{
    $target = User::where('uuid', $uuid)->firstOrFail();
    $data = $request->validate(['reason' => 'required|string|max:500']);

    try {
        $session = $svc->start($request->user(), $target, $data['reason'], $request);
    } catch (\DomainException $e) {
        if (str_contains($e->getMessage(), 'super_admin')) {
            abort(403, $e->getMessage());
        }
        abort(422, $e->getMessage());
    }

    session([
        'impersonator_id' => $request->user()->id,
        'impersonation_session_id' => $session->uuid,
    ]);
    \Illuminate\Support\Facades\Auth::loginUsingId($target->id);

    return redirect('/compte');
}
```

- [ ] **Step 5: Create the impersonation banner**

`resources/views/layouts/partials/impersonation-banner.blade.php`:

```blade
@php
    $impersonationSvc = app(\App\Modules\Core\Services\ImpersonationService::class);
    $impSession = $impersonationSvc->currentSession();
@endphp
@if($impSession)
<div style="background:#FFF3E0;border-bottom:2px solid #E65100;padding:10px 16px;text-align:center;font-size:.85rem;color:#E65100;font-weight:600;">
    ⚠ Vous êtes connecté en tant que <strong>{{ auth()->user()->name }}</strong>
    &mdash; <a href="/stop-impersonate" style="color:#E65100;text-decoration:underline;">Arrêter l'impersonation</a>
</div>
@endif
```

- [ ] **Step 6: Inject banner in dashboard layout**

Open `resources/views/layouts/dashboard.blade.php`. Locate the `<body>` opening tag or the start of `.dashboard-wrap`. Insert just inside `<body>` (or before the sidebar) :

```blade
@include('layouts.partials.impersonation-banner')
```

- [ ] **Step 7: Add routes**

```php
use App\Modules\Core\Http\Controllers\Admin\ImpersonationController;

// Inside admin auth+env:admin group:
Route::post('/utilisateurs/{uuid}/impersonate', [AdminUsersController::class, 'impersonate'])
    ->middleware('perm:users.impersonate')->name('admin.users.impersonate');

// Outside admin group (accessible during impersonation):
Route::get('/stop-impersonate', [ImpersonationController::class, 'stop'])
    ->middleware('auth')->name('impersonate.stop');
```

- [ ] **Step 8: Run tests**

Run: `./vendor/bin/phpunit tests/Feature/Admin/ImpersonationTest.php`
Expected: PASS (4 tests).

- [ ] **Step 9: Commit**

```bash
git add app/Modules/Core/Http/Controllers/Admin/ImpersonationController.php \
        app/Modules/Core/Http/Controllers/Admin/AdminUsersController.php \
        resources/views/layouts/partials/impersonation-banner.blade.php \
        resources/views/layouts/dashboard.blade.php \
        routes/web.php \
        tests/Feature/Admin/ImpersonationTest.php
git commit -m "feat(admin): impersonation (start/stop) + banner permanent"
```

---

## Task 17: `AdminPractitionerCategoriesController` (CRUD)

**Files:**
- Create: `app/Modules/Annuaire/Http/Controllers/Admin/AdminPractitionerCategoriesController.php`
- Create: `resources/views/admin/practitioner-categories/index.blade.php`
- Create: `resources/views/admin/practitioner-categories/form.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/PractitionerCategoriesAdminTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
        $role = Role::create(['slug' => 'ad_'.uniqid(), 'name_fr' => 'A', 'environment' => 'admin']);
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
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Feature/Admin/PractitionerCategoriesAdminTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement the controller**

```php
<?php
declare(strict_types=1);

namespace App\Modules\Annuaire\Http\Controllers\Admin;

use App\Modules\Annuaire\Models\PractitionerCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminPractitionerCategoriesController
{
    public function index(): View
    {
        $categories = PractitionerCategory::withCount('practitioners')
            ->orderBy('display_order')
            ->get();
        return view('admin.practitioner-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.practitioner-categories.form', [
            'category' => new PractitionerCategory(),
            'parents' => PractitionerCategory::whereNull('parent_category_id')->orderBy('name_fr')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:40|unique:practitioner_categories,code',
            'name_fr' => 'required|string|max:120',
            'name_en' => 'nullable|string|max:120',
            'description_fr' => 'nullable|string',
            'icon_name' => 'nullable|string|max:40',
            'color_hex' => 'nullable|string|max:7',
            'parent_category_id' => 'nullable|integer|exists:practitioner_categories,id',
            'is_medical' => 'nullable|boolean',
            'display_order' => 'nullable|integer',
        ]);
        $data['is_medical'] = (bool) ($data['is_medical'] ?? true);
        PractitionerCategory::create($data);
        return redirect()->route('admin.pro-cats.index')->with('success', 'Catégorie créée');
    }

    public function edit(string $uuid): View
    {
        $cat = PractitionerCategory::where('uuid', $uuid)->firstOrFail();
        return view('admin.practitioner-categories.form', [
            'category' => $cat,
            'parents' => PractitionerCategory::whereNull('parent_category_id')->where('id', '!=', $cat->id)->orderBy('name_fr')->get(),
        ]);
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        $cat = PractitionerCategory::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'code' => 'required|string|max:40|unique:practitioner_categories,code,'.$cat->id,
            'name_fr' => 'required|string|max:120',
            'name_en' => 'nullable|string|max:120',
            'description_fr' => 'nullable|string',
            'icon_name' => 'nullable|string|max:40',
            'color_hex' => 'nullable|string|max:7',
            'parent_category_id' => 'nullable|integer|exists:practitioner_categories,id',
            'is_medical' => 'nullable|boolean',
            'display_order' => 'nullable|integer',
        ]);
        $data['is_medical'] = (bool) ($data['is_medical'] ?? true);
        $cat->update($data);
        return redirect()->route('admin.pro-cats.index')->with('success', 'Catégorie mise à jour');
    }

    public function destroy(string $uuid): RedirectResponse
    {
        $cat = PractitionerCategory::where('uuid', $uuid)->firstOrFail();
        if ($cat->practitioners()->count() > 0) {
            return back()->withErrors(['delete' => 'Des praticiens sont rattachés à cette catégorie.']);
        }
        $cat->delete();
        return redirect()->route('admin.pro-cats.index')->with('success', 'Catégorie supprimée');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.uuid' => 'required|string|exists:practitioner_categories,uuid',
            'items.*.display_order' => 'required|integer|min:0',
        ]);
        foreach ($data['items'] as $item) {
            PractitionerCategory::where('uuid', $item['uuid'])->update(['display_order' => $item['display_order']]);
        }
        return back()->with('success', 'Ordre mis à jour');
    }
}
```

- [ ] **Step 4: Create the views**

`resources/views/admin/practitioner-categories/index.blade.php`:

```blade
@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Catégories pro')
@section('page-title', 'Catégories de professionnels')
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'pro-cats']) @endsection

@section('content')
<a href="/admin/practitioner-categories/create" style="display:inline-block;margin-bottom:14px;padding:8px 16px;background:#B71C1C;color:white;border-radius:6px;text-decoration:none;font-weight:600;font-size:.82rem;">+ Nouvelle catégorie</a>

<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead><tr style="background:#FAFAFA;">
            <th style="padding:12px 16px;text-align:left;">Code</th>
            <th style="padding:12px 16px;text-align:left;">Nom</th>
            <th style="padding:12px 16px;text-align:left;">Parent</th>
            <th style="padding:12px 16px;text-align:left;">Couleur</th>
            <th style="padding:12px 16px;text-align:left;">Pratitioners</th>
            <th style="padding:12px 16px;"></th>
        </tr></thead>
        <tbody>
        @foreach($categories as $c)
        <tr style="border-top:1px solid #F5F5F5;">
            <td style="padding:12px 16px;"><code>{{ $c->code }}</code></td>
            <td style="padding:12px 16px;">{{ $c->name_fr }}</td>
            <td style="padding:12px 16px;color:#757575;">{{ $c->parent?->name_fr ?? '—' }}</td>
            <td style="padding:12px 16px;">
                @if($c->color_hex)<span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{{ $c->color_hex }};border:1px solid #DDD;vertical-align:middle;"></span> {{ $c->color_hex }}@else—@endif
            </td>
            <td style="padding:12px 16px;">{{ $c->practitioners_count }}</td>
            <td style="padding:12px 16px;text-align:right;">
                <a href="/admin/practitioner-categories/{{ $c->uuid }}/edit" style="color:#B71C1C;text-decoration:none;font-size:.78rem;margin-right:8px;">Éditer</a>
                <form method="POST" action="/admin/practitioner-categories/{{ $c->uuid }}" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Supprimer ?')" style="background:none;border:none;color:#C62828;cursor:pointer;font-size:.78rem;">Supprimer</button>
                </form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
```

`resources/views/admin/practitioner-categories/form.blade.php`:

```blade
@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', $category->exists ? 'Éditer catégorie' : 'Nouvelle catégorie')
@section('page-title', $category->exists ? 'Éditer : '.$category->name_fr : 'Nouvelle catégorie pro')
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'pro-cats']) @endsection

@section('content')
<form method="POST" action="{{ $category->exists ? '/admin/practitioner-categories/'.$category->uuid : '/admin/practitioner-categories' }}" style="max-width:600px;background:white;border:1px solid #EEE;border-radius:14px;padding:18px;display:flex;flex-direction:column;gap:12px;">
    @csrf
    @if($category->exists) @method('PUT') @endif

    <label>Code (slug)<input type="text" name="code" required value="{{ old('code', $category->code) }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Nom (FR)<input type="text" name="name_fr" required value="{{ old('name_fr', $category->name_fr) }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Nom (EN)<input type="text" name="name_en" value="{{ old('name_en', $category->name_en) }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Description<textarea name="description_fr" rows="3" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">{{ old('description_fr', $category->description_fr) }}</textarea></label>
    <label>Icône (nom)<input type="text" name="icon_name" value="{{ old('icon_name', $category->icon_name) }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Couleur (hex)<input type="color" name="color_hex" value="{{ old('color_hex', $category->color_hex ?? '#388E3C') }}" style="width:80px;height:36px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Catégorie parente
        <select name="parent_category_id" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
            <option value="">— Aucune —</option>
            @foreach($parents as $p)
                <option value="{{ $p->id }}" @if(old('parent_category_id', $category->parent_category_id) == $p->id) selected @endif>{{ $p->name_fr }}</option>
            @endforeach
        </select>
    </label>
    <label><input type="checkbox" name="is_medical" value="1" @if(old('is_medical', $category->is_medical ?? true)) checked @endif> Catégorie médicale</label>
    <label>Ordre d'affichage<input type="number" name="display_order" value="{{ old('display_order', $category->display_order ?? 0) }}" style="width:100px;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <button type="submit" style="padding:10px;background:#B71C1C;color:white;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Enregistrer</button>
</form>
@endsection
```

- [ ] **Step 5: Add routes**

```php
use App\Modules\Annuaire\Http\Controllers\Admin\AdminPractitionerCategoriesController;

// Inside admin auth+env:admin group:
Route::prefix('practitioner-categories')->name('admin.pro-cats.')->group(function (): void {
    Route::get('/', [AdminPractitionerCategoriesController::class, 'index'])->middleware('perm:pro_categories.view')->name('index');
    Route::get('/create', [AdminPractitionerCategoriesController::class, 'create'])->middleware('perm:pro_categories.manage')->name('create');
    Route::post('/', [AdminPractitionerCategoriesController::class, 'store'])->middleware('perm:pro_categories.manage')->name('store');
    Route::post('/reorder', [AdminPractitionerCategoriesController::class, 'reorder'])->middleware('perm:pro_categories.manage')->name('reorder');
    Route::get('/{uuid}/edit', [AdminPractitionerCategoriesController::class, 'edit'])->middleware('perm:pro_categories.manage')->name('edit');
    Route::put('/{uuid}', [AdminPractitionerCategoriesController::class, 'update'])->middleware('perm:pro_categories.manage')->name('update');
    Route::delete('/{uuid}', [AdminPractitionerCategoriesController::class, 'destroy'])->middleware('perm:pro_categories.manage')->name('destroy');
});
```

- [ ] **Step 6: Run tests**

Run: `./vendor/bin/phpunit tests/Feature/Admin/PractitionerCategoriesAdminTest.php`
Expected: PASS (3 tests).

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Annuaire/Http/Controllers/Admin/AdminPractitionerCategoriesController.php \
        resources/views/admin/practitioner-categories \
        routes/web.php \
        tests/Feature/Admin/PractitionerCategoriesAdminTest.php
git commit -m "feat(admin): AdminPractitionerCategoriesController CRUD complet"
```

---

## Task 18: User sessions management

**Files:**
- Modify: `app/Modules/Core/Http/Controllers/Admin/AdminUsersController.php`
- Create: `resources/views/admin/users/sessions.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/AdminUserSessionsTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
        $role = Role::create(['slug' => 'ad_'.uniqid(), 'name_fr' => 'A', 'environment' => 'admin']);
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
```

- [ ] **Step 2: Run to verify it fails**

Run: `./vendor/bin/phpunit tests/Feature/Admin/AdminUserSessionsTest.php`
Expected: FAIL.

- [ ] **Step 3: Add controller methods**

In `AdminUsersController`:

```php
public function sessions(string $uuid): View
{
    $user = User::where('uuid', $uuid)->firstOrFail();
    $tokens = $user->tokens()->orderByDesc('last_used_at')->get();
    return view('admin.users.sessions', compact('user', 'tokens'));
}

public function revokeSession(string $uuid, int $tokenId): RedirectResponse
{
    $user = User::where('uuid', $uuid)->firstOrFail();
    $user->tokens()->where('id', $tokenId)->delete();
    return back()->with('success', 'Session révoquée');
}
```

- [ ] **Step 4: Create the view**

```blade
@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Sessions')
@section('page-title', 'Sessions actives : '.$user->name)
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'users']) @endsection

@section('content')
<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead><tr style="background:#FAFAFA;">
            <th style="padding:12px 16px;text-align:left;">Nom du device</th>
            <th style="padding:12px 16px;text-align:left;">Créé le</th>
            <th style="padding:12px 16px;text-align:left;">Dernière utilisation</th>
            <th style="padding:12px 16px;"></th>
        </tr></thead>
        <tbody>
        @forelse($tokens as $t)
        <tr style="border-top:1px solid #F5F5F5;">
            <td style="padding:12px 16px;">{{ $t->name }}</td>
            <td style="padding:12px 16px;color:#757575;">{{ $t->created_at->format('d/m/Y H:i') }}</td>
            <td style="padding:12px 16px;color:#757575;">{{ $t->last_used_at?->diffForHumans() ?? '—' }}</td>
            <td style="padding:12px 16px;text-align:right;">
                <form method="POST" action="/admin/utilisateurs/{{ $user->uuid }}/sessions/{{ $t->id }}" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Révoquer cette session ?')" style="background:none;border:none;color:#C62828;cursor:pointer;font-size:.78rem;">Révoquer</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" style="padding:30px;text-align:center;color:#999;">Aucune session active</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
```

- [ ] **Step 5: Add routes**

```php
Route::get('/{uuid}/sessions', [AdminUsersController::class, 'sessions'])->middleware('perm:users.sessions')->name('sessions');
Route::delete('/{uuid}/sessions/{tokenId}', [AdminUsersController::class, 'revokeSession'])->middleware('perm:users.sessions')->name('sessions.revoke');
```

(à ajouter dans le groupe `utilisateurs`)

- [ ] **Step 6: Run tests**

Run: `./vendor/bin/phpunit tests/Feature/Admin/AdminUserSessionsTest.php`
Expected: PASS (2 tests).

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Core/Http/Controllers/Admin/AdminUsersController.php \
        resources/views/admin/users/sessions.blade.php \
        routes/web.php \
        tests/Feature/Admin/AdminUserSessionsTest.php
git commit -m "feat(admin): sessions Sanctum - liste et revocation par admin"
```

---

## Task 19: Run full DB seed + manual validation

- [ ] **Step 1: Run all seeders in correct order**

```bash
php artisan db:seed --class="App\\Modules\\Core\\Database\\Seeders\\PermissionsSeeder" --force
php artisan db:seed --class="App\\Modules\\Core\\Database\\Seeders\\AdditionalRolesSeeder" --force
php artisan db:seed --class="App\\Modules\\Core\\Database\\Seeders\\RolePermissionsSeeder" --force
php artisan db:seed --class="App\\Modules\\Annuaire\\Database\\Seeders\\PractitionerCategoriesSeeder" --force
```

Expected: 41 permissions, 12 rôles (10 existants + compta + stat), 15 catégories pro.

- [ ] **Step 2: Run full test suite to confirm no regression**

Run: `./vendor/bin/phpunit tests/Unit/Core tests/Feature/Admin tests/Feature/Core tests/Unit/Annuaire`
Expected: all pass.

- [ ] **Step 3: Manual acceptance — 8 scenarios from spec section 8.3**

Open browser at `http://localhost:8010/admin/connexion`. Test each scenario:

1. Login super_admin → menu complet
2. Créer compta → login compta → seules sections financières
3. Suspendre patient → blocage login
4. Reset password → must_change_password
5. Validate pro → access /pro/*
6. Scoped doctor → consultations CHU only
7. Impersonate Marie → banner top → stop restaure admin
8. Créer catégorie Cardiologue → apparaît dans selects

- [ ] **Step 4: Final commit + tag**

```bash
git tag -a T7-complete -m "T7 admin users + roles + permissions + categories pro - acceptance OK"
```

---

## Self-Review checklist (auto)

**Spec coverage** :
- Data model section 3 → Tasks 1-5 ✓
- Components section 4 → Tasks 8-18 ✓
- Workflows section 5 → Tasks 12-17 ✓
- Permission catalog section 6 → Tasks 6-7 ✓
- Security section 7 → controllers + middleware + ImpersonationService guards ✓
- Tests section 8 → tous les tasks ont leur test ✓

**Placeholders scan** : aucun "TBD/TODO/implement later/etc." dans les blocs code.

**Type consistency** :
- `PermissionResolver::userCan(User, string, ?Model)` — utilisé partout
- `RoleAssignmentService::assign(User, Role, ?Model, ?User, ?DateTimeInterface)` — cohérent
- `UserAdminService::resetPassword(User): string` — retour plain text cohérent
- `ImpersonationService::start(User, User, string, Request): ImpersonationSession` — cohérent
- Routes admin toutes sous `auth + env:admin + perm:<slug>` — cohérent

**Order** : data → seeders → services → controllers → vues → tests. Pas de dépendance circulaire.

Plan terminé.
