<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeatureController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\EnsureActiveUser;
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

Route::middleware(['auth:sanctum', EnsureActiveUser::class])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');
    Route::post('/auth/active-group', [AuthController::class, 'setActiveGroup'])->name('api.auth.active-group');
    Route::post('/auth/reset-default-group', [AuthController::class, 'resetDefaultGroup'])->name('api.auth.reset-default-group');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

    // Users
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('can:manajemen-pengguna')
        ->name('api.users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])
        ->middleware('can:manajemen-pengguna')
        ->name('api.users.show');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('can:tambah-pengguna')
        ->name('api.users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('can:ubah-pengguna')
        ->name('api.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('can:hapus-pengguna')
        ->name('api.users.destroy');
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        ->middleware('can:ubah-pengguna')
        ->name('api.users.toggle-status');

    // References
    Route::get('/references/wilayah', [ReferenceController::class, 'wilayah'])->name('api.references.wilayah');
    Route::get('/references/perangkat-daerah', [ReferenceController::class, 'perangkatDaerah'])->name('api.references.perangkat-daerah');

    // Features
    Route::get('/features', [FeatureController::class, 'index'])
        ->name('api.features.index');
    Route::get('/features/options', [FeatureController::class, 'options'])
        ->name('api.features.options');
    Route::post('/features', [FeatureController::class, 'store'])
        ->middleware('can:tambah-fitur')
        ->name('api.features.store');
    Route::put('/features/{feature}', [FeatureController::class, 'update'])
        ->middleware('can:ubah-fitur')
        ->name('api.features.update');
    Route::delete('/features/{feature}', [FeatureController::class, 'destroy'])
        ->middleware('can:hapus-fitur')
        ->name('api.features.destroy');
    Route::post('/features/{feature}/restore', [FeatureController::class, 'restore'])
        ->withTrashed()
        ->middleware('can:ubah-fitur')
        ->name('api.features.restore');

    // Roles
    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('can:manajemen-group')
        ->name('api.roles.index');
    Route::get('/roles/options', [RoleController::class, 'options'])
        ->name('api.roles.options');
    Route::get('/roles/{role}', [RoleController::class, 'show'])
        ->middleware('can:manajemen-group')
        ->name('api.roles.show');
    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('can:tambah-group')
        ->name('api.roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])
        ->middleware('can:ubah-group')
        ->name('api.roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('can:hapus-group')
        ->name('api.roles.destroy');
    Route::post('/roles/{role}/restore', [RoleController::class, 'restore'])
        ->withTrashed()
        ->middleware('can:ubah-group')
        ->name('api.roles.restore');
});
