<?php

use App\Domain\Inventory\Http\Controllers\EquipmentMaintenanceController;
use App\Domain\Inventory\Http\Controllers\InventoryItemController;
use App\Domain\Inventory\Http\Controllers\PurchaseOrderController;
use App\Domain\Inventory\Http\Controllers\VendorController;
use App\Domain\Shared\Http\Middleware\BranchScopeMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory & Asset Management Domain API Routes
|--------------------------------------------------------------------------
| All routes under /api/v1/inventory
*/

Route::prefix('api/v1/inventory')->middleware(['api', 'auth:sanctum', BranchScopeMiddleware::class])->group(function () {

    // 1. Inventory Items & Stock Adjustments
    Route::prefix('items')->group(function () {
        Route::get('/', [InventoryItemController::class, 'index'])->name('inventory.items.index');
        Route::post('/', [InventoryItemController::class, 'store'])->name('inventory.items.store');
        Route::get('/{inventoryItem}', [InventoryItemController::class, 'show'])->name('inventory.items.show');
        Route::match(['put', 'patch'], '/{inventoryItem}', [InventoryItemController::class, 'update'])->name('inventory.items.update');
        Route::post('/{inventoryItem}/adjust', [InventoryItemController::class, 'adjust'])->name('inventory.items.adjust');
        Route::post('/{inventoryItem}/consume', [InventoryItemController::class, 'consume'])->name('inventory.items.consume');
        Route::get('/{inventoryItem}/reconcile', [InventoryItemController::class, 'reconcile'])->name('inventory.items.reconcile');
    });

    // 2. Inventory Analytics & Reorder Alerts
    Route::get('/alerts/low-stock', [InventoryItemController::class, 'lowStockAlerts'])->name('inventory.alerts.low_stock');
    Route::get('/summary', [InventoryItemController::class, 'summary'])->name('inventory.summary');

    // 3. Vendors & Suppliers
    Route::prefix('vendors')->group(function () {
        Route::get('/', [VendorController::class, 'index'])->name('inventory.vendors.index');
        Route::post('/', [VendorController::class, 'store'])->name('inventory.vendors.store');
        Route::get('/{vendor}', [VendorController::class, 'show'])->name('inventory.vendors.show');
        Route::match(['put', 'patch'], '/{vendor}', [VendorController::class, 'update'])->name('inventory.vendors.update');
    });

    // 4. Purchase Orders & Procurement Approval Chain
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('inventory.purchase_orders.index');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('inventory.purchase_orders.store');
        Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('inventory.purchase_orders.show');
        Route::post('/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])->name('inventory.purchase_orders.submit');
        Route::post('/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('inventory.purchase_orders.approve');
        Route::post('/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('inventory.purchase_orders.reject');
        Route::post('/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('inventory.purchase_orders.receive');
    });

    // 5. Hospital Equipment & Asset Management
    Route::prefix('equipment')->group(function () {
        Route::get('/maintenance-alerts', [EquipmentMaintenanceController::class, 'maintenanceAlerts'])->name('inventory.equipment.maintenance_alerts');
        Route::get('/', [EquipmentMaintenanceController::class, 'indexEquipment'])->name('inventory.equipment.index');
        Route::post('/', [EquipmentMaintenanceController::class, 'storeEquipment'])->name('inventory.equipment.store');
        Route::get('/{equipment}', [EquipmentMaintenanceController::class, 'showEquipment'])->name('inventory.equipment.show');
        Route::match(['put', 'patch'], '/{equipment}', [EquipmentMaintenanceController::class, 'updateEquipment'])->name('inventory.equipment.update');
        Route::post('/{equipment}/schedule-maintenance', [EquipmentMaintenanceController::class, 'scheduleMaintenance'])->name('inventory.equipment.schedule_maintenance');
    });

    // 6. Maintenance Logs & Ticket Execution
    Route::prefix('maintenance-logs')->group(function () {
        Route::get('/', [EquipmentMaintenanceController::class, 'listMaintenanceLogs'])->name('inventory.maintenance_logs.index');
        Route::post('/{maintenanceLog}/complete', [EquipmentMaintenanceController::class, 'completeMaintenance'])->name('inventory.maintenance_logs.complete');
    });
});
