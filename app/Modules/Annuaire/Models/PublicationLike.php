<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $publication_id
 * @property int $user_id
 * @property-read PractitionerPublication $publication
 * @property-read User $user
 */
class PublicationLike extends Model
{
    public $timestamps = false;

    protected $fillable = ['publication_id', 'user_id'];

    /**
     * @return BelongsTo<PractitionerPublication, $this>
     */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(PractitionerPublication::class, 'publication_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
