<?php

namespace App\Traits;

use App\Models\CrmUserPermissionV2;
use App\Modules\System\Models\User;
use App\Services\Permission\UserPermissionService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait CrmPermissionQueries
 *
 * Provides methods for applying CRM permission-based queries.
 * Uses UserPermissionService as SINGLE SOURCE OF TRUTH.
 *
 * DATA FILTERING RULES:
 * - scope_global = true  → All data in company
 * - scope_own = true AND scope_global = false → Own data only
 * - scope_own = false AND scope_global = false → No data (access denied)
 *
 * NOTE: can_view does NOT affect data filtering - it only affects sidebar visibility.
 */
trait CrmPermissionQueries
{
    /**
     * Get the permission service for current user
     */
    protected function permissionService(): UserPermissionService
    {
        $user = $this->getUser();
        if (!$user) {
            // Return a dummy service that denies everything
            return new UserPermissionService(null);
        }
        return UserPermissionService::forUser($user);
    }

    /**
     * Get current authenticated user
     */
    protected function getUser(): ?User
    {
        return auth()->user();
    }

    /**
     * Check if current user is superadmin
     */
    protected function isSuperadmin(): bool
    {
        return $this->permissionService()->isSuperAdmin();
    }

    /**
     * Apply permission filter to Project query.
     *
     * Logic:
     * - Superadmin sees ALL projects in their company
     * - scope_global = true → ALL projects in their company
     * - scope_own = true (with scope_global = false) → projects they CREATED OR are MEMBER of
     * - Neither scope → NO ACCESS (handled by controller check)
     */
    protected function applyProjectPermissionFilter(Builder $query): Builder
    {
        $user = $this->getUser();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        $permService = $this->permissionService();

        // Superadmin sees all
        if ($permService->isSuperAdmin()) {
            return $query->where('company_id', $user->company_id);
        }

        // Check if user has ANY scope
        $hasGlobalScope = $permService->isGlobalScope('projects');
        $hasOwnScope = $permService->isOwnScope('projects');

        // Neither scope → no access (handled by controller's page access check)
        if (!$hasGlobalScope && !$hasOwnScope) {
            // User doesn't have scope permissions - they should see no projects
            // This case is handled at controller level (403 page access)
            return $query->whereRaw('1 = 0');
        }

        // scope_global = true → all projects in company
        if ($hasGlobalScope) {
            return $query->where('company_id', $user->company_id);
        }

        // scope_own = true (and scope_global = false) → projects they created OR are member of
        if ($hasOwnScope) {
            return $query->where('company_id', $user->company_id)
                ->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhereHas('members', fn($q2) => $q2->where('user_id', $user->id));
                });
        }

        // Fallback: no access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply permission filter to Task query.
     *
     * Logic:
     * - Superadmin sees ALL tasks in their company
     * - scope_global = true → ALL tasks in their company
     * - scope_own = true (with scope_global = false) → tasks they CREATED OR are ASSIGNED to OR are FOLLOWING
     * - Neither scope → NO ACCESS (handled by controller check)
     */
    protected function applyTaskPermissionFilter(Builder $query): Builder
    {
        $user = $this->getUser();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        $permService = $this->permissionService();

        // Superadmin sees all
        if ($permService->isSuperAdmin()) {
            return $query->where('company_id', $user->company_id);
        }

        // Check if user has ANY scope
        $hasGlobalScope = $permService->isGlobalScope('tasks');
        $hasOwnScope = $permService->isOwnScope('tasks');

        // Neither scope → no access (handled by controller's page access check)
        if (!$hasGlobalScope && !$hasOwnScope) {
            // User doesn't have scope permissions - they should see no tasks
            // This case is handled at controller level (403 page access)
            return $query->whereRaw('1 = 0');
        }

        // scope_global = true → all tasks in company
        if ($hasGlobalScope) {
            return $query->where('company_id', $user->company_id);
        }

        // scope_own = true (and scope_global = false) → tasks they created OR assigned to OR following
        if ($hasOwnScope) {
            return $query->where('company_id', $user->company_id)
                ->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhereHas('assignees', fn($q2) => $q2->where('user_id', $user->id))
                      ->orWhereHas('followers', fn($q3) => $q3->where('user_id', $user->id));
                });
        }

        // Fallback: no access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply permission filter to Attendance query.
     *
     * Logic:
     * - Superadmin sees ALL attendance in their company
     * - scope_global = true → ALL attendance in their company
     * - scope_own = true (with scope_global = false) → their own attendance only
     * - Neither scope → NO ACCESS
     */
    protected function applyAttendancePermissionFilter(Builder $query): Builder
    {
        $user = $this->getUser();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        $permService = $this->permissionService();

        // Superadmin sees all
        if ($permService->isSuperAdmin()) {
            return $query->where('company_id', $user->company_id);
        }

        // Check if user has ANY scope
        $hasGlobalScope = $permService->isGlobalScope('attendances');
        $hasOwnScope = $permService->isOwnScope('attendances');

        // Neither scope → no access
        if (!$hasGlobalScope && !$hasOwnScope) {
            return $query->whereRaw('1 = 0');
        }

        // scope_global = true → all attendance in company
        if ($hasGlobalScope) {
            return $query->where('company_id', $user->company_id);
        }

        // scope_own = true (and scope_global = false) → own attendance only
        if ($hasOwnScope) {
            return $query->where('company_id', $user->company_id)
                ->where('employee_id', $user->employee_id ?? 0);
        }

        // Fallback: no access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Check if user can VIEW module (sidebar access, NOT data visibility)
     */
    protected function canView(string $module): bool
    {
        return $this->permissionService()->canView($module);
    }

    /**
     * Check if user can CREATE in module.
     */
    protected function canCreate(string $module): bool
    {
        return $this->permissionService()->canCreate($module);
    }

    /**
     * Check if user can UPDATE in module.
     */
    protected function canUpdate(string $module): bool
    {
        return $this->permissionService()->canUpdate($module);
    }

    /**
     * Check if user can DELETE in module.
     */
    protected function canDelete(string $module): bool
    {
        return $this->permissionService()->canDelete($module);
    }

    /**
     * Check if user has GLOBAL scope.
     */
    protected function isGlobalScope(string $module): bool
    {
        return $this->permissionService()->isGlobalScope($module);
    }

    /**
     * Check if user has OWN scope.
     */
    protected function isOwnScope(string $module): bool
    {
        return $this->permissionService()->isOwnScope($module);
    }

    /**
     * Check if user can VIEW any data (scope_own OR scope_global).
     * This is used for page access control.
     */
    protected function canViewAnyData(string $module): bool
    {
        return $this->isGlobalScope($module) || $this->isOwnScope($module);
    }
}
