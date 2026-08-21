<?php

namespace App\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetReservation extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;
    use HasAuditLog;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'asset_id',
        'user_id',
        'reservation_number',
        'start_date',
        'end_date',
        'status',
        'purpose',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FULFILLED = 'fulfilled';

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

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('start_date', '<=', now()->addDays($days))
            ->where('start_date', '>=', now())
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getDaysUntilStartAttribute(): ?int
    {
        if (!$this->start_date) {
            return null;
        }

        return now()->diffInDays($this->start_date, false);
    }

    public function getStatusBadgeAttribute(): array
    {
        $badges = [
            'pending' => ['type' => 'warning', 'text' => 'Menunggu'],
            'approved' => ['type' => 'success', 'text' => 'Disetujui'],
            'rejected' => ['type' => 'danger', 'text' => 'Ditolak'],
            'cancelled' => ['type' => 'secondary', 'text' => 'Dibatalkan'],
            'fulfilled' => ['type' => 'info', 'text' => 'Selesai'],
        ];

        return $badges[$this->status] ?? ['type' => 'secondary', 'text' => ucfirst($this->status)];
    }

    // =====================================================
    // HELPERS
    // =====================================================

    public function approve(): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
        ]);
    }

    public function reject(): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
        ]);
    }

    public function cancel(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public function fulfill(): bool
    {
        return $this->update([
            'status' => self::STATUS_FULFILLED,
        ]);
    }

    /**
     * Generate next reservation number.
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        return \DB::transaction(function () use ($year, $month) {
            // Use lock to prevent race condition when multiple users create reservations simultaneously
            $lastReservation = static::where('reservation_number', 'like', "RSV-{$year}{$month}-%")
                ->lockForUpdate()
                ->orderBy('reservation_number', 'desc')
                ->first();

            $sequence = 1;

            if ($lastReservation) {
                // Extract sequence from reservation_number (e.g., "RSV-202607-0001" -> extract "0001" and add 1)
                $parts = explode('-', $lastReservation->reservation_number);
                $sequence = isset($parts[2]) ? ((int) $parts[2]) + 1 : 1;
            }

            return sprintf('RSV-%s%s-%04d', $year, $month, $sequence);
        });
    }
}
