<?php

namespace App\Services\Permission;

use App\Models\CrmUserPermissionV2;
use App\Modules\System\Models\User;
use App\Services\SidebarMenuConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Unified Permission Service - SINGLE SOURCE OF TRUTH
 *
 * This service is the ONLY authorized way to check user permissions.
 * All permission checks (Sidebar, Buttons, Routes, Controllers) MUST use this service.
 *
 * Architecture:
 *   Database (crm_user_permissions_v2)
 *        ↓
 *   UserPermissionService (loads once, caches in memory for request)
 *        ↓
 *   Sidebar, Blade, Controller, Middleware (all read from here)
 *
 * Update Flow:
 *   Click Save
 *        ↓
 *   DB::transaction()
 *        ↓
 *   Save to database
 *        ↓
 *   Clear cache
 *        ↓
 *   Reload service (invalidate in-memory cache)
 *        ↓
 *   Return success
 */
class UserPermissionService
{
    /**
     * In-memory cache for current request
     * Key: user_id, Value: array of permissions
     */
    protected static ?array $requestCache = null;

    /**
     * The user this service instance belongs to
     */
    protected ?User $user = null;

    /**
     * User ID this service is for
     */
    protected ?int $userId = null;

    /**
     * Cached permissions for this user
     */
    protected ?array $permissions = null;

    /**
     * Create a new service instance for a user
     */
    public function __construct(?User $user = null)
    {
        $this->user = $user ?? auth()->user();
        $this->userId = $this->user?->id;
    }

    /**
     * Create service for a specific user
     */
    public static function forUser(User $user): self
    {
        return new self($user);
    }

    /**
     * Create service for a user by ID
     */
    public static function forUserId(int $userId): self
    {
        $user = User::find($userId);
        return new self($user);
    }

    /**
     * Check if user is a Super Admin (Developer/Owner/Director)
     */
    public function isSuperAdmin(): bool
    {
        if (!$this->user) {
            return false;
        }

        return $this->user->is_developer
            || $this->user->company_role === 'developer'
            || $this->user->company_role === 'owner'
            || $this->user->company_role === 'director'
            || $this->user->is_owner;
    }

    /**
     * Get all permissions for the user from database
     * This is the ONLY method that reads from database
     */
    protected function loadPermissionsFromDatabase(): array
    {
        if (!$this->userId) {
            return [];
        }

        // Try in-memory cache first
        if (self::$requestCache !== null && isset(self::$requestCache[$this->userId])) {
            return self::$requestCache[$this->userId];
        }

        // Try Laravel cache
        $cacheKey = "user_permissions_{$this->userId}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            // Store in request cache
            self::$requestCache[$this->userId] = $cached;
            return $cached;
        }

        // Load from database
        $permissions = $this->loadFromDatabase();

        // Cache it
        Cache::put($cacheKey, $permissions, 3600);
        self::$requestCache[$this->userId] = $permissions;

        Log::debug('UserPermissionService::loadPermissionsFromDatabase', [
            'user_id' => $this->userId,
            'loaded_from' => 'database',
            'modules' => array_keys($permissions),
        ]);

        return $permissions;
    }

    /**
     * Load permissions directly from database
     */
    protected function loadFromDatabase(): array
    {
        if (!$this->userId) {
            return [];
        }

        $records = CrmUserPermissionV2::where('user_id', $this->userId)->get();

        $permissions = [];
        foreach ($records as $record) {
            $permissions[$record->module] = [
                'scope_own' => (bool) $record->scope_own,
                'scope_global' => (bool) $record->scope_global,
                'can_view' => (bool) $record->can_view,
                'can_create' => (bool) $record->can_create,
                'can_update' => (bool) $record->can_update,
                'can_delete' => (bool) $record->can_delete,
            ];
        }

        return $permissions;
    }

    /**
     * Get permissions array
     */
    public function getPermissions(): array
    {
        if ($this->permissions === null) {
            $this->permissions = $this->loadPermissionsFromDatabase();
        }
        return $this->permissions;
    }

    /**
     * Check if user can access ANY of the given modules
     */
    public function canAny(array $modules): bool
    {
        foreach ($modules as $module) {
            if ($this->can($module)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user can access ALL of the given modules
     */
    public function canAll(array $modules): bool
    {
        foreach ($modules as $module) {
            if (!$this->can($module)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get permission for a specific module
     */
    public function getModulePermission(string $module): array
    {
        $permissions = $this->getPermissions();

        if (isset($permissions[$module])) {
            return $permissions[$module];
        }

        // No permission record exists - return empty (NOT defaults!)
        return [
            'scope_own' => false,
            'scope_global' => false,
            'can_view' => false,
            'can_create' => false,
            'can_update' => false,
            'can_delete' => false,
        ];
    }

    // =====================================================================
    // PERMISSION CHECK METHODS
    // All permission checks go through these methods
    // =====================================================================

    /**
     * Check if user can ACCESS a module (for sidebar visibility)
     * Returns true if user has ANY permission to the module
     */
    public function can(string $module): bool
    {
        // Super admin can do everything
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check if user has any permission to this module
        $perm = $this->getModulePermission($module);
        return $perm['can_view'] || $perm['scope_own'] || $perm['scope_global'];
    }

    /**
     * Alias for can() - for sidebar access
     */
    public function canAccessSidebar(string $module): bool
    {
        return $this->can($module);
    }

    /**
     * Check if user can VIEW a module
     */
    public function canView(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $perm = $this->getModulePermission($module);
        return $perm['can_view'] || $perm['scope_own'] || $perm['scope_global'];
    }

    /**
     * Check if user can CREATE in a module
     */
    public function canCreate(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $perm = $this->getModulePermission($module);
        return $perm['can_create'];
    }

    /**
     * Check if user can UPDATE in a module
     */
    public function canUpdate(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $perm = $this->getModulePermission($module);
        return $perm['can_update'];
    }

    /**
     * Check if user can DELETE in a module
     */
    public function canDelete(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $perm = $this->getModulePermission($module);
        return $perm['can_delete'];
    }

    /**
     * Check if user has GLOBAL scope for a module
     */
    public function isGlobalScope(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $perm = $this->getModulePermission($module);
        return $perm['scope_global'];
    }

    /**
     * Check if user has OWN scope for a module
     */
    public function isOwnScope(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $perm = $this->getModulePermission($module);
        return $perm['scope_own'];
    }

    /**
     * Check if user can view a specific record
     */
    public function canViewRecord(string $module, ?int $creatorId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $perm = $this->getModulePermission($module);

        // No access
        if (!$perm['can_view'] && !$perm['scope_own'] && !$perm['scope_global']) {
            return false;
        }

        // Global scope = can view all
        if ($perm['scope_global']) {
            return true;
        }

        // Own scope = can only view own records
        if ($perm['scope_own'] && $creatorId === $this->userId) {
            return true;
        }

        // Has view permission = can view
        if ($perm['can_view']) {
            return true;
        }

        return false;
    }

    /**
     * Get list of modules user has access to (for sidebar)
     */
    public function getAccessibleModules(): array
    {
        if ($this->isSuperAdmin()) {
            return SidebarMenuConfig::getManagedModules();
        }

        $permissions = $this->getPermissions();
        $accessible = [];

        foreach ($permissions as $module => $perm) {
            if ($perm['can_view'] || $perm['scope_own'] || $perm['scope_global']) {
                $accessible[] = $module;
            }
        }

        return $accessible;
    }

    // =====================================================================
    // SAVE/UPDATE METHODS
    // =====================================================================

    /**
     * Save all permissions for a user in a single transaction
     *
     * @param array $permissions Format: ['module' => ['scope_own' => bool, 'can_view' => bool, ...]]
     */
    public function savePermissions(array $permissions): void
    {
        if (!$this->userId) {
            throw new \Exception('Cannot save permissions: no user');
        }

        Log::info('=== UserPermissionService::savePermissions START ===', [
            'user_id' => $this->userId,
            'modules' => array_keys($permissions),
        ]);

        DB::transaction(function () use ($permissions) {
            // Clear existing permissions
            CrmUserPermissionV2::where('user_id', $this->userId)->delete();

            // Insert new permissions
            foreach ($permissions as $module => $data) {
                if (!in_array($module, SidebarMenuConfig::getManagedModules())) {
                    continue;
                }

                CrmUserPermissionV2::create([
                    'user_id' => $this->userId,
                    'module' => $module,
                    'scope_own' => $data['scope_own'] ?? false,
                    'scope_global' => $data['scope_global'] ?? false,
                    'can_view' => $data['can_view'] ?? false,
                    'can_create' => $data['can_create'] ?? false,
                    'can_update' => $data['can_update'] ?? false,
                    'can_delete' => $data['can_delete'] ?? false,
                ]);
            }

            // Clear cache
            $this->clearCache();
        });

        Log::info('=== UserPermissionService::savePermissions COMPLETE ===', [
            'user_id' => $this->userId,
            'saved_modules' => array_keys($permissions),
        ]);
    }

    /**
     * Initialize default permissions for a user
     */
    public function initializeDefaults(): void
    {
        if (!$this->user) {
            return;
        }

        $role = $this->user->company_role ?? $this->user->user_type ?? 'staff';
        $defaults = SidebarMenuConfig::getDefaultModulePermissionsByRole($role);

        $this->savePermissions($defaults);
    }

    /**
     * Clear cache for this user
     */
    public function clearCache(): void
    {
        if (!$this->userId) {
            return;
        }

        // Clear Laravel cache
        Cache::forget("user_permissions_{$this->userId}");

        // Clear in-memory request cache
        if (self::$requestCache !== null && isset(self::$requestCache[$this->userId])) {
            unset(self::$requestCache[$this->userId]);
        }

        // Reset loaded permissions
        $this->permissions = null;

        Log::debug('UserPermissionService::clearCache', [
            'user_id' => $this->userId,
        ]);
    }

    /**
     * Reload permissions from database
     */
    public function reload(): self
    {
        $this->clearCache();
        $this->permissions = $this->loadPermissionsFromDatabase();
        return $this;
    }

    /**
     * Static helper to check permission for current user
     */
    public static function check(string $module, string $action = 'view'): bool
    {
        $service = new self();
        return match ($action) {
            'view' => $service->canView($module),
            'create' => $service->canCreate($module),
            'update' => $service->canUpdate($module),
            'delete' => $service->canDelete($module),
            'access' => $service->can($module),
            default => false,
        };
    }
}
