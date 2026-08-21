<?php

namespace App\Services\HR;

use App\Models\Company;
use App\Models\HRD\Attendance;
use App\Models\HRD\Department;
use App\Models\HRD\EmployeeProfile;
use App\Models\HRD\Leave;
use App\Models\HRD\LeaveType;
use App\Models\HRD\Overtime;
use App\Models\HRD\Position;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * HR Report Service
 *
 * Handles all HR report data aggregation and filtering.
 */
class HRReportService
{
    protected $user;
    protected $companyId;
    protected $company;

    public function __construct()
    {
        $this->user = auth()->user();
        $this->companyId = $this->user->company_id;
        $this->company = $this->user->company;
    }

    /**
     * Get company data for reports
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * Get report generator info
     */
    public function getGeneratorInfo(): array
    {
        $bulanIndonesia = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $day = now()->format('d');
        $month = $bulanIndonesia[(int)now()->format('n')];
        $year = now()->format('Y');
        $time = now()->format('H:i:s');

        return [
            'generated_at' => "{$day} {$month} {$year}, {$time}",
            'generated_by' => $this->user->name,
            'company_name' => $this->company?->name ?? 'Office 354',
            'company_id' => $this->companyId,
        ];
    }

    /**
     * Get filter options for reports
     */
    public function getFilterOptions(): array
    {
        return [
            'months' => $this->getMonthOptions(),
            'years' => $this->getYearOptions(),
            'departments' => $this->getDepartments(),
            'divisions' => $this->getDivisions(),
            'positions' => $this->getPositions(),
            'employees' => $this->getEmployees(),
            'statuses' => $this->getEmployeeStatuses(),
            'employee_statuses' => $this->getEmployeeStatuses(),
            'leave_types' => $this->getLeaveTypes(),
            'leave_statuses' => $this->getLeaveStatuses(),
            'overtime_statuses' => $this->getOvertimeStatuses(),
            'salary_statuses' => $this->getSalaryStatuses(),
        ];
    }

    /**
     * Get divisions grouped by department for filter dropdowns
     */
    public function getDivisionsByDepartment(): array
    {
        $departments = $this->getDepartments();
        $divisions = $this->getDivisions();

        $result = [];
        foreach ($departments as $dept) {
            $result[$dept->id] = [
                'department' => $dept,
                'divisions' => $divisions->where('department_id', $dept->id)->values(),
            ];
        }
        // Add divisions without department
        $result[0] = [
            'department' => null,
            'divisions' => $divisions->whereNull('department_id')->values(),
        ];

        return $result;
    }

    /**
     * Get month options for filters
     */
    public function getMonthOptions(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    /**
     * Get year options (current year + 5 years back)
     */
    public function getYearOptions(): array
    {
        $years = [];
        $currentYear = now()->year;
        for ($i = 0; $i <= 5; $i++) {
            $year = $currentYear - $i;
            $years[$year] = $year;
        }
        return $years;
    }

    /**
     * Get all departments
     */
    public function getDepartments(): Collection
    {
        return Department::where('company_id', $this->companyId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Get all divisions
     * Returns divisions from the Division model linked to departments
     */
    public function getDivisions(): Collection
    {
        return \App\Models\Division::where('company_id', $this->companyId)
            ->active()
            ->with('department')
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);
    }

    /**
     * Get all positions
     */
    public function getPositions(): Collection
    {
        return Position::where('company_id', $this->companyId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Get all active employees
     */
    public function getEmployees(): Collection
    {
        return EmployeeProfile::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);
    }

    /**
     * Get employee status options
     */
    public function getEmployeeStatuses(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            'probation' => 'Masa Percobaan',
            'contract' => 'Kontrak',
            'resigned' => 'Resign',
        ];
    }

    /**
     * Get leave status options
     */
    public function getLeaveStatuses(): array
    {
        return [
            'pending' => 'Menunggu',
            'approved_supervisor' => 'Disetujui Supervisor',
            'approved' => 'Disetujui HR',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /**
     * Get overtime status options
     */
    public function getOvertimeStatuses(): array
    {
        return [
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];
    }

    /**
     * Get salary payment status options
     */
    public function getSalaryStatuses(): array
    {
        return [
            'pending' => 'Menunggu',
            'paid' => 'Lunas',
        ];
    }

    /**
     * Get leave type options
     */
    public function getLeaveTypes(): Collection
    {
        return LeaveType::where('company_id', $this->companyId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Calculate employee age
     */
    public function calculateAge(Carbon $birthDate): int
    {
        return $birthDate->age;
    }

    /**
     * Calculate years of service
     */
    public function calculateYearsOfService(Carbon $joinDate): float
    {
        return round(now()->diffInYears($joinDate), 1);
    }

    /**
     * Format duration in hours to readable format
     */
    public function formatDuration(float $hours): string
    {
        $h = floor($hours);
        $m = round(($hours - $h) * 60);
        return "{$h}h {$m}m";
    }

    /**
     * Get current month name
     */
    public function getCurrentMonthName(): string
    {
        return $this->getMonthOptions()[now()->month];
    }

    /**
     * Get current year
     */
    public function getCurrentYear(): int
    {
        return now()->year;
    }
}
