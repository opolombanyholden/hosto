<?php

declare(strict_types=1);

namespace App\Modules\AI\Services;

use App\Modules\AI\Models\EpiPrediction;
use Illuminate\Database\Eloquent\Collection;

final class EpiPredictionService
{
    /**
     * Generate an epidemiological prediction.
     *
     * TODO: replace with real ML model integration.
     */
    public function predict(string $diagnosticCode, ?int $regionId, int $horizonDays = 14): EpiPrediction
    {
        // TODO: replace with actual ML model inference.
        $predictedCases = random_int(10, 500);
        $confidenceLower = (float) max(0, $predictedCases - random_int(5, 50));
        $confidenceUpper = (float) ($predictedCases + random_int(5, 50));

        return EpiPrediction::create([
            'model_name' => 'stub-arima',
            'model_version' => '0.1.0',
            'diagnostic_code' => $diagnosticCode,
            'region_id' => $regionId,
            'prediction_date' => now()->addDays($horizonDays),
            'horizon_days' => $horizonDays,
            'predicted_cases' => $predictedCases,
            'confidence_lower' => $confidenceLower,
            'confidence_upper' => $confidenceUpper,
            'accuracy_score' => null,
            'features_used' => ['historical_cases', 'seasonality', 'population_density'],
            'interpretation' => "Simulated prediction for diagnostic code {$diagnosticCode} over {$horizonDays} days.",
        ]);
    }

    /**
     * Return the latest predictions.
     *
     * @return Collection<int, EpiPrediction>
     */
    public function latestPredictions(int $limit = 10): Collection
    {
        return EpiPrediction::query()
            ->with('region')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
