<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\HRD\Department;
use App\Models\HRD\EmployeeType;
use App\Models\HRD\Placement;
use App\Models\HRD\Position;
use App\Models\Division;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterDataController extends Controller
{
    /**
     * Display the unified master data page
     */
    public function index()
    {
        return view('crm.settings.master-data.index');
    }

    /**
     * Get data for a specific tab (AJAX)
     */
    public function getData(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $type = $request->get('type', 'departments');
        $search = $request->get('search', '') ?? '';
        $status = $request->get('status', '') ?? '';
        $perPage = (int) ($request->get('per_page', 10) ?? 10);
        $page = (int) ($request->get('page', 1) ?? 1);

        return match($type) {
            'departments' => $this->getDepartmentsData($companyId, $search, $status, $perPage, $page),
            'divisions' => $this->getDivisionsData($companyId, $search, $status, $perPage, $page),
            'positions' => $this->getPositionsData($companyId, $search, $status, $perPage, $page),
            'employee-types' => $this->getEmployeeTypesData($companyId, $search, $status, $perPage, $page),
            'locations' => $this->getLocationsData($companyId, $search, $status, $perPage, $page),
            default => response()->json(['error' => 'Invalid type'], 400),
        };
    }

    /**
     * Get all dropdown options (for forms)
     */
    public function getOptions(): JsonResponse
    {
        $companyId = auth()->user()->company_id;

        return response()->json([
            'departments' => Department::forCompany($companyId)->active()->orderBy('name')->get(['id', 'name', 'code']),
            'divisions' => Division::where('company_id', $companyId)->active()->with('department')->orderBy('name')->get(['id', 'name', 'department_id']),
            'employeeTypes' => EmployeeType::forCompany($companyId)->active()->sorted()->get(['id', 'name', 'code', 'color']),
            'locations' => Placement::where('company_id', $companyId)->active()->orderBy('name')->get(['id', 'name']),
            'location_types' => Placement::$types,
        ]);
    }

    // =====================================================
    // DEPARTMENTS CRUD
    // =====================================================

    protected function getDepartmentsData(int $companyId, string $search, string $status, int $perPage, int $page): JsonResponse
    {
        $query = Department::forCompany($companyId)
            ->withCount(['divisions', 'positions', 'employees']);

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $total = $query->count();
        $departments = $query->orderBy('name')->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $departments,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage),
        ]);
    }

    public function storeDepartment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:departments,code,NULL,id,company_id,' . auth()->user()->company_id,
                'description' => 'nullable|string|max:500',
            ]);

            $department = Department::create([
                'company_id' => auth()->user()->company_id,
                'name' => $validated['name'],
                'code' => $validated['code'],
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Departemen berhasil ditambahkan',
                'data' => $department,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateDepartment(Request $request, Department $department): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:departments,code,' . $department->id . ',id,company_id,' . $department->company_id,
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean',
            ]);

            $department->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Departemen berhasil diperbarui',
                'data' => $department->fresh(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function toggleDepartment(Department $department): JsonResponse
    {
        try {
            $department->update(['is_active' => !$department->is_active]);

            return response()->json([
                'success' => true,
                'message' => $department->is_active ? 'Departemen diaktifkan' : 'Departemen dinonaktifkan',
                'data' => $department->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroyDepartment(Department $department): JsonResponse
    {
        try {
            // Check if department has related data
            if ($department->employees()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus departemen yang memiliki karyawan',
                ], 422);
            }

            if ($department->divisions()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus departemen yang memiliki divisi',
                ], 422);
            }

            $department->delete();

            return response()->json([
                'success' => true,
                'message' => 'Departemen berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =====================================================
    // DIVISIONS CRUD
    // =====================================================

    protected function getDivisionsData(int $companyId, string $search, string $status, int $perPage, int $page): JsonResponse
    {
        $query = Division::with(['department', 'positions', 'employees'])
            ->where('company_id', $companyId);

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $total = $query->count();
        $divisions = $query->orderBy('name')->skip(($page - 1) * $perPage)->take($perPage)->get();

        // Add counts
        $divisions->each(function ($division) {
            $division->positions_count = $division->positions()->count();
            $division->employees_count = $division->employees()->count();
        });

        return response()->json([
            'data' => $divisions,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage),
        ]);
    }

    public function storeDivision(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50',
                'department_id' => 'nullable|exists:departments,id',
                'description' => 'nullable|string|max:500',
            ]);

            $division = Division::create([
                'company_id' => auth()->user()->company_id,
                'department_id' => $validated['department_id'] ?? null,
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Divisi berhasil ditambahkan',
                'data' => $division->load('department'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateDivision(Request $request, Division $division): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50',
                'department_id' => 'nullable|exists:departments,id',
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean',
            ]);

            $division->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Divisi berhasil diperbarui',
                'data' => $division->fresh()->load('department'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function toggleDivision(Division $division): JsonResponse
    {
        try {
            $division->update(['is_active' => !$division->is_active]);

            return response()->json([
                'success' => true,
                'message' => $division->is_active ? 'Divisi diaktifkan' : 'Divisi dinonaktifkan',
                'data' => $division->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroyDivision(Division $division): JsonResponse
    {
        try {
            if ($division->employees()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus divisi yang memiliki karyawan',
                ], 422);
            }

            if ($division->positions()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus divisi yang memiliki posisi',
                ], 422);
            }

            $division->delete();

            return response()->json([
                'success' => true,
                'message' => 'Divisi berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =====================================================
    // POSITIONS CRUD
    // =====================================================

    protected function getPositionsData(int $companyId, string $search, string $status, int $perPage, int $page): JsonResponse
    {
        $query = Position::with(['department', 'division', 'employees'])
            ->where('company_id', $companyId);

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $total = $query->count();
        $positions = $query->orderBy('name')->skip(($page - 1) * $perPage)->take($perPage)->get();

        // Add counts
        $positions->each(function ($position) {
            $position->employees_count = $position->employees()->count();
        });

        return response()->json([
            'data' => $positions,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage),
        ]);
    }

    public function storePosition(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50',
                'level' => 'nullable|string|max:50',
                'description' => 'nullable|string|max:500',
                'is_active' => 'nullable|boolean',
            ]);

            $position = Position::create([
                'company_id' => auth()->user()->company_id,
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'level' => $validated['level'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Posisi berhasil ditambahkan',
                'data' => $position->load(['department', 'division']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updatePosition(Request $request, Position $position): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50',
                'level' => 'nullable|string|max:50',
                'description' => 'nullable|string|max:500',
                'is_active' => 'nullable|boolean',
            ]);

            $position->update([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'level' => $validated['level'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? $position->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Posisi berhasil diperbarui',
                'data' => $position->fresh()->load(['department', 'division']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function togglePosition(Position $position): JsonResponse
    {
        try {
            $position->update(['is_active' => !$position->is_active]);

            return response()->json([
                'success' => true,
                'message' => $position->is_active ? 'Posisi diaktifkan' : 'Posisi dinonaktifkan',
                'data' => $position->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroyPosition(Position $position): JsonResponse
    {
        try {
            if ($position->employees()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus posisi yang memiliki karyawan',
                ], 422);
            }

            $position->delete();

            return response()->json([
                'success' => true,
                'message' => 'Posisi berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =====================================================
    // EMPLOYEE TYPES CRUD
    // =====================================================

    protected function getEmployeeTypesData(int $companyId, string $search, string $status, int $perPage, int $page): JsonResponse
    {
        $query = EmployeeType::forCompany($companyId)
            ->withCount('employees');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $total = $query->count();
        $employeeTypes = $query->sorted()->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $employeeTypes,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage),
        ]);
    }

    public function storeEmployeeType(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50',
                'description' => 'nullable|string|max:500',
                'color' => 'nullable|string|max:20',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
            ]);

            $companyId = auth()->user()->company_id;

            // Check for duplicate code within the same company
            if (!empty($validated['code'])) {
                $existingCode = EmployeeType::forCompany($companyId)
                    ->where('code', $validated['code'])
                    ->exists();

                if ($existingCode) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kode tipe karyawan sudah digunakan',
                    ], 422);
                }
            }

            // Get max sort_order for this company
            $maxSort = EmployeeType::forCompany($companyId)->max('sort_order') ?? 0;

            $employeeType = EmployeeType::create([
                'company_id' => $companyId,
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'] ?? '#6B7280',
                'sort_order' => $validated['sort_order'] ?? ($maxSort + 1),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tipe Karyawan berhasil ditambahkan',
                'data' => $employeeType,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateEmployeeType(Request $request, EmployeeType $employeeType): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50',
                'description' => 'nullable|string|max:500',
                'color' => 'nullable|string|max:20',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
            ]);

            $companyId = auth()->user()->company_id;

            // Check for duplicate code within the same company, excluding current record
            if (!empty($validated['code'])) {
                $existingCode = EmployeeType::forCompany($companyId)
                    ->where('code', $validated['code'])
                    ->where('id', '!=', $employeeType->id)
                    ->exists();

                if ($existingCode) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kode tipe karyawan sudah digunakan',
                    ], 422);
                }
            }

            $employeeType->update([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'] ?? $employeeType->color,
                'sort_order' => $validated['sort_order'] ?? $employeeType->sort_order,
                'is_active' => $validated['is_active'] ?? $employeeType->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tipe Karyawan berhasil diperbarui',
                'data' => $employeeType->fresh(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function toggleEmployeeType(EmployeeType $employeeType): JsonResponse
    {
        try {
            $employeeType->update([
                'is_active' => !$employeeType->is_active,
            ]);

            $statusLabel = $employeeType->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return response()->json([
                'success' => true,
                'message' => "Tipe Karyawan berhasil {$statusLabel}",
                'data' => $employeeType->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroyEmployeeType(EmployeeType $employeeType): JsonResponse
    {
        try {
            if ($employeeType->employees()->count() > 0) {
                $employeeCount = $employeeType->employees()->count();
                return response()->json([
                    'success' => false,
                    'message' => "Tipe Karyawan \"{$employeeType->name}\" masih digunakan oleh {$employeeCount} karyawan.",
                ], 422);
            }

            $employeeType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tipe Karyawan berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =====================================================
    // LOCATIONS (PLACEMENTS) CRUD
    // =====================================================

    protected function getLocationsData(int $companyId, string $search, string $status, int $perPage, int $page): JsonResponse
    {
        $query = Placement::where('company_id', $companyId)
            ->withCount('employees');

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $total = $query->count();
        $locations = $query->orderBy('name')->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $locations,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage),
            'types' => Placement::$types,
        ]);
    }

    public function storeLocation(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50',
                'type' => 'required|in:' . implode(',', array_keys(Placement::$types)),
                'address' => 'nullable|string|max:500',
            ]);

            $location = Placement::create([
                'company_id' => auth()->user()->company_id,
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'type' => $validated['type'],
                'address' => $validated['address'] ?? null,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lokasi berhasil ditambahkan',
                'data' => $location,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateLocation(Request $request, Placement $location): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50',
                'type' => 'required|in:' . implode(',', array_keys(Placement::$types)),
                'address' => 'nullable|string|max:500',
                'is_active' => 'boolean',
            ]);

            $location->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Lokasi berhasil diperbarui',
                'data' => $location->fresh(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function toggleLocation(Placement $location): JsonResponse
    {
        try {
            $location->update(['is_active' => !$location->is_active]);

            return response()->json([
                'success' => true,
                'message' => $location->is_active ? 'Lokasi diaktifkan' : 'Lokasi dinonaktifkan',
                'data' => $location->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroyLocation(Placement $location): JsonResponse
    {
        try {
            if ($location->employees()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus lokasi yang memiliki karyawan',
                ], 422);
            }

            $location->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lokasi berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage(),
            ], 500);
        }
    }
}
