<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Core\Services\UserExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

final class UserExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_returns_streamed_response(): void
    {
        User::factory()->count(3)->create();
        $svc = new UserExportService();
        $resp = $svc->exportCsv(User::query());
        $this->assertInstanceOf(StreamedResponse::class, $resp);
        $this->assertSame('text/csv; charset=UTF-8', $resp->headers->get('Content-Type'));
    }

    public function test_export_contains_expected_columns(): void
    {
        User::factory()->create(['name' => 'Marie NDONG', 'email' => 'marie@h.com']);
        $svc = new UserExportService();
        $resp = $svc->exportCsv(User::query());
        ob_start();
        $resp->sendContent();
        $body = ob_get_clean();
        $this->assertStringContainsString('uuid,name,email', $body);
        $this->assertStringContainsString('Marie NDONG', $body);
    }
}
