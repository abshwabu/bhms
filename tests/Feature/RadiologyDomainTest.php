<?php

namespace Tests\Feature;

use App\Domain\Clinical\Models\RadiologyOrder;
use App\Domain\Patient\Models\Patient;
use App\Domain\Radiology\Models\ImagingFile;
use App\Domain\Radiology\Models\ImagingFileChunk;
use App\Domain\Radiology\Models\ImagingOrder;
use App\Domain\Radiology\Models\ImagingReport;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RadiologyDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected Branch $branch;
    protected User $doctor;
    protected User $radiologist;
    protected User $technologist;
    protected Patient $patient;
    protected RadiologyOrder $clinicalOrder;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        // 1. Organization & Branch
        $this->org = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'Metro Imaging & Diagnostics',
            'code' => 'MID',
            'tax_number' => 'TAX-MID-5544',
        ]);

        $this->branch = Branch::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'name' => 'Metro Radiology Center',
            'code' => 'MAIN-RAD',
        ]);

        // 2. Users
        $this->doctor = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. Lisa Cuddy, MD',
            'email' => 'lcuddy@metroimaging.org',
            'password' => bcrypt('password123'),
        ]);

        $this->radiologist = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. James Wilson, MD (Radiology)',
            'email' => 'jwilson@metroimaging.org',
            'password' => bcrypt('password123'),
        ]);

        $this->technologist = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Sam Bradley, RT(R)(CT)',
            'email' => 'sbradley@metroimaging.org',
            'password' => bcrypt('password123'),
        ]);

        // 3. Patient
        $this->patient = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-2026-RAD-001',
            'first_name' => 'Walter',
            'last_name' => 'White',
            'date_of_birth' => '1958-09-07',
            'gender' => 'male',
            'blood_group' => 'A+',
        ]);

        // 4. Clinical Radiology Order
        $this->clinicalOrder = RadiologyOrder::create([
            'id' => (string) Str::uuid(),
            'order_number' => 'RAD-ORD-2026-901',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'ordering_doctor_id' => $this->doctor->id,
            'modality' => 'CT',
            'body_part' => 'Chest',
            'procedure_name' => 'CT Chest High Resolution without Contrast',
            'priority' => 'urgent',
            'clinical_indication' => 'Chronic persistent hemoptysis and cough',
            'status' => 'ordered',
            'ordered_at' => now(),
        ]);
    }

    protected function actingAsStaff(?User $user = null)
    {
        $u = $user ?? $this->radiologist;
        Sanctum::actingAs($u);

        return $this->withHeaders([
            'X-Branch-ID' => $this->branch->id,
            'Accept' => 'application/json',
        ]);
    }

    // =========================================================================
    // 1. ORDER INTAKE, MODALITY SCHEDULING & WORKLIST
    // =========================================================================

    public function test_can_intake_new_imaging_order_with_unique_accession_number(): void
    {
        $response = $this->actingAsStaff()->postJson('/api/v1/radiology/orders', [
            'patient_id' => $this->patient->id,
            'radiology_order_id' => $this->clinicalOrder->id,
            'modality' => 'CT',
            'procedure_name' => 'CT Chest High Resolution',
            'procedure_code' => 'CPT-71250',
            'body_part' => 'Chest',
            'priority' => 'urgent',
            'clinical_indication' => 'Hemoptysis and suspected bronchiectasis',
            'transport_mode' => 'ambulatory',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.patient.id', $this->patient->id)
            ->assertJsonPath('data.modality', 'CT')
            ->assertJsonPath('data.priority', 'urgent');

        $accession = $response->json('data.accession_number');
        $this->assertMatchesRegularExpression('/^ACC-\d{4}-[A-Z0-9]{8}$/', $accession);
        $this->assertNotEmpty($response->json('data.dicom_study_uid'));
    }

    public function test_can_schedule_imaging_order_slot_room_and_technologist(): void
    {
        $order = ImagingOrder::create([
            'id' => (string) Str::uuid(),
            'accession_number' => 'ACC-2026-TEST001',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'radiology_order_id' => $this->clinicalOrder->id,
            'ordering_doctor_id' => $this->doctor->id,
            'modality' => 'CT',
            'procedure_name' => 'CT Chest',
            'body_part' => 'Chest',
            'priority' => 'urgent',
            'status' => 'ordered',
        ]);

        $scheduleTime = now()->addDay()->setTime(14, 30);

        $response = $this->actingAsStaff()->postJson("/api/v1/radiology/orders/{$order->id}/schedule", [
            'scheduled_at' => $scheduleTime->toIso8601String(),
            'scheduled_room' => 'CT Suite 1 (GE Revolution)',
            'technologist_id' => $this->technologist->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.scheduled_room', 'CT Suite 1 (GE Revolution)')
            ->assertJsonPath('data.technologist', $this->technologist->name);

        $this->assertEquals('scheduled', $this->clinicalOrder->fresh()->status);
    }

    public function test_can_advance_order_status_to_in_progress_and_completed(): void
    {
        $order = ImagingOrder::create([
            'id' => (string) Str::uuid(),
            'accession_number' => 'ACC-2026-TEST002',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'ordering_doctor_id' => $this->doctor->id,
            'modality' => 'X-Ray',
            'procedure_name' => 'Chest X-Ray',
            'body_part' => 'Chest',
            'status' => 'scheduled',
        ]);

        // Start acquisition
        $resStart = $this->actingAsStaff()->postJson("/api/v1/radiology/orders/{$order->id}/start");
        $resStart->assertStatus(200)->assertJsonPath('data.status', 'in_progress');

        // Complete acquisition
        $resComp = $this->actingAsStaff()->postJson("/api/v1/radiology/orders/{$order->id}/complete");
        $resComp->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.pacs_status', 'available');
    }

    // =========================================================================
    // 2. ACCEPTANCE CRITERIA 1 & 3: REPORT ENTRY (DRAFT & FINALIZED STATES)
    // =========================================================================

    public function test_report_is_unambiguously_linked_to_correct_order_and_patient(): void
    {
        $order = ImagingOrder::create([
            'id' => (string) Str::uuid(),
            'accession_number' => 'ACC-2026-LINK001',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'ordering_doctor_id' => $this->doctor->id,
            'modality' => 'MRI',
            'procedure_name' => 'MRI Brain without Contrast',
            'body_part' => 'Brain',
            'status' => 'completed',
        ]);

        $response = $this->actingAsStaff()->postJson('/api/v1/radiology/reports', [
            'imaging_order_id' => $order->id,
            'clinical_indication' => 'Evaluate for acute ischemia',
            'technique' => 'Axial T1, T2, FLAIR, DWI, and ADC sequences of the brain.',
            'findings' => 'No acute territorial infarction or intracranial hemorrhage.',
            'impression' => 'Normal brain MRI without evidence of acute ischemia.',
            'finalize' => false, // Save as Draft
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.imaging_order_id', $order->id)
            ->assertJsonPath('data.patient_id', $this->patient->id)
            ->assertJsonPath('data.patient.name', 'Walter White')
            ->assertJsonPath('data.patient.mrn', 'MRN-2026-RAD-001')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.version', 1);

        $this->assertDatabaseHas('imaging_reports', [
            'imaging_order_id' => $order->id,
            'patient_id' => $this->patient->id,
            'status' => 'draft',
        ]);
    }

    public function test_reports_support_draft_and_finalized_states(): void
    {
        $order = ImagingOrder::create([
            'id' => (string) Str::uuid(),
            'accession_number' => 'ACC-2026-STATE01',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'radiology_order_id' => $this->clinicalOrder->id,
            'ordering_doctor_id' => $this->doctor->id,
            'modality' => 'CT',
            'procedure_name' => 'CT Abdomen and Pelvis with IV Contrast',
            'body_part' => 'Abdomen',
            'status' => 'completed',
        ]);

        // 1. Create Draft Report
        $draftRes = $this->actingAsStaff()->postJson('/api/v1/radiology/reports', [
            'imaging_order_id' => $order->id,
            'technique' => 'Helical axial CT from diaphragm through pubic symphysis.',
            'findings' => 'Draft notes: liver and kidneys appear unremarkable.',
            'impression' => 'Preliminary: no acute intra-abdominal pathology.',
            'finalize' => false,
        ]);

        $draftRes->assertStatus(201)->assertJsonPath('data.status', 'draft');
        $reportId = $draftRes->json('data.id');

        // 2. Finalize Report
        $finalizeRes = $this->actingAsStaff()->postJson("/api/v1/radiology/reports/{$reportId}/finalize");

        $finalizeRes->assertStatus(200)
            ->assertJsonPath('data.status', 'finalized')
            ->assertJsonPath('data.radiologist', $this->radiologist->name);

        $hash = $finalizeRes->json('data.digital_signature_hash');
        $this->assertStringStartsWith('RAD-SHA256-', $hash);

        // Clinical order status is updated to reported
        $this->assertEquals('reported', $this->clinicalOrder->fresh()->status);
        $this->assertEquals('completed', $order->fresh()->status);
    }

    public function test_finalized_report_is_immutable_and_amendments_create_versioned_entry(): void
    {
        $order = ImagingOrder::create([
            'id' => (string) Str::uuid(),
            'accession_number' => 'ACC-2026-AMEND01',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'ordering_doctor_id' => $this->doctor->id,
            'modality' => 'X-Ray',
            'procedure_name' => 'Chest X-Ray',
            'body_part' => 'Chest',
            'status' => 'completed',
        ]);

        $report = ImagingReport::create([
            'id' => (string) Str::uuid(),
            'report_number' => 'RAD-REP-2026-LOCK01',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'imaging_order_id' => $order->id,
            'patient_id' => $this->patient->id,
            'radiologist_id' => $this->radiologist->id,
            'status' => 'finalized',
            'findings' => 'Original clear chest radiograph.',
            'impression' => 'No acute cardiopulmonary disease.',
            'digital_signature_hash' => 'RAD-SHA256-abc123locked',
            'finalized_at' => now(),
            'version' => 1,
        ]);

        // Direct update on locked report throws DomainException
        $this->expectException(DomainException::class);
        $report->update(['findings' => 'Illegal direct overwrite attempt']);
    }

    public function test_can_create_versioned_amendment_for_finalized_report(): void
    {
        $order = ImagingOrder::create([
            'id' => (string) Str::uuid(),
            'accession_number' => 'ACC-2026-AMEND02',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'ordering_doctor_id' => $this->doctor->id,
            'modality' => 'X-Ray',
            'procedure_name' => 'Chest X-Ray',
            'body_part' => 'Chest',
            'status' => 'completed',
        ]);

        $original = ImagingReport::create([
            'id' => (string) Str::uuid(),
            'report_number' => 'RAD-REP-2026-ORIG01',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'imaging_order_id' => $order->id,
            'patient_id' => $this->patient->id,
            'radiologist_id' => $this->radiologist->id,
            'status' => 'finalized',
            'findings' => 'Clear lungs.',
            'impression' => 'Normal chest.',
            'digital_signature_hash' => 'RAD-SHA256-original',
            'finalized_at' => now(),
            'version' => 1,
            'is_amended' => false,
        ]);

        $response = $this->actingAsStaff()->postJson("/api/v1/radiology/reports/{$original->id}/amend", [
            'amendment_reason' => 'Subtle subsegmental atelectasis noted on secondary review with clinical team.',
            'findings' => 'Lungs are largely clear with minor subsegmental plate-like atelectasis at the left lung base.',
            'impression' => 'Subtle left basal subsegmental atelectasis; no pneumonia or pneumothorax.',
            'finalize' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.amended_from_id', $original->id)
            ->assertJsonPath('data.amendment_reason', 'Subtle subsegmental atelectasis noted on secondary review with clinical team.')
            ->assertJsonPath('data.status', 'finalized');

        $this->assertTrue($original->fresh()->is_amended);
        $this->assertEquals('amended', $original->fresh()->status);
    }

    // =========================================================================
    // 3. ACCEPTANCE CRITERIA 2: DIRECT & CHUNKED UPLOAD OF LARGE IMAGE FILES
    // =========================================================================

    public function test_can_upload_direct_imaging_file(): void
    {
        $order = ImagingOrder::create([
            'id' => (string) Str::uuid(),
            'accession_number' => 'ACC-2026-FILE001',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'ordering_doctor_id' => $this->doctor->id,
            'modality' => 'X-Ray',
            'procedure_name' => 'Chest X-Ray',
            'body_part' => 'Chest',
            'status' => 'in_progress',
        ]);

        $fakeFile = UploadedFile::fake()->image('cxr_view.jpg', 1920, 1080);

        $response = $this->actingAsStaff()->postJson('/api/v1/radiology/files', [
            'imaging_order_id' => $order->id,
            'file' => $fakeFile,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.original_file_name', 'cxr_view.jpg')
            ->assertJsonPath('data.0.mime_type', 'image/jpeg');

        $fileId = $response->json('data.0.id');
        $this->assertDatabaseHas('imaging_files', [
            'id' => $fileId,
            'imaging_order_id' => $order->id,
        ]);
    }

    public function test_large_file_uploads_via_chunked_upload_without_timing_out(): void
    {
        $order = ImagingOrder::create([
            'id' => (string) Str::uuid(),
            'accession_number' => 'ACC-2026-CHUNK01',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'ordering_doctor_id' => $this->doctor->id,
            'modality' => 'CT',
            'procedure_name' => 'High-Res Chest CT DICOM Series',
            'body_part' => 'Chest',
            'status' => 'in_progress',
        ]);

        // Step 1: Initialize chunked upload session for a large DICOM file (e.g. 15MB split into 3 chunks)
        $initRes = $this->actingAsStaff()->postJson('/api/v1/radiology/files/chunk/init', [
            'file_name' => 'CT_Chest_Series_512.dcm',
            'total_chunks' => 3,
            'total_size_bytes' => 15 * 1024 * 1024,
        ]);

        $initRes->assertStatus(201)
            ->assertJsonPath('data.total_chunks', 3)
            ->assertJsonPath('data.file_name', 'CT_Chest_Series_512.dcm');

        $uploadId = $initRes->json('data.upload_id');
        $this->assertNotEmpty($uploadId);

        // Step 2: Upload Chunk 0
        $chunk0 = UploadedFile::fake()->create('chunk_0.part', 5120); // 5MB simulated part
        $chunkRes0 = $this->actingAsStaff()->postJson('/api/v1/radiology/files/chunk/upload', [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 3,
            'chunk' => $chunk0,
        ]);
        $chunkRes0->assertStatus(200)->assertJsonPath('data.received_chunks', 1);

        // Step 3: Upload Chunk 1
        $chunk1 = UploadedFile::fake()->create('chunk_1.part', 5120);
        $chunkRes1 = $this->actingAsStaff()->postJson('/api/v1/radiology/files/chunk/upload', [
            'upload_id' => $uploadId,
            'chunk_index' => 1,
            'total_chunks' => 3,
            'chunk' => $chunk1,
        ]);
        $chunkRes1->assertStatus(200)->assertJsonPath('data.received_chunks', 2);

        // Step 4: Upload Chunk 2 (Final Chunk)
        $chunk2 = UploadedFile::fake()->create('chunk_2.part', 5120);
        $chunkRes2 = $this->actingAsStaff()->postJson('/api/v1/radiology/files/chunk/upload', [
            'upload_id' => $uploadId,
            'chunk_index' => 2,
            'total_chunks' => 3,
            'chunk' => $chunk2,
        ]);
        $chunkRes2->assertStatus(200)->assertJsonPath('data.progress_percentage', 100);

        // Step 5: Finalize and Assemble Chunks
        $finalizeRes = $this->actingAsStaff()->postJson('/api/v1/radiology/files/chunk/finalize', [
            'upload_id' => $uploadId,
            'imaging_order_id' => $order->id,
            'file_name' => 'CT_Chest_Series_512.dcm',
        ]);

        $finalizeRes->assertStatus(201)
            ->assertJsonPath('data.original_file_name', 'CT_Chest_Series_512.dcm')
            ->assertJsonPath('data.is_dicom', true)
            ->assertJsonPath('data.mime_type', 'application/dicom');

        $this->assertNotEmpty($finalizeRes->json('data.dicom_sop_instance_uid'));
        $this->assertNotEmpty($finalizeRes->json('data.pacs_preview_url'));

        // Verify stored in DB
        $this->assertDatabaseHas('imaging_files', [
            'imaging_order_id' => $order->id,
            'original_file_name' => 'CT_Chest_Series_512.dcm',
            'is_dicom' => true,
        ]);
    }

    // =========================================================================
    // 4. PRINTABLE REPORT PAYLOAD GENERATION
    // =========================================================================

    public function test_can_generate_structured_printable_radiology_report(): void
    {
        $order = ImagingOrder::create([
            'id' => (string) Str::uuid(),
            'accession_number' => 'ACC-2026-PRINT01',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'ordering_doctor_id' => $this->doctor->id,
            'modality' => 'CT',
            'procedure_name' => 'CT Brain without Contrast',
            'body_part' => 'Brain',
            'status' => 'completed',
        ]);

        $report = ImagingReport::create([
            'id' => (string) Str::uuid(),
            'report_number' => 'RAD-REP-2026-PRINT01',
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'imaging_order_id' => $order->id,
            'patient_id' => $this->patient->id,
            'radiologist_id' => $this->radiologist->id,
            'status' => 'finalized',
            'clinical_indication' => 'Acute severe headache',
            'findings' => 'No acute intracranial hemorrhage or mass effect.',
            'impression' => 'Unremarkable noncontrast head CT.',
            'digital_signature_hash' => 'RAD-SHA256-printhash123',
            'finalized_at' => now(),
        ]);

        $response = $this->actingAsStaff()->getJson("/api/v1/radiology/reports/{$report->id}/print");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.report_number', 'RAD-REP-2026-PRINT01')
            ->assertJsonPath('data.patient.name', 'Walter White')
            ->assertJsonPath('data.order.accession_number', 'ACC-2026-PRINT01')
            ->assertJsonPath('data.digital_signature_hash', 'RAD-SHA256-printhash123');
    }
}
