<?php

declare(strict_types=1);

namespace Database\Factories\Referentiel;

use App\Modules\Referentiel\Models\City;
use App\Modules\Referentiel\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
final class CityFactory extends Factory
{
    protected $model = City::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'region_id' => Region::factory(),
            'name_fr' => $this->faker->city(),
            'name_en' => $this->faker->city(),
            'is_capital' => false,
            'population' => $this->faker->numberBetween(1000, 500000),
            'is_active' => true,
            'display_order' => 0,
        ];
    }

    public function capital(): self
    {
        return $this->state(['is_capital' => true]);
    }
}
