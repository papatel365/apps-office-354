<?php

namespace App\Console\Commands;

use App\Models\CrmModulePermission;
use App\Models\SidebarPermission;
use App\Models\User;
use App\Services\SidebarMenuConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSidebarPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:migrate
                            {--dry-run : Show what would be changed without making changes}
                            {--clear-cache : Clear permissions cache after migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate sidebar permissions from old format to new hierarchical format';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting sidebar permissions migration...');
        $this->info('');

        // Show dry run warning
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->info('');
        }

        // Old to new permission key mapping
        $permissionMap = [
            // Standalone items
            'dashboard' => 'sidebar.dashboard',
            'guides' => 'sidebar.guides',
            'page_builder' => 'sidebar.page_builder',
            'marketplace' => 'sidebar.marketplace',

            // Projects section
            'project_tasks' => 'sidebar.projects',
            'projects' => 'sidebar.projects.index',
            'tasks' => 'sidebar.projects.tasks',
            'tasks.calendar' => 'sidebar.projects.calendar',

            // Assets section
            'assets_section' => 'sidebar.assets',
            'assets' => 'sidebar.assets.index',
            'asset_categories' => 'sidebar.assets.categories',

            // HRD section
            'hrd_expert' => 'sidebar.staff',
            'hrd.dashboard' => 'sidebar.staff.dashboard',
            'hrd.employees' => 'sidebar.staff.employees',
            'hrd.attendances' => 'sidebar.staff.attendances',
            'hrd.reports' => 'sidebar.staff.reports',

            // Finance section
            'finance' => 'sidebar.finance',
            'proposals' => 'sidebar.finance.proposals',
            'estimates' => 'sidebar.finance.estimates',
            'invoices' => 'sidebar.finance.invoices',
            'payments' => 'sidebar.finance.payments',
            'credit_notes' => 'sidebar.finance.credit_notes',
            'items' => 'sidebar.finance.items',
            'transactions' => 'sidebar.finance.transactions',
            'contracts' => 'sidebar.finance.contracts',
            'subscriptions' => 'sidebar.finance.subscriptions',
            'clients' => 'sidebar.finance.clients',
            'leads' => 'sidebar.finance.leads',

            // Sales section
            'sales_management' => 'sidebar.sales',
            'sales.dashboard' => 'sidebar.sales.dashboard',
            'sales.leads' => 'sidebar.sales.leads',
            'sales.prospects' => 'sidebar.sales.prospects',
            'sales.deals' => 'sidebar.sales.deals',
            'sales.customers' => 'sidebar.sales.customers',
            'sales.quotations' => 'sidebar.sales.quotations',
            'sales.orders' => 'sidebar.sales.orders',
            'sales.activities' => 'sidebar.sales.activities',

            // Reports section
            'reports_utilities' => 'sidebar.reports',
            'reports' => 'sidebar.reports.projects',
            'audit' => 'sidebar.reports.staff',
            'tools' => 'sidebar.reports.finance',

            // Settings section
            'settings' => 'sidebar.settings',
            'company_settings' => 'sidebar.settings',
        ];

        $totalUpdated = 0;
        $totalUsers = User::count();

        $this->info("Total users in system: {$totalUsers}");
        $this->info('');

        // Process crm_module_permissions table
        $this->info('Processing crm_module_permissions table...');

        $oldPermissions = DB::table('crm_module_permissions')
            ->where('module', 'sidebar')
            ->whereNotNull('permission_key')
            ->get();

        $this->info("Found {$oldPermissions->count()} sidebar permission records");

        $updatedCount = 0;
        foreach ($oldPermissions as $perm) {
            if (isset($permissionMap[$perm->permission_key])) {
                $oldKey = $perm->permission_key;
                $newKey = $permissionMap[$perm->permission_key];

                if ($this->option('dry-run')) {
                    $this->line("  [DRY-RUN] Would update: {$oldKey} -> {$newKey}");
                } else {
                    DB::table('crm_module_permissions')
                        ->where('id', $perm->id)
                        ->update(['permission_key' => $newKey]);
                    $this->line("  Updated: {$oldKey} -> {$newKey}");
                }
                $updatedCount++;
            } elseif (str_starts_with($perm->permission_key, 'sidebar.')) {
                $this->line("  Already in new format: {$perm->permission_key}");
            } else {
                $this->warn("  Unknown permission key: {$perm->permission_key}");
            }
        }

        $totalUpdated += $updatedCount;

        // Process sidebar_permissions table (if exists)
        if (DB::getSchemaBuilder()->hasTable('sidebar_permissions')) {
            $this->info('');
            $this->info('Processing sidebar_permissions table...');

            $oldSidebarPerms = DB::table('sidebar_permissions')
                ->whereNotNull('permission_key')
                ->get();

            $this->info("Found {$oldSidebarPerms->count()} sidebar_permission records");

            foreach ($oldSidebarPerms as $perm) {
                if (isset($permissionMap[$perm->permission_key])) {
                    $oldKey = $perm->permission_key;
                    $newKey = $permissionMap[$perm->permission_key];

                    if ($this->option('dry-run')) {
                        $this->line("  [DRY-RUN] Would update: {$oldKey} -> {$newKey}");
                    } else {
                        DB::table('sidebar_permissions')
                            ->where('id', $perm->id)
                            ->update(['permission_key' => $newKey]);
                        $this->line("  Updated: {$oldKey} -> {$newKey}");
                    }
                    $totalUpdated++;
                } elseif (str_starts_with($perm->permission_key, 'sidebar.')) {
                    $this->line("  Already in new format: {$perm->permission_key}");
                } else {
                    $this->warn("  Unknown permission key: {$perm->permission_key}");
                }
            }
        }

        // Clear cache if requested
        if ($this->option('clear-cache') && !$this->option('dry-run')) {
            $this->info('');
            $this->info('Clearing permissions cache...');

            // Clear SidebarMenuConfig cache
            SidebarMenuConfig::clearCache();
            $this->line('  Cleared SidebarMenuConfig cache');

            // Clear all user permission caches
            $users = User::where('is_active', true)->get();
            foreach ($users as $user) {
                CrmModulePermission::clearCache($user->id);
                SidebarPermission::clearCache($user->id);
            }
            $this->line("  Cleared cache for {$users->count()} users");
        }

        $this->info('');
        $this->info('========================================');
        $this->info('Migration complete!');
        $this->info("Total records updated: {$totalUpdated}");

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No changes were actually made.');
            $this->info('Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }
}
