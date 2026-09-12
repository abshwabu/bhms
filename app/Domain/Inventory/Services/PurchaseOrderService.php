<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\PurchaseOrderItem;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * Generate unique sequential PO number: PO-2026-0001
     */
    public function generatePoNumber(): string
    {
        $year = Carbon::now()->format('Y');
        $prefix = "PO-{$year}-";

        $count = PurchaseOrder::where('po_number', 'like', "{$prefix}%")->count();
        $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$seq}";
    }

    /**
     * Create new purchase order in draft state.
     */
    public function createPurchaseOrder(array $data, string $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $poNumber = $this->generatePoNumber();

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'organization_id' => $data['organization_id'],
                'branch_id' => $data['branch_id'],
                'vendor_id' => $data['vendor_id'],
                'status' => 'draft',
                'order_date' => $data['order_date'] ?? Carbon::today(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'tax_cents' => (int) ($data['tax_cents'] ?? 0),
                'shipping_cost_cents' => (int) ($data['shipping_cost_cents'] ?? 0),
                'currency' => $data['currency'] ?? 'USD',
                'created_by' => $userId,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotalCents = 0;

            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $item = InventoryItem::findOrFail($itemData['inventory_item_id']);
                    $qtyOrdered = (int) $itemData['quantity_ordered'];
                    $unitCostCents = isset($itemData['unit_cost_cents'])
                        ? (int) $itemData['unit_cost_cents']
                        : $item->unit_cost_cents;
                    $lineTotal = $qtyOrdered * $unitCostCents;

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'inventory_item_id' => $item->id,
                        'quantity_ordered' => $qtyOrdered,
                        'quantity_received' => 0,
                        'unit_cost_cents' => $unitCostCents,
                        'total_cost_cents' => $lineTotal,
                        'notes' => $itemData['notes'] ?? null,
                    ]);

                    $subtotalCents += $lineTotal;
                }
            }

            $po->subtotal_cents = $subtotalCents;
            $po->total_cents = $subtotalCents + $po->tax_cents + $po->shipping_cost_cents;
            $po->save();

            return $po->load(['vendor', 'items.inventoryItem', 'creator']);
        });
    }

    /**
     * Submit purchase order for supervisor approval.
     */
    public function submitPurchaseOrder(PurchaseOrder $po, string $userId): PurchaseOrder
    {
        if ($po->status !== 'draft') {
            throw new DomainException("Only draft purchase orders can be submitted for approval. Current status: {$po->status}");
        }

        if ($po->items()->count() === 0) {
            throw new DomainException("Cannot submit a purchase order with no line items.");
        }

        $po->status = 'submitted';
        $po->submitted_by = $userId;
        $po->submitted_at = Carbon::now();
        $po->save();

        return $po->load(['vendor', 'items.inventoryItem', 'submitter']);
    }

    /**
     * Approve submitted purchase order (Authorizes procurement & stock receipt).
     */
    public function approvePurchaseOrder(PurchaseOrder $po, string $approverId): PurchaseOrder
    {
        if ($po->status !== 'submitted') {
            throw new DomainException("Purchase order must be submitted before approval. Current status: {$po->status}");
        }

        $po->status = 'approved';
        $po->approved_by = $approverId;
        $po->approved_at = Carbon::now();
        $po->rejection_reason = null;
        $po->save();

        return $po->load(['vendor', 'items.inventoryItem', 'approver']);
    }

    /**
     * Reject submitted purchase order.
     */
    public function rejectPurchaseOrder(PurchaseOrder $po, string $rejectorId, string $reason): PurchaseOrder
    {
        if ($po->status !== 'submitted') {
            throw new DomainException("Only submitted purchase orders can be rejected. Current status: {$po->status}");
        }

        $po->status = 'rejected';
        $po->approved_by = $rejectorId;
        $po->approved_at = Carbon::now();
        $po->rejection_reason = $reason;
        $po->save();

        return $po->load(['vendor', 'items.inventoryItem', 'approver']);
    }

    /**
     * Receive items into inventory from an approved purchase order.
     * Acceptance criterion: Purchase orders follow a defined approval chain before stock is received.
     */
    public function receiveItems(PurchaseOrder $po, array $receivedItems, string $userId): PurchaseOrder
    {
        if (!$po->can_receive) {
            throw new DomainException("Stock can only be received against approved purchase orders. Current status: {$po->status}");
        }

        return DB::transaction(function () use ($po, $receivedItems, $userId) {
            $allReceived = true;

            foreach ($receivedItems as $rec) {
                $poItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where('id', $rec['purchase_order_item_id'])
                    ->firstOrFail();

                $toReceive = (int) $rec['quantity_received'];
                if ($toReceive <= 0) {
                    continue;
                }

                $newTotalReceived = $poItem->quantity_received + $toReceive;
                if ($newTotalReceived > $poItem->quantity_ordered) {
                    throw new DomainException("Cannot receive {$toReceive} units for {$poItem->inventoryItem->name}. Ordered: {$poItem->quantity_ordered}, Already received: {$poItem->quantity_received}");
                }

                $poItem->quantity_received = $newTotalReceived;
                $poItem->save();

                // Increment stock on inventory item & log audit movement
                $invItem = $poItem->inventoryItem;
                $beforeStock = $invItem->current_stock;
                $invItem->current_stock += $toReceive;
                $invItem->save();

                InventoryMovement::create([
                    'organization_id' => $po->organization_id,
                    'branch_id' => $po->branch_id,
                    'inventory_item_id' => $invItem->id,
                    'movement_type' => 'purchase_receipt',
                    'quantity' => $toReceive,
                    'quantity_before' => $beforeStock,
                    'quantity_after' => $invItem->current_stock,
                    'unit_cost_cents' => $poItem->unit_cost_cents,
                    'total_cost_cents' => $toReceive * $poItem->unit_cost_cents,
                    'reference_type' => 'purchase_order',
                    'reference_id' => $po->id,
                    'department' => 'Central Warehouse',
                    'performed_by' => $userId,
                    'notes' => "Received from PO #{$po->po_number} ({$po->vendor->name})",
                ]);
            }

            // Check if all items in PO are now received
            $po->refresh();
            foreach ($po->items as $item) {
                if ($item->quantity_received < $item->quantity_ordered) {
                    $allReceived = false;
                    break;
                }
            }

            $po->status = $allReceived ? 'received' : 'partially_received';
            $po->save();

            return $po->load(['vendor', 'items.inventoryItem']);
        });
    }
}
