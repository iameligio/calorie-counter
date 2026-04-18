<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\FoodLogController;
use App\Http\Controllers\ProgressController;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/progress', [ProgressController::class, 'index']);
    Route::get('/foods/search', [FoodController::class, 'search']);
    Route::get('/foods', [FoodController::class, 'index']);
    Route::get('/foods/{food}', [FoodController::class, 'show']);
    Route::post('/foods', [FoodController::class, 'store'])->middleware('throttle:foods');
    
    Route::get('/logs', [FoodLogController::class, 'index']);
    Route::post('/logs', [FoodLogController::class, 'store'])->middleware('throttle:logs');
    Route::delete('/logs/{id}', [FoodLogController::class, 'destroy']);

    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update']);
});
