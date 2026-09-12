<?php

namespace App\Domain\IPD\Services;

use App\Domain\IPD\Models\Admission;
use App\Domain\IPD\Models\Bed;
use App\Domain\IPD\Models\BedTransfer;
use App\Domain\IPD\Models\Ward;
use App\Domain\Shared\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AdtService
{
    public function __construct(
        protected DischargeSummaryService $dischargeService
    ) {
    }

    /**
     * Admit a patient to a ward and bed.
     * Enforces: A bed cannot be assigned to two active admissions simultaneously.
     */
    public function admitPatient(array $data, Branch $branch, User $staffUser): Admission
    {
        return DB::transaction(function () use ($data, $branch, $staffUser) {
            $bedId = $data['bed_id'];

            // 1. Concurrency Lock on target bed
            $bed = Bed::where('id', $bedId)->lockForUpdate()->firstOrFail();

            // 2. Strict Check: Verify bed is available and not already assigned to an active admission
            $hasActiveAdmission = Admission::where('bed_id', $bed->id)
                ->where('status', 'admitted')
                ->exists();

            if ($bed->status !== 'available' || $hasActiveAdmission) {
                throw new InvalidArgumentException(
                    "Bed #{$bed->bed_number} is already occupied or assigned. A bed cannot be assigned to two active admissions simultaneously."
                );
            }

            // 3. Generate unique sequential admission number: ADM-{YEAR}-{BRANCH}-{000001}
            $year = date('Y');
            $branchCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $branch->code ?? 'HSP'));
            $todayCount = Admission::where('branch_id', $branch->id)
                ->whereDate('created_at', now()->toDateString())
                ->count() + 1;
            $admissionNumber = sprintf('ADM-%d-%s-%06d', $year, $branchCode, $todayCount);

            // 4. Create Admission
            $admission = Admission::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'admission_number' => $admissionNumber,
                'patient_id' => $data['patient_id'],
                'appointment_id' => $data['appointment_id'] ?? null,
                'ward_id' => $bed->ward_id,
                'bed_id' => $bed->id,
                'admitting_doctor_id' => $data['admitting_doctor_id'],
                'attending_doctor_id' => $data['attending_doctor_id'] ?? $data['admitting_doctor_id'],
                'admitted_by' => $staffUser->id, // Staff ID
                'admission_type' => $data['admission_type'] ?? 'emergency',
                'status' => 'admitted',
                'admitted_at' => $data['admitted_at'] ?? now(),
                'admitting_diagnosis' => $data['admitting_diagnosis'],
                'primary_diagnosis' => $data['primary_diagnosis'] ?? $data['admitting_diagnosis'],
                'secondary_diagnoses' => $data['secondary_diagnoses'] ?? [],
                'procedures_performed' => $data['procedures_performed'] ?? [],
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'initial_vitals' => $data['initial_vitals'] ?? [],
                'insurance_policy_id' => $data['insurance_policy_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // 5. Mark Bed as occupied
            $bed->update(['status' => 'occupied']);

            return $admission->load(['patient', 'ward', 'bed', 'admittingDoctor', 'attendingDoctor', 'admittedByUser']);
        });
    }

    /**
     * Transfer patient to another ward and bed with complete audit logging.
     * Enforces: Destination bed must be free and transfer logged with timestamp & staff ID.
     */
    public function transferBed(Admission $admission, string $toBedId, string $reason, User $staffUser): BedTransfer
    {
        return DB::transaction(function () use ($admission, $toBedId, $reason, $staffUser) {
            if ($admission->status !== 'admitted') {
                throw new InvalidArgumentException("Cannot transfer patient with status '{$admission->status}'. Admission must be active.");
            }

            $oldBedId = $admission->bed_id;
            $oldWardId = $admission->ward_id;

            if ($oldBedId === $toBedId) {
                throw new InvalidArgumentException("Destination bed is identical to the currently assigned bed.");
            }

            // Lock both old and target beds
            $oldBed = Bed::where('id', $oldBedId)->lockForUpdate()->firstOrFail();
            $newBed = Bed::where('id', $toBedId)->lockForUpdate()->firstOrFail();

            // Destination bed availability check
            $destinationOccupied = Admission::where('bed_id', $newBed->id)
                ->where('status', 'admitted')
                ->exists();

            if ($newBed->status !== 'available' || $destinationOccupied) {
                throw new InvalidArgumentException(
                    "Destination bed #{$newBed->bed_number} is already occupied. Transfer cannot proceed."
                );
            }

            $transferTimestamp = now();

            // 1. Audit Log: Create immutable BedTransfer record with staff ID and timestamp
            $transfer = BedTransfer::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $admission->organization_id,
                'branch_id' => $admission->branch_id,
                'admission_id' => $admission->id,
                'patient_id' => $admission->patient_id,
                'from_ward_id' => $oldWardId,
                'from_bed_id' => $oldBedId,
                'to_ward_id' => $newBed->ward_id,
                'to_bed_id' => $newBed->id,
                'reason' => $reason,
                'transferred_by' => $staffUser->id, // Staff ID
                'transferred_at' => $transferTimestamp, // Timestamp
                'status' => 'completed',
            ]);

            // 2. Update Admission current ward and bed
            $admission->update([
                'ward_id' => $newBed->ward_id,
                'bed_id' => $newBed->id,
            ]);

            // 3. Update Bed statuses
            $oldBed->update(['status' => 'cleaning']);
            $newBed->update(['status' => 'occupied']);

            return $transfer->load(['admission', 'patient', 'fromWard', 'fromBed', 'toWard', 'toBed', 'transferredByUser']);
        });
    }

    /**
     * Discharge patient from inpatient care, releasing bed and auto-populating discharge summary.
     */
    public function dischargePatient(Admission $admission, array $dischargeData, User $staffUser): Admission
    {
        return DB::transaction(function () use ($admission, $dischargeData, $staffUser) {
            if ($admission->status !== 'admitted') {
                throw new InvalidArgumentException("Admission is already {$admission->status}.");
            }

            $dischargeTimestamp = now();

            // 1. Update Admission status
            $admission->update([
                'status' => 'discharged',
                'discharged_at' => $dischargeTimestamp,
                'discharged_by' => $staffUser->id, // Staff ID
                'discharge_type' => $dischargeData['discharge_type'] ?? 'regular',
                'primary_diagnosis' => $dischargeData['primary_diagnosis'] ?? $admission->primary_diagnosis,
                'secondary_diagnoses' => $dischargeData['secondary_diagnoses'] ?? $admission->secondary_diagnoses,
                'procedures_performed' => $dischargeData['procedures_performed'] ?? $admission->procedures_performed,
            ]);

            // 2. Release Bed into cleaning
            $bed = Bed::where('id', $admission->bed_id)->lockForUpdate()->first();
            if ($bed) {
                $bed->update(['status' => 'cleaning']);
            }

            // 3. Auto-generate Discharge Summary from stay records
            $this->dischargeService->generateSummary(
                $admission->fresh(),
                $dischargeData,
                $staffUser
            );

            return $admission->fresh(['patient', 'ward', 'bed', 'dischargeSummary', 'dischargedByUser']);
        });
    }
}
