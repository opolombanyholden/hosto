<?php

declare(strict_types=1);

namespace Tests\Feature\Annuaire;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Referentiel\Models\City;
use App\Modules\Referentiel\Models\Country;
use App\Modules\Referentiel\Models\Region;
use App\Modules\Referentiel\Models\Service;
use App\Modules\Referentiel\Models\StructureType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class GeoSearchTest extends TestCase
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

    public function test_proximity_search_returns_structures_within_radius(): void
    {
        // Centre: 0.39, 9.45 (Libreville)
        $near = Hosto::factory()->for($this->city)->create(['name' => 'Near']);
        $near->setCoordinates(0.391, 9.451); // ~200m

        $far = Hosto::factory()->for($this->city)->create(['name' => 'Far']);
        $far->setCoordinates(-1.633, 13.583); // Franceville, ~500km

        $response = $this->getJson('/api/v1/annuaire/hostos?lat=0.39&lng=9.45&rayon=5&sort=distance');

        $response->assertOk();
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('Near', $response->json('data.0.name'));
        $this->assertIsFloat($response->json('data.0.distance_km'));
    }

    public function test_proximity_search_sorts_by_distance(): void
    {
        $a = Hosto::factory()->for($this->city)->create(['name' => 'A - Far']);
        $a->setCoordinates(0.40, 9.48); // ~3.5km

        $b = Hosto::factory()->for($this->city)->create(['name' => 'B - Close']);
        $b->setCoordinates(0.391, 9.451); // ~200m

        $response = $this->getJson('/api/v1/annuaire/hostos?lat=0.39&lng=9.45&rayon=10&sort=distance');

        $response->assertOk();
        $this->assertSame('B - Close', $response->json('data.0.name'));
        $this->assertSame('A - Far', $response->json('data.1.name'));
    }

    public function test_combined_filter_type_and_proximity(): void
    {
        $pharmacie = StructureType::factory()->create(['slug' => 'pharmacie', 'name_fr' => 'Pharmacie', 'name_en' => 'Pharmacy']);
        $hopital = StructureType::factory()->create(['slug' => 'hopital', 'name_fr' => 'Hôpital', 'name_en' => 'Hospital']);

        $p = Hosto::factory()->for($this->city)->create(['name' => 'Pharma']);
        $p->structureTypes()->attach($pharmacie->id, ['is_primary' => true]);
        $p->setCoordinates(0.391, 9.451);

        $h = Hosto::factory()->for($this->city)->create(['name' => 'Hospital']);
        $h->structureTypes()->attach($hopital->id, ['is_primary' => true]);
        $h->setCoordinates(0.392, 9.452);

        $response = $this->getJson('/api/v1/annuaire/hostos?lat=0.39&lng=9.45&rayon=5&type=pharmacie');

        $response->assertOk();
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('Pharma', $response->json('data.0.name'));
    }

    public function test_filter_by_service_code(): void
    {
        $urgence = Service::factory()->create(['code' => 'URGENCE', 'category' => 'prestation', 'name_fr' => 'Urgences', 'name_en' => 'Emergency']);

        $withUrgence = Hosto::factory()->for($this->city)->create(['name' => 'With']);
        $withUrgence->services()->attach($urgence->id, ['is_available' => true]);

        Hosto::factory()->for($this->city)->create(['name' => 'Without']);

        $response = $this->getJson('/api/v1/annuaire/hostos?service=URGENCE');

        $response->assertOk();
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('With', $response->json('data.0.name'));
    }

    public function test_without_geo_params_returns_all_active_sorted_by_name(): void
    {
        Hosto::factory()->for($this->city)->create(['name' => 'Zeta Hospital']);
        Hosto::factory()->for($this->city)->create(['name' => 'Alpha Clinic']);

        $response = $this->getJson('/api/v1/annuaire/hostos');

        $response->assertOk();
        $this->assertSame(2, $response->json('meta.total'));
        $this->assertSame('Alpha Clinic', $response->json('data.0.name'));
    }
}
