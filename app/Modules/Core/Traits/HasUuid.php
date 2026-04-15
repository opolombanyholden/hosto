<?php

declare(strict_types=1);

namespace App\Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * HasUuid.
 *
 * Ensures every persisted model has a UUIDv7 stored in the "uuid" column.
 * UUIDv7 preserves chronological order, giving us better index locality
 * than pure-random UUIDv4.
 *
 * Usage:
 *     use HasUuid;
 *
 *     // Optionally expose uuid as the route key:
 *     public function getRouteKeyName(): string { return 'uuid'; }
 *
 * @see docs/adr/0003-uuid-conventions-schema.md
 *
 * @method static Builder<static> whereUuid(string $uuid)
 */
trait HasUuid
{
    /**
     * Scope a query to an explicit uuid match.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWhereUuid(Builder $query, string $uuid): Builder
    {
        return $query->where($this->getTable().'.uuid', $uuid);
    }

    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model): void {
            /** @phpstan-ignore-next-line property.notFound */
            if (empty($model->uuid)) {
                /** @phpstan-ignore-next-line property.notFound */
                $model->uuid = (string) Str::uuid7();
            }
        });
    }
}
