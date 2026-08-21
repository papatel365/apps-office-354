<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use App\Core\Traits\BelongsToTenant;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Training extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'description',
        'type',
        'provider',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'duration_hours',
        'max_participants',
        'cost',
        'cost_per_participant',
        'is_mandatory',
        'status',
        'objectives',
        'materials',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_hours' => 'decimal:2',
            'cost' => 'decimal:2',
            'cost_per_participant' => 'decimal:2',
            'is_mandatory' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    const STATUS_PLANNED = 'planned';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_INTERNAL = 'internal';
    const TYPE_EXTERNAL = 'external';
    const TYPE_ONLINE = 'online';

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PLANNED, self::STATUS_ONGOING]);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', today());
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_PLANNED => 'Direncanakan',
            self::STATUS_ONGOING => 'Sedang Berlangsung',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function getIsTodayAttribute(): bool
    {
        return $this->start_date && $this->start_date->isToday();
    }

    public function getFormattedCostAttribute(): string
    {
        return 'Rp ' . number_format($this->cost, 0, ',', '.');
    }
}
