<?php
declare(strict_types=1);

namespace App\Modules\EVax\Services;

use App\Modules\EVax\Models\Vaccine;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class VaccinationCatalogService
{
    /** @return Collection<int, Vaccine> */
    public function all(): Collection
    {
        return Vaccine::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('code')
            ->get();
    }

    public function findByCode(string $code): ?Vaccine
    {
        return Vaccine::whereRaw('LOWER(code) = ?', [strtolower($code)])->first();
    }

    /**
     * Returns the expected PEV calendar for a given date of birth.
     * Status: 'upcoming' | 'due_now' | 'overdue'. Vaccines without
     * schedule_age_days are excluded (out-of-PEV vaccines like COVID/grippe).
     *
     * @return array<int, array{vaccine: Vaccine, due_at: CarbonImmutable, status: string}>
     */
    public function pevSchedule(\DateTimeInterface $dateOfBirth): array
    {
        $dob = CarbonImmutable::instance($dateOfBirth);
        $today = CarbonImmutable::today();
        $tolerance = 14; // days

        $out = [];
        foreach ($this->all() as $vaccine) {
            if ($vaccine->schedule_age_days === null) {
                continue;
            }
            $dueAt = $dob->addDays($vaccine->schedule_age_days);
            $delta = $today->diffInDays($dueAt, false);

            if ($delta > $tolerance) {
                $status = 'upcoming';
            } elseif ($delta < -$tolerance) {
                $status = 'overdue';
            } else {
                $status = 'due_now';
            }

            $out[] = ['vaccine' => $vaccine, 'due_at' => $dueAt, 'status' => $status];
        }
        return $out;
    }
}
