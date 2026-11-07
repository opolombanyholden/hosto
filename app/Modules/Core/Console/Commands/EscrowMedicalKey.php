<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use App\Modules\Core\Models\MedicalEncryptionKey;
use App\Modules\Core\Services\KeyEscrow\MedicalKeyEscrow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Splits the medical key into one sheet per custodian. Neither the key nor the
 * shares are printed in the console, so they do not end up in a terminal log.
 */
final class EscrowMedicalKey extends Command
{
    protected $signature = 'hosto:medical-key-escrow
        {--shares=5 : Nombre de depositaires}
        {--threshold=3 : Nombre de fragments necessaires pour reconstituer la cle}
        {--output= : Dossier ou ecrire les fiches (doit etre vide ou absent)}';

    protected $description = 'Met la cle de chiffrement medicale sous sequestre aupres de plusieurs depositaires.';

    public function handle(MedicalKeyEscrow $escrow): int
    {
        $keyId = $escrow->currentKeyId();

        if ($keyId === null) {
            $this->error('MEDICAL_ENCRYPTION_KEY est absente ou invalide : rien a mettre sous sequestre.');

            return self::FAILURE;
        }

        $shares = (int) $this->option('shares');
        $threshold = (int) $this->option('threshold');
        $dir = (string) ($this->option('output') ?: storage_path('app/private/key-escrow/'.$keyId.'-'.now()->format('Ymd-His')));

        if (File::isDirectory($dir) && File::files($dir) !== []) {
            $this->error("Le dossier {$dir} n'est pas vide.");

            return self::FAILURE;
        }

        try {
            $parts = $escrow->splitCurrentKey($shares, $threshold);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        File::ensureDirectoryExists($dir, 0700);
        chmod($dir, 0700);

        foreach ($parts as $x => $share) {
            $path = "{$dir}/fragment-{$x}-sur-{$shares}.txt";
            File::put($path, $this->sheet($share, $x, $shares, $threshold, $keyId));
            chmod($path, 0600);
        }

        MedicalEncryptionKey::updateOrCreate(['key_id' => $keyId], [
            'escrow_shares' => $shares,
            'escrow_threshold' => $threshold,
            'escrowed_at' => now(),
        ]);

        $this->info("{$shares} fiches ecrites dans {$dir}");
        $this->line("Il faudra {$threshold} fragments sur {$shares} pour reconstituer la cle {$keyId}.");
        $this->newLine();
        $this->warn('1. Imprimez chaque fiche et remettez-la sous enveloppe scellee a un depositaire different.');
        $this->warn('2. Supprimez ensuite ce dossier du serveur.');
        $this->warn("3. Faites un exercice : php artisan hosto:medical-key-recover --verify avec {$threshold} depositaires.");

        return self::SUCCESS;
    }

    private function sheet(string $share, int $x, int $shares, int $threshold, string $keyId): string
    {
        $date = now()->format('d/m/Y');
        $readable = trim(chunk_split(substr($share, strrpos($share, '-', -10) + 1, 64), 8, ' '));

        return <<<TXT
        HOSTO — FRAGMENT DE LA CLE DE CHIFFREMENT DES DONNEES MEDICALES
        ================================================================

        Fragment {$x} — il faut {$threshold} fragments sur {$shares} pour reconstituer la cle.
        Empreinte de la cle : {$keyId}        Etabli le : {$date}

        Depositaire : ____________________________   Signature : ____________

        FRAGMENT (a recopier en entier, sans espace) :

        {$share}

        Partie centrale, par groupes, pour la relecture :
        {$readable}

        ----------------------------------------------------------------
        CE QUE VOUS DEVEZ FAIRE
        - Conserver cette fiche sous enveloppe scellee, dans un coffre.
        - Ne jamais la photographier, la scanner, l'envoyer par courriel ou
          par messagerie, ni la stocker sur un ordinateur ou un telephone.
        - Signaler immediatement sa perte ou son vol au responsable securite :
          la cle devra alors etre remplacee.
        - Si vous quittez votre fonction, remettre la fiche a votre successeur
          designe, en presence d'un temoin.

        POURQUOI
        Sans cette cle, les donnees medicales de la plateforme sont
        definitivement illisibles. Un fragment seul ne revele rien de la cle :
        il faut {$threshold} depositaires reunis pour la reconstituer.

        RECONSTITUTION (procedure ADR 0017)
        En presence d'au moins {$threshold} depositaires et du responsable technique :
        php artisan hosto:medical-key-recover
        Chaque depositaire saisit lui-meme son fragment.

        TXT;
    }
}
