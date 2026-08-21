<?php

namespace App\Policies;

use App\Models\Backup;
use App\Models\User;

class BackupPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Developer can access all
        if ($user->is_developer) {
            return true;
        }

        // Owner, Director, Admin can access
        if ($user->is_owner || $user->is_director || $user->is_company_admin) {
            return true;
        }

        // Check specific permission
        return $user->hasSidebarPermission('backup');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Backup $backup): bool
    {
        // Must be same company
        if ($backup->company_id !== $user->company_id) {
            return false;
        }

        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Developer can create
        if ($user->is_developer) {
            return true;
        }

        // Owner, Director, Admin can create
        if ($user->is_owner || $user->is_director || $user->is_company_admin) {
            return true;
        }

        // Check specific permission
        return $user->hasSidebarPermission('backup');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Backup $backup): bool
    {
        // Must be same company
        if ($backup->company_id !== $user->company_id) {
            return false;
        }

        return $this->create($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Backup $backup): bool
    {
        // Must be same company
        if ($backup->company_id !== $user->company_id) {
            return false;
        }

        return $this->create($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Backup $backup): bool
    {
        // Must be same company
        if ($backup->company_id !== $user->company_id) {
            return false;
        }

        // Developer can always restore
        if ($user->is_developer) {
            return true;
        }

        // Only Owner can restore
        return $user->is_owner;
    }

    /**
     * Determine whether the user can download the model.
     */
    public function download(User $user, Backup $backup): bool
    {
        return $this->view($user, $backup);
    }
}
