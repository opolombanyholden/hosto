<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Core\Traits\TracksActor;
use App\Modules\Referentiel\Models\Specialty;
use Carbon\CarbonImmutable;
use Database\Factories\Annuaire\PractitionerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Practitioner.
 *
 * @property int $id
 * @property string $uuid
 * @property int|null $user_id
 * @property string|null $title
 * @property string $first_name
 * @property string $last_name
 * @property string $slug
 * @property string|null $gender
 * @property string|null $registration_number
 * @property string $practitioner_type
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $bio_fr
 * @property string|null $bio_en
 * @property string|null $profile_image_url
 * @property array<int, string>|null $languages
 * @property int|null $consultation_fee_min
 * @property int|null $consultation_fee_max
 * @property bool $accepts_new_patients
 * @property bool $does_teleconsultation
 * @property bool $is_active
 * @property bool $is_verified
 * @property string $origin
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $full_name
 * @property-read User|null $user
 * @property-read Collection<int, Hosto> $structures
 * @property-read Collection<int, Specialty> $specialties
 */
class Practitioner extends Model
{
    /** @use HasFactory<PractitionerFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;
    use TracksActor;

    /** @var list<string> */
    protected $fillable = [
        'user_id', 'title', 'first_name', 'last_name', 'slug', 'gender',
        'registration_number', 'practitioner_type', 'phone', 'email',
        'bio_fr', 'bio_en', 'profile_image_url', 'languages',
        'consultation_fee_min', 'consultation_fee_max',
        'accepts_new_patients', 'does_teleconsultation',
        'is_active', 'is_verified',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->title ? $this->title.' ' : '').$this->first_name.' '.$this->last_name);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Hosto, $this>
     */
    public function structures(): BelongsToMany
    {
        return $this->belongsToMany(Hosto::class, 'practitioner_hosto')
            ->withPivot('role_in_structure', 'is_primary', 'display_order')
            ->orderByPivot('display_order');
    }

    /**
     * @return BelongsToMany<Specialty, $this>
     */
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'practitioner_specialty')
            ->withPivot('is_primary', 'display_order')
            ->orderByPivot('display_order');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('practitioners.is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('practitioners.practitioner_type', $type);
    }

    protected static function newFactory(): PractitionerFactory
    {
        return PractitionerFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'accepts_new_patients' => 'boolean',
            'does_teleconsultation' => 'boolean',
        ];
    }
}
