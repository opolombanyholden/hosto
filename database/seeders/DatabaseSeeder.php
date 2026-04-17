<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Referentiel\Database\Seeders\GabonSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            GabonSeeder::class,
        ]);
    }
}
