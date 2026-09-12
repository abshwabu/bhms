<?php

namespace App\Domain\Administration\Http\Controllers;

use App\Domain\Administration\Http\Requests\StoreBranchRequest;
use App\Domain\Administration\Http\Requests\UpdateBranchRequest;
use App\Domain\Administration\Services\BranchManagementService;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BranchManagementController extends Controller
{
    public function __construct(
        protected BranchManagementService $branchService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $branches = $this->branchService->getBranches($request->query('organization_id'));

        return response()->json([
            'success' => true,
            'data' => $branches,
        ]);
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $branch = $this->branchService->createBranch($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Hospital branch registered successfully.',
            'data' => $branch,
        ], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $branch = Branch::with('organization')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $branch,
        ]);
    }

    public function update(UpdateBranchRequest $request, string $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);
        $updated = $this->branchService->updateBranch($branch, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully.',
            'data' => $updated,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);
        $this->branchService->deleteBranch($branch);

        return response()->json([
            'success' => true,
            'message' => 'Branch deactivated successfully.',
        ]);
    }

    public function assignUser(Request $request, string $branchId): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $this->branchService->assignUserToBranch(
            $branchId,
            $request->input('user_id'),
            (bool) $request->input('is_default', false)
        );

        return response()->json([
            'success' => true,
            'message' => 'User assigned to branch successfully.',
        ]);
    }
}
