<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Str;

/**
 * Accent- and case-insensitive "contains" search on PostgreSQL.
 *
 * "paracétamol" must find "Paracetamol", and the other way round: French
 * speakers type accents inconsistently, especially on a phone.
 */
final class AccentInsensitive
{
    private const FROM = 'àáâãäåçèéêëìíîïñòóôõöùúûüýÿ';

    private const TO = 'aaaaaaceeeeiiiinooooouuuuyy';

    public static function contains(Builder $query, string $column, string $term, string $boolean = 'and'): Builder
    {
        $pattern = '%'.addcslashes(mb_strtolower(Str::ascii($term)), '\\%_').'%';

        return $query->whereRaw(
            "translate(lower({$column}), ?, ?) like ?",
            [self::FROM, self::TO, $pattern],
            $boolean,
        );
    }
}
