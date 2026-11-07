<?php

declare(strict_types=1);

namespace Tests\Feature\Compte;

use App\Models\User;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class MedicalPinGateTest extends TestCase
{
    use DatabaseTransactions;

    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);
        $this->patient = User::factory()->create(['medical_pin' => Hash::make('1234'), 'medical_pin_set_at' => now()]);
        $this->patient->roles()->attach($role);
        RateLimiter::clear("medical-pin:{$this->patient->id}");
    }

    public function test_protected_page_sends_the_patient_to_a_real_pin_screen(): void
    {
        $this->actingAs($this->patient)->get('/compte/soins')->assertRedirect('/compte/pin-medical');

        $this->actingAs($this->patient)->get('/compte/pin-medical')->assertOk()->assertSee('name="pin"', false);
    }

    public function test_correct_pin_returns_to_the_requested_page(): void
    {
        $this->actingAs($this->patient)->get('/compte/soins');

        $this->actingAs($this->patient)->post('/compte/pin-medical', ['pin' => '1234'])->assertRedirect('/compte/soins');

        $this->actingAs($this->patient)->get('/compte/soins')->assertOk();
    }

    public function test_wrong_pin_is_refused(): void
    {
        $this->actingAs($this->patient)->post('/compte/pin-medical', ['pin' => '9999'])->assertSessionHasErrors('pin');

        $this->actingAs($this->patient)->get('/compte/soins')->assertRedirect('/compte/pin-medical');
    }

    public function test_pin_attempts_are_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->patient)->post('/compte/pin-medical', ['pin' => '9999']);
        }

        $this->actingAs($this->patient)->post('/compte/pin-medical', ['pin' => '1234'])->assertSessionHasErrors('pin');

        $this->actingAs($this->patient)->get('/compte/soins')->assertRedirect('/compte/pin-medical');
    }

    public function test_patient_without_pin_is_asked_to_create_one(): void
    {
        $this->patient->forceFill(['medical_pin' => null])->save();

        $this->actingAs($this->patient)->get('/compte/soins')->assertRedirect(route('compte.complete-profile'));
    }
}
