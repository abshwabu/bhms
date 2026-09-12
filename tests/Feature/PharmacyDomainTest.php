<?php

namespace Tests\Feature;

use App\Domain\Clinical\Models\Prescription;
use App\Domain\Clinical\Models\PrescriptionItem;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Models\PatientAllergy;
use App\Domain\Pharmacy\Models\DispensingRecord;
use App\Domain\Pharmacy\Models\Drug;
use App\Domain\Pharmacy\Models\DrugBatch;
use App\Domain\Pharmacy\Models\DrugStockMovement;
use App\Domain\Pharmacy\Services\DispensingService;
use App\Domain\Pharmacy\Services\PharmacyAlertService;
use App\Domain\Pharmacy\Services\PharmacyInventoryService;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PharmacyDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected Branch $branch;
    protected User $pharmacist;
    protected User $doctor;
    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'Metro General Health System',
            'code' => 'MGH',
            'tax_number' => 'TAX-PHARM-1122',
        ]);

        $this->branch = Branch::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'name' => 'Main Hospital Pharmacy',
            'code' => 'PHARM-MAIN',
            'is_active' => true,
        ]);

        $this->pharmacist = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Pharm. Elena Rostova',
            'email' => 'elena.pharm@mgh.org',
            'password' => bcrypt('password123'),
        ]);

        $this->doctor = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. Robert House, MD',
            'email' => 'rhouse@mgh.org',
            'password' => bcrypt('password123'),
        ]);

        $this->patient = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-PHARM-001',
            'first_name' => 'Alexander',
            'last_name' => 'Hamilton',
            'gender' => 'male',
            'date_of_birth' => '1985-05-12',
            'phone' => '+15551234567',
            'email' => 'ahamilton@example.com',
        ]);
    }

    /**
     * Helper to authenticate requests.
     */
    protected function actAsPharmacist(): self
    {
        Sanctum::actingAs($this->pharmacist);
        $this->withHeaders([
            'X-Branch-ID' => $this->branch->id,
            'Accept' => 'application/json',
        ]);
        return $this;
    }

    /**
     * Helper to create a drug in inventory.
     */
    protected function createDrug(array $overrides = []): Drug
    {
        return Drug::create(array_merge([
            'id' => (string) Str::uuid(),
            'sku' => 'DRG-' . strtoupper(Str::random(6)),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'brand_name' => 'Amoxil',
            'generic_name' => 'Amoxicillin',
            'form' => 'capsule',
            'strength' => '500 mg',
            'unit_of_measure' => 'capsule',
            'reorder_threshold' => 40,
            'target_stock_level' => 200,
            'unit_cost_cents' => 15,
            'unit_price_cents' => 45,
            'is_prescription_required' => true,
            'is_controlled_substance' => false,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Helper to create a batch for a drug.
     */
    protected function createBatch(Drug $drug, int $quantity, Carbon $expiryDate, array $overrides = []): DrugBatch
    {
        return DrugBatch::create(array_merge([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'drug_id' => $drug->id,
            'batch_number' => 'LOT-' . strtoupper(Str::random(8)),
            'manufacturing_date' => Carbon::today()->subMonths(2),
            'expiry_date' => $expiryDate->toDateString(),
            'quantity_received' => $quantity,
            'quantity_on_hand' => $quantity,
            'unit_cost_cents' => $drug->unit_cost_cents,
            'supplier_name' => 'PharmaSupply Co.',
            'status' => 'active',
        ], $overrides));
    }

    /**
     * Helper to create a finalized clinical prescription.
     */
    protected function createPrescription(string $medicationName, string $genericName, int $quantity): Prescription
    {
        $prescription = Prescription::create([
            'id' => (string) Str::uuid(),
            'prescription_number' => 'RX-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'finalized',
            'has_safety_warnings' => false,
            'prescribed_at' => now(),
            'finalized_at' => now(),
        ]);

        PrescriptionItem::create([
            'id' => (string) Str::uuid(),
            'prescription_id' => $prescription->id,
            'medication_name' => $medicationName,
            'generic_name' => $genericName,
            'form' => 'capsule',
            'dosage' => '500 mg',
            'frequency' => 'TID',
            'duration_days' => 7,
            'quantity' => $quantity,
            'instructions' => 'Take 1 capsule 3 times daily with food',
            'status' => 'pending',
        ]);

        return $prescription->fresh(['items', 'patient', 'doctor']);
    }

    /**
     * TEST 1: First-Expiry-First-Out (FEFO) Stock Decrementing.
     * When dispensing across multiple batches, earlier expiring batch is decremented first.
     */
    public function test_dispensing_a_prescription_automatically_decrements_stock_using_fefo(): void
    {
        $this->actAsPharmacist();

        $drug = $this->createDrug([
            'brand_name' => 'Amoxil',
            'generic_name' => 'Amoxicillin',
        ]);

        // Batch A: expires in 20 days, 30 units (earlier expiry)
        $batchA = $this->createBatch($drug, 30, Carbon::today()->addDays(20));

        // Batch B: expires in 120 days, 100 units (later expiry)
        $batchB = $this->createBatch($drug, 100, Carbon::today()->addDays(120));

        // Prescription requests 50 units
        $prescription = $this->createPrescription('Amoxil', 'Amoxicillin', 50);

        $response = $this->postJson('/api/v1/pharmacy/dispensing', [
            'prescription_id' => $prescription->id,
            'pharmacist_notes' => 'Patient advised to complete entire course.',
            'counseling_notes' => 'Take with full glass of water.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        // Verify FEFO behavior:
        // Batch A should be completely depleted (30 deducted, remaining 0)
        $batchA->refresh();
        $this->assertEquals(0, $batchA->quantity_on_hand);
        $this->assertEquals('depleted', $batchA->status);

        // Batch B should have remaining 20 units deducted (100 - 20 = 80)
        $batchB->refresh();
        $this->assertEquals(80, $batchB->quantity_on_hand);
        $this->assertEquals('active', $batchB->status);

        // Drug total stock on hand should now be 80
        $drug->refresh();
        $this->assertEquals(80, $drug->total_stock_on_hand);

        // Verify stock movement audit records
        $this->assertDatabaseHas('drug_stock_movements', [
            'drug_batch_id' => $batchA->id,
            'movement_type' => 'dispense',
            'quantity' => -30,
            'quantity_before' => 30,
            'quantity_after' => 0,
            'performed_by' => $this->pharmacist->id,
        ]);

        $this->assertDatabaseHas('drug_stock_movements', [
            'drug_batch_id' => $batchB->id,
            'movement_type' => 'dispense',
            'quantity' => -20,
            'quantity_before' => 100,
            'quantity_after' => 80,
            'performed_by' => $this->pharmacist->id,
        ]);

        // Verify Prescription status is updated to 'dispensed'
        $prescription->refresh();
        $this->assertEquals('dispensed', $prescription->status);
    }

    /**
     * TEST 2: Expired Batches Are Blocked from Dispensing.
     */
    public function test_expired_batches_are_strictly_blocked_from_dispensing(): void
    {
        $this->actAsPharmacist();

        $drug = $this->createDrug([
            'brand_name' => 'Augmentin',
            'generic_name' => 'Amoxicillin-Clavulanate',
        ]);

        // Batch is expired (yesterday)
        $expiredBatch = $this->createBatch($drug, 50, Carbon::today()->subDays(1), [
            'status' => 'expired',
        ]);

        $prescription = $this->createPrescription('Augmentin', 'Amoxicillin-Clavulanate', 20);

        // Attempting to dispense should fail because no active non-expired batch is available
        $response = $this->postJson('/api/v1/pharmacy/dispensing', [
            'prescription_id' => $prescription->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        // Verify batch was NOT touched
        $expiredBatch->refresh();
        $this->assertEquals(50, $expiredBatch->quantity_on_hand);

        // Direct model call to decrementStock on expired batch must throw DomainException
        try {
            $expiredBatch->decrementStock(5, 'dispense');
            $this->fail("Expected DomainException when decrementing expired batch.");
        } catch (DomainException $e) {
            $this->assertStringContainsString('Cannot dispense or deduct from expired batch', $e->getMessage());
        }
    }

    /**
     * TEST 3: Configurable Low-Stock Alerts Fire at or below threshold.
     */
    public function test_low_stock_alerts_fire_at_configurable_threshold(): void
    {
        $this->actAsPharmacist();

        // Drug with threshold = 50
        $drug = $this->createDrug([
            'brand_name' => 'Lisinopril',
            'generic_name' => 'Lisinopril',
            'reorder_threshold' => 50,
            'target_stock_level' => 200,
        ]);

        // Create batch with 60 units (Above threshold -> no alert yet)
        $batch = $this->createBatch($drug, 60, Carbon::today()->addDays(90));

        $alertService = app(PharmacyAlertService::class);
        $alertsBefore = $alertService->getLowStockAlerts($this->branch->id);
        $lisinoprilAlertBefore = collect($alertsBefore)->firstWhere('drug_id', $drug->id);
        $this->assertNull($lisinoprilAlertBefore, 'Drug should not have a low stock alert when stock (60) > threshold (50)');

        // Deduct 25 units -> remaining is 35 (<= 50 -> low stock alert should fire!)
        $batch->decrementStock(25, 'dispense', 'Test deduction', null, null, $this->pharmacist->id);

        $drug->refresh();
        $this->assertEquals(35, $drug->total_stock_on_hand);
        $this->assertTrue($drug->is_low_stock);

        // Fetch via API endpoint
        $response = $this->getJson('/api/v1/pharmacy/drugs/alerts/low-stock');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $alerts = $response->json('data');
        $matchingAlert = collect($alerts)->firstWhere('drug_id', $drug->id);

        $this->assertNotNull($matchingAlert);
        $this->assertEquals(35, $matchingAlert['current_stock']);
        $this->assertEquals(50, $matchingAlert['reorder_threshold']);
        $this->assertEquals(15, $matchingAlert['deficit']);
        $this->assertEquals('warning', $matchingAlert['severity']);
    }

    /**
     * TEST 4: Near-Expiry Batch Alerts Detect Lots Approaching Expiry.
     */
    public function test_near_expiry_batch_alerts_detect_approaching_lots(): void
    {
        $this->actAsPharmacist();

        $drug = $this->createDrug(['brand_name' => 'Omeprazole']);

        // Batch expiring in 10 days
        $batchNear = $this->createBatch($drug, 40, Carbon::today()->addDays(10));

        // Batch expiring in 250 days
        $batchFar = $this->createBatch($drug, 150, Carbon::today()->addDays(250));

        $response = $this->getJson('/api/v1/pharmacy/drugs/alerts/expiring?days=60');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $alerts = $response->json('data');

        $nearAlert = collect($alerts)->firstWhere('batch_id', $batchNear->id);
        $farAlert = collect($alerts)->firstWhere('batch_id', $batchFar->id);

        $this->assertNotNull($nearAlert);
        $this->assertEquals('critical_near_expiry', $nearAlert['status_level']);
        $this->assertNull($farAlert, 'Far expiry batch should not be in the 60-day alert list');
    }

    /**
     * TEST 5: Secondary Drug Interaction and Allergy Warning Detection at Dispensing Time.
     */
    public function test_secondary_drug_interaction_checks_at_dispensing_time(): void
    {
        $this->actAsPharmacist();

        // 1. Patient has allergy to Penicillin
        PatientAllergy::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'allergen' => 'Penicillin',
            'reaction' => 'Anaphylaxis and hives',
            'severity' => 'severe',
            'status' => 'active',
        ]);

        // 2. Doctor prescribes Amoxicillin
        $drug = $this->createDrug([
            'brand_name' => 'Amoxil',
            'generic_name' => 'Amoxicillin',
        ]);
        $this->createBatch($drug, 100, Carbon::today()->addDays(100));

        $prescription = $this->createPrescription('Amoxil', 'Amoxicillin', 20);

        // 3. Request preview at dispensing station
        $response = $this->getJson("/api/v1/pharmacy/dispensing/preview/{$prescription->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.has_interaction_warnings', true);

        $alerts = $response->json('data.interaction_alerts');
        $this->assertNotEmpty($alerts);
        $this->assertEquals('drug_allergy', $alerts[0]['type']);
        $this->assertEquals('high', $alerts[0]['severity']);
        $this->assertStringContainsString('Penicillin', $alerts[0]['title']);

        // 4. Proceed to dispense and verify warnings are permanently archived on DispensingRecord
        $dispenseResponse = $this->postJson('/api/v1/pharmacy/dispensing', [
            'prescription_id' => $prescription->id,
            'pharmacist_notes' => 'Consulted with Dr. House. Confirmed mild rash history only; alternative not available, antihistamine co-administered.',
        ]);

        $dispenseResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $dispRecordId = $dispenseResponse->json('data.id');

        $record = DispensingRecord::find($dispRecordId);
        $this->assertTrue($record->has_interaction_warnings);
        $this->assertNotEmpty($record->interaction_alerts);
    }

    /**
     * TEST 6: Stock Intake, Adjustments, and Quarantining.
     */
    public function test_stock_intake_adjustment_and_quarantine_workflows(): void
    {
        $this->actAsPharmacist();

        $drug = $this->createDrug(['brand_name' => 'Metformin HCl']);

        // 1. Intake stock
        $intakeResponse = $this->postJson('/api/v1/pharmacy/stock/intake', [
            'drug_id' => $drug->id,
            'batch_number' => 'LOT-MET-2026-INTAKE',
            'expiry_date' => Carbon::today()->addMonths(12)->toDateString(),
            'quantity_received' => 200,
            'unit_cost_cents' => 12,
            'supplier_name' => 'Apex Healthcare',
            'notes' => 'Q3 Delivery shipment',
        ]);

        $intakeResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $batchId = $intakeResponse->json('data.id');

        $batch = DrugBatch::find($batchId);
        $this->assertEquals(200, $batch->quantity_on_hand);

        // 2. Adjust stock (-10 for breakage)
        $adjustResponse = $this->postJson('/api/v1/pharmacy/stock/adjust', [
            'drug_batch_id' => $batchId,
            'quantity_delta' => -10,
            'reason' => 'Broken vials during shelving',
        ]);

        $adjustResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $batch->refresh();
        $this->assertEquals(190, $batch->quantity_on_hand);

        // 3. Quarantine batch -> should remove from active batches
        $quarantineResponse = $this->postJson('/api/v1/pharmacy/stock/quarantine', [
            'drug_batch_id' => $batchId,
            'reason' => 'Manufacturer recall batch inquiry',
        ]);

        $quarantineResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $batch->refresh();
        $this->assertEquals('quarantined', $batch->status);

        // Should not appear in drug's activeBatches
        $drug->refresh();
        $this->assertFalse($drug->activeBatches->contains('id', $batch->id));
        $this->assertEquals(0, $drug->total_stock_on_hand);
    }
}
