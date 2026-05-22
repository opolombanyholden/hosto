<?php
declare(strict_types=1);

namespace Tests\Unit\EVax;

use App\Models\User;
use App\Modules\EVax\Models\Dependent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DependentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dependent_belongs_to_user_and_has_qr_secret(): void
    {
        $parent = User::factory()->create();
        $dep = Dependent::create([
            'user_id' => $parent->id,
            'first_name' => 'Junior',
            'last_name' => $parent->name,
            'date_of_birth' => '2025-01-15',
            'gender' => 'male',
        ]);

        $this->assertNotNull($dep->uuid);
        $this->assertNotNull($dep->carnet_qr_secret);
        $this->assertSame(32, strlen($dep->carnet_qr_secret));
        $this->assertSame($parent->id, $dep->user->id);
    }
}
