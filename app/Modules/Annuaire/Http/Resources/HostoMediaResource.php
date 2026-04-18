<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Http\Resources;

use App\Modules\Annuaire\Models\HostoMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HostoMedia
 */
final class HostoMediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'url' => $this->url,
            'alt_text' => $this->alt_text,
            'is_primary' => $this->is_primary,
        ];
    }
}
