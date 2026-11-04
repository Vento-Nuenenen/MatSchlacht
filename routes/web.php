<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\AzureController;
use App\Http\Controllers\Auth\HitobitoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AzureGroupRoleMappingController;
use App\Http\Controllers\ComponentSettingController;
use App\Http\Controllers\FileStorageConnectionController;
use App\Http\Controllers\HitobitoAccessRuleController;
use App\Http\Controllers\HitobitoTestController;
use App\Http\Controllers\ItemCollectionController;
use App\Http\Controllers\ItemContentController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemGroupController;
use App\Http\Controllers\ItemMaintenanceController;
use App\Http\Controllers\ItemProcurementController;
use App\Http\Controllers\ItemStockController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\MaintenanceOverviewController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\OverwatchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\StorageUnitController;
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

Route::middleware('component:hitobito_login')->group(function () {
    Route::get('/auth/hitobito', [HitobitoController::class, 'redirectToHitobito'])->name('hitobito.login');
    Route::get('/auth/hitobito/callback', [HitobitoController::class, 'handleHitobitoCallback'])->name('hitobito.callback');
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
    Route::middleware('component:storage')->group(function () {
        Route::resource('storage-units', StorageUnitController::class);
        Route::get('storage-units/{storageUnit}/labels', [LabelController::class, 'storageUnit'])->name('labels.storage-unit');
        Route::get('labels/items', [LabelController::class, 'items'])->name('labels.items');
        Route::post('item-stocks', [ItemStockController::class, 'store'])->name('item-stocks.store');
        Route::put('item-stocks/{itemStock}', [ItemStockController::class, 'update'])->name('item-stocks.update');
        Route::delete('item-stocks/{itemStock}', [ItemStockController::class, 'destroy'])->name('item-stocks.destroy');
    });
    Route::middleware('component:procurement')->group(function () {
        Route::post('items/{item}/procurements', [ItemProcurementController::class, 'store'])->name('items.procurements.store');
        Route::get('item-procurements/{itemProcurement}/edit', [ItemProcurementController::class, 'edit'])->name('items.procurements.edit');
        Route::put('item-procurements/{itemProcurement}', [ItemProcurementController::class, 'update'])->name('items.procurements.update');
        Route::delete('item-procurements/{itemProcurement}', [ItemProcurementController::class, 'destroy'])->name('items.procurements.destroy');
    });
    Route::middleware('component:maintenance')->group(function () {
        Route::get('maintenance', [MaintenanceOverviewController::class, 'index'])->name('maintenance.index');
        Route::post('items/{item}/maintenances', [ItemMaintenanceController::class, 'store'])->name('items.maintenances.store');
        Route::get('item-maintenances/{itemMaintenance}/edit', [ItemMaintenanceController::class, 'edit'])->name('items.maintenances.edit');
        Route::put('item-maintenances/{itemMaintenance}', [ItemMaintenanceController::class, 'update'])->name('items.maintenances.update');
        Route::delete('item-maintenances/{itemMaintenance}', [ItemMaintenanceController::class, 'destroy'])->name('items.maintenances.destroy');
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
    Route::middleware('component:hitobito_login')->group(function () {
        Route::resource('hitobito-access-rules', HitobitoAccessRuleController::class)->except(['show']);
        Route::get('/hitobito-test', [HitobitoTestController::class, 'index'])->name('hitobito-test.index');
        Route::post('/hitobito-test', [HitobitoTestController::class, 'start'])->name('hitobito-test.start');
    });
    Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/settings/components', [ComponentSettingController::class, 'index'])->name('settings.components.index');
    Route::put('/settings/components', [ComponentSettingController::class, 'update'])->name('settings.components.update');
    Route::post('/settings/file-storage/test', [ComponentSettingController::class, 'testFileStorage'])->name('settings.file-storage.test');
    Route::get('/settings/file-storage/{provider}/connect', [FileStorageConnectionController::class, 'connect'])->name('settings.file-storage.connect');
    Route::get('/settings/file-storage/{provider}/callback', [FileStorageConnectionController::class, 'callback'])->name('settings.file-storage.callback');
});
