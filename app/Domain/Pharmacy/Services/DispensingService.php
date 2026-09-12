<?php

namespace App\Domain\Pharmacy\Services;

use App\Domain\Clinical\Models\Prescription;
use App\Domain\Clinical\Models\PrescriptionItem;
use App\Domain\Clinical\Services\DrugInteractionService;
use App\Domain\Pharmacy\Models\DispensingRecord;
use App\Domain\Pharmacy\Models\DispensingRecordItem;
use App\Domain\Pharmacy\Models\Drug;
use App\Domain\Pharmacy\Models\DrugBatch;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DispensingService
{
    public function __construct(
        protected DrugInteractionService $drugInteractionService
    ) {}

    /**
     * Preview dispensing plan for a prescription:
     * - Matches prescription items to catalog drugs
     * - Computes FEFO batch allocations
     * - Runs secondary drug-drug and allergy interaction checks
     */
    public function previewDispensation(string $prescriptionId): array
    {
        $prescription = Prescription::with(['patient.allergies', 'doctor', 'items'])->findOrFail($prescriptionId);

        $medicationCheckItems = [];
        $itemsPlan = [];
        $hasShortage = false;

        foreach ($prescription->items as $item) {
            $medicationCheckItems[] = [
                'medication_name' => $item->medication_name,
                'generic_name' => $item->generic_name,
                'dosage' => $item->dosage,
            ];

            // Match drug by generic_name or brand_name or medication_name
            $drug = $this->findMatchingDrug($item, $prescription->branch_id);

            $batchAllocations = [];
            $availableStock = 0;
            $neededQuantity = $item->quantity;

            if ($drug) {
                // Fetch non-expired active batches ordered by expiry_date ASC (FEFO)
                $batches = DrugBatch::where('drug_id', $drug->id)
                    ->where('quantity_on_hand', '>', 0)
                    ->where('expiry_date', '>', Carbon::today()->toDateString())
                    ->where('status', '!=', 'quarantined')
                    ->orderBy('expiry_date', 'asc')
                    ->get();

                $availableStock = $batches->sum('quantity_on_hand');
                $remainingNeeded = $neededQuantity;

                foreach ($batches as $batch) {
                    if ($remainingNeeded <= 0) {
                        break;
                    }
                    $allocateQty = min($remainingNeeded, $batch->quantity_on_hand);
                    $batchAllocations[] = [
                        'batch_id' => $batch->id,
                        'batch_number' => $batch->batch_number,
                        'expiry_date' => $batch->expiry_date->toDateString(),
                        'days_until_expiry' => $batch->days_until_expiry,
                        'quantity_to_deduct' => $allocateQty,
                        'quantity_on_hand' => $batch->quantity_on_hand,
                    ];
                    $remainingNeeded -= $allocateQty;
                }

                if ($availableStock < $neededQuantity) {
                    $hasShortage = true;
                }
            } else {
                $hasShortage = true;
            }

            $itemsPlan[] = [
                'prescription_item_id' => $item->id,
                'medication_name' => $item->medication_name,
                'generic_name' => $item->generic_name,
                'dosage' => $item->dosage,
                'frequency' => $item->frequency,
                'instructions' => $item->instructions,
                'prescribed_quantity' => $item->quantity,
                'status' => $item->status,
                'drug' => $drug ? [
                    'id' => $drug->id,
                    'sku' => $drug->sku,
                    'brand_name' => $drug->brand_name,
                    'generic_name' => $drug->generic_name,
                    'unit_price_cents' => $drug->unit_price_cents,
                    'unit_price' => $drug->unit_price,
                    'total_stock_on_hand' => $drug->total_stock_on_hand,
                ] : null,
                'available_stock' => $availableStock,
                'is_in_stock' => $availableStock >= $neededQuantity,
                'fefo_batch_allocations' => $batchAllocations,
            ];
        }

        // Secondary interaction checks at dispensing time
        $interactionAlerts = $this->drugInteractionService->check($prescription->patient, $medicationCheckItems);

        return [
            'prescription' => [
                'id' => $prescription->id,
                'prescription_number' => $prescription->prescription_number,
                'patient' => [
                    'id' => $prescription->patient->id,
                    'full_name' => $prescription->patient->full_name,
                    'mrn' => $prescription->patient->mrn,
                    'allergies' => $prescription->patient->allergies,
                ],
                'doctor' => [
                    'id' => $prescription->doctor->id ?? null,
                    'name' => $prescription->doctor->name ?? 'Attending Physician',
                ],
                'status' => $prescription->status,
                'prescribed_at' => $prescription->prescribed_at ? $prescription->prescribed_at->toIso8601String() : null,
            ],
            'items' => $itemsPlan,
            'interaction_alerts' => $interactionAlerts,
            'has_interaction_warnings' => !empty($interactionAlerts),
            'has_stock_shortage' => $hasShortage,
            'can_dispense' => !$hasShortage && in_array($prescription->status, ['finalized', 'partial'], true),
        ];
    }

    /**
     * Dispense a prescription, allocating batches using FEFO,
     * decrementing stock, recording movements, and updating prescription state.
     */
    public function dispense(array $data, string $pharmacistId): DispensingRecord
    {
        return DB::transaction(function () use ($data, $pharmacistId) {
            $prescription = Prescription::with(['patient.allergies', 'items'])->lockForUpdate()->findOrFail($data['prescription_id']);

            if ($prescription->status === 'dispensed') {
                throw new DomainException("Prescription {$prescription->prescription_number} has already been fully dispensed.");
            }

            if ($prescription->status === 'cancelled') {
                throw new DomainException("Prescription {$prescription->prescription_number} is cancelled and cannot be dispensed.");
            }

            // Run secondary interaction check
            $medicationCheckItems = [];
            foreach ($prescription->items as $item) {
                $medicationCheckItems[] = [
                    'medication_name' => $item->medication_name,
                    'generic_name' => $item->generic_name,
                ];
            }
            $interactionAlerts = $this->drugInteractionService->check($prescription->patient, $medicationCheckItems);
            $hasInteractions = !empty($interactionAlerts);

            $dispensationNumber = 'DSP-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $dispensingRecord = DispensingRecord::create([
                'id' => (string) Str::uuid(),
                'dispensation_number' => $dispensationNumber,
                'organization_id' => $prescription->organization_id,
                'branch_id' => $prescription->branch_id,
                'prescription_id' => $prescription->id,
                'patient_id' => $prescription->patient_id,
                'pharmacist_id' => $pharmacistId,
                'status' => 'completed',
                'has_interaction_warnings' => $hasInteractions,
                'interaction_alerts' => $interactionAlerts,
                'pharmacist_notes' => $data['pharmacist_notes'] ?? null,
                'counseling_notes' => $data['counseling_notes'] ?? null,
                'dispensed_at' => Carbon::now(),
            ]);

            $customItemsInput = collect($data['items'] ?? [])->keyBy('prescription_item_id');

            foreach ($prescription->items as $prescriptionItem) {
                // If item is already dispensed, skip
                if ($prescriptionItem->status === 'dispensed') {
                    continue;
                }

                $itemOverride = $customItemsInput->get($prescriptionItem->id);

                // Identify Drug
                $drugId = $itemOverride['drug_id'] ?? null;
                $drug = $drugId ? Drug::findOrFail($drugId) : $this->findMatchingDrug($prescriptionItem, $prescription->branch_id);

                if (!$drug) {
                    throw new DomainException("No catalog drug found for medication '{$prescriptionItem->medication_name}'. Please map to an inventory SKU.");
                }

                $qtyNeeded = isset($itemOverride['quantity']) ? (int) $itemOverride['quantity'] : $prescriptionItem->quantity;
                if ($qtyNeeded <= 0) {
                    continue;
                }

                // If specific batch requested
                $specificBatchId = $itemOverride['batch_id'] ?? null;
                if ($specificBatchId) {
                    $batch = DrugBatch::lockForUpdate()->findOrFail($specificBatchId);
                    if ($batch->drug_id !== $drug->id) {
                        throw new DomainException("Batch '{$batch->batch_number}' does not belong to drug '{$drug->brand_name}'.");
                    }
                    if ($batch->is_expired) {
                        throw new DomainException("Cannot dispense from expired batch '{$batch->batch_number}'. Expiry was {$batch->expiry_date->toDateString()}.");
                    }
                    if ($batch->quantity_on_hand < $qtyNeeded) {
                        throw new DomainException("Insufficient stock in batch '{$batch->batch_number}'. Available: {$batch->quantity_on_hand}, Requested: {$qtyNeeded}.");
                    }

                    $batch->decrementStock(
                        $qtyNeeded,
                        'dispense',
                        "Prescription {$prescription->prescription_number}",
                        'dispensing_record',
                        $dispensingRecord->id,
                        $pharmacistId
                    );

                    DispensingRecordItem::create([
                        'id' => (string) Str::uuid(),
                        'dispensing_record_id' => $dispensingRecord->id,
                        'prescription_item_id' => $prescriptionItem->id,
                        'drug_id' => $drug->id,
                        'drug_batch_id' => $batch->id,
                        'quantity_dispensed' => $qtyNeeded,
                        'directions' => $prescriptionItem->instructions,
                        'unit_price_cents' => $drug->unit_price_cents,
                        'total_price_cents' => $qtyNeeded * $drug->unit_price_cents,
                    ]);
                } else {
                    // FEFO Allocation: strict ordering by expiry_date ASC, excluding expired and quarantined
                    $eligibleBatches = DrugBatch::where('drug_id', $drug->id)
                        ->where('quantity_on_hand', '>', 0)
                        ->where('expiry_date', '>', Carbon::today()->toDateString())
                        ->where('status', '!=', 'quarantined')
                        ->orderBy('expiry_date', 'asc')
                        ->lockForUpdate()
                        ->get();

                    $totalAvailable = $eligibleBatches->sum('quantity_on_hand');
                    if ($totalAvailable < $qtyNeeded) {
                        throw new DomainException(
                            "Insufficient active non-expired stock for '{$drug->brand_name}'. Required: {$qtyNeeded}, Available: {$totalAvailable}."
                        );
                    }

                    $remainingToDeduct = $qtyNeeded;
                    foreach ($eligibleBatches as $batch) {
                        if ($remainingToDeduct <= 0) {
                            break;
                        }

                        $deductThisBatch = min($remainingToDeduct, $batch->quantity_on_hand);

                        $batch->decrementStock(
                            $deductThisBatch,
                            'dispense',
                            "Prescription {$prescription->prescription_number} (FEFO Batch {$batch->batch_number})",
                            'dispensing_record',
                            $dispensingRecord->id,
                            $pharmacistId
                        );

                        DispensingRecordItem::create([
                            'id' => (string) Str::uuid(),
                            'dispensing_record_id' => $dispensingRecord->id,
                            'prescription_item_id' => $prescriptionItem->id,
                            'drug_id' => $drug->id,
                            'drug_batch_id' => $batch->id,
                            'quantity_dispensed' => $deductThisBatch,
                            'directions' => $prescriptionItem->instructions,
                            'unit_price_cents' => $drug->unit_price_cents,
                            'total_price_cents' => $deductThisBatch * $drug->unit_price_cents,
                        ]);

                        $remainingToDeduct -= $deductThisBatch;
                    }
                }

                // Update item status
                $prescriptionItem->status = 'dispensed';
                $prescriptionItem->save();
            }

            // Update prescription status
            $hasUndispensed = $prescription->items()->where('status', '!=', 'dispensed')->exists();
            $prescription->status = $hasUndispensed ? 'partial' : 'dispensed';
            $prescription->save();

            return $dispensingRecord->load(['items.drug', 'items.batch', 'prescription', 'patient', 'pharmacist']);
        });
    }

    /**
     * Match a prescription item to a drug in inventory.
     */
    protected function findMatchingDrug(PrescriptionItem $item, string $branchId): ?Drug
    {
        $medName = trim($item->medication_name);
        $genericName = trim($item->generic_name ?? '');

        // 1. Exact match by brand_name or generic_name
        $match = Drug::where('branch_id', $branchId)
            ->where('is_active', true)
            ->where(function ($q) use ($medName, $genericName) {
                $q->whereRaw('LOWER(brand_name) = ?', [strtolower($medName)])
                  ->orWhereRaw('LOWER(generic_name) = ?', [strtolower($medName)]);

                if (!empty($genericName)) {
                    $q->orWhereRaw('LOWER(generic_name) = ?', [strtolower($genericName)])
                      ->orWhereRaw('LOWER(brand_name) = ?', [strtolower($genericName)]);
                }
            })
            ->first();

        if ($match) {
            return $match;
        }

        // 2. Substring match
        return Drug::where('branch_id', $branchId)
            ->where('is_active', true)
            ->where(function ($q) use ($medName, $genericName) {
                $q->where('brand_name', 'ILIKE', "%{$medName}%")
                  ->orWhere('generic_name', 'ILIKE', "%{$medName}%");

                if (!empty($genericName)) {
                    $q->orWhere('generic_name', 'ILIKE', "%{$genericName}%");
                }
            })
            ->first();
    }
}
