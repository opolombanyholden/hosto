<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\MedicalRecordGrant;
use App\Modules\Core\Services\MedicalRecordGrantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

final class MedicalRecordGrantServiceTest extends TestCase
{
    use RefreshDatabase;

    private MedicalRecordGrantService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new MedicalRecordGrantService();
    }

    public function test_grant_creates_active_record(): void
    {
        $p = User::factory()->create();
        $prac = Practitioner::factory()->create();
        $g = $this->svc->grant($p, $prac, null, null);
        $this->assertTrue($g->isActive());
        $this->assertSame($p->id, $g->patient_id);
    }

    public function test_grant_is_idempotent(): void
    {
        $p = User::factory()->create();
        $prac = Practitioner::factory()->create();
        $g1 = $this->svc->grant($p, $prac, null, null);
        $g2 = $this->svc->grant($p, $prac, null, null);
        $this->assertSame($g1->id, $g2->id);
        $this->assertSame(1, MedicalRecordGrant::count());
    }

    public function test_revoke_sets_revoked_at(): void
    {
        $p = User::factory()->create();
        $prac = Practitioner::factory()->create();
        $g = $this->svc->grant($p, $prac, null, null);
        $this->svc->revoke($g, $p);
        $this->assertNotNull($g->fresh()->revoked_at);
        $this->assertFalse($g->fresh()->isActive());
    }

    public function test_grant_after_revoke_reactivates_or_creates_new(): void
    {
        $p = User::factory()->create();
        $prac = Practitioner::factory()->create();
        $g1 = $this->svc->grant($p, $prac, null, null);
        $this->svc->revoke($g1, $p);

        $g2 = $this->svc->grant($p, $prac, null, null);
        $this->assertTrue($g2->isActive());
        $this->assertSame(1, MedicalRecordGrant::where('patient_id', $p->id)
            ->whereNull('revoked_at')->count());
    }

    public function test_has_active_grant_true_only_when_not_revoked(): void
    {
        $p = User::factory()->create();
        $prac = Practitioner::factory()->create();
        $this->assertFalse($this->svc->hasActiveGrant($p, $prac));

        $g = $this->svc->grant($p, $prac, null, null);
        $this->assertTrue($this->svc->hasActiveGrant($p, $prac));

        $this->svc->revoke($g, $p);
        $this->assertFalse($this->svc->hasActiveGrant($p, $prac));
    }

    public function test_log_access_persists(): void
    {
        $p = User::factory()->create();
        $prac = Practitioner::factory()->create();
        $procUser = User::factory()->create();
        $g = $this->svc->grant($p, $prac, null, null);

        $req = Request::create('/x', 'GET', server: ['REMOTE_ADDR' => '1.2.3.4', 'HTTP_USER_AGENT' => 'Test']);
        $log = $this->svc->logAccess($g, $procUser, ['allergies', 'medications'], $req);

        $this->assertNotNull($log->id);
        $this->assertSame('1.2.3.4', $log->ip_address);
        $this->assertSame(['allergies', 'medications'], $log->sections_accessed);
        $this->assertSame(1, $g->fresh()->access_count);
        $this->assertNotNull($g->fresh()->last_accessed_at);
    }
}
