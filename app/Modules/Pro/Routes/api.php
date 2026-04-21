<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pro Module — API routes (placeholder for Phase 5 mobile)
|--------------------------------------------------------------------------
|
| Mounted under /api/v1/pro
| Full API implementation for Flutter will be added when mobile development starts.
|
*/

// Placeholder — web routes handle the UI for now.
Route::get('status', fn () => response()->json(['data' => ['module' => 'pro', 'status' => 'active']]));
