<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Annuaire\Database\Seeders\HostoMediaSeeder;
use App\Modules\Annuaire\Database\Seeders\HostosLibrevilleSeeder;
use App\Modules\Annuaire\Database\Seeders\PractitionersSeeder;
use App\Modules\Core\Database\Seeders\RolesSeeder;
use App\Modules\Referentiel\Database\Seeders\GabonSeeder;
use App\Modules\Referentiel\Database\Seeders\MedicationsSeeder;
use App\Modules\Referentiel\Database\Seeders\ServicesSeeder;
use App\Modules\Referentiel\Database\Seeders\SpecialtiesSeeder;
use App\Modules\Referentiel\Database\Seeders\StructureTypesSeeder;
use App\Modules\RendezVous\Database\Seeders\TimeSlotsSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Core
            RolesSeeder::class,

            // Referentiels
            GabonSeeder::class,
            StructureTypesSeeder::class,
            SpecialtiesSeeder::class,
            ServicesSeeder::class,
            MedicationsSeeder::class,

            // Annuaire
            HostosLibrevilleSeeder::class,
            HostoMediaSeeder::class,
            PractitionersSeeder::class,

            // RendezVous
            TimeSlotsSeeder::class,
        ]);
    }
}
