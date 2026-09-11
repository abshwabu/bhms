<?php

namespace App\Domain\OPD\Http\Controllers;

use App\Domain\OPD\Models\Department;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    /**
     * List all active OPD departments for the current branch.
     */
    public function index(): JsonResponse
    {
        $departments = Department::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        return ApiResponse::success($departments, 'Departments retrieved successfully.');
    }

    /**
     * Create a new clinical department.
     */
    public function store(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:10'],
            'description' => ['nullable', 'string'],
        ]);

        $department = Department::create(array_merge($validated, [
            'id' => (string) Str::uuid(),
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]));

        return ApiResponse::success($department, 'Department created successfully.', 201);
    }
}
