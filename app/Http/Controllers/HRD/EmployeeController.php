<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\HRD\Department;
use App\Models\HRD\EmployeeDocument;
use App\Models\HRD\EmployeeProfile;
use App\Models\HRD\Position;
use App\Modules\System\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * Employee List
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $search = $request->search ?? '';
        $status = $request->status ?? 'all';
        $department = $request->department ?? 'all';
        $division = $request->division ?? 'all';
        $filter = $request->filter ?? '';

        $query = EmployeeProfile::where('company_id', $companyId)
            ->with(['user', 'department', 'division', 'position', 'employeeType']);

        // Search
        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'resigned') {
            $query->where('is_active', false);
        }
        // If 'all', show all employees (both active and resigned)

        // Department filter
        if ($department !== 'all') {
            $query->where('department_id', $department);
        }

        // Division filter
        if ($division !== 'all') {
            $query->where('division_id', $division);
        }

        // Special filters
        if ($filter === 'expiring') {
            $query->expiringContract(30);
        } elseif ($filter === 'new') {
            $query->whereMonth('join_date', now()->month)
                ->whereYear('join_date', now()->year);
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(20);

        $departments = Department::where('company_id', $companyId)->active()->orderBy('name')->get();
        $divisions = Division::where('company_id', $companyId)->active()->orderBy('name')->get();

        // Statistics
        // Total hanya menghitung yang aktif (exclude resign)
        $stats = [
            'total' => EmployeeProfile::where('company_id', $companyId)->where('is_active', true)->count(),
            'active' => EmployeeProfile::where('company_id', $companyId)->active()->count(),
            'contract' => EmployeeProfile::where('company_id', $companyId)->active()->contract()->count(),
            'probation' => EmployeeProfile::where('company_id', $companyId)->active()->where('employment_type', 'probation')->count(),
            'permanent' => EmployeeProfile::where('company_id', $companyId)->active()->where('employment_type', 'permanent')->count(),
            'resigned' => EmployeeProfile::where('company_id', $companyId)->where('is_active', false)->count(),
            'expiring' => EmployeeProfile::where('company_id', $companyId)->active()->expiringContract(30)->count(),
        ];

        // Employee Summary by Employment Type (for chart) - uses employee_type_id relationship
        // Include Resign in the chart data
        $employeeSummary = [
            'total' => EmployeeProfile::where('company_id', $companyId)->where('is_active', true)->count(),
            'total_all' => EmployeeProfile::where('company_id', $companyId)->count(),
            'by_type' => EmployeeProfile::where('company_id', $companyId)
                ->where('is_active', true) // Only active employees in chart by type
                ->whereNotNull('employee_type_id')
                ->selectRaw('employee_type_id, count(*) as count')
                ->groupBy('employee_type_id')
                ->with('employeeType:id,name,code,color')
                ->get()
                ->mapWithKeys(fn($item) => [$item->employeeType->code ?? 'unknown' => $item->count]),
            'resigned' => EmployeeProfile::where('company_id', $companyId)->where('is_active', false)->count(),
            // Fallback counts from employment_type for backward compatibility (active only)
            'permanent' => EmployeeProfile::where('company_id', $companyId)->active()->where('employment_type', 'permanent')->count(),
            'contract' => EmployeeProfile::where('company_id', $companyId)->active()->contract()->count(),
            'probation' => EmployeeProfile::where('company_id', $companyId)->active()->where('employment_type', 'probation')->count(),
            // Get all employee types for the chart
            'employeeTypes' => \App\Models\HRD\EmployeeType::forCompany($companyId)->active()->sorted()->get(['id', 'name', 'code', 'color']),
        ];

        return view('crm.hrd.employees.index', compact(
            'employees', 'departments', 'divisions', 'stats', 'employeeSummary',
            'search', 'status', 'department', 'division', 'filter'
        ));
    }

    /**
     * Show Employee Profile
     */
    public function show(EmployeeProfile $employee): View
    {
        // Verify tenant access
        if ($employee->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized access to employee');
        }

        $employee->load([
            'user', 'department', 'division', 'position', 'placement', 'employeeType', 'supervisor',
            'documents',
            'attendances' => fn($q) => $q->orderBy('date', 'desc')->limit(30),
            'leaves' => fn($q) => $q->orderBy('created_at', 'desc')->limit(10),
            'salaries' => fn($q) => $q->with('components')->orderBy('period_year', 'desc')->orderBy('period_month', 'desc')->limit(6),
            'leaveEntitlements' => fn($q) => $q->active()->orderBy('year', 'desc'),
        ]);

        // Get latest salary with components
        $latestSalary = $employee->salaries->first();
        $salaryDetails = null;
        if ($latestSalary) {
            // Load components if not already loaded
            if (!$latestSalary->relationLoaded('components')) {
                $latestSalary->load('components');
            }
            $notes = json_decode($latestSalary->notes ?? '{}', true);

            // Get components from relationship
            $allowanceComponents = $latestSalary->components->where('type', 'allowance')->values();
            $deductionComponents = $latestSalary->components->where('type', 'deduction')->values();

            $salaryDetails = [
                'basic_salary' => $latestSalary->basic_salary,
                'total_allowances' => $latestSalary->allowances,
                'total_deductions' => $latestSalary->deductions,
                'total_salary' => $latestSalary->total_salary,
                'allowances' => $allowanceComponents,
                'deductions' => $deductionComponents,
                'payment_method' => $notes['payment_method'] ?? $latestSalary->payment_method ?? 'transfer',
                'bank_name' => $latestSalary->bank_name ?? $notes['bank_name'] ?? null,
                'bank_account' => $latestSalary->bank_account_number ?? $notes['bank_account_number'] ?? null,
                'bank_account_name' => $latestSalary->bank_account_holder ?? $notes['bank_account_name'] ?? null,
            ];
        }

        // Calculate attendance statistics
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $monthlyAttendances = $employee->attendances->filter(function($a) use ($currentMonth, $currentYear) {
            return $a->date && $a->date->month === $currentMonth && $a->date->year === $currentYear;
        });

        $todayAttendance = $employee->attendances->firstWhere('date', now()->toDateString());

        $attendanceStats = [
            'today_status' => $todayAttendance ? ($todayAttendance->status ?? '-') : 'Alpha',
            'total_present' => $monthlyAttendances->whereIn('status', ['present', 'hadir'])->count(),
            'total_late' => $monthlyAttendances->where('late_minutes', '>', 0)->count(),
            'total_absent' => $monthlyAttendances->filter(function($a) {
                return in_array($a->status ?? '', ['absent', 'alpha', 'Alpha']);
            })->count(),
            'total_leave' => $monthlyAttendances->filter(function($a) {
                return in_array($a->status ?? '', ['leave', 'cuti', 'Izin']);
            })->count(),
            'total_sick' => $monthlyAttendances->filter(function($a) {
                return in_array($a->status ?? '', ['sick', 'sakit', 'Sakit']);
            })->count(),
        ];

        // Calculate statistics
        $stats = [
            'total_attendance' => $employee->attendances->count(),
            'on_time_rate' => $employee->attendances->count() > 0
                ? round(($employee->attendances->where('late_minutes', 0)->count() / $employee->attendances->count()) * 100, 1)
                : 0,
            'total_leave' => $employee->leaves->count(),
        ];

        return view('crm.hrd.employees.show', compact('employee', 'stats', 'salaryDetails', 'attendanceStats'));
    }

    /**
     * Create Employee - Legacy method (redirect to wizard)
     * @deprecated Use EmployeeWizardController::create instead
     */
    public function create(): View
    {
        // Redirect to wizard controller
        return redirect()->route('administrasi.data_karyawan.wizard.create');
    }

    /**
     * Store Employee
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'nik' => 'nullable|string|max:50',
            'place_of_birth' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'division_id' => 'nullable|exists:divisions,id',
            'position_id' => 'nullable|exists:positions,id',
            'employment_type' => 'nullable|in:permanent,contract,probation,part_time',
            'join_date' => 'nullable|date',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date',
            'basic_salary' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
        ]);

        // Create user first
        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'company_id' => $companyId,
            'is_active' => true,
        ]);

        // Create employee profile
        $employeeData = collect($validated)->except(['name', 'email', 'phone', 'password'])->toArray();
        $employeeData['user_id'] = $newUser->id;
        $employeeData['company_id'] = $companyId;
        $employeeData['is_active'] = true;

        $employee = EmployeeProfile::create($employeeData);

        return redirect()
            ->route('administrasi.data_karyawan.show', $employee->id)
            ->with('success', 'Karyawan berhasil ditambahkan');
    }

    /**
     * Edit Employee - Legacy method (redirect to wizard)
     * @deprecated Use EmployeeWizardController::edit instead
     */
    public function edit(EmployeeProfile $employee): RedirectResponse
    {
        // Redirect to wizard controller
        return redirect()->route('administrasi.data_karyawan.wizard.edit', $employee->id);
    }

    /**
     * Update Employee
     */
    public function update(Request $request, EmployeeProfile $employee): RedirectResponse
    {
        // Verify tenant access
        if ($employee->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized access to employee');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $employee->user_id,
            'phone' => 'nullable|string|max:20',
            'nik' => 'nullable|string|max:50',
            'place_of_birth' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'division_id' => 'nullable|exists:divisions,id',
            'position_id' => 'nullable|exists:positions,id',
            'employment_type' => 'nullable|in:permanent,contract,probation,part_time',
            'join_date' => 'nullable|date',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date',
            'basic_salary' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Update user
        $employee->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        // Update employee profile
        $employeeData = collect($validated)->except(['name', 'email', 'phone'])->toArray();
        $employee->update($employeeData);

        return redirect()
            ->route('administrasi.data_karyawan.show', $employee->id)
            ->with('success', 'Data karyawan berhasil diperbarui');
    }

    /**
     * Delete Employee (Soft Delete)
     * NOTE: Only employee profile is soft deleted, user account is preserved for data integrity
     */
    public function destroy(Request $request, EmployeeProfile $employee): JsonResponse
    {
        try {
            // STEP 1: Verify tenant access using withoutCompanyScope to bypass global scope
            $employeeCheck = EmployeeProfile::withoutGlobalScopes()
                ->where('id', $employee->id)
                ->first();

            if (!$employeeCheck || $employeeCheck->company_id !== auth()->user()->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke data karyawan ini.'
                ], 403);
            }

            // STEP 2: Check protected roles (bypass global scope for user access)
            $user = $employeeCheck->user;
            $protectedRoles = [
                User::ROLE_DEVELOPER,
                User::ROLE_OWNER,
                User::ROLE_PUSAT,
            ];

            if ($user && in_array($user->company_role, $protectedRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak dapat menghapus akun dengan role ini.'
                ], 403);
            }

            // STEP 3: Check self-deletion
            if ($employeeCheck->user_id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak dapat menghapus akun sendiri.'
                ], 403);
            }

            // STEP 4: Begin transaction
            DB::beginTransaction();

            try {
                // Soft delete employee profile (bypass global scope for delete)
                EmployeeProfile::withoutGlobalScopes()
                    ->where('id', $employee->id)
                    ->delete();

                // Create audit log
                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'employee_soft_delete',
                    'description' => "Menghapus karyawan: {$employeeCheck->full_name}",
                    'company_id' => auth()->user()->company_id,
                    'ip_address' => $request->ip(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Karyawan berhasil dihapus.'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error deleting employee', [
                'employee_id' => $employee->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee data for delete confirmation modal
     */
    public function getDeleteData(EmployeeProfile $employee): JsonResponse
    {
        \Log::info('GET DELETE DATA - Request received', [
            'employee_id' => $employee->id,
        ]);

        // Verify tenant access
        if ($employee->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data karyawan ini.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'email' => $employee->user?->email ?? '-',
                'nik' => $employee->nik ?? '-',
                'position' => $employee->position?->name ?? '-',
                'department' => $employee->department?->name ?? '-',
                'status' => $employee->is_active ? 'Aktif' : 'Resign',
            ]
        ]);
    }

    /**
     * Show trashed (deleted) employees
     */
    public function trashed(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        // Only Owner, Developer, and Superadmin can access
        $allowedRoles = [User::ROLE_DEVELOPER, User::ROLE_OWNER, User::ROLE_PUSAT];
        if (!in_array($user->company_role, $allowedRoles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $search = $request->search ?? '';

        $query = EmployeeProfile::onlyTrashed()
            ->where('company_id', $companyId)
            ->with(['user', 'department', 'position']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', "%{$search}%");
                    })
                    ->orWhere('ktp_number', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('deleted_at', 'desc')->paginate(20);

        return view('crm.hrd.employees.trashed', compact('employees', 'search'));
    }

    /**
     * Restore trashed employee
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();

        // Only Owner, Developer, and Superadmin can restore
        $allowedRoles = [User::ROLE_DEVELOPER, User::ROLE_OWNER, User::ROLE_PUSAT];
        if (!in_array($user->company_role, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengembalikan karyawan.'
            ], 403);
        }

        // Bypass global scopes to find the trashed employee
        $employee = EmployeeProfile::withoutGlobalScopes()->onlyTrashed()->find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan.'
            ], 404);
        }

        // Verify tenant access
        if ($employee->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data karyawan ini.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $employeeName = $employee->full_name;

            // Restore user account first (bypass scope)
            if ($employee->user) {
                User::withoutGlobalScopes()->find($employee->user_id)?->restore();
            }

            // Restore employee profile
            $employee->restore();

            // Update employee status to inactive (not active by default)
            $employee->update(['is_active' => false]);

            // Audit Log
            \Log::info('Employee restored', [
                'action' => 'employee_restore',
                'employee_id' => $employee->id,
                'employee_name' => $employeeName,
                'restored_by_user_id' => auth()->id(),
                'restored_by_user_name' => auth()->user()->name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil dikembalikan.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengembalikan karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force delete (permanently delete) trashed employee
     */
    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();

        // Only Developer can permanently delete
        if (!$user->is_developer) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Developer yang dapat menghapus karyawan secara permanen.'
            ], 403);
        }

        // Bypass global scopes to find the trashed employee
        $employee = EmployeeProfile::withoutGlobalScopes()->onlyTrashed()->find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan.'
            ], 404);
        }

        // Verify tenant access
        if ($employee->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data karyawan ini.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $employeeName = $employee->full_name;

            // Permanently delete user account (bypass scope)
            if ($employee->user) {
                User::withoutGlobalScopes()->find($employee->user_id)?->forceDelete();
            }

            // Permanently delete employee profile
            $employee->forceDelete();

            // Audit Log
            \Log::info('Employee permanently deleted', [
                'action' => 'employee_force_delete',
                'employee_id' => $id,
                'employee_name' => $employeeName,
                'deleted_by_user_id' => auth()->id(),
                'deleted_by_user_name' => auth()->user()->name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil dihapus secara permanen.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus karyawan secara permanen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload Document
     */
    public function uploadDocument(Request $request, EmployeeProfile $employee): RedirectResponse
    {
        // Verify tenant access
        if ($employee->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized access to employee');
        }

        $validated = $request->validate([
            'document_type' => 'required|string',
            'title' => 'required|string|max:100',
            'file' => 'required|file|max:10240', // 10MB
            'issued_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
        ]);

        $path = $request->file('file')->store('hrd/documents');

        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'user_id' => $employee->user_id,
            'company_id' => $employee->company_id,
            'document_type' => $validated['document_type'],
            'title' => $validated['title'],
            'file_path' => $path,
            'file_name' => $request->file('file')->getClientOriginalName(),
            'file_size' => $request->file('file')->getSize(),
            'mime_type' => $request->file('file')->getMimeType(),
            'issued_date' => $validated['issued_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Dokumen berhasil diupload');
    }

    /**
     * Export Employees to CSV
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        // Load ALL necessary relations to avoid N+1 queries and ensure proper data
        $employees = EmployeeProfile::where('company_id', $companyId)
            ->with([
                'user',
                'department',
                'division',
                'position',
                'employeeType',
                'placement',
            ])
            ->get();

        // Use EmployeeReportResource for consistent data formatting
        $formattedEmployees = \App\Http\Resources\EmployeeReportResource::collection($employees);

        // Build CSV with all columns
        $csvData = "NIK,ID Karyawan,Nama,Email,No HP,Departemen,Divisi,Jabatan,Tipe Karyawan,Status,Tanggal Bergabung,Tanggal Resign,Alasan Resign\n";

        foreach ($formattedEmployees as $emp) {
            $csvData .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $emp['employee_id'] ?? '',
                $emp['employee_id'] ?? '',
                str_replace('"', '""', $emp['full_name'] ?? ''),
                str_replace('"', '""', $emp['email'] ?? ''),
                $emp['phone'] ?? '',
                str_replace('"', '""', $emp['department'] ?? ''),    // String name, not object
                str_replace('"', '""', $emp['division'] ?? ''),     // String name, not object
                str_replace('"', '""', $emp['position'] ?? ''),     // String name, not object
                str_replace('"', '""', $emp['employee_type'] ?? ''), // String name, not object
                $emp['status_label'] ?? '',
                $emp['join_date_formatted'] ?? '',
                $emp['resign_date_formatted'] ?? '',
                str_replace('"', '""', $emp['resign_reason'] ?? '')
            );
        }

        return response()->streamDownload(
            fn() => print($csvData),
            'employees_' . date('Y-m-d') . '.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    /**
     * Change employee status to Resign
     * Sets is_active = false and saves resign_date + resign_reason
     */
    public function resign(Request $request, EmployeeProfile $employee): JsonResponse
    {
        // Verify tenant access
        if ($employee->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data karyawan ini.'
            ], 403);
        }

        // Validate request
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Check if already resigned
        if (!$employee->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan sudah berstatus resign.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Update employee status
            $employee->update([
                'is_active' => false,
                'resign_date' => now()->format('Y-m-d'),
                'resign_reason' => $validated['reason'],
            ]);

            // Also deactivate the user account
            if ($employee->user_id) {
                User::where('id', $employee->user_id)->update(['is_active' => false]);
            }

            // Audit log
            \Log::channel('single')->info('Employee status changed to Resign', [
                'action' => 'employee_resign',
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'resign_date' => now()->format('Y-m-d'),
                'resign_reason' => $validated['reason'],
                'changed_by_user_id' => auth()->id(),
                'changed_by_user_name' => auth()->user()->name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status karyawan berhasil diubah menjadi Resign.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }
}
