<?php

namespace Database\Seeders;

use App\Domain\Clinical\Models\RadiologyOrder;
use App\Domain\Patient\Models\Patient;
use App\Domain\Radiology\Models\ImagingFile;
use App\Domain\Radiology\Models\ImagingOrder;
use App\Domain\Radiology\Models\ImagingReport;
use App\Domain\Radiology\Services\PacsIntegrationService;
use App\Domain\Shared\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RadiologyModuleSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::first();
        $patient = Patient::first();
        $doctor = User::where('email', 'like', '%doctor%')
            ->orWhere('name', 'like', '%Dr.%')
            ->first() ?? User::first();

        if (!$branch || !$patient || !$doctor) {
            return;
        }

        $pacsService = app(PacsIntegrationService::class);
        $radOrder = RadiologyOrder::first();

        // 1. Finalized Chest X-Ray Study
        $order1 = ImagingOrder::firstOrCreate(
            ['accession_number' => 'ACC-2026-0000101'],
            [
                'id' => (string) Str::uuid(),
                'accession_number' => 'ACC-2026-0000101',
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'patient_id' => $patient->id,
                'radiology_order_id' => $radOrder?->id,
                'ordering_doctor_id' => $doctor->id,
                'technologist_id' => $doctor->id,
                'modality' => 'X-Ray',
                'procedure_code' => 'CPT-71046',
                'procedure_name' => 'Chest X-Ray 2 Views (PA and Lateral)',
                'body_part' => 'Chest',
                'priority' => 'routine',
                'clinical_indication' => 'Chronic cough and baseline pulmonary assessment',
                'transport_mode' => 'ambulatory',
                'status' => 'completed',
                'scheduled_at' => now()->subDays(1)->setTime(10, 0),
                'scheduled_room' => 'X-Ray Suite 1',
                'started_at' => now()->subDays(1)->setTime(10, 5),
                'completed_at' => now()->subDays(1)->setTime(10, 25),
                'dicom_study_uid' => $pacsService->generateStudyInstanceUid(),
                'pacs_status' => 'available',
            ]
        );

        $report1 = ImagingReport::firstOrCreate(
            ['imaging_order_id' => $order1->id],
            [
                'id' => (string) Str::uuid(),
                'report_number' => 'RAD-REP-2026-000101',
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'imaging_order_id' => $order1->id,
                'patient_id' => $patient->id,
                'radiologist_id' => $doctor->id,
                'status' => 'finalized',
                'clinical_indication' => 'Chronic cough for 3 weeks; rule out parenchymal consolidation.',
                'technique' => 'Posteroanterior and left lateral views of the chest obtained in full inspiration.',
                'comparison' => 'No prior chest radiographs available for comparison.',
                'findings' => 'The lungs are clear without focal consolidation, pneumothorax, or large pleural effusion. Cardiomediastinal silhouette and hilar contours are within normal limits for age. Visualized osseous structures demonstrate no acute fractures.',
                'impression' => '1. No acute cardiopulmonary disease. 2. Clear lungs bilaterally with normal heart size.',
                'recommendations' => 'Clinical follow-up as indicated.',
                'critical_alert' => false,
                'digital_signature_hash' => 'RAD-SHA256-' . substr(hash('sha256', 'RAD-REP-2026-000101-seed'), 0, 40),
                'finalized_at' => now()->subDays(1)->setTime(11, 15),
                'finalized_by' => $doctor->id,
                'version' => 1,
                'is_amended' => false,
            ]
        );

        ImagingFile::firstOrCreate(
            ['file_name' => 'CXR_PA_LAT_001.jpg'],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'imaging_order_id' => $order1->id,
                'imaging_report_id' => $report1->id,
                'patient_id' => $patient->id,
                'file_name' => 'CXR_PA_LAT_001.jpg',
                'original_file_name' => 'Chest_PA_Inspiration.jpg',
                'file_path' => 'radiology/samples/cxr_sample.jpg',
                'disk' => 'public',
                'mime_type' => 'image/jpeg',
                'file_size_bytes' => 1024 * 768 * 2,
                'is_dicom' => false,
                'series_description' => 'PA Chest View',
                'series_number' => 1,
                'instance_number' => 1,
                'upload_status' => 'completed',
                'uploaded_by' => $doctor->id,
            ]
        );

        // 2. Scheduled CT Brain with Contrast (Urgent)
        $order2 = ImagingOrder::firstOrCreate(
            ['accession_number' => 'ACC-2026-0000102'],
            [
                'id' => (string) Str::uuid(),
                'accession_number' => 'ACC-2026-0000102',
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'patient_id' => $patient->id,
                'ordering_doctor_id' => $doctor->id,
                'modality' => 'CT',
                'procedure_code' => 'CPT-70460',
                'procedure_name' => 'CT Head / Brain with IV Contrast',
                'body_part' => 'Brain',
                'priority' => 'urgent',
                'clinical_indication' => 'Severe acute headache with visual disturbances',
                'patient_preparation' => 'NPO for 4 hours prior. Check serum creatinine/eGFR prior to IV iodinated contrast.',
                'transport_mode' => 'wheelchair',
                'status' => 'scheduled',
                'scheduled_at' => now()->addHours(2),
                'scheduled_room' => 'CT Suite 2 (Siemens Somatom)',
                'dicom_study_uid' => $pacsService->generateStudyInstanceUid(),
                'pacs_status' => 'pending',
            ]
        );
    }
}
