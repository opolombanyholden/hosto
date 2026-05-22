<?php
declare(strict_types=1);

namespace Tests\Feature\EVax;

use Tests\TestCase;

final class GenerateCarnetKeypairTest extends TestCase
{
    public function test_command_generates_keypair_files(): void
    {
        $tmpDir = sys_get_temp_dir().'/evax-keytest-'.uniqid();
        mkdir($tmpDir);

        $this->artisan('evax:generate-keypair', ['--out' => $tmpDir, '--kid' => 'test-kid'])
            ->expectsOutputToContain('test-kid')
            ->assertSuccessful();

        $this->assertFileExists($tmpDir.'/carnet-private.pem');
        $this->assertFileExists($tmpDir.'/carnet-public.pem');

        $priv = file_get_contents($tmpDir.'/carnet-private.pem');
        $pub = file_get_contents($tmpDir.'/carnet-public.pem');
        $this->assertStringContainsString('PRIVATE KEY', $priv);
        $this->assertStringContainsString('PUBLIC KEY', $pub);

        @unlink($tmpDir.'/carnet-private.pem');
        @unlink($tmpDir.'/carnet-public.pem');
        @rmdir($tmpDir);
    }
}
