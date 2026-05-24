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
        // Drop existing unique (user_id, role_id) — replaced by (user_id, role_id, scope_type, scope_id)
        DB::statement('ALTER TABLE user_roles DROP CONSTRAINT IF EXISTS user_roles_user_id_role_id_unique');

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->string('scope_type', 80)->nullable()->after('role_id');
            $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
            $table->foreignId('assigned_by')->nullable()->after('scope_id')
                ->constrained('users')->nullOnDelete();
            $table->timestampTz('assigned_at')->useCurrent()->after('assigned_by');
            $table->timestampTz('expires_at')->nullable()->after('assigned_at');
        });

        // PostgreSQL treats NULL != NULL in UNIQUE constraints, so we use two partial unique indexes:
        // 1) for global assignments (both scope columns NULL)
        // 2) for scoped assignments (both scope columns NOT NULL)
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS user_roles_global_unique
            ON user_roles (user_id, role_id)
            WHERE scope_type IS NULL AND scope_id IS NULL
        ');

        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS user_roles_scoped_unique
            ON user_roles (user_id, role_id, scope_type, scope_id)
            WHERE scope_type IS NOT NULL AND scope_id IS NOT NULL
        ');

        DB::statement('CREATE INDEX IF NOT EXISTS user_roles_scope_idx ON user_roles (scope_type, scope_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS user_roles_scope_idx');
        DB::statement('DROP INDEX IF EXISTS user_roles_scoped_unique');
        DB::statement('DROP INDEX IF EXISTS user_roles_global_unique');

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->dropForeign(['assigned_by']);
            $table->dropColumn(['scope_type', 'scope_id', 'assigned_by', 'assigned_at', 'expires_at']);
        });
    }
};
