<?php

namespace App\Domain\OPD\Services;

use App\Domain\OPD\Models\ConsultationNote;
use App\Domain\Shared\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SoapNoteService
{
    /**
     * Create a draft SOAP consultation note.
     */
    public function createDraft(array $data, User $doctor, Branch $branch): ConsultationNote
    {
        return ConsultationNote::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
            'appointment_id' => $data['appointment_id'] ?? null,
            'patient_id' => $data['patient_id'],
            'doctor_id' => $doctor->id,
            'version' => 1,

            // Subjective
            'chief_complaint' => $data['chief_complaint'],
            'history_of_presenting_illness' => $data['history_of_presenting_illness'] ?? null,
            'review_of_systems' => $data['review_of_systems'] ?? null,

            // Objective
            'vitals' => $data['vitals'] ?? [],
            'physical_examination' => $data['physical_examination'] ?? null,

            // Assessment
            'provisional_diagnosis' => $data['provisional_diagnosis'],
            'differential_diagnoses' => $data['differential_diagnoses'] ?? null,
            'icd10_codes' => $data['icd10_codes'] ?? [],

            // Plan
            'treatment_plan' => $data['treatment_plan'],
            'prescriptions_advice' => $data['prescriptions_advice'] ?? null,
            'orders_requested' => $data['orders_requested'] ?? null,
            'diet_and_lifestyle_advice' => $data['diet_and_lifestyle_advice'] ?? null,
            'follow_up_recommended_date' => $data['follow_up_recommended_date'] ?? null,
            'follow_up_instructions' => $data['follow_up_instructions'] ?? null,

            'is_signed_off' => false,
            'notes_status' => 'draft',
        ]);
    }

    /**
     * Update an existing draft SOAP note. Throws if already signed off.
     */
    public function updateDraft(ConsultationNote $note, array $data, User $doctor): ConsultationNote
    {
        if ($note->is_signed_off) {
            throw new InvalidArgumentException(
                "SOAP consultation note #{$note->id} has already been signed off and is legally immutable. Create an amended version instead."
            );
        }

        $note->update($data);

        return $note->fresh(['patient', 'doctor', 'appointment']);
    }

    /**
     * Sign off on a consultation note. Makes it legally immutable.
     */
    public function signOff(ConsultationNote $note, User $doctor): ConsultationNote
    {
        if ($note->is_signed_off) {
            return $note;
        }

        $note->update([
            'is_signed_off' => true,
            'signed_off_at' => now(),
            'signed_off_by' => $doctor->id,
            'notes_status' => 'signed_off',
        ]);

        return $note->fresh(['patient', 'doctor', 'appointment']);
    }

    /**
     * Create an amended version of an already signed-off note.
     * Preserves original signed-off note intact and increments version counter.
     */
    public function amendSignedNote(
        ConsultationNote $originalNote,
        array $amendedData,
        string $amendmentReason,
        User $doctor
    ): ConsultationNote {
        return DB::transaction(function () use ($originalNote, $amendedData, $amendmentReason, $doctor) {
            if (!$originalNote->is_signed_off) {
                throw new InvalidArgumentException("Only signed-off notes require amendment versioning. For drafts, use updateDraft.");
            }

            // Mark original note status as amended
            $originalNote->update(['notes_status' => 'amended']);

            // Create new version
            $newVersionNumber = $originalNote->version + 1;

            $newNote = ConsultationNote::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $originalNote->organization_id,
                'branch_id' => $originalNote->branch_id,
                'appointment_id' => $originalNote->appointment_id,
                'patient_id' => $originalNote->patient_id,
                'doctor_id' => $doctor->id,
                'parent_note_id' => $originalNote->id,
                'version' => $newVersionNumber,

                'chief_complaint' => $amendedData['chief_complaint'] ?? $originalNote->chief_complaint,
                'history_of_presenting_illness' => $amendedData['history_of_presenting_illness'] ?? $originalNote->history_of_presenting_illness,
                'review_of_systems' => $amendedData['review_of_systems'] ?? $originalNote->review_of_systems,
                'vitals' => $amendedData['vitals'] ?? $originalNote->vitals,
                'physical_examination' => $amendedData['physical_examination'] ?? $originalNote->physical_examination,
                'provisional_diagnosis' => $amendedData['provisional_diagnosis'] ?? $originalNote->provisional_diagnosis,
                'differential_diagnoses' => $amendedData['differential_diagnoses'] ?? $originalNote->differential_diagnoses,
                'icd10_codes' => $amendedData['icd10_codes'] ?? $originalNote->icd10_codes,
                'treatment_plan' => $amendedData['treatment_plan'] ?? $originalNote->treatment_plan,
                'prescriptions_advice' => $amendedData['prescriptions_advice'] ?? $originalNote->prescriptions_advice,
                'orders_requested' => $amendedData['orders_requested'] ?? $originalNote->orders_requested,
                'diet_and_lifestyle_advice' => $amendedData['diet_and_lifestyle_advice'] ?? $originalNote->diet_and_lifestyle_advice,
                'follow_up_recommended_date' => $amendedData['follow_up_recommended_date'] ?? $originalNote->follow_up_recommended_date,
                'follow_up_instructions' => $amendedData['follow_up_instructions'] ?? $originalNote->follow_up_instructions,

                'is_signed_off' => true,
                'signed_off_at' => now(),
                'signed_off_by' => $doctor->id,
                'notes_status' => 'signed_off',
                'amendment_reason' => $amendmentReason,
            ]);

            return $newNote->load(['patient', 'doctor', 'parentNote']);
        });
    }
}
