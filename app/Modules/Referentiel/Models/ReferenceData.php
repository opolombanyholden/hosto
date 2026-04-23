<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $category
 * @property string $code
 * @property string $label_fr
 * @property string|null $label_en
 * @property array<string, mixed>|null $metadata
 * @property bool $is_active
 * @property int $display_order
 */
class ReferenceData extends Model
{
    protected $table = 'reference_data';

    protected $fillable = [
        'category', 'code', 'label_fr', 'label_en',
        'metadata', 'is_active', 'display_order',
    ];

    /**
     * Get all active items for a category, ordered by display_order.
     *
     * @return Collection<int, self>
     */
    public static function forCategory(string $category): Collection
    {
        return self::where('category', $category)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('label_fr')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            'id_document_type' => 'Types de piece d\'identite',
            'security_question' => 'Questions secretes',
            'blood_group' => 'Groupes sanguins',
            'gender' => 'Sexes',
            'contact_relation' => 'Liens familiaux',
            'publication_type' => 'Types de publication',
            'care_type' => 'Types de soins',
            'treatment_type' => 'Types de traitements',
            'urgency_level' => 'Niveaux d\'urgence',
            'insurance_provider' => 'Assureurs',
            'country_code' => 'Indicatifs telephoniques',
        ];
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
