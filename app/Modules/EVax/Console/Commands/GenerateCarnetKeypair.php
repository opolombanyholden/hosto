<?php
declare(strict_types=1);

namespace App\Modules\EVax\Console\Commands;

use Illuminate\Console\Command;

final class GenerateCarnetKeypair extends Command
{
    protected $signature = 'evax:generate-keypair
        {--out= : Output directory (default: storage/keys)}
        {--kid= : Key identifier to embed in the output (default: current config kid)}';

    protected $description = 'Generates an EC P-256 keypair for the carnet de vaccination JWS signing.';

    public function handle(): int
    {
        $outDir = $this->option('out') ?: storage_path('keys');
        $kid = $this->option('kid') ?: config('hosto.carnet.kid', 'hosto-dev-2026');

        if (! is_dir($outDir) && ! mkdir($outDir, 0700, true) && ! is_dir($outDir)) {
            $this->error("Cannot create directory: {$outDir}");
            return self::FAILURE;
        }

        $config = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
        $res = openssl_pkey_new($config);
        if ($res === false) {
            $this->error('Failed to generate keypair: '.openssl_error_string());
            return self::FAILURE;
        }

        openssl_pkey_export($res, $privPem);
        $details = openssl_pkey_get_details($res);
        $pubPem = $details['key'];

        $privPath = $outDir.'/carnet-private.pem';
        $pubPath = $outDir.'/carnet-public.pem';

        file_put_contents($privPath, $privPem);
        chmod($privPath, 0600);
        file_put_contents($pubPath, $pubPem);
        chmod($pubPath, 0644);

        $this->info("Keypair generated for kid: {$kid}");
        $this->info("Private key: {$privPath}");
        $this->info("Public key : {$pubPath}");
        $this->newLine();
        $this->info('Add to your .env :');
        $this->line("CARNET_KEY_ID={$kid}");
        $this->line("CARNET_PRIVATE_KEY_PATH={$privPath}");
        $this->line('CARNET_PUBLIC_KEY_CURRENT="'.trim(str_replace(["\n", "\r"], '\\n', $pubPem)).'"');

        return self::SUCCESS;
    }
}
