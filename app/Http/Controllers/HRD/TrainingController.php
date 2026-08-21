<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Training;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $query = Training::where('company_id', $companyId);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $trainings = $query->orderBy('start_date', 'desc')->paginate(20);

        return view('crm.hrd.trainings.index', compact('trainings'));
    }
}
