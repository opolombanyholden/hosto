<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use App\Modules\Core\Services\MedicalCipher;
use App\Modules\Core\Support\MedicalFields;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Encrypts rows and files not encrypted yet, and moves data encrypted with a
 * previous key to the current one. Safe to run again.
 */
final class EncryptMedicalData extends Command
{
    protected $signature = 'hosto:medical-encrypt {--dry-run : Compte sans rien modifier}';

    protected $description = 'Chiffre les donnees medicales existantes et applique la rotation de cle.';

    public function handle(MedicalCipher $cipher): int
    {
        if (! $cipher->isReady()) {
            $this->error('MEDICAL_ENCRYPTION_KEY est absente ou invalide. Rien n\'a ete modifie.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $values = 0;

        foreach (MedicalFields::COLUMNS as $table => $columns) {
            $count = $this->processTable($cipher, $table, $columns, $dryRun);
            $values += $count;
            $this->line(sprintf('%-22s %d valeur(s)', $table, $count));
        }

        $files = $this->processFiles($cipher, $dryRun);
        $this->line(sprintf('%-22s %d fichier(s)', 'documents', $files));

        $this->info(sprintf(
            '%s : %d valeur(s) et %d fichier(s).',
            $dryRun ? 'A chiffrer' : 'Chiffres',
            $values,
            $files,
        ));

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $columns
     */
    private function processTable(MedicalCipher $cipher, string $table, array $columns, bool $dryRun): int
    {
        $count = 0;

        DB::table($table)->select(array_merge(['id'], $columns))->orderBy('id')->chunkById(200, function ($rows) use ($cipher, $table, $columns, $dryRun, &$count): void {
            foreach ($rows as $row) {
                $changes = [];

                foreach ($columns as $column) {
                    $value = $row->{$column};

                    if ($value !== null && ! $cipher->isCurrent((string) $value)) {
                        $changes[$column] = $cipher->encrypt($cipher->decrypt((string) $value));
                    }
                }

                $count += count($changes);

                if ($changes !== [] && ! $dryRun) {
                    DB::table($table)->where('id', $row->id)->update($changes);
                }
            }
        });

        return $count;
    }

    private function processFiles(MedicalCipher $cipher, bool $dryRun): int
    {
        $disk = Storage::disk('private_appointments');
        $count = 0;

        foreach (DB::table('appointment_documents')->pluck('stored_path') as $storedPath) {
            $path = str_replace('appointments/', '', (string) $storedPath);

            if (! $disk->exists($path)) {
                continue;
            }

            $contents = (string) $disk->get($path);

            if ($cipher->isCurrent($contents)) {
                continue;
            }

            $count++;

            if (! $dryRun) {
                $disk->put($path, $cipher->encrypt($cipher->decrypt($contents)));
            }
        }

        return $count;
    }
}
