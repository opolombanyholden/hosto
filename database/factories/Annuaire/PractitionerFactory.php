<?php

declare(strict_types=1);

namespace Database\Factories\Annuaire;

use App\Modules\Annuaire\Models\Practitioner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Practitioner>
 */
final class PractitionerFactory extends Factory
{
    protected $model = Practitioner::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $first = $this->faker->firstName();
        $last = $this->faker->lastName();

        return [
            'title' => $this->faker->randomElement(['Dr', 'Dr', 'Dr', 'Pr']),
            'first_name' => $first,
            'last_name' => $last,
            'slug' => Str::slug("dr-{$first}-{$last}").'-'.$this->faker->unique()->numerify('###'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'practitioner_type' => 'doctor',
            'phone' => '+241'.random_int(10000000, 99999999),
            'is_active' => true,
            'is_verified' => true,
            'accepts_new_patients' => true,
        ];
    }
}
