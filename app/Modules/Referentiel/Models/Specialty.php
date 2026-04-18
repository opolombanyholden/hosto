<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Models;

use App\Modules\Core\Traits\HasUuid;
use App\Modules\Referentiel\Models\Concerns\HasBilingualName;
use Carbon\CarbonImmutable;
use Database\Factories\Referentiel\SpecialtyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Specialty.
 *
 * Medical specialty (cardiologie, pediatrie, chirurgie, etc.).
 * Organized as a self-referencing tree: parent_id → sub-specialties.
 *
 * @property int $id
 * @property string $uuid
 * @property string $code
 * @property string $name_fr
 * @property string $name_en
 * @property string|null $name_local
 * @property string|null $description_fr
 * @property string|null $description_en
 * @property int|null $parent_id
 * @property bool $is_active
 * @property int $display_order
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $name
 * @property-read Specialty|null $parent
 * @property-read Collection<int, Specialty> $children
 */
class Specialty extends Model
{
    use HasBilingualName;

    /** @use HasFactory<SpecialtyFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;

    protected $table = 'specialties';

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name_fr',
        'name_en',
        'name_local',
        'description_fr',
        'description_en',
        'parent_id',
        'is_active',
        'display_order',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Specialty, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Specialty, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Top-level specialties only (no parent).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    protected static function newFactory(): SpecialtyFactory
    {
        return SpecialtyFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }
}
