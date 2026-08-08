<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\FoodLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use Illuminate\Support\Facades\Route;

// Every route below also inherits the `throttle:api` baseline from the api
// middleware group (see bootstrap/app.php). The named limiters here are the
// tighter, per-endpoint ceilings on top of it.

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'not.banned'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::middleware('throttle:reads')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/progress', [ProgressController::class, 'index']);
        Route::get('/foods', [FoodController::class, 'index']);
        Route::get('/foods/{food}', [FoodController::class, 'show']);
        Route::get('/logs', [FoodLogController::class, 'index']);
    });

    Route::post('/foods', [FoodController::class, 'store'])->middleware('throttle:foods');
    Route::post('/logs', [FoodLogController::class, 'store'])->middleware('throttle:logs');
    Route::delete('/logs/{id}', [FoodLogController::class, 'destroy']);

    Route::put('/profile', [ProfileController::class, 'update']);
});
