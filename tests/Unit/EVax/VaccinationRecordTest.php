<?php
declare(strict_types=1);

namespace Tests\Unit\EVax;

use App\Models\User;
use App\Modules\EVax\Models\Dependent;
use App\Modules\EVax\Models\Vaccine;
use App\Modules\EVax\Models\VaccinationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VaccinationRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_can_target_a_patient_or_a_dependent(): void
    {
        $parent = User::factory()->create();
        $dep = Dependent::create([
            'user_id' => $parent->id,
            'first_name' => 'Junior',
            'last_name' => 'M.',
            'date_of_birth' => '2025-01-15',
        ]);
        $bcg = Vaccine::create([
            'code' => 'BCG',
            'name_fr' => 'BCG',
            'diseases' => ['tuberculose'],
            'doses_total' => 1,
        ]);

        $r1 = VaccinationRecord::create([
            'patient_id' => $parent->id,
            'vaccine_id' => $bcg->id,
            'vaccine_name' => $bcg->name_fr,
            'dose_number' => 1,
            'administered_at' => '2026-01-01',
            'is_standardized' => true,
            'carnet_revision' => 1,
        ]);

        $r2 = VaccinationRecord::create([
            'dependent_id' => $dep->id,
            'vaccine_id' => $bcg->id,
            'vaccine_name' => $bcg->name_fr,
            'dose_number' => 1,
            'administered_at' => '2026-02-01',
            'is_standardized' => true,
            'carnet_revision' => 1,
        ]);

        $this->assertTrue($r1->is_standardized);
        $this->assertSame($bcg->id, $r1->vaccine->id);
        $this->assertSame($dep->id, $r2->dependent->id);
    }

    public function test_record_for_dependent_has_no_patient_id(): void
    {
        $parent = User::factory()->create();
        $dep = Dependent::create([
            'user_id' => $parent->id,
            'first_name' => 'J', 'last_name' => 'M', 'date_of_birth' => '2025-01-15',
        ]);
        $r = VaccinationRecord::create([
            'dependent_id' => $dep->id,
            'vaccine_name' => 'BCG',
            'dose_number' => 1,
            'administered_at' => '2026-01-01',
            'is_standardized' => false,
            'carnet_revision' => 1,
        ]);
        $this->assertNull($r->patient_id);
        $this->assertSame($dep->id, $r->dependent_id);
    }
}
