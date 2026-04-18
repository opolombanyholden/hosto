<?php

declare(strict_types=1);

namespace Database\Factories\Referentiel;

use App\Modules\Referentiel\Models\Medication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medication>
 */
final class MedicationFactory extends Factory
{
    protected $model = Medication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dci' => $this->faker->unique()->word().' '.$this->faker->randomElement(['500mg', '1g', '250mg']),
            'therapeutic_class' => $this->faker->randomElement(['Antalgique', 'Antibiotique', 'Anti-inflammatoire']),
            'dosage_form' => $this->faker->randomElement(['comprime', 'gelule', 'sirop', 'injectable']),
            'prescription_required' => $this->faker->boolean(60),
            'is_active' => true,
        ];
    }
}
