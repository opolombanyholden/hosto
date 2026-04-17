<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Models\Concerns;

/**
 * HasBilingualName.
 *
 * Adds a computed `name` accessor that returns the best available
 * translation of the entity's name, based on the current app locale.
 *
 * Expected columns on the model:
 *   - name_fr        : required, default language
 *   - name_en        : required, English translation
 *   - name_local     : optional, native/local name
 *
 * Resolution order (for locale L):
 *   1. name_{L} if the column exists and is not empty
 *   2. name_local if non-empty
 *   3. name_fr (always present, mandatory)
 */
trait HasBilingualName
{
    /**
     * @phpstan-ignore-next-line method.childParameterType, method.childReturnType
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        $localeColumn = "name_{$locale}";
        /** @phpstan-ignore-next-line property.notFound */
        if (isset($this->attributes[$localeColumn]) && ! empty($this->attributes[$localeColumn])) {
            /** @phpstan-ignore-next-line property.notFound */
            return (string) $this->attributes[$localeColumn];
        }

        /** @phpstan-ignore-next-line property.notFound */
        if (! empty($this->attributes['name_local'] ?? null)) {
            /** @phpstan-ignore-next-line property.notFound */
            return (string) $this->attributes['name_local'];
        }

        /** @phpstan-ignore-next-line property.notFound */
        return (string) $this->attributes['name_fr'];
    }

    /**
     * Return the name in a specific locale, with cascading fallbacks.
     */
    public function nameIn(string $locale): string
    {
        $column = "name_{$locale}";

        /** @phpstan-ignore-next-line property.notFound */
        if (! empty($this->attributes[$column] ?? null)) {
            /** @phpstan-ignore-next-line property.notFound */
            return (string) $this->attributes[$column];
        }

        /** @phpstan-ignore-next-line property.notFound */
        return (string) $this->attributes['name_fr'];
    }
}
