<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Api\V1\Admin\CitizenController;
use App\Http\Controllers\Api\V1\Citizen\ProfileController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\FinanceTransactionController;
use App\Http\Controllers\LetterController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [ProfileController::class, 'show']);

        Route::get('/letters', [LetterController::class, 'index']);
        Route::post('/letters', [LetterController::class, 'store']);
        Route::get('/letters/{id}', [LetterController::class, 'show']);
        Route::patch('/letters/{id}/status', [LetterController::class, 'update']);

        Route::get('/complaints', [ComplaintController::class, 'index']);

        // Admin routes
        Route::prefix('admin')->group(function () {
            Route::apiResource('citizens', CitizenController::class);
            Route::get('/letters', [LetterController::class, 'adminIndex']);

            Route::patch('/complaints/{id}/status', [ComplaintController::class, 'update']);
            Route::get('/complaints', [ComplaintController::class, 'adminIndex']);

            Route::apiResource('finance', FinanceTransactionController::class)->except(['show', 'destroy']);
            Route::apiResource('announcements', AnnouncementController::class)->except(['show', 'destroy']);
        });
    });

    // Public routes (complaints can be submitted anonymously)
    Route::post('/complaints', [ComplaintController::class, 'store']);

    // Public routes (published finance & announcements)
    Route::get('/finance', [FinanceTransactionController::class, 'publicIndex']);
    Route::get('/announcements', [AnnouncementController::class, 'publicIndex']);
});
