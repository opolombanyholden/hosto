<?php

declare(strict_types=1);

namespace Tests\Feature\Pharma;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use App\Modules\Pharma\Models\Dispensation;
use App\Modules\Pharma\Models\PharmacyStock;
use App\Modules\Pro\Models\Consultation;
use App\Modules\Pro\Models\Prescription;
use App\Modules\Pro\Models\PrescriptionItem;
use App\Modules\Referentiel\Models\Medication;
use App\Modules\Referentiel\Models\ReferenceData;
use App\Modules\Referentiel\Models\StructureType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class PharmacyWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $patient;

    private User $pharmacist;

    private User $otherPharmacist;

    private Hosto $pharmacy;

    private Hosto $otherPharmacy;

    private Prescription $prescription;

    private PharmacyStock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        ReferenceData::firstOrCreate(['category' => 'payment_method', 'code' => 'cash'], ['label_fr' => 'Espèces', 'label_en' => 'Cash']);
        $type = StructureType::firstOrCreate(['slug' => 'pharmacie'], ['name_fr' => 'Pharmacie', 'name_en' => 'Pharmacy']);
        $role = Role::firstOrCreate(['slug' => 'pharmacist'], ['name_fr' => 'Pharmacien', 'name_en' => 'Pharmacist', 'environment' => 'pro']);
        $patientRole = Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);

        $this->pharmacy = Hosto::factory()->create(['name' => 'Pharmacie Test']);
        $this->otherPharmacy = Hosto::factory()->create();
        $this->pharmacy->structureTypes()->attach($type->id);
        $this->otherPharmacy->structureTypes()->attach($type->id);

        $this->pharmacist = $this->staff($role, $this->pharmacy);
        $this->otherPharmacist = $this->staff($role, $this->otherPharmacy);

        $this->patient = User::factory()->create(['medical_pin' => Hash::make('1234'), 'medical_pin_set_at' => now()]);
        $this->patient->roles()->attach($patientRole);

        $practitioner = Practitioner::factory()->create();
        $consultation = Consultation::create([
            'practitioner_id' => $practitioner->id, 'patient_id' => $this->patient->id, 'hosto_id' => $this->pharmacy->id,
            'status' => 'completed', 'motif' => 'Fievre',
        ]);
        $this->prescription = Prescription::create([
            'consultation_id' => $consultation->id, 'practitioner_id' => $practitioner->id, 'patient_id' => $this->patient->id,
            'status' => 'active', 'reference' => Prescription::generateReference(), 'valid_until' => now()->addMonth(),
        ]);
        PrescriptionItem::create([
            'prescription_id' => $this->prescription->id, 'medication_name' => 'Paracetamol 500mg', 'quantity' => 2, 'display_order' => 0,
        ]);

        $medication = Medication::create(['dci' => 'Paracetamol', 'strength' => '500mg', 'dosage_form' => 'comprime', 'is_active' => true]);
        $this->stock = PharmacyStock::create([
            'hosto_id' => $this->pharmacy->id, 'medication_id' => $medication->id, 'quantity_in_stock' => 10,
            'quantity_min_alert' => 3, 'unit_price' => 900, 'currency_code' => 'XAF', 'is_available' => true,
        ]);
    }

    public function test_patient_sends_prescription_to_a_pharmacy(): void
    {
        $order = $this->order();

        $this->assertSame('pending', $order->status);
        $this->assertSame($this->pharmacy->id, $order->hosto_id);
        $this->assertSame(1, $order->items()->count());
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $order->delivery_code);
    }

    public function test_patient_cannot_order_someone_elses_prescription(): void
    {
        $intruder = User::factory()->create(['medical_pin' => Hash::make('1234'), 'medical_pin_set_at' => now()]);
        $intruder->roles()->attach(Role::where('slug', 'patient')->first());

        $this->actingAs($intruder)->withSession(['medical_pin_verified_at' => now()->timestamp])
            ->post("/compte/soins/ordonnances/{$this->prescription->uuid}/commander", ['pharmacy_uuid' => $this->pharmacy->uuid])
            ->assertNotFound();
    }

    public function test_prescription_cannot_be_sent_to_a_non_pharmacy_or_twice(): void
    {
        $clinic = Hosto::factory()->create();

        $this->asPatient()->post("/compte/soins/ordonnances/{$this->prescription->uuid}/commander", ['pharmacy_uuid' => $clinic->uuid])
            ->assertSessionHasErrors('pharmacy_uuid');
        $this->assertSame(0, Dispensation::count());

        $this->order();
        $this->asPatient()->post("/compte/soins/ordonnances/{$this->prescription->uuid}/commander", ['pharmacy_uuid' => $this->otherPharmacy->uuid]);
        $this->assertSame(1, Dispensation::where('prescription_id', $this->prescription->id)->count());
    }

    public function test_expired_prescription_cannot_be_ordered(): void
    {
        $this->prescription->update(['valid_until' => now()->subDay()]);

        $this->asPatient()->post("/compte/soins/ordonnances/{$this->prescription->uuid}/commander", ['pharmacy_uuid' => $this->pharmacy->uuid]);

        $this->assertSame(0, Dispensation::count());
    }

    public function test_pharmacist_sees_only_orders_sent_to_their_pharmacy(): void
    {
        $order = $this->order();

        $this->actingAs($this->pharmacist)->get('/pro/pharmacie')->assertOk()->assertSee($order->reference);
        $this->actingAs($this->otherPharmacist)->get('/pro/pharmacie')->assertOk()->assertDontSee($order->reference);
        $this->actingAs($this->otherPharmacist)->get("/pro/pharmacie/commandes/{$order->uuid}")->assertNotFound();
    }

    public function test_doctor_or_patient_cannot_open_the_pharmacy_space(): void
    {
        $doctor = User::factory()->create();
        $doctor->roles()->attach(Role::firstOrCreate(['slug' => 'doctor'], ['name_fr' => 'Medecin', 'name_en' => 'Doctor', 'environment' => 'pro']));

        $this->actingAs($doctor)->get('/pro/pharmacie')->assertForbidden();
    }

    public function test_preparing_prices_the_order_and_decrements_stock(): void
    {
        $order = $this->order();

        $this->prepare($order)->assertRedirect("/pro/pharmacie/commandes/{$order->uuid}");

        $order->refresh();
        $this->assertSame('dispensed', $order->status);
        $this->assertSame(1800, $order->total_amount);
        $this->assertSame($this->pharmacist->id, $order->dispensed_by_id);
        $this->assertSame(8, $this->stock->refresh()->quantity_in_stock);
        $this->assertSame('dispensed', $this->prescription->refresh()->status);
    }

    public function test_cannot_prepare_more_than_the_stock(): void
    {
        $order = $this->order();

        $this->prepare($order, 11)->assertSessionHasErrors('items');

        $this->assertSame('pending', $order->refresh()->status);
        $this->assertSame(10, $this->stock->refresh()->quantity_in_stock);
    }

    public function test_cannot_prepare_with_another_pharmacys_stock(): void
    {
        $order = $this->order();
        $foreign = PharmacyStock::create([
            'hosto_id' => $this->otherPharmacy->id, 'medication_id' => $this->stock->medication_id,
            'quantity_in_stock' => 50, 'unit_price' => 100, 'currency_code' => 'XAF', 'is_available' => true,
        ]);
        $item = $order->items()->firstOrFail();

        $this->actingAs($this->pharmacist)->post("/pro/pharmacie/commandes/{$order->uuid}/preparer", [
            'items' => [$item->id => ['stock' => $foreign->uuid, 'quantity' => 1]],
        ])->assertSessionHasErrors('items');

        $this->assertSame(50, $foreign->refresh()->quantity_in_stock);
    }

    public function test_delivery_requires_the_patient_secret_code(): void
    {
        $order = $this->order();
        $this->prepare($order);
        RateLimiter::clear("pharma-delivery:{$order->uuid}");
        $code = $order->refresh()->delivery_code;
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->actingAs($this->pharmacist)
            ->post("/pro/pharmacie/commandes/{$order->uuid}/livrer", ['code' => $wrong, 'payment_method' => 'cash'])
            ->assertSessionHasErrors('code');
        $this->assertSame('dispensed', $order->refresh()->status);

        $this->actingAs($this->pharmacist)
            ->post("/pro/pharmacie/commandes/{$order->uuid}/livrer", ['code' => $code, 'payment_method' => 'cash'])
            ->assertRedirect("/pro/pharmacie/commandes/{$order->uuid}");

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertTrue((bool) $order->is_paid);
        $this->assertNotNull($order->delivered_at);
    }

    public function test_delivery_code_attempts_are_limited(): void
    {
        $order = $this->order();
        $this->prepare($order);
        RateLimiter::clear("pharma-delivery:{$order->uuid}");
        $code = $order->refresh()->delivery_code;
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->pharmacist)->post("/pro/pharmacie/commandes/{$order->uuid}/livrer", ['code' => $wrong, 'payment_method' => 'cash']);
        }
        $this->actingAs($this->pharmacist)->post("/pro/pharmacie/commandes/{$order->uuid}/livrer", ['code' => $code, 'payment_method' => 'cash']);

        $this->assertSame('dispensed', $order->refresh()->status);
    }

    public function test_code_is_shown_to_the_patient_but_never_to_the_pharmacist(): void
    {
        $order = $this->order();
        $this->prepare($order);
        $code = $order->refresh()->delivery_code;

        $this->asPatient()->get('/compte/soins')->assertOk()->assertSee($code);
        $this->actingAs($this->pharmacist)->get("/pro/pharmacie/commandes/{$order->uuid}")->assertOk()->assertDontSee($code);
    }

    public function test_patient_space_requires_the_medical_pin(): void
    {
        $this->actingAs($this->patient)->get('/compte/soins')->assertRedirect();
    }

    public function test_pharmacist_updates_own_stock_only(): void
    {
        $this->actingAs($this->pharmacist)->post("/pro/pharmacie/stock/{$this->stock->uuid}", ['quantity_in_stock' => 0, 'unit_price' => 950])
            ->assertRedirect('/pro/pharmacie');
        $this->stock->refresh();
        $this->assertSame(0, $this->stock->quantity_in_stock);
        $this->assertSame(950, $this->stock->unit_price);
        $this->assertFalse((bool) $this->stock->is_available);

        $this->actingAs($this->otherPharmacist)->post("/pro/pharmacie/stock/{$this->stock->uuid}", ['quantity_in_stock' => 99, 'unit_price' => 1])
            ->assertNotFound();
    }

    public function test_a_different_molecule_is_refused_without_a_reason(): void
    {
        $order = $this->order();
        $other = $this->otherMoleculeStock();
        $item = $order->items()->firstOrFail();

        $this->actingAs($this->pharmacist)->post("/pro/pharmacie/commandes/{$order->uuid}/preparer", [
            'items' => [$item->id => ['stock' => $other->uuid, 'quantity' => 1]],
        ])->assertSessionHasErrors('items');

        $this->assertSame('pending', $order->refresh()->status);
        $this->assertSame(10, $other->refresh()->quantity_in_stock);
    }

    public function test_a_justified_substitution_is_recorded_and_shown_to_the_patient(): void
    {
        $order = $this->order();
        $other = $this->otherMoleculeStock();
        $item = $order->items()->firstOrFail();

        $this->actingAs($this->pharmacist)->post("/pro/pharmacie/commandes/{$order->uuid}/preparer", [
            'items' => [$item->id => ['stock' => $other->uuid, 'quantity' => 1, 'substitution_reason' => 'Accord du prescripteur']],
        ])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertTrue((bool) $item->is_substituted);
        $this->assertSame('Accord du prescripteur', $item->substitution_reason);
        $this->assertSame('Amlodipine 5mg', $item->medication_name);

        $this->asPatient()->get('/compte/soins')->assertOk()->assertSee('Amlodipine 5mg')->assertSee('Accord du prescripteur');
    }

    public function test_the_prescribed_product_is_preselected_when_in_stock(): void
    {
        $order = $this->order();
        $this->otherMoleculeStock();

        $this->actingAs($this->pharmacist)->get("/pro/pharmacie/commandes/{$order->uuid}")
            ->assertOk()
            ->assertSee('value="'.$this->stock->uuid.'" selected', false);
    }

    private function staff(Role $role, Hosto $hosto): User
    {
        $user = User::factory()->create();
        UserRoleAssignment::create(['user_id' => $user->id, 'role_id' => $role->id, 'scope_type' => Hosto::class, 'scope_id' => $hosto->id]);

        return $user;
    }

    private function asPatient(): self
    {
        return $this->actingAs($this->patient)->withSession(['medical_pin_verified_at' => now()->timestamp]);
    }

    private function order(): Dispensation
    {
        $this->asPatient()->post("/compte/soins/ordonnances/{$this->prescription->uuid}/commander", [
            'pharmacy_uuid' => $this->pharmacy->uuid,
        ])->assertRedirect('/compte/soins');

        return Dispensation::where('prescription_id', $this->prescription->id)->firstOrFail();
    }

    private function prepare(Dispensation $order, int $quantity = 2): TestResponse
    {
        $item = $order->items()->firstOrFail();

        return $this->actingAs($this->pharmacist)->post("/pro/pharmacie/commandes/{$order->uuid}/preparer", [
            'items' => [$item->id => ['stock' => $this->stock->uuid, 'quantity' => $quantity]],
        ]);
    }

    private function otherMoleculeStock(): PharmacyStock
    {
        $medication = Medication::create(['dci' => 'Amlodipine', 'strength' => '5mg', 'dosage_form' => 'comprime', 'is_active' => true]);

        return PharmacyStock::create([
            'hosto_id' => $this->pharmacy->id, 'medication_id' => $medication->id, 'quantity_in_stock' => 10,
            'unit_price' => 500, 'currency_code' => 'XAF', 'is_available' => true,
        ]);
    }
}
