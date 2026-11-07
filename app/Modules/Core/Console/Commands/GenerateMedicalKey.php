<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use App\Modules\Core\Services\KeyEscrow\MedicalKeyEscrow;
use Illuminate\Console\Command;

final class GenerateMedicalKey extends Command
{
    protected $signature = 'hosto:medical-key
        {--write : Ajoute la cle au fichier .env si elle n\'y est pas}
        {--rotate : Genere la cle suivante pour une rotation, alors que des donnees sont deja chiffrees}';

    protected $description = 'Genere une cle de chiffrement des donnees medicales.';

    public function handle(MedicalKeyEscrow $escrow): int
    {
        // Generating a fresh key over encrypted data is how data gets lost:
        // the right move is to recover the existing key from its custodians.
        if (! $this->option('rotate') && $escrow->keyIdsInData() !== []) {
            $this->error('Des donnees medicales sont deja chiffrees : ne creez pas de nouvelle cle.');
            $this->line('Cle perdue sur ce serveur : php artisan hosto:medical-key-recover');
            $this->line('Rotation volontaire : php artisan hosto:medical-key --rotate (voir ADR 0016).');

            return self::FAILURE;
        }

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

        if ($this->option('rotate')) {
            $this->error('En rotation, placez vous-meme la nouvelle cle et l\'ancienne dans MEDICAL_ENCRYPTION_PREVIOUS_KEYS (ADR 0016).');

            return self::FAILURE;
        }

        file_put_contents($path, rtrim($env)."\n\nMEDICAL_ENCRYPTION_KEY={$key}\n");

        $this->info('Cle ajoutee a .env.');
        $this->warn('Mettez-la sous sequestre maintenant : php artisan hosto:medical-key-escrow');

        return self::SUCCESS;
    }
}
