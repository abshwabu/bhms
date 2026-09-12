<?php

namespace App\Domain\Billing\Http\Controllers;

use App\Domain\Billing\Http\Resources\PaymentResource;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Services\PaymentService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * List payment transactions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()
            ->with(['invoice', 'patient', 'cashier'])
            ->orderBy('received_at', 'desc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->input('invoice_id'));
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('payment_mode')) {
            $query->where('payment_mode', $request->input('payment_mode'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('receipt_number', 'ILIKE', "%{$term}%")
                  ->orWhere('transaction_reference', 'ILIKE', "%{$term}%")
                  ->orWhereHas('patient', function ($pq) use ($term) {
                      $pq->where('first_name', 'ILIKE', "%{$term}%")
                         ->orWhere('last_name', 'ILIKE', "%{$term}%")
                         ->orWhere('mrn', 'ILIKE', "%{$term}%");
                  });
            });
        }

        $payments = $query->paginate($request->input('per_page', 25));

        return ApiResponse::paginated(
            $payments->through(fn($p) => new PaymentResource($p)),
            'Payments retrieved successfully.'
        );
    }

    /**
     * Record a payment against an invoice.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'uuid', 'exists:invoices,id'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'string', 'in:cash,card,mobile_money,insurance,bank_transfer'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $cashierId = $request->user()?->id ?? $request->input('cashier_id');
        if (!$cashierId) {
            return ApiResponse::error('Cashier user is required.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $payment = $this->paymentService->recordPayment($invoice, $validated, $cashierId);

            return ApiResponse::success(
                new PaymentResource($payment),
                "Payment recorded successfully under Receipt #{$payment->receipt_number}.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'PAYMENT_RECORDING_ERROR', [], 422);
        }
    }

    /**
     * Show payment receipt details.
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['invoice.items', 'patient', 'cashier']);

        return ApiResponse::success(
            new PaymentResource($payment),
            'Payment receipt details retrieved.'
        );
    }

    /**
     * Reverse a payment transaction.
     */
    public function reverse(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id ?? $request->input('reversed_by');
        if (!$userId) {
            return ApiResponse::error('User ID is required to reverse payment.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $payment = $this->paymentService->reversePayment($payment, $validated['reason'], $userId);

            return ApiResponse::success(
                new PaymentResource($payment),
                "Payment #{$payment->receipt_number} has been reversed."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'PAYMENT_REVERSAL_ERROR', [], 422);
        }
    }
}
