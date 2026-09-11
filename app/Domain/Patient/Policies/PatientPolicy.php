<?php

namespace App\Domain\Patient\Policies;

use App\Domain\Patient\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientPolicy
{
    use HandlesAuthorization;

    /**
     * Intercept checks for super_admin.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine if the user can view any patients (search/list).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['hospital_admin', 'doctor', 'nurse', 'staff', 'receptionist', 'pharmacist', 'lab_technician', 'billing_officer'])
            || $user->hasPermissionTo('patient.records.view');
    }

    /**
     * Determine if the user can view the patient demographics.
     */
    public function view(User $user, Patient $patient): bool
    {
        // Patient self-access
        if ($user->is_patient && $patient->portal_user_id === $user->id) {
            return true;
        }

        return $user->hasAnyRole(['hospital_admin', 'doctor', 'nurse', 'staff', 'receptionist', 'pharmacist', 'lab_technician', 'billing_officer'])
            || $user->hasPermissionTo('patient.records.view');
    }

    /**
     * Determine if the user can register a new patient.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['hospital_admin', 'doctor', 'nurse', 'staff', 'receptionist'])
            || $user->hasPermissionTo('patient.records.create');
    }

    /**
     * Determine if the user can update patient demographics.
     */
    public function update(User $user, Patient $patient): bool
    {
        return $user->hasAnyRole(['hospital_admin', 'doctor', 'nurse', 'staff', 'receptionist'])
            || $user->hasPermissionTo('patient.records.update');
    }

    /**
     * Determine if the user can soft-delete a patient.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasAnyRole(['hospital_admin'])
            || $user->hasPermissionTo('patient.records.delete');
    }

    /**
     * Determine if the user can access clinical medical history & allergies.
     * Strictly restricted away from receptionists/billing staff unless granted permission.
     */
    public function viewMedicalHistory(User $user, Patient $patient): bool
    {
        if ($user->is_patient && $patient->portal_user_id === $user->id) {
            return true;
        }

        return $user->hasAnyRole(['doctor', 'nurse', 'pharmacist', 'hospital_admin'])
            || $user->hasPermissionTo('clinical.history.view');
    }

    /**
     * Determine if the user can record or modify clinical medical history & allergies.
     * Strictly restricted to licensed clinical staff (Doctor, Nurse).
     */
    public function modifyMedicalHistory(User $user, Patient $patient): bool
    {
        return $user->hasAnyRole(['doctor', 'nurse', 'hospital_admin'])
            || $user->hasPermissionTo('clinical.history.modify');
    }
}
