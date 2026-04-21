<?php

declare(strict_types=1);

namespace Database\Factories\Pro;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Pro\Models\Consultation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consultation>
 */
final class ConsultationFactory extends Factory
{
    protected $model = Consultation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'practitioner_id' => Practitioner::factory(),
            'patient_id' => User::factory(),
            'hosto_id' => Hosto::factory(),
            'status' => 'completed',
            'motif' => $this->faker->sentence(4),
            'started_at' => now(),
            'completed_at' => now(),
        ];
    }
}
