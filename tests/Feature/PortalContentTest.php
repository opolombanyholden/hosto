<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Annuaire\Models\Hosto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class PortalContentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_portal_shows_real_counts_and_no_unproven_claims(): void
    {
        Hosto::factory()->count(2)->create(['is_active' => true]);
        $count = Hosto::where('is_active', true)->count();

        $this->get('/')
            ->assertOk()
            ->assertSee((string) $count)
            ->assertDontSee('1 247')
            ->assertDontSee('AES-256')
            ->assertDontSee('Mode offline')
            ->assertDontSee('FHIR');
    }
}
