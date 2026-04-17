<?php

declare(strict_types=1);

namespace Database\Factories\Referentiel;

use App\Modules\Referentiel\Models\StructureType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StructureType>
 */
final class StructureTypeFactory extends Factory
{
    protected $model = StructureType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'name_fr' => 'Type '.$this->faker->word(),
            'name_en' => 'Type '.$this->faker->word(),
            'is_active' => true,
            'display_order' => 0,
        ];
    }
}
