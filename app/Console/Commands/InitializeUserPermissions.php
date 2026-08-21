<?php

namespace App\Console\Commands;

use App\Models\CrmModulePermission;
use App\Models\User;
use App\Services\SidebarMenuConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitializeUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:init
                            {--force : Force re-initialization even if permissions exist}
                            {--user= : Initialize permissions for a specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize sidebar permissions for users who do not have them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting user permissions initialization...');
        $this->info('');

        // Check for specific user
        $userId = $this->option('user');
        $force = $this->option('force');

        if ($userId) {
            $users = User::where('id', $userId)->get();
            if ($users->isEmpty()) {
                $this->error("User with ID {$userId} not found.");
                return Command::FAILURE;
            }
            $this->info("Initializing permissions for user: {$users->first()->name}");
        } else {
            // Get users who don't have permissions
            $users = User::where('is_active', true)->get();
            $this->info("Found {$users->count()} total users");
        }

        $initialized = 0;
        $skipped = 0;

        foreach ($users as $user) {
            // Skip if user already has permissions (unless force)
            $hasExisting = CrmModulePermission::where('user_id', $user->id)->exists();

            if ($hasExisting && !$force) {
                $this->line("  Skipping {$user->name} (permissions already exist)");
                $skipped++;
                continue;
            }

            // Skip superadmin roles
            if (in_array($user->company_role, ['developer', 'owner', 'director'])) {
                $this->line("  Skipping {$user->name} ({$user->company_role} - has full access)");
                $skipped++;
                continue;
            }

            // Get role
            $role = $user->company_role ?? $user->user_type ?? 'staff';
            $this->line("  Initializing {$user->name} (role: {$role})...");

            // Get default permissions based on role
            $defaultPermissions = SidebarMenuConfig::getDefaultPermissionsByRole($role);

            if ($force && $hasExisting) {
                // Delete existing permissions
                CrmModulePermission::where('user_id', $user->id)->delete();
                $this->line("    Deleted existing permissions");
            }

            // Insert new permissions
            $now = now();
            $records = [];
            foreach ($defaultPermissions as $perm) {
                if (!str_starts_with($perm, 'sidebar.')) {
                    continue;
                }

                $records[] = [
                    'company_id' => $user->company_id,
                    'user_id' => $user->id,
                    'module' => 'sidebar',
                    'permission_key' => $perm,
                    'allowed' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($records)) {
                CrmModulePermission::insert($records);
                $this->line("    Added " . count($records) . " permissions");
            }

            $initialized++;
        }

        $this->info('');
        $this->info('========================================');
        $this->info('Initialization complete!');
        $this->info("Users initialized: {$initialized}");
        $this->info("Users skipped: {$skipped}");

        return Command::SUCCESS;
    }
}
