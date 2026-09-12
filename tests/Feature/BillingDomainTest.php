<?php

namespace Tests\Feature;

use App\Domain\Billing\Models\Discount;
use App\Domain\Billing\Models\InsuranceClaim;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceItem;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\PriceList;
use App\Domain\Billing\Models\Refund;
use App\Domain\Billing\Services\DiscountRefundApprovalService;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Models\PatientInsurance;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BillingDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected Branch $branch;
    protected User $cashier;
    protected User $supervisor;
    protected User $doctor;
    protected Patient $patient;
    protected PatientInsurance $insurance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'Metro Health System',
            'code' => 'MHS',
            'tax_number' => 'TAX-MHS-9988',
        ]);

        $this->branch = Branch::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'name' => 'Central Hospital Billing Desk',
            'code' => 'BILLING-MAIN',
            'is_active' => true,
        ]);

        $this->cashier = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Cashier Sarah Connor',
            'email' => 'sconnor.billing@mhs.org',
            'password' => bcrypt('password123'),
        ]);

        $this->supervisor = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Finance Director Arthur Dent',
            'email' => 'adent.finance@mhs.org',
            'password' => bcrypt('password123'),
        ]);

        $this->doctor = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. Stephen Strange, MD',
            'email' => 'sstrange@mhs.org',
            'password' => bcrypt('password123'),
        ]);

        $this->patient = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-BILL-001',
            'first_name' => 'Bruce',
            'last_name' => 'Wayne',
            'gender' => 'male',
            'date_of_birth' => '1980-02-19',
            'phone' => '+15550009999',
            'email' => 'bruce.wayne@example.com',
        ]);

        $this->insurance = PatientInsurance::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'provider_name' => 'Aetna Global Health',
            'policy_number' => 'POL-AETNA-887766',
            'coverage_percentage' => 80.00,
            'copay_amount_cents' => 1500,
            'valid_from' => Carbon::today()->subMonths(3),
            'valid_until' => Carbon::today()->addMonths(9),
            'status' => 'active',
        ]);
    }

    protected function actAsCashier(): self
    {
        Sanctum::actingAs($this->cashier);
        $this->withHeaders([
            'X-Branch-ID' => $this->branch->id,
            'Accept' => 'application/json',
        ]);
        return $this;
    }

    protected function actAsSupervisor(): self
    {
        Sanctum::actingAs($this->supervisor);
        $this->withHeaders([
            'X-Branch-ID' => $this->branch->id,
            'Accept' => 'application/json',
        ]);
        return $this;
    }

    /**
     * TEST 1: Exact Integer Cents Reconciliation (Zero Rounding Drift).
     */
    public function test_invoice_totals_reconcile_exactly_against_itemized_charges_with_no_rounding_drift(): void
    {
        $this->actAsCashier();

        // 3 items with specific quantities, prices, and line discounts
        $items = [
            [
                'item_type' => 'consultation',
                'description' => 'Cardiology Specialist Consultation',
                'quantity' => 1,
                'unit_price_cents' => 6000, // $60.00
                'discount_cents' => 500,   // $5.00
                'department' => 'cardiology',
                'doctor_id' => $this->doctor->id,
            ],
            [
                'item_type' => 'procedure',
                'description' => '12-Lead Electrocardiogram',
                'quantity' => 2,
                'unit_price_cents' => 4500, // 2 x $45.00 = $90.00
                'discount_cents' => 0,
                'department' => 'cardiology',
                'doctor_id' => $this->doctor->id,
            ],
            [
                'item_type' => 'drug',
                'description' => 'Amoxicillin 500mg 20 Capsules',
                'quantity' => 20,
                'unit_price_cents' => 45,   // 20 x $0.45 = $9.00
                'discount_cents' => 100,  // $1.00
                'department' => 'pharmacy',
            ],
        ];

        $response = $this->postJson('/api/v1/billing/invoices', [
            'patient_id' => $this->patient->id,
            'billing_type' => 'opd',
            'department' => 'cardiology',
            'doctor_id' => $this->doctor->id,
            'tax_cents' => 250, // $2.50 tax
            'items' => $items,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $invoiceId = $response->json('data.id');
        $invoice = Invoice::find($invoiceId);

        // Subtotal = 6000 + 9000 + 900 = 15900 cents ($159.00)
        $this->assertEquals(15900, $invoice->subtotal_cents);
        $this->assertEquals(159.00, $invoice->subtotal);

        // Discounts = 500 + 0 + 100 = 600 cents ($6.00)
        $this->assertEquals(600, $invoice->discount_cents);
        $this->assertEquals(6.00, $invoice->discount);

        // Tax = 250 cents ($2.50)
        $this->assertEquals(250, $invoice->tax_cents);

        // Total = 15900 - 600 + 250 = 15550 cents ($155.50)
        $this->assertEquals(15550, $invoice->total_cents);
        $this->assertEquals(155.50, $invoice->total);

        // Balance = 15550 cents
        $this->assertEquals(15550, $invoice->balance_cents);
        $this->assertEquals('unpaid', $invoice->status);
    }

    /**
     * TEST 2: Partial Payments and Outstanding Balances Tracked Accurately.
     */
    public function test_partial_payments_and_outstanding_balances_tracked_accurately(): void
    {
        $this->actAsCashier();

        // Create an invoice of $100.00 (10000 cents)
        $invoice = Invoice::create([
            'id' => (string) Str::uuid(),
            'invoice_number' => 'INV-TEST-PARTIAL',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'status' => 'unpaid',
            'subtotal_cents' => 10000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 10000,
            'paid_cents' => 0,
            'balance_cents' => 10000,
            'created_by' => $this->cashier->id,
        ]);

        InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $invoice->id,
            'item_type' => 'procedure',
            'description' => 'Comprehensive Consultation & Procedure',
            'quantity' => 1,
            'unit_price_cents' => 10000,
            'subtotal_cents' => 10000,
            'discount_cents' => 0,
            'total_cents' => 10000,
        ]);

        // 1. Record 1st Partial Payment: $35.00 (3500 cents) via Cash
        $pay1 = $this->postJson('/api/v1/billing/payments', [
            'invoice_id' => $invoice->id,
            'amount_cents' => 3500,
            'payment_mode' => 'cash',
            'notes' => 'First installment in cash',
        ]);

        $pay1->assertStatus(201)
            ->assertJsonPath('success', true);

        $invoice->refresh();
        $this->assertEquals(3500, $invoice->paid_cents);
        $this->assertEquals(6500, $invoice->balance_cents);
        $this->assertEquals('partially_paid', $invoice->status);

        // 2. Record 2nd Partial Payment: $65.00 (6500 cents) via Mobile Money
        $pay2 = $this->postJson('/api/v1/billing/payments', [
            'invoice_id' => $invoice->id,
            'amount_cents' => 6500,
            'payment_mode' => 'mobile_money',
            'transaction_reference' => 'MM-TXN-49102',
            'notes' => 'Settling balance via mobile money',
        ]);

        $pay2->assertStatus(201)
            ->assertJsonPath('success', true);

        $invoice->refresh();
        $this->assertEquals(10000, $invoice->paid_cents);
        $this->assertEquals(0, $invoice->balance_cents);
        $this->assertEquals('paid', $invoice->status);

        // 3. Attempting another payment on a fully paid invoice must fail
        $payOver = $this->postJson('/api/v1/billing/payments', [
            'invoice_id' => $invoice->id,
            'amount_cents' => 1000,
            'payment_mode' => 'cash',
        ]);

        $payOver->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * TEST 3: Discounts Above Configurable Threshold Require Second Approval.
     */
    public function test_discounts_above_configurable_threshold_require_second_approval(): void
    {
        $this->actAsCashier();

        // Invoice of $200.00 (20000 cents)
        $invoice = Invoice::create([
            'id' => (string) Str::uuid(),
            'invoice_number' => 'INV-TEST-DISCOUNT',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'status' => 'unpaid',
            'subtotal_cents' => 20000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 20000,
            'paid_cents' => 0,
            'balance_cents' => 20000,
            'created_by' => $this->cashier->id,
        ]);

        InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $invoice->id,
            'item_type' => 'procedure',
            'description' => 'Surgical Ward Package',
            'quantity' => 1,
            'unit_price_cents' => 20000,
            'subtotal_cents' => 20000,
            'discount_cents' => 0,
            'total_cents' => 20000,
        ]);

        $approvalService = app(DiscountRefundApprovalService::class);
        $approvalService->setThresholdCents(5000); // $50.00 threshold

        // Case A: Discount of $30.00 (3000 cents <= threshold) -> Auto Approved
        $autoDiscountRes = $this->postJson('/api/v1/billing/discounts', [
            'invoice_id' => $invoice->id,
            'discount_type' => 'fixed',
            'amount_cents' => 3000,
            'reason' => 'Minor promotional discount',
        ]);

        $autoDiscountRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.requires_approval', false);

        $invoice->refresh();
        // Total decreased by 3000 -> 17000 cents
        $this->assertEquals(3000, $invoice->discount_cents);
        $this->assertEquals(17000, $invoice->total_cents);
        $this->assertEquals(17000, $invoice->balance_cents);

        // Case B: Discount of $80.00 (8000 cents > threshold) -> Requires Second Approval
        $highDiscountRes = $this->postJson('/api/v1/billing/discounts', [
            'invoice_id' => $invoice->id,
            'discount_type' => 'fixed',
            'amount_cents' => 8000,
            'reason' => 'Financial hardship courtesy discount',
        ]);

        $highDiscountRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending_approval')
            ->assertJsonPath('data.requires_approval', true);

        $discountId = $highDiscountRes->json('data.id');

        // Verify that pending discount is NOT yet applied to invoice balance!
        $invoice->refresh();
        $this->assertEquals(3000, $invoice->discount_cents);
        $this->assertEquals(17000, $invoice->total_cents);

        // Case C: Supervisor Approves the High Discount
        $this->actAsSupervisor();
        $approveRes = $this->postJson("/api/v1/billing/discounts/{$discountId}/approve");

        $approveRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved');

        // Now invoice balance must be updated!
        $invoice->refresh();
        // Total discount: 3000 + 8000 = 11000 cents
        $this->assertEquals(11000, $invoice->discount_cents);
        $this->assertEquals(9000, $invoice->total_cents);
        $this->assertEquals(9000, $invoice->balance_cents);
    }

    /**
     * TEST 4: Refunds Above Configurable Threshold Require Second Approval.
     */
    public function test_refunds_above_threshold_require_second_approval(): void
    {
        $this->actAsCashier();

        // Invoice of $150.00 (15000 cents) fully paid
        $invoice = Invoice::create([
            'id' => (string) Str::uuid(),
            'invoice_number' => 'INV-TEST-REFUND',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'status' => 'paid',
            'subtotal_cents' => 15000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 15000,
            'paid_cents' => 15000,
            'balance_cents' => 0,
            'created_by' => $this->cashier->id,
        ]);

        InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $invoice->id,
            'item_type' => 'procedure',
            'description' => 'Minor Outpatient Procedure',
            'quantity' => 1,
            'unit_price_cents' => 15000,
            'subtotal_cents' => 15000,
            'discount_cents' => 0,
            'total_cents' => 15000,
        ]);

        Payment::create([
            'id' => (string) Str::uuid(),
            'receipt_number' => 'REC-TEST-REFUND-01',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'invoice_id' => $invoice->id,
            'patient_id' => $this->patient->id,
            'payment_mode' => 'card',
            'amount_cents' => 15000,
            'cashier_id' => $this->cashier->id,
            'status' => 'completed',
            'received_at' => now(),
        ]);

        $approvalService = app(DiscountRefundApprovalService::class);
        $approvalService->setThresholdCents(5000); // $50.00

        // Request refund of $100.00 (10000 cents > 5000 cents) -> Requires approval
        $refundRes = $this->postJson('/api/v1/billing/refunds', [
            'invoice_id' => $invoice->id,
            'amount_cents' => 10000,
            'refund_mode' => 'card',
            'reason' => 'Patient cancelled procedure before performance',
        ]);

        $refundRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending_approval')
            ->assertJsonPath('data.requires_approval', true);

        $refundId = $refundRes->json('data.id');

        // Verify invoice balance not modified yet
        $invoice->refresh();
        $this->assertEquals(15000, $invoice->paid_cents);

        // Supervisor approves refund
        $this->actAsSupervisor();
        $approveRes = $this->postJson("/api/v1/billing/refunds/{$refundId}/approve");

        $approveRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved');

        // Invoice net paid should now be $50.00 (5000 cents) and balance $100.00 (10000 cents)
        $invoice->refresh();
        $this->assertEquals(5000, $invoice->paid_cents);
        $this->assertEquals(10000, $invoice->balance_cents);
        $this->assertEquals('partially_paid', $invoice->status);
    }

    /**
     * TEST 5: Insurance Claim Submission, Adjudication, and Reconciliation.
     */
    public function test_insurance_claim_submission_adjudication_and_reconciliation(): void
    {
        $this->actAsCashier();

        $invoice = Invoice::create([
            'id' => (string) Str::uuid(),
            'invoice_number' => 'INV-TEST-CLAIM',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'status' => 'unpaid',
            'subtotal_cents' => 50000, // $500.00
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 50000,
            'paid_cents' => 0,
            'balance_cents' => 50000,
            'created_by' => $this->cashier->id,
        ]);

        InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $invoice->id,
            'item_type' => 'procedure',
            'description' => 'Inpatient Orthopedic Consultation and Imaging',
            'quantity' => 1,
            'unit_price_cents' => 50000,
            'subtotal_cents' => 50000,
            'discount_cents' => 0,
            'total_cents' => 50000,
        ]);

        // 1. Submit claim to insurer
        $claimRes = $this->postJson('/api/v1/billing/claims', [
            'invoice_id' => $invoice->id,
            'claimed_amount_cents' => 50000,
            'pre_auth_number' => 'AUTH-PRE-9090',
            'notes' => 'Inpatient orthopedic consultation and imaging',
        ]);

        $claimRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'submitted');

        $claimId = $claimRes->json('data.id');

        // 2. Adjudicate claim response from insurer (Insurer approves 40000 cents = $400.00)
        $adjudicateRes = $this->postJson("/api/v1/billing/claims/{$claimId}/adjudicate", [
            'status' => 'approved',
            'approved_amount_cents' => 40000,
            'adjudication_notes' => 'Approved 80% coverage per policy terms. Copay applies.',
        ]);

        $adjudicateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_amount_cents', 40000);

        // 3. Reconcile claim payout: credits payment of $400.00 to invoice
        $reconcileRes = $this->postJson("/api/v1/billing/claims/{$claimId}/reconcile");

        $reconcileRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'reconciled');

        $invoice->refresh();
        $this->assertEquals(40000, $invoice->paid_cents);
        $this->assertEquals(10000, $invoice->balance_cents); // $100.00 remaining patient responsibility
        $this->assertEquals('partially_paid', $invoice->status);
    }

    /**
     * TEST 6: Revenue Reports by Department and Doctor.
     */
    public function test_revenue_reports_by_department_and_doctor(): void
    {
        $this->actAsSupervisor();

        $inv = Invoice::create([
            'id' => (string) Str::uuid(),
            'invoice_number' => 'INV-TEST-REPORT-01',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'status' => 'paid',
            'subtotal_cents' => 30000,
            'discount_cents' => 2000,
            'tax_cents' => 0,
            'total_cents' => 28000,
            'paid_cents' => 28000,
            'balance_cents' => 0,
            'created_by' => $this->cashier->id,
        ]);

        InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $inv->id,
            'item_type' => 'procedure',
            'description' => 'Cardiology Echo Doppler',
            'quantity' => 1,
            'unit_price_cents' => 20000,
            'subtotal_cents' => 20000,
            'discount_cents' => 1000,
            'total_cents' => 19000,
            'department' => 'cardiology',
            'doctor_id' => $this->doctor->id,
        ]);

        InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $inv->id,
            'item_type' => 'procedure',
            'description' => 'Pediatrics Immunization',
            'quantity' => 1,
            'unit_price_cents' => 10000,
            'subtotal_cents' => 10000,
            'discount_cents' => 1000,
            'total_cents' => 9000,
            'department' => 'pediatrics',
            'doctor_id' => $this->doctor->id,
        ]);

        // 1. Department Revenue Report
        $deptRes = $this->getJson('/api/v1/billing/reports/departments');
        $deptRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $deptData = $deptRes->json('data');
        $cardio = collect($deptData)->firstWhere('department', 'cardiology');
        $this->assertNotNull($cardio);
        $this->assertEquals(19000, $cardio['net_total_cents']);

        // 2. Doctor Revenue Report
        $docRes = $this->getJson('/api/v1/billing/reports/doctors');
        $docRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $docData = $docRes->json('data');
        $doctorRow = collect($docData)->firstWhere('doctor_id', $this->doctor->id);
        $this->assertNotNull($doctorRow);
        $this->assertEquals(28000, $doctorRow['net_total_cents']);
    }
}
