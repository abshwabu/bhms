<?php

namespace App\Domain\OPD\Http\Controllers;

use App\Domain\OPD\Http\Requests\AmendSoapNoteRequest;
use App\Domain\OPD\Http\Requests\StoreSoapNoteRequest;
use App\Domain\OPD\Http\Resources\ConsultationNoteResource;
use App\Domain\OPD\Models\ConsultationNote;
use App\Domain\OPD\Services\SoapNoteService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SoapNoteController extends Controller
{
    public function __construct(protected SoapNoteService $soapService)
    {
    }

    /**
     * List consultation notes for a patient.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate(['patient_id' => ['required', 'uuid', 'exists:patients,id']]);

        $notes = ConsultationNote::with(['doctor', 'parentNote'])
            ->where('patient_id', $request->input('patient_id'))
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success(
            ConsultationNoteResource::collection($notes),
            'Patient consultation notes retrieved.'
        );
    }

    /**
     * Create a SOAP consultation note (as draft or signed-off).
     */
    public function store(StoreSoapNoteRequest $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);
        $doctor = $request->user();

        $data = $request->validated();
        $note = $this->soapService->createDraft($data, $doctor, $branch);

        if ($request->boolean('sign_off_now')) {
            $note = $this->soapService->signOff($note, $doctor);
        }

        return ApiResponse::success(
            new ConsultationNoteResource($note->load(['patient', 'doctor'])),
            $note->is_signed_off ? 'Consultation note signed off and locked.' : 'Consultation note draft saved.',
            201
        );
    }

    /**
     * Retrieve single consultation note.
     */
    public function show(ConsultationNote $note): JsonResponse
    {
        return ApiResponse::success(
            new ConsultationNoteResource($note->load(['patient', 'doctor', 'parentNote', 'amendments'])),
            'Consultation note retrieved.'
        );
    }

    /**
     * Update draft consultation note.
     */
    public function update(StoreSoapNoteRequest $request, ConsultationNote $note): JsonResponse
    {
        try {
            $updated = $this->soapService->updateDraft($note, $request->validated(), $request->user());

            if ($request->boolean('sign_off_now')) {
                $updated = $this->soapService->signOff($updated, $request->user());
            }

            return ApiResponse::success(
                new ConsultationNoteResource($updated),
                'Draft note updated.'
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                'NOTE_LOCKED_IMMUTABLE',
                ['note' => [$e->getMessage()]],
                422
            );
        }
    }

    /**
     * Sign off on consultation note to make it legally immutable.
     */
    public function signOff(ConsultationNote $note): JsonResponse
    {
        $signed = $this->soapService->signOff($note, request()->user());

        return ApiResponse::success(
            new ConsultationNoteResource($signed),
            'Consultation note signed off successfully. It is now legally sealed and immutable.'
        );
    }

    /**
     * Amend an already signed-off consultation note (creates version N+1).
     */
    public function amend(AmendSoapNoteRequest $request, ConsultationNote $note): JsonResponse
    {
        try {
            $amendedNote = $this->soapService->amendSignedNote(
                $note,
                $request->validated(),
                $request->input('amendment_reason'),
                $request->user()
            );

            return ApiResponse::success(
                new ConsultationNoteResource($amendedNote),
                "Consultation note amended. Version {$amendedNote->version} created.",
                201
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 'AMENDMENT_FAILED', [], 422);
        }
    }
}
