<?php

declare(strict_types=1);

namespace App\Modules\Analytic\Http\Controllers;

use App\Modules\Analytic\Models\EpiAlert;
use App\Modules\Analytic\Models\PathologyStat;
use App\Modules\Analytic\Services\AnalyticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AnalyticController
{
    public function __construct(
        private readonly AnalyticService $analyticService,
    ) {}

    /**
     * Dashboard: national summary + top pathologies + active alerts.
     */
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'data' => [
                'summary' => $this->analyticService->nationalSummary(),
                'top_pathologies' => $this->analyticService->topPathologies(),
                'active_alerts' => $this->analyticService->activeAlerts(),
            ],
        ]);
    }

    /**
     * List pathology stats with optional filters, paginated.
     */
    public function pathologies(Request $request): JsonResponse
    {
        $query = PathologyStat::query();

        if ($request->filled('region')) {
            $query->where('region_id', $request->input('region'));
        }

        if ($request->filled('code')) {
            $query->where('diagnostic_code', $request->input('code'));
        }

        if ($request->filled('from')) {
            $query->where('date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('date', '<=', $request->input('to'));
        }

        return response()->json($query->paginate());
    }

    /**
     * List epidemiological alerts ordered by detected_at desc, paginated.
     */
    public function alerts(): JsonResponse
    {
        return response()->json(
            EpiAlert::query()
                ->orderByDesc('detected_at')
                ->paginate(),
        );
    }
}
