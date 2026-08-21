<?php

namespace App\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetTransfer extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;
    use HasAuditLog;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'asset_id',
        'transfer_number',
        'transfer_type',
        'from_location',
        'to_location',
        'from_department',
        'to_department',
        'from_user_id',
        'to_user_id',
        'transfer_date',
        'status',
        'reason',
        'notes',
        'description',
        'photos',
        'initiated_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'approved_at' => 'datetime',
            'photos' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    const TYPE_LOCATION = 'location';
    const TYPE_DEPARTMENT = 'department';
    const TYPE_USER = 'user';

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeInTransit($query)
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getTransferTypeLabelAttribute(): string
    {
        $labels = [
            'location' => 'Lokasi',
            'department' => 'Departemen',
            'user' => 'Pengguna',
        ];

        return $labels[$this->transfer_type] ?? ucfirst($this->transfer_type);
    }

    public function getStatusBadgeAttribute(): array
    {
        $badges = [
            'pending' => ['type' => 'warning', 'text' => 'Menunggu'],
            'approved' => ['type' => 'info', 'text' => 'Disetujui'],
            'in_transit' => ['type' => 'primary', 'text' => 'Dalam Perjalanan'],
            'completed' => ['type' => 'success', 'text' => 'Selesai'],
            'cancelled' => ['type' => 'secondary', 'text' => 'Dibatalkan'],
        ];

        return $badges[$this->status] ?? ['type' => 'secondary', 'text' => ucfirst($this->status)];
    }

    /**
     * Get photo URLs.
     */
    public function getPhotoUrlsAttribute(): array
    {
        if (!$this->photos || !is_array($this->photos)) {
            return [];
        }

        return array_map(function ($photo) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($photo);
        }, $this->photos);
    }

    /**
     * Get the user who handed over the asset.
     */
    public function getHandedOverByAttribute(): ?User
    {
        return $this->fromUser;
    }

    /**
     * Get the user who received the asset.
     */
    public function getReceivedByAttribute(): ?User
    {
        return $this->toUser;
    }

    // =====================================================
    // HELPERS
    // =====================================================

    public function approve(): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function startTransit(): bool
    {
        return $this->update([
            'status' => self::STATUS_IN_TRANSIT,
        ]);
    }

    public function complete(): bool
    {
        $updated = $this->update([
            'status' => self::STATUS_COMPLETED,
        ]);

        if ($updated) {
            // Update asset location/user based on transfer type
            $asset = $this->asset;
            if ($this->transfer_type === self::TYPE_LOCATION) {
                $asset->update(['location' => $this->to_location]);
            }
        }

        return $updated;
    }

    public function cancel(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Generate next transfer number.
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        return \DB::transaction(function () use ($year, $month) {
            // Use lock to prevent race condition when multiple users create transfers simultaneously
            $lastTransfer = static::where('transfer_number', 'like', "TRF-{$year}{$month}-%")
                ->lockForUpdate()
                ->orderBy('transfer_number', 'desc')
                ->first();

            $sequence = 1;

            if ($lastTransfer) {
                // Extract sequence from transfer_number (e.g., "TRF-202607-0001" -> extract "0001" and add 1)
                $parts = explode('-', $lastTransfer->transfer_number);
                $sequence = isset($parts[2]) ? ((int) $parts[2]) + 1 : 1;
            }

            return sprintf('TRF-%s%s-%04d', $year, $month, $sequence);
        });
    }
}
