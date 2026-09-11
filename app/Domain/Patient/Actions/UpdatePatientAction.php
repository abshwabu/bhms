<?php

namespace App\Domain\Patient\Actions;

use App\Domain\Patient\Events\PatientUpdatedEvent;
use App\Domain\Patient\Models\Patient;
use Illuminate\Support\Facades\DB;

class UpdatePatientAction
{
    public function execute(Patient $patient, array $data, ?string $userId = null): Patient
    {
        return DB::transaction(function () use ($patient, $data, $userId) {
            $data['updated_by'] = $userId;
            $original = $patient->toArray();

            $patient->update($data);

            $changed = array_diff_assoc($patient->toArray(), $original);

            event(new PatientUpdatedEvent($patient, $changed));

            return $patient->fresh(['medicalHistory', 'allergies', 'insurance', 'relationships']);
        });
    }
}
