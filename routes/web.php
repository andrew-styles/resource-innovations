<?php

use App\Http\Controllers\OTPController;
use App\Http\Controllers\SecretsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/otp', [OTPController::class, 'login'])
    ->name('otp.login');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/secrets', [SecretsController::class, 'index'])
        ->name('secrets.index');
    Route::post('/secrets', [SecretsController::class, 'store'])
        ->name('secrets.store');
});
