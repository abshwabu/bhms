<?php

namespace Database\Seeders;

use App\Domain\Clinical\Models\Prescription;
use App\Domain\Pharmacy\Models\DispensingRecord;
use App\Domain\Pharmacy\Models\DispensingRecordItem;
use App\Domain\Pharmacy\Models\Drug;
use App\Domain\Pharmacy\Models\DrugBatch;
use App\Domain\Pharmacy\Models\DrugStockMovement;
use App\Domain\Shared\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PharmacyModuleSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::first();
        if (!$branch) {
            return;
        }

        $adminUser = User::first();
        $userId = $adminUser?->id;

        // 1. Seed Comprehensive Catalog of Essential Drugs
        $drugsData = [
            [
                'sku' => 'DRG-AMX-500',
                'brand_name' => 'Amoxil',
                'generic_name' => 'Amoxicillin',
                'form' => 'capsule',
                'strength' => '500 mg',
                'unit_of_measure' => 'capsule',
                'reorder_threshold' => 50,
                'target_stock_level' => 300,
                'unit_cost_cents' => 15,
                'unit_price_cents' => 45,
                'is_prescription_required' => true,
                'notes' => 'Broad-spectrum penicillin antibiotic.',
            ],
            [
                'sku' => 'DRG-MET-500',
                'brand_name' => 'Glucophage',
                'generic_name' => 'Metformin HCl',
                'form' => 'tablet',
                'strength' => '500 mg',
                'unit_of_measure' => 'tablet',
                'reorder_threshold' => 100,
                'target_stock_level' => 600,
                'unit_cost_cents' => 10,
                'unit_price_cents' => 30,
                'is_prescription_required' => true,
                'notes' => 'First-line biguanide oral antihyperglycemic.',
            ],
            [
                'sku' => 'DRG-LIS-010',
                'brand_name' => 'Prinivil',
                'generic_name' => 'Lisinopril',
                'form' => 'tablet',
                'strength' => '10 mg',
                'unit_of_measure' => 'tablet',
                'reorder_threshold' => 50, // will be low stock (stock = 25)
                'target_stock_level' => 200,
                'unit_cost_cents' => 20,
                'unit_price_cents' => 60,
                'is_prescription_required' => true,
                'notes' => 'ACE inhibitor for hypertension and heart failure.',
            ],
            [
                'sku' => 'DRG-WAR-005',
                'brand_name' => 'Coumadin',
                'generic_name' => 'Warfarin Sodium',
                'form' => 'tablet',
                'strength' => '5 mg',
                'unit_of_measure' => 'tablet',
                'reorder_threshold' => 30,
                'target_stock_level' => 150,
                'unit_cost_cents' => 25,
                'unit_price_cents' => 75,
                'is_prescription_required' => true,
                'notes' => 'Vitamin K antagonist oral anticoagulant. High bleeding risk.',
            ],
            [
                'sku' => 'DRG-PCM-500',
                'brand_name' => 'Panadol',
                'generic_name' => 'Paracetamol',
                'form' => 'tablet',
                'strength' => '500 mg',
                'unit_of_measure' => 'tablet',
                'reorder_threshold' => 150,
                'target_stock_level' => 800,
                'unit_cost_cents' => 5,
                'unit_price_cents' => 15,
                'is_prescription_required' => false,
                'notes' => 'Analgesic and antipyretic.',
            ],
            [
                'sku' => 'DRG-OME-020',
                'brand_name' => 'Prilosec',
                'generic_name' => 'Omeprazole',
                'form' => 'capsule',
                'strength' => '20 mg',
                'unit_of_measure' => 'capsule',
                'reorder_threshold' => 40,
                'target_stock_level' => 250,
                'unit_cost_cents' => 18,
                'unit_price_cents' => 55,
                'is_prescription_required' => true,
                'notes' => 'Proton pump inhibitor (PPI) for GERD and peptic ulcers.',
            ],
            [
                'sku' => 'DRG-ATV-020',
                'brand_name' => 'Lipitor',
                'generic_name' => 'Atorvastatin Calcium',
                'form' => 'tablet',
                'strength' => '20 mg',
                'unit_of_measure' => 'tablet',
                'reorder_threshold' => 60,
                'target_stock_level' => 300,
                'unit_cost_cents' => 30,
                'unit_price_cents' => 90,
                'is_prescription_required' => true,
                'notes' => 'HMG-CoA reductase inhibitor for hypercholesterolemia.',
            ],
        ];

        $seededDrugs = [];
        foreach ($drugsData as $d) {
            $drug = Drug::firstOrCreate(
                ['sku' => $d['sku']],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $branch->organization_id,
                    'branch_id' => $branch->id,
                    ...$d,
                ]
            );
            $seededDrugs[$d['sku']] = $drug;
        }

        // 2. Seed Batches to demonstrate FEFO, Expiry Blocking, and Near-Expiry Alerts
        $today = Carbon::today();

        // Amoxicillin: Lot 1 (expires in 20 days -> FEFO first), Lot 2 (expires in 180 days), Lot 3 (expired 30 days ago)
        $amxDrug = $seededDrugs['DRG-AMX-500'];
        $this->createBatchWithMovement($amxDrug, 'LOT-AMX-FEFO-01', 80, $today->copy()->addDays(20), $branch, $userId);
        $this->createBatchWithMovement($amxDrug, 'LOT-AMX-LATER-02', 200, $today->copy()->addDays(180), $branch, $userId);
        $this->createBatchWithMovement($amxDrug, 'LOT-AMX-EXPIRED-OLD', 40, $today->copy()->subDays(30), $branch, $userId, 'expired');

        // Metformin: 2 active lots
        $metDrug = $seededDrugs['DRG-MET-500'];
        $this->createBatchWithMovement($metDrug, 'LOT-MET-2026-A', 150, $today->copy()->addDays(60), $branch, $userId);
        $this->createBatchWithMovement($metDrug, 'LOT-MET-2026-B', 400, $today->copy()->addDays(365), $branch, $userId);

        // Lisinopril: Low stock test (Total stock: 25 vs reorder threshold 50)
        $lisDrug = $seededDrugs['DRG-LIS-010'];
        $this->createBatchWithMovement($lisDrug, 'LOT-LIS-LOW-01', 25, $today->copy()->addDays(90), $branch, $userId);

        // Warfarin
        $warDrug = $seededDrugs['DRG-WAR-005'];
        $this->createBatchWithMovement($warDrug, 'LOT-WAR-2026-01', 75, $today->copy()->addDays(150), $branch, $userId);

        // Paracetamol
        $pcmDrug = $seededDrugs['DRG-PCM-500'];
        $this->createBatchWithMovement($pcmDrug, 'LOT-PCM-2026-01', 600, $today->copy()->addDays(300), $branch, $userId);

        // Omeprazole: Near-expiry lot (expires in 9 days -> triggers near-expiry alert)
        $omeDrug = $seededDrugs['DRG-OME-020'];
        $this->createBatchWithMovement($omeDrug, 'LOT-OME-EXPIRING-SOON', 20, $today->copy()->addDays(9), $branch, $userId);
        $this->createBatchWithMovement($omeDrug, 'LOT-OME-2027-MAIN', 180, $today->copy()->addDays(400), $branch, $userId);

        // 3. Connect with any existing prescription to demonstrate completed dispensation
        $prescription = Prescription::with('items')->where('status', 'dispensed')->first();
        if ($prescription && $prescription->items->isNotEmpty() && !DispensingRecord::where('prescription_id', $prescription->id)->exists()) {
            $firstItem = $prescription->items->first();
            $drug = $amxDrug;
            $batch = $amxDrug->activeBatches()->first();

            if ($batch) {
                $dispRecord = DispensingRecord::create([
                    'id' => (string) Str::uuid(),
                    'dispensation_number' => 'DSP-DEMO-0001',
                    'organization_id' => $branch->organization_id,
                    'branch_id' => $branch->id,
                    'prescription_id' => $prescription->id,
                    'patient_id' => $prescription->patient_id,
                    'pharmacist_id' => $userId,
                    'status' => 'completed',
                    'has_interaction_warnings' => false,
                    'interaction_alerts' => [],
                    'pharmacist_notes' => 'Patient verified. Instructions provided for meal-time administration.',
                    'counseling_notes' => 'Take with a full glass of water. Complete full course.',
                    'dispensed_at' => now(),
                ]);

                DispensingRecordItem::create([
                    'id' => (string) Str::uuid(),
                    'dispensing_record_id' => $dispRecord->id,
                    'prescription_item_id' => $firstItem->id,
                    'drug_id' => $drug->id,
                    'drug_batch_id' => $batch->id,
                    'quantity_dispensed' => min(10, $batch->quantity_on_hand),
                    'directions' => $firstItem->instructions ?? 'Take 1 capsule 3 times daily',
                    'unit_price_cents' => $drug->unit_price_cents,
                    'total_price_cents' => min(10, $batch->quantity_on_hand) * $drug->unit_price_cents,
                ]);
            }
        }
    }

    protected function createBatchWithMovement(
        Drug $drug,
        string $batchNumber,
        int $quantity,
        Carbon $expiryDate,
        Branch $branch,
        ?string $userId,
        string $status = 'active'
    ): DrugBatch {
        $batch = DrugBatch::firstOrCreate(
            ['drug_id' => $drug->id, 'batch_number' => $batchNumber],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'batch_number' => $batchNumber,
                'manufacturing_date' => Carbon::today()->subMonths(3),
                'expiry_date' => $expiryDate->toDateString(),
                'quantity_received' => $quantity,
                'quantity_on_hand' => $quantity,
                'unit_cost_cents' => $drug->unit_cost_cents,
                'supplier_name' => 'MediCorp Pharmaceuticals Ltd.',
                'status' => $status,
            ]
        );

        if (!DrugStockMovement::where('drug_batch_id', $batch->id)->exists()) {
            DrugStockMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'drug_id' => $drug->id,
                'drug_batch_id' => $batch->id,
                'movement_type' => 'intake',
                'quantity' => $quantity,
                'quantity_before' => 0,
                'quantity_after' => $quantity,
                'reference_type' => 'purchase_order',
                'reference_id' => null,
                'reason' => 'Seeded batch stock inventory',
                'performed_by' => $userId,
            ]);
        }

        return $batch;
    }
}
