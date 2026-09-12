<?php

namespace Database\Seeders;

use App\Domain\Clinical\Models\Diagnosis;
use App\Domain\Clinical\Models\EhrRecord;
use App\Domain\Clinical\Models\Icd10Code;
use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Clinical\Models\Prescription;
use App\Domain\Clinical\Models\PrescriptionItem;
use App\Domain\Clinical\Models\RadiologyOrder;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClinicalModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Comprehensive ICD-10 Master Catalog
        $icdCodes = [
            // Endocrine & Metabolic
            ['code' => 'E11.9', 'description' => 'Type 2 diabetes mellitus without complications', 'category' => 'Endocrine, nutritional and metabolic diseases', 'chapter' => 'IV'],
            ['code' => 'E11.65', 'description' => 'Type 2 diabetes mellitus with hyperglycemia', 'category' => 'Endocrine, nutritional and metabolic diseases', 'chapter' => 'IV'],
            ['code' => 'E10.9', 'description' => 'Type 1 diabetes mellitus without complications', 'category' => 'Endocrine, nutritional and metabolic diseases', 'chapter' => 'IV'],
            ['code' => 'E03.9', 'description' => 'Hypothyroidism, unspecified', 'category' => 'Endocrine, nutritional and metabolic diseases', 'chapter' => 'IV'],
            ['code' => 'E66.9', 'description' => 'Obesity, unspecified', 'category' => 'Endocrine, nutritional and metabolic diseases', 'chapter' => 'IV'],
            ['code' => 'E78.5', 'description' => 'Hyperlipidemia, unspecified', 'category' => 'Endocrine, nutritional and metabolic diseases', 'chapter' => 'IV'],
            
            // Circulatory & Cardiovascular
            ['code' => 'I10', 'description' => 'Essential (primary) hypertension', 'category' => 'Diseases of the circulatory system', 'chapter' => 'IX'],
            ['code' => 'I20.9', 'description' => 'Angina pectoris, unspecified', 'category' => 'Diseases of the circulatory system', 'chapter' => 'IX'],
            ['code' => 'I21.9', 'description' => 'Acute myocardial infarction, unspecified', 'category' => 'Diseases of the circulatory system', 'chapter' => 'IX'],
            ['code' => 'I25.10', 'description' => 'Atherosclerotic heart disease of native coronary artery without angina', 'category' => 'Diseases of the circulatory system', 'chapter' => 'IX'],
            ['code' => 'I48.91', 'description' => 'Unspecified atrial fibrillation', 'category' => 'Diseases of the circulatory system', 'chapter' => 'IX'],
            ['code' => 'I50.9', 'description' => 'Heart failure, unspecified', 'category' => 'Diseases of the circulatory system', 'chapter' => 'IX'],

            // Respiratory
            ['code' => 'J06.9', 'description' => 'Acute upper respiratory infection, unspecified', 'category' => 'Diseases of the respiratory system', 'chapter' => 'X'],
            ['code' => 'J18.9', 'description' => 'Pneumonia, unspecified organism', 'category' => 'Diseases of the respiratory system', 'chapter' => 'X'],
            ['code' => 'J20.9', 'description' => 'Acute bronchitis, unspecified', 'category' => 'Diseases of the respiratory system', 'chapter' => 'X'],
            ['code' => 'J44.9', 'description' => 'Chronic obstructive pulmonary disease, unspecified', 'category' => 'Diseases of the respiratory system', 'chapter' => 'X'],
            ['code' => 'J45.909', 'description' => 'Unspecified asthma, uncomplicated', 'category' => 'Diseases of the respiratory system', 'chapter' => 'X'],
            ['code' => 'J02.9', 'description' => 'Acute pharyngitis, unspecified', 'category' => 'Diseases of the respiratory system', 'chapter' => 'X'],

            // Digestive / Gastrointestinal
            ['code' => 'K21.9', 'description' => 'Gastro-esophageal reflux disease without esophagitis', 'category' => 'Diseases of the digestive system', 'chapter' => 'XI'],
            ['code' => 'K29.70', 'description' => 'Gastritis, unspecified, without bleeding', 'category' => 'Diseases of the digestive system', 'chapter' => 'XI'],
            ['code' => 'K80.20', 'description' => 'Calculus of gallbladder without cholecystitis', 'category' => 'Diseases of the digestive system', 'chapter' => 'XI'],
            ['code' => 'K52.9', 'description' => 'Noninfective gastroenteritis and colitis, unspecified', 'category' => 'Diseases of the digestive system', 'chapter' => 'XI'],

            // Genitourinary
            ['code' => 'N39.0', 'description' => 'Urinary tract infection, site not specified', 'category' => 'Diseases of the genitourinary system', 'chapter' => 'XIV'],
            ['code' => 'N18.9', 'description' => 'Chronic kidney disease, unspecified', 'category' => 'Diseases of the genitourinary system', 'chapter' => 'XIV'],
            ['code' => 'N20.0', 'description' => 'Calculus of kidney', 'category' => 'Diseases of the genitourinary system', 'chapter' => 'XIV'],

            // Musculoskeletal
            ['code' => 'M54.50', 'description' => 'Low back pain, unspecified', 'category' => 'Diseases of the musculoskeletal system', 'chapter' => 'XIII'],
            ['code' => 'M17.9', 'description' => 'Osteoarthritis of knee, unspecified', 'category' => 'Diseases of the musculoskeletal system', 'chapter' => 'XIII'],
            ['code' => 'M25.50', 'description' => 'Pain in unspecified joint', 'category' => 'Diseases of the musculoskeletal system', 'chapter' => 'XIII'],

            // Symptoms & Signs
            ['code' => 'R07.9', 'description' => 'Chest pain, unspecified', 'category' => 'Symptoms, signs and abnormal clinical findings', 'chapter' => 'XVIII'],
            ['code' => 'R10.9', 'description' => 'Abdominal pain, unspecified', 'category' => 'Symptoms, signs and abnormal clinical findings', 'chapter' => 'XVIII'],
            ['code' => 'R50.9', 'description' => 'Fever, unspecified', 'category' => 'Symptoms, signs and abnormal clinical findings', 'chapter' => 'XVIII'],
            ['code' => 'R51', 'description' => 'Headache', 'category' => 'Symptoms, signs and abnormal clinical findings', 'chapter' => 'XVIII'],
            ['code' => 'R05', 'description' => 'Cough', 'category' => 'Symptoms, signs and abnormal clinical findings', 'chapter' => 'XVIII'],
            ['code' => 'R06.02', 'description' => 'Shortness of breath', 'category' => 'Symptoms, signs and abnormal clinical findings', 'chapter' => 'XVIII'],
            ['code' => 'R42', 'description' => 'Dizziness and giddiness', 'category' => 'Symptoms, signs and abnormal clinical findings', 'chapter' => 'XVIII'],

            // Infectious Diseases
            ['code' => 'A09', 'description' => 'Infectious gastroenteritis and colitis, unspecified', 'category' => 'Certain infectious and parasitic diseases', 'chapter' => 'I'],
            ['code' => 'B34.9', 'description' => 'Viral infection, unspecified', 'category' => 'Certain infectious and parasitic diseases', 'chapter' => 'I'],
            ['code' => 'A41.9', 'description' => 'Sepsis, unspecified organism', 'category' => 'Certain infectious and parasitic diseases', 'chapter' => 'I'],

            // Health Status & Factors
            ['code' => 'Z00.00', 'description' => 'Encounter for general adult medical examination without abnormal findings', 'category' => 'Factors influencing health status and contact with health services', 'chapter' => 'XXI'],
            ['code' => 'Z23', 'description' => 'Encounter for immunization', 'category' => 'Factors influencing health status and contact with health services', 'chapter' => 'XXI'],
            ['code' => 'Z88.0', 'description' => 'Allergy status to penicillin', 'category' => 'Factors influencing health status and contact with health services', 'chapter' => 'XXI'],
        ];

        foreach ($icdCodes as $codeData) {
            Icd10Code::firstOrCreate(
                ['code' => $codeData['code']],
                [
                    'id' => (string) Str::uuid(),
                    'description' => $codeData['description'],
                    'category' => $codeData['category'],
                    'chapter' => $codeData['chapter'],
                    'is_billable' => true,
                    'is_active' => true,
                ]
            );
        }

        // 2. Fetch or mock Branch, Doctor, Patient
        $branch = Branch::first();
        $doctor = User::where('email', 'like', '%doctor%')
            ->orWhere('name', 'like', '%Dr.%')
            ->first() ?? User::first();
        $patient = Patient::first();

        if (!$branch || !$doctor || !$patient) {
            return;
        }

        // 3. Create Sample EHR Record
        $ehr = EhrRecord::firstOrCreate(
            ['patient_id' => $patient->id, 'title' => 'Initial Outpatient Internal Medicine Consult'],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'patient_id' => $patient->id,
                'encounter_type' => 'opd_appointment',
                'author_id' => $doctor->id,
                'record_type' => 'consultation_note',
                'category' => 'internal_medicine',
                'title' => 'Initial Outpatient Internal Medicine Consult',
                'clinical_notes' => [
                    'chief_complaint' => 'Persistent polyuria, polydipsia, and fatigue for 3 weeks.',
                    'subjective' => 'Patient reports increased thirst and blurred vision. No shortness of breath or chest pain.',
                    'objective' => 'Alert, oriented x 3. Heart regular rhythm, lungs clear bilaterally. Abdomen soft, non-tender.',
                    'assessment' => 'Uncontrolled Type 2 Diabetes Mellitus with Essential Hypertension.',
                    'plan' => 'Initiate Metformin 500mg BID and Lisinopril 10mg daily. Order HbA1c, fasting lipid panel, and renal function. Schedule follow up in 2 weeks.',
                ],
                'vitals' => [
                    'bp_systolic' => 138,
                    'bp_diastolic' => 86,
                    'heart_rate' => 78,
                    'respiratory_rate' => 16,
                    'temperature_c' => 36.8,
                    'spo2' => 99,
                    'bmi' => 28.4,
                ],
                'status' => 'finalized',
                'version' => 1,
                'is_amended' => false,
                'finalized_at' => now()->subDays(2),
                'finalized_by' => $doctor->id,
            ]
        );

        // 4. Create Diagnoses
        Diagnosis::firstOrCreate(
            ['patient_id' => $patient->id, 'icd10_code' => 'E11.9'],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'patient_id' => $patient->id,
                'ehr_record_id' => $ehr->id,
                'doctor_id' => $doctor->id,
                'icd10_code' => 'E11.9',
                'icd10_title' => 'Type 2 diabetes mellitus without complications',
                'type' => 'primary',
                'severity' => 'moderate',
                'clinical_status' => 'active',
                'verification_status' => 'confirmed',
                'onset_date' => now()->subMonths(1)->toDateString(),
            ]
        );

        Diagnosis::firstOrCreate(
            ['patient_id' => $patient->id, 'icd10_code' => 'I10'],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'patient_id' => $patient->id,
                'ehr_record_id' => $ehr->id,
                'doctor_id' => $doctor->id,
                'icd10_code' => 'I10',
                'icd10_title' => 'Essential (primary) hypertension',
                'type' => 'secondary',
                'severity' => 'mild',
                'clinical_status' => 'active',
                'verification_status' => 'confirmed',
                'onset_date' => now()->subYears(1)->toDateString(),
            ]
        );

        // 5. Create Sample Prescription
        $prescription = Prescription::firstOrCreate(
            ['prescription_number' => 'RX-2026-INIT01'],
            [
                'id' => (string) Str::uuid(),
                'prescription_number' => 'RX-2026-INIT01',
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'patient_id' => $patient->id,
                'ehr_record_id' => $ehr->id,
                'doctor_id' => $doctor->id,
                'status' => 'finalized',
                'has_safety_warnings' => false,
                'safety_alerts' => [],
                'prescribed_at' => now()->subDays(2),
                'finalized_at' => now()->subDays(2),
            ]
        );

        PrescriptionItem::firstOrCreate(
            ['prescription_id' => $prescription->id, 'medication_name' => 'Metformin'],
            [
                'id' => (string) Str::uuid(),
                'prescription_id' => $prescription->id,
                'medication_name' => 'Metformin',
                'generic_name' => 'Metformin Hydrochloride',
                'form' => 'tablet',
                'dosage' => '500 mg',
                'route' => 'oral',
                'frequency' => 'BID (Twice daily with meals)',
                'duration_days' => 30,
                'quantity' => 60,
                'instructions' => 'Take with breakfast and dinner',
                'status' => 'pending',
            ]
        );

        // 6. Create Lab Order
        LabOrder::firstOrCreate(
            ['order_number' => 'LAB-2026-00101'],
            [
                'id' => (string) Str::uuid(),
                'order_number' => 'LAB-2026-00101',
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'patient_id' => $patient->id,
                'ehr_record_id' => $ehr->id,
                'ordering_doctor_id' => $doctor->id,
                'test_type' => 'Hemoglobin A1c (HbA1c)',
                'test_code' => '4548-4',
                'priority' => 'routine',
                'clinical_indication' => 'Suspected new onset T2DM evaluation',
                'status' => 'completed',
                'ordered_at' => now()->subDays(2),
                'completed_at' => now()->subDays(1),
                'results_summary' => 'HbA1c elevated at 8.9% (Normal: < 5.7%). Consistent with uncontrolled diabetes.',
                'structured_results' => [
                    ['parameter' => 'HbA1c', 'value' => '8.9', 'unit' => '%', 'reference_range' => '< 5.7', 'flag' => 'HIGH']
                ],
                'abnormal_flags' => true,
            ]
        );

        // 7. Create Radiology Order
        RadiologyOrder::firstOrCreate(
            ['order_number' => 'RAD-2026-00101'],
            [
                'id' => (string) Str::uuid(),
                'order_number' => 'RAD-2026-00101',
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'patient_id' => $patient->id,
                'ehr_record_id' => $ehr->id,
                'ordering_doctor_id' => $doctor->id,
                'modality' => 'X-Ray',
                'body_part' => 'Chest',
                'procedure_name' => 'Chest X-Ray PA and Lateral',
                'priority' => 'routine',
                'clinical_indication' => 'Baseline cardiovascular and pulmonary evaluation',
                'status' => 'reported',
                'ordered_at' => now()->subDays(2),
                'performed_at' => now()->subDays(1),
                'reported_at' => now()->subDays(1),
                'findings' => 'Normal cardiac silhouette. No focal airspace consolidation, pneumothorax, or pleural effusion.',
                'impression' => 'Clear chest radiograph with no acute cardiopulmonary disease.',
            ]
        );
    }
}
