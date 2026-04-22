<?php

declare(strict_types=1);

namespace App\Modules\AI\Providers;

use App\Modules\AI\Services\ChatbotService;
use App\Modules\AI\Services\EpiPredictionService;
use App\Modules\AI\Services\OcrService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OcrService::class);
        $this->app->singleton(ChatbotService::class);
        $this->app->singleton(EpiPredictionService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $routesFile = __DIR__.'/../Routes/api.php';
        if (file_exists($routesFile)) {
            Route::middleware('api')
                ->prefix('api/'.config('hosto.api.current_version').'/ai')
                ->name('ai.api.')
                ->group($routesFile);
        }
    }
}
