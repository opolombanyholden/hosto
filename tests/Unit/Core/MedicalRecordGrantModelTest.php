<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\MedicalRecordAccessLog;
use App\Modules\Core\Models\MedicalRecordGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MedicalRecordGrantModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_grant_persists(): void
    {
        $patient = User::factory()->create();
        $prac = Practitioner::factory()->create();

        $g = MedicalRecordGrant::create([
            'patient_id' => $patient->id,
            'practitioner_id' => $prac->id,
            'granted_at' => now(),
        ]);

        $this->assertNotNull($g->uuid);
        $this->assertNull($g->revoked_at);
        $this->assertSame(0, $g->access_count);
    }

    public function test_grant_access_log_relation(): void
    {
        $patient = User::factory()->create();
        $prac = Practitioner::factory()->create();
        $procUser = User::factory()->create();

        $g = MedicalRecordGrant::create([
            'patient_id' => $patient->id,
            'practitioner_id' => $prac->id,
            'granted_at' => now(),
        ]);

        MedicalRecordAccessLog::create([
            'grant_id' => $g->id,
            'practitioner_user_id' => $procUser->id,
            'accessed_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $this->assertCount(1, $g->fresh()->accessLogs);
    }
}
