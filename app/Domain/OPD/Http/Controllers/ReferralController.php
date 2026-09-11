<?php

namespace App\Domain\OPD\Http\Controllers;

use App\Domain\OPD\Http\Requests\StoreReferralRequest;
use App\Domain\OPD\Http\Resources\ReferralResource;
use App\Domain\OPD\Models\Referral;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReferralController extends Controller
{
    /**
     * List referrals with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Referral::with(['patient', 'referringDoctor', 'fromDepartment', 'toDepartment', 'toDoctor'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('referral_type')) {
            $query->where('referral_type', $request->input('referral_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('to_department_id')) {
            $query->where('to_department_id', $request->input('to_department_id'));
        }

        $referrals = $query->paginate($request->input('per_page', 20));

        return ApiResponse::paginated(
            $referrals,
            ReferralResource::class,
            'Referrals retrieved successfully.'
        );
    }

    /**
     * Create a new patient referral (inter-department or external facility).
     */
    public function store(StoreReferralRequest $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);

        $data = $request->validated();
        $data['organization_id'] = $branch->organization_id;
        $data['branch_id'] = $branch->id;
        $data['referring_doctor_id'] = $request->user()->id;
        $data['status'] = 'pending';

        $referral = Referral::create($data);

        return ApiResponse::success(
            new ReferralResource($referral->load(['patient', 'referringDoctor', 'fromDepartment', 'toDepartment', 'toDoctor'])),
            'Referral created successfully.',
            201
        );
    }

    /**
     * Retrieve single referral details.
     */
    public function show(Referral $referral): JsonResponse
    {
        return ApiResponse::success(
            new ReferralResource($referral->load(['patient', 'referringDoctor', 'fromDepartment', 'toDepartment', 'toDoctor', 'consultationNote'])),
            'Referral details retrieved.'
        );
    }

    /**
     * Update referral status (e.g., accepted, completed, rejected).
     */
    public function updateStatus(Request $request, Referral $referral): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'accepted', 'completed', 'cancelled', 'rejected'])],
        ]);

        $referral->update(['status' => $validated['status']]);

        return ApiResponse::success(
            new ReferralResource($referral->fresh(['patient', 'referringDoctor', 'fromDepartment', 'toDepartment', 'toDoctor'])),
            "Referral status updated to {$validated['status']}."
        );
    }
}
