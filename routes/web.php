<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\FinanceTransactionController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/offline', fn () => view('offline'))->name('offline');

// -----------------------------------------------------------------
// Autentikasi (tamu)
// -----------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::get('/register', fn () => view('auth.register'))->name('register');
    Route::get('/forgot-password', fn () => view('auth.forgot'))->name('password.request');
    Route::get('/reset-password/{token}', fn (string $token) => view('auth.reset', ['token' => $token]))->name('password.reset');
});

Route::post('/login', [AuthController::class, 'webLogin'])->name('login.post')->middleware('throttle:20,1');
Route::post('/logout', [AuthController::class, 'webLogout'])->name('logout');

// -----------------------------------------------------------------
// Aplikasi (wajib login) — satu shell, halaman diatur per peran
// -----------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AppController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/profile', [AppController::class, 'profile'])->name('profile');
    Route::get('/app/{page}', [AppController::class, 'page'])->name('app.page');

    // Laporan & backup kas: izin yang sama dengan jalur /api/v1/admin/finance/* (bukan nama peran).
    Route::middleware('permission:finance.report.read,finance.read')->group(function () {
        Route::get('/dashboard/finance/reports/monthly', [FinanceTransactionController::class, 'downloadMonthlyReport'])->name('finance.report.monthly');
        Route::get('/dashboard/finance/reports/citizen-dues', [FinanceTransactionController::class, 'downloadCitizenDuesReport'])->name('finance.report.citizen-dues');
        Route::get('/dashboard/finance/backups', [FinanceTransactionController::class, 'listBackups'])->name('finance.backup.list');
        Route::get('/dashboard/finance/backups/{filename}', [FinanceTransactionController::class, 'downloadBackup'])->name('finance.backup.download');
    });
    Route::post('/dashboard/finance/backups', [FinanceTransactionController::class, 'createBackupStore'])
        ->middleware('permission:finance.report.generate,finance.manage')
        ->name('finance.backup.create');
});

// -----------------------------------------------------------------
// OpenAPI 3.1 & Swagger UI
// -----------------------------------------------------------------
Route::get('/docs/openapi.yaml', function () {
    $path = file_exists(base_path('docs/api_spec/openapi.yaml'))
        ? base_path('docs/api_spec/openapi.yaml')
        : base_path('docs/openapi.yaml');

    abort_unless(file_exists($path), 404, 'Spesifikasi OpenAPI tidak ditemukan.');

    return response(file_get_contents($path), 200, ['Content-Type' => 'text/yaml; charset=UTF-8']);
})->name('docs.openapi');

Route::get('/docs/api', fn () => view('docs.swagger'))->name('docs.swagger');
Route::get('/api/documentation', fn () => redirect()->route('docs.swagger'));
