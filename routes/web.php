<?php

use App\Http\Controllers\Auth\AzureController;
use App\Http\Controllers\AzureGroupRoleMappingController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemGroupController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OverwatchController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/overwatch');

Auth::routes();

Route::get('/auth/azure', [AzureController::class, 'redirectToAzure'])->name('azure.login');
Route::get('/auth/azure/callback', [AzureController::class, 'handleAzureCallback']);

Route::get('/overwatch', [OverwatchController::class, 'index'])->name('home');

Route::resource('users', UserController::class);
Route::resource('units', UnitController::class);
Route::resource('itemgroups', ItemGroupController::class);
Route::resource('items', ItemController::class);
Route::resource('orders', OrderController::class);
Route::resource('roles', RolePermissionController::class)->only(['index', 'edit', 'update', 'destroy']);
Route::resource('azure-group-role-mappings', AzureGroupRoleMappingController::class)->except(['show']);
