<?php

declare(strict_types=1);

namespace Database\Factories\Referentiel;

use App\Modules\Referentiel\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Specialty>
 */
final class SpecialtyFactory extends Factory
{
    protected $model = Specialty::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('SPE-???')),
            'name_fr' => 'Spécialité '.$this->faker->word(),
            'name_en' => 'Specialty '.$this->faker->word(),
            'parent_id' => null,
            'is_active' => true,
            'display_order' => 0,
        ];
    }
}
