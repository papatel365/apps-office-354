<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        // Placeholder for audit functionality
        return view('crm.hrd.audit.index', compact('companyId'));
    }
}
