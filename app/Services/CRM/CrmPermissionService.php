<?php

namespace App\Services\CRM;

use App\Modules\System\Models\User;
use App\Services\SidebarMenuConfig;
use App\Services\Permission\UserPermissionService;
use Illuminate\Support\Facades\Log;

/**
 * CRM Permission Service - DEPRECATED
 *
 * This class is kept for backward compatibility.
 * Use App\Services\Permission\UserPermissionService instead.
 *
 * All methods now delegate to UserPermissionService.
 */
class CrmPermissionService
{
    /**
     * Get a UserPermissionService instance for a user
     */
    protected function getService(User $user): UserPermissionService
    {
        return UserPermissionService::forUser($user);
    }

    /**
     * Check if user is a Super Admin
     */
    public function isSuperAdmin(User $user): bool
    {
        return $this->getService($user)->isSuperAdmin();
    }

    /**
     * Check if user can access a sidebar item
     */
    public function canAccessSidebar(User $user, string $module): bool
    {
        return $this->getService($user)->canAccessSidebar($module);
    }

    /**
     * Check if user can view a module
     */
    public function canView(User $user, string $module): bool
    {
        return $this->getService($user)->canView($module);
    }

    /**
     * Check if user can create in a module
     */
    public function canCreate(User $user, string $module): bool
    {
        return $this->getService($user)->canCreate($module);
    }

    /**
     * Check if user can update in a module
     */
    public function canUpdate(User $user, string $module): bool
    {
        return $this->getService($user)->canUpdate($module);
    }

    /**
     * Check if user can delete in a module
     */
    public function canDelete(User $user, string $module): bool
    {
        return $this->getService($user)->canDelete($module);
    }

    /**
     * Check if user has global scope for a module
     */
    public function isGlobalScope(User $user, string $module): bool
    {
        return $this->getService($user)->isGlobalScope($module);
    }

    /**
     * Alias for isGlobalScope
     */
    public function canViewGlobal(User $user, string $module): bool
    {
        return $this->isGlobalScope($user, $module);
    }

    /**
     * Check if user has own scope for a module
     */
    public function isOwnScope(User $user, string $module): bool
    {
        return $this->getService($user)->isOwnScope($module);
    }

    /**
     * Check if user can view a specific record
     */
    public function canViewRecord(User $user, string $module, ?int $creatorId = null): bool
    {
        return $this->getService($user)->canViewRecord($module, $creatorId);
    }

    /**
     * Get all accessible sidebar items for a user
     */
    public function getAccessibleSidebarItems(User $user): array
    {
        $menuConfig = SidebarMenuConfig::getMenuConfig();
        $accessible = [];

        foreach ($menuConfig as $menu) {
            if ($menu['type'] === 'item') {
                if ($this->canAccessSidebar($user, $menu['key'])) {
                    $accessible[] = $menu;
                }
            } elseif ($menu['type'] === 'group') {
                $accessibleChildren = [];
                foreach ($menu['children'] as $child) {
                    if ($this->canAccessSidebar($user, $child['key'])) {
                        $accessibleChildren[] = $child;
                    }
                }
                if (!empty($accessibleChildren)) {
                    $groupMenu = $menu;
                    $groupMenu['children'] = $accessibleChildren;
                    $accessible[] = $groupMenu;
                }
            }
        }

        return $accessible;
    }

    /**
     * Get all permissions for a user
     */
    public function getAllUserPermissions(User $user): array
    {
        return $this->getService($user)->getPermissions();
    }

    /**
     * Get user permission for a specific module
     */
    public function getUserModulePermission(User $user, string $module): array
    {
        return $this->getService($user)->getModulePermission($module);
    }

    /**
     * Initialize permissions for a user
     */
    public function initializeForUser(User $user): void
    {
        $this->getService($user)->initializeDefaults();
    }
}
