<?php

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
