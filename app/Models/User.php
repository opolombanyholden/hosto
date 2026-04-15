<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @property string|null $remember_token
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property string|null $created_by
 * @property string|null $updated_by
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
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
            'password' => 'hashed',
        ];
    }
}
