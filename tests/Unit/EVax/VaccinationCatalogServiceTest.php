<?php
declare(strict_types=1);

namespace Tests\Unit\EVax;

use App\Modules\EVax\Models\Vaccine;
use App\Modules\EVax\Services\VaccinationCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VaccinationCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_by_code_is_case_insensitive(): void
    {
        Vaccine::create(['code' => 'BCG', 'name_fr' => 'BCG', 'doses_total' => 1]);
        $svc = new VaccinationCatalogService();
        $this->assertNotNull($svc->findByCode('BCG'));
        $this->assertNotNull($svc->findByCode('bcg'));
        $this->assertNull($svc->findByCode('unknown'));
    }

    public function test_pev_schedule_returns_due_status_for_newborn(): void
    {
        Vaccine::create(['code' => 'BCG', 'name_fr' => 'BCG', 'schedule_age_days' => 0, 'doses_total' => 1, 'display_order' => 1]);
        Vaccine::create(['code' => 'PENTA1', 'name_fr' => 'Penta 1', 'schedule_age_days' => 42, 'doses_total' => 1, 'display_order' => 2]);
        Vaccine::create(['code' => 'COVID', 'name_fr' => 'COVID', 'schedule_age_days' => null, 'doses_total' => 1, 'display_order' => 99]);

        $svc = new VaccinationCatalogService();
        // Date de naissance = il y a 10 jours.
        $dob = now()->subDays(10)->toImmutable();
        $schedule = $svc->pevSchedule($dob);

        $bcgEntry = collect($schedule)->first(fn ($e) => $e['vaccine']->code === 'BCG');
        $pentaEntry = collect($schedule)->first(fn ($e) => $e['vaccine']->code === 'PENTA1');
        $covidEntry = collect($schedule)->first(fn ($e) => $e['vaccine']->code === 'COVID');

        $this->assertSame('due_now', $bcgEntry['status']);
        $this->assertSame('upcoming', $pentaEntry['status']);
        $this->assertNull($covidEntry, 'Vaccines without schedule_age_days are excluded from PEV schedule');
    }

    public function test_pev_schedule_marks_overdue_dose(): void
    {
        Vaccine::create(['code' => 'BCG', 'name_fr' => 'BCG', 'schedule_age_days' => 0, 'doses_total' => 1, 'display_order' => 1]);
        $svc = new VaccinationCatalogService();
        $dob = now()->subDays(60)->toImmutable();
        $schedule = $svc->pevSchedule($dob);
        $bcg = collect($schedule)->first(fn ($e) => $e['vaccine']->code === 'BCG');
        $this->assertSame('overdue', $bcg['status']);
    }
}
