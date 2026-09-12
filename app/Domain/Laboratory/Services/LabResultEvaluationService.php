<?php

namespace App\Domain\Laboratory\Services;

use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Laboratory\Models\LabResult;
use App\Domain\Laboratory\Models\LabResultItem;
use App\Domain\Laboratory\Models\ReferenceRange;
use App\Domain\Patient\Models\Patient;
use Illuminate\Support\Facades\Log;

class LabResultEvaluationService
{
    /**
     * Evaluate parameter values against reference ranges and create/update result items.
     *
     * @param LabResult $labResult
     * @param array $parameters Array of [['parameter_name' => ..., 'measured_value' => ...]]
     * @return array Array of evaluated item models
     */
    public function evaluateAndSaveItems(LabResult $labResult, array $parameters): array
    {
        $patient = $labResult->patient;
        $gender = strtolower($patient?->gender ?? 'all');
        $age = $patient?->date_of_birth ? $patient->date_of_birth->age : 30;

        $hasAbnormal = false;
        $hasCritical = false;
        $savedItems = [];

        foreach ($parameters as $paramData) {
            $name = trim($paramData['parameter_name'] ?? '');
            $value = trim($paramData['measured_value'] ?? '');

            // Find matching reference range for this parameter, gender, and age
            $refRange = $this->findReferenceRange($labResult->lab_test_id, $name, $gender, $age);

            $flag = 'normal';
            $refLow = null;
            $refHigh = null;
            $unit = $paramData['unit'] ?? $refRange?->unit;

            if ($refRange) {
                $flag = $refRange->evaluateValue($value);
                $refLow = $refRange->normal_low;
                $refHigh = $refRange->normal_high;
                $unit = $refRange->unit;
            } elseif (is_numeric($value)) {
                // If custom limits were provided directly in input
                if (isset($paramData['reference_low']) && (float)$value < (float)$paramData['reference_low']) {
                    $flag = 'low';
                } elseif (isset($paramData['reference_high']) && (float)$value > (float)$paramData['reference_high']) {
                    $flag = 'high';
                }
            }

            if (in_array($flag, ['low', 'high', 'critical_low', 'critical_high', 'abnormal'], true)) {
                $hasAbnormal = true;
            }

            if (in_array($flag, ['critical_low', 'critical_high'], true)) {
                $hasCritical = true;
            }

            $numericVal = is_numeric($value) ? (float)$value : null;

            $item = LabResultItem::updateOrCreate(
                [
                    'lab_result_id' => $labResult->id,
                    'parameter_name' => $name,
                ],
                [
                    'reference_range_id' => $refRange?->id,
                    'measured_value' => (string)$value,
                    'numeric_value' => $numericVal,
                    'unit' => $unit,
                    'reference_low' => $refLow,
                    'reference_high' => $refHigh,
                    'flag' => $flag,
                    'notes' => $paramData['notes'] ?? null,
                ]
            );

            $savedItems[] = $item;
        }

        // Update overall result flags
        $labResult->has_abnormal_values = $hasAbnormal;
        $labResult->has_critical_values = $hasCritical;

        // If abnormal results are found, trigger notification to ordering doctor
        if ($hasAbnormal) {
            $this->notifyOrderingDoctor($labResult);
        }

        $labResult->save();

        return $savedItems;
    }

    /**
     * Find best matching reference range by test, parameter, gender, and age.
     */
    public function findReferenceRange(?string $testId, string $parameterName, string $gender, int $age): ?ReferenceRange
    {
        $query = ReferenceRange::where('parameter_name', 'ILIKE', $parameterName);

        if ($testId) {
            $query->where('lab_test_id', $testId);
        }

        // Try exact gender match or fallback to 'all'
        $ranges = $query->get();

        // 1. Exact gender + age match
        $match = $ranges->first(function ($r) use ($gender, $age) {
            return ($r->gender === $gender) &&
                   ($r->age_min_years === null || $age >= $r->age_min_years) &&
                   ($r->age_max_years === null || $age <= $r->age_max_years);
        });

        if ($match) {
            return $match;
        }

        // 2. Gender = 'all' with age match
        return $ranges->first(function ($r) use ($age) {
            return ($r->gender === 'all' || empty($r->gender)) &&
                   ($r->age_min_years === null || $age >= $r->age_min_years) &&
                   ($r->age_max_years === null || $age <= $r->age_max_years);
        }) ?? $ranges->first();
    }

    /**
     * Trigger urgent clinical notification to the ordering physician when abnormal/critical results occur.
     */
    public function notifyOrderingDoctor(LabResult $labResult): void
    {
        $order = $labResult->labOrder;
        if (!$order) {
            return;
        }

        $doctor = $order->orderingDoctor;
        $patient = $labResult->patient;
        $severity = $labResult->has_critical_values ? 'CRITICAL PANIC VALUE' : 'ABNORMAL RESULT';

        $message = "🚨 [LAB ALERT - {$severity}] Patient: " . ($patient ? "{$patient->first_name} {$patient->last_name} ({$patient->mrn})" : "ID {$labResult->patient_id}") .
                   " | Order: {$order->order_number} | Test: {$order->test_type}. Immediate clinical review required.";

        Log::warning($message, [
            'lab_result_id' => $labResult->id,
            'doctor_id' => $doctor?->id,
            'has_critical' => $labResult->has_critical_values,
        ]);

        $labResult->doctor_notified_at = now();
        $labResult->doctor_notified_channel = 'system_alert';
    }
}
