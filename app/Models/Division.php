<?php

namespace App\Models;

use App\Modules\System\Models\User;
use App\Services\SidebarItemsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Division extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'department_id',
        'name',
        'slug',
        'code',
        'description',
        'sidebar_permissions',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sidebar_permissions' => 'array',
    ];

    // Boot method to auto-generate slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($division) {
            if (empty($division->slug)) {
                $division->slug = Str::slug($division->name);
            }
        });
    }

    /**
     * Default sidebar permissions for division
     */
    public static function getDefaultSidebarPermissions(): array
    {
        return [
            'dashboard',
            'clients',
            'projects',
            'tasks',
        ];
    }

    /**
     * All available sidebar permissions
     */
    public static function getAvailablePermissions(): array
    {
        return SidebarItemsService::getFlatPermissions();
    }

    /**
     * Get sidebar permissions for this division
     */
    public function getSidebarPermissions(): array
    {
        return $this->sidebar_permissions ?? self::getDefaultSidebarPermissions();
    }

    /**
     * Check permission
     */
    public function hasSidebarPermission(string $permission): bool
    {
        return in_array($permission, $this->getSidebarPermissions());
    }

    /**
     * Get formatted permissions for display
     */
    public function getFormattedPermissions(): array
    {
        $permissions = $this->getSidebarPermissions();
        $available = self::getAvailablePermissions();

        $formatted = [];
        foreach ($permissions as $perm) {
            $formatted[$perm] = $available[$perm] ?? ucfirst(str_replace('_', ' ', $perm));
        }

        return $formatted;
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\App\Models\HRD\Department::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\Position::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\EmployeeProfile::class, 'division_id');
    }

    /**
     * Get members count
     */
    public function getMembersCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Get active employees count
     */
    public function getEmployeeCountAttribute(): int
    {
        return $this->employees()->where('is_active', true)->count();
    }

    /**
     * Scope for active divisions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for divisions in a department
     */
    public function scopeInDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->is_active
            ? 'bg-green-100 text-green-700'
            : 'bg-gray-100 text-gray-700';
    }
}
