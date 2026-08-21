<?php

namespace App\Models\HRD;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'head_id',
        'parent_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Default departments for seeding
     */
    public static function defaults(): array
    {
        return [
            ['code' => 'HRGAL', 'name' => 'HRGA & LEGAL', 'description' => 'Human Resources, General Affairs & Legal'],
            ['code' => 'SLS', 'name' => 'SALES & MARKETING', 'description' => 'Sales & Marketing'],
            ['code' => 'DEV', 'name' => 'DEVOPS', 'description' => 'Development & Operations'],
            ['code' => 'FIN', 'name' => 'FINANCE', 'description' => 'Finance & Accounting'],
            ['code' => 'INF', 'name' => 'INFRATEL NOC & SUPPORT', 'description' => 'Infrastructure, NOC & Support'],
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'head_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class, 'department_id');
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(\App\Models\Division::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeForCompany($q, int $companyId)
    {
        return $q->where('company_id', $companyId);
    }

    /**
     * Get divisions for dropdown filtering
     */
    public function getDivisionsList(): array
    {
        return $this->divisions()->active()->pluck('name', 'id')->toArray();
    }

    /**
     * Get employee count
     */
    public function getEmployeeCountAttribute(): int
    {
        return $this->employees()->where('is_active', true)->count();
    }

    /**
     * Get total employee count including divisions
     */
    public function getTotalEmployeeCountAttribute(): int
    {
        $directCount = $this->employee_count;
        $divisionCount = $this->divisions()->withCount(['employees' => function ($q) {
            $q->where('is_active', true);
        }])->get()->sum('employees_count');
        return $directCount + $divisionCount;
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
