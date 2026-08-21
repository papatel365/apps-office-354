<?php

namespace App\Services\HRD;

use App\Models\HRD\Department;
use App\Models\HRD\SidebarPermission;
use App\Models\Division;
use App\Models\HRD\EmployeeProfile;
use App\Models\HRD\EmployeeType;
use App\Models\HRD\LeaveEntitlement;
use App\Models\HRD\LeaveType;
use App\Models\HRD\Placement;
use App\Models\HRD\Salary;
use App\Models\HRD\SalaryComponent;
use App\Models\HRD\Shift;
use App\Models\HRD\ShiftSchedule;
use App\Modules\System\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeWizardService
{
    const ACCOUNT_OPTION_EXISTING = 'existing';
    const ACCOUNT_OPTION_NEW = 'new';
    const ACCOUNT_OPTION_NONE = 'none';

    /**
     * Get initial data for the wizard
     */
    public function getInitialData(int $companyId): array
    {
        // Get available members of this company who don't have employee profile linked yet
        $availableMembers = $this->getAvailableMembers($companyId);

        // Get sidebar permission service for menu tree
        $sidebarService = new SidebarPermissionService();

        return [
            'departments' => Department::where('company_id', $companyId)->active()->orderBy('name')->get(),
            'divisions' => Division::where('company_id', $companyId)->active()->orderBy('name')->get(),
            'positions' => \App\Models\HRD\Position::where('company_id', $companyId)->active()->orderBy('name')->get(),
            'placements' => Placement::where('company_id', $companyId)->active()->orderBy('name')->get(),
            'shifts' => Shift::where('company_id', $companyId)->active()->orderBy('name')->get(),
            'leaveTypes' => LeaveType::where('company_id', $companyId)->active()->orderBy('name')->get(),
            'employeeTypes' => EmployeeType::forCompany($companyId)->active()->sorted()->get(['id', 'name', 'code', 'color']),
            'supervisors' => EmployeeProfile::where('employee_profiles.company_id', $companyId)
                ->where('employee_profiles.is_active', true)
                ->whereNotNull('employee_profiles.user_id')
                ->join('users', 'users.id', '=', 'employee_profiles.user_id')
                ->orderBy('users.name')
                ->select('employee_profiles.*')
                ->with('user')
                ->get(),
            'banks' => $this->getBankList(),
            'availableMembers' => $availableMembers,
            'accountOptions' => $this->getAccountOptions(),
            'sidebarMenuTree' => $sidebarService->getMenuTree(),
            'sidebarPermissionStats' => [
                'total' => count($sidebarService->getAllPermissionKeys()),
                'enabled' => 0,
                'disabled' => 0,
                'has_custom' => false,
            ],
        ];
    }

    /**
     * Get members of company who don't have employee profile linked yet
     */
    public function getAvailableMembers(int $companyId): array
    {
        // Get users who are members of this company but don't have employee profile
        return User::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotIn('id', function ($query) {
                $query->select('user_id')
                    ->from('employee_profiles')
                    ->whereNotNull('user_id');
            })
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->display_role,
                    'role_key' => $user->company_role,
                ];
            })
            ->toArray();
    }

    /**
     * Get employee data for editing
     */
    public function getEmployeeData(EmployeeProfile $employee): array
    {
        // Get sidebar permission service for menu tree
        $sidebarService = new SidebarPermissionService();

        // Get current employee permissions
        $employeePermissions = $sidebarService->getEmployeePermissions($employee->id);
        $permissionStats = $sidebarService->getPermissionStats($employee->id);

        // Load relations
        $employee->load([
            'user',
            'department',
            'division',
            'position',
            'placement',
            'employeeType',
            'supervisor',
            'documents',
            'leaveEntitlements' => fn($q) => $q->active(),
            'salaries' => fn($q) => $q->orderBy('period_year', 'desc')->orderBy('period_month', 'desc')->limit(6),
        ]);

        return [
            'employee' => $employee,
            'departments' => Department::where('company_id', $employee->company_id)->active()->orderBy('name')->get(),
            'divisions' => Division::where('company_id', $employee->company_id)->active()->orderBy('name')->get(),
            'positions' => \App\Models\HRD\Position::where('company_id', $employee->company_id)->active()->orderBy('name')->get(),
            'placements' => Placement::where('company_id', $employee->company_id)->active()->orderBy('name')->get(),
            'shifts' => Shift::where('company_id', $employee->company_id)->active()->orderBy('name')->get(),
            'leaveTypes' => LeaveType::where('company_id', $employee->company_id)->active()->orderBy('name')->get(),
            'employeeTypes' => EmployeeType::forCompany($employee->company_id)->active()->sorted()->get(['id', 'name', 'code', 'color']),
            'supervisors' => EmployeeProfile::where('employee_profiles.company_id', $employee->company_id)
                ->where('employee_profiles.is_active', true)
                ->where('employee_profiles.id', '!=', $employee->id)
                ->join('users', 'users.id', '=', 'employee_profiles.user_id')
                ->orderBy('users.name')
                ->select('employee_profiles.*')
                ->with('user')
                ->get(),
            'banks' => $this->getBankList(),
            'sidebarMenuTree' => $sidebarService->getMenuTree(),
            'employeePermissions' => $employeePermissions,
            'sidebarPermissionStats' => $permissionStats,
        ];
    }

    /**
     * Get available account options
     */
    public function getAccountOptions(): array
    {
        return [
            self::ACCOUNT_OPTION_EXISTING => 'Gunakan Akun Existing',
            self::ACCOUNT_OPTION_NEW => 'Buat Akun Baru',
            self::ACCOUNT_OPTION_NONE => 'Tanpa Akun Login',
        ];
    }

    /**
     * Create employee with all related data in atomic transaction
     */
    public function createEmployee(array $data): EmployeeProfile
    {
        return DB::transaction(function () use ($data) {
            $userData = $data['user'] ?? [];
            $employeeData = $data['employee'] ?? [];
            $placementData = $data['placement'] ?? null;
            $shiftData = $data['shift'] ?? null;
            $leaveData = $data['leave'] ?? null;
            $payrollData = $data['payroll'] ?? null;
            $companyId = $data['company_id'];

            $accountOption = $userData['account_option'] ?? self::ACCOUNT_OPTION_NEW;

            if (empty($employeeData['employee_number'])) {
                $employeeData['employee_number'] = $this->generateEmployeeNumber($companyId);
            }

            $userId = null;
            $userName = $userData['name'] ?? $employeeData['full_name'] ?? 'Unknown';

            if ($accountOption === self::ACCOUNT_OPTION_EXISTING) {
                $selectedUserId = $userData['existing_user_id'] ?? null;
                if (!$selectedUserId) {
                    throw new \Exception('Akun user harus dipilih');
                }
                $existingUser = User::where('id', $selectedUserId)->where('company_id', $companyId)->first();
                if (!$existingUser) {
                    throw new \Exception('Akun user tidak valid');
                }
                $existingLink = EmployeeProfile::where('user_id', $selectedUserId)->whereNotNull('user_id')->first();
                if ($existingLink) {
                    throw new \Exception('Akun user sudah terhubung');
                }
                $userId = $selectedUserId;
                $userName = $existingUser->name;
            } elseif ($accountOption === self::ACCOUNT_OPTION_NEW) {
                if (empty($userData['email']) || empty($userData['password'])) {
                    throw new \Exception('Email dan password harus diisi');
                }
                if (User::where('email', $userData['email'])->exists()) {
                    throw new \Exception('Email sudah digunakan');
                }
                $newUser = User::create([
                    'name' => $userData['name'] ?? $employeeData['full_name'] ?? 'Unknown',
                    'username' => $userData['name'] ?? null,
                    'email' => $userData['email'],
                    'phone' => $userData['phone'] ?? null,
                    'password' => Hash::make($userData['password']),
                    'company_id' => $companyId,
                    'is_active' => true,
                ]);
                $userId = $newUser->id;
                $userName = $newUser->name;
            }

            $employeeFullName = $employeeData['full_name'] ?? $userName ?? 'Unknown';
            $maritalStatus = $employeeData['marital_status'] ?? null;
            $punyaAnak = $maritalStatus === 'married' ? ($employeeData['punya_anak'] ?? null) : null;
            $jumlahAnak = null;
            if ($maritalStatus === 'married' && $punyaAnak === true) {
                $jumlahAnak = $employeeData['jumlah_anak'] ?? null;
            }

            $employeeCreateData = [
                'company_id' => $companyId,
                'user_id' => $userId,
                'full_name' => $employeeFullName,
                'nick_name' => $employeeData['nick_name'] ?? null,
                'employee_number' => $employeeData['employee_number'] ?? null,
                'gender' => $employeeData['gender'] ?? null,
                'birth_date' => $employeeData['date_of_birth'] ?? null,
                'birth_place' => $employeeData['place_of_birth'] ?? null,
                'phone' => $userData['phone'] ?? $employeeData['phone'] ?? null,
                'mobile' => $employeeData['mobile'] ?? null,
                'address' => $employeeData['address'] ?? null,
                'city' => $employeeData['city'] ?? null,
                'province' => $employeeData['province'] ?? null,
                'postal_code' => $employeeData['postal_code'] ?? null,
                'ktp_number' => $employeeData['nik'] ?? null,
                'npwp_number' => $employeeData['npwp_number'] ?? null,
                'bpjs_kesehatan' => $employeeData['bpjs_kesehatan'] ?? null,
                'bpjs_ketenagakerjaan' => $employeeData['bpjs_ketenagakerjaan'] ?? null,
                'blood_type' => $employeeData['blood_type'] ?? null,
                'religion' => $employeeData['religion'] ?? null,
                'marital_status' => $maritalStatus,
                'punya_anak' => $punyaAnak,
                'jumlah_anak' => $jumlahAnak,
                'emergency_contact_name' => $employeeData['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $employeeData['emergency_contact_phone'] ?? null,
                'emergency_contact_relation' => $employeeData['emergency_contact_relation'] ?? null,
                'department_id' => $employeeData['department_id'] ?? null,
                'position_id' => $employeeData['position_id'] ?? null,
                'division_id' => $employeeData['division_id'] ?? null,
                'supervisor_id' => $employeeData['supervisor_id'] ?? null,
                'join_date' => $employeeData['join_date'] ?? now(),
                'contract_end_date' => $employeeData['contract_start'] ?? null,
                'contract_end' => $employeeData['contract_end'] ?? null,
                'employee_type_id' => $employeeData['employee_type_id'] ?? null,
                'is_active' => isset($employeeData['is_active']) ? (bool) $employeeData['is_active'] : true,
                'bank_name' => $payrollData['bank_name'] ?? null,
                'bank_account_number' => $payrollData['bank_account'] ?? null,
                'bank_account_holder' => $payrollData['bank_account_name'] ?? null,
            ];

            $employee = EmployeeProfile::create($employeeCreateData);

            if ($placementData && !empty($placementData['placement_id'])) {
                $employee->update([
                    'placement_id' => $placementData['placement_id'],
                    'placement_name' => Placement::find($placementData['placement_id'])?->name,
                ]);
            }

            if ($userId && $shiftData && !($shiftData['skip'] ?? false) && !empty($shiftData['shift_id'])) {
                $shiftStartDate = $shiftData['start_date'] ?? now()->toDateString();
                $shiftEndDate = isset($shiftData['schedule_days']) ? (int) $shiftData['schedule_days'] : 30;
                $currentDate = \Carbon\Carbon::parse($shiftStartDate);
                $endDate = $currentDate->copy()->addDays($shiftEndDate);
                while ($currentDate <= $endDate) {
                    ShiftSchedule::create([
                        'company_id' => $companyId,
                        'employee_id' => $employee->id,
                        'shift_id' => $shiftData['shift_id'],
                        'date' => $currentDate->toDateString(),
                        'notes' => 'Scheduled from onboarding wizard',
                    ]);
                    $currentDate->addDay();
                }
            }

            if ($userId && $leaveData && !($leaveData['skip'] ?? false)) {
                $leaveMode = $leaveData['mode'] ?? 'policy';
                $effectiveDate = $leaveData['effective_date'] ?? now()->toDateString();
                $year = \Carbon\Carbon::parse($effectiveDate)->year;
                if ($leaveMode === 'policy' && !empty($leaveData['policy_leave_type_id'])) {
                    $this->createLeaveEntitlement($companyId, $employee->id, $leaveData['policy_leave_type_id'], $year, $effectiveDate, $leaveData, auth()->id());
                } elseif ($leaveMode === 'manual' && !empty($leaveData['entitlements'])) {
                    foreach ($leaveData['entitlements'] as $entitlement) {
                        if (!empty($entitlement['leave_type_id']) && !empty($entitlement['days'])) {
                            $this->createLeaveEntitlement($companyId, $employee->id, $entitlement['leave_type_id'], $year, $effectiveDate, $entitlement, auth()->id());
                        }
                    }
                }
            }

            if ($payrollData && !($payrollData['skip'] ?? false) && !empty($payrollData['basic_salary'])) {
                $basicSalary = (float) $payrollData['basic_salary'];
                $totalAllowances = 0;
                $totalDeductions = 0;
                $allowanceComponents = [];
                $deductionComponents = [];

                if (!empty($payrollData['allowances'])) {
                    foreach ($payrollData['allowances'] as $allowance) {
                        if (!empty($allowance['name']) && isset($allowance['amount'])) {
                            $amount = (float) $allowance['amount'];
                            $calcType = $allowance['type'] ?? 'fixed';
                            if ($calcType === 'percentage') {
                                $amount = ($basicSalary * $amount) / 100;
                            }
                            $totalAllowances += $amount;
                            $allowanceComponents[] = ['name' => $allowance['name'], 'type' => 'allowance', 'calculation_type' => $calcType, 'original_amount' => (float) $allowance['amount'], 'calculated_amount' => $amount];
                        }
                    }
                }

                if (!empty($payrollData['deductions'])) {
                    foreach ($payrollData['deductions'] as $deduction) {
                        if (!empty($deduction['name']) && isset($deduction['amount'])) {
                            $amount = (float) $deduction['amount'];
                            $calcType = $deduction['type'] ?? 'fixed';
                            if ($calcType === 'percentage') {
                                $amount = ($basicSalary * $amount) / 100;
                            }
                            $totalDeductions += $amount;
                            $deductionComponents[] = ['name' => $deduction['name'], 'type' => 'deduction', 'calculation_type' => $calcType, 'original_amount' => (float) $deduction['amount'], 'calculated_amount' => $amount];
                        }
                    }
                }

                $payrollNotes = json_encode(['allowances' => $allowanceComponents, 'deductions' => $deductionComponents, 'payment_method' => $payrollData['payment_method'] ?? 'transfer', 'bank_name' => $payrollData['bank_name'] ?? null, 'bank_account_number' => $payrollData['bank_account'] ?? null, 'bank_account_name' => $payrollData['bank_account_name'] ?? null], JSON_UNESCAPED_UNICODE);

                $salary = Salary::create([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'employee_id' => $employee->id,
                    'basic_salary' => $basicSalary,
                    'allowances' => $totalAllowances,
                    'deductions' => $totalDeductions,
                    'total_salary' => $basicSalary + $totalAllowances - $totalDeductions,
                    'bank_name' => $payrollData['bank_name'] ?? null,
                    'bank_account_number' => $payrollData['bank_account'] ?? null,
                    'bank_account_holder' => $payrollData['bank_account_name'] ?? null,
                    'period_year' => now()->year,
                    'period_month' => now()->month,
                    'status' => 'pending',
                    'notes' => $payrollNotes,
                ]);

                foreach ($allowanceComponents as $component) {
                    SalaryComponent::create(['salary_id' => $salary->id, 'type' => 'allowance', 'name' => $component['name'], 'calculation_type' => $component['calculation_type'], 'amount' => $component['original_amount']]);
                }
                foreach ($deductionComponents as $component) {
                    SalaryComponent::create(['salary_id' => $salary->id, 'type' => 'deduction', 'name' => $component['name'], 'calculation_type' => $component['calculation_type'], 'amount' => $component['original_amount']]);
                }
            }

            if (!empty($data['sidebar_permissions']) && is_array($data['sidebar_permissions'])) {
                $sidebarService = new SidebarPermissionService();
                $sidebarService->savePermissions($employee->id, $data['sidebar_permissions']);
            }

            return $employee;
        });
    }

    /**
     * Create a single leave entitlement with prorata calculation if needed
     */
    protected function createLeaveEntitlement(int $companyId, int $employeeId, int $leaveTypeId, int $year, string $effectiveDate, array $data, ?int $createdBy): LeaveEntitlement
    {
        $leaveType = LeaveType::find($leaveTypeId);
        $entitledDays = $leaveType?->default_days ?? ($data['days'] ?? 12);

        $calculationMode = $data['calculation_mode'] ?? 'full';
        if ($calculationMode === 'prorata' && !empty($data['join_date'])) {
            $joinDate = \Carbon\Carbon::parse($data['join_date']);
            $yearStart = \Carbon\Carbon::createFromDate($year, 1, 1);
            $yearEnd = \Carbon\Carbon::createFromDate($year, 12, 31);
            $remainingDays = $yearEnd->diffInDays($joinDate) + 1;
            $totalDaysInYear = $yearStart->diffInDays($yearEnd) + 1;
            $entitledDays = (int) round(($entitledDays * $remainingDays) / $totalDaysInYear);
            $entitledDays = max(0, $entitledDays);
        }

        return LeaveEntitlement::create([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'year' => $year,
            'entitled_days' => $entitledDays,
            'used_days' => 0,
            'pending_days' => 0,
            'effective_date' => $effectiveDate,
            'notes' => $data['notes'] ?? null,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Update employee with all related data
     */
    public function updateEmployee(EmployeeProfile $employee, array $data): EmployeeProfile
    {
        return DB::transaction(function () use ($employee, $data) {
            $userData = $data['user'] ?? [];
            $employeeData = $data['employee'] ?? [];
            $placementData = $data['placement'] ?? null;
            $shiftData = $data['shift'] ?? null;
            $leaveData = $data['leave'] ?? null;
            $payrollData = $data['payroll'] ?? null;

            $accountOption = $userData['account_option'] ?? null;
            if ($accountOption !== null) {
                $this->handleUserAccountChange($employee, $userData, $accountOption);
            } else {
                if (!empty($userData)) {
                    $userRelation = $employee->user ?? null;
                    if ($userRelation) {
                        $userUpdate = array_filter([
                            'name' => $userData['name'] ?? null,
                            'email' => $userData['email'] ?? null,
                            'phone' => $userData['phone'] ?? null,
                        ], fn($v) => $v !== null);
                        if (!empty($userUpdate)) {
                            $userRelation->update($userUpdate);
                        }
                    }
                }
            }

            $maritalStatus = $employeeData['marital_status'] ?? null;
            $punyaAnak = null;
            $jumlahAnak = null;
            if ($maritalStatus === 'married') {
                $punyaAnak = $employeeData['punya_anak'] ?? null;
                if ($punyaAnak === true || $punyaAnak === '1' || $punyaAnak === 1) {
                    $jumlahAnak = $employeeData['jumlah_anak'] ?? null;
                }
            }

            $employeeUpdate = array_filter([
                'full_name' => $employeeData['full_name'] ?? null,
                'gender' => $employeeData['gender'] ?? null,
                'birth_date' => $employeeData['date_of_birth'] ?? null,
                'birth_place' => $employeeData['place_of_birth'] ?? null,
                'address' => $employeeData['address'] ?? null,
                'city' => $employeeData['city'] ?? null,
                'province' => $employeeData['province'] ?? null,
                'postal_code' => $employeeData['postal_code'] ?? null,
                'phone' => $employeeData['phone'] ?? null,
                'npwp_number' => $employeeData['npwp_number'] ?? null,
                'bpjs_kesehatan' => $employeeData['bpjs_kesehatan'] ?? null,
                'bpjs_ketenagakerjaan' => $employeeData['bpjs_ketenagkerjaan'] ?? null,
                'ktp_number' => $employeeData['nik'] ?? null,
                'department_id' => $employeeData['department_id'] ?? null,
                'division_id' => $employeeData['division_id'] ?? null,
                'position_id' => $employeeData['position_id'] ?? null,
                'supervisor_id' => $employeeData['supervisor_id'] ?? null,
                'join_date' => $employeeData['join_date'] ?? null,
                'contract_end' => $employeeData['contract_end'] ?? $employeeData['contract_start'] ?? null,
                'employee_type_id' => $employeeData['employee_type_id'] ?? null,
                'is_active' => isset($employeeData['is_active']) ? (bool) $employeeData['is_active'] : null,
                'marital_status' => $maritalStatus,
                'punya_anak' => $punyaAnak,
                'jumlah_anak' => $jumlahAnak,
            ], fn($v) => $v !== null);

            if (!empty($employeeUpdate)) {
                $employee->update($employeeUpdate);
            }

            if ($placementData !== null) {
                if (!empty($placementData['placement_id'])) {
                    $employee->update(['placement_id' => $placementData['placement_id'], 'placement_name' => Placement::find($placementData['placement_id'])?->name]);
                } else {
                    $employee->update(['placement_id' => null, 'placement_name' => null]);
                }
            }

            if ($payrollData !== null && !($payrollData['skip'] ?? false) && !empty($payrollData['basic_salary'])) {
                $basicSalary = (float) $payrollData['basic_salary'];
                $totalAllowances = 0;
                $totalDeductions = 0;
                $allowanceComponents = [];
                $deductionComponents = [];

                if (!empty($payrollData['allowances']) && is_array($payrollData['allowances'])) {
                    foreach ($payrollData['allowances'] as $allowance) {
                        if (!empty($allowance['name']) && isset($allowance['amount'])) {
                            $amount = is_array($allowance['amount']) ? 0 : (float) $allowance['amount'];
                            $calcType = $allowance['type'] ?? 'fixed';
                            if ($calcType === 'percentage') {
                                $amount = ($basicSalary * $amount) / 100;
                            }
                            $totalAllowances += $amount;
                            $allowanceComponents[] = ['name' => $allowance['name'], 'type' => 'allowance', 'calculation_type' => $calcType, 'original_amount' => (float) $allowance['amount'], 'calculated_amount' => $amount];
                        }
                    }
                }

                if (!empty($payrollData['deductions']) && is_array($payrollData['deductions'])) {
                    foreach ($payrollData['deductions'] as $deduction) {
                        if (!empty($deduction['name']) && isset($deduction['amount'])) {
                            $amount = is_array($deduction['amount']) ? 0 : (float) $deduction['amount'];
                            $calcType = $deduction['type'] ?? 'fixed';
                            if ($calcType === 'percentage') {
                                $amount = ($basicSalary * $amount) / 100;
                            }
                            $totalDeductions += $amount;
                            $deductionComponents[] = ['name' => $deduction['name'], 'type' => 'deduction', 'calculation_type' => $calcType, 'original_amount' => (float) $deduction['amount'], 'calculated_amount' => $amount];
                        }
                    }
                }

                $netSalary = $basicSalary + $totalAllowances - $totalDeductions;
                $payrollNotes = json_encode(['allowances' => $allowanceComponents, 'deductions' => $deductionComponents, 'payment_method' => $payrollData['payment_method'] ?? 'transfer'], JSON_UNESCAPED_UNICODE);

                $existingSalary = Salary::where('employee_id', $employee->id)->where('period_year', now()->year)->where('period_month', now()->month)->first();
                $salaryData = [
                    'company_id' => $employee->company_id,
                    'user_id' => $employee->user_id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $basicSalary,
                    'allowances' => $totalAllowances,
                    'deductions' => $totalDeductions,
                    'total_salary' => $netSalary,
                    'bank_name' => $payrollData['bank_name'] ?? null,
                    'bank_account_number' => $payrollData['bank_account'] ?? null,
                    'bank_account_holder' => $payrollData['bank_account_name'] ?? null,
                    'period_year' => now()->year,
                    'period_month' => now()->month,
                    'status' => 'pending',
                    'notes' => $payrollNotes,
                ];

                if ($existingSalary) {
                    $existingSalary->update($salaryData);
                    $salary = $existingSalary;
                } else {
                    $salary = Salary::create($salaryData);
                }

                $salary->components()->delete();
                foreach ($allowanceComponents as $component) {
                    SalaryComponent::create(['salary_id' => $salary->id, 'type' => 'allowance', 'name' => $component['name'], 'calculation_type' => $component['calculation_type'], 'amount' => $component['original_amount']]);
                }
                foreach ($deductionComponents as $component) {
                    SalaryComponent::create(['salary_id' => $salary->id, 'type' => 'deduction', 'name' => $component['name'], 'calculation_type' => $component['calculation_type'], 'amount' => $component['original_amount']]);
                }

                $employee->update([
                    'bank_name' => $payrollData['bank_name'] ?? null,
                    'bank_account_number' => $payrollData['bank_account'] ?? null,
                    'bank_account_holder' => $payrollData['bank_account_name'] ?? null,
                ]);
            } elseif ($payrollData !== null && ($payrollData['skip'] ?? false)) {
                if (!empty($payrollData['bank_name']) || !empty($payrollData['bank_account']) || !empty($payrollData['bank_account_name'])) {
                    $employee->update(['bank_name' => $payrollData['bank_name'] ?? null, 'bank_account_number' => $payrollData['bank_account'] ?? null, 'bank_account_holder' => $payrollData['bank_account_name'] ?? null]);
                }
            }

            if ($employee->user_id && $leaveData !== null && !($leaveData['skip'] ?? false)) {
                if (!empty($leaveData['leave_type_id'])) {
                    $existingEntitlement = LeaveEntitlement::where('employee_id', $employee->id)->where('year', now()->year)->first();
                    if ($existingEntitlement) {
                        $existingEntitlement->update(['leave_type_id' => $leaveData['leave_type_id'], 'entitled_days' => $leaveData['initial_balance'] ?? $existingEntitlement->entitled_days, 'effective_date' => $leaveData['effective_date'] ?? $existingEntitlement->effective_date]);
                    } else {
                        LeaveEntitlement::create(['company_id' => $employee->company_id, 'employee_id' => $employee->id, 'leave_type_id' => $leaveData['leave_type_id'], 'year' => now()->year, 'entitled_days' => $leaveData['initial_balance'] ?? 12, 'used_days' => 0, 'pending_days' => 0, 'effective_date' => $leaveData['effective_date'] ?? now(), 'created_by' => auth()->id()]);
                    }
                }
            }

            if (array_key_exists('sidebar_permissions', $data) && is_array($data['sidebar_permissions'])) {
                $sidebarService = new SidebarPermissionService();
                if (!empty($data['sidebar_permissions'])) {
                    $sidebarService->savePermissions($employee->id, $data['sidebar_permissions']);
                } else {
                    $sidebarService->clearPermissions($employee->id);
                }
            }

            return $employee->fresh(['user', 'department', 'position', 'placement', 'salaries', 'leaveEntitlements']);
        });
    }

    /**
     * Handle user account linking/unlinking
     */
    public function handleUserAccountChange(EmployeeProfile $employee, array $userData, string $accountOption): void
    {
        if ($accountOption === self::ACCOUNT_OPTION_NONE) {
            $employee->update(['user_id' => null]);
            return;
        }

        if ($accountOption === self::ACCOUNT_OPTION_EXISTING) {
            $selectedUserId = $userData['existing_user_id'] ?? null;
            if (!$selectedUserId) {
                throw new \Exception('Akun user harus dipilih');
            }
            $existingUser = User::where('id', $selectedUserId)->where('company_id', $employee->company_id)->first();
            if (!$existingUser) {
                throw new \Exception('Akun user tidak valid');
            }
            $existingLink = EmployeeProfile::where('user_id', $selectedUserId)->where('id', '!=', $employee->id)->whereNotNull('user_id')->first();
            if ($existingLink) {
                throw new \Exception('Akun user sudah terhubung');
            }
            $employee->update(['user_id' => $selectedUserId]);
            return;
        }

        if ($accountOption === self::ACCOUNT_OPTION_NEW) {
            if (empty($userData['email']) || empty($userData['password'])) {
                throw new \Exception('Email dan password harus diisi');
            }
            $emailQuery = User::where('email', $userData['email']);
            if ($employee->user_id) {
                $emailQuery->where('id', '!=', $employee->user_id);
            }
            if ($emailQuery->exists()) {
                throw new \Exception('Email sudah digunakan');
            }
            if ($employee->user) {
                $employee->user->update(['name' => $userData['name'] ?? $employee->full_name, 'email' => $userData['email'], 'phone' => $userData['phone'] ?? null, 'password' => Hash::make($userData['password'])]);
            } else {
                $newUser = User::create(['name' => $userData['name'] ?? $employee->full_name, 'username' => $userData['name'] ?? null, 'email' => $userData['email'], 'phone' => $userData['phone'] ?? null, 'password' => Hash::make($userData['password']), 'company_id' => $employee->company_id, 'is_active' => true]);
                $employee->update(['user_id' => $newUser->id]);
            }
            return;
        }
    }


    /**
     * Generate unique employee number
     */
    protected function generateEmployeeNumber(int $companyId): string
    {
        $year = date('Y');
        $prefix = "EMP-{$year}-";
        $maxAttempts = 20;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $latest = EmployeeProfile::withTrashed()->where('company_id', $companyId)->where('employee_number', 'like', $prefix . '%')->orderBy('employee_number', 'desc')->first();
            if ($latest && preg_match('/(\d+)$/', $latest->employee_number, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            } else {
                $nextNumber = 1;
            }
            $candidateNumber = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $exists = EmployeeProfile::withTrashed()->where('employee_number', $candidateNumber)->exists();
            if (!$exists) {
                return $candidateNumber;
            }
        }

        $timestamp = now()->format('mdHis');
        $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $timestamp . $random;
    }

    /**
     * Get list of Indonesian banks
     */
    protected function getBankList(): array
    {
        return [
            'Bank Central Asia (BCA)',
            'Bank Mandiri',
            'Bank BRI',
            'Bank BNI',
            'Bank BTN',
            'Bank CIMB Niaga',
            'Bank Permata',
            'Bank Danamon',
            'Bank Panin',
            'Bank OCBC NISP',
            'Bank Muamalat',
            'Bank Syariah Indonesia (BSI)',
            'Bank Maybank Indonesia',
            'Bank Sinarmas',
            'Bank Mega',
            'Bank BJB',
            'Bank Nagari',
            'Bankaltimtara',
            'Bank Kalbar',
            'Bankjateng',
            'Bank DIY',
            'Lainnya',
        ];
    }
}