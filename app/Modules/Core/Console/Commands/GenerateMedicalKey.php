<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use Illuminate\Console\Command;

final class GenerateMedicalKey extends Command
{
    protected $signature = 'hosto:medical-key {--write : Ajoute la cle au fichier .env si elle n\'y est pas}';

    protected $description = 'Genere une cle de chiffrement des donnees medicales.';

    public function handle(): int
    {
        $key = 'base64:'.base64_encode(random_bytes(32));

        if (! $this->option('write')) {
            $this->line($key);

            return self::SUCCESS;
        }

        $path = base_path('.env');
        $env = is_file($path) ? (string) file_get_contents($path) : '';

        if (preg_match('/^MEDICAL_ENCRYPTION_KEY=.+$/m', $env) === 1) {
            $this->error('Une cle existe deja dans .env : elle n\'a pas ete remplacee.');

            return self::FAILURE;
        }

        file_put_contents($path, rtrim($env)."\n\nMEDICAL_ENCRYPTION_KEY={$key}\n");

        $this->info('Cle ajoutee a .env.');
        $this->warn('Sauvegardez-la hors du serveur : sans elle, les donnees medicales sont irrecuperables.');

        return self::SUCCESS;
    }
}
