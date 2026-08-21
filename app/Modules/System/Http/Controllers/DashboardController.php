<?php

namespace App\Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardDataService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardDataService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $data = $this->dashboardService->getAllData();

        return view('dashboard.index', [
            'stats' => $data['stats'],
            'recentActivities' => $data['recentActivities'],
            'recentEmployees' => $data['recentEmployees'],
            'attendanceStats' => $data['attendanceStats'],
            'permissions' => $data['permissions'],
            'displayName' => auth()->user()->name,
        ]);
    }
}
