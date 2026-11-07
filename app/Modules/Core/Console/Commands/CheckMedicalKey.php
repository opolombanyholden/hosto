<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use App\Modules\Core\Models\MedicalEncryptionKey;
use App\Modules\Core\Services\KeyEscrow\MedicalKeyEscrow;
use App\Modules\Core\Services\MedicalCipher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Daily safety net: every encrypted value must be readable with an available
 * key, and the key in service must be under escrow.
 */
final class CheckMedicalKey extends Command
{
    protected $signature = 'hosto:medical-key-check';

    protected $description = 'Verifie que les donnees medicales restent dechiffrables et que la cle est sous sequestre.';

    public function handle(MedicalKeyEscrow $escrow, MedicalCipher $cipher): int
    {
        $problems = [];
        $current = $cipher->currentKeyId();

        if ($current === null) {
            $problems[] = 'MEDICAL_ENCRYPTION_KEY est absente ou invalide.';
        }

        foreach (array_diff($escrow->keyIdsInData(), $cipher->availableKeyIds()) as $missing) {
            $problems[] = "Des donnees sont illisibles : elles sont chiffrees avec la cle {$missing}, absente de la configuration.";
        }

        if ($current !== null && MedicalEncryptionKey::where('key_id', $current)->whereNotNull('escrowed_at')->doesntExist()) {
            $problems[] = "La cle en service ({$current}) n'est pas sous sequestre : lancez hosto:medical-key-escrow.";
        }

        if ($problems === []) {
            $this->info("Cle {$current} disponible et sous sequestre, toutes les donnees sont dechiffrables.");

            return self::SUCCESS;
        }

        foreach ($problems as $problem) {
            $this->error($problem);
        }

        Log::critical('Securite de la cle de chiffrement medicale', ['problemes' => $problems]);

        return self::FAILURE;
    }
}
