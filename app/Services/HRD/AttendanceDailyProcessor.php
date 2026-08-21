<?php

namespace App\Services\HRD;

use App\Models\HRD\Attendance;
use App\Models\HRD\EmployeeProfile;
use App\Models\HRD\Leave;
use App\Models\HRD\ShiftSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Attendance Daily Processor
 *
 * Service untuk memproses absensi harian dan membuat record Alpha
 * untuk karyawan yang tidak hadir pada hari kerja.
 *
 * Logika:
 * - Dijalankan setiap pergantian hari (via scheduler atau lazy load)
 * - Mengecek tanggal kemarin untuk setiap karyawan aktif
 * - Jika tidak ada attendance record DAN bukan hari libur/minggu/cuti,
 *   maka buat record dengan status 'absent'
 */
class AttendanceDailyProcessor
{
    /**
     * Holiday nasional Indonesia untuk 2025-2026
     * Format: 'YYYY-MM-DD' => 'Nama Hari Libur'
     */
    protected array $nationalHolidays = [
        // 2025
        '2025-01-01' => 'Tahun Baru 2025',
        '2025-01-29' => 'Isra Mi\'raj',
        '2025-01-31' => 'Tahun Baru Imlek 2576',
        '2025-02-27' => 'Harisuci Nyepi Tahun Baru Saka 1947',
        '2025-03-20' => 'Hari Raya Idulfitri 1446 H (Jumat)',
        '2025-03-21' => 'Hari Raya Idulfitri 1446 H (Sabtu)',
        '2025-03-24' => 'Cuti Bersama Idulfitri',
        '2025-03-25' => 'Cuti Bersama Idulfitri',
        '2025-03-26' => 'Cuti Bersama Idulfitri',
        '2025-03-27' => 'Cuti Bersama Idulfitri',
        '2025-03-28' => 'Cuti Bersama Idulfitri',
        '2025-04-18' => 'Wafat Yesus Kristus',
        '2025-05-01' => 'Hari Buruh Internasional',
        '2025-05-12' => 'Hari Raya Ascension',
        '2025-05-29' => 'Hari Raya Waisak 2569',
        '2025-06-01' => 'Hari Lahir Pancasila',
        '2025-06-07' => 'Hari Raya Idulfitri 1447 H (Lebaran Haji)',
        '2025-08-17' => 'Hari Ulang Tahun Kemerdekaan RI',
        '2025-08-21' => 'Maulid Nabi Muhammad SAW',
        '2025-12-25' => 'Hari Raya Natal',
        '2025-12-26' => 'Cuti Bersama Natal',

        // 2026
        '2026-01-01' => 'Tahun Baru 2026',
        '2026-01-27' => 'Isra Mi\'raj',
        '2026-02-17' => 'Tahun Baru Imlek 2577',
        '2026-03-03' => 'Hari Suci Nyepi Tahun Baru Saka 1948',
        '2026-03-10' => 'Hari Raya Idulfitri 1447 H',
        '2026-03-11' => 'Hari Raya Idulfitri 1447 H',
        '2026-04-03' => 'Wafat Yesus Kristus',
        '2026-05-01' => 'Hari Buruh Internasional',
        '2026-05-14' => 'Hari Raya Ascension',
        '2026-05-21' => 'Hari Raya Waisak 2570',
        '2026-06-01' => 'Hari Lahir Pancasila',
        '2026-08-17' => 'Hari Ulang Tahun Kemerdekaan RI',
        '2026-08-27' => 'Maulid Nabi Muhammad SAW',
        '2026-09-06' => 'Hari Raya Idulfitri 1448 H (Lebaran Haji)',
        '2026-12-25' => 'Hari Raya Natal',
    ];

    /**
     * Process yesterday's attendance for all companies.
     * Should be called daily via scheduler or lazy load.
     *
     * @param Carbon|null $date The date to process (defaults to yesterday)
     * @return array Processing result summary
     */
    public function processYesterday(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::yesterday();
        return $this->processDate($date);
    }

    /**
     * Roles yang dikecualikan dari perhitungan absensi.
     * Director dan Owner tidak wajib absensi.
     */
    protected array $excludedRoles = [
        'director',
        'owner',
    ];

    /**
     * Process attendance for a specific date.
     *
     * @param Carbon $date The date to process
     * @param int|null $companyId Process only for specific company (null = all)
     * @return array Processing result summary
     */
    public function processDate(Carbon $date, ?int $companyId = null): array
    {
        $startTime = microtime(true);
        $processed = 0;
        $created = 0;
        $skipped = 0;
        $errors = [];

        Log::info("[AttendanceDailyProcessor] Starting processing for date: {$date->format('Y-m-d')}");

        try {
            // Get all active employees EXCLUDING directors/owners based on company_role
            $query = EmployeeProfile::where('is_active', true)
                ->whereNotNull('user_id')
                ->whereHas('user', function ($q) {
                    $q->where(function ($subQ) {
                        $subQ->whereNull('company_role')
                             ->orWhereNotIn('company_role', $this->excludedRoles);
                    });
                });

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $employees = $query->get();

            foreach ($employees as $employee) {
                try {
                    $result = $this->processEmployeeAttendance($employee, $date);

                    if ($result === 'created') {
                        $created++;
                    } elseif ($result === 'skipped') {
                        $skipped++;
                    }

                    $processed++;
                } catch (\Throwable $e) {
                    $errors[] = [
                        'employee_id' => $employee->id,
                        'error' => $e->getMessage(),
                    ];
                    Log::error("[AttendanceDailyProcessor] Error processing employee {$employee->id}: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::error("[AttendanceDailyProcessor] Fatal error: " . $e->getMessage());
            throw $e;
        }

        $duration = round(microtime(true) - $startTime, 2);

        $summary = [
            'date' => $date->format('Y-m-d'),
            'processed' => $processed,
            'created' => $created,
            'skipped' => $skipped,
            'errors' => count($errors),
            'duration_seconds' => $duration,
        ];

        Log::info("[AttendanceDailyProcessor] Completed: {$created} absent records created, {$skipped} skipped, {$processed} processed in {$duration}s");

        return $summary;
    }

    /**
     * Process attendance for a specific employee on a specific date.
     *
     * @param EmployeeProfile $employee
     * @param Carbon $date
     * @return string 'created'|'skipped'|'already_exists'
     */
    protected function processEmployeeAttendance(EmployeeProfile $employee, Carbon $date): string
    {
        $dateStr = $date->format('Y-m-d');

        // Check if attendance record already exists for this employee on this date
        $existingAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $dateStr)
            ->exists();

        if ($existingAttendance) {
            return 'already_exists';
        }

        // Check if this date should be excluded from Alpha processing
        if ($this->shouldSkipDate($date, $employee)) {
            return 'skipped';
        }

        // Create absent record
        DB::transaction(function () use ($employee, $date, $dateStr) {
            // Get employee's shift schedule for this date (if any)
            $shiftSchedule = ShiftSchedule::where('employee_id', $employee->id)
                ->whereDate('date', $dateStr)
                ->with('shift')
                ->first();

            Attendance::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'user_id' => $employee->user_id,
                'placement_id' => $employee->placement_id,
                'date' => $dateStr,
                'status' => 'absent',
                'notes' => 'Alpha - Generated automatically by system',
                'shift_id' => $shiftSchedule?->shift_id,
                'shift_name' => $shiftSchedule?->shift?->name,
                'shift_start' => $shiftSchedule?->shift?->start_time,
                'shift_end' => $shiftSchedule?->shift?->end_time,
            ]);

            Log::debug("[AttendanceDailyProcessor] Created absent record for employee {$employee->id} on {$dateStr}");
        });

        return 'created';
    }

    /**
     * Check if a date should be skipped (not counted as Alpha).
     *
     * Conditions to skip:
     * 1. Sunday (dayOfWeek === 0)
     * 2. National holiday
     * 3. Employee has approved leave on this date
     * 4. Employee has approved sick leave on this date
     * 5. Employee has approved permission on this date
     * 6. Employee has approved overtime on this date
     * 7. Employee's shift schedule marks this as off-day
     * 8. Date is in the future
     * 9. Employee hasn't joined yet (join_date > date)
     *
     * @param Carbon $date
     * @param EmployeeProfile $employee
     * @return bool
     */
    protected function shouldSkipDate(Carbon $date, EmployeeProfile $employee): bool
    {
        $dateStr = $date->format('Y-m-d');

        // Skip future dates
        if ($date->isFuture()) {
            return true;
        }

        // Skip if employee hasn't joined yet
        if ($employee->join_date && $date->lt($employee->join_date)) {
            return true;
        }

        // Skip Sundays (dayOfWeek 0)
        if ($date->dayOfWeek === Carbon::SUNDAY) {
            return true;
        }

        // Skip national holidays
        if ($this->isNationalHoliday($date)) {
            return true;
        }

        // Check if employee has approved leave on this date
        if ($this->hasApprovedLeave($employee->id, $date)) {
            return true;
        }

        // Check if employee has approved sick leave on this date
        if ($this->hasApprovedSickLeave($employee->id, $date)) {
            return true;
        }

        // Check if employee has approved permission on this date
        if ($this->hasApprovedPermission($employee->id, $date)) {
            return true;
        }

        // Check if employee has approved overtime on this date
        if ($this->hasApprovedOvertime($employee->id, $date)) {
            return true;
        }

        // Check if this is employee's off-day according to shift schedule
        if ($this->isOffDay($employee->id, $date)) {
            return true;
        }

        return false;
    }

    /**
     * Check if date is a national holiday.
     */
    protected function isNationalHoliday(Carbon $date): bool
    {
        return isset($this->nationalHolidays[$date->format('Y-m-d')]);
    }

    /**
     * Check if employee has approved leave on this date.
     */
    protected function hasApprovedLeave(int $employeeId, Carbon $date): bool
    {
        return Leave::where('user_id', $employeeId)
            ->whereIn('status', [Leave::STATUS_APPROVED, Leave::STATUS_APPROVED_SUPERVISOR])
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    /**
     * Check if employee has approved sick leave on this date.
     * Note: Currently sick is part of Leave model with leave_type = 'sick'
     */
    protected function hasApprovedSickLeave(int $employeeId, Carbon $date): bool
    {
        return Leave::where('user_id', $employeeId)
            ->whereIn('status', [Leave::STATUS_APPROVED, Leave::STATUS_APPROVED_SUPERVISOR])
            ->where('leave_type', 'sick')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    /**
     * Check if employee has approved permission on this date.
     * Note: Currently permission is part of Leave model with leave_type = 'permission'
     */
    protected function hasApprovedPermission(int $employeeId, Carbon $date): bool
    {
        return Leave::where('user_id', $employeeId)
            ->whereIn('status', [Leave::STATUS_APPROVED, Leave::STATUS_APPROVED_SUPERVISOR])
            ->where('leave_type', 'permission')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    /**
     * Check if employee has approved overtime on this date.
     * Note: We check overtime records for the date
     */
    protected function hasApprovedOvertime(int $employeeId, Carbon $date): bool
    {
        // Check if Overtime model exists and has approved status
        if (class_exists(\App\Models\HRD\Overtime::class)) {
            $overtime = \App\Models\HRD\Overtime::where('employee_id', $employeeId)
                ->whereDate('date', $date)
                ->where('status', 'approved')
                ->exists();

            if ($overtime) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this is employee's off-day according to shift schedule.
     *
     * Logic:
     * - Get employee's shift schedule for this date
     * - If no schedule exists, assume regular 5-day work week (Mon-Fri)
     * - If schedule exists, check if it's marked as off-day
     */
    protected function isOffDay(int $employeeId, Carbon $date): bool
    {
        $dateStr = $date->format('Y-m-d');

        // Check if there's a shift schedule for this date
        $schedule = ShiftSchedule::where('employee_id', $employeeId)
            ->whereDate('date', $dateStr)
            ->first();

        // If no schedule exists for this specific date,
        // assume it's a working day (Mon-Fri) unless Sunday check already caught it
        if (!$schedule) {
            // Default: Mon-Fri is working, Sat is off (for non-shift employees)
            // But Sunday is already handled above
            return false; // Let it be processed as potential absent
        }

        // If schedule exists but shift_id is null or shift marked as off
        if ($schedule->shift_id === null) {
            return true; // This is an off day
        }

        // Check if the shift itself has any special marking
        $shift = $schedule->shift;
        if ($shift && $shift->is_active === false) {
            return true; // Shift is inactive = off day
        }

        return false;
    }

    /**
     * Get the holiday name for a specific date.
     *
     * @param Carbon $date
     * @return string|null
     */
    public function getHolidayName(Carbon $date): ?string
    {
        return $this->nationalHolidays[$date->format('Y-m-d')] ?? null;
    }

    /**
     * Check if a date is a working day.
     *
     * @param Carbon $date
     * @param int|null $companyId
     * @return bool
     */
    public function isWorkingDay(Carbon $date, ?int $companyId = null): bool
    {
        // Future dates are not working days
        if ($date->isFuture()) {
            return false;
        }

        // Sunday is never a working day
        if ($date->dayOfWeek === Carbon::SUNDAY) {
            return false;
        }

        // National holidays are not working days
        if ($this->isNationalHoliday($date)) {
            return false;
        }

        return true;
    }

    /**
     * Count working days in a month (excluding weekends and holidays).
     *
     * @param int $year
     * @param int $month
     * @return int
     */
    public function countWorkingDaysInMonth(int $year, int $month): int
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $workingDays = 0;
        while ($start <= $end) {
            if ($this->isWorkingDay($start)) {
                $workingDays++;
            }
            $start->addDay();
        }

        return $workingDays;
    }
}
