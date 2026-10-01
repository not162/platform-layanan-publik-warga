<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Api\V1\Admin\CitizenController;
use App\Http\Controllers\Api\V1\Citizen\ProfileController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\FinanceTransactionController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\PublicContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [ProfileController::class, 'show']);
        Route::patch('/me', [ProfileController::class, 'update']);

        Route::get('/letters', [LetterController::class, 'index']);
        Route::post('/letters', [LetterController::class, 'store']);
        Route::get('/letters/{id}', [LetterController::class, 'show']);
        Route::patch('/letters/{id}/status', [LetterController::class, 'update']);

        Route::get('/complaints', [ComplaintController::class, 'index']);
        Route::get('/complaints/{id}', [ComplaintController::class, 'show']);

        // Admin routes
        Route::prefix('admin')->middleware('role:ADMIN,SUPERADMIN')->group(function () {
            Route::apiResource('citizens', CitizenController::class);
            Route::get('/letters', [LetterController::class, 'adminIndex']);
            Route::patch('/letters/{id}/verify', [LetterController::class, 'adminVerify']);
            Route::patch('/letters/{id}/approve', [LetterController::class, 'adminApprove']);
            Route::patch('/letters/{id}/reject', [LetterController::class, 'adminReject']);

            Route::patch('/complaints/{id}/status', [ComplaintController::class, 'update']);
            Route::get('/complaints', [ComplaintController::class, 'adminIndex']);

            Route::apiResource('finance', FinanceTransactionController::class)->except(['show'])->middleware('permission:finance.manage');
            Route::apiResource('announcements', AnnouncementController::class)->except(['show', 'destroy']);
        });
    });

    // Public letter verification and tracking
    Route::get('/public/letter/verify/{token}', [LetterController::class, 'verifyPublic']);
    Route::get('/public/track/{ticket}', [LetterController::class, 'trackPublic']);

    // Public routes (complaints can be submitted anonymously)
    Route::post('/complaints', [ComplaintController::class, 'store']);

    // Public routes (published finance & announcements)
    Route::get('/finance/summary', [FinanceTransactionController::class, 'publicSummary']);
    Route::get('/finance', [FinanceTransactionController::class, 'publicIndex']);
    Route::get('/announcements', [AnnouncementController::class, 'publicIndex']);

    // Public community information (announcements, events, officers, emergency, round schedules)
    Route::get('/public/overview', [PublicContentController::class, 'portalOverview']);
    Route::get('/public/announcements', [PublicContentController::class, 'announcements']);
    Route::get('/public/events', [PublicContentController::class, 'events']);
    Route::get('/public/officers', [PublicContentController::class, 'officers']);
    Route::get('/public/emergency-contacts', [PublicContentController::class, 'emergencyContacts']);
    Route::get('/public/schedules', [PublicContentController::class, 'roundSchedules']);
});
