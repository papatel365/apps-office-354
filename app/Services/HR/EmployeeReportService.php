<?php

namespace App\Services\HR;

use App\Http\Resources\EmployeeReportResource;
use App\Models\HRD\EmployeeProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Employee Report Service
 *
 * Handles employee report data aggregation and filtering.
 */
class EmployeeReportService
{
    protected $companyId;
    protected $reportService;

    public function __construct(HRReportService $reportService)
    {
        $this->companyId = auth()->user()->company_id;
        $this->reportService = $reportService;
    }

    /**
     * Get employee report data
     */
    public function getReportData(array $filters): array
    {
        $departmentId = $filters['department_id'] ?? null;
        $divisionId = $filters['division_id'] ?? null;
        $positionId = $filters['position_id'] ?? null;
        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;

        // Build query with ALL necessary relations for export
        $query = EmployeeProfile::where('company_id', $this->companyId)
            ->with([
                'user',
                'department',
                'division',
                'position',
                'employeeType',
                'placement',
            ]);

        // Apply filters
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        }

        if ($positionId) {
            $query->where('position_id', $positionId);
        }

        if ($status) {
            switch ($status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'probation':
                    $query->where('employment_type', 'probation');
                    break;
                case 'contract':
                    $query->where('employment_type', 'contract');
                    break;
                case 'resigned':
                    $query->whereNotNull('resign_date');
                    break;
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', "%{$search}%");
                    });
            });
        }

        $employees = $query->orderBy('full_name')->get();

        // Calculate stats using Collection
        $summary = $this->getSummary($employees);
        $byDepartment = $this->groupByDepartment($employees);
        $byStatus = $this->groupByStatus($employees);
        $byEmploymentType = $this->groupByEmploymentType($employees);
        $ageDistribution = $this->getAgeDistribution($employees);
        $tenureDistribution = $this->getTenureDistribution($employees);

        return [
            'employees' => $employees,  // Collection of EmployeeProfile models
            'summary' => $summary,
            'byDepartment' => $byDepartment,
            'byStatus' => $byStatus,
            'byEmploymentType' => $byEmploymentType,
            'ageDistribution' => $ageDistribution,
            'tenureDistribution' => $tenureDistribution,
            'filters' => $filters,
        ];
    }

    /**
     * Get summary statistics
     * IMPORTANT: All counts are calculated from the SAME filtered collection
     * to ensure Single Source of Truth
     */
    public function getSummary(Collection $employees): array
    {
        $total = $employees->count();

        // Active = is_active = true AND no resign_date
        $active = $employees->where('is_active', true)->whereNull('resign_date')->count();

        // Inactive = is_active = false AND no resign_date
        $inactive = $employees->where('is_active', false)->whereNull('resign_date')->count();

        // Resigned = has resign_date (regardless of is_active status)
        // An employee with resign_date is considered resigned even if is_active is still true
        // because resign_date indicates the employee has resigned
        $resigned = $employees->whereNotNull('resign_date')->count();

        // Calculate average age
        $employeesWithBirthDate = $employees->filter(fn($e) => $e->birth_date);
        $avgAge = $employeesWithBirthDate->count() > 0
            ? round($employeesWithBirthDate->map(fn($e) => Carbon::parse($e->birth_date)->age)->avg(), 1)
            : 0;

        // Calculate average tenure (only for non-resigned employees)
        $employeesWithJoinDate = $employees->filter(fn($e) => $e->join_date && !$e->resign_date);
        $avgTenure = $employeesWithJoinDate->count() > 0
            ? round($employeesWithJoinDate->map(fn($e) => now()->diffInYears(Carbon::parse($e->join_date)))->avg(), 1)
            : 0;

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'probation' => $employees->where('employment_type', 'probation')->whereNull('resign_date')->count(),
            'contract' => $employees->where('employment_type', 'contract')->whereNull('resign_date')->count(),
            'permanent' => $employees->where('employment_type', 'permanent')->whereNull('resign_date')->count(),
            'resigned' => $resigned,
            'avg_age' => $avgAge,
            'avg_tenure' => $avgTenure,
            'male' => $employees->where('gender', 'male')->count(),
            'female' => $employees->where('gender', 'female')->count(),
        ];
    }

    /**
     * Group employees by department
     */
    public function groupByDepartment(Collection $employees): array
    {
        $grouped = $employees->groupBy(fn($e) => $e->department?->name ?? 'Tanpa Departemen');

        return $grouped->map(fn($group, $name) => [
            'name' => $name,
            'total' => $group->count(),
            'active' => $group->where('is_active', true)->count(),
            'male' => $group->where('gender', 'male')->count(),
            'female' => $group->where('gender', 'female')->count(),
        ])->sortByDesc('total')->values()->toArray();
    }

    /**
     * Group employees by status
     * Consistent with summary calculation
     */
    public function groupByStatus(Collection $employees): array
    {
        return [
            [
                'status' => 'active',
                'label' => 'Aktif',
                'total' => $employees->where('is_active', true)->whereNull('resign_date')->count(),
                'color' => 'green',
            ],
            [
                'status' => 'inactive',
                'label' => 'Nonaktif',
                'total' => $employees->where('is_active', false)->whereNull('resign_date')->count(),
                'color' => 'gray',
            ],
            [
                'status' => 'resigned',
                'label' => 'Resign',
                'total' => $employees->whereNotNull('resign_date')->count(),
                'color' => 'red',
            ],
        ];
    }

    /**
     * Group employees by employment type
     * Only non-resigned employees are counted
     */
    public function groupByEmploymentType(Collection $employees): array
    {
        // Only count non-resigned employees for employment type breakdown
        $activeEmployees = $employees->whereNull('resign_date');

        return [
            [
                'type' => 'permanent',
                'label' => 'Karyawan Tetap',
                'total' => $activeEmployees->where('employment_type', 'permanent')->count(),
                'color' => 'blue',
            ],
            [
                'type' => 'contract',
                'label' => 'Kontrak',
                'total' => $activeEmployees->where('employment_type', 'contract')->count(),
                'color' => 'amber',
            ],
            [
                'type' => 'probation',
                'label' => 'Masa Percobaan',
                'total' => $activeEmployees->where('employment_type', 'probation')->count(),
                'color' => 'purple',
            ],
            [
                'type' => 'internship',
                'label' => 'Magang',
                'total' => $activeEmployees->where('employment_type', 'internship')->count(),
                'color' => 'cyan',
            ],
        ];
    }

    /**
     * Get age distribution
     */
    public function getAgeDistribution(Collection $employees): array
    {
        $ranges = [
            ['min' => 0, 'max' => 25, 'label' => '< 25'],
            ['min' => 25, 'max' => 30, 'label' => '25-30'],
            ['min' => 30, 'max' => 35, 'label' => '30-35'],
            ['min' => 35, 'max' => 40, 'label' => '35-40'],
            ['min' => 40, 'max' => 45, 'label' => '40-45'],
            ['min' => 45, 'max' => 100, 'label' => '> 45'],
        ];

        $distribution = [];
        foreach ($ranges as $range) {
            $count = $employees->filter(function ($e) use ($range) {
                if (!$e->birth_date) return false;
                $age = Carbon::parse($e->birth_date)->age;
                if ($range['max'] === 100) {
                    return $age >= $range['min'];
                }
                return $age >= $range['min'] && $age < $range['max'];
            })->count();

            $distribution[] = [
                'label' => $range['label'],
                'count' => $count,
            ];
        }

        return $distribution;
    }

    /**
     * Get tenure distribution
     */
    public function getTenureDistribution(Collection $employees): array
    {
        $ranges = [
            ['min' => 0, 'max' => 1, 'label' => '< 1 tahun'],
            ['min' => 1, 'max' => 3, 'label' => '1-3 tahun'],
            ['min' => 3, 'max' => 5, 'label' => '3-5 tahun'],
            ['min' => 5, 'max' => 10, 'label' => '5-10 tahun'],
            ['min' => 10, 'max' => 100, 'label' => '> 10 tahun'],
        ];

        $distribution = [];
        foreach ($ranges as $range) {
            $count = $employees->filter(function ($e) use ($range) {
                if (!$e->join_date) return false;
                $years = now()->diffInYears(Carbon::parse($e->join_date));
                if ($range['max'] === 100) {
                    return $years >= $range['min'];
                }
                return $years >= $range['min'] && $years < $range['max'];
            })->count();

            $distribution[] = [
                'label' => $range['label'],
                'count' => $count,
            ];
        }

        return $distribution;
    }

    /**
     * Format employee data for export (keys must match what view expects)
     * IMPORTANT: This method ensures all relation fields are extracted as STRING names only
     */
    public function formatForExport($employees): array
    {
        // If already an array, return as is
        if (is_array($employees)) {
            return $employees;
        }

        // Accept Collection and convert to array for mapping
        if ($employees instanceof \Illuminate\Support\Collection) {
            $employees = $employees->all();
        }

        // If not array at this point, return empty array
        if (!is_array($employees)) {
            return [];
        }

        return array_map(function ($emp) {
            // If already an array (already formatted), return it
            if (is_array($emp)) {
                return $emp;
            }

            // Use EmployeeReportResource to ensure consistent, flat data
            return (new \App\Http\Resources\EmployeeReportResource($emp))->resolve();
        }, $employees);
    }
}
