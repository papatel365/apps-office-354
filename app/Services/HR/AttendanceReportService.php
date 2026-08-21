<?php

namespace App\Services\HR;

use App\Models\HRD\Attendance;
use App\Models\HRD\EmployeeProfile;
use App\Services\HRD\AttendanceDailyProcessor;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Attendance Report Service
 *
 * Handles attendance report data aggregation and filtering.
 *
 * NOTE: This service now reads pre-processed attendance data from the database.
 * The AttendanceDailyProcessor runs nightly to create absent (Alpha) records
 * for employees who didn't check in on working days.
 *
 * Directors and owners are excluded based on company_role field.
 */
class AttendanceReportService
{
    protected $companyId;
    protected $reportService;
    protected AttendanceDailyProcessor $dailyProcessor;

    /**
     * Roles that are excluded from attendance calculations.
     */
    protected array $excludedRoles = ['director', 'owner'];

    /**
     * Indonesian month abbreviations
     */
    protected array $monthNames = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    public function __construct(HRReportService $reportService, AttendanceDailyProcessor $dailyProcessor)
    {
        $this->companyId = auth()->user()->company_id;
        $this->reportService = $reportService;
        $this->dailyProcessor = $dailyProcessor;
    }

    /**
     * Get attendance report data
     */
    public function getReportData(array $filters): array
    {
        $month = $filters['month'] ?? now()->month;
        $year = $filters['year'] ?? now()->year;
        $departmentId = $filters['department_id'] ?? null;
        $divisionId = $filters['division_id'] ?? null;
        $employeeId = $filters['employee_id'] ?? null;

        // Calculate payroll period: 29th previous month to 28th selected month
        $periodEnd = Carbon::create($year, $month, 28);
        $periodStart = $periodEnd->copy()->subMonth()->day(29);

        // Build query for the payroll period
        $query = Attendance::where('company_id', $this->companyId)
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->with(['employee.department', 'employee.division', 'employee.position', 'shift']);

        // Apply filters
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($departmentId) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }

        if ($divisionId) {
            $query->whereHas('employee', fn($q) => $q->where('division_id', $divisionId));
        }

        $attendances = $query->orderBy('date', 'asc')->get();

        // Generate period dates array
        $periodDates = $this->generatePeriodDates($month, $year);

        return [
            'attendances' => $attendances,
            'summary' => $this->getSummary($attendances, $month, $year),
            'byEmployee' => $this->groupByEmployee($attendances),
            'byDepartment' => $this->groupByDepartment($attendances),
            'dailyData' => $this->getDailyData($attendances, $year, $month),
            'monthName' => $this->reportService->getMonthOptions()[$month],
            'year' => $year,
            'filters' => $filters,
            // New payroll period data
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'periodDates' => $periodDates,
            'periodStartFormatted' => $periodStart->format('d ') . $this->monthNames[(int)$periodStart->month] . ' ' . $periodStart->year,
            'periodEndFormatted' => $periodEnd->format('d ') . $this->monthNames[(int)$periodEnd->month] . ' ' . $periodEnd->year,
            'periodString' => $periodStart->format('d ') . $this->monthNames[(int)$periodStart->month] . ' ' . $periodStart->year . ' - ' . $periodEnd->format('d ') . $this->monthNames[(int)$periodEnd->month] . ' ' . $periodEnd->year,
        ];
    }

    /**
     * Generate array of dates for the payroll period (29 prev month - 28 selected month)
     */
    public function generatePeriodDates(int $month, int $year): array
    {
        $periodEnd = Carbon::create($year, $month, 28);
        $periodStart = $periodEnd->copy()->subMonth()->day(29);

        $dates = [];
        $current = $periodStart->copy();

        while ($current <= $periodEnd) {
            $dates[] = [
                'date' => $current->copy(),
                'dateString' => $current->format('Y-m-d'),
                'day' => $current->day,
                'dayName' => $current->format('d-') . $this->monthNames[(int)$current->month],
                'dayOfWeek' => $current->dayOfWeek,
                'isWeekend' => in_array($current->dayOfWeek, [Carbon::SUNDAY, Carbon::SATURDAY]),
            ];
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Get attendance matrix for export (employee x date)
     *
     * Simplified: Only H (Hadir) and A (Alfa/Tidak Absen)
     */
    public function getAttendanceMatrix(array $filters): array
    {
        $month = $filters['month'] ?? now()->month;
        $year = $filters['year'] ?? now()->year;
        $departmentId = $filters['department_id'] ?? null;
        $divisionId = $filters['division_id'] ?? null;

        // Calculate payroll period
        $periodEnd = Carbon::create($year, $month, 28);
        $periodStart = $periodEnd->copy()->subMonth()->day(29);

        // Get eligible employees
        $employeeQuery = EmployeeProfile::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->whereHas('user', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereNull('company_role')
                         ->orWhereNotIn('company_role', $this->excludedRoles);
                });
            })
            ->with(['user', 'department', 'position']);

        if ($departmentId) {
            $employeeQuery->where('department_id', $departmentId);
        }

        if ($divisionId) {
            $employeeQuery->where('division_id', $divisionId);
        }

        // Order by employee number (NIP), fallback to full name if empty
        $employees = $employeeQuery->orderByRaw("COALESCE(employee_number, full_name)")->get();

        // Get all attendance records for the period
        $attendances = Attendance::where('company_id', $this->companyId)
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get()
            ->keyBy(fn($a) => $a->employee_id . '_' . $a->date->format('Y-m-d'));

        // Generate period dates
        $periodDates = $this->generatePeriodDates($month, $year);

        // Build matrix
        $matrix = [];
        foreach ($employees as $employee) {
            $row = [
                'employee_id' => $employee->id,
                'name' => $employee->full_name ?? '-',
                'attendance' => [],
                'total_hadir' => 0,
            ];

            foreach ($periodDates as $periodDate) {
                $key = $employee->id . '_' . $periodDate['dateString'];
                $attendance = $attendances->get($key);

                if ($attendance) {
                    $status = $this->mapStatusToCode($attendance->status, $attendance->late_minutes);
                    $row['attendance'][$periodDate['dateString']] = $status;

                    // Count only hadir
                    if ($status === 'H') {
                        $row['total_hadir']++;
                    }
                } else {
                    // No attendance record - mark as A (Alfa/Tidak Absen)
                    $row['attendance'][$periodDate['dateString']] = 'A';
                }
            }

            $matrix[] = $row;
        }

        // Period label: "Bulan Tahun" format (e.g., "Agustus 2026")
        $monthNamesFull = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $periodLabel = ($monthNamesFull[(int)$month] ?? $month) . ' ' . $year;

        return [
            'employees' => $matrix,
            'periodDates' => $periodDates,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'periodString' => $periodLabel, // Just "Bulan Tahun" format
            'totalEmployees' => count($employees),
            'totalWorkingDays' => count(array_filter($periodDates, fn($d) => !$d['isWeekend'])),
        ];
    }

    /**
     * Map attendance status to display code
     */
    protected function mapStatusToCode(string $status, ?int $lateMinutes = null): string
    {
        // Simplified: Only H (Hadir) or A (Alfa/Tidak Absen)
        // Any present status (present, ontime, late, permit, sick, wfh, leave) = H
        // absent status = A
        return match ($status) {
            'present', 'ontime', 'late', 'permit', 'sick', 'wfh', 'leave' => 'H',
            'absent' => 'A',
            default => 'A', // Treat unknown status as absent
        };
    }

    /**
     * Get summary statistics
     *
     * NOTE: 'absent' now includes:
     * - Pre-processed absent records from AttendanceDailyProcessor
     * - Manually created absent records
     * - No longer calculates "missing" attendance on the fly
     *
     * Directors and owners are excluded from total_employees count.
     */
    public function getSummary(Collection $attendances, int $month, int $year): array
    {
        // Calculate payroll period for working days count
        $periodEnd = Carbon::create($year, $month, 28);
        $periodStart = $periodEnd->copy()->subMonth()->day(29);

        // Total active employees this period (excluding directors/owners based on company_role)
        $totalEmployees = EmployeeProfile::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->whereHas('user', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereNull('company_role')
                         ->orWhereNotIn('company_role', $this->excludedRoles);
                });
            })
            ->count();

        // Attendance counts - all counts now come from pre-processed records
        $present = $attendances->whereIn('status', ['present', 'ontime', 'late'])->count();
        $late = $attendances->where('status', 'late')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $permit = $attendances->where('status', 'permit')->count();
        $sick = $attendances->where('status', 'sick')->count();
        $wfh = $attendances->where('status', 'wfh')->count();
        $leave = $attendances->where('status', 'leave')->count();

        // Calculate total working days using the daily processor for the payroll period
        $workingDays = 0;
        $current = $periodStart->copy();
        while ($current <= $periodEnd) {
            if ($this->dailyProcessor->isWorkingDay($current)) {
                $workingDays++;
            }
            $current->addDay();
        }

        // Calculate expected attendance based on employees who joined before month end
        // and have not left yet
        $totalPossibleAttendance = $totalEmployees * $workingDays;

        return [
            'total_employees' => $totalEmployees,
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'permit' => $permit,
            'sick' => $sick,
            'wfh' => $wfh,
            'leave' => $leave,
            'working_days' => $workingDays,
            // Attendance rate = (present + late) / expected
            // Note: This no longer includes absent in the denominator
            'attendance_rate' => $totalPossibleAttendance > 0
                ? round((($present + $late) / $totalPossibleAttendance) * 100, 1)
                : 0,
            'total_late_minutes' => $attendances->sum('late_minutes'),
            'avg_working_hours' => $attendances->avg('working_hours') ?? 0,
        ];
    }

    /**
     * Group attendance by employee
     */
    public function groupByEmployee(Collection $attendances): array
    {
        return $attendances->groupBy('employee_id')->map(function ($group, $employeeId) {
            $first = $group->first();
            $employee = $first->employee;

            return [
                'employee_id' => $employeeId,
                'employee_name' => $employee->full_name ?? '-',
                'department' => $employee->department?->name ?? '-',
                'position' => $employee->position?->name ?? '-',
                'total_days' => $group->count(),
                'present' => $group->whereIn('status', ['present', 'ontime', 'late'])->count(),
                'late' => $group->where('status', 'late')->count(),
                'absent' => $group->where('status', 'absent')->count(),
                'permit' => $group->where('status', 'permit')->count(),
                'sick' => $group->where('status', 'sick')->count(),
                'wfh' => $group->where('status', 'wfh')->count(),
                'leave' => $group->where('status', 'leave')->count(),
                'total_hours' => round($group->sum('working_hours') ?? 0, 1),
                'total_late_minutes' => $group->sum('late_minutes'),
                'avg_working_hours' => round($group->avg('working_hours') ?? 0, 1),
                'check_in_avg' => $group->avg('check_in') ? Carbon::parse($group->avg('check_in'))->format('H:i') : '-',
                'check_out_avg' => $group->avg('check_out') ? Carbon::parse($group->avg('check_out'))->format('H:i') : '-',
            ];
        })->values()->toArray();
    }

    /**
     * Group attendance by department
     */
    public function groupByDepartment(Collection $attendances): array
    {
        $result = [];

        foreach ($attendances as $att) {
            $deptName = $att->employee?->department?->name ?? 'Tanpa Departemen';
            if (!isset($result[$deptName])) {
                $result[$deptName] = [
                    'name' => $deptName,
                    'total' => 0,
                    'present' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'permit' => 0,
                    'sick' => 0,
                    'wfh' => 0,
                    'leave' => 0,
                ];
            }

            $result[$deptName]['total']++;
            if (in_array($att->status, ['present', 'ontime', 'late'])) {
                $result[$deptName]['present']++;
            }
            if ($att->status === 'late') {
                $result[$deptName]['late']++;
            }
            if ($att->status === 'absent') {
                $result[$deptName]['absent']++;
            }
            if ($att->status === 'permit') {
                $result[$deptName]['permit']++;
            }
            if ($att->status === 'sick') {
                $result[$deptName]['sick']++;
            }
            if ($att->status === 'wfh') {
                $result[$deptName]['wfh']++;
            }
            if ($att->status === 'leave') {
                $result[$deptName]['leave']++;
            }
        }

        // Sort by total descending
        usort($result, fn($a, $b) => $b['total'] <=> $a['total']);

        return array_values($result);
    }

    /**
     * Get daily attendance data for charts
     */
    public function getDailyData(Collection $attendances, int $year, int $month): array
    {
        // Use payroll period dates
        $periodEnd = Carbon::create($year, $month, 28);
        $periodStart = $periodEnd->copy()->subMonth()->day(29);

        $dailyData = [];
        $current = $periodStart->copy();

        while ($current <= $periodEnd) {
            $dateStr = $current->format('Y-m-d');
            $dayAttendances = $attendances->filter(fn($a) => $a->date->format('Y-m-d') === $dateStr);

            // Check if this is a working day
            $isWorkingDay = $this->dailyProcessor->isWorkingDay($current);
            $holidayName = $this->dailyProcessor->getHolidayName($current);

            $dailyData[] = [
                'date' => $current->format('d M'),
                'day_name' => $current->format('D'),
                'is_working_day' => $isWorkingDay,
                'is_weekend' => $current->dayOfWeek === Carbon::SUNDAY,
                'is_holiday' => $holidayName !== null,
                'holiday_name' => $holidayName,
                'present' => $dayAttendances->whereIn('status', ['present', 'ontime', 'late'])->count(),
                'late' => $dayAttendances->where('status', 'late')->count(),
                'absent' => $dayAttendances->where('status', 'absent')->count(),
                'permit' => $dayAttendances->where('status', 'permit')->count(),
                'sick' => $dayAttendances->where('status', 'sick')->count(),
                'wfh' => $dayAttendances->where('status', 'wfh')->count(),
            ];

            $current->addDay();
        }

        return $dailyData;
    }

    /**
     * Get working days in a month (excluding Sundays and national holidays)
     *
     * @deprecated Use AttendanceDailyProcessor::countWorkingDaysInMonth() instead
     */
    public function getWorkingDaysInMonth(int $year, int $month): int
    {
        return $this->dailyProcessor->countWorkingDaysInMonth($year, $month);
    }

    /**
     * Get today's attendance summary
     *
     * Excludes directors and owners based on company_role field.
     */
    public function getTodaySummary(): array
    {
        $today = now()->format('Y-m-d');

        // Get eligible employee IDs (excluding directors/owners)
        $eligibleEmployeeIds = EmployeeProfile::where('company_id', $this->companyId)
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

        $attendances = Attendance::where('company_id', $this->companyId)
            ->whereDate('date', $today)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->get();

        return [
            'date' => $today,
            'total_active' => count($eligibleEmployeeIds),
            'present' => $attendances->whereIn('status', ['present', 'ontime', 'late'])->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'permit' => $attendances->where('status', 'permit')->count(),
            'sick' => $attendances->where('status', 'sick')->count(),
            'wfh' => $attendances->where('status', 'wfh')->count(),
        ];
    }
}
