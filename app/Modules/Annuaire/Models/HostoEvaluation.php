<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * HostoEvaluation.
 *
 * PRIVATE evaluation — visible ONLY to:
 *   - The structure owner (via pro dashboard)
 *   - HOSTO administrators
 *   - Ministry of Health (via Hosto Analytic)
 *
 * NEVER displayed publicly to patients.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $hosto_id
 * @property int|null $score_accueil
 * @property int|null $score_proprete
 * @property int|null $score_competence
 * @property int|null $score_delai
 * @property int $score_global
 * @property string|null $comment
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $user
 * @property-read Hosto $hosto
 */
class HostoEvaluation extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'hosto_id',
        'score_accueil', 'score_proprete', 'score_competence', 'score_delai', 'score_global',
        'comment',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Hosto, $this>
     */
    public function hosto(): BelongsTo
    {
        return $this->belongsTo(Hosto::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score_accueil' => 'integer',
            'score_proprete' => 'integer',
            'score_competence' => 'integer',
            'score_delai' => 'integer',
            'score_global' => 'integer',
        ];
    }
}
