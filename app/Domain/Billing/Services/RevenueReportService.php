<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Discount;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceItem;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Refund;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueReportService
{
    /**
     * Itemized revenue report grouped by medical department.
     */
    public function getDepartmentRevenue(?string $branchId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', '!=', 'cancelled');

        if ($branchId) {
            $query->where('invoices.branch_id', $branchId);
        }

        if ($startDate) {
            $query->where('invoices.created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('invoices.created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $results = $query->select(
            'invoice_items.department',
            DB::raw('COUNT(invoice_items.id) as total_items_count'),
            DB::raw('SUM(invoice_items.subtotal_cents) as subtotal_cents'),
            DB::raw('SUM(invoice_items.discount_cents) as discount_cents'),
            DB::raw('SUM(invoice_items.total_cents) as net_total_cents')
        )
        ->groupBy('invoice_items.department')
        ->orderByDesc('net_total_cents')
        ->get();

        $rows = [];
        foreach ($results as $res) {
            $net = (int) $res->net_total_cents;
            $rows[] = [
                'department' => $res->department ?: 'general',
                'department_name' => ucfirst($res->department ?: 'general'),
                'items_count' => (int) $res->total_items_count,
                'subtotal_cents' => (int) $res->subtotal_cents,
                'subtotal' => ((int) $res->subtotal_cents) / 100,
                'discount_cents' => (int) $res->discount_cents,
                'discount' => ((int) $res->discount_cents) / 100,
                'net_total_cents' => $net,
                'net_total' => $net / 100,
            ];
        }

        return $rows;
    }

    /**
     * Itemized revenue report grouped by attending / ordering doctor.
     */
    public function getDoctorRevenue(?string $branchId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('users', 'invoice_items.doctor_id', '=', 'users.id')
            ->where('invoices.status', '!=', 'cancelled');

        if ($branchId) {
            $query->where('invoices.branch_id', $branchId);
        }

        if ($startDate) {
            $query->where('invoices.created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('invoices.created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $results = $query->select(
            'invoice_items.doctor_id',
            'users.name as doctor_name',
            'users.email as doctor_email',
            DB::raw('COUNT(invoice_items.id) as services_count'),
            DB::raw('SUM(invoice_items.subtotal_cents) as subtotal_cents'),
            DB::raw('SUM(invoice_items.discount_cents) as discount_cents'),
            DB::raw('SUM(invoice_items.total_cents) as net_total_cents')
        )
        ->groupBy('invoice_items.doctor_id', 'users.name', 'users.email')
        ->orderByDesc('net_total_cents')
        ->get();

        $rows = [];
        foreach ($results as $res) {
            $net = (int) $res->net_total_cents;
            $rows[] = [
                'doctor_id' => $res->doctor_id,
                'doctor_name' => $res->doctor_name ?: 'Hospital General Clinic',
                'doctor_email' => $res->doctor_email,
                'services_count' => (int) $res->services_count,
                'subtotal_cents' => (int) $res->subtotal_cents,
                'subtotal' => ((int) $res->subtotal_cents) / 100,
                'discount_cents' => (int) $res->discount_cents,
                'discount' => ((int) $res->discount_cents) / 100,
                'net_total_cents' => $net,
                'net_total' => $net / 100,
            ];
        }

        return $rows;
    }

    /**
     * Cashier collection breakdown by payment modes (cash, card, mobile_money, insurance, bank_transfer).
     */
    public function getPaymentModeCollections(?string $branchId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = Payment::query()->where('status', 'completed');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate) {
            $query->where('received_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('received_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $results = $query->select(
            'payment_mode',
            DB::raw('COUNT(id) as transaction_count'),
            DB::raw('SUM(amount_cents) as total_collected_cents')
        )
        ->groupBy('payment_mode')
        ->orderByDesc('total_collected_cents')
        ->get();

        $rows = [];
        foreach ($results as $res) {
            $collected = (int) $res->total_collected_cents;
            $rows[] = [
                'payment_mode' => $res->payment_mode,
                'mode_name' => ucwords(str_replace('_', ' ', $res->payment_mode)),
                'transaction_count' => (int) $res->transaction_count,
                'total_collected_cents' => $collected,
                'total_collected' => $collected / 100,
            ];
        }

        return $rows;
    }

    /**
     * Executive billing summary KPIs.
     */
    public function getExecutiveSummary(?string $branchId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $invQuery = Invoice::query()->where('status', '!=', 'cancelled');
        $payQuery = Payment::query()->where('status', 'completed');
        $refQuery = Refund::query()->whereIn('status', ['approved', 'processed']);
        $disQuery = Discount::query()->where('status', 'approved');

        if ($branchId) {
            $invQuery->where('branch_id', $branchId);
            $payQuery->where('branch_id', $branchId);
            $refQuery->where('branch_id', $branchId);
            $disQuery->where('branch_id', $branchId);
        }

        if ($startDate) {
            $invQuery->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
            $payQuery->where('received_at', '>=', Carbon::parse($startDate)->startOfDay());
            $refQuery->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
            $disQuery->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $invQuery->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
            $payQuery->where('received_at', '<=', Carbon::parse($endDate)->endOfDay());
            $refQuery->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
            $disQuery->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $totalInvoicedCents = (int) $invQuery->sum('total_cents');
        $totalPaidCents = (int) $payQuery->sum('amount_cents');
        $totalRefundedCents = (int) $refQuery->sum('amount_cents');
        $netCollectedCents = max(0, $totalPaidCents - $totalRefundedCents);
        $totalOutstandingCents = (int) $invQuery->sum('balance_cents');
        $totalDiscountsCents = (int) $disQuery->sum('amount_cents');

        $collectionRate = $totalInvoicedCents > 0
            ? round(($netCollectedCents / $totalInvoicedCents) * 100, 1)
            : 0.0;

        return [
            'total_invoiced_cents' => $totalInvoicedCents,
            'total_invoiced' => $totalInvoicedCents / 100,
            'total_collected_cents' => $netCollectedCents,
            'total_collected' => $netCollectedCents / 100,
            'total_outstanding_cents' => $totalOutstandingCents,
            'total_outstanding' => $totalOutstandingCents / 100,
            'total_discounts_cents' => $totalDiscountsCents,
            'total_discounts' => $totalDiscountsCents / 100,
            'total_refunds_cents' => $totalRefundedCents,
            'total_refunds' => $totalRefundedCents / 100,
            'collection_rate_percentage' => $collectionRate,
        ];
    }
}
