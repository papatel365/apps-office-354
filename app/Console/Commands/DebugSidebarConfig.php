<?php

namespace App\Console\Commands;

use App\Services\SidebarMenuConfig;
use Illuminate\Console\Command;

class DebugSidebarConfig extends Command
{
    protected $signature = 'sidebar:debug';
    protected $description = 'Debug sidebar menu configuration';

    public function handle()
    {
        $this->info('=== SIDEBAR MENU CONFIG DEBUG ===');
        $this->info('');

        // Get menu config
        $menuConfig = SidebarMenuConfig::getMenuConfig();
        $this->info('Total menu items: ' . count($menuConfig));
        $this->info('');

        // Show all menus
        $this->info('=== MENU STRUCTURE ===');
        foreach ($menuConfig as $menu) {
            $type = $menu['type'] ?? 'unknown';
            $key = $menu['key'] ?? '';
            $label = $menu['label'] ?? '';
            $isVisible = $menu['is_visible'] ?? true;
            $childrenCount = count($menu['children'] ?? []);

            if ($type === 'item') {
                $route = $menu['route'] ?? 'N/A';
                $perm = $menu['permission_key'] ?? 'N/A';
                $this->info("[ITEM] {$key} | {$label} | route: {$route} | perm: {$perm} | visible: " . ($isVisible ? 'YES' : 'NO'));
            } else {
                $this->info("[GROUP] {$key} | {$label} | children: {$childrenCount} | visible: " . ($isVisible ? 'YES' : 'NO'));

                foreach ($menu['children'] ?? [] as $child) {
                    $childKey = $child['key'] ?? '';
                    $childLabel = $child['label'] ?? '';
                    $childRoute = $child['route'] ?? 'N/A';
                    $childPerm = $child['permission_key'] ?? 'N/A';
                    $childVisible = $child['is_visible'] ?? true;
                    $this->info("    └─ [{$childKey}] {$childLabel} | route: {$childRoute} | perm: {$childPerm} | visible: " . ($childVisible ? 'YES' : 'NO'));
                }
            }
        }

        $this->info('');
        $this->info('=== PENGATURAN GROUP ===');
        foreach ($menuConfig as $menu) {
            if ($menu['key'] === 'atur_crm') {
                foreach ($menu['children'] ?? [] as $child) {
                    $this->info("- {$child['label']} ({$child['key']}) - route: {$child['route']}");
                }
            }
        }

        return Command::SUCCESS;
    }
}
