<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Models;

use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * HostoMedia.
 *
 * A media file attached to a health structure.
 *
 * @property int $id
 * @property string $uuid
 * @property int $hosto_id
 * @property string $type profile | cover | gallery
 * @property string $url
 * @property string|null $alt_text
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property int|null $width
 * @property int|null $height
 * @property bool $is_primary
 * @property int $display_order
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Hosto $hosto
 */
class HostoMedia extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'hosto_media';

    /** @var list<string> */
    protected $fillable = [
        'hosto_id',
        'type',
        'url',
        'alt_text',
        'mime_type',
        'file_size',
        'width',
        'height',
        'is_primary',
        'display_order',
    ];

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
            'is_primary' => 'boolean',
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'display_order' => 'integer',
        ];
    }
}
