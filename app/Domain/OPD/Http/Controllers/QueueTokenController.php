<?php

namespace App\Domain\OPD\Http\Controllers;

use App\Domain\OPD\Http\Requests\StoreQueueTokenRequest;
use App\Domain\OPD\Http\Resources\QueueTokenResource;
use App\Domain\OPD\Models\Department;
use App\Domain\OPD\Models\QueueToken;
use App\Domain\OPD\Services\QueueTokenService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueTokenController extends Controller
{
    public function __construct(protected QueueTokenService $queueService)
    {
    }

    /**
     * List tokens for today.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $query = QueueToken::with(['patient', 'department', 'doctor'])
            ->where('branch_id', $branchId)
            ->whereDate('token_date', now()->toDateString())
            ->orderBy('token_number', 'asc');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return ApiResponse::success(
            QueueTokenResource::collection($query->get()),
            'Queue tokens retrieved.'
        );
    }

    /**
     * Issue a new walk-in or checked-in queue token.
     */
    public function store(StoreQueueTokenRequest $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);
        $department = Department::findOrFail($request->input('department_id'));

        $token = $this->queueService->issueToken(
            $department,
            $request->input('patient_id'),
            $branch,
            $request->input('appointment_id'),
            $request->input('doctor_id'),
            $request->input('priority', 'normal')
        );

        return ApiResponse::success(
            new QueueTokenResource($token),
            "Token {$token->token_code} issued successfully.",
            201
        );
    }

    /**
     * Call next waiting patient in the queue.
     */
    public function callNext(Request $request): JsonResponse
    {
        $request->validate([
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'counter_room' => ['required', 'string'],
        ]);

        $department = Department::findOrFail($request->input('department_id'));

        $token = $this->queueService->callNext(
            $department,
            $request->input('counter_room'),
            $request->user()?->id
        );

        if (!$token) {
            return ApiResponse::success(null, 'No waiting patients in this department queue.');
        }

        return ApiResponse::success(
            new QueueTokenResource($token),
            "Now calling token {$token->token_code} to {$token->counter_room}."
        );
    }

    /**
     * Update queue token status (in_consultation, completed, skipped).
     */
    public function updateStatus(Request $request, QueueToken $token): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:in_consultation,completed,skipped,cancelled'],
        ]);

        $status = $request->input('status');
        $updates = ['status' => $status];

        if ($status === 'in_consultation') {
            $updates['consultation_started_at'] = now();
        } elseif ($status === 'completed') {
            $updates['completed_at'] = now();
        }

        $token->update($updates);

        return ApiResponse::success(
            new QueueTokenResource($token),
            "Token {$token->token_code} updated to {$status}."
        );
    }

    /**
     * Waiting room screen feed endpoint (for TV monitors in reception/waiting bays).
     */
    public function display(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $displayData = $this->queueService->getWaitingRoomDisplay(
            $branchId,
            $request->input('department_id')
        );

        return ApiResponse::success($displayData, 'Live waiting room queue feed.');
    }
}
