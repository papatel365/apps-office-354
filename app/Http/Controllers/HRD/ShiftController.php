<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Department;
use App\Models\HRD\EmployeeProfile;
use App\Models\HRD\Shift;
use App\Models\HRD\ShiftSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ShiftController extends Controller
{
    /**
     * Shift List
     */
    public function index(): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $shifts = Shift::where('company_id', $companyId)
            ->active()
            ->orderBy('start_time')
            ->get();

        return view('crm.hrd.shifts.index', compact('shifts'));
    }

    /**
     * Create Shift
     */
    public function create(): View
    {
        return view('crm.hrd.shifts.create');
    }

    /**
     * Store Shift
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:shifts,code',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'grace_period_minutes' => 'nullable|integer|min:0',
            'late_tolerance_minutes' => 'nullable|integer|min:0',
            'early_out_tolerance_minutes' => 'nullable|integer|min:0',
            'overtime_start_time' => 'nullable|date_format:H:i',
            'color' => 'nullable|string|max:7',
            'is_night_shift' => 'nullable|boolean',
        ]);

        Shift::create(array_merge($validated, [
            'company_id' => $companyId,
            'is_active' => true,
        ]));

        return redirect()
            ->route('hrd.shifts.index')
            ->with('success', 'Shift berhasil ditambahkan');
    }

    /**
     * Edit Shift
     */
    public function edit(Shift $shift): View
    {
        return view('crm.hrd.shifts.edit', compact('shift'));
    }

    /**
     * Update Shift
     */
    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:shifts,code,' . $shift->id,
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'grace_period_minutes' => 'nullable|integer|min:0',
            'late_tolerance_minutes' => 'nullable|integer|min:0',
            'early_out_tolerance_minutes' => 'nullable|integer|min:0',
            'overtime_start_time' => 'nullable|date_format:H:i',
            'color' => 'nullable|string|max:7',
            'is_night_shift' => 'nullable|boolean',
        ]);

        $shift->update($validated);

        return redirect()
            ->route('hrd.shifts.index')
            ->with('success', 'Shift berhasil diperbarui');
    }

    /**
     * Shift Calendar
     */
    public function calendar(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;

        $employees = EmployeeProfile::where('company_id', $companyId)
            ->active()
            ->with(['user', 'department'])
            ->orderBy('department_id')
            ->get();

        $shifts = Shift::where('company_id', $companyId)->active()->get();

        // Get schedules for the month
        $schedules = ShiftSchedule::where('company_id', $companyId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->with(['employee', 'shift'])
            ->get()
            ->groupBy(fn($s) => $s->employee_id . '-' . $s->date->format('Y-m-d'));

        return view('crm.hrd.shifts.calendar', compact(
            'employees', 'shifts', 'schedules', 'year', 'month'
        ));
    }

    /**
     * Assign Shift to Employee
     */
    public function assignShift(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'employee_id' => 'required|exists:employee_profiles,id',
            'shift_id' => 'required|exists:shifts,id',
            'date' => 'required|date',
        ]);

        ShiftSchedule::updateOrCreate(
            [
                'employee_id' => $validated['employee_id'],
                'date' => $validated['date'],
            ],
            [
                'company_id' => $companyId,
                'shift_id' => $validated['shift_id'],
            ]
        );

        return back()->with('success', 'Shift berhasil ditugaskan');
    }

    /**
     * Copy Weekly Shifts
     */
    public function copyWeek(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'from_week' => 'required|date',
            'to_week' => 'required|date|after:from_week',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employee_profiles,id',
        ]);

        // Week starts on Sunday (day 0) for Indonesian calendar
        $fromDate = Carbon::parse($validated['from_week'])->startOfWeek(Carbon::SUNDAY);
        $toDate = Carbon::parse($validated['to_week'])->startOfWeek(Carbon::SUNDAY);

        // Get source schedules
        $sourceSchedules = ShiftSchedule::where('company_id', $companyId)
            ->whereBetween('date', [$fromDate, $fromDate->copy()->endOfWeek(Carbon::SUNDAY)])
            ->whereIn('employee_id', $validated['employee_ids'])
            ->get()
            ->keyBy(fn($s) => $s->employee_id . '-' . $s->date->format('N')); // Group by day of week

        // Copy to destination week (week starts on Sunday for Indonesian calendar)
        $destDate = $toDate;
        while ($destDate->lte($toDate->copy()->endOfWeek(Carbon::SUNDAY))) {
            $dayOfWeek = $destDate->dayOfWeek;

            foreach ($validated['employee_ids'] as $employeeId) {
                $sourceKey = $employeeId . '-' . $dayOfWeek;
                if (isset($sourceSchedules[$sourceKey])) {
                    ShiftSchedule::updateOrCreate(
                        [
                            'employee_id' => $employeeId,
                            'date' => $destDate->format('Y-m-d'),
                        ],
                        [
                            'company_id' => $companyId,
                            'shift_id' => $sourceSchedules[$sourceKey]->shift_id,
                        ]
                    );
                }
            }

            $destDate->addDay();
        }

        return back()->with('success', 'Shift mingguan berhasil disalin');
    }

    /**
     * Delete Shift
     */
    public function destroy(Shift $shift): RedirectResponse
    {
        $shift->update(['is_active' => false]);

        return redirect()
            ->route('hrd.shifts.index')
            ->with('success', 'Shift berhasil dihapus');
    }
}
