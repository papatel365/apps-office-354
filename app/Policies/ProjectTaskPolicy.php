<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Modules\System\Models\User;
use App\Services\Permission\UserPermissionService;

class ProjectTaskPolicy
{
    /**
     * Determine whether the user can view the task.
     *
     * RULES:
     * - Superadmin → can view ALL tasks in their company
     * - Admin/Director/Pusat Admin → can view ALL tasks in their company
     * - Global scope (scope_global = true) → can view ALL tasks in their company
     * - Own scope (scope_own = true, scope_global = false) → can view tasks where user is:
     *   - CREATOR (created_by)
     *   - OR ASSIGNEE (in task_assignees table)
     *   - OR FOLLOWER (in task_followers table)
     * - Neither scope → NO ACCESS
     *
     * NOTE: Tenant isolation is handled separately in the controller/middleware.
     * This policy only handles role-based visibility within the user's tenant.
     */
    public function view($user, Task $task): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        // Superadmin sees all
        if ($permService->isSuperAdmin()) {
            return true;
        }

        // Admin/Director can view all tasks within their tenant
        if (Task::isUserDirectorOrAdmin($user)) {
            return true;
        }

        // Global scope = all tasks in company
        if ($permService->isGlobalScope('tasks')) {
            return true;
        }

        // Own scope = tasks created by user OR assigned to user OR following user
        if ($permService->isOwnScope('tasks')) {
            return $task->created_by === $user->id
                || $task->assignees()->where('user_id', $user->id)->exists()
                || $task->followers()->where('user_id', $user->id)->exists();
        }

        // No scope = no access
        return false;
    }

    public function viewAny($user): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        // User can access list if they have ANY scope (global or own)
        return $permService->isSuperAdmin()
            || Task::isUserDirectorOrAdmin($user)
            || $permService->isGlobalScope('tasks')
            || $permService->isOwnScope('tasks');
    }

    public function create($user, $project = null): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        // Check if user can create tasks
        if (!$permService->canCreate('tasks')) {
            return false;
        }

        if (!$project) {
            return true;
        }

        // For project-specific task creation, check project membership or global scope
        if ($permService->isGlobalScope('projects')) {
            return true;
        }

        return $project->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Update permission:
     * - Admin/Director/Pusat Admin/Superadmin → can update any task
     * - Global scope → can update any task
     * - Own scope → can only update tasks they created OR are assigned to
     */
    public function update($user, Task $task): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        // Superadmin/Admin/Director can update any task
        if ($permService->isSuperAdmin() || Task::isUserDirectorOrAdmin($user)) {
            return true;
        }

        // Global scope = all tasks
        if ($permService->isGlobalScope('tasks')) {
            return true;
        }

        // Own scope = only tasks they created OR are assigned to
        if ($permService->isOwnScope('tasks')) {
            return $task->created_by === $user->id
                || $task->assignees()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Delete permission:
     * - Superadmin, Admin, Director, and Pusat Admin ONLY
     */
    public function delete($user, Task $task): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        return $permService->isSuperAdmin()
            || $user->is_director
            || $user->is_pusat_admin
            || $user->is_company_admin;
    }

    /**
     * Restore permission:
     * - Admin, Director, and Pusat Admin ONLY
     */
    public function restore($user, Task $task): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        return $permService->isSuperAdmin()
            || $user->is_director
            || $user->is_pusat_admin
            || $user->is_company_admin;
    }

    /**
     * Force delete permission:
     * - Admin, Director, and Pusat Admin ONLY
     */
    public function forceDelete($user, Task $task): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        return $permService->isSuperAdmin()
            || $user->is_director
            || $user->is_pusat_admin
            || $user->is_company_admin;
    }

    /**
     * Approve permission:
     * - Admin, Director, and Pusat Admin ONLY
     */
    public function approve($user, Task $task): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        return $permService->isSuperAdmin()
            || $user->is_director
            || $user->is_pusat_admin
            || $user->is_company_admin;
    }

    /**
     * Reject permission:
     * - Admin, Director, and Pusat Admin ONLY
     */
    public function reject($user, Task $task): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        return $permService->isSuperAdmin()
            || $user->is_director
            || $user->is_pusat_admin
            || $user->is_company_admin;
    }

    /**
     * Change status permission:
     * - Admin, Director, and Pusat Admin → can change any status
     * - Global scope → can change any status
     * - Own scope → can only change if they created OR are assigned (NOT follower - followers are read-only)
     */
    public function changeStatus($user, Task $task): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        // Superadmin/Admin/Director can change any status
        if ($permService->isSuperAdmin() || Task::isUserDirectorOrAdmin($user)) {
            return true;
        }

        // Global scope = all tasks
        if ($permService->isGlobalScope('tasks')) {
            return true;
        }

        // Own scope = only tasks they created OR are assigned to (NOT followers - followers are read-only)
        if ($permService->isOwnScope('tasks')) {
            return $task->created_by === $user->id
                || $task->assignees()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Upload photo permission:
     * - User must be able to update the task
     * - Task must not be completed or cancelled
     */
    public function uploadPhoto($user, Task $task): bool
    {
        if (!$this->update($user, $task)) {
            return false;
        }

        return !in_array($task->status, [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED]);
    }

    /**
     * View task in project (project-level visibility):
     * - Admin, Director, and Pusat Admin → can see all tasks in project
     * - Global scope → can see all tasks in project
     * - Own scope → can only see if they are CREATOR OR ASSIGNEE OR FOLLOWER
     */
    public function viewInProject($user, Task $task, Project $project): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        // Superadmin/Admin/Director can see all
        if ($permService->isSuperAdmin() || Task::isUserDirectorOrAdmin($user)) {
            return true;
        }

        // Global scope = all tasks
        if ($permService->isGlobalScope('tasks')) {
            return true;
        }

        // Own scope = only tasks they created OR are assigned OR are following
        if ($permService->isOwnScope('tasks')) {
            return $task->created_by === $user->id
                || $task->assignees()->where('user_id', $user->id)->exists()
                || $task->followers()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * View milestone tasks:
     * - Admin, Director, and Pusat Admin → can see all
     * - Global scope → can see all
     * - Own scope → no direct access (must go through task assignees)
     */
    public function viewMilestoneTasks($user, ProjectMilestone $milestone): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        return $permService->isSuperAdmin()
            || Task::isUserDirectorOrAdmin($user)
            || $permService->isGlobalScope('tasks');
    }

    /**
     * Move milestone permission - Admin, Director, and Pusat Admin ONLY
     */
    public function moveMilestone($user, Task $task): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        return $permService->isSuperAdmin()
            || $user->is_director
            || $user->is_pusat_admin
            || $user->is_company_admin;
    }

    /**
     * Bulk move permission - Admin, Director, and Pusat Admin ONLY
     */
    public function bulkMoveMilestone($user): bool
    {
        if (!$user) {
            return false;
        }

        $permService = UserPermissionService::forUser($user);

        return $permService->isSuperAdmin()
            || $user->is_director
            || $user->is_pusat_admin
            || $user->is_company_admin;
    }
}
