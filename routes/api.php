<?php

use App\Http\Controllers\Api\FeatureController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/users', [UserController::class, 'index'])->name('api.users.index');
Route::get('/users/test-error', [UserController::class, 'testError'])->name('api.users.test-error');
Route::get('/features', [FeatureController::class, 'index'])->name('api.features.index');
Route::get('/features/options', [FeatureController::class, 'options'])->name('api.features.options');
Route::post('/features', [FeatureController::class, 'store'])->name('api.features.store');
Route::put('/features/{feature}', [FeatureController::class, 'update'])->name('api.features.update');
Route::delete('/features/{feature}', [FeatureController::class, 'destroy'])->name('api.features.destroy');
Route::post('/features/{feature}/restore', [FeatureController::class, 'restore'])
    ->withTrashed()
    ->name('api.features.restore');
