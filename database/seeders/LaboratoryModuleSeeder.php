<?php

namespace Database\Seeders;

use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Laboratory\Models\LabResult;
use App\Domain\Laboratory\Models\LabResultItem;
use App\Domain\Laboratory\Models\LabSample;
use App\Domain\Laboratory\Models\LabTest;
use App\Domain\Laboratory\Models\ReferenceRange;
use App\Domain\Laboratory\Services\BarcodeService;
use App\Domain\Laboratory\Services\LabResultEvaluationService;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LaboratoryModuleSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::first();
        if (!$branch) {
            return;
        }

        // 1. Seed Diagnostic Lab Tests Catalog
        $testsCatalog = [
            [
                'code' => 'CBC',
                'name' => 'Complete Blood Count (CBC with Differential)',
                'category' => 'Hematology',
                'specimen_type' => 'Whole Blood (EDTA)',
                'container_type' => 'Lavender top tube',
                'turnaround_time_hours' => 2,
                'price' => 35.00,
                'ranges' => [
                    ['parameter' => 'WBC', 'unit' => '10^3/uL', 'gender' => 'all', 'low' => 4.5, 'high' => 11.0, 'crit_low' => 2.0, 'crit_high' => 30.0],
                    ['parameter' => 'RBC', 'unit' => '10^6/uL', 'gender' => 'male', 'low' => 4.5, 'high' => 5.9],
                    ['parameter' => 'RBC', 'unit' => '10^6/uL', 'gender' => 'female', 'low' => 4.0, 'high' => 5.2],
                    ['parameter' => 'Hemoglobin', 'unit' => 'g/dL', 'gender' => 'male', 'low' => 13.5, 'high' => 17.5, 'crit_low' => 7.0, 'crit_high' => 20.0],
                    ['parameter' => 'Hemoglobin', 'unit' => 'g/dL', 'gender' => 'female', 'low' => 12.0, 'high' => 15.5, 'crit_low' => 7.0, 'crit_high' => 20.0],
                    ['parameter' => 'Hematocrit', 'unit' => '%', 'gender' => 'male', 'low' => 41.0, 'high' => 50.0],
                    ['parameter' => 'Hematocrit', 'unit' => '%', 'gender' => 'female', 'low' => 36.0, 'high' => 48.0],
                    ['parameter' => 'Platelets', 'unit' => '10^3/uL', 'gender' => 'all', 'low' => 150.0, 'high' => 450.0, 'crit_low' => 50.0, 'crit_high' => 1000.0],
                ]
            ],
            [
                'code' => 'BMP',
                'name' => 'Basic Metabolic Panel (BMP)',
                'category' => 'Clinical Chemistry',
                'specimen_type' => 'Serum',
                'container_type' => 'Gold top SST tube',
                'turnaround_time_hours' => 3,
                'price' => 45.00,
                'ranges' => [
                    ['parameter' => 'Glucose', 'unit' => 'mg/dL', 'gender' => 'all', 'low' => 70.0, 'high' => 99.0, 'crit_low' => 50.0, 'crit_high' => 400.0],
                    ['parameter' => 'BUN', 'unit' => 'mg/dL', 'gender' => 'all', 'low' => 7.0, 'high' => 20.0],
                    ['parameter' => 'Creatinine', 'unit' => 'mg/dL', 'gender' => 'male', 'low' => 0.7, 'high' => 1.3, 'crit_high' => 4.0],
                    ['parameter' => 'Creatinine', 'unit' => 'mg/dL', 'gender' => 'female', 'low' => 0.5, 'high' => 1.1, 'crit_high' => 4.0],
                    ['parameter' => 'Sodium', 'unit' => 'mEq/L', 'gender' => 'all', 'low' => 135.0, 'high' => 145.0, 'crit_low' => 120.0, 'crit_high' => 160.0],
                    ['parameter' => 'Potassium', 'unit' => 'mEq/L', 'gender' => 'all', 'low' => 3.5, 'high' => 5.0, 'crit_low' => 2.8, 'crit_high' => 6.2],
                    ['parameter' => 'Chloride', 'unit' => 'mEq/L', 'gender' => 'all', 'low' => 96.0, 'high' => 106.0],
                    ['parameter' => 'Carbon Dioxide', 'unit' => 'mEq/L', 'gender' => 'all', 'low' => 23.0, 'high' => 29.0],
                ]
            ],
            [
                'code' => 'LFT',
                'name' => 'Liver Function Tests (Hepatic Function Panel)',
                'category' => 'Clinical Chemistry',
                'specimen_type' => 'Serum',
                'container_type' => 'Gold top SST tube',
                'turnaround_time_hours' => 3,
                'price' => 50.00,
                'ranges' => [
                    ['parameter' => 'ALT', 'unit' => 'U/L', 'gender' => 'all', 'low' => 7.0, 'high' => 56.0],
                    ['parameter' => 'AST', 'unit' => 'U/L', 'gender' => 'all', 'low' => 10.0, 'high' => 40.0],
                    ['parameter' => 'Total Bilirubin', 'unit' => 'mg/dL', 'gender' => 'all', 'low' => 0.1, 'high' => 1.2],
                    ['parameter' => 'Alkaline Phosphatase', 'unit' => 'U/L', 'gender' => 'all', 'low' => 44.0, 'high' => 147.0],
                    ['parameter' => 'Albumin', 'unit' => 'g/dL', 'gender' => 'all', 'low' => 3.4, 'high' => 5.4],
                ]
            ],
            [
                'code' => 'LIPID',
                'name' => 'Lipid Panel',
                'category' => 'Clinical Chemistry',
                'specimen_type' => 'Serum',
                'container_type' => 'Gold top SST tube',
                'turnaround_time_hours' => 4,
                'price' => 40.00,
                'ranges' => [
                    ['parameter' => 'Total Cholesterol', 'unit' => 'mg/dL', 'gender' => 'all', 'low' => 100.0, 'high' => 199.0],
                    ['parameter' => 'Triglycerides', 'unit' => 'mg/dL', 'gender' => 'all', 'low' => 50.0, 'high' => 149.0],
                    ['parameter' => 'HDL Cholesterol', 'unit' => 'mg/dL', 'gender' => 'male', 'low' => 40.0, 'high' => 90.0],
                    ['parameter' => 'HDL Cholesterol', 'unit' => 'mg/dL', 'gender' => 'female', 'low' => 50.0, 'high' => 100.0],
                    ['parameter' => 'LDL Cholesterol', 'unit' => 'mg/dL', 'gender' => 'all', 'low' => 50.0, 'high' => 99.0],
                ]
            ],
            [
                'code' => 'HBA1C',
                'name' => 'Hemoglobin A1c (Glycated Hemoglobin)',
                'category' => 'Endocrinology',
                'specimen_type' => 'Whole Blood (EDTA)',
                'container_type' => 'Lavender top tube',
                'turnaround_time_hours' => 2,
                'price' => 40.00,
                'ranges' => [
                    ['parameter' => 'HbA1c', 'unit' => '%', 'gender' => 'all', 'low' => 4.0, 'high' => 5.6, 'crit_high' => 10.0],
                ]
            ],
            [
                'code' => 'URINE_ROUTINE',
                'name' => 'Urinalysis Routine with Microscopy',
                'category' => 'Clinical Pathology',
                'specimen_type' => 'Urine',
                'container_type' => 'Sterile urine cup',
                'turnaround_time_hours' => 1,
                'price' => 20.00,
                'ranges' => [
                    ['parameter' => 'pH', 'unit' => '', 'gender' => 'all', 'low' => 4.5, 'high' => 8.0],
                    ['parameter' => 'Specific Gravity', 'unit' => '', 'gender' => 'all', 'low' => 1.005, 'high' => 1.030],
                ]
            ]
        ];

        foreach ($testsCatalog as $tData) {
            $test = LabTest::firstOrCreate(
                ['code' => $tData['code']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $tData['name'],
                    'code' => $tData['code'],
                    'category' => $tData['category'],
                    'specimen_type' => $tData['specimen_type'],
                    'container_type' => $tData['container_type'],
                    'turn_around_time_minutes' => ($tData['turnaround_time_hours'] ?? 2) * 60,
                    'price_cents' => (int)(($tData['price'] ?? 0) * 100),
                    'is_active' => true,
                ]
            );

            // Seed reference ranges
            foreach ($tData['ranges'] as $rData) {
                ReferenceRange::firstOrCreate(
                    [
                        'lab_test_id' => $test->id,
                        'parameter_name' => $rData['parameter'],
                        'gender' => $rData['gender'],
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'lab_test_id' => $test->id,
                        'parameter_name' => $rData['parameter'],
                        'unit' => $rData['unit'],
                        'gender' => $rData['gender'],
                        'normal_low' => $rData['low'] ?? null,
                        'normal_high' => $rData['high'] ?? null,
                        'critical_low' => $rData['crit_low'] ?? null,
                        'critical_high' => $rData['crit_high'] ?? null,
                    ]
                );
            }
        }

        // 2. Link sample order to sample specimen & result
        $labOrder = LabOrder::first();
        $patient = Patient::first();
        $user = User::first();

        if ($labOrder && $patient && $user) {
            $barcodeService = app(BarcodeService::class);
            $hba1cTest = LabTest::where('code', 'HBA1C')->first();

            // Create sample
            $sample = LabSample::firstOrCreate(
                ['lab_order_id' => $labOrder->id],
                [
                    'id' => (string) Str::uuid(),
                    'barcode' => 'SMP-' . date('Y') . '-00000101',
                    'organization_id' => $branch->organization_id,
                    'branch_id' => $branch->id,
                    'lab_order_id' => $labOrder->id,
                    'patient_id' => $patient->id,
                    'sample_type' => 'Whole Blood (EDTA)',
                    'container_type' => 'Lavender top tube',
                    'status' => 'analyzed',
                    'collected_at' => now()->subDays(1)->subHours(3),
                    'collected_by' => $user->id,
                    'received_at' => now()->subDays(1)->subHours(2),
                    'received_by' => $user->id,
                ]
            );

            // Create result
            $result = LabResult::firstOrCreate(
                ['lab_order_id' => $labOrder->id],
                [
                    'id' => (string) Str::uuid(),
                    'report_number' => 'REP-2026-000101',
                    'organization_id' => $branch->organization_id,
                    'branch_id' => $branch->id,
                    'lab_order_id' => $labOrder->id,
                    'lab_test_id' => $hba1cTest?->id,
                    'lab_sample_id' => $sample->id,
                    'patient_id' => $patient->id,
                    'technician_id' => $user->id,
                    'pathologist_id' => $user->id,
                    'status' => 'signed',
                    'has_abnormal_values' => true,
                    'has_critical_values' => false,
                    'doctor_notified_at' => now()->subDays(1)->subHours(1),
                    'doctor_notified_channel' => 'system_alert',
                    'clinical_remarks' => 'Significantly elevated HbA1c indicative of poor glycemic control.',
                    'methodology' => 'High-Performance Liquid Chromatography (HPLC)',
                    'digital_signature_hash' => 'SIG-SHA256-' . substr(hash('sha256', 'REP-2026-000101-seed'), 0, 40),
                    'signed_at' => now()->subDays(1)->subHours(1),
                    'version' => 1,
                    'is_amended' => false,
                ]
            );

            // Create Result Item
            $refRange = ReferenceRange::where('parameter_name', 'HbA1c')->first();
            LabResultItem::firstOrCreate(
                ['lab_result_id' => $result->id, 'parameter_name' => 'HbA1c'],
                [
                    'reference_range_id' => $refRange?->id,
                    'measured_value' => '8.9',
                    'numeric_value' => 8.9,
                    'unit' => '%',
                    'reference_low' => 4.0,
                    'reference_high' => 5.6,
                    'flag' => 'high',
                ]
            );
        }
    }
}
