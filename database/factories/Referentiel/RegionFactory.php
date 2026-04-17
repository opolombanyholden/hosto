<?php

declare(strict_types=1);

namespace Database\Factories\Referentiel;

use App\Modules\Referentiel\Models\Country;
use App\Modules\Referentiel\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Region>
 */
final class RegionFactory extends Factory
{
    protected $model = Region::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'code' => strtoupper($this->faker->unique()->lexify('??-?')),
            'kind' => 'province',
            'name_fr' => 'Province de '.$this->faker->city(),
            'name_en' => 'Province of '.$this->faker->city(),
            'is_active' => true,
            'display_order' => 0,
        ];
    }
}
