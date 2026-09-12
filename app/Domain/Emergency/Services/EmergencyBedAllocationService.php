<?php

namespace App\Domain\Emergency\Services;

use App\Domain\Emergency\Models\EmergencyBedAllocation;
use App\Domain\Emergency\Models\EmergencyCase;
use App\Domain\IPD\Models\Bed;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class EmergencyBedAllocationService
{
    /**
     * Allocate hospital bed to emergency case.
     * Can override standard queue if bed is reserved/occupied, with mandatory logged clinical justification.
     * Acceptance criterion: Emergency bed allocation can override standard bed queue with a logged justification.
     */
    public function allocateBed(
        EmergencyCase $case,
        string $bedId,
        string $allocatedByUserId,
        bool $isOverride = false,
        ?string $overrideReason = null
    ): EmergencyBedAllocation {
        return DB::transaction(function () use ($case, $bedId, $allocatedByUserId, $isOverride, $overrideReason) {
            $bed = Bed::with('ward')->findOrFail($bedId);

            if (!$bed->is_active) {
                throw new DomainException("Bed {$bed->bed_number} is deactivated and cannot be assigned.");
            }

            // Check if bed is available
            $isBedAvailable = ($bed->status === 'available');

            if (!$isBedAvailable) {
                if (!$isOverride) {
                    throw new DomainException(
                        "Bed {$bed->bed_number} is currently marked as '{$bed->status}'. " .
                        "To assign this bed to Emergency Case {$case->case_number}, an emergency priority override with logged clinical justification is required."
                    );
                }

                // Mandatory justification validation for priority override
                if (empty($overrideReason) || trim($overrideReason) === '') {
                    throw new DomainException("Priority bed override requires a mandatory logged clinical justification.");
                }
            }

            // Determine priority tier
            $priorityTier = match ($case->current_esi_level) {
                1 => $isOverride ? 'ESI-1 Resuscitation Immediate Override' : 'ESI-1 Resuscitation Priority',
                2 => $isOverride ? 'ESI-2 Emergent Priority Override' : 'ESI-2 Emergent High Priority',
                3 => $isOverride ? 'ESI-3 Urgent Queue Override' : 'ESI-3 Standard ER Allocation',
                default => $isOverride ? 'Emergency Queue Override' : 'Standard Bed Allocation',
            };

            $now = Carbon::now();

            // Release any previously allocated bed for this case
            if ($case->assigned_bed_id && $case->assigned_bed_id !== $bed->id) {
                $this->releasePreviousBed($case, $now);
            }

            // Create logged allocation record
            $allocation = EmergencyBedAllocation::create([
                'organization_id' => $case->organization_id,
                'branch_id' => $case->branch_id,
                'emergency_case_id' => $case->id,
                'bed_id' => $bed->id,
                'is_override' => $isOverride,
                'override_reason' => $isOverride ? trim($overrideReason) : null,
                'priority_tier' => $priorityTier,
                'allocated_by' => $allocatedByUserId,
                'allocated_at' => $now,
            ]);

            // Update bed status in beds table
            $bed->update([
                'status' => 'occupied',
            ]);

            // Update emergency case
            $case->update([
                'assigned_bed_id' => $bed->id,
                'bed_assigned_at' => $now,
                'status' => 'bed_assigned',
            ]);

            return $allocation->load(['bed.ward', 'emergencyCase', 'allocatedByUser']);
        });
    }

    /**
     * Release bed upon discharge, IPD admission transfer, or case completion.
     */
    public function releaseBed(EmergencyCase $case, ?string $notes = null): void
    {
        if (!$case->assigned_bed_id) {
            return;
        }

        DB::transaction(function () use ($case, $notes) {
            $now = Carbon::now();

            // Close active allocation
            EmergencyBedAllocation::where('emergency_case_id', $case->id)
                ->where('bed_id', $case->assigned_bed_id)
                ->whereNull('released_at')
                ->update([
                    'released_at' => $now,
                    'release_notes' => $notes ?? 'Emergency case bed cleared.',
                ]);

            // Reset bed status to available
            $bed = Bed::find($case->assigned_bed_id);
            if ($bed) {
                $bed->update(['status' => 'available']);
            }

            $case->update([
                'assigned_bed_id' => null,
            ]);
        });
    }

    protected function releasePreviousBed(EmergencyCase $case, Carbon $now): void
    {
        EmergencyBedAllocation::where('emergency_case_id', $case->id)
            ->where('bed_id', $case->assigned_bed_id)
            ->whereNull('released_at')
            ->update([
                'released_at' => $now,
                'release_notes' => 'Patient transferred to alternative bed.',
            ]);

        $prevBed = Bed::find($case->assigned_bed_id);
        if ($prevBed) {
            $prevBed->update(['status' => 'available']);
        }
    }
}
