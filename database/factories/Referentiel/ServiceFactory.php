<?php

declare(strict_types=1);

namespace Database\Factories\Referentiel;

use App\Modules\Referentiel\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
final class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('SVC-???')),
            'category' => $this->faker->randomElement(['prestation', 'soin', 'examen']),
            'name_fr' => 'Service '.$this->faker->word(),
            'name_en' => 'Service '.$this->faker->word(),
            'is_active' => true,
            'display_order' => 0,
        ];
    }
}
