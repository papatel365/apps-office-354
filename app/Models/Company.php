<?php

namespace App\Models;

use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'short_name',
        'tagline',
        'footer_text',
        'address',
        'phone',
        'email',
        'website',
        'npwp',
        'logo',
        'favicon',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Always include these accessors in JSON/API responses
    protected $appends = ['logo_url', 'favicon_url'];

    // Accessors for file URLs - using Storage facade for proper Laravel storage abstraction
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return Storage::disk('public')->url($this->logo);
        }
        return null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        if ($this->favicon) {
            return Storage::disk('public')->url($this->favicon);
        }
        return null;
    }

    // Boot method to auto-generate UUID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->uuid)) {
                $company->uuid = (string) Str::uuid();
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\Department::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\Position::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function employeeProfiles(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\EmployeeProfile::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\Placement::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\Shift::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\Leave::class);
    }

    public function leaveTypes(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\LeaveType::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\Salary::class);
    }

    public function assetCategories(): HasMany
    {
        return $this->hasMany(AssetCategory::class);
    }

    public function getTotalStaffAttribute(): int
    {
        return $this->users()->count();
    }

    public function getActiveStaffAttribute(): int
    {
        return $this->users()->where('is_active', true)->count();
    }
}
