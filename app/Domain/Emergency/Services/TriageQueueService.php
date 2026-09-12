<?php

namespace App\Domain\Emergency\Services;

use App\Domain\Emergency\Models\EmergencyCase;
use App\Domain\Emergency\Models\TriageRecord;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TriageQueueService
{
    /**
     * ESI severity scores:
     * Level 1: Resuscitation (100) - Immediate life-saving intervention
     * Level 2: Emergent (80) - High risk, confused/lethargic, severe pain, danger vitals
     * Level 3: Urgent (60) - Stable vitals, needs 2+ hospital resources
     * Level 4: Less Urgent (40) - Needs 1 hospital resource
     * Level 5: Non-Urgent (20) - Needs 0 hospital resources
     */
    protected array $esiScores = [
        1 => 100,
        2 => 80,
        3 => 60,
        4 => 40,
        5 => 20,
    ];

    /**
     * Create initial emergency case record.
     */
    public function createEmergencyCase(array $data, ?string $userId = null): EmergencyCase
    {
        $arrivalDatetime = !empty($data['arrival_datetime'])
            ? Carbon::parse($data['arrival_datetime'])
            : Carbon::now();

        $esi = (int) ($data['initial_triage_esi'] ?? 3);
        if ($esi < 1 || $esi > 5) {
            $esi = 3;
        }

        $caseNumber = $this->generateCaseNumber($data['branch_id']);

        return EmergencyCase::create([
            'organization_id' => $data['organization_id'],
            'branch_id' => $data['branch_id'],
            'case_number' => $caseNumber,
            'patient_id' => $data['patient_id'] ?? null,
            'patient_temp_name' => $data['patient_temp_name'] ?? null,
            'patient_gender' => $data['patient_gender'] ?? 'unknown',
            'patient_estimated_age' => $data['patient_estimated_age'] ?? null,
            'arrival_mode' => $data['arrival_mode'] ?? 'ambulance',
            'ambulance_dispatch_id' => $data['ambulance_dispatch_id'] ?? null,
            'arrival_datetime' => $arrivalDatetime,
            'chief_complaint' => $data['chief_complaint'],
            'initial_triage_esi' => $esi,
            'current_esi_level' => $esi,
            'priority_score' => $this->esiScores[$esi] ?? 60,
            'status' => 'triaged',
            'assigned_doctor_id' => $data['assigned_doctor_id'] ?? null,
            'assigned_nurse_id' => $userId ?? ($data['assigned_nurse_id'] ?? null),
        ]);
    }

    /**
     * Perform structured triage assessment.
     * Evaluates quantitative vitals, red flags, and sets automated queue priority.
     * Acceptance criterion: Triage severity level determines queue priority automatically.
     */
    public function performTriage(EmergencyCase $case, array $data, string $triagedByUserId): TriageRecord
    {
        $vitals = $data['vital_signs'] ?? [];
        $redFlags = $data['red_flags'] ?? [];

        // Determine if danger zone vitals are present
        $isDangerZone = $this->checkDangerZoneVitals($vitals);

        $requestedEsi = isset($data['esi_level']) ? (int) $data['esi_level'] : null;

        // Auto-escalate ESI level if critical red flags or danger vitals detected
        $calculatedEsi = $this->calculateEsiLevel($requestedEsi, $vitals, $redFlags, $isDangerZone);

        $severityLabels = [
            1 => 'Resuscitation (Immediate Life Threat)',
            2 => 'Emergent (High Risk / Danger Vitals)',
            3 => 'Urgent (Multi-Resource Stable)',
            4 => 'Less Urgent (Single Resource)',
            5 => 'Non-Urgent (No Resources)',
        ];

        $reassessmentMinutes = match ($calculatedEsi) {
            1 => 0,   // Continuous monitoring
            2 => 15,  // Every 15 mins
            3 => 30,  // Every 30 mins
            4 => 60,  // Hourly
            5 => 120, // 2 hours
            default => 30,
        };

        $triagedAt = Carbon::now();
        $reassessmentDue = $reassessmentMinutes > 0 ? $triagedAt->copy()->addMinutes($reassessmentMinutes) : null;

        $triageRecord = TriageRecord::create([
            'organization_id' => $case->organization_id,
            'branch_id' => $case->branch_id,
            'emergency_case_id' => $case->id,
            'triaged_by' => $triagedByUserId,
            'triaged_at' => $triagedAt,
            'esi_level' => $calculatedEsi,
            'severity_label' => $severityLabels[$calculatedEsi] ?? 'Urgent',
            'triage_category' => $data['triage_category'] ?? 'general',
            'vital_signs' => $vitals,
            'is_danger_zone_vitals' => $isDangerZone,
            'red_flags' => $redFlags,
            'assessment_notes' => $data['assessment_notes'] ?? 'Emergency clinical triage completed.',
            'reassessment_interval_minutes' => $reassessmentMinutes,
            'reassessment_due_at' => $reassessmentDue,
        ]);

        // Automatically update EmergencyCase priority and queue placement
        $case->update([
            'current_esi_level' => $calculatedEsi,
            'priority_score' => $this->esiScores[$calculatedEsi] ?? 60,
            'status' => 'triaged',
        ]);

        return $triageRecord->load('triagedBy');
    }

    /**
     * Check if vitals fall in the acute pediatric/adult danger zones.
     */
    public function checkDangerZoneVitals(array $v): bool
    {
        $hr = isset($v['heart_rate']) ? (float) $v['heart_rate'] : null;
        $rr = isset($v['respiratory_rate']) ? (float) $v['respiratory_rate'] : null;
        $spo2 = isset($v['spo2']) ? (float) $v['spo2'] : null;
        $sys = isset($v['bp_systolic']) ? (float) $v['bp_systolic'] : null;
        $gcs = isset($v['gcs']) ? (int) $v['gcs'] : null;

        if ($spo2 !== null && $spo2 < 90) return true;
        if ($hr !== null && ($hr < 40 || $hr > 130)) return true;
        if ($rr !== null && ($rr < 10 || $rr > 32)) return true;
        if ($sys !== null && ($sys < 85 || $sys > 210)) return true;
        if ($gcs !== null && $gcs < 13) return true;

        return false;
    }

    /**
     * Compute automated ESI level based on red flags, danger vitals, and nurse inputs.
     */
    protected function calculateEsiLevel(?int $explicitEsi, array $vitals, array $redFlags, bool $isDangerZone): int
    {
        // Immediate resuscitation triggers -> Level 1
        $level1Triggers = ['cardiac_arrest', 'respiratory_arrest', 'severe_anaphylaxis', 'unresponsive', 'major_trauma'];
        foreach ($level1Triggers as $trigger) {
            if (in_array($trigger, $redFlags)) {
                return 1;
            }
        }

        if ($explicitEsi === 1) {
            return 1;
        }

        // High risk or danger zone vitals -> Level 2
        $level2Triggers = ['stemi_suspected', 'acute_stroke', 'severe_sepsis', 'active_hemorrhage', 'severe_respiratory_distress'];
        foreach ($level2Triggers as $trigger) {
            if (in_array($trigger, $redFlags)) {
                return 2;
            }
        }

        if ($isDangerZone) {
            // Danger zone vitals automatically escalate to at least Level 2
            return min($explicitEsi ?? 2, 2);
        }

        return $explicitEsi ?? 3;
    }

    /**
     * Fetch active triage queue sorted automatically by severity level and arrival time.
     * Acceptance criterion: Triage severity level determines queue priority automatically.
     */
    public function getActiveTriageQueue(?string $branchId = null): Collection
    {
        $query = EmergencyCase::query()
            ->with([
                'patient',
                'latestTriageRecord',
                'bed.ward',
                'doctor',
                'nurse',
                'ambulanceDispatch.ambulance',
            ])
            ->activeTriageQueue();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    protected function generateCaseNumber(string $branchId): string
    {
        $today = Carbon::today()->format('Ymd');
        $count = EmergencyCase::where('branch_id', $branchId)
            ->whereDate('arrival_datetime', Carbon::today())
            ->count() + 1;

        return sprintf('ER-%s-%04d', $today, $count);
    }
}
