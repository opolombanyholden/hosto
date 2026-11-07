<?php

declare(strict_types=1);

namespace App\Modules\Core\Exceptions;

use RuntimeException;

/**
 * Raised instead of storing medical data in clear when no valid key is set.
 */
final class MedicalEncryptionKeyMissing extends RuntimeException
{
    public static function make(): self
    {
        return new self('MEDICAL_ENCRYPTION_KEY est absente ou invalide : aucune donnee medicale ne peut etre traitee.');
    }
}
