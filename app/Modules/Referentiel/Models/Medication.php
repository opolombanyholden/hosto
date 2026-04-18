<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Models;

use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Database\Factories\Referentiel\MedicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Medication (generic drug by DCI).
 *
 * @property int $id
 * @property string $uuid
 * @property string $dci
 * @property string|null $dci_en
 * @property string|null $therapeutic_class
 * @property string|null $therapeutic_class_en
 * @property string|null $dosage_form
 * @property string|null $dosage_form_en
 * @property string|null $strength
 * @property string|null $description_fr
 * @property string|null $description_en
 * @property bool $prescription_required
 * @property bool $is_active
 * @property int $display_order
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $name
 * @property-read Collection<int, MedicationBrand> $brands
 */
class Medication extends Model
{
    /** @use HasFactory<MedicationFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'dci', 'dci_en', 'therapeutic_class', 'therapeutic_class_en',
        'dosage_form', 'dosage_form_en', 'strength',
        'description_fr', 'description_en',
        'prescription_required', 'is_active', 'display_order',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Name accessor: returns DCI in the current locale.
     */
    public function getNameAttribute(): string
    {
        if (app()->getLocale() === 'en' && ! empty($this->dci_en)) {
            return $this->dci_en;
        }

        return $this->dci;
    }

    /**
     * @return HasMany<MedicationBrand, $this>
     */
    public function brands(): HasMany
    {
        return $this->hasMany(MedicationBrand::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): MedicationFactory
    {
        return MedicationFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prescription_required' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }
}
