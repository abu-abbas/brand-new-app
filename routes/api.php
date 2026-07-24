<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/users', [UserController::class, 'index'])->name('api.users.index');
Route::get('/users/test-error', [UserController::class, 'testError'])->name('api.users.test-error');
