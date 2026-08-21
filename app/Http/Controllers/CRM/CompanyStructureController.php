<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\HRD\Department;
use App\Models\HRD\Position;
use App\Models\Division;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CompanyStructureController extends Controller
{
    /**
     * Check if user can manage company structure
     */
    protected function canManageCompany(Company $company): bool
    {
        $user = auth()->user();

        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        if ($user->is_director && $user->company_id === $company->id) {
            return true;
        }

        if ($user->company_id === $company->id) {
            return in_array($user->company_role, ['owner', 'admin', 'manager']);
        }

        return false;
    }

    /**
     * Get available employees for department/division head selection
     */
    protected function getAvailableEmployees(Company $company)
    {
        return EmployeeProfile::where('company_id', $company->id)
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->full_name ?? $emp->user->name ?? 'Unknown',
                    'position' => $emp->position ? $emp->position->name : '-',
                    'department' => $emp->department ? $emp->department->name : '-',
                ];
            });
    }

    // =====================================================
    // DEPARTMENT CRUD
    // =====================================================

    public function storeDepartment(Request $request, Company $company)
    {
        if (!$this->canManageCompany($company)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'head_id' => 'nullable|exists:employee_profiles,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate head belongs to this company
        if ($request->head_id) {
            $head = EmployeeProfile::find($request->head_id);
            if ($head->company_id !== $company->id) {
                return response()->json(['success' => false, 'message' => 'Kepala departemen tidak valid'], 422);
            }
        }

        $department = $company->departments()->create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'head_id' => $request->head_id,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Departemen berhasil ditambahkan',
            'department' => $department
        ]);
    }

    public function updateDepartment(Request $request, Company $company, Department $department)
    {
        if (!$this->canManageCompany($company)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Multi-tenant security check
        if ($department->company_id !== $company->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'head_id' => 'nullable|exists:employee_profiles,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate head belongs to this company
        if ($request->head_id) {
            $head = EmployeeProfile::find($request->head_id);
            if ($head->company_id !== $company->id) {
                return response()->json(['success' => false, 'message' => 'Kepala departemen tidak valid'], 422);
            }
        }

        $department->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'head_id' => $request->head_id,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Departemen berhasil diperbarui',
            'department' => $department
        ]);
    }

    public function destroyDepartment(Request $request, Company $company, Department $department)
    {
        if (!$this->canManageCompany($company)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($department->company_id !== $company->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if department is in use
        $inUse = $department->divisions()->exists() || $department->positions()->exists() || $department->employees()->exists();

        if ($inUse) {
            // Soft delete - just deactivate
            $department->update(['is_active' => false]);
            return response()->json([
                'success' => false,
                'message' => 'Departemen masih digunakan dalam struktur organisasi. Status diubah menjadi nonaktif.',
                'deactivated' => true
            ], 422);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Departemen berhasil dihapus'
        ]);
    }

    // =====================================================
    // POSITION CRUD
    // =====================================================

    public function storePosition(Request $request, Company $company)
    {
        if (!$this->canManageCompany($company)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate department belongs to this company
        if ($request->department_id) {
            $dept = Department::find($request->department_id);
            if ($dept->company_id !== $company->id) {
                return response()->json(['success' => false, 'message' => 'Departemen tidak valid'], 422);
            }
        }

        $position = $company->positions()->create([
            'name' => $request->name,
            'code' => $request->code,
            'department_id' => $request->department_id,
            'level' => $request->level,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Posisi berhasil ditambahkan',
            'position' => $position
        ]);
    }

    public function updatePosition(Request $request, Company $company, Position $position)
    {
        if (!$this->canManageCompany($company)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($position->company_id !== $company->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate department belongs to this company
        if ($request->department_id) {
            $dept = Department::find($request->department_id);
            if ($dept->company_id !== $company->id) {
                return response()->json(['success' => false, 'message' => 'Departemen tidak valid'], 422);
            }
        }

        $position->update([
            'name' => $request->name,
            'code' => $request->code,
            'department_id' => $request->department_id,
            'level' => $request->level,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Posisi berhasil diperbarui',
            'position' => $position
        ]);
    }

    public function destroyPosition(Request $request, Company $company, Position $position)
    {
        if (!$this->canManageCompany($company)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($position->company_id !== $company->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if position is in use
        if ($position->employees()->exists()) {
            $position->update(['is_active' => false]);
            return response()->json([
                'success' => false,
                'message' => 'Posisi masih digunakan oleh karyawan. Status diubah menjadi nonaktif.',
                'deactivated' => true
            ], 422);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Posisi berhasil dihapus'
        ]);
    }

    // =====================================================
    // DIVISION CRUD (Enhanced)
    // =====================================================

    public function storeDivision(Request $request, Company $company)
    {
        if (!$this->canManageCompany($company)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate department belongs to this company
        if ($request->department_id) {
            $dept = Department::find($request->department_id);
            if ($dept->company_id !== $company->id) {
                return response()->json(['success' => false, 'message' => 'Departemen tidak valid'], 422);
            }
        }

        $division = $company->divisions()->create([
            'name' => $request->name,
            'code' => $request->code,
            'department_id' => $request->department_id,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Divisi berhasil ditambahkan',
            'division' => $division
        ]);
    }

    public function updateDivision(Request $request, Company $company, Division $division)
    {
        if (!$this->canManageCompany($company)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($division->company_id !== $company->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate department belongs to this company
        if ($request->department_id) {
            $dept = Department::find($request->department_id);
            if ($dept->company_id !== $company->id) {
                return response()->json(['success' => false, 'message' => 'Departemen tidak valid'], 422);
            }
        }

        $division->update([
            'name' => $request->name,
            'code' => $request->code,
            'department_id' => $request->department_id,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Divisi berhasil diperbarui',
            'division' => $division
        ]);
    }

    public function destroyDivision(Request $request, Company $company, Division $division)
    {
        if (!$this->canManageCompany($company)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($division->company_id !== $company->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if division is in use
        if ($division->users()->exists()) {
            $division->update(['is_active' => false]);
            return response()->json([
                'success' => false,
                'message' => 'Divisi masih memiliki anggota. Status diubah menjadi nonaktif.',
                'deactivated' => true
            ], 422);
        }

        $division->delete();

        return response()->json([
            'success' => true,
            'message' => 'Divisi berhasil dihapus'
        ]);
    }

    // =====================================================
    // AJAX HELPERS
    // =====================================================

    public function getDepartments(Company $company)
    {
        $departments = $company->departments()
            ->with('head')
            ->orderBy('name')
            ->get()
            ->map(function ($dept) {
                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'code' => $dept->code,
                    'description' => $dept->description,
                    'head' => $dept->head ? [
                        'id' => $dept->head->id,
                        'name' => $dept->head->full_name ?? 'Unknown',
                    ] : null,
                    'is_active' => $dept->is_active,
                    'positions_count' => $dept->positions()->count(),
                    'divisions_count' => $dept->divisions()->count(),
                    'employees_count' => $dept->employees()->count(),
                ];
            });

        return response()->json(['success' => true, 'departments' => $departments]);
    }

    public function getDivisions(Company $company)
    {
        $divisions = $company->divisions()
            ->with('department')
            ->orderBy('name')
            ->get()
            ->map(function ($div) {
                return [
                    'id' => $div->id,
                    'name' => $div->name,
                    'code' => $div->code ?? '',
                    'description' => $div->description,
                    'department' => $div->department ? [
                        'id' => $div->department->id,
                        'name' => $div->department->name,
                    ] : null,
                    'is_active' => $div->is_active,
                    'members_count' => $div->users()->count(),
                ];
            });

        return response()->json(['success' => true, 'divisions' => $divisions]);
    }

    public function getPositions(Company $company)
    {
        $positions = $company->positions()
            ->with('department')
            ->orderBy('name')
            ->get()
            ->map(function ($pos) {
                return [
                    'id' => $pos->id,
                    'name' => $pos->name,
                    'code' => $pos->code,
                    'level' => $pos->level,
                    'description' => $pos->description,
                    'department' => $pos->department ? [
                        'id' => $pos->department->id,
                        'name' => $pos->department->name,
                    ] : null,
                    'is_active' => $pos->is_active,
                    'employees_count' => $pos->employees()->count(),
                ];
            });

        return response()->json(['success' => true, 'positions' => $positions]);
    }

    public function getMembers(Company $company)
    {
        $members = $company->users()
            ->with(['division', 'employeeProfile.department', 'employeeProfile.position'])
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $emp = $user->employeeProfile;
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_role' => $user->company_role,
                    'company_role_display' => $user->display_role,
                    'division' => $user->division ? [
                        'id' => $user->division->id,
                        'name' => $user->division->name,
                    ] : null,
                    'department' => $emp && $emp->department ? [
                        'id' => $emp->department->id,
                        'name' => $emp->department->name,
                    ] : null,
                    'position' => $emp && $emp->position ? [
                        'id' => $emp->position->id,
                        'name' => $emp->position->name,
                    ] : null,
                    'is_active' => $user->is_active,
                ];
            });

        return response()->json(['success' => true, 'members' => $members]);
    }

    public function getEmployees(Company $company)
    {
        $employees = $this->getAvailableEmployees($company);
        return response()->json(['success' => true, 'employees' => $employees]);
    }

    public function getDivisionsByDepartment(Company $company, Department $department)
    {
        if ($department->company_id !== $company->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $divisions = $company->divisions()
            ->where('department_id', $department->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($div) {
                return [
                    'id' => $div->id,
                    'name' => $div->name,
                ];
            });

        return response()->json(['success' => true, 'divisions' => $divisions]);
    }
}
