<?php

namespace App\Domain\Laboratory\Http\Controllers;

use App\Domain\Laboratory\Http\Requests\Hl7MessageRequest;
use App\Domain\Laboratory\Services\Hl7AstmIntegrationService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabEquipmentController extends Controller
{
    public function __construct(
        protected Hl7AstmIntegrationService $integrationService
    ) {
    }

    /**
     * Ingest HL7 v2.x ORU^R01 observation result feed from clinical analyzer.
     */
    public function receiveHl7(Hl7MessageRequest $request): JsonResponse
    {
        try {
            $rawHl7 = $request->input('hl7_message') ?: $request->getContent();

            if (empty(trim($rawHl7))) {
                return ApiResponse::error('HL7 payload is empty.', 'EMPTY_HL7_PAYLOAD', [], 422);
            }

            $result = $this->integrationService->parseHl7OruMessage($rawHl7);

            if (!($result['success'] ?? false)) {
                return ApiResponse::error($result['message'] ?? 'HL7 ingestion failed.', 'HL7_INGEST_FAILED', $result, 422);
            }

            return ApiResponse::success(
                $result,
                "HL7 ORU^R01 observations ingested successfully for sample {$result['sample_barcode']}."
            );
        } catch (Exception $e) {
            return ApiResponse::error("HL7 processing failed: " . $e->getMessage(), 'HL7_ERROR', [], 500);
        }
    }

    /**
     * Ingest ASTM E1381/E1394 data records from diagnostic instruments.
     */
    public function receiveAstm(Hl7MessageRequest $request): JsonResponse
    {
        try {
            $rawAstm = $request->input('astm_message') ?: $request->getContent();

            if (empty(trim($rawAstm))) {
                return ApiResponse::error('ASTM payload is empty.', 'EMPTY_ASTM_PAYLOAD', [], 422);
            }

            $result = $this->integrationService->parseAstmMessage($rawAstm);

            if (!($result['success'] ?? false)) {
                return ApiResponse::error($result['message'] ?? 'ASTM ingestion failed.', 'ASTM_INGEST_FAILED', $result, 422);
            }

            return ApiResponse::success(
                $result,
                "ASTM E1394 records ingested successfully for sample {$result['sample_barcode']}."
            );
        } catch (Exception $e) {
            return ApiResponse::error("ASTM processing failed: " . $e->getMessage(), 'ASTM_ERROR', [], 500);
        }
    }
}
