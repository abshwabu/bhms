<?php

namespace App\Domain\Pharmacy\Services;

use App\Domain\Pharmacy\Models\Drug;
use App\Domain\Pharmacy\Models\DrugBatch;
use App\Domain\Pharmacy\Models\DrugStockMovement;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PharmacyInventoryService
{
    /**
     * Intake new stock lot for a drug.
     */
    public function intakeStock(array $data, string $userId): DrugBatch
    {
        return DB::transaction(function () use ($data, $userId) {
            $drug = Drug::findOrFail($data['drug_id']);

            // Validate expiry date is in future
            $expiryDate = $data['expiry_date'];
            if (strtotime($expiryDate) <= strtotime('today')) {
                throw new DomainException("Cannot intake expired batch. Expiry date must be in the future.");
            }

            // Check if batch number already exists for this drug
            $existingBatch = DrugBatch::where('drug_id', $drug->id)
                ->where('batch_number', $data['batch_number'])
                ->first();

            if ($existingBatch) {
                // Increment existing batch
                $quantity = (int) $data['quantity_received'];
                $existingBatch->incrementStock(
                    $quantity,
                    'intake',
                    $data['notes'] ?? 'Additional intake for existing batch',
                    'purchase_order',
                    null,
                    $userId
                );
                return $existingBatch->fresh(['drug']);
            }

            $batch = DrugBatch::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $drug->organization_id,
                'branch_id' => $drug->branch_id,
                'drug_id' => $drug->id,
                'batch_number' => $data['batch_number'],
                'manufacturing_date' => $data['manufacturing_date'] ?? null,
                'expiry_date' => $expiryDate,
                'quantity_received' => $data['quantity_received'],
                'quantity_on_hand' => $data['quantity_received'],
                'unit_cost_cents' => $data['unit_cost_cents'] ?? $drug->unit_cost_cents,
                'supplier_name' => $data['supplier_name'] ?? null,
                'status' => 'active',
            ]);

            // Create movement record
            DrugStockMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $drug->organization_id,
                'branch_id' => $drug->branch_id,
                'drug_id' => $drug->id,
                'drug_batch_id' => $batch->id,
                'movement_type' => 'intake',
                'quantity' => $batch->quantity_received,
                'quantity_before' => 0,
                'quantity_after' => $batch->quantity_received,
                'reference_type' => 'purchase_order',
                'reference_id' => null,
                'reason' => $data['notes'] ?? 'Initial batch stock intake',
                'performed_by' => $userId,
            ]);

            return $batch->load('drug');
        });
    }

    /**
     * Perform an audit stock adjustment (either positive or negative).
     */
    public function adjustStock(string $batchId, int $quantityDelta, string $reason, string $userId): DrugStockMovement
    {
        return DB::transaction(function () use ($batchId, $quantityDelta, $reason, $userId) {
            $batch = DrugBatch::findOrFail($batchId);

            if ($quantityDelta === 0) {
                throw new DomainException("Quantity delta cannot be zero for stock adjustment.");
            }

            if ($quantityDelta > 0) {
                return $batch->incrementStock(
                    $quantityDelta,
                    'adjustment_addition',
                    $reason,
                    'manual_adjustment',
                    null,
                    $userId
                );
            } else {
                $deductAmount = abs($quantityDelta);
                if ($batch->quantity_on_hand < $deductAmount) {
                    throw new DomainException(
                        "Cannot deduct {$deductAmount} units from batch '{$batch->batch_number}'. Current stock is {$batch->quantity_on_hand}."
                    );
                }

                return $batch->decrementStock(
                    $deductAmount,
                    'adjustment_deduction',
                    $reason,
                    'manual_adjustment',
                    null,
                    $userId
                );
            }
        });
    }

    /**
     * Quarantine a batch to immediately remove it from active FEFO dispensing.
     */
    public function quarantineBatch(string $batchId, string $reason, string $userId): DrugBatch
    {
        return DB::transaction(function () use ($batchId, $reason, $userId) {
            $batch = DrugBatch::findOrFail($batchId);
            $batch->status = 'quarantined';
            $batch->save();

            DrugStockMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $batch->organization_id,
                'branch_id' => $batch->branch_id,
                'drug_id' => $batch->drug_id,
                'drug_batch_id' => $batch->id,
                'movement_type' => 'adjustment_deduction',
                'quantity' => 0,
                'quantity_before' => $batch->quantity_on_hand,
                'quantity_after' => $batch->quantity_on_hand,
                'reference_type' => 'quarantine',
                'reference_id' => null,
                'reason' => "Batch quarantined: {$reason}",
                'performed_by' => $userId,
            ]);

            return $batch;
        });
    }

    /**
     * Write off stock due to damage, contamination, or expiration.
     */
    public function writeOffStock(string $batchId, int $quantity, string $reason, string $userId): DrugStockMovement
    {
        return DB::transaction(function () use ($batchId, $quantity, $reason, $userId) {
            $batch = DrugBatch::findOrFail($batchId);

            if ($quantity <= 0) {
                throw new DomainException("Waste write-off quantity must be greater than zero.");
            }

            if ($batch->quantity_on_hand < $quantity) {
                throw new DomainException("Cannot write off {$quantity} units. Batch only has {$batch->quantity_on_hand} units on hand.");
            }

            $before = $batch->quantity_on_hand;
            $after = $before - $quantity;
            $batch->quantity_on_hand = $after;
            if ($after === 0) {
                $batch->status = 'depleted';
            }
            $batch->save();

            return DrugStockMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $batch->organization_id,
                'branch_id' => $batch->branch_id,
                'drug_id' => $batch->drug_id,
                'drug_batch_id' => $batch->id,
                'movement_type' => 'waste',
                'quantity' => -$quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference_type' => 'waste_write_off',
                'reference_id' => null,
                'reason' => $reason,
                'performed_by' => $userId,
            ]);
        });
    }
}
