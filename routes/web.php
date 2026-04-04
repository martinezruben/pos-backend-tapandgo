<?php

use App\Http\Controllers\Admin\ApiRequestLogDetailController;
use App\Http\Controllers\Admin\TransactionLineItemsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeviceLastSyncController;
use App\Http\Controllers\Admin\LocationPairingTokenController;
use App\Http\Controllers\Admin\ProductExcelController;
use App\Http\Controllers\Admin\ScreenCrudController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest:admin')->group(function (): void {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

        Route::get('/locations/{location}/pairing-token', [LocationPairingTokenController::class, 'show'])->name('locations.pairing-token.show');
        Route::post('/locations/{location}/pairing-token', [LocationPairingTokenController::class, 'store'])->name('locations.pairing-token.store');

        Route::post('/devices/{device}/reset-last-sync', [DeviceLastSyncController::class, 'store'])->name('devices.reset-last-sync');

        Route::get('/api-request-logs/{id}', [ApiRequestLogDetailController::class, 'show'])->name('api-request-logs.show');

        Route::get('/transactions/{transaction}/line-items', [TransactionLineItemsController::class, 'show'])->name('transactions.line-items');

        Route::get('/products/excel/export', [ProductExcelController::class, 'export'])->name('products.excel.export');
        Route::post('/products/excel/import', [ProductExcelController::class, 'import'])->name('products.excel.import');

        Route::get('/screens/{screen}', [ScreenCrudController::class, 'index'])->name('screens.index');
        Route::get('/screens/{screen}/create', [ScreenCrudController::class, 'create'])->name('screens.create');
        Route::post('/screens/{screen}', [ScreenCrudController::class, 'store'])->name('screens.store');
        Route::get('/screens/{screen}/{id}/edit', [ScreenCrudController::class, 'edit'])->name('screens.edit');
        Route::put('/screens/{screen}/{id}', [ScreenCrudController::class, 'update'])->name('screens.update');
        Route::delete('/screens/{screen}/{id}', [ScreenCrudController::class, 'destroy'])->name('screens.destroy');
    });
});
