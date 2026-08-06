<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeatureController;
use App\Http\Controllers\Api\ImpersonateController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\BlockImpersonatedSensitiveActions;
use App\Http\Middleware\EnforceImpersonateSession;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsurePasswordIsFresh;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login'])
    ->name('api.auth.login');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:6,1')
    ->name('api.auth.forgot-password');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:10,1')
    ->name('api.auth.reset-password');
Route::get('/auth/captcha', [AuthController::class, 'captcha'])
    ->middleware('throttle:20,1')
    ->name('api.auth.captcha');
Route::get('/auth/captcha/audio', [AuthController::class, 'captchaAudio'])
    ->middleware('throttle:20,1')
    ->name('api.auth.captcha.audio');

// Routes yang selalu reachable untuk mengakhiri sesi (Logout & Leave Impersonate)
Route::middleware([
    'auth:sanctum',
    EnforceImpersonateSession::class,
])->group(function () {
    Route::post('/impersonate/leave', [ImpersonateController::class, 'leave'])
        ->name('api.impersonate.leave');
    Route::post('/auth/logout', [AuthController::class, 'logout'])
        ->name('api.auth.logout');
});

Route::middleware([
    'auth:sanctum',
    EnforceImpersonateSession::class,
    BlockImpersonatedSensitiveActions::class,
    EnsureActiveUser::class,
    EnsurePasswordIsFresh::class,
])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');
    Route::put('/auth/password', [AuthController::class, 'changePassword'])->name('api.auth.password');
    Route::post('/auth/active-group', [AuthController::class, 'setActiveGroup'])->name('api.auth.active-group');
    Route::post('/auth/reset-default-group', [AuthController::class, 'resetDefaultGroup'])->name('api.auth.reset-default-group');
    Route::get('/profile/activity-logs', [ProfileController::class, 'activityLogs'])->name('api.profile.activity-logs');

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
    Route::post('/users/{user}/send-password-link', [UserController::class, 'sendPasswordLink'])
        ->middleware(['can:reset-password-pengguna', 'throttle:6,1'])
        ->name('api.users.send-password-link');
    Route::post('/users/{user}/impersonate', [ImpersonateController::class, 'start'])
        ->middleware('throttle:10,1')
        ->name('api.users.impersonate');

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
