<?php
declare(strict_types=1);

namespace Tests\Feature\RendezVous;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ThirdPartyLookupApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_returns_matched_for_existing_phone(): void
    {
        $caller = User::factory()->create();
        $third = User::factory()->create(['name' => 'M. Diop']);
        $third->forceFill(['phone_normalized' => '+24106000099'])->save();

        $resp = $this->actingAs($caller)
            ->getJson('/api/v1/rdv/third-party/lookup?phone=06000099');

        $resp->assertOk();
        $resp->assertJson(['matched' => true]);
    }

    public function test_lookup_returns_not_matched_for_unknown(): void
    {
        $caller = User::factory()->create();
        $resp = $this->actingAs($caller)
            ->getJson('/api/v1/rdv/third-party/lookup?phone=06999999');
        $resp->assertOk();
        $resp->assertJson(['matched' => false]);
    }

    public function test_lookup_normalizes_local_format(): void
    {
        $caller = User::factory()->create();
        $third = User::factory()->create();
        $third->forceFill(['phone_normalized' => '+24106000001'])->save();
        $resp = $this->actingAs($caller)
            ->getJson('/api/v1/rdv/third-party/lookup?phone=06000001');
        $resp->assertJson(['matched' => true]);
    }

    public function test_lookup_returns_400_on_invalid_phone(): void
    {
        $caller = User::factory()->create();
        $resp = $this->actingAs($caller)
            ->getJson('/api/v1/rdv/third-party/lookup?phone=abcd');
        $resp->assertStatus(400);
    }
}
