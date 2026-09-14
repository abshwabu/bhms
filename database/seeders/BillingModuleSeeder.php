<?php

namespace Database\Seeders;

use App\Domain\Billing\Models\Discount;
use App\Domain\Billing\Models\InsuranceClaim;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceItem;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\PriceList;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Models\PatientInsurance;
use App\Domain\Shared\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BillingModuleSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::first();
        if (!$branch) {
            return;
        }

        $patient = Patient::first();
        $doctor = User::where('name', 'like', '%Dr.%')->orWhere('email', 'like', '%doc%')->first() ?? User::first();
        $cashier = User::first();

        // 1. Seed Master Price List Catalog (Procedures, Consultations, Packages, Bed Rates)
        $catalogItems = [
            [
                'code' => 'PRC-OPD-GEN',
                'name' => 'General Physician Outpatient Consultation',
                'category' => 'consultation',
                'department' => 'general',
                'unit_price_cents' => 3500, // $35.00
                'is_package' => false,
                'description' => 'Comprehensive primary care consultation including vital assessment.',
            ],
            [
                'code' => 'PRC-OPD-SPEC',
                'name' => 'Specialist Clinical Consultation',
                'category' => 'consultation',
                'department' => 'internal_medicine',
                'unit_price_cents' => 6000, // $60.00
                'is_package' => false,
                'description' => 'Specialist physician consult (Cardiology, Endocrinology, Pulmonology).',
            ],
            [
                'code' => 'PRC-ECG-12L',
                'name' => '12-Lead Electrocardiogram (ECG)',
                'category' => 'procedure',
                'department' => 'cardiology',
                'unit_price_cents' => 4500, // $45.00
                'is_package' => false,
                'description' => '12-lead resting ECG with cardiologist interpretation.',
            ],
            [
                'code' => 'PRC-BED-WARD',
                'name' => 'General Ward Inpatient Bed (Daily Rate)',
                'category' => 'bed',
                'department' => 'general_ward',
                'unit_price_cents' => 12000, // $120.00 / day
                'is_package' => false,
                'description' => 'Inpatient semi-private accommodation with 24/7 nursing care.',
            ],
            [
                'code' => 'PRC-BED-ICU',
                'name' => 'Intensive Care Unit (ICU) Bed (Daily Rate)',
                'category' => 'bed',
                'department' => 'icu',
                'unit_price_cents' => 45000, // $450.00 / day
                'is_package' => false,
                'description' => 'Critical care monitored bed with continuous hemodynamics.',
            ],
            [
                'code' => 'PKG-HEALTH-BASIC',
                'name' => 'Executive Health Wellness Checkup Package',
                'category' => 'package',
                'department' => 'preventive_health',
                'unit_price_cents' => 15000, // $150.00
                'is_package' => true,
                'package_items' => [
                    ['code' => 'PRC-OPD-SPEC', 'name' => 'Specialist Consult', 'quantity' => 1, 'standard_price_cents' => 6000],
                    ['code' => 'PRC-ECG-12L', 'name' => '12-Lead ECG', 'quantity' => 1, 'standard_price_cents' => 4500],
                    ['code' => 'LAB-CBC', 'name' => 'CBC Panel', 'quantity' => 1, 'standard_price_cents' => 3000],
                    ['code' => 'LAB-LIPID', 'name' => 'Lipid Profile', 'quantity' => 1, 'standard_price_cents' => 4000],
                ],
                'description' => 'Comprehensive wellness health screening package with bundle discount.',
            ],
            [
                'code' => 'PKG-SURG-APP',
                'name' => 'Laparoscopic Appendectomy Surgical Package',
                'category' => 'package',
                'department' => 'surgery',
                'unit_price_cents' => 180000, // $1,800.00
                'is_package' => true,
                'package_items' => [
                    ['code' => 'PRC-OR-TIME', 'name' => 'Operating Theatre 2h', 'quantity' => 1, 'standard_price_cents' => 80000],
                    ['code' => 'PRC-SURG-FEE', 'name' => 'Surgeon Fee', 'quantity' => 1, 'standard_price_cents' => 70000],
                    ['code' => 'PRC-ANES-FEE', 'name' => 'Anesthesiologist Fee', 'quantity' => 1, 'standard_price_cents' => 40000],
                    ['code' => 'PRC-BED-WARD', 'name' => 'Post-op Ward 2 Days', 'quantity' => 2, 'standard_price_cents' => 24000],
                ],
                'description' => 'All-inclusive minimally invasive laparoscopic appendectomy package.',
            ],
        ];

        foreach ($catalogItems as $item) {
            PriceList::firstOrCreate(
                ['branch_id' => $branch->id, 'code' => $item['code']],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $branch->organization_id,
                    ...$item,
                    'is_active' => true,
                ]
            );
        }

        if (!$patient) {
            return;
        }

        // 2. Ensure Sample Patient has an Active Insurance Policy
        $insurance = PatientInsurance::firstOrCreate(
            ['patient_id' => $patient->id, 'policy_number' => 'POL-BCBS-998822'],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'provider_name' => 'Blue Cross Blue Shield Health',
                'policy_number' => 'POL-BCBS-998822',
                'group_number' => 'GRP-CORP-440',
                'coverage_type' => 'primary',
                'coverage_percentage' => 80.00,
                'copay_amount_cents' => 2000, // $20.00 copay
                'valid_from' => Carbon::today()->subMonths(6),
                'valid_until' => Carbon::today()->addMonths(6),
                'pre_auth_required' => false,
                'status' => 'active',
            ]
        );

        // 3. Seed Sample Invoice 1: Fully Paid OPD Consultation & ECG
        $inv1 = Invoice::firstOrCreate(
            ['invoice_number' => 'INV-202609-001001'],
            [
                'id' => (string) Str::uuid(),
                'invoice_number' => 'INV-202609-001001',
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'patient_id' => $patient->id,
                'doctor_id' => $doctor?->id,
                'department' => 'cardiology',
                'billing_type' => 'opd',
                'status' => 'paid',
                'subtotal_cents' => 10500, // $105.00
                'discount_cents' => 500,  // $5.00
                'tax_cents' => 0,
                'total_cents' => 10000,   // $100.00
                'paid_cents' => 10000,    // $100.00
                'balance_cents' => 0,
                'payment_terms' => 'due_on_receipt',
                'due_date' => Carbon::today(),
                'notes' => 'Routine cardiology outpatient visit and rhythm check.',
                'created_by' => $cashier->id,
            ]
        );

        if ($inv1->items()->count() === 0) {
            InvoiceItem::create([
                'id' => (string) Str::uuid(),
                'invoice_id' => $inv1->id,
                'item_type' => 'consultation',
                'item_code' => 'PRC-OPD-SPEC',
                'description' => 'Cardiology Specialist Consultation',
                'quantity' => 1,
                'unit_price_cents' => 6000,
                'subtotal_cents' => 6000,
                'discount_cents' => 500,
                'total_cents' => 5500,
                'doctor_id' => $doctor?->id,
                'department' => 'cardiology',
            ]);

            InvoiceItem::create([
                'id' => (string) Str::uuid(),
                'invoice_id' => $inv1->id,
                'item_type' => 'procedure',
                'item_code' => 'PRC-ECG-12L',
                'description' => '12-Lead Resting ECG',
                'quantity' => 1,
                'unit_price_cents' => 4500,
                'subtotal_cents' => 4500,
                'discount_cents' => 0,
                'total_cents' => 4500,
                'doctor_id' => $doctor?->id,
                'department' => 'cardiology',
            ]);

            Payment::create([
                'id' => (string) Str::uuid(),
                'receipt_number' => 'REC-202609-001001',
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'invoice_id' => $inv1->id,
                'patient_id' => $patient->id,
                'payment_mode' => 'card',
                'amount_cents' => 10000,
                'transaction_reference' => 'TXN-VISA-99120',
                'notes' => 'Visa chip payment collected at front desk counter',
                'cashier_id' => $cashier->id,
                'status' => 'completed',
                'received_at' => Carbon::today()->subDays(1),
            ]);
        }

        // 4. Seed Sample Invoice 2: Partially Paid Inpatient Admission
        $inv2 = Invoice::firstOrCreate(
            ['invoice_number' => 'INV-202609-001002'],
            [
                'id' => (string) Str::uuid(),
                'invoice_number' => 'INV-202609-001002',
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'patient_id' => $patient->id,
                'doctor_id' => $doctor?->id,
                'department' => 'general_ward',
                'billing_type' => 'ipd',
                'status' => 'partially_paid',
                'subtotal_cents' => 36000, // $360.00 (3 days ward)
                'discount_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => 36000,
                'paid_cents' => 15000,    // $150.00 initial deposit paid
                'balance_cents' => 21000, // $210.00 outstanding balance
                'payment_terms' => 'due_on_receipt',
                'due_date' => Carbon::today()->addDays(3),
                'notes' => 'Inpatient admission interim bill.',
                'created_by' => $cashier->id,
            ]
        );

        if ($inv2->items()->count() === 0) {
            InvoiceItem::create([
                'id' => (string) Str::uuid(),
                'invoice_id' => $inv2->id,
                'item_type' => 'bed_charge',
                'item_code' => 'PRC-BED-WARD',
                'description' => 'General Ward Inpatient Bed (3 Days)',
                'quantity' => 3,
                'unit_price_cents' => 12000,
                'subtotal_cents' => 36000,
                'discount_cents' => 0,
                'total_cents' => 36000,
                'doctor_id' => $doctor?->id,
                'department' => 'general_ward',
            ]);

            Payment::create([
                'id' => (string) Str::uuid(),
                'receipt_number' => 'REC-202609-001002',
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'invoice_id' => $inv2->id,
                'patient_id' => $patient->id,
                'payment_mode' => 'mobile_money',
                'amount_cents' => 15000,
                'transaction_reference' => 'MM-MPESA-884120',
                'notes' => 'Admission initial deposit via mobile money',
                'cashier_id' => $cashier->id,
                'status' => 'completed',
                'received_at' => Carbon::today()->subDays(2),
            ]);

            // Add submitted insurance claim for the remaining balance
            InsuranceClaim::create([
                'id' => (string) Str::uuid(),
                'claim_number' => 'CLM-202609-00201',
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'invoice_id' => $inv2->id,
                'patient_insurance_id' => $insurance->id,
                'patient_id' => $patient->id,
                'provider_name' => $insurance->provider_name,
                'policy_number' => $insurance->policy_number,
                'pre_auth_number' => 'AUTH-IPD-77881',
                'claimed_amount_cents' => 21000,
                'approved_amount_cents' => 0,
                'copay_amount_cents' => 2000,
                'deductible_amount_cents' => 0,
                'status' => 'under_review',
                'submission_date' => Carbon::today()->subDays(1),
                'adjudication_notes' => 'Inpatient daily bed charges submitted to insurer.',
                'submitted_by' => $cashier->id,
            ]);
        }
    }
}
