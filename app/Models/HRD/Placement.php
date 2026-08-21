<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Placement extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'employee_placements';

    protected $fillable = [
        'company_id',
        'created_by',
        'uuid',
        'name',
        'code',
        'type',
        'address',
        'city',
        'province',
        'latitude',
        'longitude',
        'radius_meters',
        'is_active',
        'sort_order',
        'description',
        'pic_name',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'radius_meters' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Location types
     */
    public static $types = [
        'kantor_pusat' => 'Kantor Pusat',
        'cabang' => 'Cabang',
        'site' => 'Site',
        'gudang' => 'Gudang',
    ];

    // Boot method to auto-generate UUID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($placement) {
            if (empty($placement->uuid)) {
                $placement->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get employees assigned to this placement.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class, 'placement_id');
    }

    /**
     * Get active employees count.
     */
    public function getActiveEmployeesCountAttribute(): int
    {
        return $this->employees()->where('is_active', true)->count();
    }

    /**
     * Scope for active placements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if coordinates are within radius.
     */
    public function isWithinRadius(float $lat, float $lng): bool
    {
        if (!$this->latitude || !$this->longitude) {
            return true; // No coordinates set, allow all
        }

        $distance = $this->calculateDistance($this->latitude, $this->longitude, $lat, $lng);
        return $distance <= $this->radius_meters;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get distance from a point in meters.
     */
    public function getDistanceFrom(float $lat, float $lng): float
    {
        return $this->calculateDistance($this->latitude ?? 0, $this->longitude ?? 0, $lat, $lng);
    }

    /**
     * Get formatted address.
     */
    public function getFormattedAddressAttribute(): ?string
    {
        $parts = array_filter([$this->address, $this->city]);
        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->is_active
            ? 'bg-green-100 text-green-700'
            : 'bg-gray-100 text-gray-700';
    }

    /**
     * Get type badge class.
     */
    public function getTypeBadgeClassAttribute(): string
    {
        return match($this->type) {
            'kantor_pusat' => 'bg-indigo-100 text-indigo-700',
            'cabang' => 'bg-blue-100 text-blue-700',
            'site' => 'bg-amber-100 text-amber-700',
            'gudang' => 'bg-stone-100 text-stone-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
