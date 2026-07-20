<?php

use App\Http\Controllers\Auth\AzureController;
use App\Http\Controllers\AzureGroupRoleMappingController;
use App\Http\Controllers\ItemCollectionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemGroupController;
use App\Http\Controllers\MailTestController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\OverwatchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/overwatch');

Auth::routes(['register' => false]);

Route::get('/auth/azure', [AzureController::class, 'redirectToAzure'])->name('azure.login');
Route::get('/auth/azure/callback', [AzureController::class, 'handleAzureCallback'])->name('azure.callback');

Route::middleware('auth')->group(function () {
    Route::get('/overwatch', [OverwatchController::class, 'index'])->name('overwatch');

    Route::resource('users', UserController::class);
    Route::post('users/{user}/password-reset', [UserController::class, 'sendResetLink'])->name('users.password-reset');
    Route::resource('item-groups', ItemGroupController::class);
    Route::resource('items', ItemController::class);
    Route::resource('item-collections', ItemCollectionController::class);
    Route::resource('orders', OrderController::class);
    Route::get('/manage/orders', [OrderManagementController::class, 'index'])->name('orders.manage');
    Route::get('/manage/orders/{order}/edit', [OrderManagementController::class, 'edit'])->name('orders.manage.edit');
    Route::get('/manage/orders/{order}/pdf', [OrderManagementController::class, 'exportPdf'])->name('orders.manage.pdf');
    Route::put('/manage/orders/{order}', [OrderManagementController::class, 'update'])->name('orders.manage.update');
    Route::post('/manage/orders/{order}/transition', [OrderManagementController::class, 'transition'])->name('orders.manage.transition');
    Route::resource('roles', RolePermissionController::class);
    Route::resource('azure-group-role-mappings', AzureGroupRoleMappingController::class)->except(['show']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/test-mail', [MailTestController::class, 'sendTestMail'])->name('test-mail');
});
