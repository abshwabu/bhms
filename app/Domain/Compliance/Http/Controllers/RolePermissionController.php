<?php

namespace App\Domain\Compliance\Http\Controllers;

use App\Domain\Compliance\Http\Requests\AssignUserRoleRequest;
use App\Domain\Compliance\Http\Requests\StoreRoleRequest;
use App\Domain\Compliance\Http\Requests\UpdateRoleRequest;
use App\Domain\Compliance\Services\RolePermissionService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __construct(
        protected RolePermissionService $rolePermissionService
    ) {}

    /**
     * List all roles with attached permissions.
     */
    public function index(): JsonResponse
    {
        $roles = $this->rolePermissionService->getRoles();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * List all available permissions in the system.
     */
    public function permissions(): JsonResponse
    {
        $data = $this->rolePermissionService->getAllPermissions();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Create a new role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->rolePermissionService->createRole($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => $role,
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an existing role.
     */
    public function update(UpdateRoleRequest $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $updated = $this->rolePermissionService->updateRole($role, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => $updated,
        ]);
    }

    /**
     * Delete a role.
     */
    public function destroy(string $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $this->rolePermissionService->deleteRole($role);

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.',
        ]);
    }

    /**
     * Assign roles to a specified user.
     */
    public function assignUserRoles(AssignUserRoleRequest $request, string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $updatedUser = $this->rolePermissionService->assignUserRoles($user, $request->validated()['roles']);

        return response()->json([
            'success' => true,
            'message' => 'User roles updated successfully.',
            'data' => [
                'user_id' => $updatedUser->id,
                'name' => $updatedUser->name,
                'roles' => $updatedUser->roles->pluck('name'),
            ],
        ]);
    }

    /**
     * Retrieve effective permissions for a user.
     */
    public function getUserPermissions(string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $permissions = $this->rolePermissionService->getUserPermissions($user);

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }
}
