<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\FoodLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use Illuminate\Support\Facades\Route;

// Every route carries exactly one `throttle:*` limiter — never two. Stacking a
// group-wide limiter on top of these doubles the rate-limit cache writes and
// deadlocks the database cache driver under concurrency. `throttle:api` is the
// catch-all for endpoints without a tighter, purpose-built ceiling.
// ApiRouteCoverageTest fails if a route is ever added without one.

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'not.banned'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:api');
    Route::get('/user', [AuthController::class, 'user'])->middleware('throttle:api');

    // Reads are individually cheap, but the food search runs an unindexable
    // LIKE across the whole table, so they share a tighter bucket.
    Route::middleware('throttle:reads')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/progress', [ProgressController::class, 'index']);
        Route::get('/foods', [FoodController::class, 'index']);
        Route::get('/foods/{food}', [FoodController::class, 'show']);
        Route::get('/logs', [FoodLogController::class, 'index']);
    });

    Route::post('/foods', [FoodController::class, 'store'])->middleware('throttle:foods');
    Route::post('/logs', [FoodLogController::class, 'store'])->middleware('throttle:logs');
    Route::delete('/logs/{id}', [FoodLogController::class, 'destroy'])->middleware('throttle:api');

    Route::put('/profile', [ProfileController::class, 'update'])->middleware('throttle:api');
});
