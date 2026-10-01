<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Api\V1\Admin\CitizenController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Citizen\ProfileController;
use App\Http\Controllers\Api\V1\LetterTypeController;
use App\Http\Controllers\Api\V1\PushSubscriptionController;
use App\Http\Controllers\Api\V1\SecurityReportController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\FinanceTransactionController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\PublicContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // -------------------------------------------------------------
    // Public Endpoints (No Authentication Required)
    // -------------------------------------------------------------
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Public letter tracking and verification
    Route::get('/public/services', [LetterTypeController::class, 'index']);
    Route::get('/public/letter/verify/{token}', [LetterController::class, 'verifyPublic']);
    Route::get('/public/track/{ticket}', [LetterController::class, 'trackPublic']);

    // Public / Warga letter types catalog & dynamic schemas
    Route::get('/letter-types', [LetterTypeController::class, 'index']);
    Route::get('/letter-types/{code}', [LetterTypeController::class, 'show']);
    Route::get('/letter-types/{code}/form-schema', [LetterTypeController::class, 'formSchema']);

    // Anonymous / public complaint submission
    Route::post('/complaints', [ComplaintController::class, 'store']);

    // Public community and transparency information
    Route::get('/finance/summary', [FinanceTransactionController::class, 'publicSummary']);
    Route::get('/finance', [FinanceTransactionController::class, 'publicIndex']);
    Route::get('/announcements', [AnnouncementController::class, 'publicIndex']);

    Route::get('/public/overview', [PublicContentController::class, 'portalOverview']);
    Route::get('/public/announcements', [PublicContentController::class, 'announcements']);
    Route::get('/public/events', [PublicContentController::class, 'events']);
    Route::get('/public/officers', [PublicContentController::class, 'officers']);
    Route::get('/public/emergency-contacts', [PublicContentController::class, 'emergencyContacts']);
    Route::get('/public/schedules', [PublicContentController::class, 'roundSchedules']);

    // Web push subscription registration
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store']);

    // -------------------------------------------------------------
    // Authenticated Citizen & General User Endpoints
    // -------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [ProfileController::class, 'show']);
        Route::patch('/me', [ProfileController::class, 'update']);

        // Letters management
        Route::get('/letters', [LetterController::class, 'index']);
        Route::post('/letters', [LetterController::class, 'store']);
        Route::get('/letters/{id}', [LetterController::class, 'show']);
        Route::patch('/letters/{id}', [LetterController::class, 'update']);
        Route::patch('/letters/{id}/status', [LetterController::class, 'update']); // Backward compatibility
        Route::delete('/letters/{id}', [LetterController::class, 'destroy']);

        // Letter actions & attachments
        Route::post('/letters/{id}/submit', [LetterController::class, 'submit']);
        Route::get('/letters/{id}/preview', [LetterController::class, 'preview']);
        Route::get('/letters/{id}/export-payload', [LetterController::class, 'exportPayload']);
        Route::get('/letters/{id}/download', [LetterController::class, 'download']);
        Route::post('/letters/{id}/attachments', [LetterController::class, 'addAttachment']);
        Route::delete('/letters/{id}/attachments/{attachment}', [LetterController::class, 'deleteAttachment']);

        // Complaints (authenticated citizen scope)
        Route::get('/complaints', [ComplaintController::class, 'index']);
        Route::get('/complaints/{id}', [ComplaintController::class, 'show']);

        // Security reports (warga creation & tracking)
        Route::get('/security-reports', [SecurityReportController::class, 'index']);
        Route::post('/security-reports', [SecurityReportController::class, 'store']);
        Route::get('/security-reports/{id}', [SecurityReportController::class, 'show']);
        Route::get('/security-reports/{id}/document', [SecurityReportController::class, 'downloadDocument']);

        // ---------------------------------------------------------
        // Administrative Endpoints (RBAC + Permission Scopes)
        // ---------------------------------------------------------
        Route::prefix('admin')->group(function () {
            // Citizens management
            Route::apiResource('citizens', CitizenController::class)
                ->middleware('permission:citizen.read,citizen.manage');

            // Letters admin actions
            Route::get('/letters', [LetterController::class, 'adminIndex'])
                ->middleware('permission:letter.read,letter.verify,letter.approve');
            Route::match(['POST', 'PATCH'], '/letters/{id}/verify', [LetterController::class, 'adminVerify'])
                ->middleware('permission:letter.verify');
            Route::match(['POST', 'PATCH'], '/letters/{id}/approve', [LetterController::class, 'adminApprove'])
                ->middleware('permission:letter.approve');
            Route::match(['POST', 'PATCH'], '/letters/{id}/reject', [LetterController::class, 'adminReject'])
                ->middleware('permission:letter.reject,letter.verify,letter.approve');
            Route::post('/letters/{id}/complete', [LetterController::class, 'adminComplete'])
                ->middleware('permission:letter.complete,letter.approve');

            // Security reports management
            Route::get('/security-reports', [SecurityReportController::class, 'adminIndex'])
                ->middleware('permission:security.read,security.manage');
            Route::get('/security-reports/{id}', [SecurityReportController::class, 'show'])
                ->middleware('permission:security.read,security.manage');
            Route::patch('/security-reports/{id}', [SecurityReportController::class, 'adminUpdate'])
                ->middleware('permission:security.manage');
            Route::get('/security-reports/{id}/document', [SecurityReportController::class, 'downloadDocument'])
                ->middleware('permission:security.read,security.manage');

            // Letter types & dynamic templates management
            Route::get('/letter-types', [LetterTypeController::class, 'adminIndex'])
                ->middleware('permission:letter.template.manage');
            Route::post('/letter-types', [LetterTypeController::class, 'adminStore'])
                ->middleware('permission:letter.template.manage');
            Route::patch('/letter-types/{id}', [LetterTypeController::class, 'adminUpdate'])
                ->middleware('permission:letter.template.manage');
            Route::get('/letter-templates', [LetterTypeController::class, 'adminIndex'])
                ->middleware('permission:letter.template.manage');
            Route::patch('/letter-templates/{id}', [LetterTypeController::class, 'adminUpdate'])
                ->middleware('permission:letter.template.manage');

            // Complaints administrative handling
            Route::get('/complaints', [ComplaintController::class, 'adminIndex'])
                ->middleware('permission:complaint.read,complaint.manage');
            Route::patch('/complaints/{id}/status', [ComplaintController::class, 'update'])
                ->middleware('permission:complaint.manage');

            // Finance & Announcements
            Route::apiResource('finance', FinanceTransactionController::class)
                ->except(['show'])
                ->middleware('permission:finance.manage');
            Route::apiResource('announcements', AnnouncementController::class)
                ->middleware('permission:announcement.manage');
        });
    });
});
