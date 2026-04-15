<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Tests\TestCase;

final class HealthEndpointTest extends TestCase
{
    public function test_liveness_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/core/health/live');

        $response->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.service', 'hosto-api')
            ->assertJsonPath('data.version', 'v1')
            ->assertJsonStructure(['data' => ['status', 'service', 'version', 'timestamp']]);
    }

    public function test_readiness_checks_database_and_redis(): void
    {
        $response = $this->getJson('/api/v1/core/health/ready');

        $response->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.checks.database.status', 'ok')
            ->assertJsonPath('data.checks.redis.status', 'ok');
    }

    public function test_request_id_header_is_echoed_back(): void
    {
        $response = $this->getJson('/api/v1/core/health/live');

        $this->assertTrue($response->headers->has('X-Request-Id'));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $response->headers->get('X-Request-Id') ?? '',
        );
    }

    public function test_security_headers_are_present(): void
    {
        $response = $this->getJson('/api/v1/core/health/live');

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
    }

    public function test_404_on_api_returns_structured_json_error(): void
    {
        $response = $this->getJson('/api/does-not-exist');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND')
            ->assertJsonStructure(['error' => ['code', 'message', 'request_id']]);
    }
}
