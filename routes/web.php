<?php

use App\Http\Controllers\Auth\SetPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Password setup routes for invited users
Route::get('/set-password/{token}', [SetPasswordController::class, 'showSetPasswordForm'])
    ->name('password.reset');

Route::post('/set-password', [SetPasswordController::class, 'setPassword'])
    ->name('password.update');
