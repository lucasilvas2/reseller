<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StockMovementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Stock Management API Routes
Route::middleware(['auth:sanctum', 'role:dealer'])->prefix('v1')->group(function () {
    // Stock Movements API
    Route::prefix('stock-movements')->name('api.stock-movements.')->group(function () {
        Route::get('/', [StockMovementController::class, 'apiIndex'])->name('index');
        Route::post('/', [StockMovementController::class, 'apiStore'])->name('store');
        Route::get('/{id}', [StockMovementController::class, 'apiShow'])->name('show');
        Route::put('/{id}', [StockMovementController::class, 'apiUpdate'])->name('update');
    });

    // Inventory API
    Route::prefix('inventory')->name('api.inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'apiIndex'])->name('index');
        Route::get('/{id}', [InventoryController::class, 'apiShow'])->name('show');
    });
});
