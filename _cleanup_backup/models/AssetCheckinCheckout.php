<?php

namespace App\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCheckinCheckout extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use HasAuditLog;

    protected $table = 'asset_checkin_checkout';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'asset_id',
        'user_id',
        'action',
        'checkout_date',
        'checkin_date',
        'checkout_condition',
        'checkin_condition',
        'checkout_notes',
        'checkin_notes',
        'location',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'checkout_date' => 'datetime',
            'checkin_date' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    const ACTION_CHECKOUT = 'check_out';
    const ACTION_CHECKIN = 'check_in';

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeCheckouts($query)
    {
        return $query->where('action', self::ACTION_CHECKOUT);
    }

    public function scopeCheckins($query)
    {
        return $query->where('action', self::ACTION_CHECKIN);
    }

    public function scopeByAsset($query, int $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('checkout_date', [$startDate, $endDate]);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getIsCheckoutAttribute(): bool
    {
        return $this->action === self::ACTION_CHECKOUT;
    }

    public function getIsCheckinAttribute(): bool
    {
        return $this->action === self::ACTION_CHECKIN;
    }

    public function getActionLabelAttribute(): string
    {
        return $this->action === self::ACTION_CHECKOUT ? 'Check Out' : 'Check In';
    }

    // =====================================================
    // HELPERS
    // =====================================================

    public static function recordCheckout(Asset $asset, User $user, array $data = []): self
    {
        $record = static::create(array_merge([
            'tenant_id' => $asset->tenant_id,
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'action' => self::ACTION_CHECKOUT,
            'checkout_date' => now(),
            'checkout_condition' => $data['condition'] ?? 'good',
            'checkout_notes' => $data['notes'] ?? null,
            'location' => $data['location'] ?? null,
            'created_by' => auth()->id(),
        ], $data));

        $asset->update(['status' => Asset::STATUS_ASSIGNED]);

        return $record;
    }

    public static function recordCheckin(Asset $asset, User $user, array $data = []): self
    {
        $record = static::create(array_merge([
            'tenant_id' => $asset->tenant_id,
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'action' => self::ACTION_CHECKIN,
            'checkin_date' => now(),
            'checkin_condition' => $data['condition'] ?? 'good',
            'checkin_notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ], $data));

        $asset->update(['status' => Asset::STATUS_AVAILABLE]);

        return $record;
    }
}
