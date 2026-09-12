<?php

namespace App\Domain\Administration\Http\Controllers;

use App\Domain\Administration\Http\Requests\StoreDepartmentRequest;
use App\Domain\Administration\Http\Requests\StoreServiceRequest;
use App\Domain\Administration\Http\Requests\UpdateDepartmentRequest;
use App\Domain\Administration\Http\Requests\UpdateServiceRequest;
use App\Domain\Administration\Models\HospitalService;
use App\Domain\Administration\Services\MasterDataService;
use App\Domain\Billing\Models\PriceList;
use App\Domain\OPD\Models\Department;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MasterDataManagementController extends Controller
{
    public function __construct(
        protected MasterDataService $masterDataService
    ) {}

    // ==========================================
    // 1. DEPARTMENTS
    // ==========================================

    public function getDepartments(string $branchId): JsonResponse
    {
        $departments = $this->masterDataService->getDepartments($branchId);

        return response()->json([
            'success' => true,
            'data' => $departments,
        ]);
    }

    public function storeDepartment(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->masterDataService->createDepartment($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Department created successfully.',
            'data' => $department,
        ], Response::HTTP_CREATED);
    }

    public function updateDepartment(UpdateDepartmentRequest $request, string $id): JsonResponse
    {
        $department = Department::withoutGlobalScopes()->findOrFail($id);
        $updated = $this->masterDataService->updateDepartment($department, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully.',
            'data' => $updated,
        ]);
    }

    public function destroyDepartment(string $id): JsonResponse
    {
        $department = Department::withoutGlobalScopes()->findOrFail($id);
        $this->masterDataService->deleteDepartment($department);

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully.',
        ]);
    }

    // ==========================================
    // 2. HOSPITAL SERVICES
    // ==========================================

    public function getServices(Request $request, string $branchId): JsonResponse
    {
        $services = $this->masterDataService->getServices($branchId, $request->all());

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    public function storeService(StoreServiceRequest $request): JsonResponse
    {
        $service = $this->masterDataService->createService($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Hospital service registered successfully.',
            'data' => $service,
        ], Response::HTTP_CREATED);
    }

    public function updateService(UpdateServiceRequest $request, string $id): JsonResponse
    {
        $service = HospitalService::withoutGlobalScopes()->findOrFail($id);
        $updated = $this->masterDataService->updateService($service, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully.',
            'data' => $updated,
        ]);
    }

    public function destroyService(string $id): JsonResponse
    {
        $service = HospitalService::withoutGlobalScopes()->findOrFail($id);
        $this->masterDataService->deleteService($service);

        return response()->json([
            'success' => true,
            'message' => 'Service removed from catalog.',
        ]);
    }

    // ==========================================
    // 3. PRICE LISTS
    // ==========================================

    public function getPriceLists(Request $request, string $branchId): JsonResponse
    {
        $prices = $this->masterDataService->getPriceLists($branchId, $request->all());

        return response()->json([
            'success' => true,
            'data' => $prices,
        ]);
    }

    public function storePriceList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:100'],
            'unit_price_cents' => ['required', 'integer', 'min:0'],
            'is_package' => ['nullable', 'boolean'],
            'package_items' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $priceList = $this->masterDataService->createPriceList($validated);

        return response()->json([
            'success' => true,
            'message' => 'Price list item created successfully.',
            'data' => $priceList,
        ], Response::HTTP_CREATED);
    }

    public function updatePriceList(Request $request, string $id): JsonResponse
    {
        $priceList = PriceList::withoutGlobalScopes()->findOrFail($id);
        $updated = $this->masterDataService->updatePriceList($priceList, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Price list item updated successfully.',
            'data' => $updated,
        ]);
    }

    public function destroyPriceList(string $id): JsonResponse
    {
        $priceList = PriceList::withoutGlobalScopes()->findOrFail($id);
        $this->masterDataService->deletePriceList($priceList);

        return response()->json([
            'success' => true,
            'message' => 'Price list item deleted successfully.',
        ]);
    }
}
