<?php

declare(strict_types=1);

namespace App\Modules\Core\Database\Seeders;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\Role;
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
    }
}
