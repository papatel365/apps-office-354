<?php

namespace App\Services\HR;

use App\Models\HRD\Salary;
use App\Models\HRD\EmployeeProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Salary Report Service
 *
 * Handles salary/payroll report data aggregation and filtering.
 * Uses actual salary data from HRD\Salary model.
 */
class SalaryReportService
{
    protected $companyId;
    protected $reportService;

    public function __construct(HRReportService $reportService)
    {
        $this->companyId = auth()->user()->company_id;
        $this->reportService = $reportService;
    }

    /**
     * Get salary summary data for dashboard card
     */
    public function getSummaryData(array $filters = []): array
    {
        $year = $filters['year'] ?? now()->year;
        $month = $filters['month'] ?? now()->month;

        // Check if Salary model exists and has data
        $hasData = Salary::where('company_id', $this->companyId)->exists();

        if (!$hasData) {
            return [
                'total_salary_records' => 0,
                'total_paid' => 0,
                'total_pending' => 0,
                'total_amount' => 0,
                'total_employees' => 0,
                'avg_salary' => 0,
                'has_data' => false,
            ];
        }

        // Get salary data for the current period
        $salaries = Salary::where('company_id', $this->companyId)
            ->where('period_year', $year)
            ->where('period_month', $month);

        // Get total records
        $totalRecords = (clone $salaries)->count();

        // Get paid count
        $paidCount = (clone $salaries)->where('payment_status', 'paid')->count();

        // Get pending count
        $pendingCount = (clone $salaries)->where('payment_status', 'pending')->count();

        // Get total amount (paid only)
        $totalAmount = (clone $salaries)
            ->where('payment_status', 'paid')
            ->sum('total_salary');

        // Get total basic salary
        $totalBasicSalary = (clone $salaries)->sum('basic_salary');

        // Get total allowances
        $totalAllowances = (clone $salaries)->sum('allowances');

        // Get total deductions
        $totalDeductions = (clone $salaries)->sum('deductions');

        // Get unique employees
        $totalEmployees = (clone $salaries)->distinct('employee_id')->count('employee_id');

        // Calculate average salary
        $avgSalary = $totalRecords > 0 ? $totalAmount / $totalRecords : 0;

        return [
            'total_salary_records' => $totalRecords,
            'total_paid' => $paidCount,
            'total_pending' => $pendingCount,
            'total_amount' => $totalAmount,
            'total_basic_salary' => $totalBasicSalary,
            'total_allowances' => $totalAllowances,
            'total_deductions' => $totalDeductions,
            'total_employees' => $totalEmployees,
            'avg_salary' => $avgSalary,
            'has_data' => true,
        ];
    }

    /**
     * Get salary report data
     */
    public function getReportData(array $filters): array
    {
        $year = $filters['year'] ?? now()->year;
        $month = $filters['month'] ?? now()->month;
        $departmentId = $filters['department_id'] ?? null;
        $divisionId = $filters['division_id'] ?? null;
        $employeeId = $filters['employee_id'] ?? null;
        $status = $filters['status'] ?? null;

        // First, get employee IDs that match the filters (before joining with salaries)
        $employeeQuery = EmployeeProfile::where('company_id', $this->companyId);

        if ($departmentId) {
            $employeeQuery->where('department_id', $departmentId);
        }

        if ($divisionId) {
            $employeeQuery->where('division_id', $divisionId);
        }

        if ($employeeId) {
            $employeeQuery->where('id', $employeeId);
        }

        $filteredEmployeeIds = $employeeQuery->pluck('id')->toArray();

        // Build query for actual salary data
        $query = Salary::where('salaries.company_id', $this->companyId)
            ->where('salaries.period_year', $year)
            ->where('salaries.period_month', $month)
            ->whereIn('salaries.employee_id', $filteredEmployeeIds);

        if ($status && $status !== 'all') {
            $query->where('salaries.payment_status', $status);
        }

        $query->orderBy('employee_id');

        $salaries = $query->get();

        // Load employee relationships
        $employeeIds = $salaries->pluck('employee_id')->toArray();
        $employees = EmployeeProfile::whereIn('id', $employeeIds)
            ->with(['department:id,name', 'position:id,name', 'division:id,name'])
            ->get()
            ->keyBy('id');

        // Transform salary data
        $salaryData = $salaries->map(function ($sal) use ($employees) {
            $employee = $employees->get($sal->employee_id);
            return [
                'employee_id' => $employee?->employee_number ?? '-',
                'employee_name' => $employee?->full_name ?? '-',
                'department' => $employee?->department?->name ?? '-',
                'division' => $employee?->division?->name ?? '-',
                'position' => $employee?->position?->name ?? '-',
                'basic_salary' => (float) $sal->basic_salary,
                'allowances' => (float) $sal->allowances,
                'deductions' => (float) $sal->deductions,
                'bonus' => 0,
                'total_salary' => (float) $sal->total_salary,
                'payment_status' => $sal->payment_status,
            ];
        });

        // Calculate summary
        $summary = [
            'total_employees' => $salaryData->count(),
            'total_basic_salary' => $salaryData->sum('basic_salary'),
            'total_allowances' => $salaryData->sum('allowances'),
            'total_deductions' => $salaryData->sum('deductions'),
            'total_bonus' => 0,
            'total_salary' => $salaryData->sum('total_salary'),
            'avg_salary' => $salaryData->count() > 0 ? $salaryData->avg('total_salary') : 0,
        ];

        return [
            'salaries' => $salaryData,
            'summary' => $summary,
            'has_payroll_data' => $salaryData->count() > 0,
            'is_template' => false,
            'year' => $year,
            'month' => $month,
            'monthName' => $this->reportService->getMonthOptions()[$month],
            'filters' => $filters,
        ];
    }

    /**
     * Format salary data for export
     */
    public function formatForExport(Collection $salaries): array
    {
        return $salaries->map(function ($salary) {
            $statusLabel = match ($salary['payment_status'] ?? 'pending') {
                'paid' => 'Lunas',
                'pending' => 'Menunggu',
                default => ucfirst($salary['payment_status'] ?? '-'),
            };

            return [
                $salary['employee_id'] ?? '-',
                $salary['employee_name'] ?? '-',
                $salary['department'] ?? '-',
                $salary['position'] ?? '-',
                'Rp ' . number_format($salary['basic_salary'] ?? 0, 0, ',', '.'),
                'Rp ' . number_format($salary['allowances'] ?? 0, 0, ',', '.'),
                'Rp ' . number_format($salary['deductions'] ?? 0, 0, ',', '.'),
                'Rp ' . number_format($salary['total_salary'] ?? 0, 0, ',', '.'),
                $statusLabel,
            ];
        })->toArray();
    }
}
