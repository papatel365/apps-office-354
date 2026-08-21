<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CrmModulePermission;
use App\Models\HRD\Attendance;
use App\Traits\CrmPermissionQueries;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use CrmPermissionQueries;

    public function index(Request $request)
    {
        $user = auth()->user();

        // Check if user has ANY view permission for attendance
        if (!$this->canViewAny(CrmModulePermission::MODULE_ATTENDANCES)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $query = Attendance::query();

        // Apply CRM permission filter
        $query = $this->applyAttendancePermissionFilter($query);

        // Additional filters
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->with(['user', 'company'])
            ->orderBy('date', 'desc')
            ->paginate(20);

        return view('crm.hrd.attendances.index', compact('attendances'));
    }

    /**
     * Check if user can perform attendance action (check in/out).
     */
    public function canCheckIn(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Check for access permission
        if ($this->hasAccess(CrmModulePermission::MODULE_ATTENDANCES)) {
            return true;
        }

        // Also allow if user has view_own permission
        return $this->canViewOwn(CrmModulePermission::MODULE_ATTENDANCES);
    }
}
