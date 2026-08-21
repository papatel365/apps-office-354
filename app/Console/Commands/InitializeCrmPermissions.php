<?php

namespace App\Console\Commands;

use App\Models\CrmUserPermission;
use App\Modules\System\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class InitializeCrmPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:init-permissions {--user= : Specific user ID to initialize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize CRM permissions for users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Initializing CRM User Permissions...');

        // Clear caches
        Cache::flush();
        $this->info('Cache cleared.');

        $userId = $this->option('user');

        if ($userId) {
            // Initialize specific user
            $user = User::find($userId);
            if (!$user) {
                $this->error("User not found: {$userId}");
                return Command::FAILURE;
            }

            $this->initializeUser($user);
            $this->info("Permissions initialized for user: {$user->name}");
        } else {
            // Initialize all users
            $users = User::where('is_active', true)->get();

            $bar = $this->output->createProgressBar($users->count());
            $bar->start();

            foreach ($users as $user) {
                $this->initializeUser($user);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Permissions initialized for {$users->count()} users.");
        }

        return Command::SUCCESS;
    }

    /**
     * Initialize permissions for a single user.
     */
    protected function initializeUser(User $user): void
    {
        if (!$user->company_id) {
            return;
        }

        CrmUserPermission::initializeForUser($user);
    }
}
