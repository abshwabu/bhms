<?php

namespace App\Domain\IPD\Services;

use App\Domain\IPD\Models\Admission;
use App\Domain\IPD\Models\Bed;
use App\Domain\IPD\Models\Ward;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BedManagementService
{
    /**
     * Get visual Bed Map / Grid for wards and beds.
     */
    public function getBedMap(string $branchId, ?string $wardId = null): array
    {
        $query = Ward::with(['beds' => function ($q) {
            $q->where('is_active', true)
              ->with(['currentAdmission.patient'])
              ->orderBy('bed_number', 'asc');
        }])
        ->where('branch_id', $branchId)
        ->where('is_active', true);

        if ($wardId) {
            $query->where('id', $wardId);
        }

        $wards = $query->orderBy('name', 'asc')->get();

        return $wards->map(function (Ward $ward) {
            $totalBeds = $ward->beds->count();
            $occupiedBeds = $ward->beds->where('status', 'occupied')->count();
            $availableBeds = $ward->beds->where('status', 'available')->count();
            $cleaningBeds = $ward->beds->where('status', 'cleaning')->count();
            $maintenanceBeds = $ward->beds->where('status', 'maintenance')->count();
            $occupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0.0;

            return [
                'id' => $ward->id,
                'name' => $ward->name,
                'code' => $ward->code,
                'ward_type' => $ward->ward_type,
                'floor_number' => $ward->floor_number,
                'capacity' => $ward->capacity,
                'total_beds' => $totalBeds,
                'occupied_beds' => $occupiedBeds,
                'available_beds' => $availableBeds,
                'cleaning_beds' => $cleaningBeds,
                'maintenance_beds' => $maintenanceBeds,
                'occupancy_rate_percent' => $occupancyRate,
                'beds' => $ward->beds->map(function (Bed $bed) {
                    $admission = $bed->currentAdmission;
                    return [
                        'id' => $bed->id,
                        'bed_number' => $bed->bed_number,
                        'bed_type' => $bed->bed_type,
                        'status' => $bed->status,
                        'features' => $bed->features ?? [],
                        'daily_rate' => $bed->daily_rate_override ?? $bed->ward?->daily_rate ?? 0,
                        'patient' => $admission && $admission->patient ? [
                            'id' => $admission->patient->id,
                            'mrn' => $admission->patient->mrn,
                            'name' => $admission->patient->full_name,
                            'admission_number' => $admission->admission_number,
                            'admitted_at' => $admission->admitted_at?->toIso8601String(),
                            'diagnosis' => $admission->admitting_diagnosis,
                            'length_of_stay_days' => $admission->length_of_stay_days,
                        ] : null,
                    ];
                }),
            ];
        })->toArray();
    }

    /**
     * Compute Inpatient Bed Occupancy & Average Length of Stay (ALOS) Analytics.
     */
    public function getOccupancyAnalytics(string $branchId): array
    {
        $allBeds = Bed::where('branch_id', $branchId)->where('is_active', true)->get();
        $totalBeds = $allBeds->count();
        $occupiedBeds = $allBeds->where('status', 'occupied')->count();
        $availableBeds = $allBeds->where('status', 'available')->count();
        $cleaningBeds = $allBeds->where('status', 'cleaning')->count();
        $maintenanceBeds = $allBeds->where('status', 'maintenance')->count();

        $overallOccupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0.0;
        $currentInpatientCensus = Admission::where('branch_id', $branchId)->where('status', 'admitted')->count();

        // Calculate Average Length of Stay (ALOS) in days for discharged admissions
        $dischargedAdmissions = Admission::where('branch_id', $branchId)
            ->where('status', 'discharged')
            ->whereNotNull('discharged_at')
            ->whereNotNull('admitted_at')
            ->get();

        $averageLengthOfStayDays = 0.0;
        if ($dischargedAdmissions->isNotEmpty()) {
            $totalDays = $dischargedAdmissions->sum(function (Admission $adm) {
                $hours = $adm->admitted_at->diffInHours($adm->discharged_at);
                return max(1.0, $hours / 24);
            });
            $averageLengthOfStayDays = round($totalDays / $dischargedAdmissions->count(), 1);
        }

        // Breakdown per ward
        $wardsBreakdown = Ward::with('beds')
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->get()
            ->map(function (Ward $ward) {
                $wardTotal = $ward->beds->where('is_active', true)->count();
                $wardOccupied = $ward->beds->where('status', 'occupied')->count();
                $wardAvailable = $ward->beds->where('status', 'available')->count();
                return [
                    'ward_id' => $ward->id,
                    'name' => $ward->name,
                    'code' => $ward->code,
                    'ward_type' => $ward->ward_type,
                    'total_beds' => $wardTotal,
                    'occupied_beds' => $wardOccupied,
                    'available_beds' => $wardAvailable,
                    'occupancy_percent' => $wardTotal > 0 ? round(($wardOccupied / $wardTotal) * 100, 1) : 0.0,
                ];
            });

        return [
            'total_beds' => $totalBeds,
            'occupied_beds' => $occupiedBeds,
            'available_beds' => $availableBeds,
            'cleaning_beds' => $cleaningBeds,
            'maintenance_beds' => $maintenanceBeds,
            'occupancy_rate_percent' => $overallOccupancyRate,
            'current_inpatient_census' => $currentInpatientCensus,
            'average_length_of_stay_days' => $averageLengthOfStayDays,
            'total_discharged_patients_analyzed' => $dischargedAdmissions->count(),
            'wards_breakdown' => $wardsBreakdown,
        ];
    }
}
