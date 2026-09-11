<?php

namespace App\Domain\OPD\Http\Controllers;

use App\Domain\OPD\Actions\BookAppointmentAction;
use App\Domain\OPD\Actions\RescheduleAppointmentAction;
use App\Domain\OPD\Http\Requests\BookAppointmentRequest;
use App\Domain\OPD\Http\Requests\CancelAppointmentRequest;
use App\Domain\OPD\Http\Requests\RescheduleAppointmentRequest;
use App\Domain\OPD\Http\Resources\AppointmentResource;
use App\Domain\OPD\Models\Appointment;
use App\Domain\OPD\Services\QueueTokenService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class AppointmentController extends Controller
{
    public function __construct(
        protected BookAppointmentAction $bookAction,
        protected RescheduleAppointmentAction $rescheduleAction,
        protected QueueTokenService $queueTokenService
    ) {
    }

    /**
     * List and filter appointments.
     */
    public function index(Request $request): JsonResponse
    {
        $query = QueryBuilder::for(Appointment::class)
            ->allowedIncludes('patient', 'doctor', 'department', 'parentAppointment')
            ->allowedSorts('appointment_date', 'start_time', 'created_at')
            ->defaultSort('appointment_date', 'start_time');

        if ($request->filled('date')) {
            $query->whereDate('appointment_date', $request->input('date'));
        }

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->input('doctor_id'));
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $appointments = $query->paginate($request->input('per_page', 20));

        $transformed = AppointmentResource::collection($appointments->items())->resolve();
        $appointments->setCollection(collect($transformed));

        return ApiResponse::paginated($appointments, 'Appointments retrieved successfully.');
    }

    /**
     * Book a new appointment (online, in-person, or follow-up).
     * Prevents double-booking.
     */
    public function store(BookAppointmentRequest $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);

        try {
            $appointment = $this->bookAction->execute(
                $request->validated(),
                $branch,
                $request->user()?->id
            );

            return ApiResponse::success(
                new AppointmentResource($appointment),
                'Appointment successfully booked with reference: ' . $appointment->appointment_number,
                201
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                'SLOT_CONFLICT',
                ['slot' => [$e->getMessage()]],
                422
            );
        }
    }

    /**
     * Retrieve single appointment.
     */
    public function show(Appointment $appointment): JsonResponse
    {
        $appointment->load(['patient', 'doctor', 'department', 'parentAppointment', 'followUps']);

        return ApiResponse::success(
            new AppointmentResource($appointment),
            'Appointment details retrieved.'
        );
    }

    /**
     * Reschedule appointment.
     */
    public function reschedule(RescheduleAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        try {
            $updated = $this->rescheduleAction->execute(
                $appointment,
                $request->input('appointment_date'),
                $request->input('start_time'),
                $request->input('end_time')
            );

            return ApiResponse::success(
                new AppointmentResource($updated),
                'Appointment rescheduled successfully.'
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                'RESCHEDULE_FAILED',
                ['slot' => [$e->getMessage()]],
                422
            );
        }
    }

    /**
     * Cancel appointment.
     */
    public function cancel(CancelAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->input('cancellation_reason'),
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()?->id,
        ]);

        return ApiResponse::success(
            new AppointmentResource($appointment),
            'Appointment cancelled successfully.'
        );
    }

    /**
     * Check in patient and automatically issue a daily queue token.
     */
    public function checkIn(Appointment $appointment): JsonResponse
    {
        $appointment->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        $token = null;
        if ($appointment->department) {
            $branch = $appointment->branch ?: Branch::find(app('current_branch_id'));
            $token = $this->queueTokenService->issueToken(
                $appointment->department,
                $appointment->patient_id,
                $branch,
                $appointment->id,
                $appointment->doctor_id,
                'normal',
                $appointment->appointment_date->format('Y-m-d')
            );
        }

        return ApiResponse::success([
            'appointment' => new AppointmentResource($appointment),
            'queue_token' => $token ? $token->token_code : null,
        ], 'Patient checked in successfully.');
    }
}
