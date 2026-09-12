<?php

namespace App\Domain\IPD\Http\Controllers;

use App\Domain\IPD\Http\Requests\TransferBedRequest;
use App\Domain\IPD\Http\Resources\BedTransferResource;
use App\Domain\IPD\Models\Admission;
use App\Domain\IPD\Models\BedTransfer;
use App\Domain\IPD\Services\AdtService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BedTransferController extends Controller
{
    public function __construct(
        protected AdtService $adtService
    ) {
    }

    /**
     * List all bed transfers for an admission or branch.
     */
    public function index(Request $request): JsonResponse
    {
        $query = BedTransfer::with([
            'patient',
            'fromWard',
            'fromBed',
            'toWard',
            'toBed',
            'transferredByUser',
        ])->orderBy('transferred_at', 'desc');

        if ($request->filled('admission_id')) {
            $query->where('admission_id', $request->input('admission_id'));
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        return ApiResponse::success(
            BedTransferResource::collection($query->get()),
            'Bed transfer audit trail retrieved successfully.'
        );
    }

    /**
     * Perform an inpatient bed transfer with complete audit logging (timestamp + staff ID).
     */
    public function store(TransferBedRequest $request, Admission $admission): JsonResponse
    {
        try {
            $transfer = $this->adtService->transferBed(
                $admission,
                $request->input('to_bed_id'),
                $request->input('reason'),
                $request->user()
            );

            return ApiResponse::success(
                new BedTransferResource($transfer),
                "Patient successfully transferred to bed #{$transfer->toBed?->bed_number} in ward '{$transfer->toWard?->name}'.",
                201
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                'TRANSFER_FAILED',
                ['bed' => [$e->getMessage()]],
                422
            );
        }
    }
}
