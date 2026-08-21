<?php

namespace App\Models;

use App\Modules\System\Models\User;
use App\Services\SidebarMenuConfig;
use App\Services\Permission\UserPermissionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * CRM Module Permission Model - DEPRECATED
 *
 * Use UserPermissionService instead for all permission checks.
 * This class is kept for backward compatibility with old code.
 *
 * @deprecated Use UserPermissionService
 */
class CrmModulePermission extends Model
{
    use HasFactory;

    protected $table = 'crm_module_permissions';

    protected $fillable = [
        'company_id',
        'user_id',
        'module',
        'permission_key',
        'allowed',
    ];

    protected $casts = [
        'allowed' => 'boolean',
    ];

    // =====================================================
    // CONSTANTS
    // =====================================================

    const MODULE_SIDEBAR = 'sidebar';
    const MODULE_PROJECTS = 'projects';
    const MODULE_TASKS = 'tasks';
    const MODULE_ATTENDANCES = 'attendances';

    const PERM_VIEW_OWN = 'view_own';
    const PERM_VIEW_GLOBAL = 'view_global';
    const PERM_CREATE = 'create';
    const PERM_EDIT = 'edit';
    const PERM_DELETE = 'delete';
    const PERM_ACCESS = 'access';

    // =====================================================
    // SIDEBAR CONFIG HELPERS
    // =====================================================

    public static function getSidebarPermissionKeys(): array
    {
        return SidebarMenuConfig::getAllPermissionKeys();
    }

    public static function getSidebarPermissions(): array
    {
        return SidebarMenuConfig::getPermissionGroups();
    }

    // =====================================================
    // PERMISSION CHECKS - Delegate to UserPermissionService
    // =====================================================

    /**
     * Get user sidebar permissions as flat array
     * Returns: ['sidebar.dashboard', 'sidebar.projects', ...]
     */
    public static function getUserSidebarPermissions(int $userId): array
    {
        $permService = UserPermissionService::forUserId($userId);

        // If super admin, return all
        if ($permService->isSuperAdmin()) {
            return self::getSidebarPermissionKeys();
        }

        // Get accessible modules
        $accessibleModules = $permService->getAccessibleModules();
        $result = [];

        foreach ($accessibleModules as $module) {
            $result[] = 'sidebar.' . $module;
        }

        return $result;
    }

    /**
     * Get user sidebar permissions as structured array
     */
    public static function getUserSidebarPermissionsStructured(int $userId): array
    {
        $permService = UserPermissionService::forUserId($userId);

        if ($permService->isSuperAdmin()) {
            $result = [];
            foreach (self::getSidebarPermissionKeys() as $key) {
                $result[$key] = true;
            }
            return $result;
        }

        $result = [];
        foreach (self::getSidebarPermissionKeys() as $key) {
            $module = str_replace('sidebar.', '', $key);
            $result[$key] = $permService->can($module);
        }

        return $result;
    }

    /**
     * Convert sidebar permissions format to module permissions format
     */
    public static function syncUserSidebarPermissions(int $userId, array $allowedKeys): array
    {
        $modulePerms = [];

        foreach ($allowedKeys as $key => $value) {
            $permissionKey = null;

            if (is_string($key) && is_bool($value)) {
                if ($value) {
                    $permissionKey = $key;
                }
            } elseif (is_string($value)) {
                $permissionKey = $value;
            } elseif (is_string($key) && !is_numeric($key)) {
                if ($value) {
                    $permissionKey = $key;
                }
            }

            if ($permissionKey) {
                $module = $permissionKey;
                if (str_starts_with($module, 'sidebar.')) {
                    $module = substr($module, strlen('sidebar.'));
                }

                if (in_array($module, SidebarMenuConfig::getManagedModules())) {
                    $modulePerms[$module] = [
                        'scope_own' => true,
                        'scope_global' => false,
                        'can_view' => true,
                        'can_create' => true,
                        'can_update' => true,
                        'can_delete' => true,
                    ];
                }
            }
        }

        return $modulePerms;
    }

    public static function hasSidebarPermission(int $userId, string $permission): bool
    {
        $module = str_replace('sidebar.', '', $permission);
        return UserPermissionService::forUserId($userId)->can($module);
    }

    public static function canAccessSidebarMenu(int $userId, string $permission): bool
    {
        return self::hasSidebarPermission($userId, $permission);
    }

    // =====================================================
    // LEGACY METHODS - For backward compatibility
    // =====================================================

    public static function getDefaultPermissions(string $role): array
    {
        return SidebarMenuConfig::getDefaultPermissionsByRole($role);
    }

    public static function initializeForUser(User $user): void
    {
        UserPermissionService::forUser($user)->initializeDefaults();
    }

    public static function syncUserPermissions(int $userId, int $companyId, array $permissions): void
    {
        $newPerms = [];
        foreach ($permissions as $perm) {
            $module = str_replace('sidebar.', '', $perm);
            if (in_array($module, SidebarMenuConfig::getManagedModules())) {
                $newPerms[$module] = [
                    'scope_own' => true,
                    'scope_global' => false,
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ];
            }
        }

        UserPermissionService::forUserId($userId)->savePermissions($newPerms);
    }

    public static function getCacheKey(int $userId): string
    {
        return "user_permissions_{$userId}";
    }

    public static function getCachedPermissions(int $userId): array
    {
        return UserPermissionService::forUserId($userId)->getPermissions();
    }

    public static function clearCache(int $userId): void
    {
        UserPermissionService::forUserId($userId)->clearCache();
    }

    public static function userHasPermission(int $userId, string $module, string $permission): bool
    {
        $permService = UserPermissionService::forUserId($userId);
        $perm = $permService->getModulePermission($module);

        return match ($permission) {
            self::PERM_VIEW_OWN => $perm['scope_own'],
            self::PERM_VIEW_GLOBAL => $perm['scope_global'],
            self::PERM_CREATE => $perm['can_create'],
            self::PERM_EDIT => $perm['can_update'],
            self::PERM_DELETE => $perm['can_delete'],
            default => false,
        };
    }

    // =====================================================
    // SIDEBAR CONFIG HELPERS
    // =====================================================

    public static function getParentPermissionKey(string $childKey): ?string
    {
        return SidebarMenuConfig::getParentKey($childKey);
    }

    public static function isSidebarParentKey(string $key): bool
    {
        return SidebarMenuConfig::isParentKey($key);
    }

    public static function getChildPermissionKeys(string $parentKey): array
    {
        return SidebarMenuConfig::getChildKeys($parentKey);
    }
}
