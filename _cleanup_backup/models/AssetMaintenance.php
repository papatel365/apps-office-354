<?php

namespace App\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use HasAuditLog;

    protected $table = 'asset_maintenance';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'asset_id',
        'maintenance_type',
        'title',
        'description',
        'scheduled_date',
        'completed_date',
        'performed_by',
        'cost',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'completed_date' => 'date',
            'cost' => 'decimal:4',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    const TYPE_PREVENTIVE = 'preventive';
    const TYPE_CORRECTIVE = 'corrective';
    const TYPE_INSPECTION = 'inspection';
    const TYPE_UPGRADE = 'upgrade';

    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('scheduled_date', '<=', now()->addDays($days))
            ->where('scheduled_date', '>=', now())
            ->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
            ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_IN_PROGRESS]);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getFormattedCostAttribute(): string
    {
        return number_format($this->cost, 2);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->scheduled_date->isPast() &&
            !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'preventive' => 'Pencegahan',
            'corrective' => 'Korektif',
            'inspection' => 'Inspeksi',
            'upgrade' => 'Peningkatan',
        ];

        return $labels[$this->maintenance_type] ?? ucfirst($this->maintenance_type);
    }

    public function getStatusBadgeAttribute(): array
    {
        $badges = [
            'scheduled' => ['type' => 'info', 'text' => 'Dijadwalkan'],
            'in_progress' => ['type' => 'warning', 'text' => 'Berlangsung'],
            'completed' => ['type' => 'success', 'text' => 'Selesai'],
            'cancelled' => ['type' => 'secondary', 'text' => 'Dibatalkan'],
        ];

        return $badges[$this->status] ?? ['type' => 'secondary', 'text' => ucfirst($this->status)];
    }

    // =====================================================
    // HELPERS
    // =====================================================

    public function start(): bool
    {
        return $this->update([
            'status' => self::STATUS_IN_PROGRESS,
        ]);
    }

    public function complete(): bool
    {
        $updated = $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_date' => now(),
        ]);

        if ($updated) {
            $this->asset->update(['status' => Asset::STATUS_AVAILABLE]);
        }

        return $updated;
    }

    public function cancel(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_PREVENTIVE => 'Pencegahan',
            self::TYPE_CORRECTIVE => 'Korektif',
            self::TYPE_INSPECTION => 'Inspeksi',
            self::TYPE_UPGRADE => 'Peningkatan',
        ];
    }
}
