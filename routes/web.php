<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/overwatch');

Auth::routes();

Route::get('/overwatch', [App\Http\Controllers\OverwatchController::class, 'index'])->name('home');

Route::resource('users', UserController::class);
Route::resource('items', ItemController::class);
Route::resource('orders', OrderController::class);
Route::resource('units', UnitController::class);
