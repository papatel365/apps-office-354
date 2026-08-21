<?php

namespace App\Http\Controllers\HRD;

use App\Services\HR\HRReportService;
use App\Services\HR\ReportFilterService;
use App\Services\HR\ReportFilterInfoService;
use Illuminate\Http\Request;

/**
 * Trait ReportPageTrait
 *
 * Provides reusable methods for HRD report pages.
 * Use this trait in controllers that handle report pages.
 *
 * NOTE: The using controller must have these protected properties:
 * - protected HRReportService $reportService
 * - protected ReportFilterService $filterService
 * - protected ReportFilterInfoService $filterInfoService
 */
trait ReportPageTrait
{
    /**
     * Get default filters with current month/year
     */
    protected function getDefaultFilters(Request $request, array $keys = ['month', 'year', 'department_id', 'division_id', 'employee_id', 'status', 'search', 'position_id', 'leave_type_id']): array
    {
        $filters = $request->only($keys);

        // Set defaults for period filters
        if (!isset($filters['month']) || empty($filters['month'])) {
            $filters['month'] = now()->month;
        }
        if (!isset($filters['year']) || empty($filters['year'])) {
            $filters['year'] = now()->year;
        }

        return $filters;
    }

    /**
     * Get common view data for all report pages
     */
    protected function getCommonViewData(array $filters = []): array
    {
        return [
            'filterOptions' => $this->reportService->getFilterOptions(),
            'generatorInfo' => $this->reportService->getGeneratorInfo(),
            'exportQuery' => $this->filterService->buildExportQuery($filters),
        ];
    }

    /**
     * Generate filter info for view
     */
    protected function getFilterInfo(array $filters, array $config = []): array
    {
        $filterOptions = $this->reportService->getFilterOptions();

        return $this->filterInfoService->generate($filters, $filterOptions, $config);
    }

    /**
     * Generate filter info HTML for view
     */
    protected function getFilterInfoHtml(array $filters, array $config = []): string
    {
        $filterOptions = $this->reportService->getFilterOptions();

        return $this->filterInfoService->generateHtml($filters, $filterOptions, $config);
    }

    /**
     * Generate filter info text for exports
     */
    protected function getFilterInfoText(array $filters, array $config = []): string
    {
        $filterOptions = $this->reportService->getFilterOptions();

        return $this->filterInfoService->generateText($filters, $filterOptions, $config);
    }

    /**
     * Get period label for reports
     */
    protected function getPeriodLabel(array $filters): ?string
    {
        return $this->filterService->getPeriodLabel($filters);
    }

    /**
     * Get filter configuration for Attendance report
     */
    protected function getAttendanceFilterConfig(): array
    {
        return [
            'show_period' => true,
            'show_department' => true,
            'show_division' => true,
            'show_employee' => true,
            'show_status' => ['type' => 'attendance'],
        ];
    }

    /**
     * Get filter configuration for Employee report
     */
    protected function getEmployeeFilterConfig(): array
    {
        return [
            'show_department' => true,
            'show_division' => true,
            'show_position' => true,
            'show_employee_status' => true,
            'show_search' => true,
        ];
    }

    /**
     * Get filter configuration for Leave report
     */
    protected function getLeaveFilterConfig(): array
    {
        return [
            'show_year' => true,
            'show_department' => true,
            'show_division' => true,
            'show_employee' => true,
            'show_leave_type' => true,
            'show_status' => ['type' => 'leave'],
        ];
    }

    /**
     * Get filter configuration for Salary report
     */
    protected function getSalaryFilterConfig(): array
    {
        return [
            'show_period' => true,
            'show_department' => true,
            'show_division' => true,
            'show_employee' => true,
            'show_status' => ['type' => 'salary'],
        ];
    }
}
