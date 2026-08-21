<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\HRD\Attendance;
use App\Models\HRD\Department;
use App\Models\HRD\EmployeeProfile;
use App\Models\HRD\Leave;
use App\Models\HRD\LeaveType;
use App\Models\HRD\Overtime;
use App\Models\HRD\Position;
use App\Models\HRD\Recruitment;
use App\Models\HRD\Salary;
use App\Models\HRD\Shift;
use App\Models\HRD\Training;
use App\Modules\System\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HRDController extends Controller
{
    /**
     * Roles that are excluded from attendance calculations.
     */
    protected array $excludedRoles = ['director', 'owner'];

    /**
     * HRD Dashboard (alias for index)
     */
    public function index(): View
    {
        return $this->dashboard();
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
     * HRD Dashboard
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        // Get eligible employee IDs (excluding directors/owners based on company_role)
        $eligibleEmployeeIds = $this->getEligibleEmployeeIds($companyId);

        // Employee Statistics - Total only counts active employees (exclude resign)
        $totalEmployees = EmployeeProfile::where('company_id', $companyId)->where('is_active', true)->count();
        $activeEmployees = EmployeeProfile::where('company_id', $companyId)->active()->count();
        $contractEmployees = EmployeeProfile::where('company_id', $companyId)->active()->contract()->count();
        $newEmployeesThisMonth = EmployeeProfile::where('company_id', $companyId)
            ->active()
            ->whereMonth('join_date', now()->month)
            ->whereYear('join_date', now()->year)
            ->count();
        $resignedEmployees = EmployeeProfile::where('company_id', $companyId)
            ->whereMonth('resign_date', now()->month)
            ->whereYear('resign_date', now()->year)
            ->count();
        $expiringContracts = EmployeeProfile::where('company_id', $companyId)
            ->active()
            ->expiringContract(30)
            ->count();

        // Attendance Statistics (excluding directors/owners)
        $todayAttendance = Attendance::where('company_id', $companyId)
            ->forDate(today())
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->count();
        $presentToday = Attendance::where('company_id', $companyId)
            ->forDate(today())
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->whereIn('status', ['present', 'ontime', 'late'])
            ->count();
        $lateToday = Attendance::where('company_id', $companyId)
            ->forDate(today())
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->late()
            ->count();

        // Leave Statistics
        $pendingLeaves = Leave::where('company_id', $companyId)
            ->where('status', 'pending')
            ->count();
        $onLeaveToday = Leave::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'approved_supervisor'])
            ->where('start_date', '<=', today())
            ->where('end_date', '>=', today())
            ->count();
        $sickLeaves = Leave::where('company_id', $companyId)
            ->where('leave_type', 'sick')
            ->whereMonth('start_date', now()->month)
            ->count();

        // Overtime Statistics
        $todayOvertime = Overtime::where('company_id', $companyId)
            ->where('date', today())
            ->count();
        $pendingOvertime = Overtime::where('company_id', $companyId)
            ->pending()
            ->count();

        // Payroll Statistics
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $payrollThisMonth = Salary::where('company_id', $companyId)
            ->forPeriod($currentYear, $currentMonth)
            ->sum('total_salary');
        $pendingPayroll = Salary::where('company_id', $companyId)
            ->pending()
            ->count();

        // Recruitment Statistics
        $activeRecruitment = Recruitment::where('company_id', $companyId)
            ->whereNotIn('stage', ['hiring', 'rejected'])
            ->count();
        // Week starts on Sunday (day 0) for Indonesian calendar
        $interviewsThisWeek = Recruitment::where('company_id', $companyId)
            ->whereNotNull('interview_date')
            ->whereBetween('interview_date', [now()->startOfWeek(Carbon::SUNDAY), now()->endOfWeek(Carbon::SUNDAY)])
            ->count();

        // Recent Activity (excluding directors/owners)
        $recentAttendances = Attendance::where('company_id', $companyId)
            ->forDate(today())
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->with('employee')
            ->orderBy('check_in_time', 'desc')
            ->limit(10)
            ->get();

        $recentLeaves = Leave::where('company_id', $companyId)
            ->with(['employee', 'leaveType'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Chart Data - Attendance Last 7 Days (excluding directors/owners)
        $attendanceChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $presentCount = Attendance::where('company_id', $companyId)
                ->forDate($date)
                ->whereIn('employee_id', $eligibleEmployeeIds)
                ->present()
                ->count();
            $attendanceChart[] = [
                'date' => $date->format('d M'),
                'present' => $presentCount,
                'late' => Attendance::where('company_id', $companyId)
                    ->forDate($date)
                    ->whereIn('employee_id', $eligibleEmployeeIds)
                    ->late()
                    ->count(),
                'absent' => count($eligibleEmployeeIds) - $presentCount,
            ];
        }

        // Chart Data - Leave by Type
        $leaveByType = LeaveType::where('company_id', $companyId)
            ->active()
            ->withCount(['leaves' => function ($q) {
                $q->whereMonth('start_date', now()->month);
            }])
            ->get()
            ->pluck('leaves_count', 'name');

        // Chart Data - Monthly Payroll
        $payrollChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $payrollChart[] = [
                'month' => $month->format('M Y'),
                'total' => Salary::where('company_id', $companyId)
                    ->forPeriod($month->year, $month->month)
                    ->sum('total_salary'),
            ];
        }

        // Employee Growth Chart
        $employeeGrowth = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $employeeGrowth[] = [
                'month' => $month->format('M Y'),
                'total' => EmployeeProfile::where('company_id', $companyId)
                    ->where('join_date', '<=', $month->endOfMonth())
                    ->where(function ($q) use ($month) {
                        $q->whereNull('resign_date')
                            ->orWhere('resign_date', '>', $month->startOfMonth());
                    })
                    ->count(),
            ];
        }

        // Department Distribution
        $departmentDistribution = Department::where('company_id', $companyId)
            ->active()
            ->withCount('employees')
            ->get()
            ->map(fn($d) => [
                'name' => $d->name,
                'count' => $d->employees_count,
            ]);

        // Smart Notifications
        $notifications = $this->getSmartNotifications($companyId);

        return view('crm.hrd.dashboard.index', compact(
            'totalEmployees', 'activeEmployees', 'contractEmployees',
            'newEmployeesThisMonth', 'resignedEmployees', 'expiringContracts',
            'todayAttendance', 'presentToday', 'lateToday',
            'pendingLeaves', 'onLeaveToday', 'sickLeaves',
            'todayOvertime', 'pendingOvertime',
            'payrollThisMonth', 'pendingPayroll',
            'activeRecruitment', 'interviewsThisWeek',
            'recentAttendances', 'recentLeaves',
            'attendanceChart', 'leaveByType', 'payrollChart', 'employeeGrowth',
            'departmentDistribution', 'notifications'
        ));
    }

    /**
     * Get smart notifications for HR Dashboard
     */
    protected function getSmartNotifications(int $companyId): array
    {
        $notifications = [];

        // Expiring contracts
        $expiringContracts = EmployeeProfile::where('company_id', $companyId)
            ->expiringContract(30)
            ->count();
        if ($expiringContracts > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'fa-file-contract',
                'title' => 'Kontrak Akan Berakhir',
                'message' => "{$expiringContracts} kontrak kerja akan habis dalam 30 hari",
                'action' => route('administrasi.data_karyawan.index', ['filter' => 'expiring']),
            ];
        }

        // Pending leaves
        $pendingLeaves = Leave::where('company_id', $companyId)->pending()->count();
        if ($pendingLeaves > 0) {
            $notifications[] = [
                'type' => 'info',
                'icon' => 'fa-calendar-minus',
                'title' => 'Cuti Menunggu Approval',
                'message' => "{$pendingLeaves} pengajuan cuti menunggu persetujuan",
                'action' => route('administrasi.leaves.index', ['status' => 'pending']),
            ];
        }

        // Pending overtime
        $pendingOvertime = Overtime::where('company_id', $companyId)->pending()->count();
        if ($pendingOvertime > 0) {
            $notifications[] = [
                'type' => 'info',
                'icon' => 'fa-clock',
                'title' => 'Lembur Menunggu Approval',
                'message' => "{$pendingOvertime} pengajuan lembur menunggu persetujuan",
                'action' => route('administrasi.overtimes.index', ['status' => 'pending']),
            ];
        }

        // Pending payroll
        $pendingPayroll = Salary::where('company_id', $companyId)->pending()->count();
        if ($pendingPayroll > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'fa-money-bills',
                'title' => 'Payroll Belum Diproses',
                'message' => "{$pendingPayroll} slip gaji belum diproses",
                'action' => route('administrasi.payroll.index', ['status' => 'pending']),
            ];
        }

        // Suspicious attendance
        $suspiciousAttendance = Attendance::where('company_id', $companyId)
            ->forDate(today()->subDay())
            ->suspicious()
            ->count();
        if ($suspiciousAttendance > 0) {
            $notifications[] = [
                'type' => 'danger',
                'icon' => 'fa-exclamation-triangle',
                'title' => 'Absensi Mencurigakan',
                'message' => "{$suspiciousAttendance} absensi mencurigakan kemarin",
                'action' => route('administrasi.audit.index'),
            ];
        }

        // Interview today
        $interviewsToday = Recruitment::where('company_id', $companyId)
            ->whereDate('interview_date', today())
            ->count();
        if ($interviewsToday > 0) {
            $notifications[] = [
                'type' => 'success',
                'icon' => 'fa-calendar-check',
                'title' => 'Interview Hari Ini',
                'message' => "{$interviewsToday} interview scheduled today",
                'action' => route('administrasi.recruitment.index', ['filter' => 'today']),
            ];
        }

        // Training today
        $trainingsToday = Training::where('company_id', $companyId)
            ->whereDate('start_date', today())
            ->count();
        if ($trainingsToday > 0) {
            $notifications[] = [
                'type' => 'success',
                'icon' => 'fa-graduation-cap',
                'title' => 'Training Hari Ini',
                'message' => "{$trainingsToday} training scheduled today",
                'action' => route('administrasi.trainings.index'),
            ];
        }

        return $notifications;
    }
}
