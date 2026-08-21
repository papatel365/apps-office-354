<?php

namespace App\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAllocation extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use HasAuditLog;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'asset_id',
        'user_id',
        'allocated_by',
        'allocation_date',
        'return_due_date',
        'return_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'allocation_date' => 'date',
            'return_due_date' => 'date',
            'return_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    const STATUS_ACTIVE = 'active';
    const STATUS_RETURNED = 'returned';
    const STATUS_OVERDUE = 'overdue';

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allocator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeReturned($query)
    {
        return $query->where('status', self::STATUS_RETURNED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('return_due_date')
            ->where('return_due_date', '<', now());
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAsset($query, int $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE &&
            $this->return_due_date &&
            $this->return_due_date->isPast();
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->return_due_date) {
            return null;
        }

        return now()->diffInDays($this->return_due_date, false);
    }

    public function getStatusBadgeAttribute(): array
    {
        $badges = [
            'active' => ['type' => 'success', 'text' => 'Aktif'],
            'returned' => ['type' => 'secondary', 'text' => 'Dikembalikan'],
            'overdue' => ['type' => 'danger', 'text' => 'Terlambat'],
        ];

        return $badges[$this->status] ?? ['type' => 'secondary', 'text' => ucfirst($this->status)];
    }

    // =====================================================
    // HELPERS
    // =====================================================

    public function return(): bool
    {
        $updated = $this->update([
            'return_date' => now(),
            'status' => self::STATUS_RETURNED,
        ]);

        if ($updated) {
            $this->asset->update(['status' => Asset::STATUS_AVAILABLE]);
        }

        return $updated;
    }
}
