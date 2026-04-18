<?php

declare(strict_types=1);

namespace App\Modules\RendezVous\Database\Seeders;

use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds demo time slots for the next 7 days.
 */
final class TimeSlotsSeeder extends Seeder
{
    public function run(): void
    {
        $practitioners = Practitioner::with('structures')->get();

        foreach ($practitioners as $prac) {
            $primary = $prac->structures->first();
            if (! $primary) {
                continue;
            }

            // Generate slots for the next 5 working days.
            for ($dayOffset = 1; $dayOffset <= 7; $dayOffset++) {
                $date = Carbon::today()->addDays($dayOffset);
                if ($date->isWeekend()) {
                    continue;
                }

                $slots = [
                    ['start' => '08:00', 'end' => '08:30'],
                    ['start' => '08:30', 'end' => '09:00'],
                    ['start' => '09:00', 'end' => '09:30'],
                    ['start' => '09:30', 'end' => '10:00'],
                    ['start' => '10:00', 'end' => '10:30'],
                    ['start' => '14:00', 'end' => '14:30'],
                    ['start' => '14:30', 'end' => '15:00'],
                    ['start' => '15:00', 'end' => '15:30'],
                ];

                foreach ($slots as $slot) {
                    TimeSlot::firstOrCreate([
                        'practitioner_id' => $prac->id,
                        'hosto_id' => $primary->id,
                        'date' => $date->toDateString(),
                        'start_time' => $slot['start'],
                    ], [
                        'end_time' => $slot['end'],
                        'duration_minutes' => 30,
                        'is_available' => true,
                        'is_teleconsultation' => $prac->does_teleconsultation,
                        'fee' => $prac->consultation_fee_min,
                    ]);
                }
            }
        }
    }
}
