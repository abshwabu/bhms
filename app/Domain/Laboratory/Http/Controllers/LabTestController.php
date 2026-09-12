<?php

namespace App\Domain\Laboratory\Http\Controllers;

use App\Domain\Laboratory\Http\Resources\LabTestResource;
use App\Domain\Laboratory\Http\Resources\ReferenceRangeResource;
use App\Domain\Laboratory\Models\LabTest;
use App\Domain\Laboratory\Models\ReferenceRange;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LabTestController extends Controller
{
    /**
     * List lab test catalog master items.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LabTest::query()->with('referenceRanges')->where('is_active', true);

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ILIKE', "%{$term}%")
                  ->orWhere('code', 'ILIKE', "%{$term}%");
            });
        }

        $tests = $query->orderBy('category')->orderBy('name')->get();

        return ApiResponse::success(
            LabTestResource::collection($tests),
            'Laboratory tests catalog retrieved successfully.'
        );
    }

    /**
     * Show single lab test with all parameter reference ranges.
     */
    public function show(LabTest $labTest): JsonResponse
    {
        $labTest->load('referenceRanges');

        return ApiResponse::success(
            new LabTestResource($labTest),
            'Laboratory test retrieved successfully.'
        );
    }

    /**
     * Store new lab test in catalog.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:lab_tests,code'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:100'],
            'specimen_type' => ['nullable', 'string', 'max:50'],
            'container_type' => ['nullable', 'string', 'max:100'],
            'turn_around_time_minutes' => ['nullable', 'integer'],
            'price_cents' => ['nullable', 'integer'],
        ]);

        $test = LabTest::create([
            'id' => (string) Str::uuid(),
            ...$validated,
            'is_active' => true,
        ]);

        return ApiResponse::success(
            new LabTestResource($test),
            "Lab test '{$test->name}' created successfully.",
            201
        );
    }

    /**
     * Add or update parameter reference range.
     */
    public function addReferenceRange(LabTest $labTest, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parameter_name' => ['required', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'string', 'in:all,male,female'],
            'age_min_years' => ['nullable', 'integer'],
            'age_max_years' => ['nullable', 'integer'],
            'normal_low' => ['nullable', 'numeric'],
            'normal_high' => ['nullable', 'numeric'],
            'critical_low' => ['nullable', 'numeric'],
            'critical_high' => ['nullable', 'numeric'],
            'qualitative_normal' => ['nullable', 'string'],
        ]);

        $range = ReferenceRange::create([
            'id' => (string) Str::uuid(),
            'lab_test_id' => $labTest->id,
            ...$validated,
        ]);

        return ApiResponse::success(
            new ReferenceRangeResource($range),
            'Reference range added successfully.',
            201
        );
    }

    /**
     * List all reference ranges with optional parameter filtering.
     */
    public function referenceRanges(Request $request): JsonResponse
    {
        $query = ReferenceRange::query()->with('labTest');

        if ($request->filled('parameter_name')) {
            $query->where('parameter_name', 'ILIKE', '%' . trim($request->input('parameter_name')) . '%');
        }

        if ($request->filled('lab_test_id')) {
            $query->where('lab_test_id', $request->input('lab_test_id'));
        }

        $ranges = $query->orderBy('parameter_name')->get();

        return ApiResponse::success(
            ReferenceRangeResource::collection($ranges),
            'Reference ranges retrieved successfully.'
        );
    }
}
