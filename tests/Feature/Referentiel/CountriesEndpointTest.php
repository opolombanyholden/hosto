<?php

declare(strict_types=1);

namespace Tests\Feature\Referentiel;

use App\Modules\Referentiel\Models\Country;
use App\Modules\Referentiel\Models\Region;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class CountriesEndpointTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_returns_active_countries_with_region_count(): void
    {
        Country::factory()->create(['iso2' => 'GA', 'name_fr' => 'Gabon', 'name_en' => 'Gabon']);
        Country::factory()->create(['iso2' => 'CM', 'name_fr' => 'Cameroun', 'name_en' => 'Cameroon']);

        $response = $this->getJson('/api/v1/referentiel/countries');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['iso2', 'iso3', 'name', 'name_fr', 'name_en', 'phone_prefix', 'currency_code'],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_show_returns_single_country_by_iso2(): void
    {
        Country::factory()->create(['iso2' => 'GA', 'name_fr' => 'Gabon', 'name_en' => 'Gabon']);

        $response = $this->getJson('/api/v1/referentiel/countries/GA');

        $response->assertOk()
            ->assertJsonPath('data.iso2', 'GA')
            ->assertJsonPath('data.name_fr', 'Gabon');
    }

    public function test_show_is_case_insensitive(): void
    {
        Country::factory()->create(['iso2' => 'GA', 'name_fr' => 'Gabon', 'name_en' => 'Gabon']);

        $this->getJson('/api/v1/referentiel/countries/ga')
            ->assertOk()
            ->assertJsonPath('data.iso2', 'GA');
    }

    public function test_show_returns_404_for_unknown_country(): void
    {
        $this->getJson('/api/v1/referentiel/countries/XX')
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_regions_returns_provinces_of_a_country(): void
    {
        $country = Country::factory()->create(['iso2' => 'GA', 'name_fr' => 'Gabon', 'name_en' => 'Gabon']);
        Region::factory()->count(3)->for($country)->create();

        $response = $this->getJson('/api/v1/referentiel/countries/GA/regions');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['uuid', 'code', 'kind', 'name', 'name_fr', 'name_en'],
                ],
            ]);
    }
}
