<?php

namespace App\Services\HRD;

use App\Models\HRD\SidebarPermission;
use App\Services\SidebarMenuConfig;
use Illuminate\Support\Collection;

/**
 * Service for managing sidebar permissions per employee.
 * Uses SidebarMenuConfig as the single source of truth for menu configuration.
 */
class SidebarPermissionService
{
    /**
     * Get all sidebar menu items in a tree structure for the permission UI.
     * Uses the same structure as the actual sidebar via SidebarMenuConfig.
     */
    public function getMenuTree(): array
    {
        return SidebarMenuConfig::getMenuTree();
    }

    /**
     * Get all permission keys (flat list).
     */
    public function getAllPermissionKeys(): array
    {
        return SidebarMenuConfig::getAllPermissionKeys();
    }

    /**
     * Get only parent keys.
     */
    public function getParentKeys(): array
    {
        return SidebarMenuConfig::getParentKeys();
    }

    /**
     * Get child keys for a specific parent.
     */
    public function getChildKeys(string $parentKey): array
    {
        return SidebarMenuConfig::getChildKeys($parentKey);
    }

    /**
     * Get permissions for a specific employee.
     * Returns array of menu_key => can_view
     */
    public function getEmployeePermissions(int $employeeId): array
    {
        $permissions = SidebarPermission::forEmployee($employeeId)
            ->pluck('can_view', 'menu_key')
            ->toArray();

        return $permissions;
    }

    /**
     * Get enabled menu keys for an employee.
     */
    public function getEnabledMenuKeys(int $employeeId): array
    {
        return SidebarPermission::forEmployee($employeeId)
            ->enabled()
            ->pluck('menu_key')
            ->toArray();
    }

    /**
     * Get disabled menu keys for an employee.
     */
    public function getDisabledMenuKeys(int $employeeId): array
    {
        return SidebarPermission::forEmployee($employeeId)
            ->disabled()
            ->pluck('menu_key')
            ->toArray();
    }

    /**
     * Check if an employee has any sidebar permissions set.
     */
    public function hasPermissions(int $employeeId): bool
    {
        return SidebarPermission::forEmployee($employeeId)->exists();
    }

    /**
     * Save permissions for an employee.
     * Uses updateOrCreate to avoid duplicate entry errors.
     * Explicitly passes company_id to ensure proper scoping.
     *
     * @param int $employeeId
     * @param array $enabledMenuKeys Array of menu keys that should be enabled
     */
    public function savePermissions(int $employeeId, array $enabledMenuKeys): void
    {
        $allKeys = $this->getAllPermissionKeys();

        // Get company_id from employee to ensure proper scoping
        $employee = \App\Models\HRD\EmployeeProfile::find($employeeId);
        $companyId = $employee?->company_id;

        // Use updateOrCreate with explicit company_id to avoid UNIQUE constraint violations
        foreach ($allKeys as $menuKey) {
            SidebarPermission::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'company_id' => $companyId,
                    'menu_key' => $menuKey,
                ],
                [
                    'can_view' => in_array($menuKey, $enabledMenuKeys),
                ]
            );
        }
    }

    /**
     * Update permissions for an employee (partial update).
     * Only updates the keys provided.
     *
     * @param int $employeeId
     * @param array $menuKeys Array of menu keys to enable
     */
    public function enablePermissions(int $employeeId, array $menuKeys): void
    {
        $allKeys = $this->getAllPermissionKeys();

        // Get company_id from employee to ensure proper scoping
        $employee = \App\Models\HRD\EmployeeProfile::find($employeeId);
        $companyId = $employee?->company_id;

        foreach ($allKeys as $menuKey) {
            SidebarPermission::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'company_id' => $companyId,
                    'menu_key' => $menuKey,
                ],
                [
                    'can_view' => in_array($menuKey, $menuKeys),
                ]
            );
        }
    }

    /**
     * Clear all permissions for an employee.
     */
    public function clearPermissions(int $employeeId): void
    {
        SidebarPermission::forEmployee($employeeId)->delete();
    }

    /**
     * Get statistics for an employee's permissions.
     */
    public function getPermissionStats(int $employeeId): array
    {
        $total = count($this->getAllPermissionKeys());
        $enabled = SidebarPermission::forEmployee($employeeId)->enabled()->count();
        $disabled = SidebarPermission::forEmployee($employeeId)->disabled()->count();
        $hasCustom = $this->hasPermissions($employeeId);

        return [
            'total' => $total,
            'enabled' => $enabled,
            'disabled' => $disabled,
            'has_custom' => $hasCustom,
        ];
    }

    /**
     * Check if a user can access a specific menu item.
     * Priority: Developer > Employee Permission > Role Default
     *
     * @param int|null $employeeId The employee profile ID
     * @param string $menuKey The menu key to check
     * @param bool $isDeveloper Whether the user is a developer
     * @param array $roleDefaultPermissions Default permissions from the user's role
     * @return bool
     */
    public function canAccessMenu(?int $employeeId, string $menuKey, bool $isDeveloper, array $roleDefaultPermissions = []): bool
    {
        // 1. Developer always has full access
        if ($isDeveloper) {
            return true;
        }

        // 2. Check employee-specific permissions if set
        if ($employeeId !== null && $this->hasPermissions($employeeId)) {
            $enabledKeys = $this->getEnabledMenuKeys($employeeId);

            // If employee has explicit permission for this key, use it
            if (in_array($menuKey, $enabledKeys)) {
                return true;
            }

            // Check if this is a parent key - allow if any child is enabled
            $childKeys = $this->getChildKeys($menuKey);
            if (!empty($childKeys)) {
                $enabledChildren = array_intersect($childKeys, $enabledKeys);
                if (!empty($enabledChildren)) {
                    return true;
                }
            }

            return false;
        }

        // 3. Fall back to role default permissions
        return in_array($menuKey, $roleDefaultPermissions);
    }

    /**
     * Get the full list of accessible menu keys for an employee.
     * This considers employee permissions and falls back to role defaults.
     */
    public function getAccessibleMenuKeys(?int $employeeId, bool $isDeveloper, array $roleDefaultPermissions = []): array
    {
        // Developer has all
        if ($isDeveloper) {
            return $this->getAllPermissionKeys();
        }

        // Check employee-specific permissions
        if ($employeeId !== null && $this->hasPermissions($employeeId)) {
            return $this->getEnabledMenuKeys($employeeId);
        }

        // Fall back to role defaults
        return $roleDefaultPermissions;
    }
}
