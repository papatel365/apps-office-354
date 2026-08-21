<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Attendance;
use App\Models\HRD\EmployeeProfile;
use App\Models\HRD\Leave;
use App\Models\HRD\Overtime;
use App\Models\HRD\Salary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PayrollController extends Controller
{
    /**
     * Roles that are excluded from payroll calculations.
     * Directors typically don't receive regular payroll deductions.
     */
    protected array $excludedRoles = ['director', 'owner'];

    /**
     * Payroll List
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;
        $status = $request->status ?? 'all';

        $query = Salary::where('company_id', $companyId)
            ->forPeriod($year, $month)
            ->with(['employee']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $salaries = $query->orderBy('created_at', 'desc')->get();

        // Statistics
        $stats = [
            'total' => $salaries->count(),
            'pending' => $salaries->where('status', 'pending')->count(),
            'approved' => $salaries->where('status', 'approved')->count(),
            'paid' => $salaries->where('status', 'paid')->count(),
            'total_gross' => $salaries->sum('basic_salary') + $salaries->sum('allowances'),
            'total_deductions' => $salaries->sum(function ($s) {
                return $s->late_deduction + $s->absent_deduction + $s->bpjs_employee + $s->tax;
            }),
            'total_net' => $salaries->sum('total_salary'),
        ];

        return view('crm.hrd.payroll.index', compact(
            'salaries', 'month', 'year', 'status', 'stats'
        ));
    }

    /**
     * Generate Payroll
     */
    public function generate(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        // Check if already generated
        $existing = Salary::where('company_id', $companyId)
            ->forPeriod($year, $month)
            ->exists();

        if ($existing) {
            return back()->with('error', 'Payroll untuk periode ini sudah digenerate');
        }

        // Get active employees EXCLUDING directors/owners based on company_role
        $employees = EmployeeProfile::where('company_id', $companyId)
            ->active()
            ->whereNotNull('user_id')
            ->whereHas('user', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereNull('company_role')
                         ->orWhereNotIn('company_role', $this->excludedRoles);
                });
            })
            ->get();

        $batchCount = 0;
        foreach ($employees as $employee) {
            // Calculate deductions
            $lateMinutes = Attendance::where('employee_id', $employee->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->late()
                ->sum('late_minutes');

            $lateDeduction = $lateMinutes * ($employee->basic_salary / 480) * 0.5; // 50% penalty

            $absentDays = 0; // Calculate based on attendance
            $absentDeduction = $absentDays * ($employee->basic_salary / 22);

            // Overtime
            $overtimeHours = Overtime::where('employee_id', $employee->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->approved()
                ->sum('total_hours');

            $overtimePay = $overtimeHours * ($employee->basic_salary / 173);

            // Calculate totals
            $grossSalary = $employee->basic_salary + $employee->allowances;
            $totalDeductions = $lateDeduction + $absentDeduction + ($employee->bpjs_amount ?? 0);
            $totalSalary = $grossSalary + $overtimePay - $totalDeductions;

            Salary::create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'period_month' => $month,
                'period_year' => $year,
                'basic_salary' => $employee->basic_salary,
                'allowances' => $employee->allowances,
                'overtime_pay' => $overtimePay,
                'late_deduction' => $lateDeduction,
                'absent_deduction' => $absentDeduction,
                'bpjs_employee' => $employee->bpjs_amount ?? 0,
                'deductions' => $totalDeductions,
                'total_salary' => $totalSalary,
                'status' => 'draft',
            ]);

            $batchCount++;
        }

        return redirect()
            ->route('hrd.payroll.index', ['month' => $month, 'year' => $year])
            ->with('success', "Payroll berhasil digenerate untuk {$batchCount} karyawan");
    }

    /**
     * Approve Payroll
     */
    public function approve(Salary $salary): RedirectResponse
    {
        $salary->approve(auth()->user());

        return back()->with('success', 'Payroll berhasil disetujui');
    }

    /**
     * Mark as Paid
     */
    public function markAsPaid(Salary $salary): RedirectResponse
    {
        $salary->markAsPaid();

        return back()->with('success', 'Payroll berhasil ditandai dibayar');
    }

    /**
     * Export Payslip
     */
    public function exportPayslip(Salary $salary)
    {
        $salary->load(['employee', 'employee.department', 'employee.position']);

        // Return PDF or view for printing
        return view('crm.hrd.payroll.payslip', compact('salary'));
    }

    /**
     * Bulk Approve
     */
    public function bulkApprove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'salary_ids' => 'required|array',
            'salary_ids.*' => 'exists:salaries,id',
        ]);

        Salary::whereIn('id', $validated['salary_ids'])
            ->pending()
            ->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

        return back()->with('success', count($validated['salary_ids']) . ' payroll berhasil disetujui');
    }
}
