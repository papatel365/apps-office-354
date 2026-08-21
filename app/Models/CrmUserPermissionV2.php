<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CRM User Permission Model V2
 *
 * This is a PURE DATA model. All business logic is in UserPermissionService.
 *
 * Database table: crm_user_permissions_v2
 *
 * @property int $id
 * @property int $user_id
 * @property string $module
 * @property bool $scope_own
 * @property bool $scope_global
 * @property bool $can_view
 * @property bool $can_create
 * @property bool $can_update
 * @property bool $can_delete
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CrmUserPermissionV2 extends Model
{
    protected $table = 'crm_user_permissions_v2';

    protected $fillable = [
        'user_id',
        'module',
        'scope_own',
        'scope_global',
        'can_view',
        'can_create',
        'can_update',
        'can_delete',
    ];

    protected $casts = [
        'scope_own' => 'boolean',
        'scope_global' => 'boolean',
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\System\Models\User::class);
    }

    // =====================================================
    // QUERY SCOPES
    // =====================================================

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    // =====================================================
    // LEGACY STATIC METHODS
    // These are kept for backward compatibility.
    // All logic now uses UserPermissionService.
    // =====================================================

    /**
     * Get user's permissions for a module
     * @deprecated Use UserPermissionService::getModulePermission()
     */
    public static function getUserModulePermission(int $userId, string $module): array
    {
        $service = \App\Services\Permission\UserPermissionService::forUserId($userId);
        return $service->getModulePermission($module);
    }

    /**
     * Get all permissions for a user
     * @deprecated Use UserPermissionService::getPermissions()
     */
    public static function getAllUserPermissions(int $userId): array
    {
        $service = \App\Services\Permission\UserPermissionService::forUserId($userId);
        return $service->getPermissions();
    }

    /**
     * Check if user can access a module
     * @deprecated Use UserPermissionService::can()
     */
    public static function canAccess(int $userId, string $module): bool
    {
        $service = \App\Services\Permission\UserPermissionService::forUserId($userId);
        return $service->can($module);
    }

    /**
     * Check if user can view a module
     * @deprecated Use UserPermissionService::canView()
     */
    public static function canView(int $userId, string $module): bool
    {
        $service = \App\Services\Permission\UserPermissionService::forUserId($userId);
        return $service->canView($module);
    }

    /**
     * Check if user can create in a module
     * @deprecated Use UserPermissionService::canCreate()
     */
    public static function canCreate(int $userId, string $module): bool
    {
        $service = \App\Services\Permission\UserPermissionService::forUserId($userId);
        return $service->canCreate($module);
    }

    /**
     * Check if user can update in a module
     * @deprecated Use UserPermissionService::canUpdate()
     */
    public static function canUpdate(int $userId, string $module): bool
    {
        $service = \App\Services\Permission\UserPermissionService::forUserId($userId);
        return $service->canUpdate($module);
    }

    /**
     * Check if user can delete in a module
     * @deprecated Use UserPermissionService::canDelete()
     */
    public static function canDelete(int $userId, string $module): bool
    {
        $service = \App\Services\Permission\UserPermissionService::forUserId($userId);
        return $service->canDelete($module);
    }

    /**
     * Check if user has global scope
     * @deprecated Use UserPermissionService::isGlobalScope()
     */
    public static function isGlobal(int $userId, string $module): bool
    {
        $service = \App\Services\Permission\UserPermissionService::forUserId($userId);
        return $service->isGlobalScope($module);
    }

    /**
     * Check if user has own scope
     * @deprecated Use UserPermissionService::isOwnScope()
     */
    public static function isOwn(int $userId, string $module): bool
    {
        $service = \App\Services\Permission\UserPermissionService::forUserId($userId);
        return $service->isOwnScope($module);
    }

    /**
     * Check if user can view a specific record
     * @deprecated Use UserPermissionService::canViewRecord()
     */
    public static function canViewRecord(int $userId, string $module, ?int $creatorId = null): bool
    {
        $service = \App\Services\Permission\UserPermissionService::forUserId($userId);
        return $service->canViewRecord($module, $creatorId);
    }

    /**
     * Save user permission for a module
     * @deprecated Use UserPermissionService::savePermissions()
     */
    public static function saveUserPermission(int $userId, string $module, array $data): void
    {
        $service = \App\Services\Permission\UserPermissionService::forUserId($userId);
        $service->savePermissions([$module => $data]);
    }

    /**
     * Sync all permissions for a user
     * @deprecated Use UserPermissionService::savePermissions()
     */
    public static function syncPermissions(int $userId, array $permissions): void
    {
        $service = \App\Services\Permission\UserPermissionService::forUserId($userId);
        $service->savePermissions($permissions);
    }

    /**
     * Initialize default permissions for a user
     * @deprecated Use UserPermissionService::initializeDefaults()
     */
    public static function initializeForUser(\App\Modules\System\Models\User $user): void
    {
        $service = \App\Services\Permission\UserPermissionService::forUser($user);
        $service->initializeDefaults();
    }

    // =====================================================
    // CACHE METHODS
    // =====================================================

    public static function getCacheKey(int $userId): string
    {
        return "user_permissions_{$userId}";
    }

    /**
     * Get cached permissions
     * @deprecated Use UserPermissionService
     */
    public static function getCachedPermissions(int $userId): array
    {
        return self::getAllUserPermissions($userId);
    }

    /**
     * Clear cache
     * @deprecated Use UserPermissionService::clearCache()
     */
    public static function clearCache(int $userId): void
    {
        \Illuminate\Support\Facades\Cache::forget(self::getCacheKey($userId));
    }
}
