<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Module;
use App\Models\Plan;
use App\Models\SubscriptionModule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AssignAllModulesToCompany extends Command
{
    protected $signature = 'company:assign-all-modules {company_id : The ID of the company}';
    protected $description = 'Assign all premium modules to a company';

    public function handle(): int
    {
        $companyId = $this->argument('company_id');
        $company = Company::find($companyId);

        if (!$company) {
            $this->error("Company with ID {$companyId} not found!");
            return 1;
        }

        $this->info("Assigning all premium modules to: {$company->name}");

        // Get all premium (non-core) modules
        $premiumModules = Module::where('is_core', false)->where('is_active', true)->get();

        if ($premiumModules->isEmpty()) {
            $this->warn("No premium modules found!");
            return 1;
        }

        // Create or get a subscription (use first available plan)
        $firstModule = $premiumModules->first();
        $defaultPlan = $firstModule ? $firstModule->plans()->first() : null;

        $subscription = CompanySubscription::updateOrCreate(
            [
                'company_id' => $company->id,
                'billing_cycle' => 'monthly',
            ],
            [
                'plan_id' => $defaultPlan?->id,
                'subscription_code' => 'CUSTOM-' . strtoupper($company->slug) . '-' . date('Ymd'),
                'price' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addYear(),
                'next_billing_date' => Carbon::now()->addYear(),
                'status' => CompanySubscription::STATUS_ACTIVE,
                'auto_renew' => true,
                'notes' => 'All modules assigned via command',
            ]
        );

        $this->info("Subscription created/updated: {$subscription->subscription_code}");

        // Add all premium modules to subscription
        $count = 0;
        foreach ($premiumModules as $module) {
            // Get default plan
            $plan = $module->plans()->first();

            // Create subscription module
            $subModule = SubscriptionModule::updateOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'module_id' => $module->id,
                ],
                [
                    'status' => SubscriptionModule::STATUS_ACTIVE,
                    'activated_at' => Carbon::now(),
                    'expires_at' => Carbon::now()->addYear(),
                ]
            );

            $this->line("  ✓ Added: {$module->name} ({$module->code})");
            $count++;
        }

        $this->newLine();
        $this->info("Successfully assigned {$count} premium modules to {$company->name}!");
        $this->info("Company can now access all premium features.");

        return 0;
    }
}
