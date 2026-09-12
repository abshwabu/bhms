<?php

namespace App\Domain\Clinical\Services;

use App\Domain\Clinical\Models\EhrRecord;
use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Clinical\Models\Prescription;
use App\Domain\Clinical\Models\RadiologyOrder;
use App\Domain\OPD\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;

class DoctorDashboardService
{
    /**
     * Compile comprehensive doctor personal clinical dashboard data.
     */
    public function getDashboardData(string $doctorId, ?string $branchId = null): array
    {
        $today = Carbon::today();
        $doctor = User::findOrFail($doctorId);

        // 1. Scheduled / Queue Appointments for Today
        $appointmentQuery = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $today)
            ->with(['patient.allergies'])
            ->orderBy('start_time', 'asc');

        if ($branchId) {
            $appointmentQuery->where('branch_id', $branchId);
        }

        $todayAppointments = $appointmentQuery->get();

        $patientsSeenCount = $todayAppointments->whereIn('status', ['completed', 'in_consultation'])->count();
        $scheduledCount = $todayAppointments->count();

        // 2. Pending Chart Reviews (Draft EHR notes authored by this doctor awaiting final sign-off)
        $pendingChartsQuery = EhrRecord::where('author_id', $doctorId)
            ->where('status', 'draft')
            ->with(['patient'])
            ->orderBy('created_at', 'desc');

        if ($branchId) {
            $pendingChartsQuery->where('branch_id', $branchId);
        }

        $pendingCharts = $pendingChartsQuery->get();

        // 3. Diagnostic Results Awaiting Doctor Review / Sign-off
        // Completed lab orders not yet reviewed by doctor
        $pendingLabsQuery = LabOrder::where('ordering_doctor_id', $doctorId)
            ->where('status', 'completed')
            ->whereNull('reviewed_by_doctor_at')
            ->with(['patient'])
            ->orderBy('completed_at', 'desc');

        if ($branchId) {
            $pendingLabsQuery->where('branch_id', $branchId);
        }

        $pendingLabs = $pendingLabsQuery->get();

        // Completed radiology orders not yet reviewed by doctor
        $pendingRadsQuery = RadiologyOrder::where('ordering_doctor_id', $doctorId)
            ->whereIn('status', ['reported', 'performed'])
            ->whereNull('reviewed_by_doctor_at')
            ->with(['patient'])
            ->orderBy('reported_at', 'desc');

        if ($branchId) {
            $pendingRadsQuery->where('branch_id', $branchId);
        }

        $pendingRads = $pendingRadsQuery->get();

        // 4. Prescriptions Authored Today
        $prescriptionsTodayQuery = Prescription::where('doctor_id', $doctorId)
            ->whereDate('prescribed_at', $today);

        if ($branchId) {
            $prescriptionsTodayQuery->where('branch_id', $branchId);
        }

        $prescriptionsTodayCount = $prescriptionsTodayQuery->count();

        // Map Patient Roster for UI
        $patientRoster = $todayAppointments->map(function ($appt) {
            $patient = $appt->patient;
            return [
                'appointment_id' => $appt->id,
                'appointment_number' => $appt->appointment_number,
                'patient_id' => $appt->patient_id,
                'patient_name' => $patient ? trim("{$patient->first_name} {$patient->last_name}") : 'Unknown Patient',
                'mrn' => $patient?->mrn,
                'gender' => $patient?->gender,
                'date_of_birth' => $patient?->date_of_birth?->format('Y-m-d'),
                'start_time' => $appt->start_time,
                'end_time' => $appt->end_time,
                'status' => $appt->status,
                'reason_for_visit' => $appt->reason_for_visit,
                'allergies_count' => $patient?->allergies?->count() ?? 0,
                'allergies' => $patient?->allergies?->pluck('allergen')->all() ?? [],
            ];
        });

        // Map Pending Charts for UI
        $pendingChartsMapped = $pendingCharts->map(function ($chart) {
            $patient = $chart->patient;
            return [
                'id' => $chart->id,
                'patient_id' => $chart->patient_id,
                'patient_name' => $patient ? trim("{$patient->first_name} {$patient->last_name}") : 'Unknown Patient',
                'mrn' => $patient?->mrn,
                'title' => $chart->title,
                'record_type' => $chart->record_type,
                'category' => $chart->category,
                'created_at' => $chart->created_at->toIso8601String(),
                'version' => $chart->version,
            ];
        });

        // Map Diagnostic Results for Review
        $diagnosticReviews = collect();

        foreach ($pendingLabs as $lab) {
            $diagnosticReviews->push([
                'order_type' => 'lab',
                'id' => $lab->id,
                'order_number' => $lab->order_number,
                'patient_id' => $lab->patient_id,
                'patient_name' => $lab->patient ? trim("{$lab->patient->first_name} {$lab->patient->last_name}") : 'Unknown Patient',
                'test_or_modality' => $lab->test_type,
                'completed_at' => $lab->completed_at?->toIso8601String(),
                'results_summary' => $lab->results_summary,
                'abnormal_flags' => $lab->abnormal_flags,
                'priority' => $lab->priority,
            ]);
        }

        foreach ($pendingRads as $rad) {
            $diagnosticReviews->push([
                'order_type' => 'radiology',
                'id' => $rad->id,
                'order_number' => $rad->order_number,
                'patient_id' => $rad->patient_id,
                'patient_name' => $rad->patient ? trim("{$rad->patient->first_name} {$rad->patient->last_name}") : 'Unknown Patient',
                'test_or_modality' => "{$rad->modality} ({$rad->procedure_name})",
                'completed_at' => $rad->reported_at?->toIso8601String(),
                'results_summary' => $rad->impression ?? $rad->findings,
                'abnormal_flags' => false,
                'priority' => $rad->priority,
            ]);
        }

        return [
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'email' => $doctor->email,
            ],
            'metrics' => [
                'patients_seen_today' => $patientsSeenCount,
                'scheduled_today' => $scheduledCount,
                'pending_chart_reviews' => $pendingCharts->count(),
                'pending_diagnostic_reviews' => $diagnosticReviews->count(),
                'active_prescriptions_today' => $prescriptionsTodayCount,
            ],
            'patient_roster' => $patientRoster,
            'pending_chart_reviews' => $pendingChartsMapped,
            'pending_diagnostic_reviews' => $diagnosticReviews->values()->all(),
        ];
    }
}
