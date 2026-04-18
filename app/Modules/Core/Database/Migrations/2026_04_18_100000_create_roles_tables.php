<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles and user-role assignments.
 *
 * Three environments, each with its own set of roles:
 *
 *   admin : super_admin, moderator, ministry
 *   pro   : structure_owner, doctor, pharmacist, lab_tech, nurse, admin_staff
 *   usager: patient
 *
 * A user can have multiple roles (e.g., doctor + patient).
 *
 * @see docs/adr/0011-trois-environnements-authentification.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->string('slug', 50)->unique();
            $table->string('name_fr');
            $table->string('name_en');
            $table->string('environment', 20)->index(); // admin, pro, usager
            $table->text('description_fr')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
        });

        SchemaBuilder::installUpdatedAtTrigger('roles');

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        SchemaBuilder::dropUpdatedAtTrigger('roles');
        Schema::dropIfExists('roles');
    }
};
