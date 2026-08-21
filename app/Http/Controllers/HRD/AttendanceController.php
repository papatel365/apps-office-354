<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Attendance;
use App\Models\HRD\Department;
use App\Models\HRD\EmployeeProfile;
use App\Models\HRD\EmployeeAccountLinkRequest;
use App\Models\HRD\Placement;
use App\Models\HRD\Shift;
use App\Services\HRD\AttendanceDailyProcessor;
use App\Services\HRD\AttendanceSummaryService;
use App\Services\HRD\EmployeeAccountLinkService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{
    protected EmployeeAccountLinkService $linkService;
    protected AttendanceDailyProcessor $dailyProcessor;
    protected AttendanceSummaryService $summaryService;

    public function __construct(
        EmployeeAccountLinkService $linkService,
        AttendanceDailyProcessor $dailyProcessor,
        AttendanceSummaryService $summaryService
    ) {
        $this->linkService = $linkService;
        $this->dailyProcessor = $dailyProcessor;
        $this->summaryService = $summaryService;
    }

    /**
     * Check if user can view all attendance data.
     * Uses UserPermissionService as single source of truth.
     */
    protected function canViewAllAttendance($user): bool
    {
        $service = \App\Services\Permission\UserPermissionService::forUser($user);
        return $service->isGlobalScope('attendances');
    }

    /**
     * Attendance List
     *
     * LOGIC:
     * - Shows ALL eligible employees (not just those with attendance records)
     * - Employees without attendance records are shown with "Belum Absen" status
     * - Directors and owners are excluded from the list
     * - Department filter applies to employees, not attendance records
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $date = $request->date ?? today()->format('Y-m-d');
        $department = $request->department ?? 'all';
        $status = $request->input('status', 'all');
        $outsideRadius = $request->boolean('outside_radius', false);

        // =========================================================
        // LAZY PROCESSING: Run daily processor for yesterday
        // if not yet processed (when viewing past dates)
        // =========================================================
        $this->runLazyProcessing($date);

        // Check permission scope
        $canViewAll = $this->canViewAllAttendance($user);

        // Get current user's employee for filtering
        $currentEmployee = EmployeeProfile::where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->first();

        // =========================================================
        // GET ELIGIBLE EMPLOYEES (excludes Director, Owner, etc.)
        // Filter based on company_role field in users table
        // =========================================================
        $excludedRoles = ['director', 'owner'];

        // Get all eligible employees who should do attendance
        // Exclude employees whose users have company_role = 'director' or 'owner'
        $eligibleEmployeesQuery = EmployeeProfile::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->whereHas('user', function ($q) use ($excludedRoles) {
                $q->where(function ($subQ) use ($excludedRoles) {
                    $subQ->whereNull('company_role')
                         ->orWhereNotIn('company_role', $excludedRoles);
                });
            });

        // Apply scope filter: if not global, only show own data
        if (!$canViewAll && $currentEmployee) {
            $eligibleEmployeesQuery->where('id', $currentEmployee->id);
        }

        // Apply department filter only for global scope
        if ($department !== 'all' && $canViewAll) {
            $eligibleEmployeesQuery->where('department_id', $department);
        } elseif (!$canViewAll && $currentEmployee?->department_id) {
            // For own scope: filter by user's department if specified
            if ($department !== 'all') {
                $eligibleEmployeesQuery->where('department_id', $department);
            }
        }

        $eligibleEmployeeIds = $eligibleEmployeesQuery->pluck('id')->toArray();

        // Get attendance records for the date (only for eligible employees)
        $attendanceMap = Attendance::where('company_id', $companyId)
            ->whereDate('date', $date)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->with(['employee', 'employee.user', 'shift', 'placement'])
            ->get()
            ->keyBy('employee_id');

        // =========================================================
        // BUILD COMBINED LIST: Employees with Attendance Data
        // =========================================================
        $eligibleEmployees = EmployeeProfile::whereIn('id', $eligibleEmployeeIds)
            ->with(['user', 'department', 'position', 'placement'])
            ->orderBy('full_name')
            ->get();

        // Combine employees with their attendance data
        $combinedData = $eligibleEmployees->map(function ($employee) use ($attendanceMap) {
            $attendance = $attendanceMap->get($employee->id);
            $employee->attendance = $attendance;
            $employee->has_attendance = $attendance !== null;
            return $employee;
        });

        // =========================================================
        // APPLY STATUS FILTER
        // =========================================================
        if ($status !== 'all') {
            if ($status === 'present') {
                // Hadir + Terlambat
                $combinedData = $combinedData->filter(function ($emp) {
                    return $emp->has_attendance &&
                           in_array($emp->attendance->status, ['present', 'ontime', 'late']);
                });
            } elseif ($status === 'not_present') {
                // Belum Absen + Alpha
                $combinedData = $combinedData->filter(function ($emp) {
                    if (!$emp->has_attendance) {
                        return true; // Belum Absen
                    }
                    return in_array($emp->attendance->status, ['absent']);
                });
            } else {
                // Specific status
                $combinedData = $combinedData->filter(function ($emp) use ($status) {
                    return $emp->has_attendance && $emp->attendance->status === $status;
                });
            }
        }

        // Paginate the combined results
        $perPage = 30;
        $total = $combinedData->count();
        $offset = ($request->get('page', 1) - 1) * $perPage;
        $paginatedData = $combinedData->slice($offset, $perPage);

        // Create a custom paginator
        $attendances = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedData->values(),
            $total,
            $perPage,
            $request->get('page', 1),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Statistics - based on all eligible employees
        $presentCount = $attendanceMap->filter(function ($att) {
            return in_array($att->status, ['present', 'ontime', 'late']);
        })->count();

        $totalEligible = count($eligibleEmployeeIds);
        $notPresentCount = $totalEligible - $presentCount;

        // Statistics
        if ($canViewAll) {
            $stats = [
                'total' => $totalEligible,
                'present' => $presentCount,
                'late' => $attendanceMap->where('status', 'late')->count(),
                'suspicious' => $attendanceMap->where('is_suspicious', true)->count(),
                'outside_radius' => $attendanceMap->where('is_outside_radius', true)->count(),
            ];
            $attendanceChart = [
                'present' => $attendanceMap->whereIn('status', ['present', 'ontime'])->count(),
                'late' => $attendanceMap->where('status', 'late')->count(),
                'leave' => $attendanceMap->where('status', 'leave')->count(),
                'sick' => $attendanceMap->where('status', 'sick')->count(),
                'absent' => $attendanceMap->where('status', 'absent')->count(),
            ];
        } else {
            // Own scope: only own statistics
            $ownAttendance = $attendanceMap->get($currentEmployee?->id);
            $stats = [
                'total' => 1,
                'present' => $ownAttendance && in_array($ownAttendance->status, ['present', 'ontime', 'late']) ? 1 : 0,
                'late' => $ownAttendance?->status === 'late' ? 1 : 0,
                'suspicious' => $ownAttendance?->is_suspicious ? 1 : 0,
                'outside_radius' => $ownAttendance?->is_outside_radius ? 1 : 0,
            ];
            $attendanceChart = [
                'present' => $ownAttendance && in_array($ownAttendance->status, ['present', 'ontime']) ? 1 : 0,
                'late' => $ownAttendance?->status === 'late' ? 1 : 0,
                'leave' => $ownAttendance?->status === 'leave' ? 1 : 0,
                'sick' => $ownAttendance?->status === 'sick' ? 1 : 0,
                'absent' => $ownAttendance?->status === 'absent' ? 1 : 0,
            ];
        }

        // Departments - filtered by permission scope
        if ($canViewAll) {
            $departments = Department::where('company_id', $companyId)->active()->get();
        } else {
            // Own scope: only user's own department
            $departments = collect();
            if ($currentEmployee?->department_id) {
                $departments = Department::where('id', $currentEmployee->department_id)->active()->get();
            }
        }

        $placements = [];
        if (Schema::hasTable('employee_placements')) {
            $placements = Placement::where('company_id', $companyId)->active()->get();
        }

        // Get employees for calendar dropdown
        // Exclude directors/owners based on company_role field
        // For global scope: show all eligible employees
        // For own scope: show only own employee
        if ($canViewAll) {
            $employees = EmployeeProfile::where('company_id', $companyId)
                ->active()
                ->whereNotNull('user_id')
                ->whereHas('user', function ($q) use ($excludedRoles) {
                    $q->where(function ($subQ) use ($excludedRoles) {
                        $subQ->whereNull('company_role')
                             ->orWhereNotIn('company_role', $excludedRoles);
                    });
                })
                ->with('user')
                ->orderBy('full_name')
                ->get();
        } else {
            // For own scope: only show current user's employee
            $employees = collect();
            if ($currentEmployee) {
                $currentEmployee->load('user');
                $employees = collect([$currentEmployee]);
            }
        }

        return view('crm.hrd.attendances.index', compact(
            'attendances', 'date', 'department', 'status', 'stats', 'attendanceChart',
            'departments', 'placements', 'outsideRadius', 'employees', 'canViewAll', 'currentEmployee'
        ));
    }

    /**
     * Get calendar data for attendance view
     */
    public function calendarData(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        $canViewAll = $this->canViewAllAttendance($user);

        // Force timezone to Asia/Jakarta for all date operations
        $timezone = 'Asia/Jakarta';
        $now = now()->timezone($timezone);

        // Determine period - ensure integer types for parameters
        $period = $request->input('period', 'month'); // day, week, month, year
        $year = (int) $request->input('year', $now->year);
        $month = (int) $request->input('month', $now->month);
        $day = (int) $request->input('day', $now->day);

        // For regular users, only show their own attendance
        $employeeId = $request->input('employee_id');

        // Regular users can only see their own data unless they have view_all permission
        if (!$canViewAll) {
            $currentEmployee = EmployeeProfile::where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->first();

            if (!$currentEmployee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil karyawan tidak ditemukan',
                ], 403);
            }
            $employeeId = $currentEmployee->id;
        } elseif ($employeeId) {
            // Admin/Director: verify employee belongs to same company
            $employee = EmployeeProfile::where('id', $employeeId)
                ->where('company_id', $companyId)
                ->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan tidak ditemukan',
                ], 403);
            }
        }

        // Calculate date range based on period
        // Use Asia/Jakarta timezone explicitly for all date operations
        $startDate = Carbon::create($year, $month, 1, 0, 0, 0, $timezone);
        $endDate = $startDate->copy();

        if ($period === 'day') {
            $startDate = Carbon::create($year, $month, $day, 0, 0, 0, $timezone);
            $endDate = $startDate->copy()->endOfDay();
        } elseif ($period === 'week') {
            // Week starts on Sunday (Indonesian standard - same as monthly calendar)
            // Calendar columns: Min(0) | Sen(1) | Sel(2) | Rab(3) | Kam(4) | Jum(5) | Sab(6)
            // Calculate Sunday of the week containing the selected day
            $currentDate = Carbon::create($year, $month, $day, 0, 0, 0, $timezone);
            $dayOfWeek = $currentDate->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday

            // Sunday is day 0 in both Carbon and Indonesian calendar
            // If today is Sunday, week starts today
            // Otherwise, go back to previous Sunday
            if ($dayOfWeek === 0) {
                $startDate = $currentDate->copy()->startOfDay();
            } else {
                // Go back (dayOfWeek) days to reach Sunday
                // e.g., Tuesday (day=2) - 2 = Sunday
                $startDate = $currentDate->copy()->subDays($dayOfWeek)->startOfDay();
            }
            $endDate = $startDate->copy()->addDays(6)->endOfDay();
        } elseif ($period === 'month') {
            $startDate = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($period === 'year') {
            $startDate = Carbon::create($year, 1, 1, 0, 0, 0, $timezone)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        }

        // Build query
        $query = Attendance::where('company_id', $companyId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with(['employee', 'placement', 'shift']);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $attendances = $query->get();

        // Build calendar data grouped by date
        $days = [];
        $summary = [
            'work_days' => 0,
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'leave' => 0,
            'sick' => 0,
            'not_yet' => 0,
        ];

        // Get employee join date for "not_yet" calculation - ensure same timezone
        $employeeJoinDate = null;
        if ($employeeId) {
            $emp = EmployeeProfile::find($employeeId);
            $employeeJoinDate = $emp?->join_date?->timezone($timezone);
        }

        /**
         * Build calendar grid with proper day alignment
         *
         * Indonesian Calendar Format:
         * Columns: Min(0) | Sen(1) | Sel(2) | Rab(3) | Kam(4) | Jum(5) | Sab(6)
         *
         * The calendar grid must align each date to its correct day column.
         * For monthly view: include padding days from prev/next month to fill complete weeks.
         * For weekly view: show the full week starting from Sunday.
         */
        if ($period === 'month') {
            // Monthly calendar: build full calendar grid with padding
            $firstOfMonth = Carbon::create($year, $month, 1, 0, 0, 0, $timezone);
            $firstDayOfWeek = $firstOfMonth->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday

            // Add padding days from previous month (to align the first day correctly)
            // If first day is Sunday (0), no padding needed
            // If first day is Wednesday (3), we need 3 empty cells before it
            $paddingStart = $firstOfMonth->copy()->subDays($firstDayOfWeek);

            // Calculate total cells needed (always 42 cells for 6 weeks)
            $totalCells = 42;

            // Generate calendar grid
            for ($i = 0; $i < $totalCells; $i++) {
                $current = $paddingStart->copy()->addDays($i);
                $dateStr = $current->format('Y-m-d');
                $dayOfWeek = $current->dayOfWeek; // 0=Sunday (Indonesian format)

                // Determine if this is a padding day (from prev/next month) or actual month day
                // Use == instead of === to handle string/integer comparison safely
                $isCurrentMonth = ($current->month == $month && $current->year == $year);
                $isToday = $current->isToday();
                $isPast = $current->isPast();
                $isFuture = $current->isFuture();

                $attendance = $isCurrentMonth ? $attendances->first(fn($a) => $a->date->format('Y-m-d') === $dateStr) : null;

                $dayStatus = 'future';
                $dayData = null;

                if (!$isCurrentMonth) {
                    // Padding day - mark as padding
                    $dayStatus = 'padding';
                } elseif ($employeeJoinDate && $current < $employeeJoinDate->copy()->startOfDay()) {
                    $dayStatus = 'future';
                } elseif ($isFuture) {
                    $dayStatus = 'future';
                } elseif ($attendance) {
                    // Ada attendance - TAMPILKAN DATA ABSENSI TERLEBIH DAHULU
                    $dayStatus = $this->getAttendanceStatus($attendance);
                    $dayData = $this->buildAttendanceData($attendance);
                    $summary['work_days']++;
                    if (in_array($attendance->status, ['present', 'ontime'])) {
                        $summary['present']++;
                    } elseif ($attendance->status === 'late') {
                        $summary['late']++;
                    } elseif ($attendance->status === 'leave') {
                        $summary['leave']++;
                    } elseif ($attendance->status === 'sick') {
                        $summary['sick']++;
                    } elseif ($attendance->status === 'absent') {
                        $summary['absent']++;
                    }
                } elseif ($dayOfWeek === 0) {
                    // Hari Libur = Minggu saja
                    $dayStatus = 'weekend';
                } else {
                    $dayStatus = 'not_yet';
                    $summary['work_days']++;
                    $summary['not_yet']++;
                }

                $days[] = [
                    'date' => $dateStr,
                    'day' => $current->day,
                    'day_name' => $current->isoFormat('ddd'),
                    'day_of_week' => $dayOfWeek,
                    'is_today' => $isToday,
                    'is_weekend' => $dayOfWeek === 0,
                    'is_past' => $isPast,
                    'is_future' => $isFuture,
                    'is_current_month' => $isCurrentMonth,
                    'is_padding' => !$isCurrentMonth,
                    'status' => $dayStatus,
                    'attendance' => $dayData,
                ];
            }
        } else {
            // Weekly or other periods: simple sequential dates
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $dateStr = $current->format('Y-m-d');
                $dayOfWeek = $current->dayOfWeek; // 0 = Sunday

                $attendance = $attendances->first(fn($a) => $a->date->format('Y-m-d') === $dateStr);

                $dayStatus = 'future';
                $dayData = null;

                if ($employeeJoinDate && $current < $employeeJoinDate->copy()->startOfDay()) {
                    $dayStatus = 'future';
                } elseif ($current->isFuture()) {
                    $dayStatus = 'future';
                } elseif ($attendance) {
                    // Ada attendance - TAMPILKAN DATA ABSENSI TERLEBIH DAHULU
                    $dayStatus = $this->getAttendanceStatus($attendance);
                    $dayData = $this->buildAttendanceData($attendance);
                    $summary['work_days']++;
                    if (in_array($attendance->status, ['present', 'ontime'])) {
                        $summary['present']++;
                    } elseif ($attendance->status === 'late') {
                        $summary['late']++;
                    } elseif ($attendance->status === 'leave') {
                        $summary['leave']++;
                    } elseif ($attendance->status === 'sick') {
                        $summary['sick']++;
                    } elseif ($attendance->status === 'absent') {
                        $summary['absent']++;
                    }
                } elseif ($dayOfWeek === 0) {
                    // Hari Libur = Minggu saja
                    $dayStatus = 'weekend';
                } else {
                    $dayStatus = 'not_yet';
                    $summary['work_days']++;
                    $summary['not_yet']++;
                }

                $days[] = [
                    'date' => $dateStr,
                    'day' => $current->day,
                    'day_name' => $current->isoFormat('ddd'),
                    'day_of_week' => $dayOfWeek,
                    'is_today' => $current->isToday(),
                    'is_weekend' => $dayOfWeek === 0,
                    'is_past' => $current->isPast(),
                    'is_future' => $current->isFuture(),
                    'is_current_month' => true,
                    'is_padding' => false,
                    'status' => $dayStatus,
                    'attendance' => $dayData,
                ];

                $current->addDay();
            }
        }

        // Get employee info for display
        $employeeInfo = null;
        if ($employeeId) {
            $emp = EmployeeProfile::where('id', $employeeId)
                ->with('user')
                ->first();
            if ($emp) {
                $employeeInfo = [
                    'id' => $emp->id,
                    'name' => $emp->full_name,
                    'employee_number' => $emp->employee_number,
                    'nik' => $emp->nik,
                    'department' => $emp->department?->name,
                    'position' => $emp->position?->name,
                    'user_name' => $emp->user?->name,
                    'user_email' => $emp->user?->email,
                    'photo' => $emp->photo,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'period' => [
                'type' => $period,
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'year' => (int) $year,
                'month' => (int) $month,
                'day' => (int) $day,
            ],
            'employee' => $employeeInfo,
            'summary' => $summary,
            'days' => $days,
            'can_view_all' => $canViewAll,
        ]);
    }

    /**
     * Determine attendance status for calendar display
     */
    protected function getAttendanceStatus(Attendance $attendance): string
    {
        if ($attendance->status === 'leave') return 'leave';
        if ($attendance->status === 'sick') return 'sick';
        if ($attendance->status === 'present' || $attendance->status === 'ontime') return 'present';
        if ($attendance->status === 'late') return 'late';
        if ($attendance->status === 'absent') return 'absent';
        return 'present';
    }

    /**
     * Build attendance data array for calendar display
     */
    protected function buildAttendanceData(Attendance $attendance): array
    {
        return [
            // Basic Info
            'id' => $attendance->id,
            'status' => $attendance->status,
            'status_label' => $attendance->status_label,
            'status_class' => $attendance->status_badge_class,
            'date' => $attendance->date->format('Y-m-d'),
            'notes' => $attendance->notes,
            'approval_notes' => $attendance->approval_notes,

            // Time Info
            'check_in' => $attendance->check_in_formatted,
            'check_out' => $attendance->check_out_formatted,
            'check_in_time' => $attendance->check_in_time?->format('H:i:s'),
            'check_out_time' => $attendance->check_out_time?->format('H:i:s'),
            'working_hours' => $attendance->working_hours,
            'late_minutes' => $attendance->late_minutes,
            'overtime_minutes' => $attendance->overtime_minutes,
            'early_leave_minutes' => $attendance->early_leave_minutes,

            // Shift Info
            'shift' => $attendance->shift?->name,
            'shift_start' => $attendance->shift_start?->format('H:i'),
            'shift_end' => $attendance->shift_end?->format('H:i'),

            // Location Info
            'location' => $attendance->attendance_location_name,
            'distance' => $attendance->distance_formatted,
            'distance_meters' => $attendance->distance_meters,

            // Check In Photo & Location
            'check_in_photo' => $attendance->check_in_photo,
            'check_in_address' => $attendance->check_in_address,
            'check_in_latitude' => $attendance->check_in_latitude,
            'check_in_longitude' => $attendance->check_in_longitude,
            'check_in_gps_accuracy' => $attendance->check_in_gps_accuracy,
            'check_in_timezone' => $attendance->check_in_timezone,
            'check_in_timezone_name' => $attendance->check_in_timezone_name,
            'check_in_province' => $attendance->check_in_province,
            'check_in_city' => $attendance->check_in_city,

            // Check Out Photo & Location
            'check_out_photo' => $attendance->check_out_photo,
            'check_out_address' => $attendance->check_out_address,
            'check_out_latitude' => $attendance->check_out_latitude,
            'check_out_longitude' => $attendance->check_out_longitude,
            'check_out_gps_accuracy' => $attendance->check_out_gps_accuracy,
            'check_out_timezone' => $attendance->check_out_timezone,
            'check_out_timezone_name' => $attendance->check_out_timezone_name,
            'check_out_province' => $attendance->check_out_province,
            'check_out_city' => $attendance->check_out_city,

            // Verification Flags
            'is_face_verified' => $attendance->is_face_verified,
            'is_location_verified' => $attendance->is_location_verified,
            'is_outside_radius' => $attendance->is_outside_radius,
            'is_suspicious' => $attendance->is_suspicious,
            'suspicious_reasons' => $attendance->suspicious_reasons,

            // Face Verification Details
            'face_verification_score' => $attendance->face_verification_score,

            // Device Info - Check In
            'check_in_ip' => $attendance->check_in_ip,
            'check_in_device' => $attendance->check_in_device,
            'check_in_browser' => $attendance->check_in_browser,
            'check_in_os' => $attendance->check_in_os,

            // Device Info - Check Out
            'check_out_ip' => $attendance->check_out_ip,
            'check_out_device' => $attendance->check_out_device,
            'check_out_browser' => $attendance->check_out_browser,
            'check_out_os' => $attendance->check_out_os,

            // Approval Info
            'approved_by' => $attendance->approved_by,
            'approved_at' => $attendance->approved_at?->format('d M Y H:i'),
        ];
    }

    /**
     * Face Attendance Page
     */
    public function faceAttendance(): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        // Get employee's assigned shift and placement
        $employee = EmployeeProfile::where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->with(['placement', 'department', 'position'])
            ->first();

        // Check for pending link request
        $pendingRequest = $this->linkService->getPendingRequest($user->id, $companyId);

        $todayAttendance = null;
        if ($employee) {
            $todayAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', today())
                ->first();
        }

        // Get available placements for GPS validation
        $placements = Placement::where('company_id', $companyId)->active()->get();

        return view('crm.hrd.attendances.face', compact(
            'employee', 'todayAttendance', 'placements', 'pendingRequest'
        ));
    }

    /**
     * Submit Face Attendance with race condition protection
     */
    public function submitFaceAttendance(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'type' => 'required|in:check_in,check_out',
            'photo' => 'required|string', // Base64 encoded
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'gps_accuracy' => 'nullable|numeric|min:0',
            'placement_id' => 'nullable|exists:employee_placements,id',
            // Timezone info from browser
            'timezone' => 'nullable|string|max:50',
            'timezone_offset' => 'nullable|string|max:10',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
        ]);

        $employee = EmployeeProfile::where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->with('placement')
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'code' => 'EMPLOYEE_ACCOUNT_NOT_LINKED',
                'message' => 'Akun Anda belum terhubung dengan Data Karyawan. Hubungi Admin atau HRD untuk mengaitkan akun login dengan profil karyawan.',
            ], 403);
        }

        if (!$employee->is_active) {
            return response()->json([
                'success' => false,
                'code' => 'EMPLOYEE_INACTIVE',
                'message' => 'Profil karyawan Anda sedang tidak aktif.',
            ], 403);
        }

        // Use DB transaction to prevent race conditions
        return \DB::transaction(function () use ($validated, $employee, $companyId, $user, $request) {
            // Lock the attendance record for today if exists to prevent race condition
            $today = now()->toDateString();

            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->first();

            // =====================================================
            // BUSINESS RULE ENFORCEMENT
            // =====================================================

            // RULE A: Check-in only allowed when no attendance record exists
            if ($validated['type'] === 'check_in') {
                if ($existingAttendance) {
                    return response()->json([
                        'success' => false,
                        'code' => 'ALREADY_CHECKED_IN',
                        'message' => 'Anda sudah melakukan check-in hari ini. Tidak dapat check-in dua kali.',
                    ], 400);
                }
            }

            // RULE B: Check-out only allowed when attendance exists and has check-in but no check-out
            if ($validated['type'] === 'check_out') {
                if (!$existingAttendance) {
                    return response()->json([
                        'success' => false,
                        'code' => 'NOT_CHECKED_IN',
                        'message' => 'Anda belum melakukan check-in hari ini. Harap check-in terlebih dahulu.',
                    ], 400);
                }

                if ($existingAttendance->check_out_time) {
                    return response()->json([
                        'success' => false,
                        'code' => 'ALREADY_CHECKED_OUT',
                        'message' => 'Anda sudah melakukan check-out hari ini. Tidak dapat check-out dua kali.',
                    ], 400);
                }
            }

            // RULE C: If both check-in and check-out exist, reject all additional attempts
            if ($existingAttendance && $existingAttendance->check_in_time && $existingAttendance->check_out_time) {
                return response()->json([
                    'success' => false,
                    'code' => 'ATTENDANCE_COMPLETED',
                    'message' => 'Absensi hari ini sudah lengkap. Anda dapat melakukan absensi kembali besok.',
                ], 400);
            }

            // =====================================================
            // PHOTO PROCESSING
            // =====================================================

            // Save photo - use fresh photo for each action
            $photoData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $validated['photo']));
            $photoType = $validated['type'] === 'check_in' ? 'in' : 'out';
            $photoName = 'attendance_' . $employee->id . '_' . now()->format('Ymd') . '_' . $photoType . '_' . time() . '.jpg';
            $photoPath = 'hrd/attendance/' . $photoName;
            Storage::disk('public')->put($photoPath, $photoData);

            // Determine placement
            $placementId = $validated['placement_id'] ?? $employee->placement_id;
            $placement = $placementId ? Placement::find($placementId) : $employee->placement;

            // Extract device info safely
            $userAgent = $request->userAgent() ?? 'Unknown';
            $osInfo = $this->extractOS($userAgent);

            // Prepare base data
            $data = [
                'employee_id' => $employee->id,
                'company_id' => $companyId,
                'placement_id' => $placementId,
                'date' => $today,
                'is_face_verified' => true, // Simulated
                'face_verification_score' => 0.95,
            ];

            if ($validated['type'] === 'check_in') {
                // CHECK-IN DATA
                $data['check_in_time'] = now();
                $data['check_in_photo'] = $photoPath;
                $data['check_in_latitude'] = $validated['latitude'] ?? null;
                $data['check_in_longitude'] = $validated['longitude'] ?? null;
                $data['check_in_address'] = $validated['address'] ?? null;
                $data['check_in_gps_accuracy'] = $validated['gps_accuracy'] ?? null;
                $data['check_in_ip'] = $request->ip();
                $data['check_in_device'] = substr($userAgent, 0, 255);
                $data['check_in_browser'] = substr($this->extractBrowser($userAgent), 0, 100);
                $data['check_in_os'] = substr($osInfo, 0, 50);
                $data['attendance_location_name'] = $placement?->name;
                $data['status'] = 'present';

                // Set timezone info from browser or determine from address
                $this->setTimezoneData($data, 'check_in', $validated);

                // GPS radius check for check-in
                if ($placement && $validated['latitude'] && $validated['longitude']) {
                    $distance = $this->calculateDistance(
                        $placement->latitude,
                        $placement->longitude,
                        $validated['latitude'],
                        $validated['longitude']
                    );
                    $data['distance_meters'] = $distance;
                    $data['is_outside_radius'] = $distance > ($placement->radius_meters ?? 100);
                    $data['is_location_verified'] = !$data['is_outside_radius'];

                    if ($data['is_outside_radius']) {
                        $data['is_suspicious'] = true;
                        $data['suspicious_reasons'] = ['outside_radius' => "Berada {$distance}m dari lokasi yang ditentukan"];
                    }
                }
            } else {
                // CHECK-OUT DATA (update existing record)
                $data['check_out_time'] = now();
                $data['check_out_photo'] = $photoPath;
                $data['check_out_latitude'] = $validated['latitude'] ?? null;
                $data['check_out_longitude'] = $validated['longitude'] ?? null;
                $data['check_out_address'] = $validated['address'] ?? null;
                $data['check_out_gps_accuracy'] = $validated['gps_accuracy'] ?? null;
                $data['check_out_ip'] = $request->ip();
                $data['check_out_device'] = substr($userAgent, 0, 255);
                $data['check_out_browser'] = substr($this->extractBrowser($userAgent), 0, 100);
                $data['check_out_os'] = substr($osInfo, 0, 50);

                // Set timezone info for check-out
                $this->setTimezoneData($data, 'check_out', $validated);
            }

            // Create or update attendance
            if ($existingAttendance) {
                $existingAttendance->update($data);
                $attendance = $existingAttendance->fresh();
            } else {
                $attendance = Attendance::create($data);
            }

            // Calculate late minutes if check in
            if ($validated['type'] === 'check_in' && $attendance->shift) {
                $lateMinutes = $attendance->calculateLateMinutes();
                if ($lateMinutes > 0) {
                    $attendance->update([
                        'late_minutes' => $lateMinutes,
                        'status' => 'late',
                    ]);
                }
            }

            // Calculate working hours if check out
            if ($validated['type'] === 'check_out') {
                $attendance->update([
                    'working_hours' => $attendance->calculateWorkingHours(),
                ]);
            }

            // Prepare response warnings
            $warnings = [];
            if ($attendance->is_outside_radius) {
                $warnings[] = "Anda berada di luar area yang diizinkan ({$attendance->distance_formatted} dari titik absensi)";
            }
            if ($attendance->is_suspicious && $attendance->suspicious_reasons) {
                foreach ($attendance->suspicious_reasons as $reason) {
                    if (is_string($reason)) {
                        $warnings[] = $reason;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => $validated['type'] === 'check_in' ? 'Check in berhasil' : 'Check out berhasil',
                'attendance' => [
                    'id' => $attendance->id,
                    'check_in_time' => $attendance->check_in_formatted,
                    'check_out_time' => $attendance->check_out_formatted,
                    'status' => $attendance->status_label,
                    'is_outside_radius' => $attendance->is_outside_radius,
                    'distance' => $attendance->distance_formatted,
                ],
                'warnings' => $warnings,
            ]);
        });
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    protected function calculateDistance(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): float
    {
        if (!$lat1 || !$lng1 || !$lat2 || !$lng2) {
            return 0;
        }

        $earthRadius = 6371000; // meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get linkable employees for account linking
     */
    public function getLinkableEmployees(): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $employees = $this->linkService->getLinkableEmployees($companyId, $user->id);

        return response()->json([
            'success' => true,
            'employees' => $employees,
        ]);
    }

    /**
     * Get pending link request for current user
     */
    public function getPendingRequest(): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $pendingRequest = $this->linkService->getPendingRequest($user->id, $companyId);

        if (!$pendingRequest) {
            return response()->json([
                'success' => true,
                'has_request' => false,
                'request' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'has_request' => true,
            'request' => [
                'id' => $pendingRequest->id,
                'status' => $pendingRequest->status,
                'status_label' => $pendingRequest->status_label,
                'status_badge_class' => $pendingRequest->status_badge_class,
                'employee' => [
                    'id' => $pendingRequest->employeeProfile->id,
                    'employee_number' => $pendingRequest->employeeProfile->employee_number,
                    'full_name' => $pendingRequest->employeeProfile->full_name,
                    'department' => $pendingRequest->employeeProfile->department?->name,
                    'position' => $pendingRequest->employeeProfile->position?->name,
                ],
                'requested_at' => $pendingRequest->created_at->format('d M Y H:i'),
                'can_cancel' => $pendingRequest->canBeCancelled(),
            ],
        ]);
    }

    /**
     * Direct link employee account (when email matches or admin action)
     */
    public function linkEmployee(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employee_profiles,id',
        ]);

        // Security: Validate employee belongs to same company
        $employee = EmployeeProfile::where('id', $validated['employee_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'code' => 'DIFFERENT_COMPANY',
                'message' => 'Profil karyawan tidak berada di perusahaan yang sama.',
            ], 403);
        }

        // Try direct link (for verified matches)
        $result = $this->linkService->linkAccount($validated['employee_id'], $user->id, $companyId);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'employee' => [
                'id' => $result['employee']->id,
                'employee_number' => $result['employee']->employee_number,
                'full_name' => $result['employee']->full_name,
            ],
        ]);
    }

    /**
     * Create link request (requires approval)
     */
    public function createLinkRequest(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employee_profiles,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Security: Validate employee belongs to same company
        $employee = EmployeeProfile::where('id', $validated['employee_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'code' => 'DIFFERENT_COMPANY',
                'message' => 'Profil karyawan tidak berada di perusahaan yang sama.',
            ], 403);
        }

        $result = $this->linkService->createLinkRequest(
            $user->id,
            $validated['employee_id'],
            $companyId,
            $validated['notes'] ?? null
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'request' => [
                'id' => $result['request']->id,
                'status' => $result['request']->status,
                'status_label' => $result['request']->status_label,
                'employee' => [
                    'id' => $result['request']->employeeProfile->id,
                    'employee_number' => $result['request']->employeeProfile->employee_number,
                    'full_name' => $result['request']->employeeProfile->full_name,
                ],
                'requested_at' => $result['request']->created_at->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * Cancel pending link request
     */
    public function cancelLinkRequest(Request $request): JsonResponse
    {
        $user = auth()->user();

        $requestId = $request->input('id');

        if (!$requestId) {
            return response()->json([
                'success' => false,
                'message' => 'ID request diperlukan',
            ], 400);
        }

        $result = $this->linkService->cancelRequest($requestId, $user->id);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Attendance Report
     */
    public function report(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;
        $employeeId = $request->employee ?? 'all';
        $outsideRadius = $request->boolean('outside_radius', false);

        // Get eligible employee IDs (excluding directors/owners based on company_role)
        $eligibleEmployeeIds = $this->getEligibleEmployeeIds($companyId);

        $query = Attendance::where('company_id', $companyId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->with(['employee', 'placement']);

        if ($employeeId !== 'all') {
            $query->where('employee_id', $employeeId);
        }

        if ($outsideRadius) {
            $query->where('is_outside_radius', true);
        }

        $attendances = $query->orderBy('date', 'asc')->get();

        // Get employees for dropdown (excluding directors/owners)
        $employees = EmployeeProfile::where('company_id', $companyId)
            ->active()
            ->whereIn('id', $eligibleEmployeeIds)
            ->with('user')
            ->get();

        // Summary
        $summary = [
            'total_days' => $attendances->count(),
            'present' => $attendances->whereIn('status', ['present', 'ontime', 'late'])->count(),
            'late' => $attendances->late()->count(),
            'total_late_minutes' => $attendances->sum('late_minutes'),
            'avg_working_hours' => $attendances->whereNotNull('working_hours')->avg('working_hours'),
            'outside_radius' => $attendances->where('is_outside_radius', true)->count(),
        ];

        return view('crm.hrd.attendances.report', compact(
            'attendances', 'employees', 'month', 'year', 'employeeId', 'summary', 'outsideRadius'
        ));
    }

    /**
     * Export Attendance
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        // Get eligible employee IDs (excluding directors/owners based on company_role)
        $eligibleEmployeeIds = $this->getEligibleEmployeeIds($companyId);

        $attendances = Attendance::where('company_id', $companyId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->with(['employee', 'shift', 'placement'])
            ->orderBy('date', 'asc')
            ->get();

        $csvData = "No,Nama,NIK, Departemen,Penempatan,Tanggal,Jam Masuk,Jam Pulang,Shift,Status,Terlambat (menit),Di Luar Area,Jarak GPS,Face Verified,Alamat Masuk,Lokasi Masuk,IP Device,Catatan\n";

        $no = 1;
        foreach ($attendances as $att) {
            $csvData .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,\"%s\"\n",
                $no++,
                $att->employee?->user?->name ?? 'Unknown',
                $att->employee?->nik ?? '-',
                $att->employee?->department?->name ?? '-',
                $att->placement?->name ?? '-',
                $att->date->format('Y-m-d'),
                $att->check_in_formatted,
                $att->check_out_formatted,
                $att->shift?->name ?? '-',
                $att->status_label,
                $att->late_minutes ?? 0,
                $att->is_outside_radius ? 'Ya' : 'Tidak',
                $att->distance_formatted ?? '-',
                $att->is_face_verified ? 'Ya' : 'Tidak',
                str_replace(['"', "\n", "\r"], ['""', ' ', ' '], $att->check_in_address ?? ''),
                $att->attendance_location_name ?? '-',
                $att->check_in_ip ?? '-',
                implode('; ', array_filter($att->suspicious_reasons ?? []))
            );
        }

        return response()->streamDownload(
            fn() => print($csvData),
            'attendance_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    /**
     * Extract OS from User-Agent string
     */
    protected function extractOS(string $userAgent): string
    {
        $osList = [
            'Windows 11' => 'Windows NT 10.0; Win64; x64',
            'Windows 10' => 'Windows NT 10.0',
            'Windows 8.1' => 'Windows NT 6.3',
            'Windows 8' => 'Windows NT 6.2',
            'Windows 7' => 'Windows NT 6.1',
            'macOS' => 'Macintosh',
            'iOS' => 'iPhone',
            'iPad' => 'iPad',
            'Android' => 'Android',
            'Linux' => 'Linux',
            'Chrome OS' => 'CrOS',
        ];

        foreach ($osList as $os => $signature) {
            if (str_contains($userAgent, $signature)) {
                return $os;
            }
        }

        return 'Unknown';
    }

    /**
     * Extract Browser from User-Agent string
     */
    protected function extractBrowser(string $userAgent): string
    {
        $browserList = [
            'Edge' => 'Edg/',
            'Chrome' => 'Chrome/',
            'Firefox' => 'Firefox/',
            'Safari' => 'Safari/',
            'Opera' => 'OPR/',
            'IE' => 'MSIE',
        ];

        foreach ($browserList as $browser => $signature) {
            if (str_contains($userAgent, $signature)) {
                return $browser;
            }
        }

        return 'Unknown';
    }

    /**
     * Set timezone data for attendance record
     *
     * @param array $data Reference to data array to modify
     * @param string $type 'check_in' or 'check_out'
     * @param array $validated Validated request data
     */
    protected function setTimezoneData(array &$data, string $type, array $validated): void
    {
        // Use timezone from browser if available
        if (!empty($validated['timezone'])) {
            $timezone = $validated['timezone'];
            $offset = $validated['timezone_offset'] ?? null;
        } else {
            // Determine from province/city/address
            $province = $validated['province'] ?? null;
            $city = $validated['city'] ?? null;
            $address = $validated['address'] ?? null;

            $timezone = \App\Helpers\IndonesiaTimezoneHelper::getTimezoneFromProvince($province);

            // Try city if province didn't determine timezone
            if ($timezone === 'Asia/Jakarta' && $city) {
                $timezone = \App\Helpers\IndonesiaTimezoneHelper::getTimezoneFromCity($city);
            }

            // Try address if still default
            if ($timezone === 'Asia/Jakarta' && $address) {
                $timezone = \App\Helpers\IndonesiaTimezoneHelper::getTimezoneFromAddress($address);
            }

            // Get offset from determined timezone
            $offset = \App\Helpers\IndonesiaTimezoneHelper::getTimezoneOffset($timezone);
        }

        // Get timezone info
        $tzName = \App\Helpers\IndonesiaTimezoneHelper::getTimezoneName($timezone);

        // Set fields
        $prefix = $type . '_';

        $data[$prefix . 'timezone'] = $timezone;
        $data[$prefix . 'timezone_name'] = $tzName;
        $data[$prefix . 'timezone_offset'] = $offset;

        // Set city and province if provided
        if (!empty($validated['city'])) {
            $data[$prefix . 'city'] = $validated['city'];
        }

        if (!empty($validated['province'])) {
            $data[$prefix . 'province'] = $validated['province'];
        }
    }

    /**
     * Run lazy processing for attendance.
     *
     * This method checks if yesterday's attendance has been processed.
     * If not, it runs the AttendanceDailyProcessor to create absent records.
     *
     * This ensures that even if the scheduler doesn't run (e.g., server was down),
     * the absent records will be created when users first access the attendance pages.
     */
    protected function runLazyProcessing(string $viewDate): void
    {
        // Only process if viewing a past date or today
        $carbonDate = Carbon::parse($viewDate);

        if ($carbonDate->isFuture()) {
            return;
        }

        // Only run for dates that could have absent records (before today)
        if ($carbonDate->isToday()) {
            return;
        }

        // Only process if yesterday and the date is a working day
        $yesterday = Carbon::yesterday();

        // If viewing yesterday, ensure it's processed
        if ($carbonDate->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            if ($this->dailyProcessor->isWorkingDay($yesterday)) {
                // Check if already processed
                $existingAbsents = Attendance::whereDate('date', $yesterday->format('Y-m-d'))
                    ->where('status', 'absent')
                    ->where('notes', 'like', '%Alpha%')
                    ->exists();

                if (!$existingAbsents) {
                    // Run processing for yesterday only
                    try {
                        $this->dailyProcessor->processDate($yesterday);
                    } catch (\Throwable $e) {
                        // Log error but don't interrupt user experience
                        \Log::error('[AttendanceController] Lazy processing failed: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Get attendance summary with director exclusion for popup display.
     *
     * GET /karyawan/absen/summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSummary(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        $canViewAll = $this->canViewAllAttendance($user);

        $date = $request->input('date', now()->format('Y-m-d'));
        $departmentId = $request->input('department');

        // Parse department if it's not 'all'
        $departmentId = $departmentId && $departmentId !== 'all' ? (int) $departmentId : null;

        // For own scope: get current employee's attendance summary
        if (!$canViewAll) {
            $currentEmployee = EmployeeProfile::where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->first();

            if (!$currentEmployee) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'present' => 0,
                        'not_present' => 1,
                        'total' => 1,
                    ],
                ]);
            }

            // Get own attendance for the date
            $attendance = Attendance::where('employee_id', $currentEmployee->id)
                ->whereDate('date', $date)
                ->first();

            $isPresent = $attendance && in_array($attendance->status, ['present', 'ontime', 'late']);

            return response()->json([
                'success' => true,
                'data' => [
                    'present' => $isPresent ? 1 : 0,
                    'not_present' => $isPresent ? 0 : 1,
                    'total' => 1,
                ],
            ]);
        }

        $summary = $this->summaryService->getSummary($companyId, $date, $departmentId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get list of present employees for popup display.
     *
     * GET /karyawan/absen/present-list
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPresentList(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        $canViewAll = $this->canViewAllAttendance($user);

        $date = $request->input('date', now()->format('Y-m-d'));
        $departmentId = $request->input('department');

        // Parse department if it's not 'all'
        $departmentId = $departmentId && $departmentId !== 'all' ? (int) $departmentId : null;

        // For own scope: return only own present status
        if (!$canViewAll) {
            $currentEmployee = EmployeeProfile::where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->first();

            if (!$currentEmployee) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                ]);
            }

            // Get own attendance for the date
            $attendance = Attendance::where('employee_id', $currentEmployee->id)
                ->whereDate('date', $date)
                ->first();

            $isPresent = $attendance && in_array($attendance->status, ['present', 'ontime', 'late']);

            if (!$isPresent) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [[
                    'id' => $currentEmployee->id,
                    'name' => $currentEmployee->full_name ?? $currentEmployee->user?->name ?? 'N/A',
                    'nik' => $currentEmployee->nik ?? '-',
                    'employee_number' => $currentEmployee->employee_number ?? '-',
                    'department' => $currentEmployee->department?->name ?? '-',
                    'position' => $currentEmployee->position?->name ?? '-',
                    'photo' => $currentEmployee->photo,
                    'check_in' => $attendance?->check_in_formatted ?? '-',
                    'check_out' => $attendance?->check_out_formatted ?? '-',
                    'status' => $attendance?->status ?? 'present',
                    'status_label' => $this->getStatusLabel($attendance?->status),
                    'late_minutes' => $attendance?->late_minutes ?? 0,
                ]],
                'total' => 1,
            ]);
        }

        $employees = $this->summaryService->getPresentEmployees($companyId, $date, $departmentId);

        $data = $employees->map(function ($employee) {
            $attendance = $employee->attendances->first();

            return [
                'id' => $employee->id,
                'name' => $employee->full_name ?? $employee->user?->name ?? 'N/A',
                'nik' => $employee->nik ?? '-',
                'employee_number' => $employee->employee_number ?? '-',
                'department' => $employee->department?->name ?? '-',
                'position' => $employee->position?->name ?? '-',
                'photo' => $employee->photo,
                'check_in' => $attendance?->check_in_formatted ?? '-',
                'check_out' => $attendance?->check_out_formatted ?? '-',
                'status' => $attendance?->status ?? 'present',
                'status_label' => $this->getStatusLabel($attendance?->status),
                'late_minutes' => $attendance?->late_minutes ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->values()->toArray(),
            'total' => $data->count(),
        ]);
    }

    /**
     * Get list of not present employees for popup display.
     *
     * GET /karyawan/absen/not-present-list
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNotPresentList(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        $canViewAll = $this->canViewAllAttendance($user);

        $date = $request->input('date', now()->format('Y-m-d'));
        $departmentId = $request->input('department');

        // Parse department if it's not 'all'
        $departmentId = $departmentId && $departmentId !== 'all' ? (int) $departmentId : null;

        // For own scope: return only own not-present status
        if (!$canViewAll) {
            $currentEmployee = EmployeeProfile::where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->first();

            if (!$currentEmployee) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                ]);
            }

            // Get own attendance for the date
            $attendance = Attendance::where('employee_id', $currentEmployee->id)
                ->whereDate('date', $date)
                ->first();

            $isPresent = $attendance && in_array($attendance->status, ['present', 'ontime', 'late']);

            if ($isPresent) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [[
                    'id' => $currentEmployee->id,
                    'name' => $currentEmployee->full_name ?? $currentEmployee->user?->name ?? 'N/A',
                    'nik' => $currentEmployee->nik ?? '-',
                    'employee_number' => $currentEmployee->employee_number ?? '-',
                    'department' => $currentEmployee->department?->name ?? '-',
                    'position' => $currentEmployee->position?->name ?? '-',
                    'photo' => $currentEmployee->photo,
                ]],
                'total' => 1,
            ]);
        }

        $employees = $this->summaryService->getNotPresentEmployees($companyId, $date, $departmentId);

        $data = $employees->map(function ($employee) {
            return [
                'id' => $employee->id,
                'name' => $employee->full_name ?? $employee->user?->name ?? 'N/A',
                'nik' => $employee->nik ?? '-',
                'employee_number' => $employee->employee_number ?? '-',
                'department' => $employee->department?->name ?? '-',
                'position' => $employee->position?->name ?? '-',
                'photo' => $employee->photo,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->values()->toArray(),
            'total' => $data->count(),
        ]);
    }

    /**
     * Get status label in Indonesian.
     */
    protected function getStatusLabel(?string $status): string
    {
        return match($status) {
            'present', 'ontime' => 'Hadir',
            'late' => 'Terlambat',
            'leave' => 'Izin/Cuti',
            'sick' => 'Sakit',
            'permit' => 'Izin',
            'absent' => 'Alpha',
            default => '-',
        };
    }

    /**
     * Get calendar statistics using the same logic as the List View.
     *
     * GET /karyawan/absen/calendar-stats
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCalendarStats(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        $canViewAll = $this->canViewAllAttendance($user);

        $date = $request->input('date', now()->format('Y-m-d'));
        $departmentId = $request->input('department');

        // Parse department if it's not 'all'
        $departmentId = $departmentId && $departmentId !== 'all' ? (int) $departmentId : null;

        // For own scope: return own attendance stats
        if (!$canViewAll) {
            $currentEmployee = EmployeeProfile::where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->first();

            if (!$currentEmployee) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'present' => 0,
                        'not_present' => 1,
                    ],
                ]);
            }

            // Get own attendance for the date
            $attendance = Attendance::where('employee_id', $currentEmployee->id)
                ->whereDate('date', $date)
                ->first();

            $isPresent = $attendance && in_array($attendance->status, ['present', 'ontime', 'late']);

            return response()->json([
                'success' => true,
                'data' => [
                    'present' => $isPresent ? 1 : 0,
                    'not_present' => $isPresent ? 0 : 1,
                ],
            ]);
        }

        // Use the same service as the List View
        $summary = $this->summaryService->getSummary($companyId, $date, $departmentId);

        return response()->json([
            'success' => true,
            'data' => [
                'present' => $summary['present'],
                'not_present' => $summary['not_present'],
            ],
        ]);
    }

    /**
     * Get list of present employees for a calendar period (week/month/year).
     *
     * GET /karyawan/absen/calendar-present-list
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCalendarPresentList(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        $canViewAll = $this->canViewAllAttendance($user);
        $timezone = 'Asia/Jakarta';
        $now = now()->timezone($timezone);

        // Get period parameters
        $period = $request->input('period', 'month');
        $year = (int) $request->input('year', $now->year);
        $month = (int) $request->input('month', $now->month);
        $day = (int) $request->input('day', $now->day);
        $employeeId = $request->input('employee_id');

        // Calculate date range based on period
        $startDate = Carbon::create($year, $month, 1, 0, 0, 0, $timezone);
        $endDate = $startDate->copy();

        if ($period === 'day') {
            $startDate = Carbon::create($year, $month, $day, 0, 0, 0, $timezone);
            $endDate = $startDate->copy()->endOfDay();
        } elseif ($period === 'week') {
            $currentDate = Carbon::create($year, $month, $day, 0, 0, 0, $timezone);
            $dayOfWeek = $currentDate->dayOfWeek;
            if ($dayOfWeek === 0) {
                $startDate = $currentDate->copy()->startOfDay();
            } else {
                $startDate = $currentDate->copy()->subDays($dayOfWeek)->startOfDay();
            }
            $endDate = $startDate->copy()->addDays(6)->endOfDay();
        } elseif ($period === 'month') {
            $startDate = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($period === 'year') {
            $startDate = Carbon::create($year, 1, 1, 0, 0, 0, $timezone)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        }

        // For own scope: return only own present records
        if (!$canViewAll) {
            $currentEmployee = EmployeeProfile::where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->first();

            if (!$currentEmployee) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'period' => [
                        'type' => $period,
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d'),
                    ],
                ]);
            }

            // Get own present attendance in the period
            $attendances = Attendance::where('company_id', $companyId)
                ->where('employee_id', $currentEmployee->id)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->whereIn('status', ['present', 'ontime', 'late'])
                ->with(['employee', 'placement', 'shift'])
                ->orderBy('date', 'asc')
                ->get();

            if ($attendances->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'period' => [
                        'type' => $period,
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d'),
                    ],
                ]);
            }

            // Build single employee data
            $emp = $attendances->first()->employee;
            return response()->json([
                'success' => true,
                'data' => [[
                    'id' => $emp->id,
                    'name' => $emp->full_name ?? $emp->user?->name ?? 'N/A',
                    'nik' => $emp->nik ?? '-',
                    'employee_number' => $emp->employee_number ?? '-',
                    'department' => $emp->department?->name ?? '-',
                    'position' => $emp->position?->name ?? '-',
                    'photo' => $emp->photo,
                    'total_days' => $attendances->count(),
                    'dates' => $attendances->pluck('date')->map(fn($d) => $d->format('d/m'))->toArray(),
                ]],
                'total' => 1,
                'period' => [
                    'type' => $period,
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ]);
        }

        // Build query for attendance in the period (global scope)
        $query = Attendance::where('company_id', $companyId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereIn('status', ['present', 'ontime', 'late'])
            ->with(['employee', 'placement', 'shift']);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $attendances = $query->orderBy('date', 'asc')->get();

        // Group by employee and prepare response
        $employeesData = [];
        $processedEmployees = [];

        foreach ($attendances as $attendance) {
            $empId = $attendance->employee_id;
            if (!isset($processedEmployees[$empId])) {
                $processedEmployees[$empId] = true;
                $emp = $attendance->employee;
                $employeesData[] = [
                    'id' => $emp->id,
                    'name' => $emp->full_name ?? $emp->user?->name ?? 'N/A',
                    'nik' => $emp->nik ?? '-',
                    'employee_number' => $emp->employee_number ?? '-',
                    'department' => $emp->department?->name ?? '-',
                    'position' => $emp->position?->name ?? '-',
                    'photo' => $emp->photo,
                    // Aggregate attendance details for the period
                    'total_days' => $attendances->where('employee_id', $empId)->count(),
                    'dates' => $attendances->where('employee_id', $empId)->pluck('date')->map(fn($d) => $d->format('d/m'))->toArray(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $employeesData,
            'total' => count($employeesData),
            'period' => [
                'type' => $period,
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Get list of not present employees for a calendar period (week/month/year).
     * "Not present" = eligible employees who have no attendance record in the period.
     *
     * GET /karyawan/absen/calendar-not-present-list
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCalendarNotPresentList(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        $canViewAll = $this->canViewAllAttendance($user);
        $timezone = 'Asia/Jakarta';
        $now = now()->timezone($timezone);

        // Get period parameters
        $period = $request->input('period', 'month');
        $year = (int) $request->input('year', $now->year);
        $month = (int) $request->input('month', $now->month);
        $day = (int) $request->input('day', $now->day);
        $employeeId = $request->input('employee_id');

        // Calculate date range based on period
        $startDate = Carbon::create($year, $month, 1, 0, 0, 0, $timezone);
        $endDate = $startDate->copy();

        if ($period === 'day') {
            $startDate = Carbon::create($year, $month, $day, 0, 0, 0, $timezone);
            $endDate = $startDate->copy()->endOfDay();
        } elseif ($period === 'week') {
            $currentDate = Carbon::create($year, $month, $day, 0, 0, 0, $timezone);
            $dayOfWeek = $currentDate->dayOfWeek;
            if ($dayOfWeek === 0) {
                $startDate = $currentDate->copy()->startOfDay();
            } else {
                $startDate = $currentDate->copy()->subDays($dayOfWeek)->startOfDay();
            }
            $endDate = $startDate->copy()->addDays(6)->endOfDay();
        } elseif ($period === 'month') {
            $startDate = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($period === 'year') {
            $startDate = Carbon::create($year, 1, 1, 0, 0, 0, $timezone)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        }

        // For own scope: return only own not-present status
        if (!$canViewAll) {
            $currentEmployee = EmployeeProfile::where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->first();

            if (!$currentEmployee) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'period' => [
                        'type' => $period,
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d'),
                    ],
                ]);
            }

            // Get own attendance in the period
            $attendance = Attendance::where('company_id', $companyId)
                ->where('employee_id', $currentEmployee->id)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->whereIn('status', ['present', 'ontime', 'late'])
                ->first();

            // If user has any present attendance in the period, they are NOT in "not present" list
            if ($attendance) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'period' => [
                        'type' => $period,
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d'),
                    ],
                ]);
            }

            // User has no present attendance in the period
            return response()->json([
                'success' => true,
                'data' => [[
                    'id' => $currentEmployee->id,
                    'name' => $currentEmployee->full_name ?? $currentEmployee->user?->name ?? 'N/A',
                    'nik' => $currentEmployee->nik ?? '-',
                    'employee_number' => $currentEmployee->employee_number ?? '-',
                    'department' => $currentEmployee->department?->name ?? '-',
                    'position' => $currentEmployee->position?->name ?? '-',
                    'photo' => $currentEmployee->photo,
                ]],
                'total' => 1,
                'period' => [
                    'type' => $period,
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ]);
        }

        // Get all eligible employees (excluding directors/owners)
        $eligibleEmployeeIds = $this->getEligibleEmployeeIds($companyId, null);

        // Filter by specific employee if provided
        if ($employeeId) {
            $eligibleEmployeeIds = in_array($employeeId, $eligibleEmployeeIds) ? [$employeeId] : [];
        }

        // Get employees who have ANY attendance record in the period
        $hasAttendanceIds = Attendance::where('company_id', $companyId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->distinct()
            ->pluck('employee_id')
            ->toArray();

        // Employees who should be present but don't have any attendance records in the period
        $notPresentIds = array_diff($eligibleEmployeeIds, $hasAttendanceIds);

        // Get employee profiles
        $employees = EmployeeProfile::whereIn('id', $notPresentIds)
            ->with(['user', 'department', 'position'])
            ->orderBy('full_name')
            ->get();

        $data = $employees->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->full_name ?? $emp->user?->name ?? 'N/A',
                'nik' => $emp->nik ?? '-',
                'employee_number' => $emp->employee_number ?? '-',
                'department' => $emp->department?->name ?? '-',
                'position' => $emp->position?->name ?? '-',
                'photo' => $emp->photo,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->values()->toArray(),
            'total' => $data->count(),
            'period' => [
                'type' => $period,
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Get eligible employee IDs (excluding directors/owners).
     * Uses company_role field from users table.
     *
     * @param int $companyId
     * @param int|null $departmentId
     * @return array
     */
    protected function getEligibleEmployeeIds(int $companyId, ?int $departmentId = null): array
    {
        $excludedRoles = ['director', 'owner'];

        $query = EmployeeProfile::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->whereHas('user', function ($q) use ($excludedRoles) {
                $q->where(function ($subQ) use ($excludedRoles) {
                    $subQ->whereNull('company_role')
                         ->orWhereNotIn('company_role', $excludedRoles);
                });
            });

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return $query->pluck('id')->toArray();
    }
}
