<?php

namespace App\Domain\Compliance\Http\Controllers;

use App\Domain\Compliance\Services\HipaaComplianceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HipaaComplianceController extends Controller
{
    public function __construct(
        protected HipaaComplianceService $hipaaService
    ) {}

    /**
     * Retrieve the current HIPAA compliance scorecard and checklist.
     */
    public function checklist(): JsonResponse
    {
        $scorecard = $this->hipaaService->getComplianceScorecard();

        return response()->json([
            'success' => true,
            'data' => $scorecard,
        ]);
    }

    /**
     * Run real-time automated evaluation across all 5 HIPAA technical and privacy safeguard domains.
     */
    public function evaluate(): JsonResponse
    {
        $freshScorecard = $this->hipaaService->evaluateAllSafeguards();

        return response()->json([
            'success' => true,
            'message' => 'HIPAA automated safeguard evaluation executed successfully.',
            'data' => $freshScorecard,
        ]);
    }
}
