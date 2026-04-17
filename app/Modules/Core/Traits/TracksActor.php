<?php

declare(strict_types=1);

namespace App\Modules\Core\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * TracksActor.
 *
 * Automatically fills created_by / updated_by with the authenticated
 * user's UUID on create and update operations.
 *
 * System-initiated writes (seeders, jobs without actor) leave NULL,
 * which is how audit distinguishes user vs system actions.
 *
 * @phpstan-require-extends Model
 */
trait TracksActor
{
    protected static function bootTracksActor(): void
    {
        static::creating(function (Model $model): void {
            /** @phpstan-ignore property.notFound */
            if (empty($model->created_by) && ($actor = self::currentActorUuid()) !== null) {
                /** @phpstan-ignore property.notFound */
                $model->created_by = $actor;
            }
            /** @phpstan-ignore property.notFound */
            if (empty($model->updated_by) && ($actor = self::currentActorUuid()) !== null) {
                /** @phpstan-ignore property.notFound */
                $model->updated_by = $actor;
            }
        });

        static::updating(function (Model $model): void {
            if (($actor = self::currentActorUuid()) !== null) {
                /** @phpstan-ignore property.notFound */
                $model->updated_by = $actor;
            }
        });
    }

    private static function currentActorUuid(): ?string
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        /** @phpstan-ignore property.notFound */
        return $user->uuid ?? null;
    }
}
