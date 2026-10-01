<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceTransactionController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/offline', function () {
    return view('offline');
})->name('offline');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');
Route::post('/login', [AuthController::class, 'webLogin'])->name('login.post');
Route::post('/logout', [AuthController::class, 'webLogout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/finance/reports/monthly', [FinanceTransactionController::class, 'downloadMonthlyReport'])->name('finance.report.monthly');
    Route::get('/dashboard/finance/reports/citizen-dues', [FinanceTransactionController::class, 'downloadCitizenDuesReport'])->name('finance.report.citizen-dues');
    Route::post('/dashboard/finance/backups', [FinanceTransactionController::class, 'createBackupStore'])->name('finance.backup.create');
    Route::get('/dashboard/finance/backups', [FinanceTransactionController::class, 'listBackups'])->name('finance.backup.list');
    Route::get('/dashboard/finance/backups/{filename}', [FinanceTransactionController::class, 'downloadBackup'])->name('finance.backup.download');
});

// -----------------------------------------------------------------
// OpenAPI 3.1 & Interactive Swagger UI Documentation
// -----------------------------------------------------------------
Route::get('/docs/openapi.yaml', function () {
    $path = base_path('docs/openapi.yaml');
    if (! file_exists($path)) {
        abort(404, 'Spesifikasi OpenAPI tidak ditemukan.');
    }

    return response(file_get_contents($path), 200, [
        'Content-Type' => 'text/yaml; charset=UTF-8',
    ]);
})->name('docs.openapi');

Route::get('/docs/api', function () {
    return view('docs.swagger');
})->name('docs.swagger');

Route::get('/api/documentation', function () {
    return redirect()->route('docs.swagger');
});
