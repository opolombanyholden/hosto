<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class SchemaConventionsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_created_user_receives_uuid_automatically(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test-'.uniqid().'@hosto.local',
            'password' => 'secret-but-long-enough-12345',
        ]);

        $this->assertNotEmpty($user->uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $user->uuid,
        );
    }

    public function test_user_exposes_uuid_as_route_key(): void
    {
        $user = new User;

        $this->assertSame('uuid', $user->getRouteKeyName());
    }

    public function test_user_soft_deletes(): void
    {
        $user = User::create([
            'name' => 'To Delete',
            'email' => 'del-'.uniqid().'@hosto.local',
            'password' => 'secret-but-long-enough-12345',
        ]);

        $user->delete();

        $this->assertNotNull($user->fresh()->deleted_at);
        $this->assertTrue($user->trashed());
    }
}
