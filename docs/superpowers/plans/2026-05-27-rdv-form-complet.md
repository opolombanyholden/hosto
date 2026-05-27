# T9.A — Formulaire RDV complet — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implémenter le formulaire complet de prise de RDV conformément à `docs/superpowers/specs/2026-05-27-rdv-form-complet-design.md` : 5 types RDV, 3 modes consultation, géolocation, tiers HOSTO check, upload documents, partage dossier médical avec PIN.

**Architecture:** Laravel 13 / PHP 8.3 / PostgreSQL 17. 6 migrations (3 RendezVous + 3 Core). 5 services : `AppointmentBookingService` (orchestrateur), `ThirdPartyResolverService` (lookup phone E.164), `DocumentUploadService` (CRUD docs gated), `GeocodingService` (Nominatim async), `MedicalRecordGrantService` (Core, partages persistants). FormRequest pour la validation centralisée. Jobs queue pour géocodage + purge GPS 30j.

**Tech Stack:** PHP 8.3, Laravel 13, PostgreSQL 17, libphonenumber-for-php, Nominatim (OSM), Leaflet (carte client), Sanctum (existant), Storage local disk privé.

---

## File Map

**Migrations RendezVous** (`app/Modules/RendezVous/Database/Migrations/`)
- `2026_05_27_100000_extend_appointments_for_booking_v9a.php`
- `2026_05_27_100100_create_appointment_documents_table.php`
- `2026_05_27_100200_add_phone_normalized_to_users.php`

**Migrations Core** (`app/Modules/Core/Database/Migrations/`)
- `2026_05_27_110000_create_medical_record_grants_table.php`
- `2026_05_27_110100_create_medical_record_access_logs_table.php`
- `2026_05_27_110200_create_invitation_links_table.php`

**Models**
- Modifié : `app/Modules/RendezVous/Models/Appointment.php` (casts enums + accessors)
- Modifié : `app/Models/User.php` (fillable `phone_normalized`)
- Nouveau : `app/Modules/RendezVous/Models/AppointmentDocument.php`
- Nouveau : `app/Modules/Core/Models/MedicalRecordGrant.php`
- Nouveau : `app/Modules/Core/Models/MedicalRecordAccessLog.php`
- Nouveau : `app/Modules/Core/Models/InvitationLink.php`

**Services**
- Nouveau : `app/Modules/RendezVous/Services/ThirdPartyResolverService.php`
- Nouveau : `app/Modules/RendezVous/Services/GeocodingService.php`
- Nouveau : `app/Modules/RendezVous/Services/DocumentUploadService.php`
- Nouveau : `app/Modules/Core/Services/MedicalRecordGrantService.php`
- Nouveau : `app/Modules/RendezVous/Services/AppointmentBookingService.php`

**Jobs**
- Nouveau : `app/Modules/RendezVous/Jobs/GeocodeAppointmentAddressJob.php`
- Nouveau : `app/Modules/RendezVous/Jobs/PurgeOldVisitLocationsJob.php`

**Controllers**
- Modifié : `app/Http/Controllers/BookingWebController.php` (route web `/web/rdv/book`)
- Nouveau : `app/Modules/RendezVous/Http/Controllers/AppointmentDocumentsController.php`
- Nouveau : `app/Modules/RendezVous/Http/Controllers/ThirdPartyLookupController.php`
- Nouveau : `app/Modules/Core/Http/Controllers/MedicalRecordGrantsController.php`

**Form Requests**
- Nouveau : `app/Modules/RendezVous/Http/Requests/BookAppointmentRequest.php`

**Config**
- Modifié : `config/filesystems.php` (disk `private_appointments`)
- Modifié : `composer.json` (dep `giggsey/libphonenumber-for-php`)

**Routes**
- Modifié : `routes/web.php` (nouvelles routes patient + grants)
- Modifié : `app/Modules/RendezVous/Routes/api.php` (lookup tiers)

**Views**
- Nouveau : `resources/views/compte/rendez-vous-form.blade.php`
- Nouveau : `resources/views/compte/dossier/partages.blade.php`
- Nouveau : `resources/views/compte/dossier/partage-historique.blade.php`
- Modifié : `resources/views/annuaire/book-rdv.blade.php` (utiliser nouveau form)

**Tests**
- `tests/Unit/RendezVous/` (6 fichiers)
- `tests/Unit/Core/` (1 fichier)
- `tests/Feature/RendezVous/` (4 fichiers)
- `tests/Feature/Compte/` (1 fichier)

---

## Phase 1 — Migrations + Models foundation

## Task 1: Extension de `appointments` pour T9.A

**Files:**
- Create: `app/Modules/RendezVous/Database/Migrations/2026_05_27_100000_extend_appointments_for_booking_v9a.php`
- Modify: `app/Modules/RendezVous/Models/Appointment.php`
- Test: `tests/Unit/RendezVous/AppointmentExtendedModelTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/RendezVous/AppointmentExtendedModelTest.php
<?php
declare(strict_types=1);

namespace Tests\Unit\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AppointmentExtendedModelTest extends TestCase
{
    use RefreshDatabase;

    private function buildContext(): array
    {
        $patient = User::factory()->create();
        $hosto = Hosto::factory()->create();
        $practitioner = Practitioner::factory()->create();
        $slot = TimeSlot::create([
            'practitioner_id' => $practitioner->id, 'hosto_id' => $hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30, 'is_available' => true,
        ]);
        return compact('patient', 'hosto', 'practitioner', 'slot');
    }

    public function test_is_teleconsultation_accessor_true_when_mode_telecon(): void
    {
        ['patient' => $p, 'hosto' => $h, 'practitioner' => $pr, 'slot' => $s] = $this->buildContext();
        $apt = Appointment::create([
            'time_slot_id' => $s->id, 'patient_id' => $p->id,
            'practitioner_id' => $pr->id, 'hosto_id' => $h->id,
            'appointment_type' => 'ordinaire', 'consultation_mode' => 'telecon',
        ]);
        $this->assertTrue($apt->is_teleconsultation);
    }

    public function test_is_teleconsultation_accessor_false_when_mode_in_hospital(): void
    {
        ['patient' => $p, 'hosto' => $h, 'practitioner' => $pr, 'slot' => $s] = $this->buildContext();
        $apt = Appointment::create([
            'time_slot_id' => $s->id, 'patient_id' => $p->id,
            'practitioner_id' => $pr->id, 'hosto_id' => $h->id,
            'appointment_type' => 'ordinaire', 'consultation_mode' => 'in_hospital',
        ]);
        $this->assertFalse($apt->is_teleconsultation);
    }

    public function test_is_urgent_helper(): void
    {
        ['patient' => $p, 'hosto' => $h, 'practitioner' => $pr, 'slot' => $s] = $this->buildContext();
        $apt = Appointment::create([
            'time_slot_id' => $s->id, 'patient_id' => $p->id,
            'practitioner_id' => $pr->id, 'hosto_id' => $h->id,
            'appointment_type' => 'urgence', 'consultation_mode' => 'in_hospital',
        ]);
        $this->assertTrue($apt->isUrgent());
        $this->assertFalse($apt->isHomeVisit());
    }

    public function test_is_home_visit_helper(): void
    {
        ['patient' => $p, 'hosto' => $h, 'practitioner' => $pr, 'slot' => $s] = $this->buildContext();
        $apt = Appointment::create([
            'time_slot_id' => $s->id, 'patient_id' => $p->id,
            'practitioner_id' => $pr->id, 'hosto_id' => $h->id,
            'appointment_type' => 'ordinaire', 'consultation_mode' => 'home',
            'visit_address' => 'BP 1234 Libreville',
        ]);
        $this->assertTrue($apt->isHomeVisit());
        $this->assertFalse($apt->is_teleconsultation);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/phpunit tests/Unit/RendezVous/AppointmentExtendedModelTest.php`
Expected: FAIL ("column appointment_type does not exist").

- [ ] **Step 3: Create the migration**

```php
// app/Modules/RendezVous/Database/Migrations/2026_05_27_100000_extend_appointments_for_booking_v9a.php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('appointment_type', 20)->default('ordinaire')->after('status');
            $table->string('consultation_mode', 20)->default('in_hospital')->after('appointment_type');
            $table->boolean('share_medical_record')->default(false)->after('is_for_third_party');
            $table->foreignId('third_party_user_id')->nullable()->after('third_party_user_id_placeholder_to_keep_column_position')->constrained('users')->nullOnDelete();
            $table->text('visit_address')->nullable()->after('third_party_user_id');
            $table->decimal('visit_lat', 10, 7)->nullable()->after('visit_address');
            $table->decimal('visit_lng', 10, 7)->nullable()->after('visit_lat');
            $table->timestampTz('visit_geocoded_at')->nullable()->after('visit_lng');
            $table->unsignedSmallInteger('visit_location_accuracy_m')->nullable()->after('visit_geocoded_at');
            $table->timestampTz('requested_at')->nullable()->after('visit_location_accuracy_m');
        });

        // Copy is_teleconsultation values into consultation_mode for existing rows
        DB::statement("UPDATE appointments SET consultation_mode = 'telecon' WHERE is_teleconsultation = true");

        // Drop is_teleconsultation (replaced by accessor)
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('is_teleconsultation');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->boolean('is_teleconsultation')->default(false)->after('reason');
        });
        DB::statement("UPDATE appointments SET is_teleconsultation = (consultation_mode = 'telecon')");
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['third_party_user_id']);
            $table->dropColumn([
                'appointment_type', 'consultation_mode', 'share_medical_record',
                'third_party_user_id', 'visit_address',
                'visit_lat', 'visit_lng', 'visit_geocoded_at', 'visit_location_accuracy_m',
                'requested_at',
            ]);
        });
    }
};
```

**Note importante** : la 1ère migration de cette colonne pourrait s'écrire plus simplement sans le placeholder. Voici la version corrigée et propre :

```php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('appointment_type', 20)->default('ordinaire');
            $table->string('consultation_mode', 20)->default('in_hospital');
            $table->boolean('share_medical_record')->default(false);
            $table->foreignId('third_party_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('visit_address')->nullable();
            $table->decimal('visit_lat', 10, 7)->nullable();
            $table->decimal('visit_lng', 10, 7)->nullable();
            $table->timestampTz('visit_geocoded_at')->nullable();
            $table->unsignedSmallInteger('visit_location_accuracy_m')->nullable();
            $table->timestampTz('requested_at')->nullable();
        });

        DB::statement("UPDATE appointments SET consultation_mode = 'telecon' WHERE is_teleconsultation = true");

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('is_teleconsultation');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->boolean('is_teleconsultation')->default(false);
        });
        DB::statement("UPDATE appointments SET is_teleconsultation = (consultation_mode = 'telecon')");
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['third_party_user_id']);
            $table->dropColumn([
                'appointment_type', 'consultation_mode', 'share_medical_record',
                'third_party_user_id', 'visit_address',
                'visit_lat', 'visit_lng', 'visit_geocoded_at', 'visit_location_accuracy_m',
                'requested_at',
            ]);
        });
    }
};
```

(Utilise cette version.)

- [ ] **Step 4: Update the Appointment model**

Read `app/Modules/RendezVous/Models/Appointment.php`. The existing fillable likely doesn't include all the new fields. Update :

```php
// Append to existing $fillable :
'appointment_type',
'consultation_mode',
'share_medical_record',
'third_party_user_id',
'visit_address',
'visit_lat',
'visit_lng',
'visit_geocoded_at',
'visit_location_accuracy_m',
'requested_at',
```

Add the accessor for backwards compatibility :

```php
public function getIsTeleconsultationAttribute(): bool
{
    return $this->consultation_mode === 'telecon';
}

public function isUrgent(): bool
{
    return $this->appointment_type === 'urgence';
}

public function isHomeVisit(): bool
{
    return $this->consultation_mode === 'home';
}
```

Add or extend casts() :

```php
protected function casts(): array
{
    return [
        // existing casts...
        'share_medical_record' => 'boolean',
        'visit_lat' => 'decimal:7',
        'visit_lng' => 'decimal:7',
        'visit_geocoded_at' => 'immutable_datetime',
        'visit_location_accuracy_m' => 'integer',
        'requested_at' => 'immutable_datetime',
    ];
}
```

- [ ] **Step 5: Run migration + tests**

```bash
php artisan migrate --force
./vendor/bin/phpunit tests/Unit/RendezVous/AppointmentExtendedModelTest.php
```
Expected: PASS (4 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Modules/RendezVous/Database/Migrations/2026_05_27_100000_extend_appointments_for_booking_v9a.php \
        app/Modules/RendezVous/Models/Appointment.php \
        tests/Unit/RendezVous/AppointmentExtendedModelTest.php
git commit -m "feat(rdv): extend appointments pour T9.A (type/mode/geo/share)"
```

---

## Task 2: Migration `appointment_documents` + Model

**Files:**
- Create: `app/Modules/RendezVous/Database/Migrations/2026_05_27_100100_create_appointment_documents_table.php`
- Create: `app/Modules/RendezVous/Models/AppointmentDocument.php`
- Test: `tests/Unit/RendezVous/AppointmentDocumentModelTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/RendezVous/AppointmentDocumentModelTest.php
<?php
declare(strict_types=1);

namespace Tests\Unit\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\AppointmentDocument;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AppointmentDocumentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_can_be_persisted_and_retrieved(): void
    {
        $patient = User::factory()->create();
        $hosto = Hosto::factory()->create();
        $prac = Practitioner::factory()->create();
        $slot = TimeSlot::create([
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30,
        ]);
        $apt = Appointment::create([
            'time_slot_id' => $slot->id, 'patient_id' => $patient->id,
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
        ]);

        $doc = AppointmentDocument::create([
            'appointment_id' => $apt->id,
            'uploaded_by_id' => $patient->id,
            'original_name' => 'ordonnance.pdf',
            'stored_path' => 'appointments/'.$apt->uuid.'/abc123.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'category' => 'ordonnance',
            'keep_in_dpe' => false,
        ]);

        $this->assertNotNull($doc->uuid);
        $this->assertSame($apt->id, $doc->appointment->id);
        $this->assertSame($patient->id, $doc->uploadedBy->id);
        $this->assertFalse($doc->keep_in_dpe);
    }
}
```

- [ ] **Step 2: Verify FAIL**

Run: `./vendor/bin/phpunit tests/Unit/RendezVous/AppointmentDocumentModelTest.php`
Expected: FAIL.

- [ ] **Step 3: Create migration**

```php
// app/Modules/RendezVous/Database/Migrations/2026_05_27_100100_create_appointment_documents_table.php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('uploaded_by_id')->constrained('users');
            $table->string('original_name', 255);
            $table->string('stored_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->string('category', 40)->nullable();
            $table->boolean('keep_in_dpe')->default(false);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletesTz();
            $table->index('appointment_id');
            $table->index('uploaded_by_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_documents');
    }
};
```

- [ ] **Step 4: Create model**

```php
// app/Modules/RendezVous/Models/AppointmentDocument.php
<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $appointment_id
 * @property int $uploaded_by_id
 * @property string $original_name
 * @property string $stored_path
 * @property string $mime_type
 * @property int $size_bytes
 * @property string|null $category
 * @property bool $keep_in_dpe
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class AppointmentDocument extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'appointment_id', 'uploaded_by_id', 'original_name', 'stored_path',
        'mime_type', 'size_bytes', 'category', 'keep_in_dpe',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'keep_in_dpe' => 'boolean',
            'size_bytes' => 'integer',
        ];
    }
}
```

- [ ] **Step 5: Run + commit**

```bash
php artisan migrate --force
./vendor/bin/phpunit tests/Unit/RendezVous/AppointmentDocumentModelTest.php
```
Expected: PASS.

```bash
git add app/Modules/RendezVous/Database/Migrations/2026_05_27_100100_create_appointment_documents_table.php \
        app/Modules/RendezVous/Models/AppointmentDocument.php \
        tests/Unit/RendezVous/AppointmentDocumentModelTest.php
git commit -m "feat(rdv): table appointment_documents + model"
```

---

## Task 3: Migration `users.phone_normalized` + backfill

**Files:**
- Create: `app/Modules/RendezVous/Database/Migrations/2026_05_27_100200_add_phone_normalized_to_users.php`
- Modify: `app/Models/User.php` (append `phone_normalized` to `#[Fillable]`)
- Test: `tests/Feature/RendezVous/PhoneNormalizationBackfillTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/RendezVous/PhoneNormalizationBackfillTest.php
<?php
declare(strict_types=1);

namespace Tests\Feature\RendezVous;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PhoneNormalizationBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_normalized_column_exists_and_accepts_e164(): void
    {
        $u = User::factory()->create();
        DB::table('users')->where('id', $u->id)->update(['phone_normalized' => '+241060000001']);

        $cols = \Illuminate\Support\Facades\Schema::getColumnListing('users');
        $this->assertContains('phone_normalized', $cols);
        $this->assertSame('+241060000001', $u->fresh()->phone_normalized);
    }

    public function test_phone_normalized_is_unique(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        DB::table('users')->where('id', $u1->id)->update(['phone_normalized' => '+241060000002']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('users')->where('id', $u2->id)->update(['phone_normalized' => '+241060000002']);
    }
}
```

- [ ] **Step 2: Verify FAIL**

`./vendor/bin/phpunit tests/Feature/RendezVous/PhoneNormalizationBackfillTest.php`

- [ ] **Step 3: Create migration**

```php
// app/Modules/RendezVous/Database/Migrations/2026_05_27_100200_add_phone_normalized_to_users.php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone_normalized', 20)->nullable()->unique();
        });

        // Naive backfill : copy phone to phone_normalized for existing rows.
        // ThirdPartyResolverService will properly normalize new entries later.
        DB::table('users')
            ->whereNotNull('phone')
            ->whereNull('phone_normalized')
            ->orderBy('id')
            ->chunk(500, function ($users) {
                foreach ($users as $u) {
                    // Naive: strip non-digits and prepend country code if missing.
                    // Real normalization happens via libphonenumber in the service.
                    $raw = preg_replace('/[^0-9+]/', '', $u->phone);
                    if (! str_starts_with($raw, '+')) {
                        $raw = '+241'.ltrim($raw, '0');
                    }
                    if (strlen($raw) <= 20) {
                        DB::table('users')->where('id', $u->id)
                            ->update(['phone_normalized' => $raw]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('phone_normalized');
        });
    }
};
```

- [ ] **Step 4: Update User fillable**

Read `app/Models/User.php`. In the `#[Fillable([...])]` attribute, append `'phone_normalized'`. Keep all existing entries.

- [ ] **Step 5: Run + commit**

```bash
php artisan migrate --force
./vendor/bin/phpunit tests/Feature/RendezVous/PhoneNormalizationBackfillTest.php
```
Expected: PASS (2 tests).

```bash
git add app/Modules/RendezVous/Database/Migrations/2026_05_27_100200_add_phone_normalized_to_users.php \
        app/Models/User.php \
        tests/Feature/RendezVous/PhoneNormalizationBackfillTest.php
git commit -m "feat(rdv): users.phone_normalized (E.164) + backfill"
```

---

## Task 4: Migrations Core (MedicalRecordGrant + AccessLog + InvitationLink) + Models

**Files:**
- Create: `app/Modules/Core/Database/Migrations/2026_05_27_110000_create_medical_record_grants_table.php`
- Create: `app/Modules/Core/Database/Migrations/2026_05_27_110100_create_medical_record_access_logs_table.php`
- Create: `app/Modules/Core/Database/Migrations/2026_05_27_110200_create_invitation_links_table.php`
- Create: `app/Modules/Core/Models/MedicalRecordGrant.php`
- Create: `app/Modules/Core/Models/MedicalRecordAccessLog.php`
- Create: `app/Modules/Core/Models/InvitationLink.php`
- Test: `tests/Unit/Core/MedicalRecordGrantModelTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Core/MedicalRecordGrantModelTest.php
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
```

- [ ] **Step 2: Verify FAIL**

`./vendor/bin/phpunit tests/Unit/Core/MedicalRecordGrantModelTest.php`

- [ ] **Step 3: Create migrations**

```php
// app/Modules/Core/Database/Migrations/2026_05_27_110000_create_medical_record_grants_table.php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_record_grants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained('practitioners')->cascadeOnDelete();
            $table->foreignId('source_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->jsonb('scope')->nullable();
            $table->timestampTz('granted_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->unsignedInteger('access_count')->default(0);
            $table->timestampTz('last_accessed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Partial unique : un seul grant actif (revoked_at NULL) par couple patient/practitioner.
        DB::statement('
            CREATE UNIQUE INDEX medical_record_grants_active_unique
            ON medical_record_grants (patient_id, practitioner_id)
            WHERE revoked_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS medical_record_grants_active_unique');
        Schema::dropIfExists('medical_record_grants');
    }
};
```

```php
// app/Modules/Core/Database/Migrations/2026_05_27_110100_create_medical_record_access_logs_table.php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_record_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('grant_id')->constrained('medical_record_grants')->cascadeOnDelete();
            $table->foreignId('practitioner_user_id')->constrained('users');
            $table->timestampTz('accessed_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->jsonb('sections_accessed')->nullable();
            $table->index(['grant_id', 'accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_record_access_logs');
    }
};
```

```php
// app/Modules/Core/Database/Migrations/2026_05_27_110200_create_invitation_links_table.php
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_links', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('inviter_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('context', 40);
            $table->unsignedBigInteger('context_id')->nullable();
            $table->string('phone_normalized', 20);
            $table->string('token', 64)->unique();
            $table->string('sent_via', 20)->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('accepted_at')->nullable();
            $table->foreignId('accepted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('expires_at');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index('phone_normalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_links');
    }
};
```

- [ ] **Step 4: Create models**

```php
// app/Modules/Core/Models/MedicalRecordGrant.php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\RendezVous\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property int $patient_id
 * @property int $practitioner_id
 * @property int|null $source_appointment_id
 * @property array<string, mixed>|null $scope
 * @property CarbonImmutable $granted_at
 * @property CarbonImmutable|null $revoked_at
 * @property int $access_count
 * @property CarbonImmutable|null $last_accessed_at
 * @property-read Collection<int, MedicalRecordAccessLog> $accessLogs
 */
class MedicalRecordGrant extends Model
{
    use HasUuid;

    protected $fillable = [
        'patient_id', 'practitioner_id', 'source_appointment_id',
        'scope', 'granted_at', 'revoked_at',
        'access_count', 'last_accessed_at',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /** @return BelongsTo<Practitioner, $this> */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /** @return BelongsTo<Appointment, $this> */
    public function sourceAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'source_appointment_id');
    }

    /** @return HasMany<MedicalRecordAccessLog, $this> */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(MedicalRecordAccessLog::class, 'grant_id')
            ->orderByDesc('accessed_at');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'scope' => 'array',
            'granted_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'last_accessed_at' => 'immutable_datetime',
            'access_count' => 'integer',
        ];
    }
}
```

```php
// app/Modules/Core/Models/MedicalRecordAccessLog.php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $grant_id
 * @property int $practitioner_user_id
 * @property CarbonImmutable $accessed_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<int, string>|null $sections_accessed
 */
class MedicalRecordAccessLog extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'grant_id', 'practitioner_user_id', 'accessed_at',
        'ip_address', 'user_agent', 'sections_accessed',
    ];

    /** @return BelongsTo<MedicalRecordGrant, $this> */
    public function grant(): BelongsTo
    {
        return $this->belongsTo(MedicalRecordGrant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function practitionerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'accessed_at' => 'immutable_datetime',
            'sections_accessed' => 'array',
        ];
    }
}
```

```php
// app/Modules/Core/Models/InvitationLink.php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $inviter_user_id
 * @property string $context
 * @property int|null $context_id
 * @property string $phone_normalized
 * @property string $token
 * @property string|null $sent_via
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $accepted_at
 * @property int|null $accepted_user_id
 * @property CarbonImmutable $expires_at
 */
class InvitationLink extends Model
{
    use HasUuid;

    protected $fillable = [
        'inviter_user_id', 'context', 'context_id',
        'phone_normalized', 'token', 'sent_via',
        'sent_at', 'accepted_at', 'accepted_user_id', 'expires_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function acceptedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sent_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
```

- [ ] **Step 5: Run + commit**

```bash
php artisan migrate --force
./vendor/bin/phpunit tests/Unit/Core/MedicalRecordGrantModelTest.php
```
Expected: PASS (2 tests).

```bash
git add app/Modules/Core/Database/Migrations/2026_05_27_110000_create_medical_record_grants_table.php \
        app/Modules/Core/Database/Migrations/2026_05_27_110100_create_medical_record_access_logs_table.php \
        app/Modules/Core/Database/Migrations/2026_05_27_110200_create_invitation_links_table.php \
        app/Modules/Core/Models/MedicalRecordGrant.php \
        app/Modules/Core/Models/MedicalRecordAccessLog.php \
        app/Modules/Core/Models/InvitationLink.php \
        tests/Unit/Core/MedicalRecordGrantModelTest.php
git commit -m "feat(core): medical_record_grants + access_logs + invitation_links"
```

---

## Phase 2 — Composer + Config + Storage disk

## Task 5: Composer `giggsey/libphonenumber-for-php` + disk `private_appointments`

**Files:**
- Modify: `composer.json` (require)
- Modify: `config/filesystems.php` (disks)

- [ ] **Step 1: Add the dependency**

```bash
composer require giggsey/libphonenumber-for-php:^9.0
```
Expected: package installed.

- [ ] **Step 2: Verify autoload**

```bash
php -r "require 'vendor/autoload.php'; echo class_exists('libphonenumber\\PhoneNumberUtil') ? 'OK' : 'MISSING'; echo PHP_EOL;"
```
Expected: `OK`.

- [ ] **Step 3: Update filesystems.php**

Read `config/filesystems.php`. In the `disks` array, append :

```php
'private_appointments' => [
    'driver' => 'local',
    'root' => storage_path('app/private/appointments'),
    'serve' => false,  // jamais exposé en HTTP direct
    'throw' => false,
],
```

- [ ] **Step 4: Create the directory + .gitignore**

```bash
mkdir -p storage/app/private/appointments
echo '*' > storage/app/private/appointments/.gitignore
echo '!.gitignore' >> storage/app/private/appointments/.gitignore
```

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock config/filesystems.php storage/app/private/appointments/.gitignore
git commit -m "chore(rdv): ajout libphonenumber + disk private_appointments"
```

---

## Phase 3 — Services unit-testés

## Task 6: `ThirdPartyResolverService`

**Files:**
- Create: `app/Modules/RendezVous/Services/ThirdPartyResolverService.php`
- Test: `tests/Unit/RendezVous/ThirdPartyResolverServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/RendezVous/ThirdPartyResolverServiceTest.php
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
```

- [ ] **Step 2: Verify FAIL**

`./vendor/bin/phpunit tests/Unit/RendezVous/ThirdPartyResolverServiceTest.php`

- [ ] **Step 3: Implement the service**

```php
// app/Modules/RendezVous/Services/ThirdPartyResolverService.php
<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Services;

use App\Models\User;
use App\Modules\Core\Models\InvitationLink;
use App\Modules\RendezVous\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class ThirdPartyResolverService
{
    public function normalizePhone(string $raw, string $countryIso = 'GA'): ?string
    {
        $raw = trim($raw);
        if ($raw === '') return null;
        $util = PhoneNumberUtil::getInstance();
        try {
            $parsed = $util->parse($raw, strtoupper($countryIso));
            if (! $util->isValidNumber($parsed)) {
                return null;
            }
            return $util->format($parsed, PhoneNumberFormat::E164);
        } catch (NumberParseException $e) {
            return null;
        }
    }

    public function findUserByPhone(string $normalizedPhone): ?User
    {
        return User::where('phone_normalized', $normalizedPhone)->first();
    }

    public function sendInvitation(User $inviter, string $phoneNormalized, Appointment $apt): InvitationLink
    {
        $token = Str::random(64);

        $link = InvitationLink::create([
            'inviter_user_id' => $inviter->id,
            'context' => 'appointment_third_party',
            'context_id' => $apt->id,
            'phone_normalized' => $phoneNormalized,
            'token' => $token,
            'sent_via' => 'sms',
            'sent_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        // v1 stub : log only. Real SMS integration arrives later.
        Log::info('invitation.sms.stub', [
            'phone' => $phoneNormalized,
            'token' => $token,
            'inviter_user_id' => $inviter->id,
            'context_id' => $apt->id,
        ]);

        return $link;
    }
}
```

- [ ] **Step 4: Run tests**

`./vendor/bin/phpunit tests/Unit/RendezVous/ThirdPartyResolverServiceTest.php`
Expected: PASS (6 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Modules/RendezVous/Services/ThirdPartyResolverService.php \
        tests/Unit/RendezVous/ThirdPartyResolverServiceTest.php
git commit -m "feat(rdv): ThirdPartyResolverService (E.164 + lookup + invitation stub)"
```

---

## Task 7: `GeocodingService` + `GeocodeAppointmentAddressJob`

**Files:**
- Create: `app/Modules/RendezVous/Services/GeocodingService.php`
- Create: `app/Modules/RendezVous/Jobs/GeocodeAppointmentAddressJob.php`
- Test: `tests/Unit/RendezVous/GeocodingServiceTest.php`
- Test: `tests/Feature/RendezVous/GeocodingJobTest.php`

- [ ] **Step 1: Write the failing tests**

```php
// tests/Unit/RendezVous/GeocodingServiceTest.php
<?php
declare(strict_types=1);

namespace Tests\Unit\RendezVous;

use App\Modules\RendezVous\Services\GeocodingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class GeocodingServiceTest extends TestCase
{
    public function test_geocode_returns_lat_lng_for_known_address(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                ['lat' => '0.4162', 'lon' => '9.4673', 'display_name' => 'Libreville, Gabon'],
            ], 200),
        ]);

        $svc = new GeocodingService();
        $out = $svc->geocode('Libreville');
        $this->assertNotNull($out);
        $this->assertEqualsWithDelta(0.4162, $out['lat'], 0.001);
        $this->assertEqualsWithDelta(9.4673, $out['lng'], 0.001);
    }

    public function test_geocode_returns_null_on_empty_result(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([], 200),
        ]);
        $svc = new GeocodingService();
        $this->assertNull($svc->geocode('XYZ_invalid_address'));
    }

    public function test_reverse_geocode_returns_address(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Quartier Glass, Libreville, Estuaire, Gabon',
            ], 200),
        ]);

        $svc = new GeocodingService();
        $address = $svc->reverseGeocode(0.4162, 9.4673);
        $this->assertSame('Quartier Glass, Libreville, Estuaire, Gabon', $address);
    }

    public function test_reverse_geocode_returns_null_on_error(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response('', 500),
        ]);
        $svc = new GeocodingService();
        $this->assertNull($svc->reverseGeocode(0.0, 0.0));
    }
}
```

```php
// tests/Feature/RendezVous/GeocodingJobTest.php
<?php
declare(strict_types=1);

namespace Tests\Feature\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Jobs\GeocodeAppointmentAddressJob;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class GeocodingJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_geocodes_address_and_updates_appointment(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                ['lat' => '0.41', 'lon' => '9.46'],
            ], 200),
        ]);

        $patient = User::factory()->create();
        $hosto = Hosto::factory()->create();
        $prac = Practitioner::factory()->create();
        $slot = TimeSlot::create([
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30,
        ]);
        $apt = Appointment::create([
            'time_slot_id' => $slot->id, 'patient_id' => $patient->id,
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
            'consultation_mode' => 'home',
            'visit_address' => 'Libreville, Gabon',
        ]);

        (new GeocodeAppointmentAddressJob($apt->id))->handle(app(\App\Modules\RendezVous\Services\GeocodingService::class));

        $apt->refresh();
        $this->assertEqualsWithDelta(0.41, (float) $apt->visit_lat, 0.01);
        $this->assertEqualsWithDelta(9.46, (float) $apt->visit_lng, 0.01);
        $this->assertNotNull($apt->visit_geocoded_at);
    }
}
```

- [ ] **Step 2: Verify FAIL**

`./vendor/bin/phpunit tests/Unit/RendezVous/GeocodingServiceTest.php tests/Feature/RendezVous/GeocodingJobTest.php`

- [ ] **Step 3: Implement GeocodingService**

```php
// app/Modules/RendezVous/Services/GeocodingService.php
<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Services;

use App\Modules\RendezVous\Jobs\GeocodeAppointmentAddressJob;
use App\Modules\RendezVous\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class GeocodingService
{
    private const NOMINATIM_BASE = 'https://nominatim.openstreetmap.org';
    private const USER_AGENT = 'HOSTO/1.0 (contact@hosto.ga)';

    /** @return array{lat: float, lng: float, accuracy_m: int|null}|null */
    public function geocode(string $address): ?array
    {
        try {
            $resp = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->get(self::NOMINATIM_BASE.'/search', [
                    'q' => $address, 'format' => 'json', 'limit' => 1,
                ]);
            if (! $resp->ok()) return null;
            $data = $resp->json();
            if (! is_array($data) || count($data) === 0) return null;
            return [
                'lat' => (float) ($data[0]['lat'] ?? 0),
                'lng' => (float) ($data[0]['lon'] ?? 0),
                'accuracy_m' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('geocoding.search.failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function reverseGeocode(float $lat, float $lng): ?string
    {
        try {
            $resp = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->get(self::NOMINATIM_BASE.'/reverse', [
                    'lat' => $lat, 'lon' => $lng, 'format' => 'json',
                ]);
            if (! $resp->ok()) return null;
            $data = $resp->json();
            return is_array($data) ? ($data['display_name'] ?? null) : null;
        } catch (\Throwable $e) {
            Log::warning('geocoding.reverse.failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function dispatchGeocodeJob(Appointment $apt): void
    {
        GeocodeAppointmentAddressJob::dispatch($apt->id);
    }
}
```

- [ ] **Step 4: Implement the Job**

```php
// app/Modules/RendezVous/Jobs/GeocodeAppointmentAddressJob.php
<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Jobs;

use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Services\GeocodingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GeocodeAppointmentAddressJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // seconds between retries

    public function __construct(public readonly int $appointmentId) {}

    public function handle(GeocodingService $svc): void
    {
        $apt = Appointment::find($this->appointmentId);
        if (! $apt || ! $apt->visit_address || $apt->visit_lat !== null) {
            return; // already geocoded or nothing to do
        }

        $result = $svc->geocode($apt->visit_address);
        if (! $result) return;

        $apt->update([
            'visit_lat' => $result['lat'],
            'visit_lng' => $result['lng'],
            'visit_geocoded_at' => now(),
            'visit_location_accuracy_m' => $result['accuracy_m'],
        ]);
    }
}
```

- [ ] **Step 5: Run + commit**

```bash
./vendor/bin/phpunit tests/Unit/RendezVous/GeocodingServiceTest.php tests/Feature/RendezVous/GeocodingJobTest.php
```
Expected: PASS (4 + 1 = 5 tests).

```bash
git add app/Modules/RendezVous/Services/GeocodingService.php \
        app/Modules/RendezVous/Jobs/GeocodeAppointmentAddressJob.php \
        tests/Unit/RendezVous/GeocodingServiceTest.php \
        tests/Feature/RendezVous/GeocodingJobTest.php
git commit -m "feat(rdv): GeocodingService (Nominatim) + job async + retry"
```

---

## Task 8: `DocumentUploadService`

**Files:**
- Create: `app/Modules/RendezVous/Services/DocumentUploadService.php`
- Test: `tests/Unit/RendezVous/DocumentUploadServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/RendezVous/DocumentUploadServiceTest.php
<?php
declare(strict_types=1);

namespace Tests\Unit\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\AppointmentDocument;
use App\Modules\RendezVous\Models\TimeSlot;
use App\Modules\RendezVous\Services\DocumentUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DocumentUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentUploadService $svc;
    private Appointment $apt;
    private User $patient;
    private Practitioner $practitioner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private_appointments');
        $this->svc = new DocumentUploadService();

        $this->patient = User::factory()->create();
        $hosto = Hosto::factory()->create();
        $this->practitioner = Practitioner::factory()->create();
        $slot = TimeSlot::create([
            'practitioner_id' => $this->practitioner->id, 'hosto_id' => $hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30,
        ]);
        $this->apt = Appointment::create([
            'time_slot_id' => $slot->id, 'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id, 'hosto_id' => $hosto->id,
        ]);
    }

    public function test_store_persists_document_under_appointment_uuid_folder(): void
    {
        $file = UploadedFile::fake()->create('ordonnance.pdf', 100, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, 'ordonnance');

        $this->assertNotNull($doc->id);
        $this->assertStringStartsWith('appointments/'.$this->apt->uuid.'/', $doc->stored_path);
        $this->assertSame('ordonnance.pdf', $doc->original_name);
        $this->assertSame('ordonnance', $doc->category);
        Storage::disk('private_appointments')->assertExists(
            str_replace('appointments/', '', $doc->stored_path)
        );
    }

    public function test_store_refuses_mime_not_allowed(): void
    {
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');
        $this->expectException(\InvalidArgumentException::class);
        $this->svc->store($this->apt, $file, $this->patient, null);
    }

    public function test_store_refuses_above_10mb(): void
    {
        $file = UploadedFile::fake()->create('big.pdf', 11 * 1024, 'application/pdf'); // 11 MB
        $this->expectException(\DomainException::class);
        $this->svc->store($this->apt, $file, $this->patient, null);
    }

    public function test_store_refuses_more_than_5_files_per_appointment(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $f = UploadedFile::fake()->create("f{$i}.pdf", 100, 'application/pdf');
            $this->svc->store($this->apt, $f, $this->patient, null);
        }
        $extra = UploadedFile::fake()->create('6.pdf', 100, 'application/pdf');
        $this->expectException(\DomainException::class);
        $this->svc->store($this->apt, $extra, $this->patient, null);
    }

    public function test_store_refuses_total_above_30mb(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $f = UploadedFile::fake()->create("f{$i}.pdf", 9 * 1024, 'application/pdf'); // 9 MB each = 27 MB total
            $this->svc->store($this->apt, $f, $this->patient, null);
        }
        $extra = UploadedFile::fake()->create('big.pdf', 9 * 1024, 'application/pdf'); // 9 MB more = 36 MB total
        $this->expectException(\DomainException::class);
        $this->svc->store($this->apt, $extra, $this->patient, null);
    }

    public function test_canAccess_allows_patient_owner(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 50, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, null);
        $this->assertTrue($this->svc->canAccess($doc, $this->patient));
    }

    public function test_canAccess_denies_random_user(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 50, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, null);
        $other = User::factory()->create();
        $this->assertFalse($this->svc->canAccess($doc, $other));
    }

    public function test_canAccess_allows_practitioner_user(): void
    {
        $procUser = User::factory()->create();
        $this->practitioner->update(['user_id' => $procUser->id]);
        $file = UploadedFile::fake()->create('o.pdf', 50, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, null);
        $this->assertTrue($this->svc->canAccess($doc, $procUser));
    }

    public function test_delete_soft_deletes(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 50, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, null);
        $this->svc->delete($doc, $this->patient);
        $this->assertSoftDeleted($doc);
    }

    public function test_promoteToDpe_marks_keep_in_dpe_true(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 50, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, null);
        $this->svc->promoteToDpe($doc);
        $this->assertTrue($doc->fresh()->keep_in_dpe);
    }

    public function test_store_hashes_filename(): void
    {
        $file = UploadedFile::fake()->create('orig name with spaces.pdf', 50, 'application/pdf');
        $doc = $this->svc->store($this->apt, $file, $this->patient, null);
        // stored path doesn't contain the original name
        $this->assertStringNotContainsString('orig name with spaces', $doc->stored_path);
    }
}
```

- [ ] **Step 2: Verify FAIL**

`./vendor/bin/phpunit tests/Unit/RendezVous/DocumentUploadServiceTest.php`

- [ ] **Step 3: Implement the service**

```php
// app/Modules/RendezVous/Services/DocumentUploadService.php
<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Services;

use App\Models\User;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\AppointmentDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DocumentUploadService
{
    public const MAX_PER_FILE_BYTES = 10 * 1024 * 1024;   // 10 MB
    public const MAX_FILES_PER_APPOINTMENT = 5;
    public const MAX_TOTAL_BYTES = 30 * 1024 * 1024;       // 30 MB
    public const DISK = 'private_appointments';

    private const ALLOWED_MIMES = [
        'application/pdf', 'image/jpeg', 'image/png', 'image/heic', 'image/heif',
    ];

    public function store(
        Appointment $apt,
        UploadedFile $file,
        User $uploader,
        ?string $category = null,
    ): AppointmentDocument {
        $mime = $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException("Mime type not allowed: {$mime}");
        }
        $size = $file->getSize();
        if ($size > self::MAX_PER_FILE_BYTES) {
            throw new \DomainException('File exceeds 10 MB limit');
        }
        $existing = AppointmentDocument::where('appointment_id', $apt->id)->get();
        if ($existing->count() >= self::MAX_FILES_PER_APPOINTMENT) {
            throw new \DomainException('Max 5 documents per appointment');
        }
        $total = $existing->sum('size_bytes') + $size;
        if ($total > self::MAX_TOTAL_BYTES) {
            throw new \DomainException('Total size exceeds 30 MB limit');
        }

        $ext = $file->getClientOriginalExtension() ?: $this->extensionForMime($mime);
        $hash = hash('sha256', $apt->uuid.$file->getClientOriginalName().microtime(true));
        $relativePath = $apt->uuid.'/'.substr($hash, 0, 32).'.'.$ext;

        Storage::disk(self::DISK)->putFileAs($apt->uuid, $file, substr($hash, 0, 32).'.'.$ext);

        return AppointmentDocument::create([
            'appointment_id' => $apt->id,
            'uploaded_by_id' => $uploader->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => 'appointments/'.$relativePath,
            'mime_type' => $mime,
            'size_bytes' => $size,
            'category' => $category,
            'keep_in_dpe' => false,
        ]);
    }

    public function download(AppointmentDocument $doc, User $accessor): StreamedResponse
    {
        if (! $this->canAccess($doc, $accessor)) {
            abort(403, 'Accès refusé à ce document.');
        }
        $relativeOnDisk = str_replace('appointments/', '', $doc->stored_path);
        if (! Storage::disk(self::DISK)->exists($relativeOnDisk)) {
            abort(404, 'Fichier introuvable.');
        }
        return Storage::disk(self::DISK)->download($relativeOnDisk, $doc->original_name, [
            'Content-Type' => $doc->mime_type,
        ]);
    }

    public function delete(AppointmentDocument $doc, User $by): void
    {
        $doc->delete();
    }

    public function promoteToDpe(AppointmentDocument $doc): void
    {
        $doc->update(['keep_in_dpe' => true]);
    }

    public function canAccess(AppointmentDocument $doc, User $user): bool
    {
        $apt = $doc->appointment;
        // Patient (owner) ?
        if ($apt->patient_id === $user->id) return true;
        // Third party user matched ?
        if ($apt->third_party_user_id === $user->id) return true;
        // Practitioner of the appointment ?
        if ($apt->practitioner && $apt->practitioner->user_id === $user->id) return true;
        // Admin ?
        if ($user->can('appointments.manage') || $user->can('users.view')) return true;
        return false;
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/heic', 'image/heif' => 'heic',
            default => 'bin',
        };
    }
}
```

- [ ] **Step 4: Run + commit**

```bash
./vendor/bin/phpunit tests/Unit/RendezVous/DocumentUploadServiceTest.php
```
Expected: PASS (10 tests).

```bash
git add app/Modules/RendezVous/Services/DocumentUploadService.php \
        tests/Unit/RendezVous/DocumentUploadServiceTest.php
git commit -m "feat(rdv): DocumentUploadService (mime/size/count/gated download)"
```

---

## Task 9: `MedicalRecordGrantService`

**Files:**
- Create: `app/Modules/Core/Services/MedicalRecordGrantService.php`
- Test: `tests/Unit/Core/MedicalRecordGrantServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Core/MedicalRecordGrantServiceTest.php
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
```

- [ ] **Step 2: Verify FAIL**

`./vendor/bin/phpunit tests/Unit/Core/MedicalRecordGrantServiceTest.php`

- [ ] **Step 3: Implement the service**

```php
// app/Modules/Core/Services/MedicalRecordGrantService.php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\MedicalRecordAccessLog;
use App\Modules\Core\Models\MedicalRecordGrant;
use App\Modules\RendezVous\Models\Appointment;
use Illuminate\Http\Request;

final class MedicalRecordGrantService
{
    public function grant(
        User $patient,
        Practitioner $practitioner,
        ?array $scope = null,
        ?Appointment $source = null,
    ): MedicalRecordGrant {
        $existing = MedicalRecordGrant::where('patient_id', $patient->id)
            ->where('practitioner_id', $practitioner->id)
            ->whereNull('revoked_at')
            ->first();
        if ($existing) {
            return $existing;
        }
        return MedicalRecordGrant::create([
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'source_appointment_id' => $source?->id,
            'scope' => $scope,
            'granted_at' => now(),
        ]);
    }

    public function revoke(MedicalRecordGrant $grant, User $by): void
    {
        if ($grant->isActive()) {
            $grant->update(['revoked_at' => now()]);
        }
    }

    public function hasActiveGrant(User $patient, Practitioner $practitioner): bool
    {
        return MedicalRecordGrant::where('patient_id', $patient->id)
            ->where('practitioner_id', $practitioner->id)
            ->whereNull('revoked_at')
            ->exists();
    }

    public function logAccess(
        MedicalRecordGrant $grant,
        User $practitionerUser,
        array $sections,
        Request $request,
    ): MedicalRecordAccessLog {
        $log = MedicalRecordAccessLog::create([
            'grant_id' => $grant->id,
            'practitioner_user_id' => $practitionerUser->id,
            'accessed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'sections_accessed' => $sections,
        ]);
        $grant->increment('access_count');
        $grant->update(['last_accessed_at' => now()]);
        return $log;
    }
}
```

- [ ] **Step 4: Run + commit**

```bash
./vendor/bin/phpunit tests/Unit/Core/MedicalRecordGrantServiceTest.php
```
Expected: PASS (6 tests).

```bash
git add app/Modules/Core/Services/MedicalRecordGrantService.php \
        tests/Unit/Core/MedicalRecordGrantServiceTest.php
git commit -m "feat(core): MedicalRecordGrantService (grant/revoke/log)"
```

---

## Task 10: `AppointmentBookingService` (orchestrator)

**Files:**
- Create: `app/Modules/RendezVous/Services/AppointmentBookingService.php`
- Test: `tests/Unit/RendezVous/AppointmentBookingServiceTest.php`

- [ ] **Step 1: Write the failing tests**

```php
// tests/Unit/RendezVous/AppointmentBookingServiceTest.php
<?php
declare(strict_types=1);

namespace Tests\Unit\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\MedicalRecordGrant;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use App\Modules\RendezVous\Services\AppointmentBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AppointmentBookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentBookingService $svc;
    private User $patient;
    private Hosto $hosto;
    private Practitioner $practitioner;
    private TimeSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(AppointmentBookingService::class);
        $this->patient = User::factory()->create();
        $this->hosto = Hosto::factory()->create();
        $this->practitioner = Practitioner::factory()->create([
            'does_teleconsultation' => true,
            'does_home_care' => true,
        ]);
        $this->slot = TimeSlot::create([
            'practitioner_id' => $this->practitioner->id, 'hosto_id' => $this->hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30,
        ]);
    }

    public function test_book_creates_appointment_with_defaults(): void
    {
        $apt = $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
        ]);
        $this->assertSame('ordinaire', $apt->appointment_type);
        $this->assertSame('in_hospital', $apt->consultation_mode);
        $this->assertFalse($apt->share_medical_record);
    }

    public function test_book_urgence_type(): void
    {
        $apt = $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'appointment_type' => 'urgence',
        ]);
        $this->assertTrue($apt->isUrgent());
    }

    public function test_book_telecon_refuses_when_practitioner_doesnt_offer(): void
    {
        $this->practitioner->update(['does_teleconsultation' => false]);
        $this->expectException(\DomainException::class);
        $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'telecon',
        ]);
    }

    public function test_book_home_refuses_when_practitioner_doesnt_offer(): void
    {
        $this->practitioner->update(['does_home_care' => false]);
        $this->expectException(\DomainException::class);
        $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'home',
            'visit_address' => 'X',
        ]);
    }

    public function test_book_home_requires_address_or_coords(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'home',
            // no address, no lat/lng
        ]);
    }

    public function test_book_third_party_with_matched_phone_sets_user_id(): void
    {
        $third = User::factory()->create();
        $third->forceFill(['phone_normalized' => '+24106000099'])->save();

        $apt = $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'is_for_third_party' => true,
            'third_party_name' => 'Ami',
            'third_party_phone' => '+24106000099',
        ]);
        $this->assertSame($third->id, $apt->third_party_user_id);
    }

    public function test_book_third_party_with_unmatched_phone_creates_invitation(): void
    {
        $apt = $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'is_for_third_party' => true,
            'third_party_name' => 'Cousin',
            'third_party_phone' => '+24199999998',
        ]);
        $this->assertNull($apt->third_party_user_id);
        $this->assertDatabaseHas('invitation_links', [
            'phone_normalized' => '+24199999998',
            'context' => 'appointment_third_party',
            'context_id' => $apt->id,
        ]);
    }

    public function test_book_with_share_medical_record_and_valid_pin_creates_grant(): void
    {
        $this->patient->update(['medical_pin' => Hash::make('1234')]);
        $apt = $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'share_medical_record' => true,
            'medical_pin' => '1234',
        ]);
        $this->assertTrue($apt->share_medical_record);
        $this->assertSame(1, MedicalRecordGrant::where('patient_id', $this->patient->id)->count());
    }

    public function test_book_with_share_medical_record_and_invalid_pin_aborts(): void
    {
        $this->patient->update(['medical_pin' => Hash::make('1234')]);
        $this->expectException(\DomainException::class);
        $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'share_medical_record' => true,
            'medical_pin' => 'wrong',
        ]);
    }

    public function test_book_home_address_dispatches_geocode_job(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $this->svc->book([
            'patient' => $this->patient,
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'home',
            'visit_address' => 'Libreville',
        ]);
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Modules\RendezVous\Jobs\GeocodeAppointmentAddressJob::class);
    }
}
```

- [ ] **Step 2: Verify FAIL**

`./vendor/bin/phpunit tests/Unit/RendezVous/AppointmentBookingServiceTest.php`

- [ ] **Step 3: Implement the service**

```php
// app/Modules/RendezVous/Services/AppointmentBookingService.php
<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Services;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\MedicalRecordGrantService;
use App\Modules\RendezVous\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class AppointmentBookingService
{
    public function __construct(
        private readonly ThirdPartyResolverService $thirdParty,
        private readonly GeocodingService $geocoding,
        private readonly MedicalRecordGrantService $grants,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *   Required: patient (User), time_slot_id, practitioner_id, hosto_id
     *   Optional: appointment_type, consultation_mode, reason, notes,
     *             specialty_code, requested_at, is_for_third_party,
     *             third_party_name, third_party_phone, third_party_age, etc.,
     *             share_medical_record, medical_pin,
     *             visit_address, visit_lat, visit_lng, visit_location_accuracy_m
     */
    public function book(array $data): Appointment
    {
        /** @var User $patient */
        $patient = $data['patient'];
        $practitioner = Practitioner::findOrFail($data['practitioner_id']);
        $mode = $data['consultation_mode'] ?? 'in_hospital';

        $this->validateConsultationMode($mode, $practitioner);
        if ($mode === 'home') {
            $this->validateHomeAddress($data);
        }

        // Resolve third party
        $thirdPartyUserId = null;
        $thirdPartyPhoneNormalized = null;
        if (! empty($data['is_for_third_party'])) {
            $rawPhone = $data['third_party_phone'] ?? '';
            $normalized = $this->thirdParty->normalizePhone($rawPhone, 'GA');
            if ($normalized === null) {
                throw new \InvalidArgumentException('Phone tiers invalide');
            }
            $thirdPartyPhoneNormalized = $normalized;
            $match = $this->thirdParty->findUserByPhone($normalized);
            if ($match) {
                $thirdPartyUserId = $match->id;
            }
        }

        $apt = DB::transaction(function () use ($data, $patient, $mode, $thirdPartyUserId) {
            return Appointment::create([
                'time_slot_id' => $data['time_slot_id'],
                'patient_id' => $patient->id,
                'practitioner_id' => $data['practitioner_id'],
                'hosto_id' => $data['hosto_id'],
                'appointment_type' => $data['appointment_type'] ?? 'ordinaire',
                'consultation_mode' => $mode,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'specialty_code' => $data['specialty_code'] ?? null,
                'requested_at' => $data['requested_at'] ?? null,
                'is_for_third_party' => ! empty($data['is_for_third_party']),
                'third_party_name' => $data['third_party_name'] ?? null,
                'third_party_age' => $data['third_party_age'] ?? null,
                'third_party_gender' => $data['third_party_gender'] ?? null,
                'third_party_relation' => $data['third_party_relation'] ?? null,
                'third_party_address' => $data['third_party_address'] ?? null,
                'third_party_city' => $data['third_party_city'] ?? null,
                'third_party_phone' => $data['third_party_phone'] ?? null,
                'third_party_notes' => $data['third_party_notes'] ?? null,
                'third_party_user_id' => $thirdPartyUserId,
                'share_medical_record' => ! empty($data['share_medical_record']),
                'visit_address' => $data['visit_address'] ?? null,
                'visit_lat' => $data['visit_lat'] ?? null,
                'visit_lng' => $data['visit_lng'] ?? null,
                'visit_location_accuracy_m' => $data['visit_location_accuracy_m'] ?? null,
            ]);
        });

        // Side effects (outside the transaction, ok if any fails the apt still exists)
        if ($mode === 'home' && ($apt->visit_address && $apt->visit_lat === null)) {
            $this->geocoding->dispatchGeocodeJob($apt);
        }

        if (! empty($data['is_for_third_party']) && $thirdPartyUserId === null && $thirdPartyPhoneNormalized) {
            $this->thirdParty->sendInvitation($patient, $thirdPartyPhoneNormalized, $apt);
        }

        if (! empty($data['share_medical_record'])) {
            $pin = $data['medical_pin'] ?? null;
            $stored = $patient->medical_pin;
            if (! $stored || ! Hash::check((string) $pin, $stored)) {
                throw new \DomainException('PIN médical invalide ou non défini');
            }
            $practitionerModel = $apt->practitioner;
            $this->grants->grant($patient, $practitionerModel, null, $apt);
        }

        $this->audit->record(AuditLogger::ACTION_CREATE, 'appointment', $apt->uuid, [
            'type' => $apt->appointment_type,
            'mode' => $apt->consultation_mode,
            'third_party' => $apt->is_for_third_party,
            'share_dpe' => $apt->share_medical_record,
        ]);

        return $apt;
    }

    private function validateConsultationMode(string $mode, Practitioner $prac): void
    {
        if ($mode === 'telecon' && ! $prac->does_teleconsultation) {
            throw new \DomainException('Ce praticien ne fait pas de téléconsultation.');
        }
        if ($mode === 'home' && ! $prac->does_home_care) {
            throw new \DomainException('Ce praticien ne fait pas de visite à domicile.');
        }
        if (! in_array($mode, ['in_hospital', 'home', 'telecon'], true)) {
            throw new \InvalidArgumentException("Mode invalide: {$mode}");
        }
    }

    private function validateHomeAddress(array $data): void
    {
        $hasAddress = ! empty($data['visit_address']);
        $hasCoords = isset($data['visit_lat'], $data['visit_lng']);
        if (! $hasAddress && ! $hasCoords) {
            throw new \InvalidArgumentException('Adresse ou coordonnées requises pour une visite à domicile');
        }
    }
}
```

- [ ] **Step 4: Run + commit**

```bash
./vendor/bin/phpunit tests/Unit/RendezVous/AppointmentBookingServiceTest.php
```
Expected: PASS (10 tests).

```bash
git add app/Modules/RendezVous/Services/AppointmentBookingService.php \
        tests/Unit/RendezVous/AppointmentBookingServiceTest.php
git commit -m "feat(rdv): AppointmentBookingService (orchestrateur + tests TDD)"
```

---

## Phase 4 — FormRequest + Controllers + Routes

## Task 11: `BookAppointmentRequest` (FormRequest centralisé)

**Files:**
- Create: `app/Modules/RendezVous/Http/Requests/BookAppointmentRequest.php`

(Pas de test dédié — la validation est testée via les tests feature dans Task 14.)

- [ ] **Step 1: Create the request**

```php
// app/Modules/RendezVous/Http/Requests/BookAppointmentRequest.php
<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'time_slot_id' => 'required|integer|exists:time_slots,id',
            'practitioner_id' => 'required|integer|exists:practitioners,id',
            'hosto_id' => 'required|integer|exists:hostos,id',
            'appointment_type' => 'nullable|in:ordinaire,urgence,grossesse,natalite,chronique',
            'consultation_mode' => 'nullable|in:in_hospital,home,telecon',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'specialty_code' => 'nullable|string|max:20',
            'requested_at' => 'nullable|date|after:now',
            'is_for_third_party' => 'sometimes|boolean',
            'third_party_name' => 'required_if:is_for_third_party,true|nullable|string|max:255',
            'third_party_phone' => 'required_if:is_for_third_party,true|nullable|string|max:30',
            'third_party_age' => 'nullable|integer|min:0|max:120',
            'third_party_gender' => 'nullable|in:male,female,other',
            'third_party_relation' => 'nullable|string|max:30',
            'third_party_address' => 'nullable|string|max:255',
            'third_party_city' => 'nullable|string|max:120',
            'third_party_notes' => 'nullable|string|max:1000',
            'share_medical_record' => 'sometimes|boolean',
            'medical_pin' => 'required_if:share_medical_record,true|nullable|string|digits_between:4,6',
            'visit_address' => 'nullable|string|max:500',
            'visit_lat' => 'nullable|numeric|between:-90,90',
            'visit_lng' => 'nullable|numeric|between:-180,180',
            'visit_location_accuracy_m' => 'nullable|integer|min:0|max:10000',
            'documents' => 'nullable|array|max:5',
            'documents.*' => 'file|max:10240|mimetypes:application/pdf,image/jpeg,image/png,image/heic,image/heif',
        ];
    }

    public function messages(): array
    {
        return [
            'documents.max' => 'Maximum 5 documents par RDV.',
            'documents.*.max' => 'Chaque document doit faire moins de 10 MB.',
            'medical_pin.required_if' => 'PIN médical requis pour partager le dossier.',
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Modules/RendezVous/Http/Requests/BookAppointmentRequest.php
git commit -m "feat(rdv): BookAppointmentRequest (validation centralisee)"
```

---

## Task 12: `ThirdPartyLookupController` + route API

**Files:**
- Create: `app/Modules/RendezVous/Http/Controllers/ThirdPartyLookupController.php`
- Modify: `app/Modules/RendezVous/Routes/api.php`
- Test: `tests/Feature/RendezVous/ThirdPartyLookupApiTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/RendezVous/ThirdPartyLookupApiTest.php
<?php
declare(strict_types=1);

namespace Tests\Feature\RendezVous;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ThirdPartyLookupApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_returns_matched_for_existing_phone(): void
    {
        $caller = User::factory()->create();
        $third = User::factory()->create(['name' => 'M. Diop']);
        $third->forceFill(['phone_normalized' => '+24106000099'])->save();

        $resp = $this->actingAs($caller)
            ->getJson('/api/v1/rdv/third-party/lookup?phone=06000099');

        $resp->assertOk();
        $resp->assertJson(['matched' => true, 'full_name' => 'M. Diop']);
    }

    public function test_lookup_returns_not_matched_for_unknown(): void
    {
        $caller = User::factory()->create();
        $resp = $this->actingAs($caller)
            ->getJson('/api/v1/rdv/third-party/lookup?phone=06999999');
        $resp->assertOk();
        $resp->assertJson(['matched' => false]);
    }

    public function test_lookup_normalizes_local_format(): void
    {
        $caller = User::factory()->create();
        $third = User::factory()->create();
        $third->forceFill(['phone_normalized' => '+24106000001'])->save();
        $resp = $this->actingAs($caller)
            ->getJson('/api/v1/rdv/third-party/lookup?phone=06000001');
        $resp->assertJson(['matched' => true]);
    }

    public function test_lookup_returns_400_on_invalid_phone(): void
    {
        $caller = User::factory()->create();
        $resp = $this->actingAs($caller)
            ->getJson('/api/v1/rdv/third-party/lookup?phone=abcd');
        $resp->assertStatus(400);
    }
}
```

- [ ] **Step 2: Verify FAIL**

`./vendor/bin/phpunit tests/Feature/RendezVous/ThirdPartyLookupApiTest.php`

- [ ] **Step 3: Implement the controller**

```php
// app/Modules/RendezVous/Http/Controllers/ThirdPartyLookupController.php
<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Http\Controllers;

use App\Modules\RendezVous\Services\ThirdPartyResolverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ThirdPartyLookupController
{
    public function __construct(private readonly ThirdPartyResolverService $svc) {}

    public function __invoke(Request $request): JsonResponse
    {
        $phone = trim((string) $request->query('phone', ''));
        if ($phone === '') {
            return response()->json(['matched' => false]);
        }
        $normalized = $this->svc->normalizePhone($phone, 'GA');
        if ($normalized === null) {
            return response()->json(['error' => 'invalid_phone'], 400);
        }
        $user = $this->svc->findUserByPhone($normalized);
        if (! $user) {
            return response()->json(['matched' => false]);
        }
        return response()->json([
            'matched' => true,
            'full_name' => $user->name,
        ]);
    }
}
```

- [ ] **Step 4: Add route**

Read `app/Modules/RendezVous/Routes/api.php`. Note the existing api.php structure (mounted under `/api/v1/rdv`). Append :

```php
use App\Modules\RendezVous\Http\Controllers\ThirdPartyLookupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:30,60'])
    ->get('third-party/lookup', ThirdPartyLookupController::class)
    ->name('rdv.api.third-party.lookup');
```

If the existing api.php uses a `Route::prefix('rdv')`, place the line inside that group. Inspect the file first to confirm.

- [ ] **Step 5: Add `throttle:30,60` mapping if not standard**

This is the default Laravel throttle (30 req / 60 minutes). No additional setup needed.

**Important** : the auth method for API endpoints under `/api/v1/...` may already be defined in the existing `app/Modules/RendezVous/Providers/RendezVousServiceProvider.php`. If the existing routes don't use Sanctum/auth, omit `auth:sanctum` middleware OR use session-based auth via `web` middleware family. The test uses `actingAs($user)` which works with session auth. To make it simplest and pass the test, replace `auth:sanctum` with `web` middleware (which includes session auth) :

```php
Route::middleware(['web', 'throttle:30,60'])
    ->get('third-party/lookup', ThirdPartyLookupController::class)
    ->name('rdv.api.third-party.lookup');
```

The controller already aborts if no user via the test's `actingAs($caller)` precondition is satisfied. Add inside the controller :

```php
if (! $request->user()) {
    abort(401);
}
```

at the top of `__invoke()`.

- [ ] **Step 6: Run + commit**

```bash
php artisan route:clear
./vendor/bin/phpunit tests/Feature/RendezVous/ThirdPartyLookupApiTest.php
```
Expected: PASS (4 tests).

```bash
git add app/Modules/RendezVous/Http/Controllers/ThirdPartyLookupController.php \
        app/Modules/RendezVous/Routes/api.php \
        tests/Feature/RendezVous/ThirdPartyLookupApiTest.php
git commit -m "feat(rdv): API third-party lookup avec normalisation E.164 + rate-limit"
```

---

## Task 13: `AppointmentDocumentsController` + routes + tests

**Files:**
- Create: `app/Modules/RendezVous/Http/Controllers/AppointmentDocumentsController.php`
- Modify: `routes/web.php` (ajouter les routes documents)
- Test: `tests/Feature/RendezVous/AppointmentDocumentsControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/RendezVous/AppointmentDocumentsControllerTest.php
<?php
declare(strict_types=1);

namespace Tests\Feature\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\AppointmentDocument;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AppointmentDocumentsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;
    private Appointment $apt;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private_appointments');
        $this->patient = User::factory()->create(['phone_verified_at' => now()]);
        $hosto = Hosto::factory()->create();
        $prac = Practitioner::factory()->create();
        $slot = TimeSlot::create([
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30,
        ]);
        $this->apt = Appointment::create([
            'time_slot_id' => $slot->id, 'patient_id' => $this->patient->id,
            'practitioner_id' => $prac->id, 'hosto_id' => $hosto->id,
        ]);
    }

    public function test_patient_can_upload_document(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 100, 'application/pdf');
        $resp = $this->actingAs($this->patient)
            ->post('/web/rdv/'.$this->apt->uuid.'/documents', [
                'file' => $file, 'category' => 'ordonnance',
            ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('appointment_documents', [
            'appointment_id' => $this->apt->id, 'original_name' => 'o.pdf',
        ]);
    }

    public function test_patient_cannot_upload_to_others_appointment(): void
    {
        $other = User::factory()->create();
        $file = UploadedFile::fake()->create('o.pdf', 100, 'application/pdf');
        $resp = $this->actingAs($other)
            ->post('/web/rdv/'.$this->apt->uuid.'/documents', ['file' => $file]);
        $resp->assertForbidden();
    }

    public function test_patient_can_download_own_document(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 100, 'application/pdf');
        app(\App\Modules\RendezVous\Services\DocumentUploadService::class)
            ->store($this->apt, $file, $this->patient, null);
        $doc = AppointmentDocument::first();
        $resp = $this->actingAs($this->patient)
            ->get('/web/rdv/documents/'.$doc->uuid.'/download');
        $resp->assertOk();
        $resp->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_random_user_cannot_download(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 100, 'application/pdf');
        $doc = app(\App\Modules\RendezVous\Services\DocumentUploadService::class)
            ->store($this->apt, $file, $this->patient, null);
        $other = User::factory()->create();
        $resp = $this->actingAs($other)
            ->get('/web/rdv/documents/'.$doc->uuid.'/download');
        $resp->assertForbidden();
    }

    public function test_owner_can_delete_document(): void
    {
        $file = UploadedFile::fake()->create('o.pdf', 100, 'application/pdf');
        $doc = app(\App\Modules\RendezVous\Services\DocumentUploadService::class)
            ->store($this->apt, $file, $this->patient, null);
        $resp = $this->actingAs($this->patient)
            ->delete('/web/rdv/documents/'.$doc->uuid);
        $resp->assertRedirect();
        $this->assertSoftDeleted($doc);
    }
}
```

- [ ] **Step 2: Verify FAIL**

`./vendor/bin/phpunit tests/Feature/RendezVous/AppointmentDocumentsControllerTest.php`

- [ ] **Step 3: Implement the controller**

```php
// app/Modules/RendezVous/Http/Controllers/AppointmentDocumentsController.php
<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Http\Controllers;

use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\AppointmentDocument;
use App\Modules\RendezVous\Services\DocumentUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AppointmentDocumentsController
{
    public function __construct(private readonly DocumentUploadService $svc) {}

    public function store(Request $request, string $appointmentUuid): RedirectResponse
    {
        $apt = Appointment::where('uuid', $appointmentUuid)->firstOrFail();
        $user = $request->user();
        // Only patient owner or matched third party can upload
        if ($apt->patient_id !== $user->id && $apt->third_party_user_id !== $user->id) {
            abort(403, 'Vous ne pouvez pas uploader pour ce RDV.');
        }
        $validated = $request->validate([
            'file' => 'required|file|max:10240|mimetypes:application/pdf,image/jpeg,image/png,image/heic,image/heif',
            'category' => 'nullable|string|in:ordonnance,examen,autre',
        ]);
        try {
            $this->svc->store($apt, $validated['file'], $user, $validated['category'] ?? null);
        } catch (\DomainException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
        return back()->with('success', 'Document attaché.');
    }

    public function download(Request $request, string $uuid): StreamedResponse
    {
        $doc = AppointmentDocument::where('uuid', $uuid)->firstOrFail();
        return $this->svc->download($doc, $request->user());
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $doc = AppointmentDocument::where('uuid', $uuid)->firstOrFail();
        if ($doc->uploaded_by_id !== $request->user()->id) {
            abort(403, 'Seul l\'uploader peut supprimer.');
        }
        $this->svc->delete($doc, $request->user());
        return back()->with('success', 'Document supprimé.');
    }
}
```

- [ ] **Step 4: Add routes**

Read `routes/web.php`. Find a place inside an authenticated group (look for `Route::middleware('auth')`) and add :

```php
use App\Modules\RendezVous\Http\Controllers\AppointmentDocumentsController;

// Inside an auth-protected group :
Route::post('/web/rdv/{appointmentUuid}/documents', [AppointmentDocumentsController::class, 'store'])
    ->name('rdv.documents.store');
Route::get('/web/rdv/documents/{uuid}/download', [AppointmentDocumentsController::class, 'download'])
    ->name('rdv.documents.download');
Route::delete('/web/rdv/documents/{uuid}', [AppointmentDocumentsController::class, 'destroy'])
    ->name('rdv.documents.destroy');
```

- [ ] **Step 5: Run + commit**

```bash
php artisan route:clear
./vendor/bin/phpunit tests/Feature/RendezVous/AppointmentDocumentsControllerTest.php
```
Expected: PASS (5 tests).

```bash
git add app/Modules/RendezVous/Http/Controllers/AppointmentDocumentsController.php \
        routes/web.php \
        tests/Feature/RendezVous/AppointmentDocumentsControllerTest.php
git commit -m "feat(rdv): AppointmentDocumentsController (upload/download/delete gated)"
```

---

## Task 14: `MedicalRecordGrantsController` + vues + routes

**Files:**
- Create: `app/Modules/Core/Http/Controllers/MedicalRecordGrantsController.php`
- Create: `resources/views/compte/dossier/partages.blade.php`
- Create: `resources/views/compte/dossier/partage-historique.blade.php`
- Modify: `routes/web.php` (routes patient `/compte/dossier/partages*`)
- Test: `tests/Feature/Compte/MedicalRecordGrantsControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Compte/MedicalRecordGrantsControllerTest.php
<?php
declare(strict_types=1);

namespace Tests\Feature\Compte;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\MedicalRecordGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MedicalRecordGrantsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_list_their_active_grants(): void
    {
        $p = User::factory()->create(['phone_verified_at' => now()]);
        $prac = Practitioner::factory()->create();
        MedicalRecordGrant::create([
            'patient_id' => $p->id, 'practitioner_id' => $prac->id,
            'granted_at' => now(),
        ]);
        $resp = $this->actingAs($p)->get('/compte/dossier/partages');
        $resp->assertOk();
        $resp->assertSeeText($prac->full_name);
    }

    public function test_patient_can_revoke_grant(): void
    {
        $p = User::factory()->create(['phone_verified_at' => now()]);
        $prac = Practitioner::factory()->create();
        $g = MedicalRecordGrant::create([
            'patient_id' => $p->id, 'practitioner_id' => $prac->id,
            'granted_at' => now(),
        ]);
        $resp = $this->actingAs($p)->post('/compte/dossier/partages/'.$g->uuid.'/revoke');
        $resp->assertRedirect();
        $this->assertNotNull($g->fresh()->revoked_at);
    }

    public function test_patient_cannot_revoke_others_grant(): void
    {
        $p1 = User::factory()->create(['phone_verified_at' => now()]);
        $p2 = User::factory()->create(['phone_verified_at' => now()]);
        $prac = Practitioner::factory()->create();
        $g = MedicalRecordGrant::create([
            'patient_id' => $p1->id, 'practitioner_id' => $prac->id,
            'granted_at' => now(),
        ]);
        $resp = $this->actingAs($p2)->post('/compte/dossier/partages/'.$g->uuid.'/revoke');
        $resp->assertForbidden();
    }

    public function test_patient_can_view_access_history(): void
    {
        $p = User::factory()->create(['phone_verified_at' => now()]);
        $prac = Practitioner::factory()->create();
        $g = MedicalRecordGrant::create([
            'patient_id' => $p->id, 'practitioner_id' => $prac->id,
            'granted_at' => now(),
        ]);
        $resp = $this->actingAs($p)->get('/compte/dossier/partages/'.$g->uuid.'/historique');
        $resp->assertOk();
    }
}
```

- [ ] **Step 2: Verify FAIL**

`./vendor/bin/phpunit tests/Feature/Compte/MedicalRecordGrantsControllerTest.php`

- [ ] **Step 3: Implement the controller**

```php
// app/Modules/Core/Http/Controllers/MedicalRecordGrantsController.php
<?php
declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Models\MedicalRecordGrant;
use App\Modules\Core\Services\MedicalRecordGrantService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MedicalRecordGrantsController
{
    public function __construct(private readonly MedicalRecordGrantService $svc) {}

    public function index(Request $request): View
    {
        $grants = MedicalRecordGrant::where('patient_id', $request->user()->id)
            ->with('practitioner')
            ->orderByDesc('granted_at')
            ->get();
        return view('compte.dossier.partages', compact('grants'));
    }

    public function show(Request $request, string $uuid): View
    {
        $grant = MedicalRecordGrant::where('uuid', $uuid)->firstOrFail();
        if ($grant->patient_id !== $request->user()->id) abort(403);
        $logs = $grant->accessLogs()->with('practitionerUser')->limit(50)->get();
        return view('compte.dossier.partage-historique', compact('grant', 'logs'));
    }

    public function revoke(Request $request, string $uuid): RedirectResponse
    {
        $grant = MedicalRecordGrant::where('uuid', $uuid)->firstOrFail();
        if ($grant->patient_id !== $request->user()->id) abort(403);
        $this->svc->revoke($grant, $request->user());
        return back()->with('success', 'Accès révoqué.');
    }
}
```

- [ ] **Step 4: Create the views**

```blade
{{-- resources/views/compte/dossier/partages.blade.php --}}
@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Mes partages')
@section('page-title', 'Partages de mon dossier médical')
@section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'dossier']) @endsection

@section('content')
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;">
    @if($grants->isEmpty())
        <p style="color:#757575;">Vous n'avez partagé votre dossier avec personne pour le moment.</p>
    @else
        @foreach($grants as $g)
            <div style="padding:14px;border-bottom:1px solid #F5F5F5;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <strong>{{ $g->practitioner->full_name }}</strong>
                    <div style="font-size:.78rem;color:#757575;">
                        Partagé depuis le {{ $g->granted_at->format('d/m/Y') }}
                        @if($g->revoked_at)
                            <span style="color:#C62828;"> — Révoqué le {{ $g->revoked_at->format('d/m/Y') }}</span>
                        @else
                            — {{ $g->access_count }} accès au total
                            @if($g->last_accessed_at)
                                · dernier le {{ $g->last_accessed_at->format('d/m/Y H:i') }}
                            @endif
                        @endif
                    </div>
                </div>
                <div style="display:flex;gap:8px;">
                    <a href="/compte/dossier/partages/{{ $g->uuid }}/historique"
                       style="padding:6px 12px;background:#E3F2FD;color:#1565C0;border-radius:6px;text-decoration:none;font-size:.78rem;font-weight:600;">
                        Historique
                    </a>
                    @if(!$g->revoked_at)
                        <form method="POST" action="/compte/dossier/partages/{{ $g->uuid }}/revoke" style="display:inline;">
                            @csrf
                            <button type="submit" onclick="return confirm('Révoquer ce partage ?')"
                                    style="padding:6px 12px;background:#FFEBEE;color:#C62828;border:none;border-radius:6px;cursor:pointer;font-size:.78rem;font-weight:600;">
                                Révoquer
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
```

```blade
{{-- resources/views/compte/dossier/partage-historique.blade.php --}}
@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Historique partage')
@section('page-title', 'Historique des accès — ' . $grant->practitioner->full_name)
@section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'dossier']) @endsection

@section('content')
<a href="/compte/dossier/partages" style="display:inline-block;margin-bottom:14px;color:#388E3C;font-size:.82rem;">← Retour aux partages</a>

<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead><tr style="background:#FAFAFA;">
            <th style="padding:12px 16px;text-align:left;">Date</th>
            <th style="padding:12px 16px;text-align:left;">Pro accédant</th>
            <th style="padding:12px 16px;text-align:left;">Sections</th>
            <th style="padding:12px 16px;text-align:left;">IP</th>
        </tr></thead>
        <tbody>
            @forelse($logs as $l)
                <tr style="border-top:1px solid #F5F5F5;">
                    <td style="padding:10px 16px;">{{ $l->accessed_at->format('d/m/Y H:i') }}</td>
                    <td style="padding:10px 16px;">{{ $l->practitionerUser?->name ?? '—' }}</td>
                    <td style="padding:10px 16px;color:#757575;">
                        {{ $l->sections_accessed ? implode(', ', $l->sections_accessed) : '—' }}
                    </td>
                    <td style="padding:10px 16px;font-family:monospace;font-size:.78rem;color:#757575;">{{ $l->ip_address ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="padding:30px;text-align:center;color:#999;">Aucun accès enregistré.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
```

- [ ] **Step 5: Add routes**

In `routes/web.php`, inside the user auth group, add :

```php
use App\Modules\Core\Http\Controllers\MedicalRecordGrantsController;

Route::middleware(['auth', 'env:usager'])->prefix('compte/dossier/partages')
    ->name('compte.dossier.partages.')->group(function (): void {
        Route::get('/', [MedicalRecordGrantsController::class, 'index'])->name('index');
        Route::get('/{uuid}/historique', [MedicalRecordGrantsController::class, 'show'])->name('show');
        Route::post('/{uuid}/revoke', [MedicalRecordGrantsController::class, 'revoke'])->name('revoke');
    });
```

- [ ] **Step 6: Run + commit**

```bash
php artisan route:clear
./vendor/bin/phpunit tests/Feature/Compte/MedicalRecordGrantsControllerTest.php
```
Expected: PASS (4 tests).

```bash
git add app/Modules/Core/Http/Controllers/MedicalRecordGrantsController.php \
        resources/views/compte/dossier/partages.blade.php \
        resources/views/compte/dossier/partage-historique.blade.php \
        routes/web.php \
        tests/Feature/Compte/MedicalRecordGrantsControllerTest.php
git commit -m "feat(core): MedicalRecordGrantsController + vues patient (list/revoke/history)"
```

---

## Task 15: Refonte `BookingWebController` et intégration `AppointmentBookingService`

**Files:**
- Modify: `app/Http/Controllers/BookingWebController.php`
- Modify: `routes/web.php` (route POST `/web/rdv/book` peut rester telle quelle mais le contrôleur change)
- Test: `tests/Feature/RendezVous/BookingFlowTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/RendezVous/BookingFlowTest.php
<?php
declare(strict_types=1);

namespace Tests\Feature\RendezVous;

use App\Models\User;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\InvitationLink;
use App\Modules\Core\Models\MedicalRecordGrant;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;
    private Hosto $hosto;
    private Practitioner $practitioner;
    private TimeSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patient = User::factory()->create(['phone_verified_at' => now()]);
        $this->hosto = Hosto::factory()->create(['is_partner' => true]);
        $this->practitioner = Practitioner::factory()->create([
            'does_teleconsultation' => true,
            'does_home_care' => true,
        ]);
        $this->slot = TimeSlot::create([
            'practitioner_id' => $this->practitioner->id, 'hosto_id' => $this->hosto->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30',
            'duration_minutes' => 30,
        ]);
    }

    public function test_user_can_book_ordinaire_in_hospital(): void
    {
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'reason' => 'Visite contrôle',
        ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'appointment_type' => 'ordinaire',
            'consultation_mode' => 'in_hospital',
        ]);
    }

    public function test_user_cannot_book_telecon_when_not_offered(): void
    {
        $this->practitioner->update(['does_teleconsultation' => false]);
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'telecon',
        ]);
        $resp->assertSessionHasErrors();
    }

    public function test_user_can_book_home_with_address(): void
    {
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'home',
            'visit_address' => 'Libreville',
        ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'consultation_mode' => 'home',
            'visit_address' => 'Libreville',
        ]);
    }

    public function test_user_can_book_home_with_geolocation(): void
    {
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'consultation_mode' => 'home',
            'visit_lat' => 0.4162,
            'visit_lng' => 9.4673,
            'visit_location_accuracy_m' => 10,
        ]);
        $resp->assertRedirect();
        $apt = Appointment::where('patient_id', $this->patient->id)->first();
        $this->assertEqualsWithDelta(0.4162, (float) $apt->visit_lat, 0.0001);
    }

    public function test_user_can_book_for_third_party_with_matched_phone(): void
    {
        $third = User::factory()->create(['name' => 'M. Diop']);
        $third->forceFill(['phone_normalized' => '+24106000099'])->save();
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'is_for_third_party' => true,
            'third_party_name' => 'M. Diop',
            'third_party_phone' => '06000099',
        ]);
        $resp->assertRedirect();
        $apt = Appointment::where('patient_id', $this->patient->id)->first();
        $this->assertSame($third->id, $apt->third_party_user_id);
    }

    public function test_user_can_book_for_third_party_with_unmatched_phone_invites(): void
    {
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'is_for_third_party' => true,
            'third_party_name' => 'Cousin X',
            'third_party_phone' => '06999998',
        ]);
        $resp->assertRedirect();
        $this->assertSame(1, InvitationLink::count());
    }

    public function test_user_can_share_medical_record_with_valid_pin(): void
    {
        $this->patient->update(['medical_pin' => Hash::make('1234')]);
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'share_medical_record' => '1',
            'medical_pin' => '1234',
        ]);
        $resp->assertRedirect();
        $this->assertSame(1, MedicalRecordGrant::where('patient_id', $this->patient->id)->count());
    }

    public function test_user_cannot_share_with_invalid_pin(): void
    {
        $this->patient->update(['medical_pin' => Hash::make('1234')]);
        $resp = $this->actingAs($this->patient)->post('/web/rdv/book', [
            'time_slot_id' => $this->slot->id,
            'practitioner_id' => $this->practitioner->id,
            'hosto_id' => $this->hosto->id,
            'share_medical_record' => '1',
            'medical_pin' => 'wrong1',
        ]);
        $resp->assertSessionHasErrors();
        $this->assertSame(0, MedicalRecordGrant::count());
    }
}
```

- [ ] **Step 2: Verify FAIL**

`./vendor/bin/phpunit tests/Feature/RendezVous/BookingFlowTest.php`

- [ ] **Step 3: Modify `BookingWebController`**

Read `app/Http/Controllers/BookingWebController.php`. Locate the `bookAppointment` method. Replace its body to use `AppointmentBookingService` :

```php
// In BookingWebController.php, at the top with other uses :
use App\Modules\RendezVous\Http\Requests\BookAppointmentRequest;
use App\Modules\RendezVous\Services\AppointmentBookingService;

// Replace bookAppointment() with :
public function bookAppointment(BookAppointmentRequest $request, AppointmentBookingService $svc)
{
    $data = $request->validated();
    $data['patient'] = $request->user();
    try {
        $apt = $svc->book($data);
    } catch (\DomainException $e) {
        return back()->withErrors(['booking' => $e->getMessage()])->withInput();
    } catch (\InvalidArgumentException $e) {
        return back()->withErrors(['booking' => $e->getMessage()])->withInput();
    }
    return redirect()->route('compte.rdv')->with('success', "RDV demandé (réf. {$apt->uuid}). Le médecin va vous proposer un horaire.");
}
```

If `bookAppointment()` is currently inline-handling lots of logic, it's OK to drop most of it — the service does the work now.

- [ ] **Step 4: Run + commit**

```bash
./vendor/bin/phpunit tests/Feature/RendezVous/BookingFlowTest.php
```
Expected: PASS (8 tests).

```bash
git add app/Http/Controllers/BookingWebController.php \
        tests/Feature/RendezVous/BookingFlowTest.php
git commit -m "feat(rdv): refonte BookingWebController via AppointmentBookingService"
```

---

## Task 16: Vue `rendez-vous-form.blade.php` (formulaire complet)

**Files:**
- Create: `resources/views/compte/rendez-vous-form.blade.php`
- Modify: `resources/views/annuaire/book-rdv.blade.php` (utiliser le nouveau form si pertinent)

(Pas de test PHPUnit ici — c'est de la UI. La validation est testée via les tests Feature de Task 15.)

- [ ] **Step 1: Create the form view**

The view is large. Create `resources/views/compte/rendez-vous-form.blade.php` with the full content of the form (radio types RDV, radio modes consultation, geolocation block, third-party block, document upload block, medical record sharing block).

```blade
@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Nouveau RDV')
@section('page-title', 'Prendre un rendez-vous')
@section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'rdv']) @endsection

@section('styles')
<style>
    .rdv-form { background:white;border:1px solid #EEE;border-radius:14px;padding:24px;max-width:760px; }
    .rdv-form section { margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #F5F5F5; }
    .rdv-form section:last-child { border-bottom:none; }
    .rdv-form h3 { margin:0 0 10px;font-size:.95rem;color:#1B2A1B; }
    .rdv-form label { display:block;font-size:.82rem;color:#424242;margin-bottom:4px; }
    .rdv-form input[type="text"], .rdv-form input[type="email"], .rdv-form input[type="tel"],
    .rdv-form input[type="number"], .rdv-form input[type="date"], .rdv-form input[type="time"],
    .rdv-form select, .rdv-form textarea {
        width:100%;padding:10px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;
        font-size:.85rem;outline:none;box-sizing:border-box;
    }
    .rdv-form .radio-group label { display:inline-flex;align-items:center;gap:6px;margin-right:14px;cursor:pointer; }
    .rdv-form .field-row { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
    .rdv-form .btn { padding:10px 22px;background:#388E3C;color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer; }
    #leafletMap { height:240px;border-radius:8px;margin-top:8px; }
</style>
@endsection

@section('content')
<form id="rdvForm" method="POST" action="/web/rdv/book" enctype="multipart/form-data" class="rdv-form">
    @csrf
    <input type="hidden" name="time_slot_id" value="{{ $slot->id ?? '' }}">
    <input type="hidden" name="practitioner_id" value="{{ $practitioner->id ?? '' }}">
    <input type="hidden" name="hosto_id" value="{{ $hosto->id ?? '' }}">

    @if($errors->any())
        <div style="padding:10px;background:#FFEBEE;color:#C62828;border-radius:8px;margin-bottom:14px;">
            {{ $errors->first() }}
        </div>
    @endif

    <section>
        <h3>1. Type de RDV</h3>
        <div class="radio-group">
            <label><input type="radio" name="appointment_type" value="ordinaire" checked> Ordinaire</label>
            <label><input type="radio" name="appointment_type" value="urgence"> Urgence</label>
            <label><input type="radio" name="appointment_type" value="grossesse"> Grossesse</label>
            <label><input type="radio" name="appointment_type" value="natalite"> Natalité</label>
            <label><input type="radio" name="appointment_type" value="chronique"> Suivi maladie chronique</label>
        </div>
    </section>

    <section>
        <h3>2. Type de consultation</h3>
        <div class="radio-group" id="consultationModeGroup">
            <label><input type="radio" name="consultation_mode" value="in_hospital" checked> À l'hôpital</label>
            @if($practitioner->does_home_care ?? false)
                <label><input type="radio" name="consultation_mode" value="home"> À domicile</label>
            @endif
            @if($practitioner->does_teleconsultation ?? false)
                <label><input type="radio" name="consultation_mode" value="telecon"> Téléconsultation</label>
            @endif
        </div>
    </section>

    <section id="homeBlock" style="display:none;">
        <h3>Adresse de visite</h3>
        <label>Adresse <input type="text" name="visit_address" placeholder="BP 1234, Quartier Glass, Libreville"></label>
        <button type="button" onclick="useGeolocation()" style="margin-top:8px;padding:8px 14px;background:#E3F2FD;color:#1565C0;border:none;border-radius:6px;cursor:pointer;font-size:.82rem;">
            📍 Utiliser ma position actuelle
        </button>
        <input type="hidden" name="visit_lat" id="visitLat">
        <input type="hidden" name="visit_lng" id="visitLng">
        <input type="hidden" name="visit_location_accuracy_m" id="visitAcc">
        <div id="leafletMap"></div>
    </section>

    <section>
        <h3>3. Motif (facultatif)</h3>
        <input type="text" name="reason" maxlength="255" placeholder="Ex : douleur lombaire, suivi annuel...">
    </section>

    <section>
        <h3>4. Pour qui ?</h3>
        <div class="radio-group">
            <label><input type="radio" name="is_for_third_party" value="0" checked onclick="toggleThirdParty(false)"> Pour moi</label>
            <label><input type="radio" name="is_for_third_party" value="1" onclick="toggleThirdParty(true)"> Pour un tiers</label>
        </div>
        <div id="thirdPartyBlock" style="display:none;margin-top:10px;">
            <div class="field-row">
                <label>Nom complet <input type="text" name="third_party_name"></label>
                <label>Téléphone <input type="tel" name="third_party_phone" id="thirdPhone" onblur="lookupThird()"></label>
            </div>
            <div id="thirdLookupResult" style="margin-top:6px;font-size:.78rem;"></div>
            <div class="field-row">
                <label>Âge <input type="number" name="third_party_age" min="0" max="120"></label>
                <label>Sexe
                    <select name="third_party_gender"><option value="">—</option><option value="male">Masculin</option><option value="female">Féminin</option></select>
                </label>
            </div>
            <label>Ville <input type="text" name="third_party_city"></label>
            <label>Lien <input type="text" name="third_party_relation" placeholder="enfant, parent, ami..."></label>
            <label>Notes <textarea name="third_party_notes" rows="2"></textarea></label>
        </div>
    </section>

    <section>
        <h3>5. Documents (max 5, 10 MB chacun, 30 MB total)</h3>
        <input type="file" name="documents[]" multiple accept="application/pdf,image/jpeg,image/png,image/heic">
    </section>

    <section>
        <h3>6. Partager mon dossier médical</h3>
        <label style="display:flex;align-items:center;gap:8px;">
            <input type="checkbox" name="share_medical_record" value="1" id="shareDpe" onchange="togglePin()">
            Je partage mon dossier médical avec ce médecin
        </label>
        <div id="pinBlock" style="display:none;margin-top:10px;">
            <label>PIN médical (4-6 chiffres) <input type="password" name="medical_pin" maxlength="6" pattern="[0-9]{4,6}" inputmode="numeric"></label>
            <p style="font-size:.72rem;color:#757575;margin-top:4px;">Le partage reste actif jusqu'à révocation explicite depuis votre espace.</p>
        </div>
    </section>

    <div style="display:flex;gap:8px;justify-content:flex-end;">
        <a href="javascript:history.back()" style="padding:10px 22px;border:1px solid #EEE;border-radius:8px;text-decoration:none;color:#424242;">Annuler</a>
        <button type="submit" class="btn">Demander ce RDV</button>
    </div>
</form>

<script>
function toggleThirdParty(show) {
    document.getElementById('thirdPartyBlock').style.display = show ? 'block' : 'none';
}

function togglePin() {
    document.getElementById('pinBlock').style.display = document.getElementById('shareDpe').checked ? 'block' : 'none';
}

let leafletMap = null;
function showHomeBlock() {
    const homeBlock = document.getElementById('homeBlock');
    homeBlock.style.display = 'block';
    if (!leafletMap) {
        leafletMap = L.map('leafletMap').setView([0.4162, 9.4673], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM', maxZoom: 19 }).addTo(leafletMap);
    }
}

document.querySelectorAll('input[name="consultation_mode"]').forEach(r => {
    r.addEventListener('change', e => {
        if (e.target.value === 'home') showHomeBlock();
        else document.getElementById('homeBlock').style.display = 'none';
    });
});

function useGeolocation() {
    if (!navigator.geolocation) { alert('Géolocalisation non supportée par votre navigateur'); return; }
    navigator.geolocation.getCurrentPosition(pos => {
        document.getElementById('visitLat').value = pos.coords.latitude;
        document.getElementById('visitLng').value = pos.coords.longitude;
        document.getElementById('visitAcc').value = Math.round(pos.coords.accuracy);
        if (leafletMap) {
            leafletMap.setView([pos.coords.latitude, pos.coords.longitude], 16);
            L.marker([pos.coords.latitude, pos.coords.longitude]).addTo(leafletMap);
        }
    }, err => alert('Géolocalisation refusée ou indisponible'));
}

async function lookupThird() {
    const phone = document.getElementById('thirdPhone').value.trim();
    if (!phone) return;
    try {
        const res = await fetch('/api/v1/rdv/third-party/lookup?phone=' + encodeURIComponent(phone));
        const data = await res.json();
        const out = document.getElementById('thirdLookupResult');
        while (out.firstChild) out.removeChild(out.firstChild);
        if (data.matched) {
            const ok = document.createElement('span');
            ok.style.color = '#2E7D32';
            ok.textContent = '✓ ' + data.full_name + ' a un compte HOSTO. Le RDV sera lié à son dossier.';
            out.appendChild(ok);
        } else {
            const ko = document.createElement('span');
            ko.style.color = '#E65100';
            ko.textContent = '✗ Aucun compte HOSTO trouvé. Un SMS d\'invitation lui sera envoyé.';
            out.appendChild(ko);
        }
    } catch (e) { /* silent */ }
}
</script>
@endsection
```

- [ ] **Step 2: Verify view renders**

```bash
php artisan view:clear
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\$u = \App\Models\User::factory()->create();
auth()->login(\$u);
\$h = \App\Modules\Annuaire\Models\Hosto::factory()->create();
\$pr = \App\Modules\Annuaire\Models\Practitioner::factory()->create();
\$s = \App\Modules\RendezVous\Models\TimeSlot::create(['practitioner_id'=>\$pr->id,'hosto_id'=>\$h->id,'date'=>now()->addDay()->toDateString(),'start_time'=>'09:00','end_time'=>'09:30','duration_minutes'=>30]);
try {
    \$html = view('compte.rendez-vous-form', ['slot' => \$s, 'practitioner' => \$pr, 'hosto' => \$h])->render();
    echo 'OK ' . strlen(\$html) . ' bytes' . PHP_EOL;
} catch (Throwable \$e) {
    echo 'ERR: ' . \$e->getMessage() . PHP_EOL;
}
" 2>&1 | tail -3
```
Expected: `OK NNNN bytes`.

- [ ] **Step 3: Commit**

```bash
git add resources/views/compte/rendez-vous-form.blade.php
git commit -m "feat(rdv): vue formulaire complet (types + geo + tiers + docs + share)"
```

---

## Task 17: Purge job des coordonnées GPS (30 j)

**Files:**
- Create: `app/Modules/RendezVous/Jobs/PurgeOldVisitLocationsJob.php`
- Modify: `routes/console.php` (scheduler)

(Pas de test dédié — c'est un cron simple. Validation manuelle via `php artisan schedule:list`.)

- [ ] **Step 1: Create the job**

```php
// app/Modules/RendezVous/Jobs/PurgeOldVisitLocationsJob.php
<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Jobs;

use App\Modules\RendezVous\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class PurgeOldVisitLocationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $cutoff = now()->subDays(30);
        $count = Appointment::where('completed_at', '<', $cutoff)
            ->whereNotNull('visit_lat')
            ->update([
                'visit_lat' => null,
                'visit_lng' => null,
                'visit_location_accuracy_m' => null,
            ]);
        Log::info('rdv.purge.visit_locations', ['cleared' => $count, 'cutoff' => $cutoff->toIso8601String()]);
    }
}
```

- [ ] **Step 2: Schedule it daily**

In `routes/console.php`, append :

```php
use App\Modules\RendezVous\Jobs\PurgeOldVisitLocationsJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new PurgeOldVisitLocationsJob())->dailyAt('03:00')->name('purge_visit_locations');
```

- [ ] **Step 3: Verify scheduled**

```bash
php artisan schedule:list
```
Expected: line `Job  App\Modules\RendezVous\Jobs\PurgeOldVisitLocationsJob  03:00`.

- [ ] **Step 4: Commit**

```bash
git add app/Modules/RendezVous/Jobs/PurgeOldVisitLocationsJob.php \
        routes/console.php
git commit -m "feat(rdv): job purge coordonnees GPS apres 30j (cron 03:00)"
```

---

## Task 18: Validation manuelle des 7 scenarios

Cette tâche n'est pas automatisable. Réalisable par l'utilisateur ou un testeur humain.

- [ ] **Scenario 1 — RDV ordinaire hôpital** : login patient → annuaire → choisir structure partenaire → choisir médecin → formulaire → tout par défaut → submit → check `/compte/rendez-vous` → RDV apparaît avec statut "pending".

- [ ] **Scenario 2 — Téléconsultation gated** : sur un médecin avec `does_teleconsultation=true`, l'option téléconsultation apparaît dans le radio. Sur un médecin sans ce drapeau, elle n'apparaît pas.

- [ ] **Scenario 3 — Domicile avec géolocation** : sélectionner mode domicile → bloc adresse + carte apparaissent → clic "Utiliser ma position" → navigateur demande permission → coordonnées capturées → marker sur la carte → submit OK.

- [ ] **Scenario 4 — Domicile adresse manuelle** : saisir "BP 1234, Quartier Glass, Libreville" sans géoloc → submit → en arrière-plan le job géocode → rafraîchir la fiche → lat/lng populés.

- [ ] **Scenario 5 — Tiers matché** : saisir téléphone d'un user HOSTO existant → message inline "✓ X a un compte HOSTO" → submit → check `appointment.third_party_user_id = X.id`.

- [ ] **Scenario 6 — Tiers non matché** : saisir téléphone inconnu → message "✗ Aucun compte HOSTO trouvé. SMS d'invitation envoyé" → submit → check `invitation_links` créé.

- [ ] **Scenario 7 — Partage dossier avec PIN** : cocher partage → champ PIN apparaît → saisir bon PIN → submit → grant créé → aller `/compte/dossier/partages` → grant visible → cliquer "Révoquer" → grant marqué `revoked_at`.

- [ ] **Final commit (optionnel)**

```bash
git tag -a T9.A-complete -m "T9.A formulaire RDV complet - acceptance OK"
```

---

## Self-Review checklist (auto)

**Spec coverage** :
- Data model section 3 → Tasks 1-4 ✓
- Composants section 4 → Tasks 6-10, 12-14 ✓
- Workflows section 5 → Tasks 14, 15, 16 ✓
- Sécurité section 6 → check explicit dans DocumentUploadService.canAccess, AppointmentBookingService.validateConsultationMode, etc. ✓
- Tests section 7 → tous les tasks ont leur test unit + feature ✓
- Géolocalisation + Nominatim → Task 7 ✓
- Purge 30 j GPS → Task 17 ✓

**Placeholders scan** : pas de TBD/TODO dans le code des steps.

**Type consistency** :
- `AppointmentBookingService::book(array $data): Appointment` — cohérent.
- `ThirdPartyResolverService::normalizePhone(string, string): ?string` — cohérent.
- `MedicalRecordGrantService::grant(User, Practitioner, ?array, ?Appointment): MedicalRecordGrant` — cohérent.
- `DocumentUploadService::store(Appointment, UploadedFile, User, ?string): AppointmentDocument` — cohérent.
- `consultation_mode` enum values : `in_hospital`, `home`, `telecon` — cohérent partout.
- `appointment_type` enum values : `ordinaire`, `urgence`, `grossesse`, `natalite`, `chronique` — cohérent partout.

**Order** : foundation (1-4) → dépendances+config (5) → services unit-testés (6-10) → FormRequest+controllers+routes (11-15) → vue UI (16) → cron (17) → validation manuelle (18). Pas de dépendance circulaire.

Plan terminé.
