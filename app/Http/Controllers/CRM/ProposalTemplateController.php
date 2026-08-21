<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\ProposalTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProposalTemplateController extends Controller
{
    /**
     * Display proposal templates list.
     */
    public function index(): View
    {
        $templates = ProposalTemplate::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        return view('crm.proposal-templates.index', compact('templates'));
    }
}
