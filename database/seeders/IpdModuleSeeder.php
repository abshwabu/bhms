<?php

namespace Database\Seeders;

use App\Domain\IPD\Models\Admission;
use App\Domain\IPD\Models\Bed;
use App\Domain\IPD\Models\BedTransfer;
use App\Domain\IPD\Models\DischargeSummary;
use App\Domain\IPD\Models\MedicationAdministration;
use App\Domain\IPD\Models\VitalsLog;
use App\Domain\IPD\Models\Ward;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IpdModuleSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();
        $branch = Branch::first();

        if (!$organization || !$branch) {
            return;
        }

        // Staff members
        $doctor = User::where('email', 'doctor@hms.local')->first()
            ?? User::where('name', 'like', '%Dr.%')->first()
            ?? User::first();

        $nurse = User::where('email', 'nurse@hms.local')->first()
            ?? User::where('name', 'like', '%Clara%')->first()
            ?? User::first();

        $admin = User::where('email', 'admin@hms.local')->first()
            ?? User::first();

        // Patients
        $john = Patient::where('national_id', 'NAT-850615-101')->first() ?? Patient::first();
        $tommy = Patient::where('national_id', 'NAT-180920-808')->first() ?? Patient::skip(1)->first() ?? $john;
        $amina = Patient::where('national_id', 'NAT-921104-450')->first() ?? Patient::skip(2)->first() ?? $john;
        $trauma = Patient::where('first_name', 'Trauma Male #1')->first() ?? Patient::skip(3)->first() ?? $john;
        $solomon = Patient::where('national_id', 'NAT-790112-234')->first() ?? Patient::skip(4)->first() ?? $john;
        $bethlehem = Patient::where('national_id', 'NAT-950418-678')->first() ?? Patient::skip(5)->first() ?? $john;

        // ==============================================================
        // 1. Inpatient Wards
        // ==============================================================
        $wardsData = [
            [
                'code' => 'MMW',
                'name' => 'General Medical Inpatient Ward',
                'ward_type' => 'general',
                'floor_number' => '2nd Floor',
                'capacity' => 12,
                'gender_restriction' => 'all',
                'daily_rate' => 650.00,
            ],
            [
                'code' => 'SURG',
                'name' => 'General & Specialized Surgical Ward',
                'ward_type' => 'general',
                'floor_number' => '3rd Floor',
                'capacity' => 10,
                'gender_restriction' => 'all',
                'daily_rate' => 850.00,
            ],
            [
                'code' => 'ICU-MAIN',
                'name' => 'Intensive Care Unit (ICU / CCU)',
                'ward_type' => 'icu',
                'floor_number' => '1st Floor',
                'capacity' => 6,
                'gender_restriction' => 'all',
                'daily_rate' => 2200.00,
            ],
            [
                'code' => 'PED-IN',
                'name' => 'Pediatric Inpatient Unit',
                'ward_type' => 'pediatric',
                'floor_number' => '2nd Floor',
                'capacity' => 8,
                'gender_restriction' => 'all',
                'daily_rate' => 550.00,
            ],
            [
                'code' => 'MAT-IN',
                'name' => 'Maternity & Postpartum Ward',
                'ward_type' => 'maternity',
                'floor_number' => '4th Floor',
                'capacity' => 8,
                'gender_restriction' => 'female_only',
                'daily_rate' => 750.00,
            ],
        ];

        $wards = [];
        foreach ($wardsData as $wData) {
            $wards[$wData['code']] = Ward::firstOrCreate(
                ['branch_id' => $branch->id, 'code' => $wData['code']],
                array_merge($wData, [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'is_active' => true,
                ])
            );
        }

        // ==============================================================
        // 2. Beds across Wards
        // ==============================================================
        $bedsConfig = [
            // General Medical Ward
            ['ward_code' => 'MMW', 'bed_number' => 'GEN-101', 'bed_type' => 'standard', 'status' => 'occupied', 'features' => ['oxygen' => true, 'call_button' => true]],
            ['ward_code' => 'MMW', 'bed_number' => 'GEN-102', 'bed_type' => 'standard', 'status' => 'occupied', 'features' => ['oxygen' => true, 'call_button' => true]],
            ['ward_code' => 'MMW', 'bed_number' => 'GEN-103', 'bed_type' => 'standard', 'status' => 'available', 'features' => ['oxygen' => true, 'call_button' => true]],
            ['ward_code' => 'MMW', 'bed_number' => 'GEN-104', 'bed_type' => 'electric', 'status' => 'available', 'features' => ['oxygen' => true, 'cardiac_monitor' => true]],
            ['ward_code' => 'MMW', 'bed_number' => 'GEN-105', 'bed_type' => 'standard', 'status' => 'cleaning', 'features' => ['oxygen' => true]],
            ['ward_code' => 'MMW', 'bed_number' => 'GEN-106', 'bed_type' => 'bariatric', 'status' => 'maintenance', 'features' => ['heavy_duty_frame' => true]],

            // Surgical Ward
            ['ward_code' => 'SURG', 'bed_number' => 'SURG-201', 'bed_type' => 'electric', 'status' => 'occupied', 'features' => ['oxygen' => true, 'suction' => true, 'cardiac_monitor' => true]],
            ['ward_code' => 'SURG', 'bed_number' => 'SURG-202', 'bed_type' => 'electric', 'status' => 'available', 'features' => ['oxygen' => true, 'suction' => true]],
            ['ward_code' => 'SURG', 'bed_number' => 'SURG-203', 'bed_type' => 'standard', 'status' => 'available', 'features' => ['oxygen' => true]],
            ['ward_code' => 'SURG', 'bed_number' => 'SURG-204', 'bed_type' => 'electric', 'status' => 'available', 'features' => ['oxygen' => true, 'iv_pole' => true]],

            // ICU Ward
            ['ward_code' => 'ICU-MAIN', 'bed_number' => 'ICU-01', 'bed_type' => 'icu_ventilator', 'status' => 'occupied', 'features' => ['ventilator' => true, 'cardiac_monitor' => true, 'infusion_pump' => true, 'wall_oxygen' => true, 'suction' => true]],
            ['ward_code' => 'ICU-MAIN', 'bed_number' => 'ICU-02', 'bed_type' => 'icu_ventilator', 'status' => 'available', 'features' => ['ventilator' => true, 'cardiac_monitor' => true, 'wall_oxygen' => true, 'suction' => true]],
            ['ward_code' => 'ICU-MAIN', 'bed_number' => 'ICU-03', 'bed_type' => 'icu_ventilator', 'status' => 'available', 'features' => ['ventilator' => true, 'cardiac_monitor' => true, 'wall_oxygen' => true, 'suction' => true]],

            // Pediatric Ward
            ['ward_code' => 'PED-IN', 'bed_number' => 'PED-301', 'bed_type' => 'crib', 'status' => 'occupied', 'features' => ['pediatric_pulse_oximeter' => true, 'wall_oxygen' => true]],
            ['ward_code' => 'PED-IN', 'bed_number' => 'PED-302', 'bed_type' => 'standard', 'status' => 'available', 'features' => ['wall_oxygen' => true]],
            ['ward_code' => 'PED-IN', 'bed_number' => 'PED-303', 'bed_type' => 'crib', 'status' => 'available', 'features' => ['wall_oxygen' => true]],

            // Maternity Ward
            ['ward_code' => 'MAT-IN', 'bed_number' => 'MAT-401', 'bed_type' => 'electric', 'status' => 'occupied', 'features' => ['fetal_doppler' => true, 'infant_bassinet' => true, 'wall_oxygen' => true]],
            ['ward_code' => 'MAT-IN', 'bed_number' => 'MAT-402', 'bed_type' => 'standard', 'status' => 'available', 'features' => ['infant_bassinet' => true, 'wall_oxygen' => true]],
            ['ward_code' => 'MAT-IN', 'bed_number' => 'MAT-403', 'bed_type' => 'standard', 'status' => 'available', 'features' => ['infant_bassinet' => true]],
        ];

        $beds = [];
        foreach ($bedsConfig as $bCfg) {
            $ward = $wards[$bCfg['ward_code']];
            $bed = Bed::firstOrCreate(
                ['ward_id' => $ward->id, 'bed_number' => $bCfg['bed_number']],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'branch_id' => $branch->id,
                    'bed_type' => $bCfg['bed_type'],
                    'status' => $bCfg['status'],
                    'features' => $bCfg['features'],
                    'is_active' => true,
                ]
            );
            $bed->update(['status' => $bCfg['status']]);
            $beds[$bCfg['bed_number']] = $bed;
        }

        // ==============================================================
        // 3. Active Inpatient Admissions (status = 'admitted')
        // ==============================================================
        $activeAdmissions = [
            [
                'admission_number' => 'ADM-2026-000001',
                'patient' => $john,
                'ward' => $wards['MMW'],
                'bed' => $beds['GEN-101'],
                'admission_type' => 'emergency',
                'admitted_at' => Carbon::now()->subDays(3)->setTime(14, 30),
                'admitting_diagnosis' => 'Hypertensive crisis with severe headache and nausea',
                'primary_diagnosis' => 'Essential (primary) hypertension, uncontrolled',
                'secondary_diagnoses' => ['Type 2 diabetes mellitus'],
                'chief_complaint' => 'Severe throbbing frontal headache for 12 hours with BP 195/110 mmHg.',
                'initial_vitals' => ['bp_systolic' => 195, 'bp_diastolic' => 110, 'heart_rate' => 92, 'temperature_c' => 36.8, 'spo2' => 98],
                'notes' => 'Admitted for acute blood pressure control, cardiac enzyme workup, and serial neurological observations.',
            ],
            [
                'admission_number' => 'ADM-2026-000002',
                'patient' => $trauma,
                'ward' => $wards['ICU-MAIN'],
                'bed' => $beds['ICU-01'],
                'admission_type' => 'emergency',
                'admitted_at' => Carbon::now()->subDays(2)->setTime(2, 15),
                'admitting_diagnosis' => 'High-velocity motor vehicle trauma with closed head injury and thoracic contusion',
                'primary_diagnosis' => 'Traumatic brain injury with acute intracranial hematoma',
                'secondary_diagnoses' => ['Pulmonary contusion, bilateral', 'Left clavicle fracture'],
                'procedures_performed' => ['Endotracheal intubation', 'Arterial line placement', 'Central venous catheterization'],
                'chief_complaint' => 'Unconscious polytrauma victim brought by EMS.',
                'initial_vitals' => ['bp_systolic' => 105, 'bp_diastolic' => 65, 'heart_rate' => 128, 'temperature_c' => 37.6, 'spo2' => 92, 'gcs' => 7],
                'notes' => 'Mechanical ventilation in progress. ICP monitoring via subdural catheter. Sedated on fentanyl/propofol.',
            ],
            [
                'admission_number' => 'ADM-2026-000003',
                'patient' => $tommy,
                'ward' => $wards['PED-IN'],
                'bed' => $beds['PED-301'],
                'admission_type' => 'emergency',
                'admitted_at' => Carbon::now()->subDays(1)->setTime(18, 45),
                'admitting_diagnosis' => 'Acute severe exacerbation of bronchial asthma with tachypnea',
                'primary_diagnosis' => 'Extrinsic asthma with acute exacerbation',
                'secondary_diagnoses' => ['Environmental allergy'],
                'procedures_performed' => ['Continuous nebulization therapy'],
                'chief_complaint' => 'Wheezing, intercostal retractions, and cough for 6 hours.',
                'initial_vitals' => ['bp_systolic' => 98, 'bp_diastolic' => 62, 'heart_rate' => 122, 'respiratory_rate' => 38, 'temperature_c' => 37.8, 'spo2' => 91],
                'notes' => 'Admitted for continuous back-to-back salbutamol neb, IV dexamethasone, and supplemental O2 via nasal cannula.',
            ],
            [
                'admission_number' => 'ADM-2026-000004',
                'patient' => $amina,
                'ward' => $wards['MAT-IN'],
                'bed' => $beds['MAT-401'],
                'admission_type' => 'maternity',
                'admitted_at' => Carbon::now()->subDays(2)->setTime(8, 0),
                'admitting_diagnosis' => 'Full-term active phase labor with cervical dilation 6cm',
                'primary_diagnosis' => 'Single spontaneous vertex delivery without tear, postpartum day 2',
                'secondary_diagnoses' => ['Latex allergy'],
                'procedures_performed' => ['Spontaneous vertex vaginal delivery', 'Active management of third stage labor'],
                'chief_complaint' => 'Regular uterine contractions 3 minutes apart.',
                'initial_vitals' => ['bp_systolic' => 116, 'bp_diastolic' => 74, 'heart_rate' => 76, 'temperature_c' => 36.7, 'spo2' => 99],
                'notes' => 'Delivered healthy female infant (3.4kg, APGAR 9/10). Mother and baby bonding well, rooming in. Discharge scheduled tomorrow.',
            ],
            [
                'admission_number' => 'ADM-2026-000005',
                'patient' => $solomon,
                'ward' => $wards['MMW'],
                'bed' => $beds['GEN-102'],
                'admission_type' => 'elective',
                'admitted_at' => Carbon::now()->subDays(4)->setTime(10, 0),
                'admitting_diagnosis' => 'Community-acquired pneumonia with fever, cough, and right lower lobe consolidation',
                'primary_diagnosis' => 'Bacterial pneumonia, unspecified organism',
                'secondary_diagnoses' => ['Type 2 diabetes mellitus'],
                'chief_complaint' => 'High-grade fever, productive rusty sputum, and right-sided pleuritic chest pain.',
                'initial_vitals' => ['bp_systolic' => 128, 'bp_diastolic' => 82, 'heart_rate' => 94, 'respiratory_rate' => 24, 'temperature_c' => 39.2, 'spo2' => 93],
                'notes' => 'IV Ceftriaxone and Azithromycin therapy. Sputum culture pending. Afebrile for the past 24 hours.',
            ],
            [
                'admission_number' => 'ADM-2026-000006',
                'patient' => $bethlehem,
                'ward' => $wards['SURG'],
                'bed' => $beds['SURG-201'],
                'admission_type' => 'elective',
                'admitted_at' => Carbon::now()->subDays(3)->setTime(11, 30),
                'admitting_diagnosis' => 'Acute phlegmonous appendicitis',
                'primary_diagnosis' => 'Acute appendicitis with localized peritonitis',
                'procedures_performed' => ['Laparoscopic appendectomy', 'Peritoneal lavage'],
                'chief_complaint' => 'Periumbilical pain migrating to right lower quadrant with nausea.',
                'initial_vitals' => ['bp_systolic' => 112, 'bp_diastolic' => 70, 'heart_rate' => 88, 'temperature_c' => 38.3, 'spo2' => 99],
                'notes' => 'Post-op Day 2. Tolerating soft diet, surgical port incisions clean and dry. Ambulated independently.',
            ],
        ];

        $admissions = [];
        foreach ($activeAdmissions as $admData) {
            $adm = Admission::firstOrCreate(
                ['admission_number' => $admData['admission_number']],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'branch_id' => $branch->id,
                    'patient_id' => $admData['patient']->id,
                    'ward_id' => $admData['ward']->id,
                    'bed_id' => $admData['bed']->id,
                    'admitting_doctor_id' => $doctor->id,
                    'attending_doctor_id' => $doctor->id,
                    'admitted_by' => $admin->id,
                    'admission_type' => $admData['admission_type'],
                    'status' => 'admitted',
                    'admitted_at' => $admData['admitted_at'],
                    'admitting_diagnosis' => $admData['admitting_diagnosis'],
                    'primary_diagnosis' => $admData['primary_diagnosis'],
                    'secondary_diagnoses' => $admData['secondary_diagnoses'] ?? [],
                    'procedures_performed' => $admData['procedures_performed'] ?? [],
                    'chief_complaint' => $admData['chief_complaint'],
                    'initial_vitals' => $admData['initial_vitals'],
                    'notes' => $admData['notes'],
                ]
            );
            $admissions[$admData['admission_number']] = $adm;
        }

        // ==============================================================
        // 4. Historical Discharged Admissions (For ALOS & census statistics)
        // ==============================================================
        $histAdmissions = [
            [
                'admission_number' => 'ADM-2026-HIST01',
                'patient' => $solomon,
                'ward' => $wards['MMW'],
                'bed' => $beds['GEN-103'], // Now available
                'admission_type' => 'emergency',
                'admitted_at' => Carbon::now()->subDays(15)->setTime(9, 0),
                'discharged_at' => Carbon::now()->subDays(10)->setTime(14, 0),
                'discharge_type' => 'routine',
                'admitting_diagnosis' => 'Acute viral gastroenteritis with severe dehydration and electrolyte imbalance',
                'primary_diagnosis' => 'Infectious gastroenteritis, fully resolved',
                'notes' => 'Successful rehydration with IV fluids. Electrolytes normalized. Discharged in stable condition.',
                'hospital_course' => 'Patient presented with 48h nausea, vomiting, and diarrhea. Resuscitated with 3L Normal Saline over 24h. Tolerated oral rehydration. Discharged home on oral probiotics.',
                'discharge_condition' => 'cured',
            ],
            [
                'admission_number' => 'ADM-2026-HIST02',
                'patient' => $amina,
                'ward' => $wards['SURG'],
                'bed' => $beds['SURG-202'], // Now available
                'admission_type' => 'elective',
                'admitted_at' => Carbon::now()->subDays(22)->setTime(8, 30),
                'discharged_at' => Carbon::now()->subDays(18)->setTime(11, 0),
                'discharge_type' => 'routine',
                'admitting_diagnosis' => 'Symptomatic cholelithiasis with recurrent biliary colic',
                'primary_diagnosis' => 'Calculus of gallbladder without cholecystitis',
                'procedures_performed' => ['Elective laparoscopic cholecystectomy'],
                'notes' => 'Uncomplicated laparoscopic cholecystectomy. Minimal blood loss. Uneventful recovery.',
                'hospital_course' => 'Elective 4-port laparoscopic cholecystectomy performed under general anesthesia. Intraoperative cholangiogram normal. Post-operative pain controlled. Diet advanced to regular low-fat diet. Surgical incisions healing primarily.',
                'discharge_condition' => 'stable',
            ],
        ];

        foreach ($histAdmissions as $hData) {
            $histAdm = Admission::firstOrCreate(
                ['admission_number' => $hData['admission_number']],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'branch_id' => $branch->id,
                    'patient_id' => $hData['patient']->id,
                    'ward_id' => $hData['ward']->id,
                    'bed_id' => $hData['bed']->id,
                    'admitting_doctor_id' => $doctor->id,
                    'attending_doctor_id' => $doctor->id,
                    'admitted_by' => $doctor->id,
                    'admission_type' => $hData['admission_type'],
                    'status' => 'discharged',
                    'admitted_at' => $hData['admitted_at'],
                    'discharged_at' => $hData['discharged_at'],
                    'discharged_by' => $doctor->id,
                    'discharge_type' => $hData['discharge_type'],
                    'admitting_diagnosis' => $hData['admitting_diagnosis'],
                    'primary_diagnosis' => $hData['primary_diagnosis'],
                    'procedures_performed' => $hData['procedures_performed'] ?? [],
                    'notes' => $hData['notes'],
                ]
            );

            // Finalized Discharge Summary
            DischargeSummary::firstOrCreate(
                ['admission_id' => $histAdm->id],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'branch_id' => $branch->id,
                    'patient_id' => $hData['patient']->id,
                    'discharging_doctor_id' => $doctor->id,
                    'admission_date' => $hData['admitted_at'],
                    'discharge_date' => $hData['discharged_at'],
                    'primary_diagnosis' => $hData['primary_diagnosis'],
                    'secondary_diagnoses' => [],
                    'procedures_performed' => $hData['procedures_performed'] ?? [],
                    'medications_at_discharge' => [
                        ['name' => 'Paracetamol 500mg', 'dosage' => '1 tablet QID PRN', 'duration' => '5 days'],
                        ['name' => 'Probiotics', 'dosage' => '1 capsule daily', 'duration' => '10 days'],
                    ],
                    'hospital_course_summary' => $hData['hospital_course'],
                    'discharge_condition' => $hData['discharge_condition'],
                    'discharge_type' => 'regular',
                    'follow_up_instructions' => 'Follow up in Outpatient Clinic if pain, fever, or vomiting occurs.',
                    'follow_up_date' => Carbon::now()->addDays(7)->toDateString(),
                    'is_finalized' => true,
                    'finalized_at' => $hData['discharged_at']->copy()->addMinutes(30),
                    'finalized_by' => $doctor->id,
                ]
            );
        }

        // ==============================================================
        // 5. Nursing Vitals Logs
        // ==============================================================
        $johnAdm = $admissions['ADM-2026-000001'];
        $vitalsRecords = [
            [
                'recorded_at' => Carbon::now()->subDays(3)->setTime(16, 0),
                'bp_systolic' => 195,
                'bp_diastolic' => 110,
                'heart_rate' => 92,
                'respiratory_rate' => 20,
                'temperature_c' => 36.8,
                'spo2' => 98.0,
                'pain_score' => 6,
                'consciousness_level' => 'alert',
                'nursing_notes' => 'Patient resting in bed. IV access established. IV Labetalol administered as ordered.',
            ],
            [
                'recorded_at' => Carbon::now()->subDays(2)->setTime(8, 0),
                'bp_systolic' => 162,
                'bp_diastolic' => 95,
                'heart_rate' => 80,
                'respiratory_rate' => 18,
                'temperature_c' => 36.7,
                'spo2' => 99.0,
                'pain_score' => 3,
                'consciousness_level' => 'alert',
                'nursing_notes' => 'Morning round. Headache improving. Oral antihypertensives administered with breakfast.',
            ],
            [
                'recorded_at' => Carbon::now()->subDays(1)->setTime(8, 0),
                'bp_systolic' => 138,
                'bp_diastolic' => 86,
                'heart_rate' => 74,
                'respiratory_rate' => 16,
                'temperature_c' => 36.6,
                'spo2' => 99.0,
                'pain_score' => 1,
                'consciousness_level' => 'alert',
                'nursing_notes' => 'Vitals stable. No dizziness upon standing. Ambulated around ward corridors comfortably.',
            ],
            [
                'recorded_at' => Carbon::now()->setTime(6, 30),
                'bp_systolic' => 126,
                'bp_diastolic' => 80,
                'heart_rate' => 72,
                'respiratory_rate' => 16,
                'temperature_c' => 36.6,
                'spo2' => 99.0,
                'pain_score' => 0,
                'consciousness_level' => 'alert',
                'nursing_notes' => 'Morning vitals optimal. Patient feels energetic, pleasant, and eager for discharge review.',
            ],
        ];

        foreach ($vitalsRecords as $vRecord) {
            VitalsLog::firstOrCreate(
                [
                    'admission_id' => $johnAdm->id,
                    'recorded_at' => $vRecord['recorded_at'],
                ],
                array_merge($vRecord, [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'branch_id' => $branch->id,
                    'patient_id' => $johnAdm->patient_id,
                    'recorded_by' => $nurse->id,
                ])
            );
        }

        // ICU Vitals for Trauma Male #1
        $traumaAdm = $admissions['ADM-2026-000002'];
        $icuVitals = [
            [
                'recorded_at' => Carbon::now()->subDays(1)->setTime(12, 0),
                'bp_systolic' => 112,
                'bp_diastolic' => 70,
                'heart_rate' => 118,
                'respiratory_rate' => 16,
                'temperature_c' => 37.5,
                'spo2' => 95.0,
                'consciousness_level' => 'pain',
                'urine_output_ml' => 65.0,
                'nursing_notes' => 'Ventilator SIMV mode. Endotracheal tube suctioned x 2. Pupils equal and reactive to light.',
            ],
            [
                'recorded_at' => Carbon::now()->setTime(6, 0),
                'bp_systolic' => 118,
                'bp_diastolic' => 72,
                'heart_rate' => 96,
                'respiratory_rate' => 16,
                'temperature_c' => 37.1,
                'spo2' => 98.0,
                'consciousness_level' => 'voice',
                'urine_output_ml' => 80.0,
                'nursing_notes' => 'Sedation weaned slightly. Patient opens eyes to verbal stimuli. Hemodynamically stable.',
            ],
        ];

        foreach ($icuVitals as $iVit) {
            VitalsLog::firstOrCreate(
                [
                    'admission_id' => $traumaAdm->id,
                    'recorded_at' => $iVit['recorded_at'],
                ],
                array_merge($iVit, [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'branch_id' => $branch->id,
                    'patient_id' => $traumaAdm->patient_id,
                    'recorded_by' => $nurse->id,
                ])
            );
        }

        // ==============================================================
        // 6. Medication Administration Records (MAR)
        // ==============================================================
        $medsData = [
            [
                'admission' => $johnAdm,
                'medication_name' => 'Amlodipine Besylate',
                'dosage' => '10 mg',
                'route' => 'oral',
                'scheduled_time' => Carbon::now()->setTime(8, 0),
                'administered_at' => Carbon::now()->setTime(8, 5),
                'status' => 'given',
                'notes' => 'Administered with breakfast. Patient tolerated well.',
            ],
            [
                'admission' => $johnAdm,
                'medication_name' => 'Lisinopril',
                'dosage' => '20 mg',
                'route' => 'oral',
                'scheduled_time' => Carbon::now()->setTime(8, 0),
                'administered_at' => Carbon::now()->setTime(8, 5),
                'status' => 'given',
                'notes' => 'Administered alongside amlodipine.',
            ],
            [
                'admission' => $traumaAdm,
                'medication_name' => 'Ceftriaxone Sodium',
                'dosage' => '2 g IV Piggyback',
                'route' => 'iv',
                'scheduled_time' => Carbon::now()->subHours(4),
                'administered_at' => Carbon::now()->subHours(4)->addMinutes(10),
                'status' => 'given',
                'notes' => 'Infused over 30 minutes via central venous catheter.',
            ],
            [
                'admission' => $traumaAdm,
                'medication_name' => 'Mannitol 20%',
                'dosage' => '100 mL',
                'route' => 'iv',
                'scheduled_time' => Carbon::now()->subHours(8),
                'administered_at' => Carbon::now()->subHours(8)->addMinutes(5),
                'status' => 'given',
                'notes' => 'Administered for intracranial pressure management.',
            ],
            [
                'admission' => $admissions['ADM-2026-000003'], // Tommy
                'medication_name' => 'Salbutamol Nebulizer',
                'dosage' => '2.5 mg / 2.5 mL',
                'route' => 'inhalation',
                'scheduled_time' => Carbon::now()->subHours(2),
                'administered_at' => Carbon::now()->subHours(2),
                'status' => 'given',
                'notes' => 'Driven by oxygen at 6 L/min. Wheezing significantly diminished.',
            ],
            [
                'admission' => $admissions['ADM-2026-000006'], // Bethlehem
                'medication_name' => 'Cefazolin',
                'dosage' => '1 g',
                'route' => 'iv',
                'scheduled_time' => Carbon::now()->subHours(6),
                'administered_at' => Carbon::now()->subHours(6)->addMinutes(15),
                'status' => 'given',
                'notes' => 'Post-op surgical prophylaxis.',
            ],
        ];

        foreach ($medsData as $mData) {
            MedicationAdministration::firstOrCreate(
                [
                    'admission_id' => $mData['admission']->id,
                    'medication_name' => $mData['medication_name'],
                    'administered_at' => $mData['administered_at'],
                ],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'branch_id' => $branch->id,
                    'patient_id' => $mData['admission']->patient_id,
                    'administered_by' => $nurse->id,
                    'dosage' => $mData['dosage'],
                    'route' => $mData['route'],
                    'scheduled_time' => $mData['scheduled_time'],
                    'status' => $mData['status'],
                    'notes' => $mData['notes'],
                ]
            );
        }

        // ==============================================================
        // 7. Bed Transfer Audit Ledger
        // ==============================================================
        // Bethlehem was transferred from ICU-02 (PACU) to SURG-201
        $bethAdm = $admissions['ADM-2026-000006'];
        BedTransfer::firstOrCreate(
            ['admission_id' => $bethAdm->id, 'to_bed_id' => $beds['SURG-201']->id],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'admission_id' => $bethAdm->id,
                'patient_id' => $bethAdm->patient_id,
                'from_ward_id' => $wards['ICU-MAIN']->id,
                'from_bed_id' => $beds['ICU-02']->id,
                'to_ward_id' => $wards['SURG']->id,
                'to_bed_id' => $beds['SURG-201']->id,
                'reason' => 'Patient extubated, hemodynamically stable post-op. Transferred from PACU/ICU to general surgical ward.',
                'transferred_by' => $nurse->id,
                'transferred_at' => Carbon::now()->subDays(3)->setTime(16, 30),
                'status' => 'completed',
            ]
        );
    }
}
