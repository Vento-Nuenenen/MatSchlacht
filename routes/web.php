<?php

use App\Http\Controllers\Auth\AzureController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AzureGroupRoleMappingController;
use App\Http\Controllers\ComponentSettingController;
use App\Http\Controllers\ItemCollectionController;
use App\Http\Controllers\ItemContentController;
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

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('component:password_login')->group(function () {
    Route::post('login', [LoginController::class, 'login']);
    Auth::routes(['register' => false, 'login' => false, 'logout' => false]);
});

Route::middleware('component:azure_login')->group(function () {
    Route::get('/auth/azure', [AzureController::class, 'redirectToAzure'])->name('azure.login');
    Route::get('/auth/azure/callback', [AzureController::class, 'handleAzureCallback'])->name('azure.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/overwatch', [OverwatchController::class, 'index'])->name('overwatch');

    Route::resource('users', UserController::class);
    Route::post('users/{user}/password-reset', [UserController::class, 'sendResetLink'])->name('users.password-reset')->middleware('component:password_login');
    Route::post('item-groups/reorder', [ItemGroupController::class, 'reorder'])->name('item-groups.reorder');
    Route::resource('item-groups', ItemGroupController::class);
    Route::resource('items', ItemController::class);
    Route::resource('item-collections', ItemCollectionController::class)->middleware('component:item_collections');
    Route::middleware('component:item_contents')->group(function () {
        Route::post('items/{item}/contents', [ItemContentController::class, 'store'])->name('items.contents.store');
        Route::delete('item-contents/{itemContent}', [ItemContentController::class, 'destroy'])->name('items.contents.destroy');
        Route::get('/manage/orders/{order}/contents-pdf', [OrderManagementController::class, 'exportContentsPdf'])->name('orders.manage.contents-pdf');
    });
    Route::resource('orders', OrderController::class);
    Route::get('/manage/orders', [OrderManagementController::class, 'index'])->name('orders.manage');
    Route::get('/manage/orders/{order}/edit', [OrderManagementController::class, 'edit'])->name('orders.manage.edit');
    Route::get('/manage/orders/{order}/order-pdf', [OrderManagementController::class, 'exportOrderPdf'])->name('orders.manage.order-pdf');
    Route::get('/manage/orders/{order}/return-pdf', [OrderManagementController::class, 'exportReturnPdf'])->name('orders.manage.return-pdf');
    Route::put('/manage/orders/{order}', [OrderManagementController::class, 'update'])->name('orders.manage.update');
    Route::post('/manage/orders/{order}/transition', [OrderManagementController::class, 'transition'])->name('orders.manage.transition');
    Route::resource('roles', RolePermissionController::class);
    Route::resource('azure-group-role-mappings', AzureGroupRoleMappingController::class)->except(['show'])->middleware('component:azure_login');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/settings/components', [ComponentSettingController::class, 'index'])->name('settings.components.index');
    Route::put('/settings/components', [ComponentSettingController::class, 'update'])->name('settings.components.update');
    Route::get('/test-mail', [MailTestController::class, 'sendTestMail'])->name('test-mail');
});
