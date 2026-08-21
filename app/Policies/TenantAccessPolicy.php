<?php

namespace App\Policies;

use App\Modules\System\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * TenantAccessPolicy
 *
 * Centralized policy for checking user access to tenant-scoped resources.
 * Directors have full read access to all tenant data within their company.
 *
 * Access hierarchy:
 * - Developer: Full system access across all tenants
 * - Pusat Admin: Full access across all tenants
 * - Director: Full read access to ALL data within their company (tenant)
 * - Owner: Access to all free modules + subscribed premium modules
 * - Admin/Manager/Staff: Limited by role permissions
 */
class TenantAccessPolicy
{
    use HandlesAuthorization;

    /**
     * List of models that Directors can always view (read-only)
     * These are the core CRM modules that Directors should always have access to
     */
    protected static array $viewableModels = [
        // Company
        'Company',

        // Core CRM
        'Client',
        'Contact',
        'Lead',
        'Proposal',
        'Estimate',
        'Invoice',
        'Payment',
        'CreditNote',
        'Item',

        // Projects & Tasks
        'Project',
        'Task',
        'TaskComment',
        'TaskAssignee',

        // Contracts & Subscriptions
        'Contract',
        'Subscription',
        'SubscriptionItem',

        // Finance
        'Transaction',
        'EstimateRequest',

        // Assets
        'Asset',
        'AssetCategory',
        'AssetAllocation',
        'AssetCheckinCheckout',
        'AssetMaintenance',
        'AssetReservation',

        // Reports & Audit
        'AuditLog',

        // Knowledge Base
        'KnowledgeBase',
        'KnowledgeBaseCategory',

        // Users
        'User',

        // HRD Models
        'EmployeeProfile',
        'Salary',
        'Attendance',
        'Leave',
        'Overtime',
        'Shift',
        'ShiftSchedule',
        'Placement',

        // Finance Expert
        'FinanceAccount',
        'FinanceJournal',
        'FinanceBudget',
        'FinanceBank',
        'FinanceTax',
        'FinanceVendor',
        'FinanceVendorBill',
        'FinanceTransfer',
        'FinanceAlert',
        'FinanceForecast',

        // Sales Pipeline
        'SalesLead',
        'SalesProspect',
        'SalesDeal',
        'SalesQuotation',
        'SalesOrder',
        'SalesCustomer',
        'SalesActivity',
        'SalesFollowup',
        'SalesCommission',
        'SalesTarget',
    ];

    /**
     * List of models that Directors can also create/update/delete
     */
    protected static array $manageableModels = [
        // Directors have full CRUD on most models
        // but this is controlled by other policies
    ];

    /**
     * Determine whether the user can view any records of a given model type.
     *
     * @param User $user
     * @param string $model The model class name or short name
     * @return bool
     */
    public function viewAny(User $user, string $model): bool
    {
        // Developer and Pusat Admin have full access
        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        // Director has full view access to all tenant data
        if ($user->is_director) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param User $user
     * @param mixed $model The model instance
     * @return bool
     */
    public function view(User $user, mixed $model): bool
    {
        // Developer and Pusat Admin have full access
        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        // Director has full view access to all tenant data
        if ($user->is_director) {
            return $this->isInSameTenant($user, $model);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param User $user
     * @param string|null $modelClass Optional model class to check
     * @return bool
     */
    public function create(User $user, ?string $modelClass = null): bool
    {
        // Developer and Pusat Admin have full access
        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        // Directors can create within their tenant
        if ($user->is_director) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param mixed $model
     * @return bool
     */
    public function update(User $user, mixed $model): bool
    {
        // Developer and Pusat Admin have full access
        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        // Directors can update within their tenant
        if ($user->is_director) {
            return $this->isInSameTenant($user, $model);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param mixed $model
     * @return bool
     */
    public function delete(User $user, mixed $model): bool
    {
        // Developer and Pusat Admin have full access
        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        // Directors can delete within their tenant
        if ($user->is_director) {
            return $this->isInSameTenant($user, $model);
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @param mixed $model
     * @return bool
     */
    public function restore(User $user, mixed $model): bool
    {
        // Developer and Pusat Admin have full access
        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        // Directors can restore within their tenant
        if ($user->is_director) {
            return $this->isInSameTenant($user, $model);
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param mixed $model
     * @return bool
     */
    public function forceDelete(User $user, mixed $model): bool
    {
        // Developer and Pusat Admin have full access
        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        // Directors can force delete within their tenant
        if ($user->is_director) {
            return $this->isInSameTenant($user, $model);
        }

        return false;
    }

    /**
     * Check if a model belongs to the same tenant as the user
     *
     * @param User $user
     * @param mixed $model
     * @return bool
     */
    protected function isInSameTenant(User $user, mixed $model): bool
    {
        // Must be authenticated and have a company
        if (!$user->company_id) {
            return false;
        }

        // Check if model has company_id
        if (isset($model->company_id)) {
            return $model->company_id === $user->company_id;
        }

        // Check if model has tenant_id
        if (isset($model->tenant_id)) {
            return $model->tenant_id === $user->tenant_id;
        }

        // If model has a company relationship
        if (method_exists($model, 'company') && $model->company) {
            return $model->company_id === $user->company_id;
        }

        // If model has a tenant relationship
        if (method_exists($model, 'tenant') && $model->tenant) {
            return $model->tenant_id === $user->tenant_id;
        }

        // If model is a User, check directly
        if ($model instanceof User) {
            return $model->company_id === $user->company_id;
        }

        // Default: allow access (model doesn't have tenant scoping)
        return true;
    }

    /**
     * Check if a model class is viewable by Directors
     *
     * @param string $modelClass
     * @return bool
     */
    public static function isViewableModel(string $modelClass): bool
    {
        $shortName = class_basename($modelClass);
        return in_array($shortName, self::$viewableModels);
    }

    /**
     * Get all viewable model names
     *
     * @return array
     */
    public static function getViewableModels(): array
    {
        return self::$viewableModels;
    }

    /**
     * Check if user can access all data of a specific model type
     * This is used for queries - Directors can see all records in their tenant
     *
     * @param User $user
     * @param string $modelClass
     * @return bool
     */
    public function canAccessAllOfType(User $user, string $modelClass): bool
    {
        // Developer and Pusat Admin have full access
        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        // Director has full access to all tenant data
        if ($user->is_director) {
            return true;
        }

        return false;
    }
}
