<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Recruitment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruitmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $query = Recruitment::where('company_id', $companyId);

        if ($request->stage) {
            $query->where('stage', $request->stage);
        }

        $recruitments = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('crm.hrd.recruitment.index', compact('recruitments'));
    }
}
