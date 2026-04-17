<?php

declare(strict_types=1);

namespace Tests\Feature\Referentiel;

use App\Modules\Referentiel\Models\StructureType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class StructureTypesEndpointTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_returns_active_types_ordered(): void
    {
        StructureType::factory()->create(['slug' => 'z-last', 'name_fr' => 'Z Last', 'name_en' => 'Z Last', 'display_order' => 99]);
        StructureType::factory()->create(['slug' => 'a-first', 'name_fr' => 'A First', 'name_en' => 'A First', 'display_order' => 1]);

        $response = $this->getJson('/api/v1/referentiel/structure-types');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['slug', 'name', 'name_fr', 'name_en', 'icon']]])
            ->assertJsonPath('data.0.slug', 'a-first');
    }

    public function test_show_returns_single_type_by_slug(): void
    {
        StructureType::factory()->create(['slug' => 'pharmacie', 'name_fr' => 'Pharmacie', 'name_en' => 'Pharmacy']);

        $response = $this->getJson('/api/v1/referentiel/structure-types/pharmacie');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'pharmacie')
            ->assertJsonPath('data.name_fr', 'Pharmacie');
    }

    public function test_show_404_for_unknown_slug(): void
    {
        $this->getJson('/api/v1/referentiel/structure-types/unknown')
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }
}
