<?php

namespace App\Domain\IPD\Services;

use App\Domain\IPD\Models\Admission;
use App\Domain\IPD\Models\MedicationAdministration;
use App\Domain\IPD\Models\VitalsLog;
use App\Models\User;
use Illuminate\Support\Str;

class NursingStationService
{
    /**
     * Record a new vitals entry for an admitted inpatient.
     */
    public function logVitals(Admission $admission, array $data, User $nurse): VitalsLog
    {
        return VitalsLog::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $admission->organization_id,
            'branch_id' => $admission->branch_id,
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'recorded_by' => $nurse->id,
            'recorded_at' => $data['recorded_at'] ?? now(),

            'bp_systolic' => $data['bp_systolic'] ?? null,
            'bp_diastolic' => $data['bp_diastolic'] ?? null,
            'heart_rate' => $data['heart_rate'] ?? null,
            'respiratory_rate' => $data['respiratory_rate'] ?? null,
            'temperature_c' => $data['temperature_c'] ?? null,
            'spo2' => $data['spo2'] ?? null,
            'blood_glucose_mg_dl' => $data['blood_glucose_mg_dl'] ?? null,
            'pain_score' => $data['pain_score'] ?? null,
            'consciousness_level' => $data['consciousness_level'] ?? 'alert',
            'urine_output_ml' => $data['urine_output_ml'] ?? null,
            'nursing_notes' => $data['nursing_notes'] ?? null,
        ]);
    }

    /**
     * Record a medication administration event during nursing rounds.
     */
    public function recordMedication(Admission $admission, array $data, User $nurse): MedicationAdministration
    {
        return MedicationAdministration::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $admission->organization_id,
            'branch_id' => $admission->branch_id,
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'administered_by' => $nurse->id,
            'medication_name' => $data['medication_name'],
            'dosage' => $data['dosage'],
            'route' => $data['route'] ?? 'oral',
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'administered_at' => $data['administered_at'] ?? now(),
            'status' => $data['status'] ?? 'given',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Get full inpatient nursing chart (vitals history & medication round log).
     */
    public function getInpatientChart(Admission $admission): array
    {
        $vitals = VitalsLog::with('recordedByUser')
            ->where('admission_id', $admission->id)
            ->orderBy('recorded_at', 'desc')
            ->get();

        $medications = MedicationAdministration::with('administeredByUser')
            ->where('admission_id', $admission->id)
            ->orderBy('administered_at', 'desc')
            ->get();

        return [
            'admission_id' => $admission->id,
            'patient' => [
                'id' => $admission->patient?->id,
                'name' => $admission->patient?->full_name,
                'mrn' => $admission->patient?->mrn,
                'bed' => $admission->bed?->bed_number,
                'ward' => $admission->ward?->name,
            ],
            'vitals_history' => $vitals,
            'medications_history' => $medications,
            'latest_vitals' => $vitals->first(),
        ];
    }
}
