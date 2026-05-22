<?php
declare(strict_types=1);

namespace Tests\Unit\EVax;

use App\Modules\EVax\Models\Vaccine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VaccineModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_vaccine_can_be_persisted_and_retrieved(): void
    {
        $v = Vaccine::create([
            'code' => 'BCG',
            'oms_code' => 'XM1NL1',
            'name_fr' => 'BCG',
            'manufacturer' => 'Sanofi',
            'diseases' => ['tuberculose'],
            'schedule_age_days' => 0,
            'doses_total' => 1,
            'is_standardized' => true,
            'display_order' => 1,
            'is_active' => true,
        ]);

        $this->assertNotNull($v->uuid);
        $this->assertSame(['tuberculose'], $v->diseases);
        $this->assertTrue($v->is_active);
    }
}
