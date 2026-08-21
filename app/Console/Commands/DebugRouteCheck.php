<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Route;
use Illuminate\Console\Command;

class DebugRouteCheck extends Command
{
    protected $signature = 'route:debug-check';
    protected $description = 'Check if specific routes exist';

    public function handle()
    {
        $this->info('=== ROUTE EXISTENCE CHECK ===');

        $routesToCheck = [
            'pengaturan.backup.index',
            'pengaturan.hak_akses.index',
            'dashboard',
        ];

        foreach ($routesToCheck as $routeName) {
            $exists = Route::has($routeName);
            $status = $exists ? '✅ EXISTS' : '❌ NOT FOUND';
            $this->info("{$routeName}: {$status}");
        }

        return Command::SUCCESS;
    }
}
