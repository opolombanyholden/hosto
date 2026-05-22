<?php
declare(strict_types=1);

namespace Tests\Unit\EVax;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserCarnetSecretTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_gets_a_carnet_qr_secret_on_creation(): void
    {
        $u = User::factory()->create();
        $this->assertNotNull($u->carnet_qr_secret);
        $this->assertSame(32, strlen($u->carnet_qr_secret));
    }

    public function test_carnet_qr_secret_is_unique(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $this->assertNotSame($u1->carnet_qr_secret, $u2->carnet_qr_secret);
    }
}
