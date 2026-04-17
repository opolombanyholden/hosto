<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Referentiel\Database\Seeders\GabonSeeder;
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
            // Referentiels
            GabonSeeder::class,
            StructureTypesSeeder::class,
            SpecialtiesSeeder::class,
        ]);
    }
}
