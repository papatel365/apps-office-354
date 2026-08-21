<?php

namespace App\Services\HRD;

use App\Models\HRD\Attendance;
use App\Models\HRD\EmployeeProfile;
use App\Modules\System\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Attendance Summary Service
 *
 * Service untuk mengambil statistik absensi dengan pengecualian
 * Director/Direktur dari perhitungan Hadir/Tidak Hadir.
 *
 * Filter dilakukan berdasarkan field company_role di tabel users.
 */
class AttendanceSummaryService
{
    /**
     * Roles yang dikecualikan dari perhitungan absensi.
     * Director dan Owner tidak wajib absensi.
     * Cek berdasarkan company_role field di tabel users.
     */
    protected array $excludedRoles = [
        'director',
        'owner',
    ];

    /**
     * Get attendance summary for a specific date with director exclusion.
     *
     * @param int $companyId
     * @param string $date Y-m-d format
     * @param int|null $departmentId Filter by department
     * @return array
     */
    public function getSummary(int $companyId, string $date, ?int $departmentId = null): array
    {
        // Get all eligible employees (active, non-director/non-owner)
        $eligibleEmployees = $this->getEligibleEmployees($companyId, $departmentId);
        $totalEligible = $eligibleEmployees->count();

        // Get IDs of eligible employees
        $eligibleEmployeeIds = $eligibleEmployees->pluck('id')->toArray();

        // Get attendance records for the date for eligible employees only
        $presentCount = Attendance::where('company_id', $companyId)
            ->whereDate('date', $date)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->whereIn('status', ['present', 'ontime', 'late'])
            ->count();

        // Get other statuses
        $leaveCount = Attendance::where('company_id', $companyId)
            ->whereDate('date', $date)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->where('status', 'leave')
            ->count();

        $sickCount = Attendance::where('company_id', $companyId)
            ->whereDate('date', $date)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->where('status', 'sick')
            ->count();

        $permitCount = Attendance::where('company_id', $companyId)
            ->whereDate('date', $date)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->where('status', 'permit')
            ->count();

        $absentCount = Attendance::where('company_id', $companyId)
            ->whereDate('date', $date)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->where('status', 'absent')
            ->count();

        // Total already processed
        $processedTotal = $presentCount + $leaveCount + $sickCount + $permitCount + $absentCount;

        // Tidak Hadir = eligible employees who don't have any attendance record
        $notPresentCount = max(0, $totalEligible - $processedTotal);

        return [
            'total_eligible' => $totalEligible,
            'present' => $presentCount,
            'late' => 0, // included in present
            'leave' => $leaveCount,
            'sick' => $sickCount,
            'permit' => $permitCount,
            'absent' => $absentCount,
            'not_present' => $notPresentCount,
            'not_present_count' => $notPresentCount,
        ];
    }

    /**
     * Get list of employees who were present on a specific date.
     * Includes eager loading to avoid N+1.
     *
     * @param int $companyId
     * @param string $date Y-m-d format
     * @param int|null $departmentId Filter by department
     * @return Collection
     */
    public function getPresentEmployees(int $companyId, string $date, ?int $departmentId = null): Collection
    {
        // Get IDs of eligible employees (non-director/non-owner)
        $eligibleEmployeeIds = $this->getEligibleEmployees($companyId, $departmentId)
            ->pluck('id')
            ->toArray();

        // Get attendance records for the date for eligible employees
        $attendanceEmployeeIds = Attendance::where('company_id', $companyId)
            ->whereDate('date', $date)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->whereIn('status', ['present', 'ontime', 'late'])
            ->pluck('employee_id')
            ->toArray();

        // Get employee profiles with eager loading
        return EmployeeProfile::whereIn('id', $attendanceEmployeeIds)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->with(['user', 'department', 'position', 'attendances' => function ($q) use ($date) {
                $q->whereDate('date', $date)
                  ->whereIn('status', ['present', 'ontime', 'late']);
            }])
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Get list of employees who were NOT present on a specific date.
     *
     * @param int $companyId
     * @param string $date Y-m-d format
     * @param int|null $departmentId Filter by department
     * @return Collection
     */
    public function getNotPresentEmployees(int $companyId, string $date, ?int $departmentId = null): Collection
    {
        // Get IDs of eligible employees (non-director/non-owner)
        $eligibleEmployeeIds = $this->getEligibleEmployees($companyId, $departmentId)
            ->pluck('id')
            ->toArray();

        // Get employees who have any attendance record on this date
        $hasAttendanceIds = Attendance::where('company_id', $companyId)
            ->whereDate('date', $date)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->pluck('employee_id')
            ->toArray();

        // Employees who should be present but don't have any attendance records
        $notPresentIds = array_diff($eligibleEmployeeIds, $hasAttendanceIds);

        // Get employee profiles with eager loading
        return EmployeeProfile::whereIn('id', $notPresentIds)
            ->with(['user', 'department', 'position'])
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Get eligible employees (excluding directors/owners).
     * Uses company_role field from users table.
     *
     * @param int $companyId
     * @param int|null $departmentId Filter by department
     * @return Collection
     */
    protected function getEligibleEmployees(int $companyId, ?int $departmentId = null): Collection
    {
        // Get user IDs that have excluded roles via company_role field
        $excludedRoleNames = array_map('strtolower', $this->excludedRoles);

        // Get active employee profiles whose users have company_role NOT IN excluded list
        $query = EmployeeProfile::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->whereHas('user', function ($q) use ($excludedRoleNames) {
                $q->where(function ($subQ) use ($excludedRoleNames) {
                    $subQ->whereNull('company_role')
                         ->orWhereNotIn('company_role', $excludedRoleNames);
                });
            });

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return $query->get();
    }

    /**
     * Check if a role should be excluded from attendance.
     *
     * @param string|null $role
     * @return bool
     */
    public function isExcludedRole(?string $role): bool
    {
        if (!$role) return false;
        return in_array(strtolower($role), array_map('strtolower', $this->excludedRoles));
    }
}
