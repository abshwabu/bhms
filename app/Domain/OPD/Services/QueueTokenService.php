<?php

namespace App\Domain\OPD\Services;

use App\Domain\OPD\Models\Department;
use App\Domain\OPD\Models\QueueToken;
use App\Domain\Shared\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QueueTokenService
{
    /**
     * Issue a queue token that resets daily per department.
     * Guaranteed unique sequential number 1, 2, 3... per day per department.
     */
    public function issueToken(
        Department $department,
        string $patientId,
        Branch $branch,
        ?string $appointmentId = null,
        ?string $doctorId = null,
        string $priority = 'normal',
        ?string $tokenDate = null
    ): QueueToken {
        $date = $tokenDate ?: Carbon::today()->toDateString();

        return DB::transaction(function () use ($department, $patientId, $branch, $appointmentId, $doctorId, $priority, $date) {
            // Lock rows for this branch, department, and date to calculate next token_number
            $maxTokenNumber = QueueToken::where('branch_id', $branch->id)
                ->where('department_id', $department->id)
                ->whereDate('token_date', $date)
                ->orderBy('token_number', 'desc')
                ->lockForUpdate()
                ->value('token_number');

            $nextNumber = ($maxTokenNumber ?? 0) + 1;
            $deptCode = strtoupper($department->code ?: 'OPD');
            $tokenCode = sprintf('%s-%03d', $deptCode, $nextNumber);

            $token = QueueToken::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'appointment_id' => $appointmentId,
                'token_date' => $date,
                'token_number' => $nextNumber,
                'token_code' => $tokenCode,
                'status' => 'waiting',
                'priority' => $priority,
            ]);

            return $token->load(['patient', 'department', 'doctor']);
        });
    }

    /**
     * Call the next waiting patient in the department queue.
     */
    public function callNext(Department $department, string $counterRoom, ?string $doctorId = null): ?QueueToken
    {
        return DB::transaction(function () use ($department, $counterRoom, $doctorId) {
            $token = QueueToken::where('department_id', $department->id)
                ->whereDate('token_date', Carbon::today()->toDateString())
                ->where('status', 'waiting')
                ->orderByRaw("CASE WHEN priority = 'emergency' THEN 1 WHEN priority = 'urgent' THEN 2 ELSE 3 END")
                ->orderBy('token_number', 'asc')
                ->lockForUpdate()
                ->first();

            if (!$token) {
                return null;
            }

            $token->update([
                'status' => 'called',
                'counter_room' => $counterRoom,
                'doctor_id' => $doctorId ?: $token->doctor_id,
                'called_at' => now(),
            ]);

            return $token->fresh(['patient', 'department', 'doctor']);
        });
    }

    /**
     * Get live queue feed for waiting room TV screens.
     */
    public function getWaitingRoomDisplay(string $branchId, ?string $departmentId = null): array
    {
        $today = Carbon::today()->toDateString();

        $query = QueueToken::with(['patient', 'department', 'doctor'])
            ->where('branch_id', $branchId)
            ->whereDate('token_date', $today);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $allTokens = $query->orderBy('token_number', 'asc')->get();

        $nowCalling = $allTokens->filter(fn ($t) => in_array($t->status, ['called', 'in_consultation']))->values();
        $waiting = $allTokens->filter(fn ($t) => $t->status === 'waiting')->values();
        $completed = $allTokens->filter(fn ($t) => $t->status === 'completed')->values();

        return [
            'date' => $today,
            'branch_id' => $branchId,
            'calling' => $nowCalling->map(fn ($t) => [
                'token_code' => $t->token_code,
                'patient_name' => $t->patient ? $t->patient->full_name : 'Patient',
                'department_name' => $t->department ? $t->department->name : '',
                'counter_room' => $t->counter_room ?? 'Consultation Desk',
                'status' => $t->status,
                'called_at' => $t->called_at?->format('h:i A'),
            ]),
            'now_calling' => $nowCalling->map(fn ($t) => [
                'token_code' => $t->token_code,
                'patient_name' => $t->patient ? $t->patient->full_name : 'Patient',
                'department_name' => $t->department ? $t->department->name : '',
                'counter_room' => $t->counter_room ?? 'Consultation Desk',
                'status' => $t->status,
                'called_at' => $t->called_at?->format('h:i A'),
            ]),
            'waiting_count' => $waiting->count(),
            'completed_count' => $completed->count(),
            'upcoming_queue' => $waiting->take(10)->map(fn ($t) => [
                'token_code' => $t->token_code,
                'priority' => $t->priority,
                'department_name' => $t->department ? $t->department->name : '',
            ]),
        ];
    }
}
