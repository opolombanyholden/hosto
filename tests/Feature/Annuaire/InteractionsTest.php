<?php

declare(strict_types=1);

namespace Tests\Feature\Annuaire;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\HostoRecommendation;
use App\Modules\Core\Models\Role;
use App\Modules\Referentiel\Models\City;
use App\Modules\Referentiel\Models\Country;
use App\Modules\Referentiel\Models\Region;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class InteractionsTest extends TestCase
{
    use DatabaseTransactions;

    private Hosto $partner;

    private Hosto $nonPartner;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $country = Country::factory()->create(['iso2' => 'GA', 'name_fr' => 'Gabon', 'name_en' => 'Gabon']);
        $region = Region::factory()->for($country)->create();
        $city = City::factory()->for($region)->create();

        $this->partner = Hosto::factory()->for($city)->create(['is_partner' => true]);
        $this->nonPartner = Hosto::factory()->for($city)->create(['is_partner' => false]);

        Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('slug', 'patient')->first());
    }

    // --- Like ---

    public function test_can_like_partner_structure(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/annuaire/hostos/{$this->partner->uuid}/like");

        $response->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.likes_count', 1);
    }

    public function test_can_unlike_by_toggling(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/annuaire/hostos/{$this->partner->uuid}/like");
        $response = $this->postJson("/api/v1/annuaire/hostos/{$this->partner->uuid}/like");

        $response->assertOk()
            ->assertJsonPath('data.liked', false)
            ->assertJsonPath('data.likes_count', 0);
    }

    public function test_cannot_like_non_partner(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/annuaire/hostos/{$this->nonPartner->uuid}/like")
            ->assertStatus(404);
    }

    public function test_like_requires_auth(): void
    {
        $this->postJson("/api/v1/annuaire/hostos/{$this->partner->uuid}/like")
            ->assertStatus(401);
    }

    public function test_like_status_shows_current_state(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/annuaire/hostos/{$this->partner->uuid}/like-status")
            ->assertOk()
            ->assertJsonPath('data.liked', false);

        $this->postJson("/api/v1/annuaire/hostos/{$this->partner->uuid}/like");

        $this->getJson("/api/v1/annuaire/hostos/{$this->partner->uuid}/like-status")
            ->assertOk()
            ->assertJsonPath('data.liked', true);
    }

    // --- Recommend ---

    public function test_can_recommend_partner_structure(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/annuaire/hostos/{$this->partner->uuid}/recommend", [
            'content' => 'Excellent service, personnel attentionne.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending_moderation');
    }

    public function test_cannot_recommend_twice(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/annuaire/hostos/{$this->partner->uuid}/recommend", [
            'content' => 'Super.',
        ]);

        $this->postJson("/api/v1/annuaire/hostos/{$this->partner->uuid}/recommend", [
            'content' => 'Encore mieux.',
        ])->assertStatus(409);
    }

    public function test_cannot_recommend_non_partner(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/annuaire/hostos/{$this->nonPartner->uuid}/recommend", [
            'content' => 'Bon.',
        ])->assertStatus(404);
    }

    public function test_recommendations_list_shows_only_approved(): void
    {
        // Non-approved recommendation should not appear.
        HostoRecommendation::create([
            'user_id' => $this->user->id,
            'hosto_id' => $this->partner->id,
            'content' => 'Pending reco',
            'is_approved' => false,
        ]);

        $this->getJson("/api/v1/annuaire/hostos/{$this->partner->uuid}/recommendations")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // --- Partner flag in resource ---

    public function test_hostos_resource_includes_partner_and_likes(): void
    {
        $response = $this->getJson('/api/v1/annuaire/hostos');

        $response->assertOk();

        /** @var array<string, mixed>|null $partner */
        $partner = collect(array_values($response->json('data')))->firstWhere('uuid', $this->partner->uuid);
        $this->assertTrue($partner['is_partner']);
        $this->assertSame(0, $partner['likes_count']);
    }
}
