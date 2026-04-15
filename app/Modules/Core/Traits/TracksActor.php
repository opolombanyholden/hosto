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
 */
trait TracksActor
{
    protected static function bootTracksActor(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->created_by) && ($actor = self::currentActorUuid()) !== null) {
                $model->created_by = $actor;
            }
            if (empty($model->updated_by) && ($actor = self::currentActorUuid()) !== null) {
                $model->updated_by = $actor;
            }
        });

        static::updating(function (Model $model): void {
            if (($actor = self::currentActorUuid()) !== null) {
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

        // Models must expose "uuid" attribute (via HasUuid).
        return $user->uuid ?? null;
    }
}
