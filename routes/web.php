<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BrandsController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\DealershipsController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:dealer'
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductsController::class, 'create'])->name('products.create');
    Route::post('/products/store', [ProductsController::class, 'store'])->name('products.store');
    Route::get('/products/edit/{id}', [ProductsController::class, 'edit'])->name('products.edit');
    Route::post('/products/update/{id}', [ProductsController::class, 'update'])->name('products.update');
    Route::delete('/products/destroy/{id}', [ProductsController::class, 'destroy'])->name('products.destroy');

    //stocks
    Route::get('/stocks/movements', [StockController::class, 'index'])->name('stocks.movements.index');
    Route::get('/stocks/movements/create', [StockController::class, 'create'])->name('stocks.movements.create');
    Route::post('/stocks/movements/store', [StockController::class, 'store'])->name('stocks.movements.store');
    Route::get('/stocks/movements/edit/{id}', [StockController::class, 'edit'])->name('stocks.movements.edit');

    //clients
    Route::get('/clients', [ClientsController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientsController::class, 'create'])->name('clients.create');
    Route::post('/clients/store', [ClientsController::class, 'store'])->name('clients.store');
    Route::delete('/clients/delete/{id}', [ClientsController::class, 'delete'])->name('clients.delete');
});

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminController::class, 'login']);
    Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');

    Route::middleware([])->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('admin.dashboard');

        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/users/store', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/users/edit/{id}', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::post('/users/update/{id}', [UserController::class, 'update'])->name('admin.users.update');

        Route::get('/dealerships', [DealershipsController::class, 'index'])->name('admin.dealerships.index');
        Route::get('/dealerships/create', [DealershipsController::class, 'create'])->name('admin.dealerships.create');
        Route::post('/dealerships/store', [DealershipsController::class, 'store'])->name('admin.dealerships.store');
        Route::get('/dealerships/edit/{id}', [DealershipsController::class, 'edit'])->name('admin.dealerships.edit');
        Route::post('/dealerships/update/{id}', [DealershipsController::class, 'update'])->name('admin.dealerships.update');

        Route::get('/brands', [BrandsController::class, 'index'])->name('admin.brands.index');
        Route::get('/brands/create', [BrandsController::class, 'create'])->name('admin.brands.create');
        Route::post('/brands/store', [BrandsController::class, 'store'])->name('admin.brands.store');
        Route::get('/brands/edit/{id}', [BrandsController::class, 'edit'])->name('admin.brands.edit');
        Route::put('/brands/update/{id}', [BrandsController::class, 'update'])->name('admin.brands.update');
        Route::delete('/brands/destroy/{id}', [BrandsController::class, 'destroy'])->name('admin.brands.destroy');
    });
});
