<?php

namespace App\Services\CRM;

use App\Models\HRD\Department;
use App\Models\HRD\EmployeeType;
use App\Models\HRD\Placement;
use App\Models\HRD\Position;
use App\Models\Division;
use Illuminate\Support\Facades\DB;

class MasterDataService
{
    /**
     * ============================================================
     * DEPARTMENTS
     * ============================================================
     */

    public function getDepartments(int $companyId, array $filters = [])
    {
        $query = Department::forCompany($companyId)->with(['divisions', 'positions']);

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        return $query->orderBy('name')->paginate($filters['per_page'] ?? 15);
    }

    public function createDepartment(int $companyId, array $data): Department
    {
        return Department::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);
    }

    public function updateDepartment(Department $department, array $data): Department
    {
        $department->update([
            'name' => $data['name'] ?? $department->name,
            'code' => $data['code'] ?? $department->code,
            'description' => $data['description'] ?? $department->description,
            'is_active' => $data['is_active'] ?? $department->is_active,
        ]);

        return $department->fresh();
    }

    public function deleteDepartment(Department $department): array
    {
        // Check for related employees
        $employeeCount = $department->employees()->where('is_active', true)->count();
        $divisionCount = $department->divisions()->count();
        $positionCount = $department->positions()->count();

        if ($employeeCount > 0) {
            return [
                'success' => false,
                'message' => "Departemen ini masih digunakan oleh {$employeeCount} karyawan aktif. Nonaktifkaninstead untuk menonaktifkan.",
                'can_delete' => false,
            ];
        }

        // Soft delete (set inactive if has relations)
        if ($divisionCount > 0 || $positionCount > 0) {
            $department->update(['is_active' => false]);
            return [
                'success' => true,
                'message' => 'Departemen dinonaktifkan karena masih memiliki relasi.',
                'deactivated' => true,
            ];
        }

        $department->delete();

        return [
            'success' => true,
            'message' => 'Departemen berhasil dihapus.',
        ];
    }

    /**
     * ============================================================
     * DIVISIONS
     * ============================================================
     */

    public function getDivisions(int $companyId, array $filters = [])
    {
        $query = Division::with(['department'])
            ->where('company_id', $companyId);

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        return $query->orderBy('name')->paginate($filters['per_page'] ?? 15);
    }

    public function createDivision(int $companyId, array $data): Division
    {
        return Division::create([
            'company_id' => $companyId,
            'department_id' => $data['department_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);
    }

    public function updateDivision(Division $division, array $data): Division
    {
        $division->update([
            'department_id' => $data['department_id'] ?? $division->department_id,
            'name' => $data['name'] ?? $division->name,
            'code' => $data['code'] ?? $division->code,
            'description' => $data['description'] ?? $division->description,
            'is_active' => $data['is_active'] ?? $division->is_active,
        ]);

        return $division->fresh();
    }

    public function deleteDivision(Division $division): array
    {
        $employeeCount = $division->employees()->where('is_active', true)->count();
        $positionCount = $division->positions()->count();

        if ($employeeCount > 0) {
            return [
                'success' => false,
                'message' => "Divisi ini masih digunakan oleh {$employeeCount} karyawan aktif. Nonaktifkan sebagai gantinya.",
                'can_delete' => false,
            ];
        }

        if ($positionCount > 0) {
            $division->update(['is_active' => false]);
            return [
                'success' => true,
                'message' => 'Divisi dinonaktifkan karena masih memiliki relasi.',
                'deactivated' => true,
            ];
        }

        $division->delete();

        return [
            'success' => true,
            'message' => 'Divisi berhasil dihapus.',
        ];
    }

    /**
     * ============================================================
     * POSITIONS
     * ============================================================
     */

    public function getPositions(int $companyId, array $filters = [])
    {
        $query = Position::with(['department', 'division'])
            ->where('company_id', $companyId);

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['division_id'])) {
            $query->where('division_id', $filters['division_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        return $query->orderBy('name')->paginate($filters['per_page'] ?? 15);
    }

    public function createPosition(int $companyId, array $data): Position
    {
        return Position::create([
            'company_id' => $companyId,
            'department_id' => $data['department_id'] ?? null,
            'division_id' => $data['division_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'level' => $data['level'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);
    }

    public function updatePosition(Position $position, array $data): Position
    {
        $position->update([
            'department_id' => $data['department_id'] ?? $position->department_id,
            'division_id' => $data['division_id'] ?? $position->division_id,
            'name' => $data['name'] ?? $position->name,
            'code' => $data['code'] ?? $position->code,
            'level' => $data['level'] ?? $position->level,
            'description' => $data['description'] ?? $position->description,
            'is_active' => $data['is_active'] ?? $position->is_active,
        ]);

        return $position->fresh();
    }

    public function deletePosition(Position $position): array
    {
        $employeeCount = $position->employees()->where('is_active', true)->count();

        if ($employeeCount > 0) {
            return [
                'success' => false,
                'message' => "Posisi ini masih digunakan oleh {$employeeCount} karyawan aktif. Nonaktifkan sebagai gantinya.",
                'can_delete' => false,
            ];
        }

        $position->delete();

        return [
            'success' => true,
            'message' => 'Posisi berhasil dihapus.',
        ];
    }

    /**
     * ============================================================
     * EMPLOYEE TYPES
     * ============================================================
     */

    public function getEmployeeTypes(int $companyId, array $filters = [])
    {
        $query = EmployeeType::forCompany($companyId)->with('employees');

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('code', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        return $query->sorted()->paginate($filters['per_page'] ?? 15);
    }

    public function createEmployeeType(int $companyId, array $data): EmployeeType
    {
        // Get max sort_order for this company
        $maxSort = EmployeeType::forCompany($companyId)->max('sort_order') ?? 0;

        return EmployeeType::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#6B7280',
            'sort_order' => ($data['sort_order'] ?? null) ?? ($maxSort + 1),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateEmployeeType(EmployeeType $type, array $data): EmployeeType
    {
        $type->update([
            'name' => $data['name'] ?? $type->name,
            'code' => $data['code'] ?? $type->code,
            'description' => $data['description'] ?? $type->description,
            'color' => $data['color'] ?? $type->color,
            'sort_order' => $data['sort_order'] ?? $type->sort_order,
            'is_active' => $data['is_active'] ?? $type->is_active,
        ]);

        return $type->fresh();
    }

    public function deleteEmployeeType(EmployeeType $type): array
    {
        $employeeCount = $type->employee_count;

        if ($employeeCount > 0) {
            return [
                'success' => false,
                'message' => "Tipe karyawan ini masih digunakan oleh {$employeeCount} karyawan. Nonaktifkan sebagai gantinya.",
                'can_delete' => false,
            ];
        }

        $type->delete();

        return [
            'success' => true,
            'message' => 'Tipe karyawan berhasil dihapus.',
        ];
    }

    /**
     * ============================================================
     * LOCATIONS (PLACEMENTS)
     * ============================================================
     */

    public function getLocations(int $companyId, array $filters = [])
    {
        $query = Placement::where('company_id', $companyId);

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        return $query->ordered()->paginate($filters['per_page'] ?? 15);
    }

    public function createLocation(int $companyId, int $userId, array $data): Placement
    {
        return Placement::create([
            'company_id' => $companyId,
            'created_by' => $userId,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'type' => $data['type'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'province' => $data['province'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'radius_meters' => $data['radius_meters'] ?? 100,
            'description' => $data['description'] ?? null,
            'pic_name' => $data['pic_name'] ?? null,
            'is_active' => true,
        ]);
    }

    public function updateLocation(Placement $location, array $data): Placement
    {
        $location->update([
            'name' => $data['name'] ?? $location->name,
            'code' => $data['code'] ?? $location->code,
            'type' => $data['type'] ?? $location->type,
            'address' => $data['address'] ?? $location->address,
            'city' => $data['city'] ?? $location->city,
            'province' => $data['province'] ?? $location->province,
            'latitude' => $data['latitude'] ?? $location->latitude,
            'longitude' => $data['longitude'] ?? $location->longitude,
            'radius_meters' => $data['radius_meters'] ?? $location->radius_meters,
            'description' => $data['description'] ?? $location->description,
            'pic_name' => $data['pic_name'] ?? $location->pic_name,
            'is_active' => $data['is_active'] ?? $location->is_active,
        ]);

        return $location->fresh();
    }

    public function deleteLocation(Placement $location): array
    {
        $employeeCount = $location->active_employees_count;

        if ($employeeCount > 0) {
            return [
                'success' => false,
                'message' => "Lokasi ini masih digunakan oleh {$employeeCount} karyawan aktif. Nonaktifkan sebagai gantinya.",
                'can_delete' => false,
            ];
        }

        $location->delete();

        return [
            'success' => true,
            'message' => 'Lokasi berhasil dihapus.',
        ];
    }

    /**
     * ============================================================
     * DROPDOWN DATA (For AJAX)
     * ============================================================
     */

    public function getDivisionsForDropdown(int $companyId, ?int $departmentId = null): array
    {
        $query = Division::where('company_id', $companyId)->active();

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return $query->orderBy('name')->get(['id', 'name', 'code', 'department_id'])->toArray();
    }

    public function getPositionsForDropdown(int $companyId, ?int $divisionId = null): array
    {
        $query = Position::where('company_id', $companyId)->active();

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        }

        return $query->orderBy('name')->get(['id', 'name', 'code', 'division_id', 'level'])->toArray();
    }

    public function getEmployeeTypesForDropdown(int $companyId): array
    {
        return EmployeeType::forCompany($companyId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->toArray();
    }

    public function getLocationsForDropdown(int $companyId): array
    {
        return Placement::where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'address', 'city'])
            ->toArray();
    }
}
