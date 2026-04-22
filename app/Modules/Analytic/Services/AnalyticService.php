<?php

declare(strict_types=1);

namespace App\Modules\Analytic\Services;

use App\Modules\Analytic\Models\EpiAlert;
use App\Modules\Analytic\Models\HealthStatDaily;
use App\Modules\Analytic\Models\PathologyStat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class AnalyticService
{
    /**
     * National summary: totals from health_stats_daily for the last 30 days.
     *
     * @return array<string, int>
     */
    public function nationalSummary(): array
    {
        $since = Carbon::now()->subDays(30)->toDateString();

        $row = HealthStatDaily::query()
            ->where('date', '>=', $since)
            ->selectRaw('
                COALESCE(SUM(consultations_count), 0)      AS consultations_count,
                COALESCE(SUM(teleconsultations_count), 0)   AS teleconsultations_count,
                COALESCE(SUM(appointments_count), 0)        AS appointments_count,
                COALESCE(SUM(prescriptions_count), 0)       AS prescriptions_count,
                COALESCE(SUM(exams_count), 0)               AS exams_count,
                COALESCE(SUM(hospitalizations_count), 0)    AS hospitalizations_count,
                COALESCE(SUM(births_count), 0)              AS births_count,
                COALESCE(SUM(deaths_count), 0)              AS deaths_count,
                COALESCE(SUM(vaccinations_count), 0)        AS vaccinations_count
            ')
            ->first();

        return [
            'consultations_count' => (int) $row->consultations_count,
            'teleconsultations_count' => (int) $row->teleconsultations_count,
            'appointments_count' => (int) $row->appointments_count,
            'prescriptions_count' => (int) $row->prescriptions_count,
            'exams_count' => (int) $row->exams_count,
            'hospitalizations_count' => (int) $row->hospitalizations_count,
            'births_count' => (int) $row->births_count,
            'deaths_count' => (int) $row->deaths_count,
            'vaccinations_count' => (int) $row->vaccinations_count,
        ];
    }

    /**
     * Top pathologies by total cases_count over the last 30 days.
     *
     * @return Collection<int, PathologyStat>
     */
    public function topPathologies(int $limit = 10): Collection
    {
        $since = Carbon::now()->subDays(30)->toDateString();

        return PathologyStat::query()
            ->where('date', '>=', $since)
            ->selectRaw('diagnostic_code, diagnostic_label, SUM(cases_count) AS cases_count')
            ->groupBy('diagnostic_code', 'diagnostic_label')
            ->orderByDesc('cases_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Active epidemiological alerts ordered by severity descending.
     *
     * @return Collection<int, EpiAlert>
     */
    public function activeAlerts(): Collection
    {
        return EpiAlert::query()
            ->where('status', 'active')
            ->orderByDesc('severity')
            ->get();
    }
}
