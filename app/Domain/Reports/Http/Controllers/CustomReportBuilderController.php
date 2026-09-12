<?php

namespace App\Domain\Reports\Http\Controllers;

use App\Domain\Reports\Models\SavedCustomReport;
use App\Domain\Reports\Services\CustomReportBuilderService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomReportBuilderController extends Controller
{
    public function __construct(
        protected CustomReportBuilderService $builderService
    ) {}

    /**
     * Retrieve reporting schema (entities, fields, operators, and bounds).
     */
    public function schema(): JsonResponse
    {
        return ApiResponse::success(
            $this->builderService->getSchema(),
            'Report builder schema and query cost limits retrieved.'
        );
    }

    /**
     * Execute safe custom query with strict whitelisting and cost limits.
     * Acceptance criterion: Custom report builder prevents unsafe/unbounded queries.
     */
    public function query(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity' => ['required', 'string'],
            'branch_id' => ['nullable', 'uuid'],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string'],
            'filters' => ['nullable', 'array'],
            'filters.*.field' => ['required', 'string'],
            'filters.*.operator' => ['required', 'string'],
            'filters.*.value' => ['nullable'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort_field' => ['nullable', 'string'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
            'group_by' => ['nullable', 'string'],
            'aggregation' => ['nullable', 'string', 'in:count,sum,avg,min,max'],
            'aggregation_field' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        if (empty($validated['branch_id'])) {
            $validated['branch_id'] = $request->header('X-Branch-ID');
        }

        try {
            $result = $this->builderService->executeQuery($validated, false);

            return ApiResponse::success($result, 'Custom report executed successfully.');
        } catch (DomainException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                'QUERY_COST_VIOLATION',
                [],
                422
            );
        }
    }

    /**
     * List saved custom reports.
     */
    public function savedReports(Request $request): JsonResponse
    {
        $branchId = $request->header('X-Branch-ID') ?: $request->input('branch_id');

        $query = SavedCustomReport::query()->with('user');
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $reports = $query->orderBy('name', 'asc')->get();

        return ApiResponse::success($reports, 'Saved custom reports retrieved.');
    }

    /**
     * Save custom report configuration for re-use.
     */
    public function saveReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'entity' => ['required', 'string'],
            'branch_id' => ['nullable', 'uuid'],
            'selected_fields' => ['required', 'array'],
            'filters' => ['nullable', 'array'],
            'sort_field' => ['nullable', 'string'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'group_by' => ['nullable', 'string'],
            'date_range_preset' => ['nullable', 'string'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $branchId = $validated['branch_id']
            ?: $request->header('X-Branch-ID')
            ?: '84d7387e-7b2e-4533-b3e8-139e52aecb8b';

        $saved = SavedCustomReport::create([
            'organization_id' => '93da9d9c-cece-44f8-ac4e-5f788c1af982',
            'branch_id' => $branchId,
            'user_id' => $request->user()?->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'entity' => $validated['entity'],
            'selected_fields' => $validated['selected_fields'],
            'filters' => $validated['filters'] ?? [],
            'sort_field' => $validated['sort_field'] ?? null,
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
            'group_by' => $validated['group_by'] ?? null,
            'date_range_preset' => $validated['date_range_preset'] ?? 'last_30_days',
            'is_public' => $validated['is_public'] ?? false,
        ]);

        return ApiResponse::success($saved, "Custom report template '{$saved->name}' saved.", 201);
    }
}
