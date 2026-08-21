<?php

namespace App\Models;

use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SidebarPermission extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'sidebar_permissions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'permission_key',
        'allowed',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'allowed' => 'boolean',
        ];
    }

    // =====================================================
    // Relationships
    // =====================================================

    /**
     * Get the user that owns this permission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that owns this permission.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // =====================================================
    // Static Methods
    // =====================================================

    /**
     * Get all permission keys for the CRM sidebar.
     */
    public static function getCRMKeys(): array
    {
        return [
            // Beranda
            'dashboard',

            // Proyek & Tugas
            'projects',
            'tasks',
            'tasks.calendar',

            // Kelola Aset & Akses
            'assets',
            'asset_categories',

            // HRD
            'hrd.dashboard',
            'hrd.employees',
            'hrd.attendances',
            'hrd.reports',
        ];
    }

    /**
     * Get permission tree for UI display.
     */
    public static function getPermissionTree(): array
    {
        return [
            [
                'key' => 'dashboard',
                'label' => 'Beranda',
                'icon' => 'fa-home',
                'group' => 'beranda',
                'children' => [],
            ],
            [
                'key' => 'project_tasks',
                'label' => 'Proyek & Tugas',
                'icon' => 'fa-briefcase',
                'group' => 'project_tasks',
                'children' => [
                    ['key' => 'projects', 'label' => 'Proyek', 'icon' => 'fa-folder-open'],
                    ['key' => 'tasks', 'label' => 'Daftar Tugas', 'icon' => 'fa-list-check'],
                    ['key' => 'tasks.calendar', 'label' => 'Kalender', 'icon' => 'fa-calendar'],
                ],
            ],
            [
                'key' => 'assets',
                'label' => 'Kelola Aset & Akses',
                'icon' => 'fa-building',
                'group' => 'assets',
                'children' => [
                    ['key' => 'assets', 'label' => 'Manajemen Aset', 'icon' => 'fa-laptop'],
                    ['key' => 'asset_categories', 'label' => 'Kategori Aset', 'icon' => 'fa-layer-group'],
                ],
            ],
            [
                'key' => 'hrd',
                'label' => 'HRD',
                'icon' => 'fa-users-gear',
                'group' => 'hrd',
                'children' => [
                    ['key' => 'hrd.dashboard', 'label' => 'Dashboard HRD', 'icon' => 'fa-gauge'],
                    ['key' => 'hrd.employees', 'label' => 'Data Karyawan', 'icon' => 'fa-users'],
                    ['key' => 'hrd.attendances', 'label' => 'Absensi', 'icon' => 'fa-calendar-check'],
                    ['key' => 'hrd.reports', 'label' => 'Laporan HRD', 'icon' => 'fa-chart-bar'],
                ],
            ],
        ];
    }

    /**
     * Get default permissions by role.
     */
    public static function getDefaultPermissions(string $role): array
    {
        // All permissions for Developer
        if ($role === 'developer') {
            return self::getCRMKeys();
        }

        // Default: all permissions enabled
        return self::getCRMKeys();
    }

    /**
     * Get user's allowed permissions from database.
     */
    public static function getUserPermissions(int $userId): array
    {
        return self::where('user_id', $userId)
            ->where('allowed', true)
            ->pluck('permission_key')
            ->toArray();
    }

    /**
     * Sync user permissions (replace all with new set).
     * Uses database transaction for atomic operation.
     */
    public static function syncUserPermissions(int $userId, int $companyId, array $permissions): void
    {
        \DB::transaction(function () use ($userId, $companyId, $permissions) {
            // Delete all existing permissions for this user
            self::where('user_id', $userId)->delete();

            // Insert new permissions
            $now = now();
            $records = [];
            foreach ($permissions as $key) {
                $records[] = [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'permission_key' => $key,
                    'allowed' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($records)) {
                self::insert($records);
            }
        });
    }

    // =====================================================
    // Cache Methods
    // =====================================================

    /**
     * Get cache key for user permissions.
     */
    public static function getCacheKey(int $userId): string
    {
        return "sidebar_permissions_user_{$userId}";
    }

    /**
     * Get user permissions from cache or database.
     */
    public static function getCachedPermissions(int $userId): array
    {
        $cacheKey = self::getCacheKey($userId);

        return \Cache::remember($cacheKey, 3600, function () use ($userId) {
            return self::getUserPermissions($userId);
        });
    }

    /**
     * Clear user permissions cache.
     */
    public static function clearCache(int $userId): void
    {
        \Cache::forget(self::getCacheKey($userId));
    }
}
