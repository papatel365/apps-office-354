<?php

namespace App\Helpers;

use App\Modules\System\Models\User;
use App\Services\Permission\UserPermissionService;

/**
 * Sidebar Permission Helper
 *
 * @deprecated Use UserPermissionService directly
 */
class SidebarHelper
{
    /**
     * Check if current user can access a sidebar item.
     */
    public static function canAccess(string $permission): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return self::userCanAccess($user, $permission);
    }

    /**
     * Check if a specific user can access a sidebar item.
     */
    public static function userCanAccess($user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        $module = str_replace('sidebar.', '', $permission);
        return UserPermissionService::forUser($user)->can($module);
    }

    /**
     * Check if user is developer.
     */
    public static function isDeveloper($user): bool
    {
        if (!$user) {
            return false;
        }

        if (isset($user->is_developer) && $user->is_developer) {
            return true;
        }

        $role = $user->company_role ?? ($user->user_type ?? null);
        return $role === 'developer';
    }

    /**
     * Check if user is Superadmin.
     */
    public static function isSuperadmin($user): bool
    {
        if (!$user) {
            return false;
        }

        if (self::isDeveloper($user)) {
            return true;
        }

        $role = $user->company_role ?? ($user->user_type ?? null);

        if ($role === 'owner' || $role === 'director') {
            return true;
        }

        if (isset($user->is_owner) && $user->is_owner) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can manage other users' permissions.
     */
    public static function canManagePermissions($user): bool
    {
        return self::isSuperadmin($user);
    }

    /**
     * Get permission label from key.
     */
    public static function getPermissionLabel(string $key): string
    {
        $menuItem = \App\Services\SidebarMenuConfig::getMenuByPermissionKey($key);
        if ($menuItem) {
            return $menuItem['label'];
        }

        return $key;
    }

    /**
     * Get all accessible sidebar items for a user.
     */
    public static function getAccessibleSidebarItems($user): array
    {
        if (!$user) {
            return [];
        }

        return UserPermissionService::forUser($user)->getAccessibleModules();
    }

    /**
     * Check if user can view module.
     */
    public static function canView($user, string $module): bool
    {
        if (!$user) {
            return false;
        }

        return UserPermissionService::forUser($user)->canView($module);
    }

    /**
     * Check if user can create in module.
     */
    public static function canCreate($user, string $module): bool
    {
        if (!$user) {
            return false;
        }

        return UserPermissionService::forUser($user)->canCreate($module);
    }

    /**
     * Check if user can edit in module.
     */
    public static function canEdit($user, string $module): bool
    {
        if (!$user) {
            return false;
        }

        return UserPermissionService::forUser($user)->canUpdate($module);
    }

    /**
     * Check if user can delete in module.
     */
    public static function canDelete($user, string $module): bool
    {
        if (!$user) {
            return false;
        }

        return UserPermissionService::forUser($user)->canDelete($module);
    }

    /**
     * Check if user has global scope for module.
     */
    public static function isGlobal($user, string $module): bool
    {
        if (!$user) {
            return false;
        }

        return UserPermissionService::forUser($user)->isGlobalScope($module);
    }

    /**
     * Get user's module permissions.
     */
    public static function getModulePermissions($user, string $module): array
    {
        if (!$user) {
            return [];
        }

        return UserPermissionService::forUser($user)->getModulePermission($module);
    }

    /**
     * Get all user permissions (array of permission keys for sidebar).
     * @deprecated Use UserPermissionService
     */
    public static function getUserAllPermissions($user): array
    {
        if (!$user) {
            return [];
        }

        $permService = UserPermissionService::forUser($user);

        // Superadmin gets all permissions
        if ($permService->isSuperAdmin()) {
            return \App\Services\SidebarMenuConfig::getAllPermissionKeys();
        }

        // Get accessible modules and convert to sidebar format
        $accessibleModules = $permService->getAccessibleModules();
        $keys = [];

        foreach ($accessibleModules as $module) {
            $keys[] = 'sidebar.' . $module;
        }

        return $keys;
    }
}
