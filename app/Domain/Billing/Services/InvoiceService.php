<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceItem;
use App\Domain\Billing\Models\PriceList;
use App\Domain\IPD\Models\Admission;
use App\Domain\OPD\Models\Appointment;
use App\Domain\Patient\Models\Patient;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    public function __construct(
        protected BillingCalculationService $calculationService
    ) {}

    /**
     * Create a new itemized invoice.
     */
    public function createInvoice(array $data, string $creatorId): Invoice
    {
        return DB::transaction(function () use ($data, $creatorId) {
            $patient = Patient::findOrFail($data['patient_id']);

            $invoiceNumber = 'INV-' . date('Ym') . '-' . strtoupper(Str::random(6));

            $invoice = Invoice::create([
                'id' => (string) Str::uuid(),
                'invoice_number' => $invoiceNumber,
                'organization_id' => $patient->organization_id,
                'branch_id' => $data['branch_id'] ?? $patient->branch_id,
                'patient_id' => $patient->id,
                'appointment_id' => $data['appointment_id'] ?? null,
                'admission_id' => $data['admission_id'] ?? null,
                'doctor_id' => $data['doctor_id'] ?? null,
                'department' => $data['department'] ?? 'general',
                'billing_type' => $data['billing_type'] ?? 'opd',
                'status' => 'unpaid',
                'subtotal_cents' => 0,
                'discount_cents' => 0,
                'tax_cents' => (int) ($data['tax_cents'] ?? 0),
                'total_cents' => 0,
                'paid_cents' => 0,
                'balance_cents' => 0,
                'payment_terms' => $data['payment_terms'] ?? 'due_on_receipt',
                'due_date' => $data['due_date'] ?? Carbon::today()->addDays(7)->toDateString(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $creatorId,
            ]);

            // Add itemized lines if provided
            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $this->addItem($invoice, $itemData, false);
                }
            }

            // Recalculate totals and balances
            return $invoice->recalculateTotals()->load(['items', 'patient', 'doctor', 'payments', 'discounts']);
        });
    }

    /**
     * Add a line item to an invoice and recalculate totals.
     */
    public function addItem(Invoice $invoice, array $itemData, bool $recalculate = true): InvoiceItem
    {
        $quantity = (int) ($itemData['quantity'] ?? 1);
        $unitPriceCents = (int) ($itemData['unit_price_cents'] ?? 0);
        $discountCents = (int) ($itemData['discount_cents'] ?? 0);

        // If price list code is provided and unit price is not set, lookup catalog price
        if (!empty($itemData['item_code']) && $unitPriceCents === 0) {
            $catalogItem = PriceList::where('branch_id', $invoice->branch_id)
                ->where('code', $itemData['item_code'])
                ->first();
            if ($catalogItem) {
                $unitPriceCents = $catalogItem->unit_price_cents;
            }
        }

        $calc = $this->calculationService->computeItemLine($quantity, $unitPriceCents, $discountCents);

        $item = InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $invoice->id,
            'item_type' => $itemData['item_type'] ?? 'procedure',
            'item_code' => $itemData['item_code'] ?? null,
            'description' => $itemData['description'],
            'quantity' => $calc['quantity'],
            'unit_price_cents' => $calc['unit_price_cents'],
            'subtotal_cents' => $calc['subtotal_cents'],
            'discount_cents' => $calc['discount_cents'],
            'total_cents' => $calc['total_cents'],
            'doctor_id' => $itemData['doctor_id'] ?? $invoice->doctor_id,
            'department' => $itemData['department'] ?? $invoice->department,
            'reference_type' => $itemData['reference_type'] ?? null,
            'reference_id' => $itemData['reference_id'] ?? null,
        ]);

        if ($recalculate) {
            $invoice->recalculateTotals();
        }

        return $item;
    }

    /**
     * Cancel / void an invoice.
     */
    public function cancelInvoice(Invoice $invoice, string $reason, string $userId): Invoice
    {
        return DB::transaction(function () use ($invoice, $reason, $userId) {
            if ($invoice->paid_cents > 0) {
                throw new DomainException("Cannot cancel an invoice with payments recorded. Please process refunds first.");
            }

            $invoice->status = 'cancelled';
            $invoice->notes = ($invoice->notes ? $invoice->notes . "\n" : '') . "Cancelled by user {$userId}: {$reason}";
            $invoice->balance_cents = 0;
            $invoice->save();

            return $invoice;
        });
    }
}
