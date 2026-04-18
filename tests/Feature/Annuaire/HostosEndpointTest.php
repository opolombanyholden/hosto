<?php

declare(strict_types=1);

namespace Tests\Feature\Annuaire;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Referentiel\Models\City;
use App\Modules\Referentiel\Models\Country;
use App\Modules\Referentiel\Models\Region;
use App\Modules\Referentiel\Models\Service;
use App\Modules\Referentiel\Models\Specialty;
use App\Modules\Referentiel\Models\StructureType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class HostosEndpointTest extends TestCase
{
    use DatabaseTransactions;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $country = Country::factory()->create(['iso2' => 'GA', 'name_fr' => 'Gabon', 'name_en' => 'Gabon']);
        $region = Region::factory()->for($country)->create();
        $this->city = City::factory()->for($region)->create();
    }

    public function test_index_returns_active_structures(): void
    {
        Hosto::factory()->for($this->city)->create(['is_active' => true]);
        Hosto::factory()->for($this->city)->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/annuaire/hostos');

        $response->assertOk();
        $this->assertSame(1, $response->json('meta.total'));
    }

    public function test_index_filters_by_type_slug(): void
    {
        $hopital = StructureType::factory()->create(['slug' => 'hopital', 'name_fr' => 'Hôpital', 'name_en' => 'Hospital']);
        $pharmacie = StructureType::factory()->create(['slug' => 'pharmacie', 'name_fr' => 'Pharmacie', 'name_en' => 'Pharmacy']);

        $h1 = Hosto::factory()->for($this->city)->create();
        $h1->structureTypes()->attach($hopital->id, ['is_primary' => true]);

        $h2 = Hosto::factory()->for($this->city)->create();
        $h2->structureTypes()->attach($pharmacie->id, ['is_primary' => true]);

        $response = $this->getJson('/api/v1/annuaire/hostos?type=pharmacie');

        $response->assertOk();
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame($h2->uuid, $response->json('data.0.uuid'));
    }

    public function test_structure_with_multiple_types_appears_in_both_filters(): void
    {
        $clinique = StructureType::factory()->create(['slug' => 'clinique', 'name_fr' => 'Clinique', 'name_en' => 'Clinic']);
        $labo = StructureType::factory()->create(['slug' => 'laboratoire', 'name_fr' => 'Laboratoire', 'name_en' => 'Laboratory']);

        $poly = Hosto::factory()->for($this->city)->create(['name' => 'Polyclinique']);
        $poly->structureTypes()->attach($clinique->id, ['is_primary' => true]);
        $poly->structureTypes()->attach($labo->id, ['is_primary' => false]);

        // Should appear when filtering clinique
        $r1 = $this->getJson('/api/v1/annuaire/hostos?type=clinique');
        $this->assertSame(1, $r1->json('meta.total'));

        // Should also appear when filtering laboratoire
        $r2 = $this->getJson('/api/v1/annuaire/hostos?type=laboratoire');
        $this->assertSame(1, $r2->json('meta.total'));
    }

    public function test_show_returns_full_detail_with_services_and_pricing(): void
    {
        $hosto = Hosto::factory()->for($this->city)->create();

        $type = StructureType::factory()->create(['slug' => 'hopital', 'name_fr' => 'Hôpital', 'name_en' => 'Hospital']);
        $hosto->structureTypes()->attach($type->id, ['is_primary' => true]);

        $spec = Specialty::factory()->create(['code' => 'CARD', 'name_fr' => 'Cardiologie', 'name_en' => 'Cardiology']);
        $hosto->specialties()->attach($spec->id);

        $svc = Service::factory()->create(['code' => 'CONSULT', 'category' => 'prestation', 'name_fr' => 'Consultation', 'name_en' => 'Consultation']);
        $hosto->services()->attach($svc->id, ['tarif_min' => 5000, 'tarif_max' => 15000, 'is_available' => true]);

        $response = $this->getJson('/api/v1/annuaire/hostos/'.$hosto->uuid);

        $response->assertOk()
            ->assertJsonPath('data.uuid', $hosto->uuid)
            ->assertJsonPath('data.types.0.slug', 'hopital')
            ->assertJsonPath('data.specialties.0.code', 'CARD')
            ->assertJsonPath('data.services.prestation.0.code', 'CONSULT')
            ->assertJsonPath('data.services.prestation.0.tarif_min', 5000)
            ->assertJsonPath('data.services.prestation.0.tarif_max', 15000)
            ->assertJsonStructure([
                'data' => ['uuid', 'name', 'types', 'specialties', 'services', 'opening_hours'],
            ]);
    }

    public function test_show_returns_404_for_unknown_uuid(): void
    {
        $this->getJson('/api/v1/annuaire/hostos/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }

    public function test_index_fuzzy_search_by_name(): void
    {
        Hosto::factory()->for($this->city)->create(['name' => 'CHU de Libreville']);
        Hosto::factory()->for($this->city)->create(['name' => 'Pharmacie du Centre']);

        $response = $this->getJson('/api/v1/annuaire/hostos?q=CHU');

        $response->assertOk();
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('CHU de Libreville', $response->json('data.0.name'));
    }

    public function test_index_filters_guard_service(): void
    {
        Hosto::factory()->for($this->city)->create(['is_guard_service' => true, 'name' => 'Garde']);
        Hosto::factory()->for($this->city)->create(['is_guard_service' => false, 'name' => 'Normal']);

        $response = $this->getJson('/api/v1/annuaire/hostos?garde=1');

        $response->assertOk();
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('Garde', $response->json('data.0.name'));
    }
}
