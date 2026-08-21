<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\EmployeeProfile;
use App\Services\HRD\EmployeeWizardService;
use App\Traits\DebuggableWizardResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmployeeWizardController extends Controller
{
    use DebuggableWizardResponses;

    protected EmployeeWizardService $service;

    public function __construct(EmployeeWizardService $service)
    {
        $this->service = $service;
    }

    public function create(): \Illuminate\View\View
    {
        $user = auth()->user();
        $data = $this->service->getInitialData($user->company_id);
        $data['mode'] = 'create';
        $data['employee'] = null;
        return view('crm.hrd.employees.wizard', $data);
    }

    /**
     * Store new employee via wizard
     * POST /hrd/employees/wizard
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        $data = $request->all();

        // Log PAYROLL REQUEST
        Log::channel('single')->info('PAYROLL REQUEST', [
            'payroll' => $data['payroll'] ?? null,
            'all_keys' => array_keys($data),
        ]);

        // Parse numeric fields
        $this->parseNumericFields($data);

        // Build rules based on account_option
        $rules = $this->getStoreValidationRules($data);

        try {
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $data['company_id'] = $companyId;

            // Log AFTER parsing numeric fields
            Log::channel('single')->info('PAYROLL AFTER PARSE', [
                'payroll' => $data['payroll'] ?? null,
            ]);

            $employee = $this->service->createEmployee($data);

            Log::channel('single')->info('WIZARD STORE: Employee created successfully', [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'company_id' => $companyId,
            ]);

            return redirect()->route('administrasi.data_karyawan.show', $employee->id)
                ->with('success', 'Karyawan berhasil ditambahkan');

        } catch (ValidationException $e) {
            Log::channel('single')->warning('WIZARD STORE: Validation failed', [
                'errors' => $e->errors(),
                'payload' => $data,
            ]);
            return redirect()->back()->withErrors($e->errors())->withInput();

        } catch (\Illuminate\Database\QueryException $e) {
            Log::channel('single')->error('WIZARD STORE: Database error', [
                'error' => $e->getMessage(),
                'sql' => $e->sql ?? null,
                'bindings' => $e->bindings ?? [],
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $message = $this->getDatabaseErrorMessage($e);
            return redirect()->back()->with('error', $message)->withInput();

        } catch (\Throwable $e) {
            Log::channel('single')->error('WIZARD STORE: Unexpected error', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'payload' => $data,
            ]);

            $message = config('app.debug')
                ? "Error: {$e->getMessage()} ({$e->getFile()}:{$e->getLine()})"
                : 'Terjadi kesalahan pada server. Silakan coba lagi.';

            return redirect()->back()->with('error', $message)->withInput();
        }
    }

    public function edit(EmployeeProfile $employee): \Illuminate\View\View
    {
        if ($employee->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        // =====================================================
        // LAYER 1: CONTROLLER - Employee dari route model binding
        // =====================================================
        Log::info('=== LAYER 1: CONTROLLER (route model binding) ===', [
            'employee_id' => $employee->id ?? 'NULL',
            'employee_class' => get_class($employee),
            'full_name' => $employee->full_name ?? 'NULL',
            'phone' => $employee->phone ?? 'NULL',
            'nick_name' => $employee->nick_name ?? 'NULL',
            'relationLoaded_user' => $employee->relationLoaded('user'),
            'user_name' => $employee->user?->name ?? 'NULL (relation not loaded)',
        ]);

        // Load employee data dengan relations - simpan hasil load ini
        $employeeWithRelations = $this->service->getEmployeeData($employee);
        $data = $employeeWithRelations;
        $data['mode'] = 'edit';

        // =====================================================
        // FIX: Jangan timpa employee yang sudah di-load dengan yang belum di-load
        // Hapus baris ini: $data['employee'] = $employee;
        // Biarkan $data['employee'] tetap berisi employee yang sudah di-load oleh service

        return view('crm.hrd.employees.wizard', $data);
    }

    /**
     * Update employee via wizard
     * PUT /hrd/employees/{employee}/wizard
     */
    public function update(Request $request, EmployeeProfile $employee): \Illuminate\Http\RedirectResponse
    {
        // Verify session is valid
        if (!$request->session()->has('_token')) {
            \Log::channel('single')->error('WIZARD UPDATE: Session invalid or expired', [
                'user_id' => auth()->id(),
                'session_id' => $request->session()->getId(),
            ]);
            return redirect()->route('login')->with('error', 'Session expired. Silakan login ulang.');
        }

        // Verify tenant access
        if ($employee->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        // Reload employee to ensure we have fresh data
        $employee->refresh();

        // Pre-load user relation to avoid lazy loading issues
        if ($employee->user_id) {
            $employee->load('user');
            \Log::channel('single')->info('WIZARD UPDATE: User relation preloaded', [
                'user_id' => $employee->user_id,
                'user_name' => $employee->user?->name,
            ]);
        }

        $user = auth()->user();
        $data = $request->all();

        // DEBUG: Log request data
        Log::channel('single')->info('WIZARD UPDATE: === STARTING UPDATE ===', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'employee_id' => $employee->id,
            'employee_number' => $employee->employee_number,
            'employee_has_user' => !is_null($employee->user_id),
            'request_data_keys' => array_keys($data),
            'user_data_keys' => isset($data['user']) ? array_keys($data['user']) : [],
            'employee_data_keys' => isset($data['employee']) ? array_keys($data['employee']) : [],
            'payroll_data_keys' => isset($data['payroll']) ? array_keys($data['payroll']) : [],
            'payroll_basic_salary' => $data['payroll']['basic_salary'] ?? 'NOT SET',
            'payroll_skip' => $data['payroll']['skip'] ?? 'NOT SET',
            'payroll_bank_name' => $data['payroll']['bank_name'] ?? 'NOT SET',
            'payroll_bank_account' => $data['payroll']['bank_account'] ?? 'NOT SET',
            'payroll_bank_account_name' => $data['payroll']['bank_account_name'] ?? 'NOT SET',
        ]);

        $this->parseNumericFields($data);

        $rules = [
            'user.account_option' => 'nullable|in:existing,new,none',
            'user.existing_user_id' => 'nullable|integer',
            // For UPDATE (edit mode): user.name and user.email are always required
            // Password is nullable (optional in edit mode - only required for NEW account)
            'user.name' => 'required|string|max:100',
            'user.email' => 'required|email',
            'user.password' => 'nullable|string|min:6',
            'employee.department_id' => 'nullable|exists:departments,id',
            'employee.position_id' => 'nullable|exists:positions,id',
            'employee.division_id' => 'nullable|exists:divisions,id',
            'employee.employee_type_id' => 'nullable|exists:employee_types,id',
            'employee.join_date' => 'nullable|date',
            'employee.marital_status' => 'nullable|in:single,married,divorced,widowed',
            'employee.punya_anak' => 'nullable|boolean',
            'employee.jumlah_anak' => 'nullable|integer|min:1|max:20',
        ];

        // Custom validation for married status
        $maritalStatus = $data['employee']['marital_status'] ?? null;
        if ($maritalStatus === 'married') {
            $rules['employee.punya_anak'] = 'required|boolean';
            $punyaAnak = $data['employee']['punya_anak'] ?? null;
            if ($punyaAnak === true || $punyaAnak === '1' || $punyaAnak === 1) {
                $rules['employee.jumlah_anak'] = 'required|integer|min:1|max:20';
            }
        } else {
            $rules['employee.punya_anak'] = 'nullable';
            $rules['employee.jumlah_anak'] = 'nullable';
        }

        // Handle account option changes (only if explicitly set in edit mode)
        $option = $data['user']['account_option'] ?? null;
        if ($option === 'existing') {
            $rules['user.existing_user_id'] = 'required|integer';
        } elseif ($option === 'new') {
            // Creating NEW user account - password is required
            $rules['user.email'] = 'required|email|unique:users,email' . ($employee->user_id ? ',' . $employee->user_id : '');
            $rules['user.password'] = 'required|string|min:6';
        }

        try {
            Log::channel('single')->info('WIZARD UPDATE: Starting validation');
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            Log::channel('single')->info('WIZARD UPDATE: Validation passed');

            Log::channel('single')->info('WIZARD UPDATE: Calling service updateEmployee');
            $employee = $this->service->updateEmployee($employee, $data);
            Log::channel('single')->info('WIZARD UPDATE: Service returned successfully', [
                'employee_id' => $employee->id,
                'updated_employee_name' => $employee->full_name,
            ]);

            return redirect()->route('administrasi.data_karyawan.show', $employee->id)
                ->with('success', 'Karyawan diperbarui');

        } catch (ValidationException $e) {
            Log::channel('single')->error('WIZARD UPDATE: Validation FAILED', [
                'errors' => $e->errors(),
            ]);
            return redirect()->back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            Log::channel('single')->error('WIZARD UPDATE: Exception occurred!', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = config('app.debug')
                ? "Error: {$e->getMessage()} (File: {$e->getFile()}:{$e->getLine()})"
                : 'Terjadi kesalahan pada server.';

            return redirect()->back()->with('error', $message)->withInput();
        }
    }

    // ============================================================
    // QUICK CREATE METHODS - DEPRECATED
    // Quick create for Department, Division, Position, Placement
    // telah dipindahkan ke Pengaturan -> Umum
    // Method-method berikut TIDAK LAGI DIGUNAKAN
    // ============================================================

    /**
     * Quick create Department - DEPRECATED
     * Gunakan Pengaturan -> Umum sebagai gantinya
     */
    // public function quickCreateDepartment(Request $request): \Illuminate\Http\JsonResponse { ... }

    /**
     * Quick create Division - DEPRECATED
     * Gunakan Pengaturan -> Umum sebagai gantinya
     */
    // public function quickCreateDivision(Request $request): \Illuminate\Http\JsonResponse { ... }

    /**
     * Quick create Position - DEPRECATED
     * Gunakan Pengaturan -> Umum sebagai gantinya
     */
    // public function quickCreatePosition(Request $request): \Illuminate\Http\JsonResponse { ... }

    /**
     * Quick create Placement - DEPRECATED
     * Gunakan Pengaturan -> Umum sebagai gantinya
     */
    // public function quickCreatePlacement(Request $request): \Illuminate\Http\JsonResponse { ... }

    /**
     * Quick create Shift - STILL ACTIVE
     * POST /hrd/employees/wizard/quick-shift
     */
    public function quickCreateShift(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->handleAjax('quickCreateShift', $request, function () use ($request) {
            $companyId = auth()->user()->company_id;

            $rules = [
                'name' => 'required|string|max:100',
                'code' => 'nullable|string|max:20',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i',
                'late_tolerance_minutes' => 'nullable|integer|min:0',
                'early_out_tolerance_minutes' => 'nullable|integer|min:0',
                'is_night_shift' => 'nullable|boolean',
                'color' => 'nullable|string|max:20',
                'description' => 'nullable|string|max:500',
            ];

            $data = $this->getValidatedData($request, $rules);

            $shift = \App\Models\HRD\Shift::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'grace_period_minutes' => $data['late_tolerance_minutes'] ?? 15,
                'late_tolerance_minutes' => $data['late_tolerance_minutes'] ?? 15,
                'early_out_tolerance_minutes' => $data['early_out_tolerance_minutes'] ?? 0,
                'is_night_shift' => $request->boolean('is_night_shift', false),
                'color' => $data['color'] ?? '#4F46E5',
                'is_active' => true,
            ]);

            return $this->successResponse('Shift berhasil ditambahkan', [
                'id' => (string) $shift->id,
                'name' => $shift->name,
                'code' => $shift->code ?? '',
                'start_time' => $shift->start_time instanceof \Carbon\Carbon ? $shift->start_time->format('H:i') : $shift->start_time,
                'end_time' => $shift->end_time instanceof \Carbon\Carbon ? $shift->end_time->format('H:i') : $shift->end_time,
                'is_night_shift' => $shift->is_night_shift,
                'color' => $shift->color ?? '#4F46E5',
            ]);
        });
    }

    /**
     * Quick create Leave Type
     * POST /hrd/employees/wizard/quick-leave-type
     */
    public function quickCreateLeaveType(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->handleAjax('quickCreateLeaveType', $request, function () use ($request) {
            $companyId = auth()->user()->company_id;

            $rules = [
                'name' => 'required|string|max:100',
                'code' => 'nullable|string|max:20',
                'color' => 'nullable|string|max:20',
                'default_days' => 'nullable|integer|min:0',
                'is_paid' => 'nullable|boolean',
                'requires_document' => 'nullable|boolean',
                'max_consecutive_days' => 'nullable|integer|min:1',
                'min_advance_days' => 'nullable|integer|min:0',
                'description' => 'nullable|string|max:500',
            ];

            $data = $this->getValidatedData($request, $rules);

            $leaveType = \App\Models\HRD\LeaveType::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'color' => $data['color'] ?? '#10B981',
                'default_days' => $data['default_days'] ?? 12,
                'is_paid' => $request->boolean('is_paid', true),
                'requires_document' => $request->boolean('requires_document', false),
                'max_consecutive_days' => $data['max_consecutive_days'] ?? null,
                'min_advance_days' => $data['min_advance_days'] ?? 0,
                'is_active' => true,
            ]);

            return $this->successResponse('Jenis cuti berhasil ditambahkan', [
                'id' => (string) $leaveType->id,
                'name' => $leaveType->name,
                'code' => $leaveType->code ?? '',
                'color' => $leaveType->color ?? '#10B981',
                'default_days' => $leaveType->default_days ?? 12,
                'is_paid' => $leaveType->is_paid,
            ]);
        });
    }

    // ============================================================
    // GET DATA METHODS - Untuk dropdown refresh
    // ============================================================

    /**
     * Get placements for dropdown
     * GET /hrd/employees/wizard/get-placements
     */
    public function getPlacements(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->handleAjax('getPlacements', $request, function () use ($request) {
            $companyId = auth()->user()->company_id;

            $placements = \App\Models\HRD\Placement::where('company_id', $companyId)
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'address', 'city'])
                ->map(fn($p) => [
                    'id' => (string) $p->id,
                    'name' => $p->name,
                    'code' => $p->code ?? '',
                    'address' => $p->address ?? '',
                    'city' => $p->city ?? '',
                ]);

            return $this->successResponse('Data penempatan', $placements);
        });
    }

    /**
     * Get shifts for dropdown
     * GET /hrd/employees/wizard/get-shifts
     */
    public function getShifts(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->handleAjax('getShifts', $request, function () use ($request) {
            $companyId = auth()->user()->company_id;

            $shifts = \App\Models\HRD\Shift::where('company_id', $companyId)
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'start_time', 'end_time', 'is_night_shift', 'color'])
                ->map(fn($s) => [
                    'id' => (string) $s->id,
                    'name' => $s->name,
                    'code' => $s->code ?? '',
                    'start_time' => $s->start_time instanceof \Carbon\Carbon ? $s->start_time->format('H:i') : $s->start_time,
                    'end_time' => $s->end_time instanceof \Carbon\Carbon ? $s->end_time->format('H:i') : $s->end_time,
                    'is_night_shift' => $s->is_night_shift,
                    'color' => $s->color ?? '#4F46E5',
                ]);

            return $this->successResponse('Data shift', $shifts);
        });
    }

    /**
     * Get leave types for dropdown
     * GET /hrd/employees/wizard/get-leave-types
     */
    public function getLeaveTypes(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->handleAjax('getLeaveTypes', $request, function () use ($request) {
            $companyId = auth()->user()->company_id;

            $leaveTypes = \App\Models\HRD\LeaveType::where('company_id', $companyId)
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'color', 'default_days', 'is_paid'])
                ->map(fn($lt) => [
                    'id' => (string) $lt->id,
                    'name' => $lt->name,
                    'code' => $lt->code ?? '',
                    'color' => $lt->color ?? '#10B981',
                    'default_days' => $lt->default_days ?? 12,
                    'is_paid' => $lt->is_paid,
                ]);

            return $this->successResponse('Data jenis cuti', $leaveTypes);
        });
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    protected function parseNumericFields(array &$data): void
    {
        // Basic salary parsing
        if (isset($data['payroll']['basic_salary'])) {
            $val = $data['payroll']['basic_salary'];
            $data['payroll']['basic_salary'] = is_numeric($val) ? (float) $val : (float) str_replace([',', '.'], '', $val);
        }

        // Parse allowances array
        if (isset($data['payroll']['allowances']) && is_array($data['payroll']['allowances'])) {
            foreach ($data['payroll']['allowances'] as &$allowance) {
                if (isset($allowance['amount'])) {
                    $allowance['amount'] = is_numeric($allowance['amount'])
                        ? (float) $allowance['amount']
                        : (float) str_replace([',', '.'], '', $allowance['amount']);
                }
            }
        }

        // Parse deductions array
        if (isset($data['payroll']['deductions']) && is_array($data['payroll']['deductions'])) {
            foreach ($data['payroll']['deductions'] as &$deduction) {
                if (isset($deduction['amount'])) {
                    $deduction['amount'] = is_numeric($deduction['amount'])
                        ? (float) $deduction['amount']
                        : (float) str_replace([',', '.'], '', $deduction['amount']);
                }
            }
        }

        // NIK parsing
        if (isset($data['employee']['nik'])) {
            $data['employee']['nik'] = str_replace('.', '', $data['employee']['nik']);
        }

        // Parse sidebar_permissions from JSON string to array
        if (isset($data['sidebar_permissions']) && is_string($data['sidebar_permissions'])) {
            $decoded = json_decode($data['sidebar_permissions'], true);
            if (is_array($decoded)) {
                $data['sidebar_permissions'] = $decoded;
            } else {
                $data['sidebar_permissions'] = [];
            }
        }
    }

    protected function getStoreValidationRules(array $data): array
    {
        $rules = [
            // User account fields (account_option removed from UI, defaults to 'new')
            'user.account_option' => 'nullable|in:existing,new,none',
            'user.existing_user_id' => 'nullable|integer',
            'user.name' => 'nullable|string|max:100',
            'user.email' => 'nullable|email',
            'user.password' => 'nullable|string|min:6',
            // Employee personal data fields
            'employee.full_name' => 'nullable|string|max:150',
            'employee.nick_name' => 'nullable|string|max:50',
            'employee.nik' => 'nullable|string|max:20',
            'employee.npwp_number' => 'nullable|string|max:20',
            'employee.bpjs_kesehatan' => 'nullable|string|max:13',
            'employee.bpjs_ketenagakerjaan' => 'nullable|string|max:16',
            'employee.place_of_birth' => 'nullable|string|max:100',
            'employee.date_of_birth' => 'nullable|date',
            'employee.gender' => 'nullable|in:male,female',
            'employee.religion' => 'nullable|string|max:20',
            'employee.marital_status' => 'nullable|in:single,married,divorced,widowed',
            'employee.punya_anak' => 'nullable|boolean',
            'employee.jumlah_anak' => 'nullable|integer|min:1|max:20',
            'employee.blood_type' => 'nullable|in:A,B,AB,O',
            'employee.phone' => 'nullable|string|max:20',
            'employee.mobile' => 'nullable|string|max:20',
            'employee.address' => 'nullable|string|max:500',
            'employee.city' => 'nullable|string|max:100',
            'employee.province' => 'nullable|string|max:100',
            'employee.postal_code' => 'nullable|string|max:10',
            'employee.emergency_contact_name' => 'nullable|string|max:100',
            'employee.emergency_contact_phone' => 'nullable|string|max:20',
            'employee.emergency_contact_relation' => 'nullable|string|max:50',
            // Employment fields
            'employee.employee_number' => 'nullable|string|max:50',
            'employee.department_id' => 'nullable|exists:departments,id',
            'employee.position_id' => 'nullable|exists:positions,id',
            'employee.division_id' => 'nullable|exists:divisions,id',
            'employee.supervisor_id' => 'nullable|exists:employee_profiles,id',
            'employee.employee_type_id' => 'nullable|exists:employee_types,id',
            'employee.join_date' => 'nullable|date',
            'employee.contract_start' => 'nullable|date',
            'employee.contract_end' => 'nullable|date',
            'employee.is_active' => 'nullable|in:1,true,0,false',
            // Sidebar permissions (Step 8) - accepts JSON string or array
            'sidebar_permissions' => 'nullable',
        ];

        // Custom validation: if marital_status is married, punya_anak is required
        $maritalStatus = $data['employee']['marital_status'] ?? null;
        if ($maritalStatus === 'married') {
            $rules['employee.punya_anak'] = 'required|boolean';
            $punyaAnak = $data['employee']['punya_anak'] ?? null;
            if ($punyaAnak === true || $punyaAnak === '1' || $punyaAnak === 1) {
                $rules['employee.jumlah_anak'] = 'required|integer|min:1|max:20';
            }
        } else {
            // If not married, reset these fields
            $rules['employee.punya_anak'] = 'nullable';
            $rules['employee.jumlah_anak'] = 'nullable';
        }

        $accountOption = $data['user']['account_option'] ?? 'new';

        if ($accountOption === 'existing') {
            $rules['user.existing_user_id'] = 'required|integer';
        } elseif ($accountOption === 'new') {
            // User Account Fields - REQUIRED
            $rules['user.name'] = 'required|string|min:4|max:100|regex:/^[a-zA-Z0-9._]+$/';
            $rules['user.email'] = 'required|email|unique:users,email';
            $rules['user.password'] = 'required|string|min:6';
        }

        // Step 1 Required Fields - Personal Data (ALWAYS REQUIRED)
        // A. Akun Login
        $rules['user.name'] = $rules['user.name'] ?? 'required|string|min:4|max:100|regex:/^[a-zA-Z0-9._]+$/';
        $rules['user.email'] = $rules['user.email'] ?? 'required|email|unique:users,email';
        $rules['user.password'] = $rules['user.password'] ?? 'required|string|min:6';

        // B. Data Pribadi
        $rules['employee.full_name'] = 'required|string|max:150';
        $rules['employee.nick_name'] = 'required|string|max:50';
        $rules['employee.phone'] = 'required|string|max:20|regex:/^[0-9]+$/|min:10|max:15';
        $rules['employee.nik'] = 'required|string|min:16|max:16|regex:/^[0-9]+$/';
        $rules['employee.place_of_birth'] = 'required|string|max:100';
        $rules['employee.date_of_birth'] = 'required|date|before_or_equal:today';
        $rules['employee.gender'] = 'required|in:male,female';
        $rules['employee.religion'] = 'required|string|max:20';
        $rules['employee.marital_status'] = 'required|in:single,married,divorced,widowed';
        $rules['employee.blood_type'] = 'required|in:A,B,AB,O';
        $rules['employee.address'] = 'required|string|max:500';
        $rules['employee.city'] = 'required|string|max:100';
        $rules['employee.province'] = 'required|string|max:100';
        $rules['employee.postal_code'] = 'required|string|min:5|max:5|regex:/^[0-9]+$/';

        // Step 2 Required Fields - Employment Data (ALWAYS REQUIRED)
        $rules['employee.department_id'] = 'required|exists:departments,id';
        $rules['employee.division_id'] = 'required|exists:divisions,id';
        $rules['employee.position_id'] = 'required|exists:positions,id';

        // Dynamic supervisor_id: required only if company already has employees
        // First employee can be created without supervisor
        $companyId = auth()->user()->company_id;
        $hasEmployees = EmployeeProfile::where('company_id', $companyId)->exists();
        if ($hasEmployees) {
            $rules['employee.supervisor_id'] = 'required|exists:employee_profiles,id,company_id,' . $companyId;
        } else {
            $rules['employee.supervisor_id'] = 'nullable|exists:employee_profiles,id,company_id,' . $companyId;
        }

        $rules['employee.employee_type_id'] = 'required|exists:employee_types,id';
        $rules['employee.contract_start'] = 'required|date';

        // Step 3 Required Fields - Placement Data (ALWAYS REQUIRED)
        $rules['placement.placement_id'] = 'required|exists:employee_placements,id';
        $rules['placement.start_date'] = 'required|date';
        $rules['placement.notes'] = 'nullable|string|max:500';

        return $rules;
    }

    protected function getDatabaseErrorMessage(\Illuminate\Database\QueryException $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Duplicate entry') && str_contains($message, 'employee_number')) {
            return 'Nomor karyawan sudah digunakan. Silakan generate nomor baru.';
        }

        if (str_contains($message, 'Duplicate entry')) {
            return 'Data duplikat ditemukan. Silakan periksa kembali.';
        }

        if (str_contains($message, 'foreign key constraint')) {
            return 'Data tidak valid. Pastikan departemen, divisi, atau posisi yang dipilih benar.';
        }

        if (str_contains($message, 'Column') && str_contains($message, 'not found')) {
            return "Kolom database tidak ditemukan. Pastikan schema sudah dimigrate: {$message}";
        }

        if (str_contains($message, 'Incorrect datetime format')) {
            return "Format tanggal tidak valid: {$message}";
        }

        if (str_contains($message, 'Incorrect integer value')) {
            return "Tipe data integer tidak valid: {$message}";
        }

        return config('app.debug')
            ? "Database Error: {$message}"
            : 'Terjadi kesalahan database. Silakan coba lagi.';
    }
}
