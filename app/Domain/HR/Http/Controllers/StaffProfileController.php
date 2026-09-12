<?php

namespace App\Domain\HR\Http\Controllers;

use App\Domain\HR\Http\Resources\CredentialResource;
use App\Domain\HR\Http\Resources\StaffResource;
use App\Domain\HR\Http\Resources\StaffRoleResource;
use App\Domain\HR\Models\Staff;
use App\Domain\HR\Models\StaffRole;
use App\Domain\HR\Services\AttendancePerformanceService;
use App\Domain\HR\Services\CredentialService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use App\Domain\HR\Services\LeaveManagementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffProfileController extends Controller
{
    public function __construct(
        protected CredentialService $credentialService,
        protected AttendancePerformanceService $performanceService,
        protected LeaveManagementService $leaveService
    ) {}

    /**
     * List hospital staff directory.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Staff::query()
            ->with('role')
            ->orderBy('last_name', 'asc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->input('employment_type'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'ILIKE', "%{$term}%")
                  ->orWhere('last_name', 'ILIKE', "%{$term}%")
                  ->orWhere('employee_id', 'ILIKE', "%{$term}%")
                  ->orWhere('email', 'ILIKE', "%{$term}%");
            });
        }

        $staff = $query->paginate($request->input('per_page', 25));

        return ApiResponse::paginated(
            $staff->through(fn($s) => new StaffResource($s)),
            'Staff directory retrieved.'
        );
    }

    /**
     * Create staff profile.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'staff_role_id' => ['required', 'uuid', 'exists:staff_roles,id'],
            'employee_id' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'department' => ['required', 'string', 'max:50'],
            'designation' => ['required', 'string', 'max:100'],
            'employment_type' => ['required', 'string', 'in:full_time,part_time,contract,locum,intern'],
            'joining_date' => ['required', 'date'],
            'hourly_rate_cents' => ['nullable', 'integer', 'min:0'],
            'monthly_salary_cents' => ['nullable', 'integer', 'min:0'],
            'bank_details' => ['nullable', 'array'],
            'emergency_contact' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $staff = Staff::create($validated);

        // Automatically initialize default leave balances for current calendar year
        $this->leaveService->initializeYearlyBalances($staff, (int) Carbon::now()->year);

        return ApiResponse::success(
            new StaffResource($staff->load('role')),
            "Staff profile for {$staff->full_name} created successfully.",
            201
        );
    }

    /**
     * Show single staff details with credentials.
     */
    public function show(Staff $staff): JsonResponse
    {
        $staff->load(['role', 'credentials', 'leaveBalances']);

        return ApiResponse::success(
            new StaffResource($staff),
            'Staff profile details retrieved.'
        );
    }

    /**
     * Update staff profile.
     */
    public function update(Request $request, Staff $staff): JsonResponse
    {
        $validated = $request->validate([
            'staff_role_id' => ['sometimes', 'required', 'uuid', 'exists:staff_roles,id'],
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'department' => ['sometimes', 'required', 'string', 'max:50'],
            'designation' => ['sometimes', 'required', 'string', 'max:100'],
            'employment_type' => ['sometimes', 'required', 'string'],
            'status' => ['nullable', 'string', 'in:active,on_leave,suspended,resigned,terminated'],
            'hourly_rate_cents' => ['nullable', 'integer', 'min:0'],
            'monthly_salary_cents' => ['nullable', 'integer', 'min:0'],
            'bank_details' => ['nullable', 'array'],
            'emergency_contact' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $staff->update($validated);

        return ApiResponse::success(
            new StaffResource($staff->load('role')),
            "Staff profile updated successfully."
        );
    }

    /**
     * List staff roles / designations.
     */
    public function listRoles(Request $request): JsonResponse
    {
        $roles = StaffRole::query()->orderBy('name', 'asc')->get();

        return ApiResponse::success(
            StaffRoleResource::collection($roles),
            'Staff roles retrieved.'
        );
    }

    /**
     * Record medical license or professional credential.
     */
    public function recordCredential(Request $request, Staff $staff): JsonResponse
    {
        $validated = $request->validate([
            'credential_type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:150'],
            'license_number' => ['required', 'string', 'max:100'],
            'issuing_authority' => ['required', 'string', 'max:150'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date'],
            'document_url' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $credential = $this->credentialService->recordCredential($staff, $validated);

            return ApiResponse::success(
                new CredentialResource($credential->load('staff')),
                "Credential '{$credential->title}' recorded for {$staff->full_name}.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'CREDENTIAL_ERROR', [], 422);
        }
    }

    /**
     * Credential expiry alert trigger.
     * Acceptance criterion: Credential expiry (e.g., license renewal) triggers an alert to HR.
     */
    public function credentialAlerts(Request $request): JsonResponse
    {
        $alerts = $this->credentialService->getExpiringCredentialAlerts(
            $request->input('branch_id'),
            (int) $request->input('upcoming_days', 30)
        );

        return ApiResponse::success(
            $alerts,
            "Found {$alerts['total_alerts_count']} license/credential expiry alerts."
        );
    }

    /**
     * Staff performance tracking KPIs (attendance, punctuality, patients seen).
     */
    public function performance(Request $request, Staff $staff): JsonResponse
    {
        $kpis = $this->performanceService->getStaffPerformance(
            $staff,
            $request->input('start_date'),
            $request->input('end_date')
        );

        return ApiResponse::success($kpis, 'Staff performance KPIs calculated.');
    }

    /**
     * Optional payroll preview integration hook.
     */
    public function payrollPreview(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $preview = $this->performanceService->getPayrollPreview($request->input('branch_id'), $year, $month);

        return ApiResponse::success($preview, 'Payroll calculation preview generated.');
    }
}
