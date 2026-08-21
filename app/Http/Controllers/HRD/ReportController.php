<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Attendance;
use App\Models\HRD\Department;
use App\Models\HRD\EmployeeProfile;
use App\Models\HRD\Leave;
use App\Models\HRD\LeaveType;
use App\Models\HRD\Training;
use App\Models\HRD\Recruitment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Roles that are excluded from attendance calculations.
     */
    protected array $excludedRoles = ['director', 'owner'];

    /**
     * Reports Dashboard
     */
    public function index(): View
    {
        return view('crm.hrd.reports.index');
    }

    /**
     * Get eligible employee IDs excluding directors/owners based on company_role.
     */
    protected function getEligibleEmployeeIds(int $companyId): array
    {
        return EmployeeProfile::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->whereHas('user', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereNull('company_role')
                         ->orWhereNotIn('company_role', $this->excludedRoles);
                });
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Attendance Report
     */
    public function attendanceReport(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        // Get eligible employee IDs (excluding directors/owners)
        $eligibleEmployeeIds = $this->getEligibleEmployeeIds($companyId);

        $attendances = Attendance::where('company_id', $companyId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->with(['employee', 'shift'])
            ->orderBy('date')
            ->get();

        // Summary by employee
        $byEmployee = $attendances->groupBy('employee_id')->map(function ($group) {
            $first = $group->first();
            $employee = $first->employee;
            return [
                'employee_name' => $employee?->full_name ?? '-',
                'position' => $employee?->position?->name ?? '-',
                'department' => $employee?->department?->name ?? '-',
                'total_days' => $group->count(),
                'present' => $group->whereIn('status', ['present', 'ontime', 'late'])->count(),
                'late' => $group->where('late_minutes', '>', 0)->count(),
                'absent' => $group->where('status', 'absent')->count(),
                'permit' => $group->where('status', 'permit')->count(),
                'sick' => $group->where('status', 'sick')->count(),
                'total_hours' => number_format($group->sum('working_hours') ?? 0, 1),
            ];
        })->values();

        // Summary by department
        $byDepartment = [];
        foreach ($attendances as $att) {
            $deptName = $att->employee?->department?->name ?? 'Unknown';
            if (!isset($byDepartment[$deptName])) {
                $byDepartment[$deptName] = ['total' => 0, 'late' => 0];
            }
            $byDepartment[$deptName]['total']++;
            if ($att->late_minutes > 0) {
                $byDepartment[$deptName]['late']++;
            }
        }

        return view('crm.hrd.reports.attendance', compact(
            'attendances', 'byEmployee', 'byDepartment', 'month', 'year'
        ));
    }

    /**
     * Leave Report
     */
    public function leaveReport(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $year = $request->year ?? now()->year;

        $leaves = Leave::where('company_id', $companyId)
            ->whereYear('start_date', $year)
            ->with(['employee', 'leaveType'])
            ->orderBy('start_date', 'desc')
            ->get();

        // Summary by type
        $byType = LeaveType::where('company_id', $companyId)
            ->active()
            ->withCount(['leaves' => fn($q) => $q->whereYear('start_date', $year)])
            ->get();

        // Summary by department
        $byDepartment = [];
        foreach ($leaves as $leave) {
            $deptName = $leave->employee?->department?->name ?? 'Unknown';
            if (!isset($byDepartment[$deptName])) {
                $byDepartment[$deptName] = ['total' => 0, 'days' => 0];
            }
            $byDepartment[$deptName]['total']++;
            $byDepartment[$deptName]['days'] += $leave->total_days;
        }

        return view('crm.hrd.reports.leave', compact(
            'leaves', 'byType', 'byDepartment', 'year'
        ));
    }

    /**
     * Payroll Report
     */
    public function payrollReport(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $startMonth = $request->start_month ?? now()->subMonths(5)->month;
        $startYear = $request->start_year ?? now()->subMonths(5)->year;
        $endMonth = $request->end_month ?? now()->month;
        $endYear = $request->end_year ?? now()->year;

        $salaries = Salary::where('company_id', $companyId)
            ->where(function ($q) use ($startYear, $startMonth, $endYear, $endMonth) {
                $q->where(function ($q2) use ($startYear, $startMonth) {
                    $q2->where('period_year', $startYear)
                        ->where('period_month', '>=', $startMonth);
                })->orWhere('period_year', '>', $startYear);
            })
            ->where(function ($q) use ($endYear, $endMonth) {
                $q->where(function ($q2) use ($endYear, $endMonth) {
                    $q2->where('period_year', $endYear)
                        ->where('period_month', '<=', $endMonth);
                })->orWhere('period_year', '<', $endYear);
            })
            ->with(['employee'])
            ->orderBy('period_year')
            ->orderBy('period_month')
            ->get();

        // Monthly summary
        $monthlySummary = $salaries->groupBy(fn($s) => $s->period_year . '-' . str_pad($s->period_month, 2, '0', STR_PAD_LEFT))
            ->map(fn($group) => [
                'period' => $group->first()->period_year . '-' . str_pad($group->first()->period_month, 2, '0', STR_PAD_LEFT),
                'count' => $group->count(),
                'total_gross' => $group->sum(fn($s) => $s->basic_salary + $s->allowances),
                'total_deductions' => $group->sum(fn($s) => $s->late_deduction + $s->bpjs_employee + $s->tax),
                'total_net' => $group->sum('total_salary'),
            ]);

        return view('crm.hrd.reports.payroll', compact(
            'salaries', 'monthlySummary',
            'startMonth', 'startYear', 'endMonth', 'endYear'
        ));
    }

    /**
     * Training Report
     */
    public function trainingReport(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $year = $request->year ?? now()->year;

        $trainings = Training::where('company_id', $companyId)
            ->whereYear('start_date', $year)
            ->orderBy('start_date', 'desc')
            ->get();

        // Summary
        $summary = [
            'total' => $trainings->count(),
            'completed' => $trainings->where('status', 'completed')->count(),
            'upcoming' => $trainings->where('start_date', '>', now())->count(),
            'total_cost' => $trainings->sum('cost'),
        ];

        return view('crm.hrd.reports.training', compact(
            'trainings', 'summary', 'year'
        ));
    }

    /**
     * Recruitment Report
     */
    public function recruitmentReport(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $year = $request->year ?? now()->year;

        $recruitments = Recruitment::where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->get();

        // Summary
        $summary = [
            'total' => $recruitments->count(),
            'hired' => $recruitments->where('stage', 'hiring')->count(),
            'rejected' => $recruitments->where('stage', 'rejected')->count(),
            'interviews' => $recruitments->whereNotNull('interview_date')->count(),
        ];

        return view('crm.hrd.reports.recruitment', compact(
            'recruitments', 'summary', 'year'
        ));
    }

    /**
     * Turnover Report
     */
    public function turnoverReport(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $year = $request->year ?? now()->year;

        // Monthly employee counts
        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthStart = Carbon::create($year, $i, 1)->startOfMonth();
            $monthEnd = Carbon::create($year, $i, 1)->endOfMonth();

            $totalEmployees = EmployeeProfile::where('company_id', $companyId)
                ->where('join_date', '<=', $monthEnd)
                ->where(fn($q) => $q->whereNull('resign_date')->orWhere('resign_date', '>', $monthStart))
                ->count();

            $newHires = EmployeeProfile::where('company_id', $companyId)
                ->whereYear('join_date', $year)
                ->whereMonth('join_date', $i)
                ->count();

            $resigned = EmployeeProfile::where('company_id', $companyId)
                ->whereNotNull('resign_date')
                ->whereYear('resign_date', $year)
                ->whereMonth('resign_date', $i)
                ->count();

            $monthlyData[] = [
                'month' => Carbon::create($year, $i, 1)->format('M'),
                'total' => $totalEmployees,
                'new' => $newHires,
                'resigned' => $resigned,
                'turnover_rate' => $totalEmployees > 0 ? round(($resigned / $totalEmployees) * 100, 1) : 0,
            ];
        }

        return view('crm.hrd.reports.turnover', compact(
            'monthlyData', 'year'
        ));
    }

    /**
     * Export to PDF
     */
    public function exportPdf(Request $request, string $type)
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $data = [
            'company' => $user->company,
            'generated_at' => now()->format('d M Y H:i'),
            'generated_by' => $user->name,
        ];

        $pdf = Pdf::loadView('crm.hrd.reports.pdf.' . $type, $data);

        return $pdf->download('hrd_report_' . $type . '_' . date('Ymd') . '.pdf');
    }

    /**
     * Export to Excel
     */
    public function exportExcel(Request $request, string $type)
    {
        // Similar implementation to export methods
        return response()->streamDownload(
            fn() => print('Excel export for ' . $type),
            'hrd_report_' . $type . '_' . date('Ymd') . '.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
