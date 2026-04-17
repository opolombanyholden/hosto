<?php

declare(strict_types=1);

namespace Database\Factories\Annuaire;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Referentiel\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Hosto>
 */
final class HostoFactory extends Factory
{
    protected $model = Hosto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company().' '.$this->faker->randomElement(['Medical', 'Health', 'Clinic', 'Hospital']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numerify('###'),
            'city_id' => City::factory(),
            'address' => $this->faker->streetAddress(),
            'phone' => '+241'.random_int(10000000, 99999999),
            'email' => $this->faker->companyEmail(),
            'is_public' => $this->faker->boolean(70),
            'is_guard_service' => $this->faker->boolean(30),
            'is_active' => true,
            'is_verified' => $this->faker->boolean(60),
        ];
    }
}
