<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Annuaire\Database\Seeders\HostosLibrevilleSeeder;
use App\Modules\Referentiel\Database\Seeders\GabonSeeder;
use App\Modules\Referentiel\Database\Seeders\ServicesSeeder;
use App\Modules\Referentiel\Database\Seeders\SpecialtiesSeeder;
use App\Modules\Referentiel\Database\Seeders\StructureTypesSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Referentiels (order matters — hostos depend on these)
            GabonSeeder::class,
            StructureTypesSeeder::class,
            SpecialtiesSeeder::class,
            ServicesSeeder::class,

            // Annuaire
            HostosLibrevilleSeeder::class,
        ]);
    }
}
