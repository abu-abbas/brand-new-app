<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeatureController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login'])
    ->name('api.auth.login');
Route::get('/auth/captcha', [AuthController::class, 'captcha'])
    ->middleware('throttle:20,1')
    ->name('api.auth.captcha');
Route::get('/auth/captcha/audio', [AuthController::class, 'captchaAudio'])
    ->middleware('throttle:20,1')
    ->name('api.auth.captcha.audio');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

    Route::get('/users', [UserController::class, 'index'])->name('api.users.index');
    Route::get('/users/test-error', [UserController::class, 'testError'])->name('api.users.test-error');

    Route::get('/features', [FeatureController::class, 'index'])->name('api.features.index');
    Route::get('/features/options', [FeatureController::class, 'options'])
        ->middleware('can:features.view')
        ->name('api.features.options');
    Route::post('/features', [FeatureController::class, 'store'])
        ->middleware('can:features.create')
        ->name('api.features.store');
    Route::put('/features/{feature}', [FeatureController::class, 'update'])
        ->middleware('can:features.update')
        ->name('api.features.update');
    Route::delete('/features/{feature}', [FeatureController::class, 'destroy'])
        ->middleware('can:features.delete')
        ->name('api.features.destroy');
    Route::post('/features/{feature}/restore', [FeatureController::class, 'restore'])
        ->withTrashed()
        ->middleware('can:features.update')
        ->name('api.features.restore');

    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('can:roles.view')
        ->name('api.roles.index');
    Route::get('/roles/options', [RoleController::class, 'options'])
        ->middleware('can:roles.view')
        ->name('api.roles.options');
    Route::get('/roles/{role}', [RoleController::class, 'show'])
        ->middleware('can:roles.view')
        ->name('api.roles.show');
    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('can:roles.create')
        ->name('api.roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])
        ->middleware('can:roles.update')
        ->name('api.roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('can:roles.delete')
        ->name('api.roles.destroy');
    Route::post('/roles/{role}/restore', [RoleController::class, 'restore'])
        ->withTrashed()
        ->middleware('can:roles.update')
        ->name('api.roles.restore');
});
