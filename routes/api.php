<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Api\V1\Admin\CitizenController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Citizen\ProfileController;
use App\Http\Controllers\Api\V1\FinancialReportController;
use App\Http\Controllers\Api\V1\LetterTypeController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\PushSubscriptionController;
use App\Http\Controllers\Api\V1\ResidentDueController;
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
    Route::get('/public/finance/summary', [FinanceTransactionController::class, 'publicSummaryV11']);
    Route::get('/public/finance/transactions', [FinanceTransactionController::class, 'publicTransactions']);
    Route::get('/public/finance/reports/quarterly', [FinancialReportController::class, 'publicReports']);
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

        // Resident dues & personal financial ledger
        Route::get('/me/dues', [ResidentDueController::class, 'meDues']);
        Route::get('/me/dues/{id}', [ResidentDueController::class, 'meDueDetail']);
        Route::get('/me/payments', [ResidentDueController::class, 'mePayments']);
        Route::get('/me/finance/summary', [ResidentDueController::class, 'meFinanceSummary']);

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

            // Finance reports & cryptographic backup store
            Route::get('/finance/reports/monthly', [FinanceTransactionController::class, 'downloadMonthlyReport'])
                ->middleware('permission:finance.report,finance.read,finance.manage');
            Route::get('/finance/reports/citizen-dues', [FinanceTransactionController::class, 'downloadCitizenDuesReport'])
                ->middleware('permission:finance.report,finance.read,finance.manage');
            Route::post('/finance/backups', [FinanceTransactionController::class, 'createBackupStore'])
                ->middleware('permission:finance.report,finance.manage');
            Route::post('/finance/backup-store', [FinanceTransactionController::class, 'createBackupStore'])
                ->middleware('permission:finance.report,finance.manage');
            Route::get('/finance/backups', [FinanceTransactionController::class, 'listBackups'])
                ->middleware('permission:finance.report,finance.read,finance.manage');
            Route::get('/finance/backups/{filename}', [FinanceTransactionController::class, 'downloadBackup'])
                ->middleware('permission:finance.report,finance.read,finance.manage');

            // Finance Canonical Transactions Management
            Route::get('/finance/transactions', [FinanceTransactionController::class, 'index'])
                ->middleware('permission:finance.transaction.read,finance.read,finance.manage');
            Route::post('/finance/transactions', [FinanceTransactionController::class, 'store'])
                ->middleware('permission:finance.transaction.create,finance.manage');
            Route::post('/finance/transactions/{id}/publish', [FinanceTransactionController::class, 'publish'])
                ->middleware('permission:finance.transaction.publish,finance.manage');
            Route::post('/finance/transactions/{id}/reverse', [FinanceTransactionController::class, 'reverse'])
                ->middleware('permission:finance.transaction.reverse,finance.manage');

            // Resident dues & payment recording
            Route::get('/finance/dues', [ResidentDueController::class, 'adminDues'])
                ->middleware('permission:finance.dues.read,finance.read,finance.manage');
            Route::post('/finance/dues/generate', [ResidentDueController::class, 'adminGenerateDues'])
                ->middleware('permission:finance.dues.manage,finance.manage');
            Route::get('/finance/payments', [ResidentDueController::class, 'adminPayments'])
                ->middleware('permission:finance.payment.read,finance.read,finance.manage');
            Route::post('/finance/payments', [ResidentDueController::class, 'adminRecordPayment'])
                ->middleware('permission:finance.payment.create,finance.manage');

            // Purchases & itemized expenditure
            Route::get('/finance/purchases', [PurchaseController::class, 'index'])
                ->middleware('permission:finance.purchase.read,finance.read,finance.manage');
            Route::post('/finance/purchases', [PurchaseController::class, 'store'])
                ->middleware('permission:finance.purchase.create,finance.manage');
            Route::get('/finance/purchases/{id}', [PurchaseController::class, 'show'])
                ->middleware('permission:finance.purchase.read,finance.read,finance.manage');

            // Quarterly financial reports & integrity sealing
            Route::get('/finance/reports/quarterly', [FinancialReportController::class, 'adminIndex'])
                ->middleware('permission:finance.report,finance.read,finance.manage');
            Route::post('/finance/reports/quarterly/generate', [FinancialReportController::class, 'generate'])
                ->middleware('permission:finance.report,finance.manage');
            Route::post('/finance/reports/quarterly/{id}/publish', [FinancialReportController::class, 'publish'])
                ->middleware('permission:finance.report,finance.manage');

            // Legacy Finance Resource for backward compatibility
            Route::apiResource('finance', FinanceTransactionController::class)
                ->except(['show'])
                ->middleware('permission:finance.manage');
            Route::apiResource('announcements', AnnouncementController::class)
                ->middleware('permission:announcement.manage');
        });
    });
});
