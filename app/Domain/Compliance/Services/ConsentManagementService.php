<?php

namespace App\Domain\Compliance\Services;

use App\Domain\Compliance\Models\PatientConsent;
use App\Domain\Patient\Models\Patient;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConsentManagementService
{
    /**
     * Record a new patient consent.
     */
    public function createConsent(array $data): PatientConsent
    {
        $patient = Patient::findOrFail($data['patient_id']);

        $grantedAt = ! empty($data['granted_at'])
            ? Carbon::parse($data['granted_at'])
            : Carbon::now();

        $expiresAt = ! empty($data['expires_at'])
            ? Carbon::parse($data['expires_at'])
            : null;

        $user = auth()->user();

        $consent = PatientConsent::create([
            'organization_id' => $patient->organization_id,
            'branch_id' => $data['branch_id'] ?? $patient->branch_id,
            'patient_id' => $patient->id,
            'consent_type' => $data['consent_type'],
            'title' => $data['title'],
            'purpose' => $data['purpose'],
            'status' => $data['status'] ?? 'granted',
            'granted_at' => $grantedAt,
            'expires_at' => $expiresAt,
            'signature_data' => $data['signature_data'] ?? null,
            'patient_national_id' => $data['patient_national_id'] ?? $patient->national_id,
            'contact_phone' => $data['contact_phone'] ?? $patient->phone,
            'sensitive_notes' => $data['sensitive_notes'] ?? null,
            'witness_name' => $data['witness_name'] ?? null,
            'witness_user_id' => $data['witness_user_id'] ?? null,
            'ip_address' => request() ? request()->ip() : null,
            'user_agent' => request() ? request()->userAgent() : null,
            'created_by' => $user?->id,
        ]);

        return $consent->load(['patient', 'witness', 'creator']);
    }

    /**
     * Revoke an existing consent with justification.
     */
    public function revokeConsent(PatientConsent $consent, string $reason, ?string $userId = null): PatientConsent
    {
        if ($consent->status === 'revoked') {
            throw new DomainException('Consent is already revoked.');
        }

        $consent->revoke($reason, $userId);

        return $consent->fresh(['patient', 'witness']);
    }

    /**
     * Verify if a patient currently holds active, non-expired, non-revoked consent for a specific procedure/disclosure.
     */
    public function verifyConsent(string $patientId, string $consentType): array
    {
        $consent = PatientConsent::where('patient_id', $patientId)
            ->where('consent_type', $consentType)
            ->where('status', 'granted')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->latest('granted_at')
            ->first();

        return [
            'patient_id' => $patientId,
            'consent_type' => $consentType,
            'has_valid_consent' => (bool) $consent,
            'consent' => $consent,
            'verified_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * List all consents with filtering.
     */
    public function listConsents(array $filters = []): LengthAwarePaginator
    {
        $query = PatientConsent::with([
            'patient:id,mrn,first_name,last_name,phone',
            'witness:id,name',
            'creator:id,name',
        ])->latest('granted_at');

        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (! empty($filters['consent_type'])) {
            $query->where('consent_type', $filters['consent_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', $search)
                    ->orWhere('consent_type', 'ilike', $search)
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('first_name', 'ilike', $search)
                            ->orWhere('last_name', 'ilike', $search)
                            ->orWhere('mrn', 'ilike', $search);
                    });
            });
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);

        return $query->paginate($perPage);
    }

    /**
     * Get all consents for a single patient.
     */
    public function getPatientConsents(string $patientId): array
    {
        $consents = PatientConsent::with(['witness:id,name', 'creator:id,name'])
            ->where('patient_id', $patientId)
            ->latest('granted_at')
            ->get();

        return [
            'patient_id' => $patientId,
            'total' => $consents->count(),
            'active_count' => $consents->where('is_active', true)->count(),
            'consents' => $consents,
        ];
    }
}
