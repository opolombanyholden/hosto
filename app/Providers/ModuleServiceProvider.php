<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * ModuleServiceProvider.
 *
 * Auto-discovers HOSTO modules in app/Modules/ and registers their
 * ServiceProvider if present.
 *
 * Convention: each module MUST expose a Providers\{Module}ServiceProvider.
 *
 * Activation/deactivation per environment via config('hosto.modules').
 *
 * @see docs/adr/0001-architecture-monolithique-modulaire.md
 */
final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $modulesPath = app_path('Modules');

        if (! File::isDirectory($modulesPath)) {
            return;
        }

        $enabledModules = config('hosto.modules', []);

        foreach (File::directories($modulesPath) as $moduleDir) {
            $moduleName = basename($moduleDir);

            // Core is always enabled; others follow config.
            if ($moduleName !== 'Core' && ! ($enabledModules[$moduleName] ?? false)) {
                continue;
            }

            $providerClass = "App\\Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";

            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }
}
