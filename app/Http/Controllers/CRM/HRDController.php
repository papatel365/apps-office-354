<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CrmModulePermission;
use App\Modules\System\Models\User;
use App\Services\Permission\UserPermissionService;
use Illuminate\Http\Request;

class HRDController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Check if user has access via sidebar.staff permission using UserPermissionService
        $service = UserPermissionService::forUser($user);

        // Super admin can access everything
        if (!$service->can('staff')) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Get users based on company
        $usersQuery = User::query();

        // Developer/Pusat can see all companies, others only their company
        if (!$user->is_developer && !$user->is_pusat_admin) {
            $usersQuery->where('company_id', $user->company_id);

            // Manager can only see users in their division
            if ($user->is_manager && $user->division_id) {
                $usersQuery->where('division_id', $user->division_id);
            }
        }

        $users = $usersQuery->orderBy('name')->paginate(20);

        return view('crm.hrd.index', compact('users'));
    }
}
