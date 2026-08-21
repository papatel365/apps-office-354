<?php

namespace App\Services\HR;

/**
 * Report Filter Info Service
 *
 * Generates human-readable filter information for display on report pages and exports.
 */
class ReportFilterInfoService
{
    protected $reportService;
    protected $filterService;

    public function __construct(HRReportService $reportService, ReportFilterService $filterService)
    {
        $this->reportService = $reportService;
        $this->filterService = $filterService;
    }

    /**
     * Generate filter info array for display
     *
     * @param array $filters Active filters
     * @param array $availableOptions Available filter options (from getFilterOptions)
     * @param array $config Configuration for which filters to show
     * @return array ['hasFilters' => bool, 'filterInfo' => array]
     */
    public function generate(array $filters, array $availableOptions = [], array $config = []): array
    {
        $filterInfo = [];
        $hasFilters = false;

        // Period filter (month + year)
        if (!empty($config['show_period'])) {
            $periodLabel = $this->getPeriodLabel($filters, $availableOptions);
            if ($periodLabel) {
                $filterInfo[] = $periodLabel;
                $hasFilters = true;
            }
        }

        // Year only filter
        if (!empty($config['show_year'])) {
            $yearLabel = $this->getYearLabel($filters);
            if ($yearLabel) {
                $filterInfo[] = $yearLabel;
                $hasFilters = true;
            }
        }

        // Department filter
        if (!empty($config['show_department'])) {
            $deptLabel = $this->getDepartmentLabel($filters, $availableOptions);
            if ($deptLabel) {
                $filterInfo[] = $deptLabel;
                $hasFilters = true;
            }
        }

        // Division filter
        if (!empty($config['show_division'])) {
            $divLabel = $this->getDivisionLabel($filters, $availableOptions);
            if ($divLabel) {
                $filterInfo[] = $divLabel;
                $hasFilters = true;
            }
        }

        // Employee filter
        if (!empty($config['show_employee'])) {
            $empLabel = $this->getEmployeeLabel($filters, $availableOptions);
            if ($empLabel) {
                $filterInfo[] = $empLabel;
                $hasFilters = true;
            }
        }

        // Status filter
        if (!empty($config['show_status'])) {
            $statusConfig = $config['show_status'];
            $statusType = is_array($statusConfig) ? ($statusConfig['type'] ?? 'attendance') : $statusConfig;
            $statusLabel = $this->getStatusLabel($filters, $availableOptions, $statusType);
            if ($statusLabel) {
                $filterInfo[] = $statusLabel;
                $hasFilters = true;
            }
        }

        // Leave type filter
        if (!empty($config['show_leave_type'])) {
            $leaveLabel = $this->getLeaveTypeLabel($filters, $availableOptions);
            if ($leaveLabel) {
                $filterInfo[] = $leaveLabel;
                $hasFilters = true;
            }
        }

        // Position filter
        if (!empty($config['show_position'])) {
            $posLabel = $this->getPositionLabel($filters, $availableOptions);
            if ($posLabel) {
                $filterInfo[] = $posLabel;
                $hasFilters = true;
            }
        }

        // Employee status filter (active/inactive/etc)
        if (!empty($config['show_employee_status'])) {
            $empStatusLabel = $this->getEmployeeStatusLabel($filters, $availableOptions);
            if ($empStatusLabel) {
                $filterInfo[] = $empStatusLabel;
                $hasFilters = true;
            }
        }

        // Search filter
        if (!empty($config['show_search']) && !empty($filters['search'])) {
            $filterInfo[] = 'Pencarian: "' . e($filters['search']) . '"';
            $hasFilters = true;
        }

        return [
            'hasFilters' => $hasFilters,
            'filterInfo' => $filterInfo,
        ];
    }

    /**
     * Generate HTML for filter info display
     */
    public function generateHtml(array $filters, array $availableOptions = [], array $config = []): string
    {
        $result = $this->generate($filters, $availableOptions, $config);

        if ($result['hasFilters']) {
            $items = [];
            foreach ($result['filterInfo'] as $info) {
                $items[] = e($info);
            }

            return '<strong>Filter:</strong> ' . implode(' | ', $items);
        }

        return '<strong>Filter:</strong> Tidak menggunakan filter (Seluruh Data)';
    }

    /**
     * Generate plain text for filter info (for exports)
     */
    public function generateText(array $filters, array $availableOptions = [], array $config = []): string
    {
        $result = $this->generate($filters, $availableOptions, $config);

        if ($result['hasFilters']) {
            $items = [];
            foreach ($result['filterInfo'] as $info) {
                $items[] = $info;
            }

            return 'Filter: ' . implode(' | ', $items);
        }

        return 'Filter: Tidak menggunakan filter (Seluruh Data)';
    }

    /**
     * Get period label (month + year)
     */
    protected function getPeriodLabel(array $filters, array $availableOptions): ?string
    {
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;

        if ($month && $year) {
            $months = $availableOptions['months'] ?? $this->reportService->getMonthOptions();
            $monthName = $months[(int)$month] ?? $month;
            return 'Periode: ' . $monthName . ' ' . $year;
        }

        return null;
    }

    /**
     * Get year label
     */
    protected function getYearLabel(array $filters): ?string
    {
        $year = $filters['year'] ?? null;

        if ($year) {
            return 'Tahun: ' . $year;
        }

        return null;
    }

    /**
     * Get department label
     */
    protected function getDepartmentLabel(array $filters, array $availableOptions): ?string
    {
        $departmentId = $filters['department_id'] ?? null;

        if (!$departmentId) return null;

        $departments = $availableOptions['departments'] ?? collect();
        $dept = $departments instanceof \Illuminate\Support\Collection
            ? $departments->firstWhere('id', $departmentId)
            : null;

        $deptName = $dept ? ($dept->name ?? '-') : $this->filterService->getDepartmentName($departmentId);

        return 'Departemen: ' . ($deptName ?? '-');
    }

    /**
     * Get division label
     */
    protected function getDivisionLabel(array $filters, array $availableOptions): ?string
    {
        $divisionId = $filters['division_id'] ?? null;

        if (!$divisionId) return null;

        $divisions = $availableOptions['divisions'] ?? collect();
        $div = $divisions instanceof \Illuminate\Support\Collection
            ? $divisions->firstWhere('id', $divisionId)
            : null;

        $divName = $div ? ($div->name ?? '-') : $this->filterService->getDivisionName($divisionId);

        return 'Divisi: ' . ($divName ?? '-');
    }

    /**
     * Get employee label
     */
    protected function getEmployeeLabel(array $filters, array $availableOptions): ?string
    {
        $employeeId = $filters['employee_id'] ?? null;

        if (!$employeeId) return null;

        $employees = $availableOptions['employees'] ?? collect();
        $emp = $employees instanceof \Illuminate\Support\Collection
            ? $employees->firstWhere('id', $employeeId)
            : null;

        $empName = $emp ? ($emp->full_name ?? '-') : $this->filterService->getEmployeeName($employeeId);

        return 'Karyawan: ' . ($empName ?? '-');
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(array $filters, array $availableOptions, string $type): ?string
    {
        $status = $filters['status'] ?? null;

        if (!$status || $status === 'all') return null;

        $label = $this->filterService->getStatusLabel($type, $status);

        return 'Status: ' . ($label ?? $status);
    }

    /**
     * Get leave type label
     */
    protected function getLeaveTypeLabel(array $filters, array $availableOptions): ?string
    {
        $leaveTypeId = $filters['leave_type_id'] ?? null;

        if (!$leaveTypeId) return null;

        $leaveTypes = $availableOptions['leave_types'] ?? collect();
        $type = $leaveTypes instanceof \Illuminate\Support\Collection
            ? $leaveTypes->firstWhere('id', $leaveTypeId)
            : null;

        $typeName = $type ? ($type->name ?? '-') : $this->filterService->getLeaveTypeName($leaveTypeId);

        return 'Jenis Cuti: ' . ($typeName ?? '-');
    }

    /**
     * Get position label
     */
    protected function getPositionLabel(array $filters, array $availableOptions): ?string
    {
        $positionId = $filters['position_id'] ?? null;

        if (!$positionId) return null;

        $positions = $availableOptions['positions'] ?? collect();
        $pos = $positions instanceof \Illuminate\Support\Collection
            ? $positions->firstWhere('id', $positionId)
            : null;

        $posName = $pos ? ($pos->name ?? '-') : $this->filterService->getPositionName($positionId);

        return 'Jabatan: ' . ($posName ?? '-');
    }

    /**
     * Get employee status label (active/inactive/etc)
     */
    protected function getEmployeeStatusLabel(array $filters, array $availableOptions): ?string
    {
        $status = $filters['status'] ?? null;

        if (!$status || $status === 'all') return null;

        $statuses = $availableOptions['employee_statuses'] ?? $this->reportService->getEmployeeStatuses();
        $label = $statuses[$status] ?? $status;

        return 'Status Karyawan: ' . $label;
    }
}
