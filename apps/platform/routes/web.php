<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', LoginController::class)
    ->middleware('throttle:6,1')
    ->name('login');

Route::post('/logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');
