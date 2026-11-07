<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Paginates a query, filtering in PHP when a search term is given.
 *
 * For encrypted columns, which SQL cannot search. Meant for small sets, such
 * as the records of a single patient.
 */
final class SearchablePaginator
{
    /**
     * @template TRecord of Model
     *
     * @param  Builder<TRecord>  $query
     * @param  callable(TRecord, string): bool  $matches
     * @return LengthAwarePaginator<int, TRecord>
     */
    public static function paginate(Request $request, Builder $query, callable $matches): LengthAwarePaginator
    {
        $perPage = $request->integer('per_page', 5);

        if (! $request->filled('q')) {
            return $query->paginate($perPage);
        }

        $term = (string) $request->input('q');
        $matchingKeys = (clone $query)->get()
            ->filter(fn ($record): bool => $matches($record, $term))
            ->modelKeys();

        return $query->whereKey($matchingKeys)->paginate($perPage);
    }
}
