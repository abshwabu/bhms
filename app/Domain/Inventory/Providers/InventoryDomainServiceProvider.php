<?php

namespace App\Domain\Inventory\Providers;

use App\Domain\Inventory\Services\EquipmentMaintenanceService;
use App\Domain\Inventory\Services\InventoryStockService;
use App\Domain\Inventory\Services\PurchaseOrderService;
use Illuminate\Support\ServiceProvider;

class InventoryDomainServiceProvider extends ServiceProvider
{
    /**
     * Register Inventory & Asset Management domain services.
     */
    public function register(): void
    {
        $this->app->singleton(InventoryStockService::class);
        $this->app->singleton(PurchaseOrderService::class);
        $this->app->singleton(EquipmentMaintenanceService::class);
    }

    /**
     * Bootstrap Inventory Domain API routes.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
