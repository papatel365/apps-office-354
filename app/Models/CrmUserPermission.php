<?php

namespace App\Models;

use App\Modules\System\Models\User;
use App\Services\SidebarMenuConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * CRM User Permission Model (Legacy - redirects to V2)
 *
 * @deprecated Use CrmUserPermissionV2 instead
 *
 * @property int $id
 * @property int $user_id
 * @property string $module
 * @property string $scope
 * @property bool $can_view
 * @property bool $can_create
 * @property bool $can_update
 * @property bool $can_delete
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CrmUserPermission extends Model
{
    // This model is deprecated - use CrmUserPermissionV2 instead
    // All methods redirect to V2 for backward compatibility

    protected $table = 'crm_user_permissions';

    protected $fillable = [
        'user_id',
        'module',
        'scope',
        'can_view',
        'can_create',
        'can_update',
        'can_delete',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];

    const SCOPE_OWN = 'own';
    const SCOPE_GLOBAL = 'global';

    const MODULE_PROJECTS = 'projects';
    const MODULE_TASKS = 'tasks';
    const MODULE_EMPLOYEES = 'employees';
    const MODULE_ATTENDANCES = 'attendances';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =====================================================
    // LEGACY METHODS - Redirect to V2
    // =====================================================

    public static function getManagedModules(): array
    {
        return SidebarMenuConfig::getManagedModules();
    }

    public static function getModulesWithInfo(): array
    {
        return SidebarMenuConfig::getPermissionModules();
    }

    public static function getUserModulePermission(int $userId, string $module): array
    {
        return CrmUserPermissionV2::getUserModulePermission($userId, $module);
    }

    public static function getAllUserPermissions(int $userId): array
    {
        return CrmUserPermissionV2::getAllUserPermissions($userId);
    }

    public static function userCan(int $userId, string $module, string $action): bool
    {
        $perm = CrmUserPermissionV2::getUserModulePermission($userId, $module);

        switch ($action) {
            case 'view':
                return $perm['can_view'] || $perm['scope_own'] || $perm['scope_global'];
            case 'create':
                return $perm['can_create'];
            case 'update':
                return $perm['can_update'];
            case 'delete':
                return $perm['can_delete'];
            default:
                return false;
        }
    }

    public static function getUserScope(int $userId, string $module): string
    {
        $perm = CrmUserPermissionV2::getUserModulePermission($userId, $module);
        return $perm['scope_global'] ? 'global' : 'own';
    }

    public static function isGlobalScope(int $userId, string $module): bool
    {
        return CrmUserPermissionV2::isGlobal($userId, $module);
    }

    public static function isOwnScope(int $userId, string $module): bool
    {
        return CrmUserPermissionV2::isOwn($userId, $module);
    }

    public static function canViewRecord(int $userId, string $module, ?int $creatorId = null, ?int $assigneeId = null): bool
    {
        return CrmUserPermissionV2::canViewRecord($userId, $module, $creatorId);
    }

    public static function saveUserPermission(int $userId, string $module, array $data): void
    {
        CrmUserPermissionV2::saveUserPermission($userId, $module, $data);
    }

    public static function syncPermissions(int $userId, array $permissions): void
    {
        CrmUserPermissionV2::syncPermissions($userId, $permissions);
    }

    public static function initializeForUser(User $user): void
    {
        CrmUserPermissionV2::initializeForUser($user);
    }

    public static function getCacheKey(int $userId): string
    {
        return CrmUserPermissionV2::getCacheKey($userId);
    }

    public static function getCachedPermissions(int $userId): array
    {
        return CrmUserPermissionV2::getCachedPermissions($userId);
    }

    public static function clearCache(int $userId): void
    {
        CrmUserPermissionV2::clearCache($userId);
    }
}
