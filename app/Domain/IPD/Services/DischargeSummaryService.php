<?php

namespace App\Domain\IPD\Services;

use App\Domain\IPD\Models\Admission;
use App\Domain\IPD\Models\DischargeSummary;
use App\Domain\IPD\Models\MedicationAdministration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DischargeSummaryService
{
    /**
     * Generate or update discharge summary by auto-pulling stay records.
     * Automatically populates: admission date, discharge date, diagnoses, procedures, and administered medications.
     */
    public function generateSummary(Admission $admission, array $data, User $doctor): DischargeSummary
    {
        return DB::transaction(function () use ($admission, $data, $doctor) {
            // Auto-pull administered medications from nursing rounds during this admission
            $stayMedications = MedicationAdministration::where('admission_id', $admission->id)
                ->where('status', 'given')
                ->get()
                ->map(fn ($m) => [
                    'medication_name' => $m->medication_name,
                    'dosage' => $m->dosage,
                    'route' => $m->route,
                    'last_given_at' => $m->administered_at?->toIso8601String(),
                ])
                ->unique('medication_name')
                ->values()
                ->toArray();

            $medicationsAtDischarge = !empty($data['medications_at_discharge'])
                ? $data['medications_at_discharge']
                : $stayMedications;

            $primaryDiagnosis = $data['primary_diagnosis']
                ?? $admission->primary_diagnosis
                ?? $admission->admitting_diagnosis;

            $secondaryDiagnoses = $data['secondary_diagnoses']
                ?? $admission->secondary_diagnoses
                ?? [];

            $procedures = $data['procedures_performed']
                ?? $admission->procedures_performed
                ?? [];

            $dischargeSummary = DischargeSummary::updateOrCreate(
                ['admission_id' => $admission->id],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $admission->organization_id,
                    'branch_id' => $admission->branch_id,
                    'patient_id' => $admission->patient_id,
                    'discharging_doctor_id' => $doctor->id,

                    // Auto-populated stay records
                    'admission_date' => $admission->admitted_at,
                    'discharge_date' => $admission->discharged_at ?: now(),
                    'primary_diagnosis' => $primaryDiagnosis,
                    'secondary_diagnoses' => $secondaryDiagnoses,
                    'procedures_performed' => $procedures,
                    'medications_at_discharge' => $medicationsAtDischarge,

                    'hospital_course_summary' => $data['hospital_course_summary'] ?? 'Patient admitted and treated according to clinical protocol with uneventful recovery.',
                    'discharge_condition' => $data['discharge_condition'] ?? 'stable',
                    'discharge_type' => $data['discharge_type'] ?? $admission->discharge_type ?? 'regular',
                    'follow_up_instructions' => $data['follow_up_instructions'] ?? 'Follow up at OPD in 7-14 days. Report immediately if acute symptoms recur.',
                    'follow_up_date' => $data['follow_up_date'] ?? null,
                    'is_finalized' => $data['is_finalized'] ?? false,
                    'finalized_at' => (!empty($data['is_finalized']) && $data['is_finalized']) ? now() : null,
                    'finalized_by' => (!empty($data['is_finalized']) && $data['is_finalized']) ? $doctor->id : null,
                ]
            );

            return $dischargeSummary->load(['admission', 'patient', 'dischargingDoctor']);
        });
    }

    /**
     * Finalize and lock discharge summary.
     */
    public function finalize(DischargeSummary $summary, User $doctor): DischargeSummary
    {
        $summary->update([
            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => $doctor->id,
        ]);

        return $summary->fresh(['admission', 'patient', 'dischargingDoctor']);
    }
}
