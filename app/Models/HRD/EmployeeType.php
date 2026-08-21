<?php

namespace App\Models\HRD;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Default employee types for seeding
     */
    public static function defaults(): array
    {
        return [
            [
                'name' => 'Karyawan Tetap',
                'code' => 'TETAP',
                'description' => 'Karyawan dengan status tetap/permanen',
                'color' => '#22C55E', // green
                'sort_order' => 1,
            ],
            [
                'name' => 'Karyawan Kontrak',
                'code' => 'KONTRAK',
                'description' => 'Karyawan dengan perjanjian kerja tertentu',
                'color' => '#F59E0B', // amber
                'sort_order' => 2,
            ],
            [
                'name' => 'Masa Percobaan',
                'code' => 'PERCOBAAN',
                'description' => 'Karyawan dalam masa percobaan',
                'color' => '#8B5CF6', // purple
                'sort_order' => 3,
            ],
            [
                'name' => 'Magang',
                'code' => 'MAGANG',
                'description' => 'Karyawan magang/praktik kerja',
                'color' => '#3B82F6', // blue
                'sort_order' => 4,
            ],
            [
                'name' => 'Freelance',
                'code' => 'FREELANCE',
                'description' => 'Karyawan bebas/freelance',
                'color' => '#EC4899', // pink
                'sort_order' => 5,
            ],
            [
                'name' => 'Outsource',
                'code' => 'OUTSOURCE',
                'description' => 'Karyawan pihak ketiga/outsourcing',
                'color' => '#6366F1', // indigo
                'sort_order' => 6,
            ],
            [
                'name' => 'Part Time',
                'code' => 'PARTTIME',
                'description' => 'Karyawan paruh waktu',
                'color' => '#14B8A6', // teal
                'sort_order' => 7,
            ],
        ];
    }

    /**
     * Get the company that owns the employee type.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all employees with this type (using employee_type_id foreign key).
     */
    public function employees(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class, 'employee_type_id');
    }

    /**
     * Get employee count with this type.
     */
    public function getEmployeeCountAttribute(): int
    {
        return $this->employees()->count();
    }

    /**
     * Get active employee count with this type.
     */
    public function getActiveEmployeeCountAttribute(): int
    {
        return $this->employees()->where('is_active', true)->count();
    }

    /**
     * Scope a query to only include active types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include types for a specific company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to order by sort_order.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->is_active
            ? 'bg-green-100 text-green-700'
            : 'bg-gray-100 text-gray-700';
    }

    /**
     * Check if this type can be deleted (not used by any employee).
     */
    public function canBeDeleted(): bool
    {
        return $this->employees()->count() === 0;
    }

    /**
     * Get the employee count message for deletion.
     */
    public function getDeletionMessage(): string
    {
        $count = $this->employees()->count();
        return "Tipe karyawan \"{$this->name}\" masih digunakan oleh {$count} karyawan.";
    }
}
