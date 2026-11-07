<?php

declare(strict_types=1);

namespace App\Modules\Core\Database\Seeders;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates demo accounts for testing the three environments.
 */
final class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // Patient
        $patient = User::firstOrCreate(['email' => 'patient@hosto.ga'], [
            'name' => 'Jean Ndong',
            'password' => Hash::make('Patient2026!'),
            'phone' => '+24177000001',
        ]);
        $patient->roles()->syncWithoutDetaching(Role::where('slug', 'patient')->first()?->id);

        // Medecin
        $doc = User::firstOrCreate(['email' => 'medecin@hosto.ga'], [
            'name' => 'Dr. Marie Obame',
            'password' => Hash::make('Medecin2026!'),
            'phone' => '+24177000002',
        ]);
        $doc->roles()->syncWithoutDetaching(Role::where('slug', 'doctor')->first()?->id);
        Practitioner::where('last_name', 'Obame')->update(['user_id' => $doc->id]);

        // Admin
        $admin = User::firstOrCreate(['email' => 'admin@hosto.ga'], [
            'name' => 'Admin HOSTO',
            'password' => Hash::make('Admin2026!'),
            'phone' => '+24177000000',
        ]);
        $admin->roles()->syncWithoutDetaching(Role::where('slug', 'super_admin')->first()?->id);

        $this->staffAccount('pharmacien@hosto.ga', 'Pharmacien Demo', 'Pharmacien2026!', 'pharmacist', 'pharmacie');
        $this->staffAccount('laborantin@hosto.ga', 'Laborantin Demo', 'Laborantin2026!', 'lab_tech', 'laboratoire');
    }

    /**
     * Staff accounts work for one structure, given by the scope of their role.
     */
    private function staffAccount(string $email, string $name, string $password, string $roleSlug, string $typeSlug): void
    {
        $role = Role::where('slug', $roleSlug)->first();
        $structure = Hosto::whereHas('structureTypes', fn ($q) => $q->where('slug', $typeSlug))->first();

        if ($role === null || $structure === null) {
            return;
        }

        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        UserRoleAssignment::firstOrCreate([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => Hosto::class,
            'scope_id' => $structure->id,
        ]);
    }
}
