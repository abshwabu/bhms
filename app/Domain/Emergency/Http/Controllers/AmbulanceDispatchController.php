<?php

namespace App\Domain\Emergency\Http\Controllers;

use App\Domain\Emergency\Http\Resources\AmbulanceDispatchResource;
use App\Domain\Emergency\Http\Resources\AmbulanceResource;
use App\Domain\Emergency\Models\Ambulance;
use App\Domain\Emergency\Models\AmbulanceDispatch;
use App\Domain\Emergency\Services\AmbulanceDispatchService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AmbulanceDispatchController extends Controller
{
    public function __construct(
        protected AmbulanceDispatchService $dispatchService
    ) {}

    /**
     * List hospital ambulance fleet.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ambulance::query()->with('activeDispatch');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('ambulance_type')) {
            $query->where('ambulance_type', $request->input('ambulance_type'));
        }

        $ambulances = $query->orderBy('vehicle_number', 'asc')->get();

        return ApiResponse::success(
            AmbulanceResource::collection($ambulances),
            'Ambulance fleet retrieved.'
        );
    }

    /**
     * Real-time fleet overview KPIs.
     */
    public function fleetOverview(Request $request): JsonResponse
    {
        $overview = $this->dispatchService->getFleetOverview($request->input('branch_id'));

        return ApiResponse::success($overview, 'Ambulance fleet statistics overview retrieved.');
    }

    /**
     * List ambulance dispatches.
     */
    public function dispatches(Request $request): JsonResponse
    {
        $query = AmbulanceDispatch::query()
            ->with(['ambulance', 'emergencyCase', 'dispatcher'])
            ->orderBy('dispatched_at', 'desc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $dispatches = $query->paginate($request->input('per_page', 25));

        return ApiResponse::paginated(
            $dispatches->through(fn($d) => new AmbulanceDispatchResource($d)),
            'Ambulance dispatches retrieved.'
        );
    }

    /**
     * Create ambulance dispatch mission.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ambulance_id' => ['required', 'uuid', 'exists:ambulances,id'],
            'emergency_case_id' => ['nullable', 'uuid', 'exists:emergency_cases,id'],
            'caller_name' => ['nullable', 'string', 'max:100'],
            'caller_phone' => ['nullable', 'string', 'max:50'],
            'pickup_address' => ['required', 'string'],
            'pickup_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'destination_address' => ['nullable', 'string'],
            'destination_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'priority' => ['required', 'string', 'in:code_red,code_yellow,code_green'],
            'nature_of_emergency' => ['required', 'string'],
            'patient_condition_notes' => ['nullable', 'string'],
            'force_dispatch' => ['nullable', 'boolean'],
        ]);

        $userId = $request->user()?->id;

        try {
            $dispatch = $this->dispatchService->createDispatch($validated, $userId);

            return ApiResponse::success(
                new AmbulanceDispatchResource($dispatch),
                "Ambulance {$dispatch->ambulance->call_sign} dispatched under call #{$dispatch->dispatch_number}.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'DISPATCH_ERROR', [], 422);
        }
    }

    /**
     * Near real-time status update for dispatch & ambulance fleet.
     * Acceptance criterion: Ambulance status (available/dispatched/en route/arrived) updates in near real-time.
     */
    public function updateStatus(Request $request, AmbulanceDispatch $dispatch): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:dispatched,en_route_scene,at_scene,en_route_hospital,arrived_hospital,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $updated = $this->dispatchService->updateStatus($dispatch, $validated['status'], $validated['notes'] ?? null);

            return ApiResponse::success(
                new AmbulanceDispatchResource($updated),
                "Dispatch {$dispatch->dispatch_number} status updated to '{$validated['status']}'."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'DISPATCH_STATUS_ERROR', [], 422);
        }
    }

    /**
     * Ingest live GPS coordinates & telemetry from ambulance mobile unit.
     */
    public function recordTelemetry(Request $request, Ambulance $ambulance): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'speed_kmh' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'fuel_percentage' => ['nullable', 'integer', 'between:0,100'],
        ]);

        $ambulance = $this->dispatchService->recordTelemetry($ambulance, $validated);

        return ApiResponse::success(
            new AmbulanceResource($ambulance),
            "Live GPS telemetry recorded for {$ambulance->call_sign}."
        );
    }
}
