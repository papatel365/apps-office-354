<?php

namespace App\Console\Commands;

use App\Services\HRD\AttendanceDailyProcessor;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Process Daily Attendance
 *
 * Artisan command untuk memproses absensi harian dan membuat record Alpha
 * untuk karyawan yang tidak hadir.
 *
 * Usage:
 *   php artisan attendance:process-daily              # Process yesterday
 *   php artisan attendance:process-daily --date=2026-08-05   # Process specific date
 *   php artisan attendance:process-daily --force      # Force reprocess (delete and recreate)
 *   php artisan attendance:process-daily --company=1   # Process for specific company
 *
 * Schedule (in routes/console.php):
 *   $schedule->command('attendance:process-daily')->dailyAt('00:05');
 *   This runs at 00:05 every day to process yesterday's attendance.
 */
class ProcessDailyAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:process-daily
                            {--date= : Specific date to process (YYYY-MM-DD format, defaults to yesterday)}
                            {--company= : Process only for specific company ID}
                            {--force : Force reprocess - will delete existing absent records first}
                            {--dry-run : Show what would be processed without actually creating records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process daily attendance and create absent (Alpha) records for employees who did not check in';

    /**
     * Execute the console command.
     */
    public function handle(AttendanceDailyProcessor $processor): int
    {
        $this->info('Starting Daily Attendance Processor...');
        $this->newLine();

        // Parse date option
        $dateOption = $this->option('date');
        if ($dateOption) {
            try {
                $date = Carbon::parse($dateOption);
            } catch (\Exception $e) {
                $this->error("Invalid date format: {$dateOption}. Use YYYY-MM-DD format.");
                return self::FAILURE;
            }
        } else {
            // Default to yesterday
            $date = Carbon::yesterday();
        }

        // Check if date is in the future
        if ($date->isFuture()) {
            $this->warn("Cannot process future dates. Skipping.");
            return self::SUCCESS;
        }

        // Company filter
        $companyId = $this->option('company') ? (int) $this->option('company') : null;

        // Display processing info
        $this->info("Processing date: {$date->format('Y-m-d')} ({$date->isoFormat('dddd, D MMMM YYYY')})");

        if ($companyId) {
            $this->info("Company ID: {$companyId}");
        }

        if ($this->option('force')) {
            $this->warn("Force mode enabled - will delete existing absent records first");
        }

        if ($this->option('dry-run')) {
            $this->warn("DRY RUN MODE - No records will be created");
        }

        $this->newLine();

        // Check if this is a working day
        if (!$processor->isWorkingDay($date)) {
            $holidayName = $processor->getHolidayName($date);
            if ($holidayName) {
                $this->warn("{$date->format('Y-m-d')} is a national holiday: {$holidayName}");
            } else {
                $this->warn("{$date->format('Y-m-d')} is not a working day (Sunday)");
            }
            $this->info("No processing needed for non-working days.");
            return self::SUCCESS;
        }

        // Handle force mode - delete existing absent records first
        if ($this->option('force') && !$this->option('dry-run')) {
            $this->info("Deleting existing absent records for {$date->format('Y-m-d')}...");

            $query = \App\Models\HRD\Attendance::whereDate('date', $date->format('Y-m-d'))
                ->where('status', 'absent');

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $deleted = $query->delete();
            $this->info("Deleted {$deleted} existing absent records.");
            $this->newLine();
        }

        // Process attendance
        if ($this->option('dry-run')) {
            $this->dryRun($date, $companyId, $processor);
            return self::SUCCESS;
        }

        try {
            $result = $processor->processDate($date, $companyId);

            $this->newLine();
            $this->info("=== Processing Complete ===");
            $this->newLine();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Date Processed', $result['date']],
                    ['Total Employees Checked', $result['processed']],
                    ['Absent Records Created', $result['created']],
                    ['Records Skipped (Leave/Holiday)', $result['skipped']],
                    ['Errors', $result['errors']],
                    ['Duration', $result['duration_seconds'] . ' seconds'],
                ]
            );

            if ($result['errors'] > 0) {
                $this->warn("There were {$result['errors']} errors during processing. Check logs for details.");
            }

            $this->newLine();

            if ($result['created'] > 0) {
                $this->info("Successfully created {$result['created']} absent (Alpha) records.");
            } else {
                $this->info("No new absent records needed. All employees either attended or were exempted.");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Error processing attendance: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    /**
     * Dry run - show what would be processed without creating records.
     * Excludes directors and owners based on company_role field.
     */
    protected function dryRun(Carbon $date, ?int $companyId, AttendanceDailyProcessor $processor): void
    {
        $excludedRoles = ['director', 'owner'];

        $employees = \App\Models\HRD\EmployeeProfile::where('is_active', true)
            ->whereNotNull('user_id')
            ->whereHas('user', function ($q) use ($excludedRoles) {
                $q->where(function ($subQ) use ($excludedRoles) {
                    $subQ->whereNull('company_role')
                         ->orWhereNotIn('company_role', $excludedRoles);
                });
            });

        if ($companyId) {
            $employees->where('company_id', $companyId);
        }

        $employees = $employees->get();

        $this->info("DRY RUN - Would process {$employees->count()} employees (excluding directors/owners):");
        $this->newLine();

        $wouldCreate = 0;
        $wouldSkip = 0;

        $data = [];
        foreach ($employees as $employee) {
            $dateStr = $date->format('Y-m-d');

            // Check if attendance already exists
            $hasAttendance = \App\Models\HRD\Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $dateStr)
                ->exists();

            if ($hasAttendance) {
                $status = 'SKIP (already has attendance)';
                $wouldSkip++;
            } elseif ($processor->isWorkingDay($date)) {
                $status = 'CREATE ABSENT';
                $wouldCreate++;
            } else {
                $status = 'SKIP (not working day)';
                $wouldSkip++;
            }

            $data[] = [
                $employee->id,
                $employee->full_name ?? 'N/A',
                $status,
            ];
        }

        $this->table(
            ['ID', 'Employee Name', 'Action'],
            $data
        );

        $this->newLine();
        $this->info("Summary:");
        $this->info("  - Would CREATE: {$wouldCreate} absent records");
        $this->info("  - Would SKIP: {$wouldSkip} employees");
    }
}
