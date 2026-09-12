<?php

namespace App\Domain\Clinical\Http\Controllers;

use App\Domain\Clinical\Http\Requests\StoreDiagnosisRequest;
use App\Domain\Clinical\Http\Resources\DiagnosisResource;
use App\Domain\Clinical\Http\Resources\Icd10CodeResource;
use App\Domain\Clinical\Models\Diagnosis;
use App\Domain\Clinical\Services\Icd10ValidationService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DiagnosisController extends Controller
{
    public function __construct(
        protected Icd10ValidationService $icd10Service
    ) {
    }

    /**
     * List patient diagnoses.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $query = Diagnosis::query()->with(['doctor', 'patient'])->orderBy('created_at', 'desc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('clinical_status')) {
            $query->where('clinical_status', $request->input('clinical_status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $diagnoses = $query->paginate($request->input('per_page', 20));

        $transformed = DiagnosisResource::collection($diagnoses->items())->resolve();
        $diagnoses->setCollection(collect($transformed));

        return ApiResponse::paginated($diagnoses, 'Diagnoses retrieved successfully.');
    }

    /**
     * Store diagnosis with strict ICD-10 code list validation.
     */
    public function store(StoreDiagnosisRequest $request): JsonResponse
    {
        $doctorId = $request->user()?->id ?? $request->input('doctor_id');
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);

        // Validate ICD-10 code against official standard code dictionary
        $icdCode = $this->icd10Service->validate($request->input('icd10_code'));

        $diagnosis = Diagnosis::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
            'patient_id' => $request->input('patient_id'),
            'ehr_record_id' => $request->input('ehr_record_id'),
            'appointment_id' => $request->input('appointment_id'),
            'admission_id' => $request->input('admission_id'),
            'doctor_id' => $doctorId,
            'icd10_code' => $icdCode->code,
            'icd10_title' => $icdCode->description,
            'type' => $request->input('type', 'primary'),
            'severity' => $request->input('severity', 'moderate'),
            'clinical_status' => $request->input('clinical_status', 'active'),
            'verification_status' => $request->input('verification_status', 'confirmed'),
            'onset_date' => $request->input('onset_date', now()->toDateString()),
            'resolved_date' => $request->input('resolved_date'),
            'notes' => $request->input('notes'),
        ]);

        return ApiResponse::success(
            new DiagnosisResource($diagnosis->fresh('doctor')),
            "Diagnosis '{$icdCode->code}' recorded successfully with ICD-10 validation.",
            201
        );
    }

    /**
     * Update diagnosis status (e.g. resolved, remission).
     */
    public function update(Diagnosis $diagnosis, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'clinical_status' => ['nullable', 'string', 'in:active,recurrence,remission,resolved'],
            'verification_status' => ['nullable', 'string', 'in:confirmed,provisional,differential,refuted'],
            'severity' => ['nullable', 'string', 'in:mild,moderate,severe'],
            'resolved_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $diagnosis->update($validated);

        return ApiResponse::success(
            new DiagnosisResource($diagnosis->fresh('doctor')),
            'Diagnosis status updated successfully.'
        );
    }

    /**
     * Soft delete diagnosis.
     */
    public function destroy(Diagnosis $diagnosis): JsonResponse
    {
        $diagnosis->delete();

        return ApiResponse::success(null, 'Diagnosis removed successfully.');
    }

    /**
     * Search ICD-10 codes for clinician autocomplete.
     */
    public function searchIcd10(Request $request): JsonResponse
    {
        $query = $request->input('q', $request->input('search', ''));
        $codes = $this->icd10Service->search($query, $request->input('limit', 25));

        return ApiResponse::success(
            Icd10CodeResource::collection($codes),
            'ICD-10 codes searched successfully.'
        );
    }

    /**
     * Validate an ICD-10 code candidate.
     */
    public function validateIcd10(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        try {
            $code = $this->icd10Service->validate($request->input('code'));

            return ApiResponse::success([
                'valid' => true,
                'code' => $code->code,
                'description' => $code->description,
                'category' => $code->category,
            ], 'ICD-10 code is valid.');
        } catch (ValidationException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                'INVALID_ICD10_CODE',
                $e->errors(),
                422
            );
        }
    }
}
