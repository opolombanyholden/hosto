<?php

declare(strict_types=1);

namespace Tests\Feature\Annuaire;

use App\Modules\Annuaire\Models\Hosto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class EmergencySearchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_urgences_any_returns_emergency_evacuation_or_guard_structures(): void
    {
        $emergency = Hosto::factory()->create(['is_emergency_service' => true, 'is_guard_service' => false, 'is_evacuation_service' => false]);
        $guard = Hosto::factory()->create(['is_emergency_service' => false, 'is_guard_service' => true, 'is_evacuation_service' => false]);
        $evacuation = Hosto::factory()->create(['is_emergency_service' => false, 'is_guard_service' => false, 'is_evacuation_service' => true]);
        $other = Hosto::factory()->create(['is_emergency_service' => false, 'is_guard_service' => false, 'is_evacuation_service' => false]);

        $uuids = collect(array_column((array) $this->getJson('/api/v1/annuaire/hostos?urgences_any=1&per_page=100')->assertOk()->json('data'), 'uuid'));

        $this->assertTrue($uuids->contains($emergency->uuid));
        $this->assertTrue($uuids->contains($guard->uuid));
        $this->assertTrue($uuids->contains($evacuation->uuid));
        $this->assertFalse($uuids->contains($other->uuid));
    }
}
