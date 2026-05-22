<?php
declare(strict_types=1);

namespace App\Modules\EVax\Models;

use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property string $code
 * @property string|null $oms_code
 * @property string $name_fr
 * @property string|null $name_en
 * @property string|null $manufacturer
 * @property array<int, string>|null $diseases
 * @property int|null $schedule_age_days
 * @property int $doses_total
 * @property bool $is_standardized
 * @property int $display_order
 * @property bool $is_active
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class Vaccine extends Model
{
    use HasUuid;

    protected $fillable = [
        'code', 'oms_code', 'name_fr', 'name_en', 'manufacturer',
        'diseases', 'schedule_age_days', 'doses_total',
        'is_standardized', 'display_order', 'is_active',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'diseases' => 'array',
            'is_standardized' => 'boolean',
            'is_active' => 'boolean',
            'doses_total' => 'integer',
            'display_order' => 'integer',
            'schedule_age_days' => 'integer',
        ];
    }
}
