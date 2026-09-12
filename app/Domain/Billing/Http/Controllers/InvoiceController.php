<?php

namespace App\Domain\Billing\Http\Controllers;

use App\Domain\Billing\Http\Resources\InvoiceItemResource;
use App\Domain\Billing\Http\Resources\InvoiceResource;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Services\InvoiceService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * List invoices with filtering, search, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()
            ->with(['patient', 'doctor', 'items'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('billing_type')) {
            $query->where('billing_type', $request->input('billing_type'));
        }

        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'ILIKE', "%{$term}%")
                  ->orWhereHas('patient', function ($pq) use ($term) {
                      $pq->where('first_name', 'ILIKE', "%{$term}%")
                         ->orWhere('last_name', 'ILIKE', "%{$term}%")
                         ->orWhere('mrn', 'ILIKE', "%{$term}%");
                  });
            });
        }

        $invoices = $query->paginate($request->input('per_page', 20));

        return ApiResponse::paginated(
            $invoices->through(fn($inv) => new InvoiceResource($inv)),
            'Invoices retrieved successfully.'
        );
    }

    /**
     * Display a single invoice with all lines, payments, and approvals.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['patient', 'doctor', 'items.doctor', 'payments.cashier', 'discounts.requester', 'refunds', 'claims']);

        return ApiResponse::success(
            new InvoiceResource($invoice),
            'Invoice details retrieved successfully.'
        );
    }

    /**
     * Create a new itemized invoice.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'appointment_id' => ['nullable', 'uuid', 'exists:appointments,id'],
            'admission_id' => ['nullable', 'uuid', 'exists:admissions,id'],
            'doctor_id' => ['nullable', 'uuid', 'exists:users,id'],
            'department' => ['nullable', 'string', 'max:100'],
            'billing_type' => ['nullable', 'string', 'in:opd,ipd,pharmacy,emergency,diagnostic'],
            'payment_terms' => ['nullable', 'string', 'max:50'],
            'due_date' => ['nullable', 'date'],
            'tax_cents' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.item_type' => ['required_with:items', 'string'],
            'items.*.item_code' => ['nullable', 'string'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price_cents' => ['nullable', 'integer', 'min:0'],
            'items.*.discount_cents' => ['nullable', 'integer', 'min:0'],
            'items.*.doctor_id' => ['nullable', 'uuid'],
            'items.*.department' => ['nullable', 'string'],
            'items.*.reference_type' => ['nullable', 'string'],
            'items.*.reference_id' => ['nullable', 'uuid'],
        ]);

        $creatorId = $request->user()?->id ?? $request->input('created_by');
        if (!$creatorId) {
            return ApiResponse::error('User ID is required to create invoice.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $invoice = $this->invoiceService->createInvoice($validated, $creatorId);

            return ApiResponse::success(
                new InvoiceResource($invoice),
                "Invoice #{$invoice->invoice_number} generated successfully.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'INVOICE_CREATION_ERROR', [], 422);
        }
    }

    /**
     * Add an itemized line charge to an existing invoice.
     */
    public function addItem(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'item_type' => ['required', 'string'],
            'item_code' => ['nullable', 'string'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price_cents' => ['nullable', 'integer', 'min:0'],
            'discount_cents' => ['nullable', 'integer', 'min:0'],
            'doctor_id' => ['nullable', 'uuid'],
            'department' => ['nullable', 'string'],
        ]);

        try {
            $item = $this->invoiceService->addItem($invoice, $validated);

            return ApiResponse::success(
                new InvoiceItemResource($item),
                'Line item added to invoice successfully.',
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'ADD_ITEM_ERROR', [], 422);
        }
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id ?? $request->input('cancelled_by');
        if (!$userId) {
            return ApiResponse::error('User ID is required to cancel invoice.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $invoice = $this->invoiceService->cancelInvoice($invoice, $validated['reason'], $userId);

            return ApiResponse::success(
                new InvoiceResource($invoice),
                "Invoice #{$invoice->invoice_number} has been cancelled."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'CANCEL_INVOICE_ERROR', [], 422);
        }
    }

    /**
     * Generate structured print-ready receipt / invoice statement payload.
     */
    public function receipt(Invoice $invoice): JsonResponse
    {
        $invoice->load(['patient', 'doctor', 'items', 'payments.cashier', 'discounts']);

        return ApiResponse::success([
            'invoice_number' => $invoice->invoice_number,
            'hospital' => [
                'name' => 'Metro General Hospital',
                'address' => '100 Medical Center Parkway, Metropolis',
                'phone' => '+1 (555) 019-2830',
                'email' => 'billing@metrohospital.org',
                'tax_number' => 'TAX-MID-5544',
            ],
            'patient' => [
                'full_name' => $invoice->patient->full_name,
                'mrn' => $invoice->patient->mrn,
                'gender' => $invoice->patient->gender,
                'phone' => $invoice->patient->phone,
            ],
            'attending_doctor' => $invoice->doctor?->name,
            'billing_type' => strtoupper($invoice->billing_type),
            'department' => ucfirst($invoice->department),
            'status' => strtoupper($invoice->status),
            'created_at' => $invoice->created_at->toFormattedDateString(),
            'due_date' => $invoice->due_date ? $invoice->due_date->toFormattedDateString() : null,
            'items' => $invoice->items->map(fn($item) => [
                'description' => $item->description,
                'item_type' => $item->item_type,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
                'discount' => $item->discount,
                'total' => $item->total,
            ]),
            'financials' => [
                'subtotal' => $invoice->subtotal,
                'discount' => $invoice->discount,
                'tax' => $invoice->tax,
                'total' => $invoice->total,
                'paid' => $invoice->paid,
                'balance' => $invoice->balance,
            ],
            'payments' => $invoice->payments->map(fn($p) => [
                'receipt_number' => $p->receipt_number,
                'mode' => ucwords(str_replace('_', ' ', $p->payment_mode)),
                'amount' => $p->amount,
                'date' => $p->received_at->toFormattedDateString(),
                'cashier' => $p->cashier?->name,
            ]),
        ], 'Invoice receipt statement generated.');
    }
}
