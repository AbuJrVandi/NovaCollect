<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/v1/health', static fn () => response()->json([
    'success' => true,
    'message' => 'API is healthy.',
    'data' => [
        'service' => config('app.name'),
        'version' => 'v1',
    ],
    'meta' => [],
]));

Route::prefix('v1')
    ->middleware('throttle:api')
    ->group(base_path('routes/api/v1.php'));
