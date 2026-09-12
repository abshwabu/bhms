<?php

namespace App\Domain\Administration\Services;

use App\Domain\Administration\Models\HospitalService;
use App\Domain\Billing\Models\PriceList;
use App\Domain\OPD\Models\Department;
use App\Domain\Shared\Models\Branch;
use Illuminate\Database\Eloquent\Collection;

class MasterDataService
{
    // ==========================================
    // 1. DEPARTMENTS
    // ==========================================

    public function getDepartments(string $branchId): Collection
    {
        return Department::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();
    }

    public function createDepartment(array $data): Department
    {
        $branch = Branch::findOrFail($data['branch_id']);

        return Department::create([
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateDepartment(Department $department, array $data): Department
    {
        $department->update($data);

        return $department->fresh();
    }

    public function deleteDepartment(Department $department): void
    {
        $department->delete();
    }

    // ==========================================
    // 2. HOSPITAL SERVICES
    // ==========================================

    public function getServices(string $branchId, array $filters = []): Collection
    {
        $query = HospitalService::withoutGlobalScopes()
            ->with('department')
            ->where('branch_id', $branchId)
            ->orderBy('name');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->get();
    }

    public function createService(array $data): HospitalService
    {
        $branch = Branch::findOrFail($data['branch_id']);

        return HospitalService::create([
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
            'department_id' => $data['department_id'] ?? null,
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'category' => $data['category'] ?? 'clinical',
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? 15,
            'base_price_cents' => $data['base_price_cents'] ?? 0,
            'requires_doctor' => $data['requires_doctor'] ?? true,
            'is_active' => $data['is_active'] ?? true,
            'preparation_instructions' => $data['preparation_instructions'] ?? [],
        ]);
    }

    public function updateService(HospitalService $service, array $data): HospitalService
    {
        $service->update($data);

        return $service->fresh(['department']);
    }

    public function deleteService(HospitalService $service): void
    {
        $service->delete();
    }

    // ==========================================
    // 3. PRICE LISTS (BILLING CATALOG)
    // ==========================================

    public function getPriceLists(string $branchId, array $filters = []): Collection
    {
        $query = PriceList::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->orderBy('name');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->get();
    }

    public function createPriceList(array $data): PriceList
    {
        $branch = Branch::findOrFail($data['branch_id']);

        return PriceList::create([
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'category' => $data['category'] ?? 'procedure',
            'department' => $data['department'] ?? 'general',
            'unit_price_cents' => $data['unit_price_cents'] ?? 0,
            'is_package' => $data['is_package'] ?? false,
            'package_items' => $data['package_items'] ?? [],
            'is_active' => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
        ]);
    }

    public function updatePriceList(PriceList $priceList, array $data): PriceList
    {
        $priceList->update($data);

        return $priceList->fresh();
    }

    public function deletePriceList(PriceList $priceList): void
    {
        $priceList->delete();
    }
}
