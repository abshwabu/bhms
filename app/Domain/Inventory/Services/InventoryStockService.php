<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\InventoryMovement;
use DomainException;
use Illuminate\Support\Facades\DB;

class InventoryStockService
{
    /**
     * Adjust stock quantity manually (addition or reduction) with audit movement.
     */
    public function adjustStock(
        InventoryItem $item,
        int $quantityChange,
        string $movementType,
        string $userId,
        ?string $department = null,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use ($item, $quantityChange, $movementType, $userId, $department, $notes) {
            $current = $item->current_stock;
            $newStock = $current + $quantityChange;

            if ($newStock < 0) {
                throw new DomainException("Insufficient stock for item {$item->name}. Current stock: {$current}, reduction requested: " . abs($quantityChange));
            }

            $item->current_stock = $newStock;
            $item->save();

            $totalCostCents = $item->unit_cost_cents ? abs($quantityChange) * $item->unit_cost_cents : null;

            return InventoryMovement::create([
                'organization_id' => $item->organization_id,
                'branch_id' => $item->branch_id,
                'inventory_item_id' => $item->id,
                'movement_type' => $movementType,
                'quantity' => $quantityChange,
                'quantity_before' => $current,
                'quantity_after' => $newStock,
                'unit_cost_cents' => $item->unit_cost_cents,
                'total_cost_cents' => $totalCostCents,
                'reference_type' => 'manual_adjustment',
                'department' => $department,
                'performed_by' => $userId,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Record clinical / ward department consumption of inventory items.
     */
    public function consumeStock(
        InventoryItem $item,
        int $quantity,
        string $department,
        string $userId,
        ?string $notes = null
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw new DomainException("Consumption quantity must be greater than zero.");
        }

        return $this->adjustStock(
            $item,
            -$quantity,
            'consumption',
            $userId,
            $department,
            $notes ?: "Departmental consumption: {$department}"
        );
    }

    /**
     * Reconcile stock count against recorded movements.
     */
    public function reconcileStock(InventoryItem $item): array
    {
        $movements = InventoryMovement::where('inventory_item_id', $item->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $calculatedStock = 0;
        $totalReceived = 0;
        $totalConsumed = 0;
        $totalAdjusted = 0;

        foreach ($movements as $m) {
            $calculatedStock += $m->quantity;
            if ($m->movement_type === 'purchase_receipt') {
                $totalReceived += $m->quantity;
            } elseif ($m->movement_type === 'consumption') {
                $totalConsumed += abs($m->quantity);
            } else {
                $totalAdjusted += $m->quantity;
            }
        }

        $discrepancy = $item->current_stock - $calculatedStock;

        return [
            'item_id' => $item->id,
            'item_code' => $item->item_code,
            'item_name' => $item->name,
            'current_stock' => $item->current_stock,
            'calculated_stock_from_movements' => $calculatedStock,
            'total_received_from_po' => $totalReceived,
            'total_consumed_by_departments' => $totalConsumed,
            'net_manual_adjustments' => $totalAdjusted,
            'discrepancy' => $discrepancy,
            'is_reconciled' => ($discrepancy === 0),
            'total_movement_records' => $movements->count(),
        ];
    }

    /**
     * Retrieve items that have breached or reached reorder threshold.
     */
    public function getLowStockAlerts(?string $branchId = null): array
    {
        $query = InventoryItem::query()
            ->with('defaultVendor')
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'min_stock_level')
            ->orderBy('current_stock', 'asc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $items = $query->get();

        return $items->map(function ($item) {
            $reorderQty = $item->reorder_quantity > 0
                ? $item->reorder_quantity
                : max(1, ($item->min_stock_level * 2) - $item->current_stock);

            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'name' => $item->name,
                'category' => $item->category,
                'sub_category' => $item->sub_category,
                'unit_of_measure' => $item->unit_of_measure,
                'current_stock' => $item->current_stock,
                'min_stock_level' => $item->min_stock_level,
                'reorder_quantity' => $reorderQty,
                'unit_cost' => $item->unit_cost,
                'estimated_reorder_cost' => round($reorderQty * $item->unit_cost, 2),
                'stock_status' => $item->stock_status,
                'storage_location' => $item->storage_location,
                'default_vendor' => $item->defaultVendor ? [
                    'id' => $item->defaultVendor->id,
                    'name' => $item->defaultVendor->name,
                    'email' => $item->defaultVendor->email,
                ] : null,
            ];
        })->toArray();
    }

    /**
     * High-level executive inventory summary metrics.
     */
    public function getInventorySummary(?string $branchId = null): array
    {
        $query = InventoryItem::query()->where('is_active', true);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalItems = (clone $query)->count();
        $outOfStock = (clone $query)->where('current_stock', '<=', 0)->count();
        $lowStock = (clone $query)->whereColumn('current_stock', '<=', 'min_stock_level')->where('current_stock', '>', 0)->count();
        $adequateStock = max(0, $totalItems - $outOfStock - $lowStock);

        $totalValuationCents = (clone $query)->select(DB::raw('SUM(current_stock * unit_cost_cents) as val'))->value('val') ?? 0;

        $medicalCount = (clone $query)->where('category', 'medical')->count();
        $nonMedicalCount = (clone $query)->where('category', 'non_medical')->count();

        return [
            'total_inventory_items' => $totalItems,
            'out_of_stock_count' => $outOfStock,
            'low_stock_count' => $lowStock,
            'adequate_stock_count' => $adequateStock,
            'total_valuation_cents' => (int) $totalValuationCents,
            'total_valuation' => ((int) $totalValuationCents) / 100,
            'medical_items_count' => $medicalCount,
            'non_medical_items_count' => $nonMedicalCount,
        ];
    }
}
