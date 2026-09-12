<?php

namespace App\Domain\Pharmacy\Services;

use App\Domain\Pharmacy\Models\Drug;
use App\Domain\Pharmacy\Models\DrugBatch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PharmacyAlertService
{
    /**
     * Retrieve all drugs with inventory at or below their configured reorder threshold.
     */
    public function getLowStockAlerts(?string $branchId = null): array
    {
        $query = Drug::query()
            ->where('is_active', true)
            ->with(['batches' => function ($q) {
                $q->where('quantity_on_hand', '>', 0)
                  ->where('expiry_date', '>', now()->toDateString())
                  ->where('status', '!=', 'quarantined');
            }]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $drugs = $query->get();

        $alerts = [];
        foreach ($drugs as $drug) {
            $totalStock = $drug->total_stock_on_hand;
            if ($totalStock <= $drug->reorder_threshold) {
                $severity = ($totalStock === 0) ? 'critical' : 'warning';
                $deficit = max(0, $drug->reorder_threshold - $totalStock);

                $alerts[] = [
                    'drug_id' => $drug->id,
                    'sku' => $drug->sku,
                    'brand_name' => $drug->brand_name,
                    'generic_name' => $drug->generic_name,
                    'strength' => $drug->strength,
                    'form' => $drug->form,
                    'unit_of_measure' => $drug->unit_of_measure,
                    'current_stock' => $totalStock,
                    'reorder_threshold' => $drug->reorder_threshold,
                    'target_stock_level' => $drug->target_stock_level,
                    'deficit' => $deficit,
                    'severity' => $severity,
                    'message' => $totalStock === 0
                        ? "CRITICAL: '{$drug->brand_name}' ({$drug->strength}) is OUT OF STOCK. Reorder threshold is {$drug->reorder_threshold}."
                        : "LOW STOCK: '{$drug->brand_name}' ({$drug->strength}) is at {$totalStock} {$drug->unit_of_measure}(s), at or below reorder threshold of {$drug->reorder_threshold}.",
                ];
            }
        }

        // Sort critical alerts (out of stock) first, then lowest stock ratio
        usort($alerts, function ($a, $b) {
            if ($a['severity'] === 'critical' && $b['severity'] !== 'critical') {
                return -1;
            }
            if ($a['severity'] !== 'critical' && $b['severity'] === 'critical') {
                return 1;
            }
            return $a['current_stock'] <=> $b['current_stock'];
        });

        return $alerts;
    }

    /**
     * Retrieve all batches that are already expired or approaching expiry within $daysThreshold days.
     */
    public function getExpiringBatchesAlerts(?string $branchId = null, int $daysThreshold = 60): array
    {
        $today = Carbon::today();
        $cutoffDate = $today->copy()->addDays($daysThreshold)->toDateString();

        $query = DrugBatch::query()
            ->where('quantity_on_hand', '>', 0)
            ->where('expiry_date', '<=', $cutoffDate)
            ->with('drug');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $batches = $query->orderBy('expiry_date', 'asc')->get();

        $alerts = [];
        foreach ($batches as $batch) {
            $expiry = Carbon::parse($batch->expiry_date);
            $isExpired = $expiry->isPast() || $expiry->isSameDay($today);
            $daysRemaining = (int) $today->diffInDays($expiry, false);

            if ($isExpired) {
                $statusLevel = 'expired';
                $severity = 'critical';
                $msg = "EXPIRED: Batch '{$batch->batch_number}' of {$batch->drug->brand_name} expired on {$expiry->toDateString()}! Blocked from dispensing.";
            } elseif ($daysRemaining <= 30) {
                $statusLevel = 'critical_near_expiry';
                $severity = 'high';
                $msg = "EXPIRING IN {$daysRemaining} DAYS: Batch '{$batch->batch_number}' of {$batch->drug->brand_name} expires on {$expiry->toDateString()}. Prioritize dispensing under FEFO.";
            } else {
                $statusLevel = 'warning_near_expiry';
                $severity = 'medium';
                $msg = "Batch '{$batch->batch_number}' of {$batch->drug->brand_name} will expire in {$daysRemaining} days ({$expiry->toDateString()}).";
            }

            $alerts[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'drug_id' => $batch->drug_id,
                'drug_sku' => $batch->drug->sku ?? '',
                'drug_name' => $batch->drug->brand_name ?? '',
                'generic_name' => $batch->drug->generic_name ?? '',
                'quantity_on_hand' => $batch->quantity_on_hand,
                'manufacturing_date' => $batch->manufacturing_date ? $batch->manufacturing_date->toDateString() : null,
                'expiry_date' => $expiry->toDateString(),
                'days_remaining' => $daysRemaining,
                'status_level' => $statusLevel,
                'severity' => $severity,
                'message' => $msg,
            ];
        }

        return $alerts;
    }

    /**
     * Dashboard aggregate metrics for inventory and safety.
     */
    public function getInventoryMetrics(?string $branchId = null): array
    {
        $lowStockAlerts = $this->getLowStockAlerts($branchId);
        $expiryAlerts = $this->getExpiringBatchesAlerts($branchId, 60);

        $outOfStockCount = count(array_filter($lowStockAlerts, fn($a) => $a['current_stock'] === 0));
        $expiredBatchesCount = count(array_filter($expiryAlerts, fn($a) => $a['status_level'] === 'expired'));
        $nearExpiryBatchesCount = count(array_filter($expiryAlerts, fn($a) => in_array($a['status_level'], ['critical_near_expiry', 'warning_near_expiry'], true)));

        $drugsQuery = Drug::query()->where('is_active', true);
        if ($branchId) {
            $drugsQuery->where('branch_id', $branchId);
        }
        $totalDrugs = $drugsQuery->count();

        $batchesQuery = DrugBatch::query()->where('quantity_on_hand', '>', 0);
        if ($branchId) {
            $batchesQuery->where('branch_id', $branchId);
        }
        $totalUnits = (int) $batchesQuery->sum('quantity_on_hand');
        $totalValueCents = (int) $batchesQuery->select(DB::raw('SUM(quantity_on_hand * unit_cost_cents) as total_val'))->value('total_val');

        return [
            'total_active_drugs' => $totalDrugs,
            'total_units_in_stock' => $totalUnits,
            'total_inventory_cost_value_cents' => $totalValueCents,
            'low_stock_count' => count($lowStockAlerts),
            'out_of_stock_count' => $outOfStockCount,
            'expired_batches_count' => $expiredBatchesCount,
            'near_expiry_batches_count' => $nearExpiryBatchesCount,
        ];
    }
}
