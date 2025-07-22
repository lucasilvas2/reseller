<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BrandsController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\DealershipsController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\PublicDealershipsController;
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
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::middleware(['role:dealer'])->group(function () {
        Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductsController::class, 'create'])->name('products.create');
        Route::post('/products/store', [ProductsController::class, 'store'])->name('products.store');
        Route::get('/products/edit/{id}', [ProductsController::class, 'edit'])->name('products.edit');
        Route::post('/products/update/{id}', [ProductsController::class, 'update'])->name('products.update');
        Route::delete('/products/destroy/{id}', [ProductsController::class, 'destroy'])->name('products.destroy');

        //stocks
        Route::get('/stocks/dashboard', [StockController::class, 'dashboard'])->name('stocks.dashboard');
        Route::get('/stocks/movements', [StockController::class, 'index'])->name('stocks.movements.index');
        Route::get('/stocks/movements/create', [StockController::class, 'create'])->name('stocks.movements.create');
        Route::post('/stocks/movements/store', [StockController::class, 'store'])->name('stocks.movements.store');
        Route::get('/stocks/movements/edit/{id}', [StockController::class, 'edit'])->name('stocks.movements.edit');
        Route::put('/stocks/movements/update/{id}', [StockController::class, 'update'])->name('stocks.movements.update');
        Route::delete('/stocks/movements/destroy/{id}', [StockController::class, 'destroy'])->name('stocks.movements.destroy');
        Route::get('/stocks/movements/show/{id}', [StockController::class, 'show'])->name('stocks.movements.show');

        // Stock Inventory
        Route::get('/stocks/inventory', [StockController::class, 'inventory'])->name('stocks.inventory.index');

        //clients
        Route::get('/clients', [ClientsController::class, 'index'])->name('clients.index');
        Route::get('/clients/create', [ClientsController::class, 'create'])->name('clients.create');
        Route::post('/clients/store', [ClientsController::class, 'store'])->name('clients.store');
        Route::delete('/clients/destroy/{id}', [ClientsController::class, 'destroy'])->name('clients.destroy');
        Route::get('/clients/show/{id}', [ClientsController::class, 'show'])->name('clients.show');
    });

    Route::middleware(['role:user'])->group(function () {
        Route::get('/dealers', [PublicDealershipsController::class, 'index'])->name('dealers.index');
    });
});

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminController::class, 'login']);
    Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');

    Route::middleware(['auth', 'role:admin', 'admin.audit'])->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('admin.dashboard');

        // Users routes with specific permissions
        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:admin.users.index')
            ->name('admin.users.index');
        Route::get('/users/create', [UserController::class, 'create'])
            ->middleware('permission:admin.users.create')
            ->name('admin.users.create');
        Route::post('/users/store', [UserController::class, 'store'])
            ->middleware('permission:admin.users.store')
            ->name('admin.users.store');
        Route::get('/users/edit/{id}', [UserController::class, 'edit'])
            ->middleware('permission:admin.users.edit')
            ->name('admin.users.edit');
        Route::post('/users/update/{id}', [UserController::class, 'update'])
            ->middleware('permission:admin.users.update')
            ->name('admin.users.update');

        // Dealerships routes
        Route::get('/dealerships', [DealershipsController::class, 'index'])
            ->middleware('permission:admin.dealerships.index')
            ->name('admin.dealerships.index');
        Route::get('/dealerships/create', [DealershipsController::class, 'create'])
            ->middleware('permission:admin.dealerships.create')
            ->name('admin.dealerships.create');
        Route::post('/dealerships/store', [DealershipsController::class, 'store'])
            ->middleware('permission:admin.dealerships.create')
            ->name('admin.dealerships.store');
        Route::get('/dealerships/edit/{id}', [DealershipsController::class, 'edit'])
            ->middleware('permission:admin.dealerships.edit')
            ->name('admin.dealerships.edit');
        Route::post('/dealerships/update/{id}', [DealershipsController::class, 'update'])
            ->middleware('permission:admin.dealerships.update')
            ->name('admin.dealerships.update');

        // Brands routes
        Route::get('/brands', [BrandsController::class, 'index'])
            ->middleware('permission:admin.brands.index')
            ->name('admin.brands.index');
        Route::get('/brands/create', [BrandsController::class, 'create'])
            ->middleware('permission:admin.brands.create')
            ->name('admin.brands.create');
        Route::post('/brands/store', [BrandsController::class, 'store'])
            ->middleware('permission:admin.brands.create')
            ->name('admin.brands.store');
        Route::get('/brands/edit/{id}', [BrandsController::class, 'edit'])
            ->middleware('permission:admin.brands.edit')
            ->name('admin.brands.edit');
        Route::put('/brands/update/{id}', [BrandsController::class, 'update'])
            ->middleware('permission:admin.brands.update')
            ->name('admin.brands.update');
        Route::delete('/brands/destroy/{id}', [BrandsController::class, 'destroy'])
            ->middleware('permission:admin.brands.destroy')
            ->name('admin.brands.destroy');
    });
});
