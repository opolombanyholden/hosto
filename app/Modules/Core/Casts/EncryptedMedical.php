<?php

declare(strict_types=1);

namespace App\Modules\Core\Casts;

use App\Modules\Core\Services\MedicalCipher;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores an attribute encrypted, and exposes it in clear to the application.
 *
 * Usage in casts(): EncryptedMedical::class, or EncryptedMedical::class.':json'.
 * An encrypted column cannot be searched or sorted in SQL.
 *
 * @implements CastsAttributes<mixed, mixed>
 */
final class EncryptedMedical implements CastsAttributes
{
    public function __construct(private readonly ?string $format = null) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $plaintext = app(MedicalCipher::class)->decrypt((string) $value);

        return $this->format === 'json' ? json_decode($plaintext, true) : $plaintext;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $plaintext = $this->format === 'json' ? (string) json_encode($value) : (string) $value;

        return app(MedicalCipher::class)->encrypt($plaintext);
    }
}
