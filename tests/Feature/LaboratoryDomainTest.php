<?php

namespace Tests\Feature;

use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Laboratory\Models\LabResult;
use App\Domain\Laboratory\Models\LabResultItem;
use App\Domain\Laboratory\Models\LabSample;
use App\Domain\Laboratory\Models\LabTest;
use App\Domain\Laboratory\Models\ReferenceRange;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LaboratoryDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected Branch $branch;
    protected User $technician;
    protected User $pathologist;
    protected User $doctor;
    protected Patient $patient;
    protected LabTest $cbcTest;
    protected LabTest $bmpTest;
    protected LabOrder $labOrder;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Organization & Branch
        $this->org = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'St. Jude Health System',
            'code' => 'SJHS',
            'tax_number' => 'TAX-SJHS-9988',
        ]);

        $this->branch = Branch::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'name' => 'St. Jude Central Hospital',
            'code' => 'CENTRAL',
        ]);

        // 2. Users
        $this->doctor = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. Robert Chase',
            'email' => 'dr.chase@stjude.org',
            'password' => bcrypt('password123'),
        ]);

        $this->technician = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Alex Drake, MLT',
            'email' => 'adrake@stjude.org',
            'password' => bcrypt('password123'),
        ]);

        $this->pathologist = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. Gregory House, MD (Pathology)',
            'email' => 'ghouse@stjude.org',
            'password' => bcrypt('password123'),
        ]);

        // 3. Patient
        $this->patient = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-2026-LAB-001',
            'first_name' => 'Arthur',
            'last_name' => 'Pendleton',
            'date_of_birth' => '1980-04-12',
            'gender' => 'male',
            'blood_group' => 'O+',
        ]);

        // 4. Lab Tests & Reference Ranges
        $this->cbcTest = LabTest::create([
            'id' => (string) Str::uuid(),
            'code' => 'CBC',
            'name' => 'Complete Blood Count',
            'category' => 'Hematology',
            'specimen_type' => 'Whole Blood (EDTA)',
            'container_type' => 'Lavender Top',
            'turn_around_time_minutes' => 60,
            'price_cents' => 3000,
            'is_active' => true,
        ]);

        // Hemoglobin for adult male: normal 13.5 - 17.5, panic low < 7.0, panic high > 20.0
        ReferenceRange::create([
            'id' => (string) Str::uuid(),
            'lab_test_id' => $this->cbcTest->id,
            'parameter_name' => 'Hemoglobin',
            'unit' => 'g/dL',
            'gender' => 'male',
            'age_min_years' => 18,
            'age_max_years' => 120,
            'normal_low' => 13.5,
            'normal_high' => 17.5,
            'critical_low' => 7.0,
            'critical_high' => 20.0,
        ]);

        // Platelets: normal 150 - 450, panic low < 50
        ReferenceRange::create([
            'id' => (string) Str::uuid(),
            'lab_test_id' => $this->cbcTest->id,
            'parameter_name' => 'Platelets',
            'unit' => '10^3/uL',
            'gender' => 'all',
            'normal_low' => 150.0,
            'normal_high' => 450.0,
            'critical_low' => 50.0,
            'critical_high' => 1000.0,
        ]);

        $this->bmpTest = LabTest::create([
            'id' => (string) Str::uuid(),
            'code' => 'BMP',
            'name' => 'Basic Metabolic Panel',
            'category' => 'Clinical Chemistry',
            'specimen_type' => 'Serum',
            'container_type' => 'SST Gold',
            'turn_around_time_minutes' => 90,
            'price_cents' => 4500,
            'is_active' => true,
        ]);

        // Potassium: normal 3.5 - 5.0, critical low < 2.8, critical high > 6.2
        ReferenceRange::create([
            'id' => (string) Str::uuid(),
            'lab_test_id' => $this->bmpTest->id,
            'parameter_name' => 'Potassium',
            'unit' => 'mEq/L',
            'gender' => 'all',
            'normal_low' => 3.5,
            'normal_high' => 5.0,
            'critical_low' => 2.8,
            'critical_high' => 6.2,
        ]);

        // 5. Clinical Lab Order
        $this->labOrder = LabOrder::create([
            'id' => (string) Str::uuid(),
            'order_number' => 'LAB-ORD-2026-0001',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'ordering_doctor_id' => $this->doctor->id,
            'test_type' => 'Complete Blood Count',
            'priority' => 'routine',
            'clinical_indication' => 'Weakness and pale conjunctiva',
            'status' => 'pending',
            'ordered_at' => now(),
        ]);
    }

    protected function actingAsLabUser(?User $user = null)
    {
        $u = $user ?? $this->technician;
        Sanctum::actingAs($u);

        return $this->withHeaders([
            'X-Branch-ID' => $this->branch->id,
            'Accept' => 'application/json',
        ]);
    }

    // =========================================================================
    // 1. SAMPLE ACCESSION & BARCODE TRACKING
    // =========================================================================

    public function test_can_create_sample_with_unique_scannable_barcode(): void
    {
        $response = $this->actingAsLabUser()->postJson('/api/v1/laboratory/samples', [
            'lab_order_id' => $this->labOrder->id,
            'sample_type' => 'Whole Blood (EDTA)',
            'container_type' => 'Lavender top tube',
            'collection_site' => 'Right median cubital vein',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'barcode',
                    'sample_type',
                    'container_type',
                    'status',
                ]
            ]);

        $barcode = $response->json('data.barcode');
        $this->assertMatchesRegularExpression('/^SMP-\d{4}-[A-Z0-9]{8}$/', $barcode);

        $this->assertDatabaseHas('lab_samples', [
            'id' => $response->json('data.id'),
            'barcode' => $barcode,
            'status' => 'pending_collection',
        ]);
    }

    public function test_barcode_scanner_lookup_resolves_sample_and_order_instantly(): void
    {
        // 1. Create sample
        $sample = LabSample::create([
            'id' => (string) Str::uuid(),
            'barcode' => 'SMP-2026-SCAN0001',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'lab_order_id' => $this->labOrder->id,
            'patient_id' => $this->patient->id,
            'sample_type' => 'Whole Blood (EDTA)',
            'container_type' => 'Lavender Top',
            'status' => 'pending_collection',
        ]);

        // 2. Scan barcode
        $response = $this->actingAsLabUser()->getJson('/api/v1/laboratory/samples/scan/SMP-2026-SCAN0001');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.barcode', 'SMP-2026-SCAN0001')
            ->assertJsonPath('data.patient.name', 'Arthur Pendleton')
            ->assertJsonPath('data.lab_order.order_number', 'LAB-ORD-2026-0001');
    }

    public function test_barcode_scanner_lookup_returns_404_for_unknown_barcode(): void
    {
        $response = $this->actingAsLabUser()->getJson('/api/v1/laboratory/samples/scan/NON-EXISTENT-CODE');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'BARCODE_NOT_FOUND');
    }

    public function test_can_update_sample_lifecycle_status(): void
    {
        $sample = LabSample::create([
            'id' => (string) Str::uuid(),
            'barcode' => 'SMP-2026-LIFE0001',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'lab_order_id' => $this->labOrder->id,
            'patient_id' => $this->patient->id,
            'sample_type' => 'Whole Blood (EDTA)',
            'container_type' => 'Lavender Top',
            'status' => 'pending_collection',
        ]);

        // Mark as collected
        $resCollect = $this->actingAsLabUser()->patchJson("/api/v1/laboratory/samples/{$sample->id}/status", [
            'status' => 'collected',
            'collection_site' => 'Left arm',
        ]);

        $resCollect->assertStatus(200)->assertJsonPath('data.status', 'collected');
        $this->assertNotNull(LabSample::find($sample->id)->collected_at);

        // Mark as received in laboratory
        $resReceive = $this->actingAsLabUser()->patchJson("/api/v1/laboratory/samples/{$sample->id}/status", [
            'status' => 'received',
        ]);

        $resReceive->assertStatus(200)->assertJsonPath('data.status', 'received');
        $this->assertNotNull(LabSample::find($sample->id)->received_at);
    }

    // =========================================================================
    // 2. RESULT ENTRY & AUTOMATIC VALUE FLAGGING
    // =========================================================================

    public function test_auto_flags_normal_parameter_results(): void
    {
        $sample = LabSample::create([
            'id' => (string) Str::uuid(),
            'barcode' => 'SMP-2026-NORM0001',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'lab_order_id' => $this->labOrder->id,
            'patient_id' => $this->patient->id,
            'sample_type' => 'Whole Blood (EDTA)',
            'container_type' => 'Lavender Top',
            'status' => 'received',
        ]);

        $response = $this->actingAsLabUser()->postJson('/api/v1/laboratory/results', [
            'lab_order_id' => $this->labOrder->id,
            'lab_test_id' => $this->cbcTest->id,
            'lab_sample_id' => $sample->id,
            'parameters' => [
                ['parameter_name' => 'Hemoglobin', 'measured_value' => '15.2', 'unit' => 'g/dL'],
                ['parameter_name' => 'Platelets', 'measured_value' => '250', 'unit' => '10^3/uL'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.has_abnormal_values', false)
            ->assertJsonPath('data.has_critical_values', false)
            ->assertJsonPath('data.items.0.flag', 'normal')
            ->assertJsonPath('data.items.1.flag', 'normal');
    }

    public function test_auto_flags_abnormal_values_and_triggers_doctor_notification(): void
    {
        $sample = LabSample::create([
            'id' => (string) Str::uuid(),
            'barcode' => 'SMP-2026-ABN00001',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'lab_order_id' => $this->labOrder->id,
            'patient_id' => $this->patient->id,
            'sample_type' => 'Whole Blood (EDTA)',
            'container_type' => 'Lavender Top',
            'status' => 'received',
        ]);

        // Hemoglobin 10.5 is below normal for adult male (13.5 - 17.5) -> Flag: 'low'
        $response = $this->actingAsLabUser()->postJson('/api/v1/laboratory/results', [
            'lab_order_id' => $this->labOrder->id,
            'lab_test_id' => $this->cbcTest->id,
            'lab_sample_id' => $sample->id,
            'parameters' => [
                ['parameter_name' => 'Hemoglobin', 'measured_value' => '10.5', 'unit' => 'g/dL'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.has_abnormal_values', true)
            ->assertJsonPath('data.has_critical_values', false)
            ->assertJsonPath('data.items.0.flag', 'low');

        // Verify doctor notification timestamp was set
        $resultId = $response->json('data.id');
        $result = LabResult::find($resultId);
        $this->assertNotNull($result->doctor_notified_at);
        $this->assertEquals('system_alert', $result->doctor_notified_channel);
    }

    public function test_auto_flags_critical_panic_values(): void
    {
        $sample = LabSample::create([
            'id' => (string) Str::uuid(),
            'barcode' => 'SMP-2026-CRIT0001',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'lab_order_id' => $this->labOrder->id,
            'patient_id' => $this->patient->id,
            'sample_type' => 'Whole Blood (EDTA)',
            'container_type' => 'Lavender Top',
            'status' => 'received',
        ]);

        // Hemoglobin 5.8 is < critical_low (7.0) -> Flag: 'critical_low'
        $response = $this->actingAsLabUser()->postJson('/api/v1/laboratory/results', [
            'lab_order_id' => $this->labOrder->id,
            'lab_test_id' => $this->cbcTest->id,
            'lab_sample_id' => $sample->id,
            'parameters' => [
                ['parameter_name' => 'Hemoglobin', 'measured_value' => '5.8', 'unit' => 'g/dL'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.has_abnormal_values', true)
            ->assertJsonPath('data.has_critical_values', true)
            ->assertJsonPath('data.items.0.flag', 'critical_low');
    }

    // =========================================================================
    // 3. DIGITAL SIGNING & REPORT IMMUTABILITY
    // =========================================================================

    public function test_pathologist_can_digitally_sign_and_lock_lab_report(): void
    {
        // Create preliminary result
        $result = LabResult::create([
            'id' => (string) Str::uuid(),
            'report_number' => 'REP-2026-SIGN0001',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'lab_order_id' => $this->labOrder->id,
            'lab_test_id' => $this->cbcTest->id,
            'patient_id' => $this->patient->id,
            'technician_id' => $this->technician->id,
            'status' => 'preliminary',
        ]);

        LabResultItem::create([
            'id' => (string) Str::uuid(),
            'lab_result_id' => $result->id,
            'parameter_name' => 'Hemoglobin',
            'measured_value' => '14.5',
            'unit' => 'g/dL',
            'flag' => 'normal',
        ]);

        // Sign report
        $signResponse = $this->actingAsLabUser($this->pathologist)->postJson("/api/v1/laboratory/results/{$result->id}/sign", [
            'pathologist_id' => $this->pathologist->id,
            'clinical_remarks' => 'Normal hematological profile. Clinically verified.',
        ]);

        $signResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'signed')
            ->assertJsonPath('data.pathologist', $this->pathologist->name);

        $hash = $signResponse->json('data.digital_signature_hash');
        $this->assertStringStartsWith('SIG-SHA256-', $hash);

        // Lab order status updated to completed
        $this->assertEquals('completed', $this->labOrder->fresh()->status);
    }

    public function test_signed_lab_report_is_immutable_against_direct_modifications(): void
    {
        $result = LabResult::create([
            'id' => (string) Str::uuid(),
            'report_number' => 'REP-2026-LOCK0001',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'lab_order_id' => $this->labOrder->id,
            'lab_test_id' => $this->cbcTest->id,
            'patient_id' => $this->patient->id,
            'technician_id' => $this->technician->id,
            'pathologist_id' => $this->pathologist->id,
            'status' => 'signed',
            'digital_signature_hash' => 'SIG-SHA256-abc123locked',
            'signed_at' => now(),
        ]);

        // Direct update to locked report throws DomainException
        $this->expectException(DomainException::class);
        $result->update([
            'clinical_remarks' => 'Attempting to change remarks on signed legal report',
        ]);
    }

    // =========================================================================
    // 4. APPEND-ONLY AMENDMENTS FOR SIGNED REPORTS
    // =========================================================================

    public function test_can_create_versioned_amendment_for_signed_report(): void
    {
        $original = LabResult::create([
            'id' => (string) Str::uuid(),
            'report_number' => 'REP-2026-AMEND01',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'lab_order_id' => $this->labOrder->id,
            'lab_test_id' => $this->cbcTest->id,
            'patient_id' => $this->patient->id,
            'technician_id' => $this->technician->id,
            'pathologist_id' => $this->pathologist->id,
            'status' => 'signed',
            'version' => 1,
            'is_amended' => false,
            'digital_signature_hash' => 'SIG-SHA256-originalhash',
            'signed_at' => now()->subDay(),
        ]);

        LabResultItem::create([
            'id' => (string) Str::uuid(),
            'lab_result_id' => $original->id,
            'parameter_name' => 'Hemoglobin',
            'measured_value' => '11.0',
            'unit' => 'g/dL',
            'flag' => 'low',
        ]);

        // Amend report
        $amendResponse = $this->actingAsLabUser()->postJson("/api/v1/laboratory/results/{$original->id}/amend", [
            'amendment_reason' => 'Instrument recalibrated; re-run yielded corrected hemoglobin measurement.',
            'parameters' => [
                ['parameter_name' => 'Hemoglobin', 'measured_value' => '14.2', 'unit' => 'g/dL'],
            ],
        ]);

        $amendResponse->assertStatus(201)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.amended_from_id', $original->id)
            ->assertJsonPath('data.amendment_reason', 'Instrument recalibrated; re-run yielded corrected hemoglobin measurement.')
            ->assertJsonPath('data.items.0.measured_value', '14.2')
            ->assertJsonPath('data.items.0.flag', 'normal');

        // Check original report status
        $this->assertEquals('amended', $original->fresh()->status);
        $this->assertTrue($original->fresh()->is_amended);
    }

    // =========================================================================
    // 5. EQUIPMENT & INSTRUMENT INTEGRATION HOOKS (HL7 & ASTM)
    // =========================================================================

    public function test_can_ingest_hl7_oru_r01_observation_message(): void
    {
        $sample = LabSample::create([
            'id' => (string) Str::uuid(),
            'barcode' => 'SMP-2026-HL7TEST1',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'lab_order_id' => $this->labOrder->id,
            'patient_id' => $this->patient->id,
            'sample_type' => 'Whole Blood (EDTA)',
            'container_type' => 'Lavender Top',
            'status' => 'received',
        ]);

        $hl7Message = implode("\r\n", [
            'MSH|^~\&|SYSCLINIC_XN1000|LAB|HMS|HOSPITAL|20260912120000||ORU^R01|MSG001|P|2.5',
            'PID|1||MRN-2026-LAB-001||Pendleton^Arthur||19800412|M',
            'OBR|1|LAB-ORD-2026-0001|SMP-2026-HL7TEST1|CBC^Complete Blood Count|||20260912115500',
            'OBX|1|NM|HGB^Hemoglobin|1|14.8|g/dL|13.5-17.5|N|||F',
            'OBX|2|NM|PLT^Platelets|1|220|10^3/uL|150-450|N|||F',
        ]);

        $response = $this->actingAsLabUser()->postJson('/api/v1/laboratory/equipment/hl7', [
            'hl7_message' => $hl7Message,
            'device_id' => 'SYSCLINIC_XN1000',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.protocol', 'HL7_v2')
            ->assertJsonPath('data.sample_barcode', 'SMP-2026-HL7TEST1')
            ->assertJsonPath('data.parameters_ingested', 2);

        $this->assertDatabaseHas('lab_results', [
            'lab_sample_id' => $sample->id,
            'analyzer_device_id' => 'SYSCLINIC_XN1000',
        ]);
    }

    public function test_can_ingest_astm_e1394_instrument_records(): void
    {
        $sample = LabSample::create([
            'id' => (string) Str::uuid(),
            'barcode' => 'SMP-2026-ASTMTEST',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'lab_order_id' => $this->labOrder->id,
            'patient_id' => $this->patient->id,
            'sample_type' => 'Serum',
            'container_type' => 'Gold Top',
            'status' => 'received',
        ]);

        $astmMessage = implode("\r\n", [
            'H|\^&|||ROCHE_COBAS_6000|||||||P|1394-97|20260912120000',
            'P|1||MRN-2026-LAB-001||Pendleton^Arthur',
            'O|1|SMP-2026-ASTMTEST||^^^Potassium|R|20260912120100',
            'R|1|^^^Potassium|4.2|mEq/L|3.5-5.0|N|F',
            'L|1|N',
        ]);

        $response = $this->actingAsLabUser()->postJson('/api/v1/laboratory/equipment/astm', [
            'astm_message' => $astmMessage,
            'device_id' => 'ROCHE_COBAS_6000',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.protocol', 'ASTM_E1394')
            ->assertJsonPath('data.sample_barcode', 'SMP-2026-ASTMTEST')
            ->assertJsonPath('data.parameters_ingested', 1);
    }

    // =========================================================================
    // 6. PRINTABLE REPORT PAYLOAD GENERATION
    // =========================================================================

    public function test_can_generate_structured_printable_report_payload(): void
    {
        $result = LabResult::create([
            'id' => (string) Str::uuid(),
            'report_number' => 'REP-2026-PRINT001',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'lab_order_id' => $this->labOrder->id,
            'lab_test_id' => $this->cbcTest->id,
            'patient_id' => $this->patient->id,
            'technician_id' => $this->technician->id,
            'pathologist_id' => $this->pathologist->id,
            'status' => 'signed',
            'digital_signature_hash' => 'SIG-SHA256-printhash99',
            'signed_at' => now(),
        ]);

        LabResultItem::create([
            'id' => (string) Str::uuid(),
            'lab_result_id' => $result->id,
            'parameter_name' => 'Hemoglobin',
            'measured_value' => '14.8',
            'unit' => 'g/dL',
            'reference_low' => 13.5,
            'reference_high' => 17.5,
            'flag' => 'normal',
        ]);

        $response = $this->actingAsLabUser()->getJson("/api/v1/laboratory/results/{$result->id}/print");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.report_number', 'REP-2026-PRINT001')
            ->assertJsonPath('data.patient.name', 'Arthur Pendleton')
            ->assertJsonPath('data.parameters.0.parameter_name', 'Hemoglobin')
            ->assertJsonPath('data.digital_signature_hash', 'SIG-SHA256-printhash99');
    }
}
