<?php

namespace App\Domain\Laboratory\Services;

use App\Domain\Laboratory\Models\LabResult;
use App\Domain\Laboratory\Models\LabSample;
use Illuminate\Support\Str;

class Hl7AstmIntegrationService
{
    public function __construct(
        protected LabResultEvaluationService $evaluationService
    ) {
    }

    /**
     * Parse incoming HL7 v2.x ORU^R01 observation result message from laboratory analyzer.
     *
     * @param string $hl7RawMessage
     * @return array Processed result data
     */
    public function parseHl7OruMessage(string $hl7RawMessage): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($hl7RawMessage));
        $sampleBarcode = null;
        $orderNumber = null;
        $deviceId = null;
        $observations = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $fields = explode('|', $line);
            $segmentType = $fields[0] ?? '';

            if ($segmentType === 'MSH') {
                $deviceId = $fields[2] ?? 'ANALYZER_HL7';
            } elseif ($segmentType === 'OBR') {
                // OBR-2 = Placer Order Number, OBR-3 = Filler/Sample Barcode
                $orderNumber = $fields[2] ?? null;
                $sampleBarcode = $fields[3] ?? null;
            } elseif ($segmentType === 'OBX') {
                // OBX-3: Identifier^Text, OBX-5: Value, OBX-6: Units, OBX-7: Reference Range, OBX-8: Abnormal Flag
                $identField = explode('^', $fields[3] ?? '');
                $paramName = $identField[1] ?? ($identField[0] ?? 'Parameter');
                $val = $fields[5] ?? '';
                $units = $fields[6] ?? '';
                $refRange = $fields[7] ?? '';

                $observations[] = [
                    'parameter_name' => $paramName,
                    'measured_value' => $val,
                    'unit' => $units,
                    'reference_range' => $refRange,
                ];
            }
        }

        return $this->ingestAnalyzerObservations($sampleBarcode, $observations, $deviceId, 'HL7_v2');
    }

    /**
     * Parse incoming ASTM E1381/E1394 data records from clinical equipment.
     *
     * @param string $astmRawMessage
     * @return array Processed result data
     */
    public function parseAstmMessage(string $astmRawMessage): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($astmRawMessage));
        $sampleBarcode = null;
        $deviceId = null;
        $observations = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $fields = explode('|', $line);
            $recordType = $fields[0] ?? '';

            if ($recordType === 'H') {
                $deviceId = $fields[4] ?? 'ANALYZER_ASTM';
            } elseif ($recordType === 'O') {
                // O-2: Specimen ID / Barcode
                $sampleBarcode = $fields[2] ?? null;
            } elseif ($recordType === 'R') {
                // R-2: Universal Test ID, R-3: Data or Measurement Value, R-4: Units, R-5: Reference Range
                $testIdField = explode('^', $fields[2] ?? '');
                $paramName = end($testIdField) ?: ($testIdField[0] ?? 'Parameter');
                $val = $fields[3] ?? '';
                $units = $fields[4] ?? '';

                $observations[] = [
                    'parameter_name' => $paramName,
                    'measured_value' => $val,
                    'unit' => $units,
                ];
            }
        }

        return $this->ingestAnalyzerObservations($sampleBarcode, $observations, $deviceId, 'ASTM_E1394');
    }

    /**
     * Ingest parsed analyzer observations into matching lab sample and result record.
     */
    protected function ingestAnalyzerObservations(?string $barcode, array $observations, ?string $deviceId, string $protocol): array
    {
        if (empty($barcode) || empty($observations)) {
            return [
                'success' => false,
                'message' => 'No valid sample barcode or observation items found in instrument message.',
            ];
        }

        $sample = LabSample::where('barcode', $barcode)->first();
        if (!$sample) {
            return [
                'success' => false,
                'message' => "Sample with barcode '{$barcode}' not found in LIS.",
            ];
        }

        // Find or create preliminary result record
        $order = $sample->labOrder;
        $reportNumber = 'REP-' . date('Y') . '-' . strtoupper(Str::random(6));

        $result = LabResult::firstOrCreate(
            ['lab_sample_id' => $sample->id],
            [
                'id' => (string) Str::uuid(),
                'report_number' => $reportNumber,
                'organization_id' => $sample->organization_id,
                'branch_id' => $sample->branch_id,
                'lab_order_id' => $sample->lab_order_id,
                'patient_id' => $sample->patient_id,
                'status' => 'preliminary',
                'analyzer_device_id' => $deviceId,
                'methodology' => "Automated Interface ({$protocol}: {$deviceId})",
            ]
        );

        // Evaluate parameter items against reference ranges
        $evaluatedItems = $this->evaluationService->evaluateAndSaveItems($result, $observations);

        // Update sample status to completed/analyzed
        $sample->update(['status' => 'processing']);

        return [
            'success' => true,
            'protocol' => $protocol,
            'device_id' => $deviceId,
            'sample_barcode' => $barcode,
            'result_id' => $result->id,
            'report_number' => $result->report_number,
            'has_abnormal' => $result->has_abnormal_values,
            'has_critical' => $result->has_critical_values,
            'parameters_ingested' => count($evaluatedItems),
        ];
    }
}
