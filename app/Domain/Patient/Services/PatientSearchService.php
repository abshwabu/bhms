<?php

namespace App\Domain\Patient\Services;

use App\Domain\Patient\Models\Patient;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PatientSearchService
{
    /**
     * Search and filter patient records with high performance (<500ms for 100k+ rows).
     */
    public function search(array $params = [], int $defaultPerPage = 15): LengthAwarePaginator
    {
        $perPage = min((int) ($params['per_page'] ?? $defaultPerPage), 100);

        $query = QueryBuilder::for(Patient::class)
            ->allowedIncludes(
                'insurance',
                'relationships',
                'relationships.relatedPatient',
                'medicalHistory',
                'allergies'
            )
            ->allowedSorts(
                'created_at',
                'first_name',
                'last_name',
                'mrn',
                'date_of_birth'
            )
            ->defaultSort('-created_at');

        // Apply search term across ID, Name, Phone, National ID
        if (!empty($params['search'])) {
            $query->search($params['search']);
        }

        // Apply explicit exact/categorical filters
        if (!empty($params['mrn'])) {
            $query->where('mrn', 'ILIKE', '%' . trim($params['mrn']) . '%');
        }

        if (!empty($params['national_id'])) {
            $query->where('national_id', trim($params['national_id']));
        }

        if (!empty($params['phone'])) {
            $phone = preg_replace('/[^\d]/', '', $params['phone']);
            $query->where(function ($q) use ($phone) {
                $q->where('phone', 'ILIKE', "%{$phone}%")
                  ->orWhere('alternate_phone', 'ILIKE', "%{$phone}%");
            });
        }

        if (!empty($params['registration_type'])) {
            $query->where('registration_type', $params['registration_type']);
        }

        if (!empty($params['gender'])) {
            $query->where('gender', $params['gender']);
        }

        if (!empty($params['blood_group'])) {
            $query->where('blood_group', $params['blood_group']);
        }

        if (isset($params['is_active'])) {
            $query->where('is_active', filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
