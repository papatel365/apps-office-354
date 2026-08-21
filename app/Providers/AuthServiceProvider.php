<?php

namespace App\Providers;

use App\Models\Task;
use App\Modules\System\Models\User;
use App\Models\Proposal;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\CreditNote;
use App\Models\Item;
use App\Policies\ProjectTaskPolicy;
use App\Policies\TenantAccessPolicy;
use App\Policies\Sales\ProposalPolicy;
use App\Policies\Sales\EstimatePolicy;
use App\Policies\Sales\InvoicePolicy;
use App\Policies\Sales\PaymentPolicy;
use App\Policies\Sales\CreditNotePolicy;
use App\Policies\Sales\ItemPolicy;
use App\Services\Permission\UserPermissionService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Task policy for project member permission
        Task::class => ProjectTaskPolicy::class,

        // Sales module policies
        Proposal::class => ProposalPolicy::class,
        Estimate::class => EstimatePolicy::class,
        Invoice::class => InvoicePolicy::class,
        Payment::class => PaymentPolicy::class,
        CreditNote::class => CreditNotePolicy::class,
        Item::class => ItemPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Register Gates using UserPermissionService as single source of truth
        $this->registerCrmGates();
    }

    /**
     * Register Gates for CRM authorization using UserPermissionService
     * Uses UserPermissionService as SINGLE SOURCE OF TRUTH for ALL CRM permissions
     */
    protected function registerCrmGates(): void
    {
        // Gate to check if user can access all tenant data (has global view permission)
        Gate::define('access-all-tenant-data', function (User $user) {
            $service = UserPermissionService::forUser($user);
            return $service->isGlobalScope('projects') || $service->isGlobalScope('tasks');
        });

        // Gate to check if user can manage members (has settings permission)
        Gate::define('manage-members', function (User $user) {
            $service = UserPermissionService::forUser($user);
            return $service->can('settings');
        });

        // Gate to check if user can access a specific sidebar module
        Gate::define('access-module', function (User $user, string $module) {
            $service = UserPermissionService::forUser($user);
            return $service->can($module);
        });

        // Gate to check if user can view reports (has reports sidebar permission)
        Gate::define('view-reports', function (User $user) {
            $service = UserPermissionService::forUser($user);
            return $service->can('reports');
        });

        // Gate to check if user can view audit logs (has audit sidebar permission)
        Gate::define('view-audit', function (User $user) {
            $service = UserPermissionService::forUser($user);
            return $service->can('audit');
        });

        // Gate to check if user is a Super Admin (has all permissions)
        Gate::define('is-superadmin', function (User $user) {
            $service = UserPermissionService::forUser($user);
            return $service->isSuperAdmin();
        });

        // Gate to check if user can manage the company (has settings permission)
        Gate::define('manage-company', function (User $user) {
            $service = UserPermissionService::forUser($user);
            return $service->can('settings');
        });

        // Gate to check if user can view company profile (has settings permission)
        Gate::define('view-company', function (User $user) {
            $service = UserPermissionService::forUser($user);
            return $service->can('settings');
        });

        // Gate to check if user can edit company profile (has settings permission)
        Gate::define('edit-company', function (User $user) {
            $service = UserPermissionService::forUser($user);
            return $service->can('settings');
        });

        // Gate to check if user can view all tasks (has global view permission for tasks)
        Gate::define('view-all-tasks', function (User $user) {
            $service = UserPermissionService::forUser($user);
            return $service->isGlobalScope('tasks');
        });

        // Gate to check if user can create tasks in a project
        Gate::define('create-project-task', function (User $user, $project) {
            $service = UserPermissionService::forUser($user);
            // Must have create permission for tasks
            if (!$service->canCreate('tasks')) {
                return false;
            }
            // And must be a project member or have global access
            if ($service->isGlobalScope('projects')) {
                return true;
            }
            return $project && $project->members()->where('user_id', $user->id)->exists();
        });

        // Gate to check if user can approve/reject tasks (has edit permission for tasks)
        Gate::define('approve-task', function (User $user) {
            $service = UserPermissionService::forUser($user);
            return $service->canUpdate('tasks');
        });

        // Gate to check if user can manage projects (has create/edit permission for projects)
        Gate::define('manage-projects', function (User $user) {
            $service = UserPermissionService::forUser($user);
            return $service->canCreate('projects') || $service->canUpdate('projects');
        });

        // Gate to check if user can delete projects (has delete permission for projects)
        Gate::define('delete-projects', function (User $user) {
            $service = UserPermissionService::forUser($user);
            return $service->canDelete('projects');
        });
    }
}
