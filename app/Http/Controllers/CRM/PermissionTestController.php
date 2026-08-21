<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PermissionTestController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $user->load('division');

        $allPermissions = \App\Models\Division::getAvailablePermissions();

        $testPermissions = [
            'dashboard', 'clients', 'leads', 'sales', 'subscriptions',
            'expenses', 'transactions', 'contracts', 'projects', 'tasks',
            'reports', 'assets', 'audit', 'knowledge_base',
        ];

        $results = [];
        foreach ($testPermissions as $perm) {
            $results[$perm] = [
                'can_access' => $user->hasSidebarPermission($perm),
                'label' => $allPermissions[$perm] ?? $perm,
            ];
        }

        return view('crm.permission-test', compact('user', 'results', 'allPermissions'));
    }
}
