<?php

namespace App\Services\HR;

use App\Models\HRD\EmployeeProfile;
use App\Models\HRD\Leave;
use App\Models\HRD\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Leave Report Service
 *
 * Handles leave report data aggregation and filtering.
 */
class LeaveReportService
{
    protected $companyId;
    protected $reportService;

    public function __construct(HRReportService $reportService)
    {
        $this->companyId = auth()->user()->company_id;
        $this->reportService = $reportService;
    }

    /**
     * Get leave report data
     */
    public function getReportData(array $filters): array
    {
        $year = $filters['year'] ?? now()->year;
        $month = $filters['month'] ?? null;
        $departmentId = $filters['department_id'] ?? null;
        $divisionId = $filters['division_id'] ?? null;
        $employeeId = $filters['employee_id'] ?? null;
        $leaveTypeId = $filters['leave_type_id'] ?? null;
        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;

        // Build query
        $query = Leave::where('company_id', $this->companyId)
            ->whereYear('start_date', $year)
            ->with(['employee.department', 'employee.division', 'employee.position', 'leaveType']);

        // Apply filters
        if ($month) {
            $query->whereMonth('start_date', $month);
        }

        if ($departmentId) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }

        if ($divisionId) {
            $query->whereHas('employee', fn($q) => $q->where('division_id', $divisionId));
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($leaveTypeId) {
            $query->where('leave_type_id', $leaveTypeId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', fn($eq) => $eq->where('full_name', 'like', "%{$search}%"))
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $leaves = $query->orderBy('start_date', 'desc')->get();

        return [
            'leaves' => $leaves,
            'summary' => $this->getSummary($leaves, $year),
            'byType' => $this->groupByType($leaves),
            'byDepartment' => $this->groupByDepartment($leaves),
            'byStatus' => $this->groupByStatus($leaves),
            'monthlyData' => $this->getMonthlyData($leaves, $year),
            'leaveTypes' => $this->getLeaveTypesWithRemaining(),
            'year' => $year,
            'filters' => $filters,
        ];
    }

    /**
     * Get summary statistics
     */
    public function getSummary(Collection $leaves, int $year): array
    {
        $total = $leaves->count();
        $approved = $leaves->where('status', 'approved')->count();
        $pending = $leaves->where('status', 'pending')->count();
        $rejected = $leaves->where('status', 'rejected')->count();

        $totalDays = $leaves->sum('total_days');
        $approvedDays = $leaves->where('status', 'approved')->sum('total_days');

        // Active employees count
        $activeEmployees = EmployeeProfile::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->count();

        return [
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'total_days' => $totalDays,
            'approved_days' => $approvedDays,
            'pending_days' => $leaves->where('status', 'pending')->sum('total_days'),
            'avg_days_per_request' => $total > 0 ? round($totalDays / $total, 1) : 0,
            'active_employees' => $activeEmployees,
        ];
    }

    /**
     * Group leaves by type
     */
    public function groupByType(Collection $leaves): array
    {
        return $leaves->groupBy(fn($l) => $l->leaveType?->name ?? 'Lainnya')
            ->map(fn($group, $name) => [
                'name' => $name,
                'total' => $group->count(),
                'approved' => $group->where('status', 'approved')->count(),
                'pending' => $group->where('status', 'pending')->count(),
                'rejected' => $group->where('status', 'rejected')->count(),
                'total_days' => $group->sum('total_days'),
            ])
            ->sortByDesc('total')
            ->values()
            ->toArray();
    }

    /**
     * Group leaves by department
     */
    public function groupByDepartment(Collection $leaves): array
    {
        return $leaves->groupBy(fn($l) => $l->employee?->department?->name ?? 'Tanpa Departemen')
            ->map(fn($group, $name) => [
                'name' => $name,
                'total' => $group->count(),
                'total_days' => $group->sum('total_days'),
            ])
            ->sortByDesc('total')
            ->values()
            ->toArray();
    }

    /**
     * Group leaves by status
     */
    public function groupByStatus(Collection $leaves): array
    {
        return [
            [
                'status' => 'approved',
                'label' => 'Disetujui',
                'total' => $leaves->where('status', 'approved')->count(),
                'days' => $leaves->where('status', 'approved')->sum('total_days'),
                'color' => 'green',
            ],
            [
                'status' => 'pending',
                'label' => 'Menunggu',
                'total' => $leaves->where('status', 'pending')->count(),
                'days' => $leaves->where('status', 'pending')->sum('total_days'),
                'color' => 'amber',
            ],
            [
                'status' => 'rejected',
                'label' => 'Ditolak',
                'total' => $leaves->where('status', 'rejected')->count(),
                'days' => $leaves->where('status', 'rejected')->sum('total_days'),
                'color' => 'red',
            ],
            [
                'status' => 'cancelled',
                'label' => 'Dibatalkan',
                'total' => $leaves->where('status', 'cancelled')->count(),
                'days' => $leaves->where('status', 'cancelled')->sum('total_days'),
                'color' => 'gray',
            ],
        ];
    }

    /**
     * Get monthly leave data for charts
     */
    public function getMonthlyData(Collection $leaves, int $year): array
    {
        $monthNames = $this->reportService->getMonthOptions();
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthLeaves = $leaves->filter(fn($l) => Carbon::parse($l->start_date)->month === $month);

            $monthlyData[] = [
                'month' => $monthNames[$month],
                'month_short' => Carbon::create($year, $month, 1)->format('M'),
                'total' => $monthLeaves->count(),
                'approved' => $monthLeaves->where('status', 'approved')->count(),
                'pending' => $monthLeaves->where('status', 'pending')->count(),
                'rejected' => $monthLeaves->where('status', 'rejected')->count(),
                'total_days' => $monthLeaves->sum('total_days'),
            ];
        }

        return $monthlyData;
    }

    /**
     * Get leave types with remaining quota
     * Note: This returns total leaves count per type, not per employee
     */
    public function getLeaveTypesWithRemaining(): Collection
    {
        return LeaveType::where('company_id', $this->companyId)
            ->active()
            ->withCount(['leaves' => function ($query) {
                $query->whereYear('start_date', now()->year)
                    ->where('status', 'approved');
            }])
            ->get()
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'quota' => $type->quota ?? 0,
                    'used' => $type->leaves_count,
                    'remaining' => max(0, ($type->quota ?? 0) - $type->leaves_count),
                ];
            });
    }

    /**
     * Format leave data for export
     */
    public function formatForExport(Collection $leaves): array
    {
        return $leaves->map(function ($leave) {
            return [
                'ID' => $leave->employee?->employee_id ?? '-',
                'Nama' => $leave->employee?->full_name ?? '-',
                'Departemen' => $leave->employee?->department?->name ?? '-',
                'Jenis Cuti' => $leave->leaveType?->name ?? '-',
                'Tanggal Mulai' => Carbon::parse($leave->start_date)->format('d/m/Y'),
                'Tanggal Selesai' => Carbon::parse($leave->end_date)->format('d/m/Y'),
                'Lama (Hari)' => $leave->total_days,
                'Status' => $this->getStatusLabel($leave->status),
                'Alasan' => $leave->reason ?? '-',
                'Approved By' => $leave->approvedBy?->name ?? '-',
                'Tanggal Approval' => $leave->approved_at ? Carbon::parse($leave->approved_at)->format('d/m/Y') : '-',
            ];
        })->toArray();
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'approved' => 'Disetujui',
            'pending' => 'Menunggu',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($status),
        };
    }
}
