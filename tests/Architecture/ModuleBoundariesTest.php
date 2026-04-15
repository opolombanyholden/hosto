<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * Architecture rules enforced as tests.
 *
 * These tests fail the build if anyone (humans or AI) breaks the
 * structural rules HOSTO depends on for long-term maintainability.
 *
 * @see docs/adr/0001-architecture-monolithique-modulaire.md
 */
final class ModuleBoundariesTest extends TestCase
{
    private const MODULES_PATH = __DIR__.'/../../app/Modules';

    /**
     * A module may only depend on Core and on itself.
     *
     * Cross-module access (App\Modules\A use App\Modules\B\...) is
     * forbidden. Inter-module communication MUST go through public
     * service contracts (interfaces) registered in the container.
     */
    public function test_modules_only_depend_on_core_or_themselves(): void
    {
        if (! is_dir(self::MODULES_PATH)) {
            $this->markTestSkipped('No modules yet.');
        }

        $violations = [];

        foreach ($this->discoverModules() as $module) {
            $finder = (new Finder)
                ->files()
                ->in(self::MODULES_PATH.'/'.$module)
                ->name('*.php');

            foreach ($finder as $file) {
                $content = file_get_contents($file->getPathname()) ?: '';
                preg_match_all(
                    '/^\s*use\s+App\\\\Modules\\\\([A-Z][A-Za-z0-9_]*)\\\\/m',
                    $content,
                    $matches,
                );

                foreach ($matches[1] as $usedModule) {
                    if ($usedModule === $module || $usedModule === 'Core') {
                        continue;
                    }

                    $violations[] = sprintf(
                        '%s imports App\\Modules\\%s\\... (forbidden, use a Core contract)',
                        $this->relative($file),
                        $usedModule,
                    );
                }
            }
        }

        $this->assertSame([], $violations, "Cross-module imports detected:\n".implode("\n", $violations));
    }

    /**
     * Every module MUST expose a {Module}ServiceProvider so that
     * ModuleServiceProvider can autowire it.
     */
    public function test_every_module_exposes_a_service_provider(): void
    {
        if (! is_dir(self::MODULES_PATH)) {
            $this->markTestSkipped('No modules yet.');
        }

        $missing = [];

        foreach ($this->discoverModules() as $module) {
            $expected = self::MODULES_PATH."/{$module}/Providers/{$module}ServiceProvider.php";
            if (! file_exists($expected)) {
                $missing[] = "{$module} is missing Providers/{$module}ServiceProvider.php";
            }
        }

        $this->assertSame([], $missing, implode("\n", $missing));
    }

    /**
     * Models in app/Modules MUST extend Eloquent and use HasUuid trait.
     * (Inspected via static text matching to avoid loading the framework.)
     */
    public function test_module_models_use_has_uuid_trait(): void
    {
        if (! is_dir(self::MODULES_PATH)) {
            $this->markTestSkipped('No modules yet.');
        }

        $violations = [];

        foreach ($this->discoverModules() as $module) {
            $modelsPath = self::MODULES_PATH."/{$module}/Models";
            if (! is_dir($modelsPath)) {
                continue;
            }

            $finder = (new Finder)->files()->in($modelsPath)->name('*.php');
            foreach ($finder as $file) {
                $content = file_get_contents($file->getPathname()) ?: '';

                // Skip abstract base classes and interfaces.
                if (preg_match('/\babstract\s+class\b/', $content) || str_contains($content, 'interface ')) {
                    continue;
                }

                if (! str_contains($content, 'HasUuid')) {
                    $violations[] = $this->relative($file).' does not use HasUuid trait';
                }
            }
        }

        $this->assertSame([], $violations, implode("\n", $violations));
    }

    /**
     * @return array<int, string>
     */
    private function discoverModules(): array
    {
        $modules = [];
        foreach (scandir(self::MODULES_PATH) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (is_dir(self::MODULES_PATH.'/'.$entry)) {
                $modules[] = $entry;
            }
        }

        return $modules;
    }

    private function relative(SplFileInfo $file): string
    {
        return str_replace(realpath(__DIR__.'/../..').'/', '', (string) $file->getRealPath());
    }
}
