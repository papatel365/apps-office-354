<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'level',
        'department_id',
        'division_id',
        'parent_id',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Position levels hierarchy
     */
    public static $levels = [
        'Staff',
        'Senior Staff',
        'Supervisor',
        'Coordinator',
        'Manager',
        'Head',
        'General Manager',
        'Director',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Division::class, 'division_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Position::class, 'parent_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class, 'position_id');
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(KPISetting::class, 'position_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeInDepartment($q, $departmentId)
    {
        return $q->where('department_id', $departmentId);
    }

    public function scopeInDivision($q, $divisionId)
    {
        return $q->where('division_id', $divisionId);
    }

    public function scopeOfLevel($q, $level)
    {
        return $q->where('level', $level);
    }

    /**
     * Get employee count
     */
    public function getEmployeeCountAttribute(): int
    {
        return $this->employees()->where('is_active', true)->count();
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

    /**
     * Get level badge class
     */
    public function getLevelBadgeClassAttribute(): string
    {
        return match($this->level) {
            'Director' => 'bg-purple-100 text-purple-700',
            'General Manager', 'Head' => 'bg-indigo-100 text-indigo-700',
            'Manager' => 'bg-blue-100 text-blue-700',
            'Coordinator', 'Supervisor' => 'bg-cyan-100 text-cyan-700',
            'Senior Staff' => 'bg-teal-100 text-teal-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
