<?php

namespace App\Modules\System\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use App\Core\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\CrmModulePermission;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use BelongsToTenant;
    use HasAuditLog;
    use HasActivityLog;
    use SoftDeletes;

    protected $table = 'users';

    // Boot method to register model events
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate UUID when creating a new user
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    // User roles
    const ROLE_DEVELOPER = 'developer';   // Developer (full access)
    const ROLE_PUSAT = 'pusat';           // Admin Pusat
    const ROLE_OWNER = 'owner';           // Pemilik/Direktur Utama
    const ROLE_DIRECTOR = 'director';     // Direktur (akses penuh tenant)
    const ROLE_ADMIN = 'admin';            // Admin Perusahaan
    const ROLE_MANAGER = 'manager';       // Manajer
    const ROLE_STAFF = 'staff';           // Staff

    protected $fillable = [
        'uuid',
        'tenant_id',
        'company_id',
        'division_id',
        'name',
        'username',
        'email',
        'password',
        'avatar_url',
        'profile_photo',
        'phone',
        'employee_id',

        // Basic Info
        'user_type',
        'company_role',
        'is_active',
        'is_owner',
        'department',
        'position',

        // HRD / Employee Data
        'nik', // NIK KTP
        'kk_number', // Nomor Kartu Keluarga
        'birth_place', // Tempat Lahir
        'birth_date', // Tanggal Lahir
        'gender', // Jenis Kelamin
        'religion', // Agama
        'address', // Alamat Lengkap
        'province', // Provinsi
        'city', // Kota/Kabupaten
        'district', // Kecamatan
        'village', // Kelurahan/Desa
        'postal_code', // Kode Pos
        'ktp_address', // Alamat KTP (jika berbeda)
        'blood_type', // Golongan Darah
        'marital_status', // Status Perkawinan
        'emergency_contact_name', // Nama Kontak Darurat
        'emergency_contact_phone', // Telepon Darurat
        'emergency_contact_relation', // Hubungan Kontak Darurat
        'bank_name', // Nama Bank
        'bank_account_number', // Nomor Rekening
        'bank_account_name', // Nama Pemilik Rekening
        'bpjs_number', // Nomor BPJS
        'npwp_number', // Nomor NPWP
        'father_name', // Nama Ayah
        'mother_name', // Nama Ibu
        'mother_maiden_name', // Nama Ibu Kandung

        // System
        'last_login_at',
        'last_login_ip',
        'password_changed_at',
        'preferences',
        'metadata',
        'sidebar_permissions',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'is_owner' => 'boolean',
        'preferences' => 'array',
        'metadata' => 'array',
        'sidebar_permissions' => 'array',
    ];

    /**
     * Get company role label in Indonesian
     *
     * @return string
     */
    public function getCompanyRoleLabelAttribute(): string
    {
        return \App\Helpers\RoleHelper::label($this->company_role);
    }

    /**
     * Alias for company_role_label (backward compatibility)
     *
     * @return string
     */
    public function getDisplayRoleAttribute(): string
    {
        return $this->company_role_label;
    }

    /**
     * Get badge class for company role
     *
     * @return string
     */
    public function getCompanyRoleBadgeClassAttribute(): string
    {
        return \App\Helpers\RoleHelper::badgeClass($this->company_role);
    }

    // Default sidebar permissions by role
    public static function getDefaultSidebarPermissions(string $role): array
    {
        // Get all permission keys from the menu config
        $allPermissions = \App\Services\SidebarMenuConfig::getAllPermissionKeys();

        return match($role) {
            // Developer gets all permissions
            self::ROLE_DEVELOPER => $allPermissions,

            // Pusat Admin gets all permissions (full access)
            self::ROLE_PUSAT => $allPermissions,

            // Owner gets all permissions (full tenant access)
            self::ROLE_OWNER => $allPermissions,

            // Admin gets all permissions
            self::ROLE_ADMIN => $allPermissions,

            // Manager gets: dashboard, project tasks, assets, and HRD
            self::ROLE_MANAGER => [
                'dashboard',
                'project_tasks', 'projects', 'tasks', 'tasks.calendar',
                'assets_section', 'assets', 'asset_categories',
                'hrd_expert', 'hrd.dashboard', 'hrd.employees', 'hrd.attendances', 'hrd.reports',
            ],

            // Staff gets: dashboard, project tasks, and assets
            self::ROLE_STAFF => [
                'dashboard',
                'project_tasks', 'projects', 'tasks', 'tasks.calendar',
                'assets_section', 'assets', 'asset_categories',
            ],

            default => ['dashboard'],
        };
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Division::class, 'division_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\HRD\EmployeeProfile::class, 'employee_id');
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        // Prioritas 1: nick_name dari EmployeeProfile
        $employee = $this->employee;
        if ($employee && !empty($employee->nick_name)) {
            return $employee->nick_name;
        }

        // Prioritas 2: nama depan dari user name
        $nameParts = explode(' ', trim($this->name ?? ''));
        if (!empty($nameParts)) {
            return $nameParts[0];
        }

        // Fallback: full user name
        return $this->name ?? 'User';
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')
                   ->orderBy('created_at', 'desc');
    }

    // Accessors
    public function getIsDeveloperAttribute(): bool
    {
        return $this->user_type === self::ROLE_DEVELOPER || $this->hasRole(self::ROLE_DEVELOPER);
    }

    public function getIsPusatAdminAttribute(): bool
    {
        return $this->user_type === self::ROLE_PUSAT || $this->hasRole(self::ROLE_PUSAT);
    }

    public function getIsOwnerAttribute(): bool
    {
        return $this->attributes['is_owner'] || $this->user_type === self::ROLE_OWNER || $this->hasRole(self::ROLE_OWNER);
    }

    public function getIsCompanyAdminAttribute(): bool
    {
        return $this->is_owner || $this->user_type === self::ROLE_ADMIN || $this->hasRole(self::ROLE_ADMIN);
    }

    public function getIsDirectorAttribute(): bool
    {
        return ($this->company_role ?? $this->user_type) === self::ROLE_DIRECTOR || $this->hasRole(self::ROLE_DIRECTOR);
    }

    public function getIsManagerAttribute(): bool
    {
        return ($this->company_role ?? $this->user_type) === self::ROLE_MANAGER || $this->hasRole(self::ROLE_MANAGER);
    }

    public function getIsStaffAttribute(): bool
    {
        return ($this->company_role ?? $this->user_type) === self::ROLE_STAFF || $this->hasRole(self::ROLE_STAFF);
    }

    public function getCanManageMembersAttribute(): bool
    {
        return $this->is_developer || $this->is_pusat_admin || $this->is_company_admin;
    }

    public function getCanManageCompaniesAttribute(): bool
    {
        // Only Developer can manage companies
        return $this->is_developer;
    }

    public function getCanViewAllCompaniesAttribute(): bool
    {
        // Only Developer can see all companies
        return $this->is_developer;
    }

    /**
     * Check if user has permission to access a sidebar item
     * Considers employee-level, user-level, and division-level permissions
     * Priority: Developer > Employee Permission > User Permission > Division Permission
     */
    /**
     * Check if user has sidebar permission.
     * Uses UserPermissionService as single source of truth.
     */
    public function hasSidebarPermission(string $permission): bool
    {
        $service = \App\Services\Permission\UserPermissionService::forUser($this);
        $module = str_replace('sidebar.', '', $permission);
        return $service->can($module);
    }

    /**
     * Get employee-specific sidebar permissions
     * Returns array of enabled menu keys, or null if no custom permissions set
     */
    protected function getEmployeeSidebarPermissions(): ?array
    {
        // User must have employee profile to have employee permissions
        if (!$this->employee_id) {
            // Try to find employee profile by user_id
            $employee = \App\Models\HRD\EmployeeProfile::where('user_id', $this->id)->first();
            if (!$employee) {
                return null;
            }
        } else {
            $employee = \App\Models\HRD\EmployeeProfile::find($this->employee_id);
            if (!$employee) {
                return null;
            }
        }

        // Check if employee has sidebar permissions set
        $sidebarService = new \App\Services\HRD\SidebarPermissionService();
        if (!$sidebarService->hasPermissions($employee->id)) {
            return null;
        }

        return $sidebarService->getEnabledMenuKeys($employee->id);
    }

    /**
     * Check if user has employee-specific sidebar permissions set
     */
    public function hasEmployeeSidebarPermissions(): bool
    {
        return $this->getEmployeeSidebarPermissions() !== null;
    }

    /**
     * Get all effective permissions (employee + user + division combined)
     * Priority: Developer > Employee Permission > User Permission > Division Permission
     */
    public function getEffectivePermissions(): array
    {
        // Developer has all permissions
        if ($this->is_developer) {
            return \App\Services\SidebarMenuConfig::getAllPermissionKeys();
        }

        // Check employee-specific permissions first
        $employeePermissions = $this->getEmployeeSidebarPermissions();
        if ($employeePermissions !== null) {
            // Return only employee allowed permissions
            return $employeePermissions;
        }

        // Fall back to user's own permissions (or default from role)
        $permissions = $this->sidebar_permissions ?? self::getDefaultSidebarPermissions($this->user_type);

        // If user belongs to an active division, add division's permissions
        if ($this->division_id) {
            try {
                $division = $this->division;
                if ($division && $division->is_active) {
                    $divisionPermissions = $division->sidebar_permissions ?? \App\Models\Division::getDefaultSidebarPermissions();
                    $permissions = array_unique(array_merge($permissions, $divisionPermissions));
                }
            } catch (\Exception $e) {
                // Division doesn't exist or can't be loaded
            }
        }

        return $permissions;
    }

    /**
     * Check if user gets permissions from division
     */
    public function hasDivisionPermissions(): bool
    {
        if (!$this->division_id) {
            return false;
        }

        try {
            $division = $this->division;
            return $division && $division->is_active;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get division permissions only
     */
    public function getDivisionPermissions(): array
    {
        if (!$this->division_id) {
            return [];
        }

        try {
            $division = $this->division;
            if ($division && $division->is_active) {
                return $division->sidebar_permissions ?? \App\Models\Division::getDefaultSidebarPermissions();
            }
        } catch (\Exception $e) {
            // Division doesn't exist
        }

        return [];
    }

    /**
     * Check if user can access companies
     */
    public function canAccessCompanies(): bool
    {
        return $this->is_developer || $this->is_pusat_admin;
    }

    /**
     * Check if a permission key is a premium module
     */
    public function isPremiumModule(string $permission): bool
    {
        $items = \App\Services\SidebarItemsService::getSidebarItems();
        return isset($items[$permission]) && ($items[$permission]['type'] ?? null) === 'premium';
    }

    /**
     * Check if a permission key is an owner-access module (company management)
     */
    public function isOwnerAccessModule(string $permission): bool
    {
        $items = \App\Services\SidebarItemsService::getSidebarItems();
        return isset($items[$permission]) && ($items[$permission]['type'] ?? null) === 'owner_access';
    }

    /**
     * Check if user has access to a specific child item within a parent module
     * This is used for fine-grained permission checking
     */
    public function hasChildPermission(string $parentKey, string $childKey): bool
    {
        // Developer has all
        if ($this->is_developer) {
            return true;
        }

        // Get user-specific permissions (or default from role)
        $userPermissions = $this->sidebar_permissions ?? self::getDefaultSidebarPermissions($this->user_type);

        // FIRST: Check if user has explicit permission for child or parent
        // This handles manually granted permissions from Developer
        if (in_array($childKey, $userPermissions)) {
            return true;
        }
        if (in_array($parentKey, $userPermissions)) {
            return true;
        }

        // Director has full access to all modules within tenant
        if ($this->is_director) {
            // For premium modules, check subscription
            if ($this->isPremiumModule($parentKey)) {
                return $this->company && $this->company->hasModuleAccess($parentKey);
            }
            // Free modules - director always has access
            return true;
        }

        // Check if parent is owner_access module (like companies)
        if ($this->isOwnerAccessModule($parentKey)) {
            // Owner has access to companies management for their own company
            if ($this->is_owner || $this->is_pusat_admin) {
                return true;
            }
        }

        // Owner - check premium status
        if ($this->is_owner) {
            if ($this->isPremiumModule($parentKey)) {
                // Check subscription
                if ($this->company && $this->company->hasModuleAccess($parentKey)) {
                    return true;
                }
            }
            // Free modules - owner has access
            return true;
        }

        // For non-owner/non-director roles (admin, manager, staff)
        // Premium modules: only accessible if user has explicit permission (manual grant from Developer)
        if ($this->isPremiumModule($parentKey)) {
            // User doesn't have explicit permission, deny access
            return false;
        }

        // Free modules - check user's role-based permissions

        // Check division permissions
        if ($this->division_id) {
            try {
                $division = $this->division;
                if ($division && $division->is_active) {
                    $divisionPermissions = $division->sidebar_permissions ?? [];
                    return in_array($childKey, $divisionPermissions) || in_array($parentKey, $divisionPermissions);
                }
            } catch (\Exception $e) {
                // Division doesn't exist
            }
        }

        return false;
    }

    /**
     * Get child permissions that are accessible for this user within a parent module
     */
    public function getAccessibleChildPermissions(string $parentKey): array
    {
        $childKeys = \App\Services\SidebarItemsService::getChildKeys($parentKey);
        $accessible = [];

        foreach ($childKeys as $childKey) {
            if ($this->hasChildPermission($parentKey, $childKey)) {
                $accessible[] = $childKey;
            }
        }

        return $accessible;
    }

    /**
     * Check if a parent module should be visible in the sidebar
     * Parent is visible if at least one child is accessible
     */
    public function canAccessParentModule(string $parentKey): bool
    {
        // Developer has all
        if ($this->is_developer) {
            return true;
        }

        // Get user-specific permissions (or default from role)
        $userPermissions = $this->sidebar_permissions ?? self::getDefaultSidebarPermissions($this->user_type);

        // FIRST: Check if user has explicit permission on parent (handles manual grants from Developer)
        if (in_array($parentKey, $userPermissions)) {
            return true;
        }

        // Director has access to all modules within tenant
        if ($this->is_director) {
            // For premium modules, check subscription
            if ($this->isPremiumModule($parentKey)) {
                return $this->company && $this->company->hasModuleAccess($parentKey);
            }
            // Free modules - director always has access
            return true;
        }

        // Owner - check premium status
        if ($this->is_owner) {
            if ($this->isPremiumModule($parentKey)) {
                // Check subscription
                return $this->company && $this->company->hasModuleAccess($parentKey);
            }
            // Free modules - owner always has access
            return true;
        }

        // For non-owner/non-director roles (admin, manager, staff)
        // Check if any child is accessible via explicit permission
        $accessibleChildren = $this->getAccessibleChildPermissions($parentKey);
        return !empty($accessibleChildren);
    }

    /**
     * Get all available roles with Indonesian labels for dropdowns
     * Note: Use RoleHelper::label() for display, this is for select options
     */
    public static function getRoles(): array
    {
        return \App\Helpers\RoleHelper::all();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('user_type', $type);
    }

    public function scopeDevelopers($query)
    {
        return $query->where('user_type', self::ROLE_DEVELOPER);
    }

    // Helpers
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Hash::needsRehash($value)
            ? Hash::make($value)
            : $value;
    }

    public function recordLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->user_type === 'super_admin' || $this->hasRole('super_admin');
    }

    public function isDirector(): bool
    {
        return $this->user_type === 'director'
            || $this->company_role === 'director'
            || $this->hasRole('director');
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin'
            || $this->company_role === 'admin'
            || $this->hasRole('admin');
    }

    public function isDirectorOrAdmin(): bool
    {
        return $this->isDirector() || $this->isAdmin();
    }

    public function isManager(): bool
    {
        return $this->user_type === 'manager' || $this->hasRole('manager');
    }

    public function isStaff(): bool
    {
        return $this->user_type === 'staff' || $this->hasRole('staff');
    }

    public function isClient(): bool
    {
        return $this->user_type === 'client' || $this->hasRole('client');
    }

    public function isOwner(): bool
    {
        return $this->is_owner;
    }

    /**
     * Check if user can be assigned as Assignee in Tasks.
     * Only Director and Admin roles are allowed.
     *
     * PERMISSION: This defines who can be assigned as task assignees.
     * - Director ✓
     * - Admin ✓
     * - Others (Manager, Staff, Employee, Client, Guest) ✗
     */
    public function canBeAssignee(): bool
    {
        // Check company_role field (CRM specific)
        $companyRole = $this->company_role ?? null;

        // Director/Admin if company_role is director/admin
        if (in_array($companyRole, ['director', 'admin'])) {
            return true;
        }

        // Check user_type field
        $userType = $this->user_type ?? null;
        if (in_array($userType, ['director', 'admin'])) {
            return true;
        }

        // Check Spatie roles (additional layer)
        return $this->hasRole(['director', 'admin']);
    }

    /**
     * Get users who can be assignees (Director or Admin only).
     * Scoped to the same company/tenant.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $companyId
     * @param int|null $tenantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCanBeAssignee($query, ?int $companyId = null, ?int $tenantId = null)
    {
        return $query->where(function($q) {
            // Users with company_role = director or admin
            $q->whereIn('company_role', ['director', 'admin'])
              // OR users with user_type = director or admin
              ->orWhereIn('user_type', ['director', 'admin'])
              // OR users with Spatie roles (director or admin)
              ->orWhereHas('roles', function($roleQuery) {
                  $roleQuery->whereIn('name', ['director', 'admin']);
              });
        });
    }

    public function canAccessTenant(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }

    public function getPreference(string $key, mixed $default = null): mixed
    {
        return data_get($this->preferences, $key, $default);
    }

    public function setPreference(string $key, mixed $value): void
    {
        $preferences = $this->preferences ?? [];
        data_set($preferences, $key, $value);
        $this->preferences = $preferences;
        $this->saveQuietly();
    }
}
