<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Analytics\AnalyticsController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Forms\FormController;
use App\Http\Controllers\Api\V1\Forms\SubmissionController;
use App\Http\Controllers\Api\V1\Notifications\NotificationController;
use App\Http\Controllers\Api\V1\Organizations\OrganizationController;
use App\Http\Controllers\Api\V1\Projects\ProjectController;
use App\Http\Controllers\Api\V1\Reports\ReportExportController;
use Illuminate\Support\Facades\Route;

Route::get('/health', static fn () => response()->json([
    'success' => true,
    'message' => 'API is healthy.',
    'data' => [
        'service' => config('app.name'),
        'version' => 'v1',
    ],
    'meta' => [],
]));

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerification']);
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware('signed');
    });
});

Route::middleware(['auth:sanctum'])->group(function (): void {
    // Organizations
    Route::get('/organizations', [OrganizationController::class, 'index']);
    Route::post('/organizations', [OrganizationController::class, 'store']);
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
    Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);
    Route::post('/organizations/{organization}/invite', [OrganizationController::class, 'invite']);
    Route::post('/organizations/{organization}/switch', [OrganizationController::class, 'switch']);
    Route::get('/organizations/{organization}/users', [OrganizationController::class, 'users']);

    // Forms
    Route::get('/forms', [FormController::class, 'index']);
    Route::post('/forms', [FormController::class, 'store']);
    Route::get('/forms/{form}', [FormController::class, 'show']);
    Route::put('/forms/{form}', [FormController::class, 'update']);
    Route::delete('/forms/{form}', [FormController::class, 'destroy']);
    Route::post('/forms/{form}/publish', [FormController::class, 'publish']);
    Route::post('/forms/{form}/archive', [FormController::class, 'archive']);

    // Submissions
    Route::get('/submissions', [SubmissionController::class, 'index']);
    Route::post('/submissions', [SubmissionController::class, 'store']);
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show']);
    Route::put('/submissions/{submission}', [SubmissionController::class, 'update']);
    Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy']);

    // Projects
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
    Route::post('/projects/{project}/tasks', [ProjectController::class, 'storeTask']);
    Route::delete('/tasks/{task}', [ProjectController::class, 'destroyTask']);
    Route::put('/tasks/{task}', [ProjectController::class, 'updateTask']);

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index']);
    Route::get('/dashboard/stats', [AnalyticsController::class, 'dashboard']);

    // Exports
    Route::get('/exports', [ReportExportController::class, 'index']);
    Route::post('/exports', [ReportExportController::class, 'store']);
    Route::get('/exports/{reportExport}', [ReportExportController::class, 'show']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{notificationId}', [NotificationController::class, 'destroy']);
});
