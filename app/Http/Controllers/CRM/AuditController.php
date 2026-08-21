<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Contract;
use App\Models\Proposal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class AuditController extends Controller
{
    /**
     * Display audit index page with filters.
     */
    public function index(Request $request): View
    {
        $auditTypes = [
            'sales_clients' => 'Klien Penjualan',
            'subscriptions' => 'Langganan Klien',
            'invoices' => 'Faktur',
            'payments' => 'Pembayaran',
            'contracts' => 'Kontrak',
            'proposals' => 'Proposal',
            'transactions' => 'Transaksi Keuangan',
            'expenses' => 'Pengeluaran',
        ];

        $selectedTypes = $request->input('audit_types', []);

        return view('crm.audit.index', compact('auditTypes', 'selectedTypes'));
    }

    /**
     * Generate audit report based on selected types.
     */
    public function report(Request $request): View
    {
        $selectedTypes = $request->input('audit_types', []);

        $report = [];

        // Sales Clients
        if (in_array('sales_clients', $selectedTypes)) {
            $report['sales_clients'] = $this->getSalesClientsData();
        }

        // Subscriptions
        if (in_array('subscriptions', $selectedTypes)) {
            $report['subscriptions'] = $this->getSubscriptionsData();
        }

        // Invoices
        if (in_array('invoices', $selectedTypes)) {
            $report['invoices'] = $this->getInvoicesData();
        }

        // Payments
        if (in_array('payments', $selectedTypes)) {
            $report['payments'] = $this->getPaymentsData();
        }

        // Contracts
        if (in_array('contracts', $selectedTypes)) {
            $report['contracts'] = $this->getContractsData();
        }

        // Proposals
        if (in_array('proposals', $selectedTypes)) {
            $report['proposals'] = $this->getProposalsData();
        }

        // Transactions
        if (in_array('transactions', $selectedTypes)) {
            $report['transactions'] = $this->getTransactionsData();
        }

        // Expenses
        if (in_array('expenses', $selectedTypes)) {
            $report['expenses'] = $this->getExpensesData();
        }

        return view('crm.audit.report', compact('report', 'selectedTypes'));
    }

    /**
     * Get sales clients data
     */
    protected function getSalesClientsData(): array
    {
        $clients = Client::with(['invoices', 'subscriptions'])
            ->get();

        return [
            'total' => $clients->count(),
            'active' => $clients->filter(fn($c) => $c->is_active)->count(),
            'inactive' => $clients->filter(fn($c) => !$c->is_active)->count(),
            'with_invoices' => $clients->filter(fn($c) => $c->invoices->count() > 0)->count(),
            'with_subscriptions' => $clients->filter(fn($c) => $c->subscriptions->count() > 0)->count(),
            'total_invoices' => $clients->sum(fn($c) => $c->invoices->count()),
            'total_paid' => $clients->sum(fn($c) => $c->invoices->where('status', 'paid')->count()),
            'total_outstanding' => $clients->sum(fn($c) => $c->invoices->where('status', '!=', 'paid')->count()),
            'recent' => $clients->sortByDesc('created_at')->take(10),
        ];
    }

    /**
     * Get subscriptions data
     */
    protected function getSubscriptionsData(): array
    {
        $subscriptions = Subscription::with('client')->get();

        return [
            'total' => $subscriptions->count(),
            'active' => $subscriptions->filter(fn($s) => $s->status === 'active')->count(),
            'paused' => $subscriptions->filter(fn($s) => $s->status === 'paused')->count(),
            'cancelled' => $subscriptions->filter(fn($s) => $s->status === 'cancelled')->count(),
            'expired' => $subscriptions->filter(fn($s) => $s->isExpired())->count(),
            'total_value' => $subscriptions->sum('amount'),
            'active_value' => $subscriptions->where('status', 'active')->sum('amount'),
            'recent' => $subscriptions->sortByDesc('created_at')->take(10),
        ];
    }

    /**
     * Get invoices data
     */
    protected function getInvoicesData(): array
    {
        $invoices = Invoice::with('client')->get();

        return [
            'total' => $invoices->count(),
            'draft' => $invoices->filter(fn($i) => $i->status === 'draft')->count(),
            'sent' => $invoices->filter(fn($i) => $i->status === 'sent')->count(),
            'paid' => $invoices->filter(fn($i) => $i->status === 'paid')->count(),
            'overdue' => $invoices->filter(fn($i) => $i->status === 'overdue')->count(),
            'cancelled' => $invoices->filter(fn($i) => $i->status === 'cancelled')->count(),
            'total_amount' => $invoices->sum('total'),
            'paid_amount' => $invoices->sum('paid_amount'),
            'outstanding_amount' => $invoices->sum(fn($i) => $i->total - $i->paid_amount),
            'recent' => $invoices->sortByDesc('created_at')->take(10),
        ];
    }

    /**
     * Get payments data
     */
    protected function getPaymentsData(): array
    {
        $payments = Payment::with(['invoice.client', 'user'])->get();

        return [
            'total' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'by_method' => $payments->groupBy('payment_method')->map->count(),
            'by_month' => $payments->groupBy(fn($p) => $p->payment_date->format('Y-m'))->map->sum('amount'),
            'recent' => $payments->sortByDesc('payment_date')->take(10),
        ];
    }

    /**
     * Get contracts data
     */
    protected function getContractsData(): array
    {
        $contracts = Contract::with('client')->get();

        return [
            'total' => $contracts->count(),
            'draft' => $contracts->filter(fn($c) => $c->status === 'draft')->count(),
            'active' => $contracts->filter(fn($c) => $c->status === 'active')->count(),
            'expired' => $contracts->filter(fn($c) => $c->status === 'expired')->count(),
            'terminated' => $contracts->filter(fn($c) => $c->status === 'terminated')->count(),
            'total_value' => $contracts->sum('value'),
            'expiring_soon' => $contracts->filter(fn($c) => $c->days_until_expiry !== null && $c->days_until_expiry <= 30 && $c->days_until_expiry > 0)->count(),
            'recent' => $contracts->sortByDesc('created_at')->take(10),
        ];
    }

    /**
     * Get proposals data
     */
    protected function getProposalsData(): array
    {
        $proposals = Proposal::with('client')->get();

        return [
            'total' => $proposals->count(),
            'draft' => $proposals->filter(fn($p) => $p->status === 'draft')->count(),
            'sent' => $proposals->filter(fn($p) => $p->status === 'sent')->count(),
            'accepted' => $proposals->filter(fn($p) => $p->status === 'accepted')->count(),
            'rejected' => $proposals->filter(fn($p) => $p->status === 'rejected')->count(),
            'total_value' => $proposals->sum('total'),
            'accepted_value' => $proposals->where('status', 'accepted')->sum('total'),
            'recent' => $proposals->sortByDesc('created_at')->take(10),
        ];
    }

    /**
     * Get transactions data
     */
    protected function getTransactionsData(): array
    {
        $transactions = Transaction::all();

        return [
            'total' => $transactions->count(),
            'total_income' => $transactions->filter(fn($t) => $t->type === 'income')->sum('amount'),
            'total_expense' => $transactions->filter(fn($t) => $t->type === 'expense')->sum('amount'),
            'balance' => $transactions->where('type', 'income')->sum('amount') - $transactions->where('type', 'expense')->sum('amount'),
            'income_by_category' => $transactions->where('type', 'income')->groupBy('category')->map->sum('amount'),
            'expense_by_category' => $transactions->where('type', 'expense')->groupBy('category')->map->sum('amount'),
            'recent' => $transactions->sortByDesc('transaction_date')->take(10),
        ];
    }

    /**
     * Get expenses data
     */
    protected function getExpensesData(): array
    {
        $expenses = \App\Models\Expense::with('category')->get();

        return [
            'total' => $expenses->count(),
            'approved' => $expenses->filter(fn($e) => $e->status === 'approved')->count(),
            'pending' => $expenses->filter(fn($e) => $e->status === 'pending')->count(),
            'rejected' => $expenses->filter(fn($e) => $e->status === 'rejected')->count(),
            'total_amount' => $expenses->sum('amount'),
            'approved_amount' => $expenses->where('status', 'approved')->sum('amount'),
            'by_category' => $expenses->groupBy(fn($e) => $e->category->name ?? 'Uncategorized')->map->sum('amount'),
            'recent' => $expenses->sortByDesc('expense_date')->take(10),
        ];
    }
}
