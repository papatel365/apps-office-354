<?php

namespace App\Repositories;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Models\Asset;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Contract;
use App\Models\Subscription;
use App\Models\ActivityLog;

class DashboardRepository
{
    /**
     * Get dashboard statistics.
     */
    public function getStatistics(): array
    {
        return [
            'clients' => [
                'total' => Client::count(),
                'active' => Client::active()->count(),
            ],
            'leads' => [
                'total' => Lead::count(),
                'new' => Lead::byStatus('new')->count(),
                'qualified' => Lead::byStatus('qualified')->count(),
                'won' => Lead::byStatus('won')->count(),
            ],
            'invoices' => [
                'total' => Invoice::count(),
                'draft' => Invoice::draft()->count(),
                'pending' => Invoice::pending()->count(),
                'overdue' => Invoice::overdue()->count(),
                'total_outstanding' => Invoice::pending()->sum('remaining_amount'),
            ],
            'projects' => [
                'total' => Project::count(),
                'active' => Project::active()->count(),
                'completed' => Project::completed()->count(),
            ],
            'tasks' => [
                'total' => Task::count(),
                'pending' => Task::pending()->count(),
                'overdue' => Task::overdue()->count(),
            ],
            'assets' => [
                'total' => Asset::count(),
                'available' => Asset::available()->count(),
                'assigned' => Asset::assigned()->count(),
                'maintenance' => Asset::inMaintenance()->count(),
            ],
            'payments' => [
                'this_month' => Payment::thisMonth()->sum('amount'),
            ],
        ];
    }

    /**
     * Get recent activities.
     */
    public function getRecentActivities(int $limit = 20): array
    {
        return ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->groupBy(fn($log) => $log->created_at->isToday() ? 'today' : 'earlier')
            ->toArray();
    }

    /**
     * Get chart data for dashboard.
     */
    public function getChartData(): array
    {
        $year = date('Y');

        return [
            'monthly_invoices' => Invoice::whereYear('invoice_date', $year)
                ->groupByRaw('MONTH(invoice_date)')
                ->selectRaw('MONTH(invoice_date) as month, SUM(total) as total')
                ->pluck('total', 'month'),
        ];
    }
}
