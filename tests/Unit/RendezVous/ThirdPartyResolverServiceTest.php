<?php
declare(strict_types=1);

namespace Tests\Unit\RendezVous;

use App\Models\User;
use App\Modules\RendezVous\Services\ThirdPartyResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ThirdPartyResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    private ThirdPartyResolverService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new ThirdPartyResolverService();
    }

    public function test_normalize_phone_local_gabon_format(): void
    {
        $out = $this->svc->normalizePhone('06000001', 'GA');
        $this->assertSame('+24106000001', $out);
    }

    public function test_normalize_phone_already_international(): void
    {
        $out = $this->svc->normalizePhone('+24106000001', 'GA');
        $this->assertSame('+24106000001', $out);
    }

    public function test_normalize_phone_returns_null_on_invalid(): void
    {
        $this->assertNull($this->svc->normalizePhone('abcd', 'GA'));
        $this->assertNull($this->svc->normalizePhone('12', 'GA'));
    }

    public function test_find_user_by_phone_returns_match(): void
    {
        $u = User::factory()->create();
        $u->forceFill(['phone_normalized' => '+24106000002'])->save();
        $found = $this->svc->findUserByPhone('+24106000002');
        $this->assertNotNull($found);
        $this->assertSame($u->id, $found->id);
    }

    public function test_find_user_by_phone_returns_null_for_unknown(): void
    {
        $this->assertNull($this->svc->findUserByPhone('+24199999999'));
    }

    public function test_send_invitation_creates_link(): void
    {
        $inviter = User::factory()->create();
        $apt = $this->createDummyAppointment();
        $link = $this->svc->sendInvitation($inviter, '+24199999999', $apt);
        $this->assertNotNull($link->token);
        $this->assertSame('+24199999999', $link->phone_normalized);
        $this->assertSame('appointment_third_party', $link->context);
        $this->assertSame($apt->id, $link->context_id);
        $this->assertTrue($link->expires_at->isFuture());
    }

    private function createDummyAppointment(): \App\Modules\RendezVous\Models\Appointment
    {
        $hosto = \App\Modules\Annuaire\Models\Hosto::factory()->create();
        $prac = \App\Modules\Annuaire\Models\Practitioner::factory()->create();
        $patient = User::factory()->create();
        $slot = \App\Modules\RendezVous\Models\TimeSlot::create([
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30,
        ]);
        return \App\Modules\RendezVous\Models\Appointment::create([
            'time_slot_id' => $slot->id, 'patient_id' => $patient->id,
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
        ]);
    }
}
