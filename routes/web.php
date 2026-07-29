<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (SPA Shell Catch-all)
|--------------------------------------------------------------------------
*/

Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*');
