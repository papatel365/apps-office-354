<?php

namespace App\Models;

use App\Core\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class AssetActivity extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLog;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'company_id',
        'asset_id',
        'activity_type',
        'status',
        'start_date',
        'end_date',
        'data',
        'notes',
        'description',
        'created_by',
        // Allocation specific fields
        'sent_by',
        'received_by',
        'location',
        'sent_date',
        'received_date',
        // Reservation specific fields
        'used_by',
        'installation_location',
        'department',
        // Maintenance specific fields
        'maintenance_type',
        'technician',
        'cost',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'data' => 'array',
            'sent_date' => 'date',
            'received_date' => 'date',
            'cost' => 'decimal:4',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS - Activity Types
    // =====================================================

    const TYPE_ALLOCATION = 'allocation';
    const TYPE_CHECK_OUT = 'check_out';
    const TYPE_RESERVATION = 'reservation';
    const TYPE_CHECK_IN = 'check_in';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_RECALL = 'recall';
    const TYPE_MAINTENANCE = 'maintenance';

    // =====================================================
    // CONSTANTS - Status by Type
    // =====================================================

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';

    // Status labels
    const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_IN_TRANSIT => 'Dalam Perjalanan',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
        self::STATUS_SCHEDULED => 'Dijadwalkan',
        self::STATUS_IN_PROGRESS => 'Sedang Berlangsung',
    ];

    // Activity type labels
    const TYPE_LABELS = [
        self::TYPE_ALLOCATION => 'Alokasi',
        self::TYPE_CHECK_OUT => 'Check Out',
        self::TYPE_RESERVATION => 'Reservasi',
        self::TYPE_CHECK_IN => 'Check In',
        self::TYPE_TRANSFER => 'Transfer',
        self::TYPE_RECALL => 'Recall',
        self::TYPE_MAINTENANCE => 'Perawatan',
    ];

    // FontAwesome icons for each activity type
    const TYPE_ICONS = [
        self::TYPE_ALLOCATION => 'fa-solid fa-box',
        self::TYPE_CHECK_OUT => 'fa-solid fa-arrow-right-from-bracket',
        self::TYPE_RESERVATION => 'fa-solid fa-calendar-check',
        self::TYPE_CHECK_IN => 'fa-solid fa-arrow-right-to-bracket',
        self::TYPE_TRANSFER => 'fa-solid fa-truck',
        self::TYPE_RECALL => 'fa-solid fa-rotate-left',
        self::TYPE_MAINTENANCE => 'fa-solid fa-wrench',
    ];

    // Icon color classes for each activity type
    const TYPE_ICON_COLORS = [
        self::TYPE_ALLOCATION => 'text-blue-500',
        self::TYPE_CHECK_OUT => 'text-gray-500',
        self::TYPE_RESERVATION => 'text-purple-500',
        self::TYPE_CHECK_IN => 'text-gray-500',
        self::TYPE_TRANSFER => 'text-yellow-500',
        self::TYPE_RECALL => 'text-orange-500',
        self::TYPE_MAINTENANCE => 'text-amber-500',
    ];

    // Status colors
    const STATUS_COLORS = [
        self::STATUS_PENDING => 'gray',
        self::STATUS_ACTIVE => 'blue',
        self::STATUS_IN_TRANSIT => 'yellow',
        self::STATUS_COMPLETED => 'green',
        self::STATUS_CANCELLED => 'red',
        self::STATUS_SCHEDULED => 'purple',
        self::STATUS_IN_PROGRESS => 'amber',
    ];

    // Maintenance type labels
    const MAINTENANCE_TYPE_LABELS = [
        'preventive' => 'Pencegahan',
        'corrective' => 'Korektif',
        'inspection' => 'Inspeksi',
        'upgrade' => 'Peningkatan',
    ];

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

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeAllocations($query)
    {
        return $query->where('activity_type', self::TYPE_ALLOCATION);
    }

    public function scopeCheckOuts($query)
    {
        return $query->where('activity_type', self::TYPE_CHECK_OUT);
    }

    public function scopeReservations($query)
    {
        return $query->where('activity_type', self::TYPE_RESERVATION);
    }

    public function scopeCheckIns($query)
    {
        return $query->where('activity_type', self::TYPE_CHECK_IN);
    }

    public function scopeTransfers($query)
    {
        return $query->where('activity_type', self::TYPE_TRANSFER);
    }

    public function scopeRecalls($query)
    {
        return $query->where('activity_type', self::TYPE_RECALL);
    }

    public function scopeMaintenance($query)
    {
        return $query->where('activity_type', self::TYPE_MAINTENANCE);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeForAsset($query, int $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [
            self::STATUS_ACTIVE,
            self::STATUS_IN_TRANSIT,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    public function scopeActiveAllocations($query)
    {
        return $query->where('activity_type', self::TYPE_ALLOCATION)
            ->where('status', self::STATUS_ACTIVE);
    }

    public function scopeActiveReservations($query)
    {
        return $query->where('activity_type', self::TYPE_RESERVATION)
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_IN_PROGRESS]);
    }

    public function scopeActiveMaintenance($query)
    {
        return $query->where('activity_type', self::TYPE_MAINTENANCE)
            ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_IN_PROGRESS]);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get activity type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->activity_type] ?? ucfirst($this->activity_type);
    }

    /**
     * Get icon for this activity type.
     */
    public function getIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->activity_type] ?? 'fa-solid fa-circle';
    }

    /**
     * Get emoji for this activity type.
     */
    public function getEmojiAttribute(): string
    {
        return self::TYPE_EMOJIS[$this->activity_type] ?? '⚪';
    }

    /**
     * Get color for this activity type.
     */
    public function getColorAttribute(): string
    {
        $colors = [
            self::TYPE_ALLOCATION => 'blue',
            self::TYPE_RESERVATION => 'purple',
            self::TYPE_MAINTENANCE => 'amber',
        ];
        return $colors[$this->activity_type] ?? 'gray';
    }

    /**
     * Get icon color for this activity type.
     */
    public function getIconColorAttribute(): string
    {
        return self::TYPE_ICON_COLORS[$this->activity_type] ?? 'text-gray-500';
    }

    /**
     * Get status color.
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    /**
     * Get tooltip data for this activity.
     * Returns formatted data for display in tooltips.
     */
    public function getTooltipDataAttribute(): array
    {
        $data = [
            'type' => $this->type_label,
            'status' => $this->status_label,
            'dates' => $this->date_range,
        ];

        switch ($this->activity_type) {
            case self::TYPE_ALLOCATION:
                if ($this->sent_by) $data['Dikirim oleh'] = $this->sent_by;
                if ($this->received_by) $data['Diterima oleh'] = $this->received_by;
                if ($this->sent_date) $data['Tanggal Kirim'] = $this->sent_date->format('d M Y');
                if ($this->received_date) $data['Tanggal Terima'] = $this->received_date->format('d M Y');
                if ($this->location) $data['Lokasi'] = $this->location;
                break;

            case self::TYPE_RESERVATION:
                if ($this->used_by) $data['Digunakan oleh'] = $this->used_by;
                if ($this->installation_location) $data['Lokasi'] = $this->installation_location;
                if ($this->department) $data['Departemen'] = $this->department;
                if ($this->start_date) $data['Tanggal'] = $this->start_date->format('d M Y');
                break;

            case self::TYPE_MAINTENANCE:
                if ($this->maintenance_type) {
                    $data['Jenis'] = self::MAINTENANCE_TYPE_LABELS[$this->maintenance_type] ?? ucfirst($this->maintenance_type);
                }
                if ($this->technician) $data['Teknisi'] = $this->technician;
                if ($this->cost > 0) $data['Biaya'] = number_format($this->cost, 0, ',', '.');
                if ($this->start_date) $data['Mulai'] = $this->start_date->format('d M Y');
                if ($this->end_date) $data['Selesai'] = $this->end_date->format('d M Y');
                break;

            case self::TYPE_TRANSFER:
                if ($this->data['from_location'] ?? null) $data['Dari'] = $this->data['from_location'];
                if ($this->data['to_location'] ?? null) $data['Ke'] = $this->data['to_location'];
                break;

            case self::TYPE_CHECK_OUT:
                if ($this->data['user_id'] ?? null) $data['User ID'] = $this->data['user_id'];
                if ($this->data['from_location'] ?? null) $data['Dari'] = $this->data['from_location'];
                break;

            case self::TYPE_CHECK_IN:
                if ($this->data['user_id'] ?? null) $data['User ID'] = $this->data['user_id'];
                if ($this->data['to_location'] ?? null) $data['Ke'] = $this->data['to_location'];
                break;

            case self::TYPE_RECALL:
                if ($this->data['from_user_id'] ?? null) $data['Dari User ID'] = $this->data['from_user_id'];
                if ($this->data['reason'] ?? null) $data['Alasan'] = $this->data['reason'];
                break;
        }

        if ($this->notes) {
            $data['Catatan'] = $this->notes;
        }

        return $data;
    }

    /**
     * Get tooltip HTML for display.
     */
    public function getTooltipHtmlAttribute(): string
    {
        $data = $this->tooltip_data;
        $html = '<div class="text-left">';

        foreach ($data as $key => $value) {
            $html .= '<div class="flex justify-between gap-4">';
            $html .= '<span class="text-gray-500">' . $key . ':</span>';
            $html .= '<span class="font-medium text-gray-900">' . e($value) . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get compact tooltip for table display.
     */
    public function getCompactTooltipAttribute(): array
    {
        switch ($this->activity_type) {
            case self::TYPE_ALLOCATION:
                return [
                    'title' => '<i class="fa-solid fa-box text-blue-500"></i> Alokasi',
                    'lines' => array_filter([
                        $this->received_by ? 'Diterima: ' . $this->received_by : null,
                        $this->location ? 'Lokasi: ' . $this->location : null,
                        $this->received_date ? 'Tgl Terima: ' . $this->received_date->format('d M Y') : null,
                    ]),
                ];

            case self::TYPE_RESERVATION:
                return [
                    'title' => '<i class="fa-solid fa-calendar-check text-purple-500"></i> Reservasi',
                    'lines' => array_filter([
                        $this->used_by ? 'User: ' . $this->used_by : null,
                        $this->installation_location ? 'Lokasi: ' . $this->installation_location : null,
                        $this->department ? 'Dept: ' . $this->department : null,
                    ]),
                ];

            case self::TYPE_MAINTENANCE:
                return [
                    'title' => '<i class="fa-solid fa-wrench text-amber-500"></i> Maintenance',
                    'lines' => array_filter([
                        $this->maintenance_type ? 'Jenis: ' . (self::MAINTENANCE_TYPE_LABELS[$this->maintenance_type] ?? $this->maintenance_type) : null,
                        $this->technician ? 'Teknisi: ' . $this->technician : null,
                        $this->start_date ? 'Mulai: ' . $this->start_date->format('d M Y') : null,
                    ]),
                ];

            default:
                return [
                    'title' => '<i class="fa-solid ' . ($this->icon ?? 'fa-circle') . '"></i> ' . $this->type_label,
                    'lines' => [$this->status_label],
                ];
        }
    }

    /**
     * Check if activity is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if activity is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if activity is pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if activity is cancelled.
     */
    public function getIsCancelledAttribute(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Get related user from data.
     */
    public function getUserIdAttribute(): ?int
    {
        return $this->data['user_id'] ?? $this->data['to_user_id'] ?? null;
    }

    /**
     * Get related department from data.
     */
    public function getDepartmentIdAttribute(): ?int
    {
        return $this->data['to_department_id'] ?? null;
    }

    /**
     * Get location from data.
     */
    public function getLocationAttribute(): ?string
    {
        return $this->data['to_location'] ?? $this->data['location'] ?? null;
    }

    /**
     * Get duration in days.
     */
    public function getDurationDaysAttribute(): ?int
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }
        return $this->start_date->diffInDays($this->end_date);
    }

    // =====================================================
    // HELPERS
    // =====================================================

    /**
     * Mark activity as completed.
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'end_date' => now(),
        ]);
    }

    /**
     * Mark activity as cancelled.
     */
    public function cancel(?string $reason = null): void
    {
        $data = $this->data ?? [];
        if ($reason) {
            $data['cancellation_reason'] = $reason;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'end_date' => now(),
            'data' => $data,
        ]);
    }

    /**
     * Mark activity as active.
     */
    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'start_date' => $this->start_date ?? now(),
        ]);
    }

    /**
     * Start transit (for transfers).
     */
    public function startTransit(): void
    {
        $this->update([
            'status' => self::STATUS_IN_TRANSIT,
        ]);
    }

    /**
     * Resolve a valid tenant_id value.
     * If the value doesn't exist in the tenants table, return null.
     * This prevents FK constraint violations in audit_logs.
     */
    protected static function resolveTenantId(?int $candidateId): ?int
    {
        if ($candidateId === null) {
            return null;
        }

        // Cache valid tenant IDs for performance
        static $validTenantIds = null;
        if ($validTenantIds === null) {
            $validTenantIds = \Illuminate\Support\Facades\DB::table('tenants')->pluck('id')->toArray();
        }

        return in_array($candidateId, $validTenantIds) ? $candidateId : null;
    }

    /**
     * Resolve tenant_id and company_id from various sources.
     * Returns ['tenant_id' => int|null, 'company_id' => int|null]
     */
    protected static function resolveTenantAndCompany(?int $explicitTenantId, ?int $explicitCompanyId, ?int $assetId): array
    {
        $asset = $assetId ? Asset::find($assetId) : null;
        $user = auth()->user();

        // tenant_id: explicit > asset > company > user
        $candidateTenantId = $explicitTenantId
            ?? $asset?->tenant_id
            ?? $asset?->company_id
            ?? $user?->tenant_id
            ?? $user?->company_id;

        // company_id: explicit > asset > user
        $candidateCompanyId = $explicitCompanyId
            ?? $asset?->company_id
            ?? $user?->company_id;

        return [
            'tenant_id' => self::resolveTenantId($candidateTenantId),
            'company_id' => $candidateCompanyId,
        ];
    }

    /**
     * Create an allocation activity with full fields.
     * Does NOT change base status.
     */
    public static function createAllocation(array $data): self
    {
        $resolved = self::resolveTenantAndCompany(
            $data['tenant_id'] ?? null,
            $data['company_id'] ?? null,
            $data['asset_id'] ?? null
        );

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $resolved['tenant_id'],
            'company_id' => $resolved['company_id'],
            'asset_id' => $data['asset_id'],
            'activity_type' => self::TYPE_ALLOCATION,
            'status' => self::STATUS_ACTIVE,
            'start_date' => $data['sent_date'] ?? now(),
            'sent_by' => $data['sent_by'] ?? null,
            'received_by' => $data['received_by'] ?? null,
            'location' => $data['location'] ?? null,
            'sent_date' => $data['sent_date'] ?? null,
            'received_date' => $data['received_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'description' => $data['description'] ?? 'Asset dialokasikan',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Create a reservation activity with full fields.
     * Does NOT change base status.
     */
    public static function createReservation(array $data): self
    {
        $resolved = self::resolveTenantAndCompany(
            $data['tenant_id'] ?? null,
            $data['company_id'] ?? null,
            $data['asset_id'] ?? null
        );

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $resolved['tenant_id'],
            'company_id' => $resolved['company_id'],
            'asset_id' => $data['asset_id'],
            'activity_type' => self::TYPE_RESERVATION,
            'status' => self::STATUS_ACTIVE,
            'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? null,
            'used_by' => $data['used_by'] ?? null,
            'installation_location' => $data['installation_location'] ?? null,
            'department' => $data['department'] ?? null,
            'notes' => $data['notes'] ?? null,
            'description' => $data['description'] ?? 'Asset direservasi',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Create a maintenance activity with full fields.
     * Adds MAINTENANCE status WITHOUT removing available or other active statuses.
     * Asset now has: available + maintenance (or available + allocated + maintenance)
     */
    public static function createMaintenance(array $data): self
    {
        $asset = !empty($data['asset_id']) ? Asset::find($data['asset_id']) : null;
        $resolved = self::resolveTenantAndCompany(
            $data['tenant_id'] ?? null,
            $data['company_id'] ?? null,
            $data['asset_id'] ?? null
        );

        $activity = self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $resolved['tenant_id'],
            'company_id' => $resolved['company_id'],
            'asset_id' => $data['asset_id'],
            'activity_type' => self::TYPE_MAINTENANCE,
            'status' => self::STATUS_SCHEDULED,
            'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? null,
            'maintenance_type' => $data['maintenance_type'] ?? 'preventive',
            'technician' => $data['technician'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'description' => $data['description'] ?? 'Maintenance dijadwalkan',
            'created_by' => auth()->id(),
        ]);

        // Only set current_maintenance_id reference - DO NOT change base status
        // Available remains as base status, maintenance is ADDED
        if ($asset) {
            $asset->update([
                'current_maintenance_id' => $activity->id,
                // REMOVED: 'status' => Asset::STATUS_MAINTENANCE - NO LONGER OVERWRITES
            ]);
        }

        return $activity;
    }

    /**
     * Check if this activity allows modification.
     */
    public function canModify(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Get the user associated with this activity.
     */
    public function getUser(): ?User
    {
        $userId = $this->user_id;
        if (!$userId) {
            return null;
        }
        return User::find($userId);
    }

    /**
     * Get formatted date range.
     */
    public function getDateRangeAttribute(): string
    {
        if (!$this->start_date) {
            return '-';
        }

        $start = $this->start_date->format('d M Y');

        if (!$this->end_date) {
            return $start . ' - Ongoing';
        }

        return $start . ' - ' . $this->end_date->format('d M Y');
    }
}
