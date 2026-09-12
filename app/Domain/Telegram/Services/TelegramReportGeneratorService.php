<?php

namespace App\Domain\Telegram\Services;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Emergency\Models\EmergencyCase;
use App\Domain\IPD\Models\Admission;
use App\Domain\IPD\Models\Bed;
use App\Domain\Laboratory\Models\LabResult;
use App\Domain\Pharmacy\Models\Drug;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class TelegramReportGeneratorService
{
    /**
     * Generate Daily Operational Digest data and Telegram HTML.
     */
    public function generateDailyOperationalDigest(?string $branchId = null): array
    {
        $today = Carbon::today();

        // 1. Bed metrics
        $bedsQuery = Bed::query()->where('is_active', true);
        if ($branchId) {
            $bedsQuery->where('branch_id', $branchId);
        }
        $totalBeds = (clone $bedsQuery)->count();
        $occupiedBeds = (clone $bedsQuery)->where('status', 'occupied')->count();
        $availableBeds = (clone $bedsQuery)->where('status', 'available')->count();

        $totalIcuBeds = (clone $bedsQuery)->where('bed_type', 'icu')->count();
        $availableIcuBeds = (clone $bedsQuery)->where('bed_type', 'icu')->where('status', 'available')->count();
        $occupancyPct = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0;

        // 2. Admissions & Discharges
        $admissionsQuery = Admission::query();
        if ($branchId) {
            $admissionsQuery->where('branch_id', $branchId);
        }
        $admissionsToday = (clone $admissionsQuery)->whereDate('admitted_at', $today)->count();
        $dischargesToday = (clone $admissionsQuery)->where('status', 'discharged')->whereDate('discharged_at', $today)->count();
        $activeAdmissions = (clone $admissionsQuery)->where('status', 'admitted')->count();

        // 3. Emergency
        $emergencyCasesToday = 0;
        $activeEmergency = 0;
        if (Schema::hasTable('emergency_cases')) {
            $emQuery = EmergencyCase::query();
            if ($branchId) {
                $emQuery->where('branch_id', $branchId);
            }
            $emergencyCasesToday = (clone $emQuery)->whereDate('created_at', $today)->count();
            $activeEmergency = (clone $emQuery)->whereNotIn('status', ['discharged', 'transferred', 'deceased'])->count();
        }

        // 4. Revenue (Billing)
        $invoicesTotalCents = 0;
        $invoicesPaidCents = 0;
        if (Schema::hasTable('invoices')) {
            $invQuery = Invoice::query()->whereDate('created_at', $today);
            if ($branchId) {
                $invQuery->where('branch_id', $branchId);
            }
            $invoicesTotalCents = (int) (clone $invQuery)->sum('total_cents');
            $invoicesPaidCents = (int) (clone $invQuery)->sum('paid_cents');
        }

        // 5. Diagnostics / Laboratory
        $pendingLabOrders = 0;
        $criticalLabAlerts = 0;
        if (Schema::hasTable('lab_orders')) {
            $labOrderQuery = LabOrder::query()->whereNotIn('status', ['completed', 'cancelled']);
            if ($branchId) {
                $labOrderQuery->where('branch_id', $branchId);
            }
            $pendingLabOrders = $labOrderQuery->count();
        }
        if (Schema::hasTable('lab_results')) {
            $criticalLabQuery = LabResult::query()
                ->where('has_critical_values', true)
                ->whereNull('critical_acknowledged_at');
            if ($branchId) {
                $criticalLabQuery->where('branch_id', $branchId);
            }
            $criticalLabAlerts = $criticalLabQuery->count();
        }

        // 6. Pharmacy stock
        $lowStockDrugsCount = 0;
        if (Schema::hasTable('drugs')) {
            $drugs = Drug::with('activeBatches')->where('is_active', true)->get();
            foreach ($drugs as $drug) {
                if ($drug->is_low_stock) {
                    $lowStockDrugsCount++;
                }
            }
        }

        $rawData = [
            'date' => $today->toDateString(),
            'total_beds' => $totalBeds,
            'occupied_beds' => $occupiedBeds,
            'available_beds' => $availableBeds,
            'occupancy_pct' => $occupancyPct,
            'total_icu_beds' => $totalIcuBeds,
            'available_icu_beds' => $availableIcuBeds,
            'admissions_today' => $admissionsToday,
            'discharges_today' => $dischargesToday,
            'active_admissions' => $activeAdmissions,
            'emergency_today' => $emergencyCasesToday,
            'active_emergency' => $activeEmergency,
            'invoiced_amount' => $invoicesTotalCents / 100,
            'collected_amount' => $invoicesPaidCents / 100,
            'pending_lab_orders' => $pendingLabOrders,
            'critical_lab_alerts' => $criticalLabAlerts,
            'low_stock_drugs' => $lowStockDrugsCount,
        ];

        $html = "📊 <b>DAILY HOSPITAL OPERATIONAL DIGEST</b>\n"
            . "📅 <i>Date: {$today->toFormattedDateString()} | Time: " . now()->format('H:i T') . "</i>\n"
            . "──────────────────────────────\n"
            . "🏥 <b>Bed Occupancy & Census</b>\n"
            . "• Total Beds: <b>{$totalBeds}</b> | Occupied: <b>{$occupiedBeds} ({$occupancyPct}%)</b>\n"
            . "• Available Beds: <b>{$availableBeds}</b>\n"
            . "• ICU Beds Available: <b>{$availableIcuBeds} / {$totalIcuBeds}</b>\n\n"
            . "👥 <b>Patient Flow</b>\n"
            . "• Today's Admissions: <b>{$admissionsToday}</b>\n"
            . "• Today's Discharges: <b>{$dischargesToday}</b>\n"
            . "• Currently Admitted (IPD): <b>{$activeAdmissions}</b>\n"
            . "• Active Emergency Cases: <b>{$activeEmergency}</b>\n\n"
            . "💰 <b>Billing & Revenue</b>\n"
            . "• Total Invoiced: <b>$" . number_format($rawData['invoiced_amount'], 2) . "</b>\n"
            . "• Collections Received: <b>$" . number_format($rawData['collected_amount'], 2) . "</b>\n\n"
            . "🔬 <b>Diagnostics & Laboratory</b>\n"
            . "• Pending Orders: <b>{$pendingLabOrders}</b>\n"
            . "• Unacknowledged Panic/Critical Labs: <b>" . ($criticalLabAlerts > 0 ? "⚠️ {$criticalLabAlerts}" : "0") . "</b>\n\n"
            . "💊 <b>Pharmacy Alerts</b>\n"
            . "• Low Stock Reorder Items: <b>" . ($lowStockDrugsCount > 0 ? "⚠️ {$lowStockDrugsCount}" : "0") . "</b>\n"
            . "──────────────────────────────\n"
            . "<i>HMS Automated Telegram Dispatcher</i>";

        return [
            'raw_data' => $rawData,
            'html' => $html,
        ];
    }

    /**
     * Generate Shift Handover Report HTML.
     */
    public function generateShiftHandoverReport(string $shift = 'morning', ?string $branchId = null): array
    {
        $shiftTitles = [
            'morning' => 'Morning Shift (07:00 - 15:00)',
            'evening' => 'Evening Shift (15:00 - 23:00)',
            'night' => 'Night Shift (23:00 - 07:00)',
        ];
        $shiftTitle = $shiftTitles[strtolower($shift)] ?? (ucfirst($shift) . ' Shift');

        $activeAdmissions = Admission::where('status', 'admitted')->count();
        $icuOccupied = Bed::where('bed_type', 'icu')->where('status', 'occupied')->count();
        $icuAvailable = Bed::where('bed_type', 'icu')->where('status', 'available')->count();
        $activeEmergency = Schema::hasTable('emergency_cases') 
            ? EmergencyCase::whereNotIn('status', ['discharged', 'transferred'])->count() 
            : 0;

        $unacknowledgedCriticalLabs = Schema::hasTable('lab_results')
            ? LabResult::where('has_critical_values', true)->whereNull('critical_acknowledged_at')->count()
            : 0;

        $html = "🔄 <b>SHIFT HANDOVER REPORT</b>\n"
            . "⏰ <b>Shift:</b> {$shiftTitle}\n"
            . "📅 <i>Handover Time: " . now()->format('M d, Y H:i T') . "</i>\n"
            . "──────────────────────────────\n"
            . "📋 <b>Active Patient Load:</b>\n"
            . "• Total Admitted Inpatients: <b>{$activeAdmissions}</b>\n"
            . "• ICU Inpatients: <b>{$icuOccupied}</b> (Available ICU: <b>{$icuAvailable}</b>)\n"
            . "• Emergency Room Active: <b>{$activeEmergency}</b>\n\n"
            . "⚠️ <b>High Priority Follow-ups:</b>\n"
            . "• Critical Lab Alerts Pending Doctor Ack: <b>{$unacknowledgedCriticalLabs}</b>\n"
            . "• Shift Notes: Monitor high-dependency cases and incoming transfers.\n"
            . "──────────────────────────────\n"
            . "<i>Outgoing & Incoming Clinical Care Teams</i>";

        return [
            'shift' => $shift,
            'shift_title' => $shiftTitle,
            'html' => $html,
        ];
    }

    /**
     * Bed availability report for `/beds` bot command.
     */
    public function generateBedOccupancyReport(?string $branchId = null): string
    {
        $beds = Bed::where('is_active', true)->get();
        $total = $beds->count();
        $available = $beds->where('status', 'available')->count();
        $occupied = $beds->where('status', 'occupied')->count();
        $cleaning = $beds->where('status', 'maintenance')->count();

        $icuTotal = $beds->where('bed_type', 'icu')->count();
        $icuAvailable = $beds->where('bed_type', 'icu')->where('status', 'available')->count();

        $genTotal = $beds->where('bed_type', 'general')->count();
        $genAvailable = $beds->where('bed_type', 'general')->where('status', 'available')->count();

        return "🛏️ <b>HOSPITAL BED OCCUPANCY STATUS</b>\n"
            . "──────────────────────────────\n"
            . "• <b>Total Beds:</b> {$total}\n"
            . "• <b>Available:</b> {$available}\n"
            . "• <b>Occupied:</b> {$occupied}\n"
            . "• <b>Maintenance/Cleaning:</b> {$cleaning}\n\n"
            . "🏥 <b>Breakdown by Unit:</b>\n"
            . "• <b>ICU Units:</b> {$icuAvailable} available / {$icuTotal} total\n"
            . "• <b>General Units:</b> {$genAvailable} available / {$genTotal} total\n"
            . "──────────────────────────────\n"
            . "<i>Updated: " . now()->format('H:i:s') . "</i>";
    }

    /**
     * Revenue report for `/revenue` bot command.
     */
    public function generateRevenueReport(string $period = 'today', ?string $branchId = null): string
    {
        $isToday = strtolower($period) === 'today';
        $query = Invoice::query();
        
        if ($isToday) {
            $query->whereDate('created_at', Carbon::today());
            $label = 'Today (' . Carbon::today()->toFormattedDateString() . ')';
        } else {
            $query->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year);
            $label = 'This Month (' . Carbon::now()->format('F Y') . ')';
        }

        $totalCents = (int) (clone $query)->sum('total_cents');
        $paidCents = (int) (clone $query)->sum('paid_cents');
        $balanceCents = (int) (clone $query)->sum('balance_cents');
        $invoiceCount = (clone $query)->count();

        $total = number_format($totalCents / 100, 2);
        $paid = number_format($paidCents / 100, 2);
        $balance = number_format($balanceCents / 100, 2);

        return "💵 <b>BILLING & REVENUE SUMMARY</b>\n"
            . "📅 <b>Period:</b> {$label}\n"
            . "──────────────────────────────\n"
            . "• <b>Invoices Generated:</b> {$invoiceCount}\n"
            . "• <b>Total Billed:</b> \${$total}\n"
            . "• <b>Collections Received:</b> \${$paid}\n"
            . "• <b>Outstanding Balance:</b> \${$balance}\n"
            . "──────────────────────────────\n"
            . "<i>Confidential - Finance & Executive Role Only</i>";
    }

    /**
     * Pharmacy stock report for `/stock` bot command.
     */
    public function generatePharmacyStockReport(?string $branchId = null): string
    {
        if (!Schema::hasTable('drugs')) {
            return "💊 <b>Pharmacy Inventory:</b> No drug records configured.";
        }

        $drugs = Drug::with('activeBatches')->where('is_active', true)->get();
        $lowStock = $drugs->filter(fn($d) => $d->is_low_stock);

        $html = "💊 <b>PHARMACY INVENTORY ALERT</b>\n"
            . "──────────────────────────────\n";

        if ($lowStock->isEmpty()) {
            $html .= "✅ <b>All items above reorder thresholds.</b>\n";
            $html .= "• Monitored Catalog Items: " . $drugs->count() . "\n";
        } else {
            $html .= "⚠️ <b>Low Stock Items Needing Reorder (" . $lowStock->count() . "):</b>\n\n";
            foreach ($lowStock->take(10) as $item) {
                $html .= "• <b>{$item->generic_name}</b> ({$item->brand_name})\n"
                    . "   Stock: <code>{$item->total_stock_on_hand}</code> | Threshold: <code>{$item->reorder_threshold}</code>\n";
            }
            if ($lowStock->count() > 10) {
                $html .= "\n<i>...and " . ($lowStock->count() - 10) . " more items below threshold.</i>\n";
            }
        }
        $html .= "──────────────────────────────\n";
        $html .= "<i>Updated: " . now()->format('H:i:s') . "</i>";

        return $html;
    }

    /**
     * Patient census report for `/patients` bot command.
     */
    public function generatePatientCensusReport(?string $branchId = null): string
    {
        $today = Carbon::today();
        $admitted = Admission::where('status', 'admitted')->count();
        $admissionsToday = Admission::whereDate('admitted_at', $today)->count();
        $dischargesToday = Admission::where('status', 'discharged')->whereDate('discharged_at', $today)->count();

        $activeEmergency = Schema::hasTable('emergency_cases') 
            ? EmergencyCase::whereNotIn('status', ['discharged', 'transferred'])->count() 
            : 0;

        return "👥 <b>CURRENT PATIENT CENSUS</b>\n"
            . "──────────────────────────────\n"
            . "• <b>Current Inpatients (IPD):</b> {$admitted}\n"
            . "• <b>Admitted Today:</b> {$admissionsToday}\n"
            . "• <b>Discharged Today:</b> {$dischargesToday}\n"
            . "• <b>Active Emergency Cases:</b> {$activeEmergency}\n"
            . "──────────────────────────────\n"
            . "<i>Hospital Patient Census Tracker</i>";
    }
}
