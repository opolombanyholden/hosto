<?php

declare(strict_types=1);

namespace Database\Factories\Referentiel;

use App\Modules\Referentiel\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
final class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $iso2 = strtoupper($this->faker->unique()->lexify('??'));

        return [
            'iso2' => $iso2,
            'iso3' => strtoupper($this->faker->unique()->lexify('???')),
            'name_fr' => $this->faker->country(),
            'name_en' => $this->faker->country(),
            'phone_prefix' => '+'.random_int(1, 999),
            'currency_code' => strtoupper($this->faker->lexify('???')),
            'default_language' => 'fr',
            'is_active' => true,
            'display_order' => 0,
        ];
    }
}
