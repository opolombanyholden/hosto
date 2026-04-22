<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Core\Models\EmergencyContact;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User.
 *
 * Base account entity. Subtyped in later phases by role:
 *   - Phase 3 : Patient profile linked via 1-1
 *   - Phase 5 : Professionnel profile linked via 1-1
 *   - Phase 0 : basic admin / system accounts
 *
 * UUID is the externally exposed identifier.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property CarbonImmutable|null $email_verified_at
 * @property CarbonImmutable|null $phone_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property int $failed_login_attempts
 * @property CarbonImmutable|null $locked_until
 * @property CarbonImmutable|null $pro_validated_at
 * @property string|null $pro_validated_by
 * @property string|null $pro_validation_status
 * @property string|null $pro_rejection_reason
 * @property string|null $remember_token
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property string|null $nip
 * @property string|null $id_document_type
 * @property string|null $id_document_number
 * @property \Carbon\CarbonImmutable|null $date_of_birth
 * @property string|null $gender
 * @property string|null $blood_group
 * @property string|null $country_of_residence
 * @property string|null $city_of_residence
 * @property string|null $address_of_residence
 * @property string|null $profile_photo_path
 * @property string|null $security_question
 * @property string|null $security_answer
 * @property string|null $medical_pin
 * @property CarbonImmutable|null $medical_pin_set_at
 * @property CarbonImmutable|null $profile_completed_at
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, \App\Modules\Core\Models\EmergencyContact> $emergencyContacts
 */
#[Fillable([
    'name', 'email', 'phone', 'password',
    'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
    'nip', 'id_document_type', 'id_document_number',
    'date_of_birth', 'gender', 'blood_group',
    'country_of_residence', 'city_of_residence', 'address_of_residence',
    'profile_photo_path', 'security_question', 'security_answer',
    'medical_pin', 'medical_pin_set_at', 'profile_completed_at',
])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'security_answer', 'medical_pin'])]
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasUuid;
    use Notifiable;
    use SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ---------------------------------------------------------------
    // Roles
    // ---------------------------------------------------------------

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function hasAnyRole(string ...$slugs): bool
    {
        return $this->roles->whereIn('slug', $slugs)->isNotEmpty();
    }

    /**
     * Check if user can access a given environment.
     */
    public function canAccessEnvironment(string $environment): bool
    {
        return $this->roles->contains('environment', $environment);
    }

    // ---------------------------------------------------------------
    // Emergency contacts
    // ---------------------------------------------------------------

    /**
     * @return HasMany<EmergencyContact, $this>
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class)->orderBy('priority');
    }

    // ---------------------------------------------------------------
    // Profile helpers
    // ---------------------------------------------------------------

    /**
     * Check if the user has completed all required profile sections.
     */
    public function isProfileComplete(): bool
    {
        return $this->profile_completed_at !== null;
    }

    /**
     * Percentage of profile completion (0–100).
     */
    public function profileCompletionPercent(): int
    {
        $checks = [
            $this->email_verified_at !== null,
            $this->phone_verified_at !== null,
            $this->nip !== null || $this->id_document_number !== null,
            $this->date_of_birth !== null && $this->gender !== null,
            $this->country_of_residence !== null,
            $this->security_question !== null,
            $this->medical_pin !== null,
            $this->emergencyContacts()->exists(),
        ];

        return (int) round(array_sum(array_map('intval', $checks)) / count($checks) * 100);
    }

    public function hasMedicalPin(): bool
    {
        return $this->medical_pin !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'phone_verified_at' => 'immutable_datetime',
            'two_factor_confirmed_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
            'medical_pin_set_at' => 'immutable_datetime',
            'profile_completed_at' => 'immutable_datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
        ];
    }
}
