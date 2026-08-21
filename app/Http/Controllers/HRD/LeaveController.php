<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\EmployeeProfile;
use App\Models\HRD\Leave;
use App\Models\HRD\LeaveType;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class LeaveController extends Controller
{
    /**
     * Leave List
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $status = $request->status ?? 'all';
        $type = $request->type ?? 'all';
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $query = Leave::where('company_id', $companyId)
            ->with(['employee', 'leaveType']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== 'all') {
            $query->where('leave_type_id', $type);
        }

        if ($month && $year) {
            $query->whereMonth('start_date', $month)
                ->whereYear('start_date', $year);
        }

        $leaves = $query->orderBy('created_at', 'desc')->paginate(20);

        $leaveTypes = LeaveType::where('company_id', $companyId)->active()->get();

        // Statistics
        $stats = [
            'total' => Leave::where('company_id', $companyId)->count(),
            'pending' => Leave::where('company_id', $companyId)->where('status', 'pending')->count(),
            'approved' => Leave::where('company_id', $companyId)->where('status', 'approved')->count(),
            'rejected' => Leave::where('company_id', $companyId)->where('status', 'rejected')->count(),
        ];

        return view('crm.hrd.leaves.index', compact(
            'leaves', 'leaveTypes', 'stats',
            'status', 'type', 'month', 'year'
        ));
    }

    /**
     * Create Leave Request
     */
    public function create(): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $leaveTypes = LeaveType::where('company_id', $companyId)->active()->get();
        $employees = EmployeeProfile::where('company_id', $companyId)->active()->with('user')->get();

        return view('crm.hrd.leaves.create', compact('leaveTypes', 'employees'));
    }

    /**
     * Store Leave Request
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'employee_id' => 'required|exists:employee_profiles,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'document' => 'nullable|file|max:10240',
            'is_half_day' => 'nullable|boolean',
            'half_day_type' => 'nullable|in:morning,afternoon',
            'handover_notes' => 'nullable|string',
            'contact_during_leave' => 'nullable|string|max:100',
        ]);

        // Calculate total days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Check leave balance for paid leave
        $leaveType = LeaveType::find($validated['leave_type_id']);
        $employee = EmployeeProfile::find($validated['employee_id']);

        if ($leaveType->is_paid) {
            $balance = $employee->leave_balance;
            if ($totalDays > $balance) {
                return back()
                    ->withInput()
                    ->with('error', "Saldo cuti tidak cukup. Sisa saldo: {$balance} hari");
            }
        }

        $data = $validated;
        $data['company_id'] = $companyId;
        $data['total_days'] = $totalDays;
        $data['remaining_balance_after'] = $leaveType->is_paid ? $employee->leave_balance - $totalDays : null;
        $data['status'] = 'pending';

        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('hrd/leaves/documents');
        }

        Leave::create($data);

        return redirect()
            ->route('hrd.leaves.index')
            ->with('success', 'Pengajuan cuti berhasil diajukan');
    }

    /**
     * Approve Leave
     */
    public function approve(Leave $leave): JsonResponse
    {
        if ($leave->approve(auth()->user())) {
            return response()->json([
                'success' => true,
                'message' => 'Cuti berhasil disetujui',
                'status' => $leave->status,
                'status_label' => $leave->status_label,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak dapat menyetujui cuti ini',
        ], 400);
    }

    /**
     * Reject Leave
     */
    public function reject(Request $request, Leave $leave): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($leave->reject(auth()->user(), $validated['reason'])) {
            return response()->json([
                'success' => true,
                'message' => 'Cuti ditolak',
                'status' => $leave->status,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak dapat menolak cuti ini',
        ], 400);
    }

    /**
     * Leave Balance Management
     */
    public function balances(): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $leaveTypes = LeaveType::where('company_id', $companyId)->active()->get();
        $employees = EmployeeProfile::where('company_id', $companyId)
            ->active()
            ->with('user')
            ->orderBy('department_id')
            ->get();

        return view('crm.hrd.leaves.balances', compact('leaveTypes', 'employees'));
    }

    /**
     * Update Leave Balance
     */
    public function updateBalance(Request $request, EmployeeProfile $employee): RedirectResponse
    {
        $validated = $request->validate([
            'leave_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $employee->update([
            'leave_balance' => $validated['leave_balance'],
        ]);

        return back()->with('success', 'Saldo cuti berhasil diperbarui');
    }
}
