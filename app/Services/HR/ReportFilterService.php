<?php

namespace App\Services\HR;

use App\Models\HRD\Department;
use App\Models\HRD\EmployeeProfile;
use App\Models\HRD\LeaveType;
use App\Models\Division;
use App\Models\HRD\Position;
use Illuminate\Support\Collection;

/**
 * Report Filter Service
 *
 * Provides reusable filtering logic for all HRD reports.
 * This service handles common filters that can be applied across different report types.
 */
class ReportFilterService
{
    protected $companyId;
    protected $reportService;

    public function __construct(HRReportService $reportService)
    {
        $this->companyId = auth()->user()->company_id;
        $this->reportService = $reportService;
    }

    /**
     * Get the company ID
     */
    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    /**
     * Get all available filter options
     */
    public function getFilterOptions(): array
    {
        return [
            'months' => $this->reportService->getMonthOptions(),
            'years' => $this->reportService->getYearOptions(),
            'departments' => $this->reportService->getDepartments(),
            'divisions' => $this->reportService->getDivisions(),
            'positions' => $this->reportService->getPositions(),
            'employees' => $this->reportService->getEmployees(),
            'statuses' => $this->getAttendanceStatuses(),
            'leave_statuses' => $this->getLeaveStatuses(),
            'employee_statuses' => $this->reportService->getEmployeeStatuses(),
            'leave_types' => $this->reportService->getLeaveTypes(),
        ];
    }

    /**
     * Get attendance status options
     */
    public function getAttendanceStatuses(): array
    {
        return [
            'all' => 'Semua',
            'present' => 'Hadir',
            'ontime' => 'Tepat Waktu',
            'late' => 'Terlambat',
            'absent' => 'Tidak Hadir',
            'permit' => 'Izin',
            'sick' => 'Sakit',
            'wfh' => 'WFH',
        ];
    }

    /**
     * Get leave status options
     */
    public function getLeaveStatuses(): array
    {
        return [
            'all' => 'Semua Status',
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /**
     * Get salary payment status options
     */
    public function getSalaryStatuses(): array
    {
        return [
            'all' => 'Semua Status',
            'pending' => 'Menunggu',
            'paid' => 'Lunas',
        ];
    }

    /**
     * Apply common filters to a query builder
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @param string $dateColumn - The date column to filter (e.g., 'date', 'start_date')
     * @param string $dateType - 'month' or 'year' based filtering
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyCommonFilters($query, array $filters, string $dateColumn = 'date', string $dateType = 'month'): mixed
    {
        // Month filter
        if ($dateType === 'month' || $dateType === 'both') {
            $month = $filters['month'] ?? null;
            if ($month) {
                $query->whereRaw("MONTH({$dateColumn}) = ?", [$month]);
            }
        }

        // Year filter
        $year = $filters['year'] ?? null;
        if ($year) {
            $query->whereRaw("YEAR({$dateColumn}) = ?", [$year]);
        }

        // Department filter
        if (isset($filters['department_id']) && $filters['department_id']) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        // Division filter
        if (isset($filters['division_id']) && $filters['division_id']) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('division_id', $filters['division_id']);
            });
        }

        // Employee filter
        if (isset($filters['employee_id']) && $filters['employee_id']) {
            $query->where('employee_id', $filters['employee_id']);
        }

        return $query;
    }

    /**
     * Build query string for export URLs with all active filters
     */
    public function buildExportQuery(array $filters): string
    {
        $exportFilters = [];

        // Only include non-empty filters
        if (!empty($filters['month'])) {
            $exportFilters['month'] = $filters['month'];
        }
        if (!empty($filters['year'])) {
            $exportFilters['year'] = $filters['year'];
        }
        if (!empty($filters['department_id'])) {
            $exportFilters['department_id'] = $filters['department_id'];
        }
        if (!empty($filters['division_id'])) {
            $exportFilters['division_id'] = $filters['division_id'];
        }
        if (!empty($filters['employee_id'])) {
            $exportFilters['employee_id'] = $filters['employee_id'];
        }
        if (!empty($filters['status'])) {
            $exportFilters['status'] = $filters['status'];
        }
        if (!empty($filters['leave_type_id'])) {
            $exportFilters['leave_type_id'] = $filters['leave_type_id'];
        }
        if (!empty($filters['position_id'])) {
            $exportFilters['position_id'] = $filters['position_id'];
        }
        if (!empty($filters['search'])) {
            $exportFilters['search'] = $filters['search'];
        }

        return http_build_query($exportFilters);
    }

    /**
     * Get period label from filters
     */
    public function getPeriodLabel(array $filters): ?string
    {
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;

        if ($month && $year) {
            $monthName = $this->reportService->getMonthOptions()[(int)$month] ?? $month;
            return $monthName . ' ' . $year;
        }

        if ($year) {
            return 'Tahun ' . $year;
        }

        return null;
    }

    /**
     * Get department name by ID
     */
    public function getDepartmentName(?int $departmentId): ?string
    {
        if (!$departmentId) return null;

        return Department::where('id', $departmentId)
            ->value('name');
    }

    /**
     * Get division name by ID
     */
    public function getDivisionName(?int $divisionId): ?string
    {
        if (!$divisionId) return null;

        return Division::where('id', $divisionId)
            ->value('name');
    }

    /**
     * Get employee name by ID
     */
    public function getEmployeeName(?int $employeeId): ?string
    {
        if (!$employeeId) return null;

        return EmployeeProfile::where('id', $employeeId)
            ->value('full_name');
    }

    /**
     * Get leave type name by ID
     */
    public function getLeaveTypeName(?int $leaveTypeId): ?string
    {
        if (!$leaveTypeId) return null;

        return LeaveType::where('id', $leaveTypeId)
            ->value('name');
    }

    /**
     * Get position name by ID
     */
    public function getPositionName(?int $positionId): ?string
    {
        if (!$positionId) return null;

        return Position::where('id', $positionId)
            ->value('name');
    }

    /**
     * Get label for status value
     */
    public function getStatusLabel(string $type, ?string $status): ?string
    {
        if (!$status) return null;

        $statuses = match ($type) {
            'attendance' => $this->getAttendanceStatuses(),
            'leave' => $this->getLeaveStatuses(),
            'salary' => $this->getSalaryStatuses(),
            'employee' => $this->reportService->getEmployeeStatuses(),
            default => [],
        };

        return $statuses[$status] ?? $status;
    }
}
