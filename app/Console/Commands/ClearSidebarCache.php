<?php

namespace App\Console\Commands;

use App\Services\SidebarMenuConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearSidebarCache extends Command
{
    protected $signature = 'sidebar:clear-cache';
    protected $description = 'Clear sidebar menu config cache';

    public function handle()
    {
        $this->info('Clearing sidebar menu config cache...');

        // Clear SidebarMenuConfig cache
        SidebarMenuConfig::clearCache();

        // Also clear manually if exists
        Cache::forget('sidebar_menu_config_v7');
        Cache::forget('sidebar_permission_keys_v6');

        $this->info('Sidebar cache cleared successfully!');

        return Command::SUCCESS;
    }
}
