<?php

namespace Database\Seeders;

use App\Domain\OPD\Models\Appointment;
use App\Domain\OPD\Models\ConsultationNote;
use App\Domain\OPD\Models\Department;
use App\Domain\OPD\Models\DoctorSchedule;
use App\Domain\OPD\Models\QueueToken;
use App\Domain\OPD\Models\Referral;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OpdModuleSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();
        $branch = Branch::first();

        if (!$organization || !$branch) {
            return;
        }

        // Staff
        $doctor = User::where('email', 'doctor@hms.local')->first()
            ?? User::where('name', 'like', '%Dr.%')->first()
            ?? User::first();

        $admin = User::where('email', 'admin@hms.local')->first()
            ?? User::first();

        $receptionist = User::where('email', 'receptionist@hms.local')->first()
            ?? $admin;

        // Patients
        $john = Patient::where('national_id', 'NAT-850615-101')->first() ?? Patient::first();
        $tommy = Patient::where('national_id', 'NAT-180920-808')->first() ?? Patient::skip(1)->first() ?? $john;
        $amina = Patient::where('national_id', 'NAT-921104-450')->first() ?? Patient::skip(2)->first() ?? $john;
        $trauma = Patient::where('first_name', 'Trauma Male #1')->first() ?? Patient::skip(3)->first() ?? $john;
        $solomon = Patient::where('national_id', 'NAT-790112-234')->first() ?? Patient::skip(4)->first() ?? $john;
        $bethlehem = Patient::where('national_id', 'NAT-950418-678')->first() ?? Patient::skip(5)->first() ?? $john;

        // ==============================================================
        // 1. Clinical Departments
        // ==============================================================
        $departmentsData = [
            [
                'code' => 'GEN',
                'name' => 'General Outpatient Department (OPD)',
                'description' => 'Primary ambulatory care, triage consults, and preventive medicine.',
            ],
            [
                'code' => 'CARD',
                'name' => 'Cardiology & Chest Clinic',
                'description' => 'Comprehensive cardiac diagnostics, ECG, echocardiography, and vascular disease management.',
            ],
            [
                'code' => 'INT-MED',
                'name' => 'Internal Medicine Specialty Clinic',
                'description' => 'Adult internal medicine, endocrinology, nephrology, and complex chronic disease care.',
            ],
            [
                'code' => 'PED',
                'name' => 'Pediatrics & Adolescent Medicine',
                'description' => 'Newborn, infant, and pediatric specialized outpatient consultations.',
            ],
            [
                'code' => 'ORTH',
                'name' => 'Orthopedics & Sports Medicine',
                'description' => 'Bone, joint, musculoskeletal injury and post-trauma orthopedic consultations.',
            ],
            [
                'code' => 'OBGYN',
                'name' => 'Obstetrics & Women\'s Health',
                'description' => 'Antenatal care, gynecological screening, and family health consultations.',
            ],
        ];

        $departments = [];
        foreach ($departmentsData as $dData) {
            $departments[$dData['code']] = Department::firstOrCreate(
                ['branch_id' => $branch->id, 'code' => $dData['code']],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'name' => $dData['name'],
                    'description' => $dData['description'],
                    'is_active' => true,
                ]
            );
        }

        // ==============================================================
        // 2. Doctor Schedules (Recurring Monday through Friday)
        // ==============================================================
        // Days 1 (Mon) to 5 (Fri)
        for ($day = 1; $day <= 5; $day++) {
            // Morning Clinic: 08:30 - 12:30
            DoctorSchedule::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'doctor_id' => $doctor->id,
                    'department_id' => $departments['GEN']->id,
                    'day_of_week' => $day,
                    'start_time' => '08:30:00',
                ],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'schedule_type' => 'recurring',
                    'end_time' => '12:30:00',
                    'slot_duration_minutes' => 20,
                    'max_patients' => 12,
                    'is_available' => true,
                    'room_number' => 'Consultation Suite 101',
                    'notes' => 'General Medicine & OPD morning consults.',
                ]
            );

            // Afternoon Clinic: 14:00 - 17:30
            DoctorSchedule::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'doctor_id' => $doctor->id,
                    'department_id' => $departments['GEN']->id,
                    'day_of_week' => $day,
                    'start_time' => '14:00:00',
                ],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'schedule_type' => 'recurring',
                    'end_time' => '17:30:00',
                    'slot_duration_minutes' => 20,
                    'max_patients' => 10,
                    'is_available' => true,
                    'room_number' => 'Consultation Suite 101',
                    'notes' => 'Follow-up and specialized reviews.',
                ]
            );
        }

        // Doctor Arthur Sterling Cardiology Schedule (Tuesdays & Thursdays)
        foreach ([2, 4] as $cardDay) {
            DoctorSchedule::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'doctor_id' => $admin->id,
                    'department_id' => $departments['CARD']->id,
                    'day_of_week' => $cardDay,
                    'start_time' => '10:00:00',
                ],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'schedule_type' => 'recurring',
                    'end_time' => '13:00:00',
                    'slot_duration_minutes' => 30,
                    'max_patients' => 6,
                    'is_available' => true,
                    'room_number' => 'Executive Suite 201',
                    'notes' => 'Cardiology specialty consultations.',
                ]
            );
        }

        // ==============================================================
        // 3. Appointments (Today, Past, Upcoming)
        // ==============================================================
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();

        $apptsConfig = [
            // Today's Appointments
            [
                'appointment_number' => 'APT-2026-000101',
                'patient' => $john,
                'doctor' => $doctor,
                'department' => $departments['GEN'],
                'date' => $today,
                'start_time' => '08:30:00',
                'end_time' => '08:50:00',
                'type' => 'in_person',
                'status' => 'completed',
                'reason' => 'Follow-up for hypertension and medication reconciliation.',
                'checked_in_at' => Carbon::now()->setTime(8, 15),
            ],
            [
                'appointment_number' => 'APT-2026-000102',
                'patient' => $amina,
                'doctor' => $doctor,
                'department' => $departments['GEN'],
                'date' => $today,
                'start_time' => '09:00:00',
                'end_time' => '09:20:00',
                'type' => 'in_person',
                'status' => 'in_consultation',
                'reason' => 'Antenatal review and contact dermatitis consultation.',
                'checked_in_at' => Carbon::now()->setTime(8, 48),
            ],
            [
                'appointment_number' => 'APT-2026-000103',
                'patient' => $tommy,
                'doctor' => $doctor,
                'department' => $departments['PED'],
                'date' => $today,
                'start_time' => '09:30:00',
                'end_time' => '09:50:00',
                'type' => 'in_person',
                'status' => 'checked_in',
                'reason' => 'Pediatric asthma checkup and lung function check.',
                'checked_in_at' => Carbon::now()->setTime(9, 10),
            ],
            [
                'appointment_number' => 'APT-2026-000104',
                'patient' => $solomon,
                'doctor' => $doctor,
                'department' => $departments['INT-MED'],
                'date' => $today,
                'start_time' => '10:00:00',
                'end_time' => '10:20:00',
                'type' => 'in_person',
                'status' => 'scheduled',
                'reason' => 'Diabetes management review and HbA1c lab check.',
                'checked_in_at' => null,
            ],
            [
                'appointment_number' => 'APT-2026-000105',
                'patient' => $bethlehem,
                'doctor' => $doctor,
                'department' => $departments['GEN'],
                'date' => $today,
                'start_time' => '10:30:00',
                'end_time' => '10:50:00',
                'type' => 'in_person',
                'status' => 'scheduled',
                'reason' => 'Post-op surgical incision dressing inspection.',
                'checked_in_at' => null,
            ],
            [
                'appointment_number' => 'APT-2026-000106',
                'patient' => $trauma,
                'doctor' => $doctor,
                'department' => $departments['ORTH'],
                'date' => $today,
                'start_time' => '11:00:00',
                'end_time' => '11:20:00',
                'type' => 'in_person',
                'status' => 'scheduled',
                'reason' => 'Orthopedic clavicle evaluation and range-of-motion review.',
                'checked_in_at' => null,
            ],

            // Yesterday's Completed Appointment
            [
                'appointment_number' => 'APT-2026-000091',
                'patient' => $john,
                'doctor' => $doctor,
                'department' => $departments['GEN'],
                'date' => $yesterday,
                'start_time' => '09:00:00',
                'end_time' => '09:20:00',
                'type' => 'in_person',
                'status' => 'completed',
                'reason' => 'Elevated blood pressure check.',
                'checked_in_at' => Carbon::yesterday()->setTime(8, 45),
            ],

            // Tomorrow's Scheduled Appointments
            [
                'appointment_number' => 'APT-2026-000201',
                'patient' => $solomon,
                'doctor' => $doctor,
                'department' => $departments['CARD'],
                'date' => $tomorrow,
                'start_time' => '09:00:00',
                'end_time' => '09:30:00',
                'type' => 'in_person',
                'status' => 'scheduled',
                'reason' => 'Cardiovascular risk evaluation and baseline resting ECG.',
                'checked_in_at' => null,
            ],
            [
                'appointment_number' => 'APT-2026-000202',
                'patient' => $bethlehem,
                'doctor' => $doctor,
                'department' => $departments['GEN'],
                'date' => $tomorrow,
                'start_time' => '10:00:00',
                'end_time' => '10:20:00',
                'type' => 'in_person',
                'status' => 'scheduled',
                'reason' => 'Routine post-discharge clinical consultation.',
                'checked_in_at' => null,
            ],
        ];

        $appointments = [];
        foreach ($apptsConfig as $aCfg) {
            $appt = Appointment::firstOrCreate(
                ['appointment_number' => $aCfg['appointment_number']],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'branch_id' => $branch->id,
                    'department_id' => $aCfg['department']->id,
                    'patient_id' => $aCfg['patient']->id,
                    'doctor_id' => $aCfg['doctor']->id,
                    'appointment_date' => $aCfg['date'],
                    'start_time' => $aCfg['start_time'],
                    'end_time' => $aCfg['end_time'],
                    'type' => $aCfg['type'],
                    'status' => $aCfg['status'],
                    'reason_for_visit' => $aCfg['reason'],
                    'checked_in_at' => $aCfg['checked_in_at'],
                    'booked_by' => $receptionist->id,
                ]
            );
            $appointments[$aCfg['appointment_number']] = $appt;
        }

        // ==============================================================
        // 4. Queue Tokens for Today
        // ==============================================================
        $genDept = $departments['GEN'];
        $pedDept = $departments['PED'];
        $cardDept = $departments['CARD'];

        $tokensData = [
            // General OPD Tokens
            [
                'department' => $genDept,
                'patient' => $john,
                'doctor' => $doctor,
                'appointment' => $appointments['APT-2026-000101'],
                'token_number' => 1,
                'token_code' => 'GEN-001',
                'status' => 'completed',
                'priority' => 'normal',
                'counter_room' => 'Room 101',
                'called_at' => Carbon::now()->setTime(8, 32),
                'consultation_started_at' => Carbon::now()->setTime(8, 35),
                'completed_at' => Carbon::now()->setTime(8, 52),
            ],
            [
                'department' => $genDept,
                'patient' => $amina,
                'doctor' => $doctor,
                'appointment' => $appointments['APT-2026-000102'],
                'token_number' => 2,
                'token_code' => 'GEN-002',
                'status' => 'in_consultation',
                'priority' => 'normal',
                'counter_room' => 'Room 101',
                'called_at' => Carbon::now()->setTime(9, 2),
                'consultation_started_at' => Carbon::now()->setTime(9, 5),
                'completed_at' => null,
            ],
            [
                'department' => $genDept,
                'patient' => $bethlehem,
                'doctor' => $doctor,
                'appointment' => $appointments['APT-2026-000105'],
                'token_number' => 3,
                'token_code' => 'GEN-003',
                'status' => 'called',
                'priority' => 'urgent',
                'counter_room' => 'Room 101',
                'called_at' => Carbon::now()->setTime(9, 25),
                'consultation_started_at' => null,
                'completed_at' => null,
            ],
            [
                'department' => $genDept,
                'patient' => $solomon,
                'doctor' => $doctor,
                'appointment' => null,
                'token_number' => 4,
                'token_code' => 'GEN-004',
                'status' => 'waiting',
                'priority' => 'normal',
                'counter_room' => null,
                'called_at' => null,
                'consultation_started_at' => null,
                'completed_at' => null,
            ],

            // Pediatrics Token
            [
                'department' => $pedDept,
                'patient' => $tommy,
                'doctor' => $doctor,
                'appointment' => $appointments['APT-2026-000103'],
                'token_number' => 1,
                'token_code' => 'PED-001',
                'status' => 'waiting',
                'priority' => 'normal',
                'counter_room' => 'Room 105',
                'called_at' => null,
                'consultation_started_at' => null,
                'completed_at' => null,
            ],

            // Cardiology Token
            [
                'department' => $cardDept,
                'patient' => $john,
                'doctor' => $admin,
                'appointment' => null,
                'token_number' => 1,
                'token_code' => 'CARD-001',
                'status' => 'waiting',
                'priority' => 'normal',
                'counter_room' => 'Room 204',
                'called_at' => null,
                'consultation_started_at' => null,
                'completed_at' => null,
            ],
        ];

        foreach ($tokensData as $tData) {
            QueueToken::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'department_id' => $tData['department']->id,
                    'token_date' => $today,
                    'token_number' => $tData['token_number'],
                ],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'patient_id' => $tData['patient']->id,
                    'doctor_id' => $tData['doctor']?->id,
                    'appointment_id' => $tData['appointment']?->id,
                    'token_code' => $tData['token_code'],
                    'status' => $tData['status'],
                    'priority' => $tData['priority'],
                    'counter_room' => $tData['counter_room'],
                    'called_at' => $tData['called_at'],
                    'consultation_started_at' => $tData['consultation_started_at'],
                    'completed_at' => $tData['completed_at'],
                ]
            );
        }

        // ==============================================================
        // 5. SOAP Consultation Notes
        // ==============================================================
        $johnCompletedAppt = $appointments['APT-2026-000101'];
        $soapNote = ConsultationNote::firstOrCreate(
            ['appointment_id' => $johnCompletedAppt->id],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'patient_id' => $john->id,
                'doctor_id' => $doctor->id,
                'version' => 1,

                // Subjective (S)
                'chief_complaint' => 'Follow-up for essential hypertension and routine medication reconciliation.',
                'history_of_presenting_illness' => 'Patient reports compliant with morning antihypertensive therapy. No chest tightness, no shortness of breath, no visual disturbance. Mild intermittent headache during stressful workdays.',
                'review_of_systems' => [
                    'cardiovascular' => 'denies palpitations or orthopnea',
                    'neurological' => 'occasional tension headaches',
                    'respiratory' => 'clear, no wheeze',
                ],

                // Objective (O)
                'vitals' => [
                    'bp_systolic' => 132,
                    'bp_diastolic' => 84,
                    'heart_rate' => 74,
                    'temp_c' => 36.7,
                    'spo2' => 99,
                    'rr' => 16,
                    'weight_kg' => 82.0,
                    'height_cm' => 178,
                ],
                'physical_examination' => 'Alert and oriented x 3. Heart: regular rate and rhythm, S1/S2 present, no murmurs. Lungs: clear to auscultation bilaterally. Abdomen: soft, non-tender. Extremities: no lower limb edema.',

                // Assessment (A)
                'provisional_diagnosis' => 'Essential (primary) hypertension, well-controlled on current dual regimen',
                'differential_diagnoses' => 'White coat hypertension, secondary renovascular hypertension',
                'icd10_codes' => ['I10', 'E78.5'],

                // Plan (P)
                'treatment_plan' => '1. Continue Lisinopril 20mg once daily in morning. 2. Continue Amlodipine 10mg once daily. 3. Routine annual fasting lipid panel and basic metabolic panel. 4. Maintain low-sodium DASH diet.',
                'prescriptions_advice' => 'Refilled Lisinopril 20mg and Amlodipine 10mg for 90 days.',
                'orders_requested' => 'Fasting Lipid Panel, Serum Creatinine, eGFR, 12-lead ECG',
                'diet_and_lifestyle_advice' => 'Limit sodium intake to under 2.3g daily. 30 minutes of aerobic exercise 4-5 times weekly.',
                'follow_up_recommended_date' => Carbon::now()->addDays(30)->toDateString(),
                'follow_up_instructions' => 'Return to OPD in 1 month with home blood pressure monitoring log.',

                // Sign-off
                'is_signed_off' => true,
                'signed_off_at' => Carbon::now()->setTime(8, 52),
                'signed_off_by' => $doctor->id,
                'notes_status' => 'signed_off',
            ]
        );

        // ==============================================================
        // 6. Clinical Referrals
        // ==============================================================
        // Internal Referral (OPD -> Cardiology)
        Referral::firstOrCreate(
            [
                'patient_id' => $john->id,
                'to_department_id' => $departments['CARD']->id,
                'status' => 'pending',
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'referring_doctor_id' => $doctor->id,
                'consultation_note_id' => $soapNote->id,
                'referral_type' => 'internal_department',
                'from_department_id' => $departments['GEN']->id,
                'to_department_id' => $departments['CARD']->id,
                'to_doctor_id' => $admin->id,
                'priority' => 'routine',
                'reason_for_referral' => 'Evaluation for left ventricular hypertrophy and baseline echocardiography.',
                'clinical_summary' => '41-year-old male with 6-year history of primary hypertension. Stable on dual therapy. Requesting baseline 2D-Echocardiogram and cardiologist consultation.',
                'status' => 'pending',
            ]
        );

        // External Referral (OPD -> Specialized Facility)
        Referral::firstOrCreate(
            [
                'patient_id' => $trauma->id,
                'external_facility_name' => 'National Specialized Neurosurgery Hospital',
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'referring_doctor_id' => $doctor->id,
                'referral_type' => 'external_facility',
                'from_department_id' => $departments['GEN']->id,
                'external_facility_name' => 'National Specialized Neurosurgery Hospital',
                'external_specialist_name' => 'Prof. Dawit Zewde, Chief of Neurosurgery',
                'external_contact' => '+251-11-555-0999',
                'priority' => 'urgent',
                'reason_for_referral' => 'Tertiary neuro-rehabilitation and subacute subdural hematoma follow-up.',
                'clinical_summary' => 'Adult male polytrauma survivor status-post intracranial hematoma management in ICU. Ready for specialized neuro-rehabilitative step-down care.',
                'status' => 'pending',
            ]
        );
    }
}
