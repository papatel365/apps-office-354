<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Modules\System\Models\User;
use App\Models\Salary;
use App\Services\Permission\UserPermissionService;
use Illuminate\Http\Request;

class SalaryController extends Controller
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

        $salariesQuery = Salary::query();

        // Filter by company
        if (!$user->is_developer && !$user->is_pusat_admin) {
            $salariesQuery->where('company_id', $user->company_id);
        }

        $salaries = $salariesQuery->with(['user', 'company'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('crm.hrd.salaries.index', compact('salaries'));
    }
}
