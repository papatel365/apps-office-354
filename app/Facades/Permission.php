<?php

namespace App\Facades;

use App\Services\Permission\UserPermissionService;
use Illuminate\Support\Facades\Facade;

/**
 * Permission Facade - Easy access to UserPermissionService
 *
 * Usage:
 *   Permission::can('projects')
 *   Permission::canCreate('projects')
 *   Permission::canView('projects')
 *   Permission::canUpdate('projects')
 *   Permission::canDelete('projects')
 *   Permission::isGlobalScope('projects')
 *   Permission::getAccessibleModules()
 *
 * @method static bool isSuperAdmin()
 * @method static bool can(string $module)
 * @method static bool canAccessSidebar(string $module)
 * @method static bool canView(string $module)
 * @method static bool canCreate(string $module)
 * @method static bool canUpdate(string $module)
 * @method static bool canDelete(string $module)
 * @method static bool isGlobalScope(string $module)
 * @method static bool isOwnScope(string $module)
 * @method static bool canViewRecord(string $module, ?int $creatorId = null)
 * @method static array getAccessibleModules()
 * @method static array getModulePermission(string $module)
 * @method static void savePermissions(array $permissions)
 * @method static void initializeDefaults()
 * @method static void clearCache()
 * @method static self forUser(\App\Modules\System\Models\User $user)
 * @method static self forUserId(int $userId)
 */
class Permission extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'permission';
    }
}
