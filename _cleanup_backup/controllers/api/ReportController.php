<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $from = $request->from ?? Carbon::now()->startOfMonth();
        $to = $request->to ?? Carbon::now()->endOfMonth();

        $invoices = \App\Models\Invoice::whereBetween('date', [$from, $to])->get();
        $proposals = \App\Models\Proposal::whereBetween('date', [$from, $to])->get();
        $estimates = \App\Models\Estimate::whereBetween('date', [$from, $to])->get();

        $data = [
            'invoices' => [
                'count' => $invoices->count(),
                'total' => $invoices->sum('total'),
                'paid' => $invoices->where('status', 'paid')->sum('total'),
            ],
            'proposals' => [
                'count' => $proposals->count(),
                'total' => $proposals->sum('total'),
                'accepted' => $proposals->where('status', 'accepted')->count(),
            ],
            'estimates' => [
                'count' => $estimates->count(),
                'total' => $estimates->sum('total'),
                'accepted' => $estimates->where('status', 'accepted')->count(),
            ],
        ];

        return response()->json(['data' => $data]);
    }

    public function assets()
    {
        $assets = \App\Models\Asset::all();
        $categories = \App\Models\AssetCategory::withCount('assets')->get();

        $data = [
            'total' => $assets->count(),
            'total_value' => $assets->sum('purchase_cost'),
            'by_status' => $assets->groupBy('status')->map->count(),
            'by_category' => $categories,
        ];

        return response()->json(['data' => $data]);
    }

    public function projects(Request $request)
    {
        $projects = \App\Models\Project::with('client')->get();

        $data = [
            'total' => $projects->count(),
            'by_status' => $projects->groupBy('status')->map->count(),
            'projects' => $projects,
        ];

        return response()->json(['data' => $data]);
    }
}
