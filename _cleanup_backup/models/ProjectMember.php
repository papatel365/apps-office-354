<?php

namespace App\Models;

use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'user_id',
        'role',
        'hourly_rate',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'added_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getFormattedHourlyRateAttribute(): string
    {
        return number_format($this->hourly_rate, 2);
    }

    // =====================================================
    // HELPERS
    // =====================================================

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }
}
