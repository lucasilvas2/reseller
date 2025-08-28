<?php

namespace App\Providers;

use App\Repositories\StockMovementRepository;
use App\Repositories\InventoryRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register StockMovementRepository
        $this->app->singleton(StockMovementRepository::class, function ($app) {
            return new StockMovementRepository($app->make(\App\Models\StockMovement::class));
        });

        // Register InventoryRepository
        $this->app->singleton(InventoryRepository::class, function ($app) {
            return new InventoryRepository(
                $app->make(\App\Models\Product::class),
                $app->make(\App\Models\StockMovement::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
