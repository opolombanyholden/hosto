<?php

declare(strict_types=1);

namespace Tests\Feature\Referentiel;

use App\Modules\Referentiel\Models\Specialty;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class SpecialtiesEndpointTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_returns_root_specialties_with_children(): void
    {
        $parent = Specialty::factory()->create(['code' => 'CHIR', 'name_fr' => 'Chirurgie', 'name_en' => 'Surgery']);
        Specialty::factory()->create(['code' => 'CHIR-GEN', 'name_fr' => 'Chirurgie générale', 'name_en' => 'General surgery', 'parent_id' => $parent->id]);
        Specialty::factory()->create(['code' => 'CARD', 'name_fr' => 'Cardiologie', 'name_en' => 'Cardiology']);

        $response = $this->getJson('/api/v1/referentiel/specialties');

        $response->assertOk();

        $data = $response->json('data');

        // Only root-level specialties returned (CHIR and CARD, not CHIR-GEN).
        $codes = array_column($data, 'code');
        $this->assertContains('CHIR', $codes);
        $this->assertContains('CARD', $codes);
        $this->assertNotContains('CHIR-GEN', $codes);

        // CHIR has children loaded.
        /** @var array<string, mixed>|null $chir */
        $chir = collect(array_values($data))->firstWhere('code', 'CHIR');
        $this->assertCount(1, $chir['children']);
        $this->assertSame('CHIR-GEN', $chir['children'][0]['code']);
    }

    public function test_show_returns_specialty_with_parent_and_children(): void
    {
        $parent = Specialty::factory()->create(['code' => 'PED', 'name_fr' => 'Pédiatrie', 'name_en' => 'Pediatrics']);
        $child = Specialty::factory()->create(['code' => 'PED-NEO', 'name_fr' => 'Néonatalogie', 'name_en' => 'Neonatology', 'parent_id' => $parent->id]);

        $response = $this->getJson('/api/v1/referentiel/specialties/'.$child->uuid);

        $response->assertOk()
            ->assertJsonPath('data.code', 'PED-NEO')
            ->assertJsonPath('data.parent.code', 'PED');
    }

    public function test_show_404_for_unknown_uuid(): void
    {
        $this->getJson('/api/v1/referentiel/specialties/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }
}
