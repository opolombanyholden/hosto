<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use App\Modules\Core\Models\MedicalEncryptionKey;
use App\Modules\Core\Services\KeyEscrow\InvalidShare;
use App\Modules\Core\Services\KeyEscrow\MedicalKeyEscrow;
use App\Modules\Core\Services\MedicalCipher;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Rebuilds the medical key from custodian shares, typed in by the custodians.
 * With --verify it only confirms that the shares are valid (drill).
 */
final class RecoverMedicalKey extends Command
{
    protected $signature = 'hosto:medical-key-recover
        {--verify : Exercice : verifie les fragments sans afficher la cle}
        {--write : Ecrit la cle reconstituee dans .env si aucune cle n\'y figure}';

    protected $description = 'Reconstitue la cle de chiffrement medicale a partir des fragments des depositaires.';

    public function handle(MedicalKeyEscrow $escrow, MedicalCipher $cipher): int
    {
        if (MedicalEncryptionKey::whereNotNull('escrowed_at')->doesntExist()) {
            $this->error('Aucune cle n\'a encore ete mise sous sequestre : il n\'y a aucun fragment a saisir.');
            $this->line('Lancez d\'abord : php artisan hosto:medical-key-escrow');

            return self::FAILURE;
        }

        $parsed = [];
        $needed = null;

        while ($needed === null || count($parsed) < $needed) {
            $input = (string) $this->secret('Fragment '.(count($parsed) + 1));

            try {
                $share = $escrow->parse($input);
            } catch (InvalidShare $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            if ($parsed !== [] && $share['key_id'] !== $parsed[0]['key_id']) {
                $this->error('Ce fragment appartient a une autre cle ('.$share['key_id'].' au lieu de '.$parsed[0]['key_id'].').');

                return self::FAILURE;
            }

            if (in_array($share['x'], array_column($parsed, 'x'), true)) {
                $this->error('Ce fragment a deja ete saisi.');

                return self::FAILURE;
            }

            $parsed[] = $share;
            $needed ??= $share['threshold'];
        }

        try {
            $raw = $escrow->rebuild($parsed);
        } catch (InvalidShare|InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $keyId = MedicalCipher::keyIdOf($raw);
        $record = MedicalEncryptionKey::where('key_id', $keyId)->first();

        if ($record === null) {
            $this->error("La cle reconstituee ({$keyId}) est inconnue du registre : rien n'est ecrit.");

            return self::FAILURE;
        }

        $record->update(['last_verified_at' => now()]);
        $key = 'base64:'.base64_encode($raw);

        $sample = $escrow->sampleValueFor($keyId);
        if ($sample !== null) {
            (new MedicalCipher($key))->decrypt($sample);
            $this->info('Cle verifiee : une donnee medicale reelle a ete dechiffree.');
        } else {
            $this->line('Aucune donnee chiffree avec cette cle pour l\'instant.');
        }

        if ($this->option('verify')) {
            $this->info($cipher->currentKeyId() === $keyId
                ? "Exercice reussi : les fragments correspondent a la cle en service ({$keyId})."
                : "Exercice reussi : les fragments reconstituent la cle {$keyId}, qui n'est pas la cle en service.");

            return self::SUCCESS;
        }

        if ($this->option('write')) {
            return $this->writeEnv($key);
        }

        $this->warn('Cle reconstituee. Copiez-la dans MEDICAL_ENCRYPTION_KEY, puis effacez ce terminal :');
        $this->line($key);

        return self::SUCCESS;
    }

    private function writeEnv(string $key): int
    {
        $path = base_path('.env');
        $env = is_file($path) ? (string) file_get_contents($path) : '';

        if (preg_match('/^MEDICAL_ENCRYPTION_KEY=.+$/m', $env) === 1) {
            $this->error('Une cle figure deja dans .env : elle n\'a pas ete remplacee.');

            return self::FAILURE;
        }

        file_put_contents($path, rtrim($env)."\n\nMEDICAL_ENCRYPTION_KEY={$key}\n");
        $this->info('Cle ecrite dans .env.');

        return self::SUCCESS;
    }
}
