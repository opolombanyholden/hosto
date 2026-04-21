<?php

declare(strict_types=1);

namespace App\Modules\RendezVous\Database\Seeders;

use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic time slots for all practitioners across all
 * their structures, for the next 14 days.
 *
 * Each practitioner gets:
 *   - Morning slots (08:00-12:00) in their primary structure
 *   - Afternoon slots (14:00-17:00) in their secondary structure (if any)
 *   - Teleconsultation slots on specific days (if enabled)
 *   - Varied durations (20min, 30min, 45min depending on specialty)
 */
final class TimeSlotsSeeder extends Seeder
{
    public function run(): void
    {
        $practitioners = Practitioner::with('structures')->get();

        foreach ($practitioners as $prac) {
            if ($prac->structures->isEmpty()) {
                continue;
            }

            $duration = $this->durationForType($prac->practitioner_type);
            $primaryStructure = $prac->structures->first();
            $secondaryStructure = $prac->structures->count() > 1 ? $prac->structures->last() : null;

            for ($dayOffset = 1; $dayOffset <= 14; $dayOffset++) {
                $date = Carbon::today()->addDays($dayOffset);

                if ($date->isSunday()) {
                    continue;
                }

                // Saturday: morning only in primary structure.
                if ($date->isSaturday()) {
                    $this->createSlots($prac, $primaryStructure, $date, $this->morningSlots($duration), false);

                    continue;
                }

                // Weekdays: morning in primary, afternoon in secondary (or primary).
                $this->createSlots($prac, $primaryStructure, $date, $this->morningSlots($duration), false);

                $afternoonStructure = $secondaryStructure ?? $primaryStructure;
                $this->createSlots($prac, $afternoonStructure, $date, $this->afternoonSlots($duration), false);

                // Teleconsultation: dedicated slots on Tuesday and Thursday evenings.
                if ($prac->does_teleconsultation && in_array($date->dayOfWeekIso, [2, 4], true)) {
                    $this->createSlots($prac, $primaryStructure, $date, $this->teleconsultSlots(), true);
                }
            }
        }
    }

    /**
     * @param  list<array{start: string, end: string}>  $slots
     */
    private function createSlots(Practitioner $prac, mixed $structure, Carbon $date, array $slots, bool $isTeleconsult): void
    {
        $duration = $this->durationForType($prac->practitioner_type);

        foreach ($slots as $slot) {
            TimeSlot::firstOrCreate([
                'practitioner_id' => $prac->id,
                'hosto_id' => $structure->id,
                'date' => $date->toDateString(),
                'start_time' => $slot['start'],
            ], [
                'end_time' => $slot['end'],
                'duration_minutes' => $duration,
                'is_available' => true,
                'is_teleconsultation' => $isTeleconsult,
                'fee' => $isTeleconsult
                    ? (int) (($prac->consultation_fee_min ?? 15000) * 0.8) // 20% moins cher en TC
                    : $prac->consultation_fee_min,
            ]);
        }
    }

    private function durationForType(string $type): int
    {
        return match ($type) {
            'doctor' => 30,
            'pharmacist' => 15,
            'nurse', 'midwife' => 20,
            'dentist' => 45,
            default => 30,
        };
    }

    /**
     * @return list<array{start: string, end: string}>
     */
    private function morningSlots(int $duration): array
    {
        return $this->generateSlots('08:00', '12:00', $duration);
    }

    /**
     * @return list<array{start: string, end: string}>
     */
    private function afternoonSlots(int $duration): array
    {
        return $this->generateSlots('14:00', '17:00', $duration);
    }

    /**
     * @return list<array{start: string, end: string}>
     */
    private function teleconsultSlots(): array
    {
        return [
            ['start' => '17:30', 'end' => '18:00'],
            ['start' => '18:00', 'end' => '18:30'],
            ['start' => '18:30', 'end' => '19:00'],
            ['start' => '19:00', 'end' => '19:30'],
        ];
    }

    /**
     * Generate time slots between start and end with given duration.
     *
     * @return list<array{start: string, end: string}>
     */
    private function generateSlots(string $startTime, string $endTime, int $durationMinutes): array
    {
        $slots = [];
        $current = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        while ($current->copy()->addMinutes($durationMinutes)->lte($end)) {
            $slotEnd = $current->copy()->addMinutes($durationMinutes);
            $slots[] = [
                'start' => $current->format('H:i'),
                'end' => $slotEnd->format('H:i'),
            ];
            $current = $slotEnd;
        }

        return $slots;
    }
}
