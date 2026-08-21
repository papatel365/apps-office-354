<?php

namespace App\Models;

use App\Core\Traits\BelongsToCompany;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasActivityLog;
use App\Core\Traits\HasAuditLog;
use App\Modules\System\Models\User;
use App\Traits\NotifiableActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasAuditLog;
    use HasActivityLog;
    use NotifiableActivity;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'company_id',
        'asset_number',
        'name',
        'description',
        'category_id',
        'product_name',
        'access_type',
        'serial_number',
        'barcode',
        'qr_code',
        'qr_identifier',
        'purchase_date',
        'purchase_cost',
        'warranty_expires',
        'expected_lifespan_months',
        'depreciation_method',
        'salvage_value',
        'current_value',
        'status',
        'allocated_to',
        'maintenance_duration_days',
        'maintenance_start_date',
        'maintenance_end_date',
        'location',
        'notes',
        'image_path',
        'thumbnail_path',
        'original_filename',
        'file_size',
        'mime_type',
        'custom_fields',
        // License fields
        'license_name',
        'license_key',
        'license_vendor',
        'license_start_date',
        'license_end_date',
        // IPAM fields
        'ipam_ip_address',
        'ipam_subnet',
        'ipam_gateway',
        'ipam_vlan',
        'ipam_hostname',
        'ipam_network',
        // Upass fields
        'access_username',
        'access_password',
        // Warranty card
        'warranty_card_path',
        'warranty_card_original_name',
        // Tracking
        'current_location',
        'current_department',
        'current_allocation_id',
        'current_reservation_id',
        'current_maintenance_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_expires' => 'date',
            'purchase_cost' => 'decimal:4',
            'salvage_value' => 'decimal:4',
            'current_value' => 'decimal:4',
            'expected_lifespan_months' => 'integer',
            'maintenance_duration_days' => 'integer',
            'maintenance_start_date' => 'date',
            'maintenance_end_date' => 'date',
            'custom_fields' => 'array',
            // License dates
            'license_start_date' => 'date',
            'license_end_date' => 'date',
            // Encrypted sensitive fields
            'access_password' => 'encrypted',
            'license_key' => 'encrypted',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS - WORKFLOW STATUS
    // Status based on active activities/tracking
    // =====================================================

    const STATUS_ALLOCATED = 'allocated';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_RESERVED = 'reserved';

    // Legacy statuses (for backward compatibility)
    const STATUS_AVAILABLE = 'available';
    const STATUS_RETIRED = 'retired';
    const STATUS_LOST = 'lost';
    const STATUS_STOLEN = 'stolen';

    // Status labels (Indonesian)
    const STATUS_LABELS = [
        self::STATUS_ALLOCATED => 'Dialokasikan',
        self::STATUS_MAINTENANCE => 'Maintenance',
        self::STATUS_RESERVED => 'Direservasi',
        // Available is always present
        self::STATUS_AVAILABLE => 'Tersedia',
        // Legacy - deprecated
        self::STATUS_RETIRED => 'Ditarik',
        self::STATUS_LOST => 'Hilang',
        self::STATUS_STOLEN => 'Dicuri',
    ];

    // Status FontAwesome icons
    const STATUS_ICONS = [
        self::STATUS_AVAILABLE => 'fa-check-circle',    // Tersedia
        self::STATUS_ALLOCATED => 'fa-box',             // Dialokasikan
        self::STATUS_RESERVED => 'fa-calendar-check',   // Direservasi
        self::STATUS_MAINTENANCE => 'fa-wrench',         // Maintenance
        // Legacy
        self::STATUS_RETIRED => 'fa-circle',
        self::STATUS_LOST => 'fa-circle',
        self::STATUS_STOLEN => 'fa-lock',
    ];

    // Status Colors (Tailwind)
    const STATUS_COLORS = [
        self::STATUS_AVAILABLE => 'green',
        self::STATUS_ALLOCATED => 'blue',
        self::STATUS_RESERVED => 'purple',
        self::STATUS_MAINTENANCE => 'amber',
        // Legacy
        self::STATUS_RETIRED => 'gray',
        self::STATUS_LOST => 'red',
        self::STATUS_STOLEN => 'red',
    ];

    // Status badge classes (Tailwind)
    const STATUS_BADGE_CLASSES = [
        self::STATUS_AVAILABLE => 'bg-green-100 text-green-800 border-green-200',
        self::STATUS_ALLOCATED => 'bg-blue-100 text-blue-800 border-blue-200',
        self::STATUS_RESERVED => 'bg-purple-100 text-purple-800 border-purple-200',
        self::STATUS_MAINTENANCE => 'bg-amber-100 text-amber-800 border-amber-200',
        // Legacy
        self::STATUS_RETIRED => 'bg-gray-100 text-gray-600 border-gray-200',
        self::STATUS_LOST => 'bg-red-100 text-red-800 border-red-200',
        self::STATUS_STOLEN => 'bg-red-100 text-red-800 border-red-200',
    ];

    // Status icon colors (Tailwind text)
    const STATUS_ICON_COLORS = [
        self::STATUS_AVAILABLE => 'text-green-500',
        self::STATUS_ALLOCATED => 'text-blue-500',
        self::STATUS_RESERVED => 'text-purple-500',
        self::STATUS_MAINTENANCE => 'text-amber-500',
        // Legacy
        self::STATUS_RETIRED => 'text-gray-400',
        self::STATUS_LOST => 'text-red-500',
        self::STATUS_STOLEN => 'text-red-500',
    ];

    /**
     * Get valid workflow statuses for dropdown
     */
    public static function getWorkflowStatuses(): array
    {
        return [
            self::STATUS_ALLOCATED => 'Allocated',
            self::STATUS_MAINTENANCE => 'Maintenance',
            self::STATUS_RESERVED => 'Reserved',
        ];
    }

    /**
     * Get all valid status VALUES (keys only) for validation rules.
     * Used by AssetRequest to ensure consistency between frontend and backend.
     *
     * @return array Array of valid status strings
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_AVAILABLE,
            self::STATUS_ALLOCATED,
            self::STATUS_RESERVED,
            self::STATUS_MAINTENANCE,
            // Legacy statuses (kept for backward compatibility)
            self::STATUS_RETIRED,
            self::STATUS_LOST,
            self::STATUS_STOLEN,
        ];
    }

    /**
     * Get all valid status VALUES for IN OPERATOR (comma-separated string for validation rules).
     * Example: 'available,allocated,reserved,maintenance'
     *
     * @return string Comma-separated valid status values
     */
    public static function getValidStatusesString(): string
    {
        return implode(',', self::getValidStatuses());
    }

    // Activity type labels (Indonesian)
    const ACTIVITY_LABELS = [
        'allocation' => 'Alokasi',
        'check_out' => 'Check Out',
        'reservation' => 'Reservasi',
        'check_in' => 'Check In',
        'transfer' => 'Transfer',
        'recall' => 'Recall',
        'maintenance' => 'Perawatan',
    ];

    // Activity FontAwesome icons
    const ACTIVITY_ICONS = [
        'allocation' => 'fa-box',
        'check_out' => 'fa-arrow-right-from-bracket',
        'reservation' => 'fa-calendar-check',
        'check_in' => 'fa-arrow-right-to-bracket',
        'transfer' => 'fa-truck',
        'recall' => 'fa-rotate-left',
        'maintenance' => 'fa-wrench',
    ];

    // Activity Colors (Tailwind)
    const ACTIVITY_COLORS = [
        'allocation' => 'blue',
        'check_out' => 'gray',
        'reservation' => 'purple',
        'check_in' => 'gray',
        'transfer' => 'yellow',
        'recall' => 'orange',
        'maintenance' => 'amber',
    ];

    const DEPRECIATION_NONE = 'none';
    const DEPRECIATION_STRAIGHT_LINE = 'straight_line';
    const DEPRECIATION_DECLINING_BALANCE = 'declining_balance';

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // NEW: Photos relationship (multi-photo support)
    public function photos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssetPhoto::class)->ordered();
    }

    // NEW: Activities timeline
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssetActivity::class)->orderBy('created_at', 'desc');
    }

    // NEW: Get cover photo
    public function coverPhoto(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AssetPhoto::class)->where('is_cover', true);
    }

    // NEW: Warranty card
    public function warrantyCard(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AssetWarrantyCard::class);
    }

    // Legacy relationships (kept for backward compatibility)
    public function allocations(): HasMany
    {
        return $this->hasMany(AssetAllocation::class);
    }

    public function checkinCheckouts(): HasMany
    {
        return $this->hasMany(AssetCheckinCheckout::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(AssetReservation::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class, 'asset_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeAllocated($query)
    {
        return $query->whereNotNull('current_allocation_id');
    }

    public function scopeReserved($query)
    {
        return $query->whereNotNull('current_reservation_id');
    }

    public function scopeInMaintenance($query)
    {
        return $query->whereNotNull('current_maintenance_id');
    }

    public function scopeRetired($query)
    {
        return $query->where('status', self::STATUS_RETIRED);
    }

    public function scopeLost($query)
    {
        return $query->where('status', self::STATUS_LOST);
    }

    public function scopeStolen($query)
    {
        return $query->where('status', self::STATUS_STOLEN);
    }

    // NEW: Scopes for tracking
    public function scopeWithActiveActivities($query)
    {
        return $query->whereNotNull('current_allocation_id')
            ->orWhereNotNull('current_reservation_id')
            ->orWhereNotNull('current_maintenance_id');
    }

    public function scopeWithActiveAllocation($query)
    {
        return $query->whereNotNull('current_allocation_id');
    }

    public function scopeWithActiveReservation($query)
    {
        return $query->whereNotNull('current_reservation_id');
    }

    public function scopeWithActiveMaintenance($query)
    {
        return $query->where('status', self::STATUS_MAINTENANCE);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByLocation($query, string $location)
    {
        return $query->where('location', $location);
    }

    public function scopeByStatus($query, string $status)
    {
        // For multi-status system, filter based on derived statuses
        return match($status) {
            'available' => $query->where('status', self::STATUS_AVAILABLE),
            'allocated' => $query->whereNotNull('current_allocation_id'),
            'reserved' => $query->whereNotNull('current_reservation_id'),
            'maintenance' => $query->whereNotNull('current_maintenance_id'),
            // Legacy statuses
            'retired' => $query->where('status', self::STATUS_RETIRED),
            'lost' => $query->where('status', self::STATUS_LOST),
            'stolen' => $query->where('status', self::STATUS_STOLEN),
            default => $query->where('status', $status),
        };
    }

    /**
     * Scope for filtering by multiple statuses (multi-status OR logic).
     */
    public function scopeWithAnyStatus($query, array $statuses)
    {
        $conditions = [];
        foreach ($statuses as $status) {
            match($status) {
                'available' => $conditions[] = ['status', self::STATUS_AVAILABLE],
                'allocated' => $conditions[] = ['current_allocation_id', '!=', null],
                'reserved' => $conditions[] = ['current_reservation_id', '!=', null],
                'maintenance' => $conditions[] = ['current_maintenance_id', '!=', null],
                'retired' => $conditions[] = ['status', self::STATUS_RETIRED],
                'lost' => $conditions[] = ['status', self::STATUS_LOST],
                'stolen' => $conditions[] = ['status', self::STATUS_STOLEN],
                default => null,
            };
        }

        foreach ($conditions as $condition) {
            if ($condition) {
                $query->orWhere(...$condition);
            }
        }

        return $query;
    }

    public function scopeWarrantyExpired($query)
    {
        return $query->whereNotNull('warranty_expires')
            ->where('warranty_expires', '<', now());
    }

    public function scopeWarrantyExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('warranty_expires')
            ->where('warranty_expires', '<=', now()->addDays($days))
            ->where('warranty_expires', '>=', now());
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    /**
     * Get the full URL to the asset photo.
     * Returns the main image or thumbnail if available.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->image_path) {
            return Storage::disk('public')->url($this->image_path);
        }
        return null;
    }

    /**
     * Get the URL to the thumbnail photo.
     * Falls back to main image if thumbnail .
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }
        return $this->photo_url;
    }

    /**
     * Check if asset has a photo.
     */
    public function getHasPhotoAttribute(): bool
    {
        return !empty($this->image_path);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFormattedPurchaseCostAttribute(): string
    {
        // Indonesian format with dot as thousand separator, no decimal places
        return number_format($this->purchase_cost, 0, ',', '.');
    }

    public function getFormattedCurrentValueAttribute(): string
    {
        // Indonesian format with dot as thousand separator, no decimal places
        return number_format($this->current_value, 0, ',', '.');
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function getIsAssignedAttribute(): bool
    {
        // For backward compatibility - check if has active allocation
        return $this->current_allocation_id !== null;
    }

    public function getIsUnderWarrantyAttribute(): bool
    {
        return $this->warranty_expires && $this->warranty_expires->isFuture();
    }

    public function getIsExpiredWarrantyAttribute(): bool
    {
        return $this->warranty_expires && $this->warranty_expires->isPast();
    }

    public function getDaysUntilWarrantyExpiryAttribute(): ?int
    {
        if (!$this->warranty_expires) {
            return null;
        }

        return now()->diffInDays($this->warranty_expires, false);
    }

    // =====================================================
    // NEW ACCESSORS - Activity Based Status
    // =====================================================

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get all active statuses as an array.
     * 'available' is ALWAYS present - represents asset existence in system.
     * Other statuses are derived from active relationships.
     *
     * Example results:
     * - New asset: ['available']
     * - With allocation: ['available', 'allocated']
     * - With maintenance: ['available', 'maintenance']
     * - With allocation + maintenance: ['available', 'allocated', 'maintenance']
     */
    public function getStatusesAttribute(): array
    {
        // AVAILABLE is ALWAYS first and always present
        $statuses = [self::STATUS_AVAILABLE];

        if ($this->current_allocation_id) {
            $statuses[] = self::STATUS_ALLOCATED;
        }

        if ($this->current_reservation_id) {
            $statuses[] = self::STATUS_RESERVED;
        }

        if ($this->current_maintenance_id) {
            $statuses[] = self::STATUS_MAINTENANCE;
        }

        // Note: legacy statuses (retired, lost, stolen) are NOT included here
        // as they are base status changes, not derived from activities

        return $statuses;
    }

    /**
     * Get human-readable status labels for display.
     */
    public function getStatusLabelsAttribute(): array
    {
        return array_map(fn($s) => self::STATUS_LABELS[$s] ?? ucfirst($s), $this->statuses);
    }

    /**
     * Get FontAwesome icons for each active status.
     */
    public function getStatusIconsAttribute(): array
    {
        $icons = [
            self::STATUS_AVAILABLE => 'check-circle',    // fa-check-circle
            self::STATUS_ALLOCATED => 'user-check',    // fa-user-check
            self::STATUS_RESERVED => 'bookmark',        // fa-bookmark
            self::STATUS_MAINTENANCE => 'wrench',    // fa-wrench
        ];

        return array_intersect_key($icons, array_flip($this->statuses));
    }

    /**
     * Get Tailwind color classes for each status.
     */
    public function getStatusColorsAttribute(): array
    {
        $colors = [
            self::STATUS_AVAILABLE => 'green',
            self::STATUS_ALLOCATED => 'blue',
            self::STATUS_RESERVED => 'purple',
            self::STATUS_MAINTENANCE => 'amber',
        ];

        return array_intersect_key($colors, array_flip($this->statuses));
    }

    /**
     * Check if asset has a specific status.
     */
    public function hasStatus(string $status): bool
    {
        return in_array($status, $this->statuses);
    }

    /**
     * Check if asset has active allocation.
     */
    public function getIsAllocatedAttribute(): bool
    {
        return $this->current_allocation_id !== null;
    }

    /**
     * Check if asset has active reservation.
     */
    public function getIsReservedAttribute(): bool
    {
        return $this->current_reservation_id !== null;
    }

    /**
     * Check if asset has active maintenance.
     */
    public function getIsInMaintenanceAttribute(): bool
    {
        return $this->current_maintenance_id !== null;
    }

    /**
     * Check if asset can perform activities.
     * Assets with maintenance can still allocate/reserve if business rule allows.
     */
    public function getCanHaveActivitiesAttribute(): bool
    {
        // Asset with existing activities can still create new ones unless business rule prevents specific combination
        return true; // Allow activities based on individual checks in each method
    }

    /**
     * Check if asset has any active activities.
     */
    public function getHasActiveActivitiesAttribute(): bool
    {
        return $this->current_allocation_id !== null
            || $this->current_reservation_id !== null
            || $this->current_maintenance_id !== null;
    }

    /**
     * Get active allocation activity.
     */
    public function getActiveAllocationAttribute(): ?AssetActivity
    {
        if (!$this->current_allocation_id) {
            return null;
        }
        return $this->activities()->find($this->current_allocation_id);
    }

    /**
     * Get active reservation activity.
     */
    public function getActiveReservationAttribute(): ?AssetActivity
    {
        if (!$this->current_reservation_id) {
            return null;
        }
        return $this->activities()->find($this->current_reservation_id);
    }

    /**
     * Get active maintenance activity.
     */
    public function getActiveMaintenanceActivityAttribute(): ?AssetActivity
    {
        if (!$this->current_maintenance_id) {
            return null;
        }
        return $this->activities()->find($this->current_maintenance_id);
    }

    /**
     * Check if asset has any photos.
     */
    public function getHasPhotosAttribute(): bool
    {
        return $this->photos()->exists();
    }

    /**
     * Get the cover photo URL.
     */
    public function getCoverPhotoUrlAttribute(): ?string
    {
        $cover = $this->coverPhoto;
        if ($cover) {
            return $cover->url;
        }

        // Fallback to first photo
        $first = $this->photos()->first();
        return $first ? $first->url : null;
    }

    /**
     * Check if asset has warranty card.
     * Note: Warranty card is stored in the assets table (warranty_card_path column),
     * not in the asset_warranty_cards table.
     */
    public function getHasWarrantyCardAttribute(): bool
    {
        return !empty($this->warranty_card_path);
    }

    /**
     * Get warranty card URL.
     * Note: Uses Storage::url() with the warranty_card_path from assets table.
     */
    public function getWarrantyCardUrlAttribute(): ?string
    {
        if ($this->warranty_card_path) {
            return Storage::disk('public')->url($this->warranty_card_path);
        }
        return null;
    }

    /**
     * Get category type label.
     */
    public function getCategoryTypeLabelAttribute(): ?string
    {
        return $this->category?->category_type_label;
    }

    /**
     * Check if asset is categorized as Fisik.
     */
    public function getIsFisikAttribute(): bool
    {
        return $this->category?->is_fisik ?? false;
    }

    /**
     * Check if asset is categorized as Akses.
     */
    public function getIsAksesAttribute(): bool
    {
        return $this->category?->is_akses ?? false;
    }

    /**
     * Get access type label.
     */
    public function getAccessTypeLabelAttribute(): ?string
    {
        if (!$this->access_type) {
            return null;
        }

        return match($this->access_type) {
            'lisensi' => 'Lisensi',
            'ipam' => 'IPAM',
            'upass' => 'Upass',
            default => ucfirst($this->access_type),
        };
    }

    /**
     * Get masked password for display (for authorization checks, not actual masking).
     * Returns null unless user has permission to view credentials.
     */
    public function getMaskedPasswordAttribute(): ?string
    {
        if (!$this->access_password) {
            return null;
        }
        return '••••••••';
    }

    /**
     * Check if license is expired.
     */
    public function getIsLicenseExpiredAttribute(): bool
    {
        return $this->license_end_date && $this->license_end_date->isPast();
    }

    /**
     * Check if license is expiring soon (within 30 days).
     */
    public function getIsLicenseExpiringSoonAttribute(): bool
    {
        if (!$this->license_end_date) {
            return false;
        }
        return $this->license_end_date->isFuture() && $this->license_end_date->diffInDays(now()) <= 30;
    }

    /**
     * Get days until license expires.
     */
    public function getDaysUntilLicenseExpiryAttribute(): ?int
    {
        if (!$this->license_end_date) {
            return null;
        }
        return now()->diffInDays($this->license_end_date, false);
    }

    /**
     * Check if asset is in a usable state.
     * Not in maintenance, retired, lost, or stolen.
     */
    public function getIsUsableAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_AVAILABLE,
            self::STATUS_MAINTENANCE, // Can still be used but under maintenance
        ]);
    }

    /**
     * Check if asset is permanently unavailable.
     */
    public function getIsPermanentlyUnavailableAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_RETIRED,
            self::STATUS_LOST,
            self::STATUS_STOLEN,
        ]);
    }

    /**
     * Get timeline of all activities.
     */
    public function getTimelineAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activities()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent activities (last 10).
     */
    public function getRecentActivitiesAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activities()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get multi-status badges for display.
     * Returns array of badge data for all active statuses.
     */
    public function getMultiStatusBadgesAttribute(): array
    {
        $badges = [];
        foreach ($this->statuses as $status) {
            $badges[] = [
                'status' => $status,
                'label' => self::STATUS_LABELS[$status] ?? ucfirst($status),
                'icon' => self::STATUS_ICONS[$status] ?? 'fa-circle',
                'color_class' => self::STATUS_BADGE_CLASSES[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200',
                'icon_color' => self::STATUS_ICON_COLORS[$status] ?? 'text-gray-500',
                'tooltip' => $this->getStatusTooltip($status),
            ];
        }
        return $badges;
    }

    /**
     * Get tooltip text for a specific status.
     */
    protected function getStatusTooltip(string $status): string
    {
        return match($status) {
            'available' => 'Asset terdaftar dan tersedia dalam sistem',
            'allocated' => 'Asset memiliki alokasi aktif',
            'reserved' => 'Asset memiliki reservasi aktif',
            'maintenance' => 'Asset memiliki maintenance aktif',
            'retired' => 'Asset telah ditarik dari sistem',
            'lost' => 'Asset ditandai sebagai hilang',
            'stolen' => 'Asset ditandai sebagai dicuri',
            default => ucfirst($status),
        };
    }

    /**
     * @deprecated Use multi_status_badges instead
     */
    public function getStatusBadgeAttribute(): array
    {
        // Legacy single badge - returns first status only
        $status = $this->statuses[0] ?? $this->status;
        return [
            'type' => self::STATUS_COLORS[$status] ?? 'secondary',
            'text' => self::STATUS_LABELS[$status] ?? ucfirst($status),
            'status' => $status,
        ];
    }

    /**
     * Get the base status icon (FontAwesome class).
     */
    public function getStatusIconAttribute(): string
    {
        return self::STATUS_ICONS[$this->status] ?? 'fa-circle';
    }

    /**
     * Get the base status color for Tailwind classes.
     */
    public function getStatusColorClassAttribute(): string
    {
        $colors = [
            'available' => 'text-green-500',
            'maintenance' => 'text-amber-500',
            'retired' => 'text-gray-500',
            'lost' => 'text-red-500',
            'stolen' => 'text-red-500',
        ];
        return $colors[$this->status] ?? 'text-gray-500';
    }

    /**
     * Get all active activities as status indicators.
     * Returns array of activity badges with icons and tooltips.
     */
    public function getActivityIndicatorsAttribute(): array
    {
        $indicators = [];

        // Get active allocation
        if ($this->current_allocation_id) {
            $allocation = $this->activities()->find($this->current_allocation_id);
            if ($allocation && $allocation->is_active) {
                $indicators[] = [
                    'type' => 'allocation',
                    'icon' => 'fa-box',
                    'icon_color' => 'text-blue-500',
                    'color' => 'blue',
                    'tooltip' => $allocation->compact_tooltip,
                    'activity_id' => $allocation->id,
                ];
            }
        }

        // Get active reservation
        if ($this->current_reservation_id) {
            $reservation = $this->activities()->find($this->current_reservation_id);
            if ($reservation && $reservation->is_active) {
                $indicators[] = [
                    'type' => 'reservation',
                    'icon' => 'fa-calendar-check',
                    'icon_color' => 'text-purple-500',
                    'color' => 'purple',
                    'tooltip' => $reservation->compact_tooltip,
                    'activity_id' => $reservation->id,
                ];
            }
        }

        return $indicators;
    }

    /**
     * Get combined status display for table view.
     * Returns base status icon and activity indicators.
     */
    public function getCombinedStatusAttribute(): array
    {
        return [
            'base' => [
                'icon' => $this->status_icon,
                'label' => $this->status_label,
                'color' => $this->status_color_class,
            ],
            'activities' => $this->activity_indicators,
        ];
    }

    /**
     * Get tooltip HTML for the base status.
     */
    public function getBaseStatusTooltipAttribute(): string
    {
        $html = '<div class="text-left">';
        $html .= '<div class="font-semibold flex items-center gap-2">';
        $html .= '<i class="fa-solid ' . ($this->statusIcon ?? 'fa-circle') . '"></i> ';
        $html .= $this->status_label;
        $html .= '</div>';

        if ($this->hasStatus('maintenance') && $this->current_maintenance_id) {
            $maintenance = $this->active_maintenance_activity;
            if ($maintenance) {
                if ($maintenance->maintenance_type) {
                    $types = [
                        'preventive' => 'Pencegahan',
                        'corrective' => 'Korektif',
                        'inspection' => 'Inspeksi',
                        'upgrade' => 'Peningkatan',
                    ];
                    $html .= '<div>Jenis: ' . ($types[$maintenance->maintenance_type] ?? $maintenance->maintenance_type) . '</div>';
                }
                if ($maintenance->technician) {
                    $html .= '<div>Teknisi: ' . e($maintenance->technician) . '</div>';
                }
            }
        }

        if ($this->notes) {
            $html .= '<div class="text-gray-500 mt-1">' . e(Str::limit($this->notes, 50)) . '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    public function getDepreciationMethodLabelAttribute(): string
    {
        $labels = [
            'none' => 'Tidak Ada',
            'straight_line' => 'Garis Lurus',
            'declining_balance' => 'Saldo Menurun',
        ];

        return $labels[$this->depreciation_method] ?? ucfirst($this->depreciation_method);
    }

    // =====================================================
    // MUTATORS
    // =====================================================

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = ucfirst(trim($value));
    }

    public function setSerialNumberAttribute($value): void
    {
        $this->attributes['serial_number'] = $value ? strtoupper(trim($value)) : null;
    }

    // =====================================================
    // PHOTO HELPERS
    // =====================================================

    /**
     * Process and store uploaded photo.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array Photo metadata
     */
    public function processPhoto($file): array
    {
        $filename = $this->uuid . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = 'assets/' . $filename;

        // Store the main image
        Storage::disk('public')->putFileAs('assets', $file, $filename);

        // Get file info
        $metadata = [
            'image_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];

        // Generate thumbnail if image is large
        $this->generateThumbnail($file, $filename);

        return $metadata;
    }

    /**
     * Generate thumbnail from uploaded image.
     * Uses simple copy without resize.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $filename
     */
    protected function generateThumbnail($file, string $filename): void
    {
        try {
            // Create thumbnail by copying the original file ()
            $thumbnailFilename = 'thumb_' . $filename;
            $thumbnailPath = 'assets/' . $thumbnailFilename;

            // Copy the file directly
            Storage::disk('public')->putFileAs('assets', $file, $thumbnailFilename);

            $this->thumbnail_path = $thumbnailPath;
            Log::info('[THUMBNAIL] Generated thumbnail: ' . $thumbnailPath);
        } catch (\Exception $e) {
            // If thumbnail generation fails, continue without thumbnail
            Log::warning('[THUMBNAIL] Failed to create thumbnail: ' . $e->getMessage());
        }
    }

    /**
     * Delete associated photos from storage.
     */
    public function deletePhoto(): bool
    {
        $deleted = false;

        // Delete main image
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            Storage::disk('public')->delete($this->image_path);
            $deleted = true;
        }

        // Delete thumbnail
        if ($this->thumbnail_path && Storage::disk('public')->exists($this->thumbnail_path)) {
            Storage::disk('public')->delete($this->thumbnail_path);
            $deleted = true;
        }

        // Clear database fields
        $this->update([
            'image_path' => null,
            'thumbnail_path' => null,
            'original_filename' => null,
            'file_size' => null,
            'mime_type' => null,
        ]);

        return $deleted;
    }

    // =====================================================
    // HELPERS - ACTIVITY-BASED STATUS
    // =====================================================

    /**
     * Check if asset can have activities.
     * Assets can have multiple activity types simultaneously.
     */
    public function canHaveActivities(): bool
    {
        return true; // Activities can coexist
    }

    /**
     * Create an allocation activity.
     * Asset will have MULTIPLE statuses: available + allocated (and/or maintenance).
     * Allocation can coexist with maintenance.
     *
     * Required fields:
     * - received_by: Nama penerima
     * - location: Lokasi penerimaan
     *
     * Optional fields:
     * - sent_by: Nama pengirim
     * - sent_date: Tanggal dikirim
     * - received_date: Tanggal diterima
     * - notes: Catatan
     */
    public function createAllocation(array $data): AssetActivity
    {
        if (!$this->canHaveActivities()) {
            throw new \Exception('Asset tidak dapat dialokasikan.');
        }

        // Check for conflicting activities - only reservation blocks allocation
        // Maintenance CAN coexist with allocation
        if ($this->current_reservation_id) {
            throw new \Exception('Asset sedang dalam reservasi. Selesaikan reservasi terlebih dahulu.');
        }

        // Get tenant_id from asset or fallback to company_id (this project uses company_id)
        // If tenant_id is null, use company_id as the tenant identifier
        $tenantId = $this->tenant_id ?? $this->company_id ?? auth()->user()?->tenant_id ?? auth()->user()?->company_id;
        $companyId = $this->company_id ?? auth()->user()?->company_id;

        // Use the new AssetActivity method with specific fields
        $activity = AssetActivity::createAllocation([
            'asset_id' => $this->id,
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'sent_by' => $data['sent_by'] ?? null,
            'received_by' => $data['received_by'] ?? null,
            'location' => $data['location'] ?? null,
            'sent_date' => $data['sent_date'] ?? null,
            'received_date' => $data['received_date'] ?? now(),
            'notes' => $data['notes'] ?? null,
            'description' => $data['description'] ?? "Dialokasikan ke {$data['received_by']}",
        ]);

        // Update allocation reference only - 'available' stays as base status
        $this->update([
            'current_allocation_id' => $activity->id,
        ]);

        return $activity;
    }

    /**
     * Complete allocation. Removes allocation relationship, preserves other statuses like maintenance.
     */
    public function completeAllocation(): void
    {
        if ($this->current_allocation_id) {
            $activity = $this->activities()->find($this->current_allocation_id);
            if ($activity) {
                $activity->complete();
            }
            $this->update([
                'current_allocation_id' => null,
            ]);
        }
    }

    /**
     * Create a check-out activity.
     */
    public function createCheckOut(array $data): AssetActivity
    {
        if (!$this->canHaveActivities()) {
            throw new \Exception('Asset must be available to check out.');
        }

        return $this->activities()->create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenant_id,
            'activity_type' => AssetActivity::TYPE_CHECK_OUT,
            'status' => AssetActivity::STATUS_COMPLETED,
            'start_date' => $data['start_date'] ?? now(),
            'data' => [
                'user_id' => $data['user_id'] ?? null,
                'from_location' => $data['from_location'] ?? $this->current_location,
                'to_user_id' => $data['to_user_id'] ?? null,
                'condition' => $data['condition'] ?? null,
            ],
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Create a reservation activity.
     * Asset will have MULTIPLE statuses: available + reserved (and/or maintenance).
     * Reservation can coexist with maintenance.
     *
     * Required fields:
     * - used_by: Siapa yang menggunakan
     * - installation_location: Lokasi pemasangan
     *
     * Optional fields:
     * - department: Departemen
     * - start_date: Tanggal mulai
     * - end_date: Tanggal selesai
     * - notes: Catatan
     */
    public function createReservation(array $data): AssetActivity
    {
        if (!$this->canHaveActivities()) {
            throw new \Exception('Asset tidak dapat direservasi.');
        }

        // Check for conflicting activities - only allocation blocks reservation
        // Maintenance CAN coexist with reservation
        if ($this->current_allocation_id) {
            throw new \Exception('Asset sedang dalam alokasi. Selesaikan alokasi terlebih dahulu.');
        }

        // Get tenant_id from asset or fallback to company_id (this project uses company_id)
        // If tenant_id is null, use company_id as the tenant identifier
        $tenantId = $this->tenant_id ?? $this->company_id ?? auth()->user()?->tenant_id ?? auth()->user()?->company_id;
        $companyId = $this->company_id ?? auth()->user()?->company_id;

        // Use the new AssetActivity method with specific fields
        $activity = AssetActivity::createReservation([
            'asset_id' => $this->id,
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'used_by' => $data['used_by'] ?? null,
            'installation_location' => $data['installation_location'] ?? null,
            'department' => $data['department'] ?? null,
            'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'description' => $data['description'] ?? "Direservasi untuk {$data['used_by']}",
        ]);

        // Update reservation reference only - 'available' stays as base status
        $this->update([
            'current_reservation_id' => $activity->id,
        ]);

        return $activity;
    }

    /**
     * Complete reservation. Removes reservation relationship, preserves other statuses.
     */
    public function completeReservation(): void
    {
        if ($this->current_reservation_id) {
            $activity = $this->activities()->find($this->current_reservation_id);
            if ($activity) {
                $activity->complete();
            }
            $this->update([
                'current_reservation_id' => null,
            ]);
        }
    }

    /**
     * Create a check-in activity.
     */
    public function createCheckIn(array $data): AssetActivity
    {
        return $this->activities()->create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenant_id,
            'activity_type' => AssetActivity::TYPE_CHECK_IN,
            'status' => AssetActivity::STATUS_COMPLETED,
            'start_date' => $data['start_date'] ?? now(),
            'data' => [
                'user_id' => $data['user_id'] ?? null,
                'to_location' => $data['to_location'] ?? $this->current_location,
                'condition' => $data['condition'] ?? null,
            ],
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Create a transfer activity.
     */
    public function createTransfer(array $data): AssetActivity
    {
        if (!$this->canHaveActivities()) {
            throw new \Exception('Asset must be available to transfer.');
        }

        return $this->activities()->create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenant_id,
            'activity_type' => AssetActivity::TYPE_TRANSFER,
            'status' => AssetActivity::STATUS_IN_TRANSIT,
            'start_date' => $data['start_date'] ?? now(),
            'data' => [
                'from_location' => $data['from_location'] ?? $this->current_location,
                'to_location' => $data['to_location'] ?? null,
                'from_department_id' => $data['from_department_id'] ?? null,
                'to_department_id' => $data['to_department_id'] ?? null,
                'reason' => $data['reason'] ?? null,
            ],
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Complete a transfer and update asset location.
     */
    public function completeTransfer(AssetActivity $transfer): void
    {
        if ($transfer->activity_type !== AssetActivity::TYPE_TRANSFER) {
            throw new \Exception('Invalid activity type.');
        }

        $data = $transfer->data ?? [];

        $transfer->complete();

        // Update asset location
        $this->update([
            'current_location' => $data['to_location'] ?? $this->current_location,
            'current_department' => $data['to_department_id'] ?? $this->current_department,
        ]);
    }

    /**
     * Create a recall activity.
     */
    public function createRecall(array $data): AssetActivity
    {
        if (!$this->canHaveActivities()) {
            throw new \Exception('Asset must be available to recall.');
        }

        return $this->activities()->create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenant_id,
            'activity_type' => AssetActivity::TYPE_RECALL,
            'status' => AssetActivity::STATUS_PENDING,
            'start_date' => $data['start_date'] ?? now(),
            'data' => [
                'from_user_id' => $data['from_user_id'] ?? null,
                'reason' => $data['reason'] ?? null,
            ],
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Create a maintenance activity.
     * Maintenance can run alongside other activities (allocated, reserved).
     * The asset will have MULTIPLE statuses: available + maintenance (and/or allocated, reserved).
     *
     * Required fields:
     * - maintenance_type: preventive, corrective, inspection, upgrade
     *
     * Optional fields:
     * - technician: Nama teknisi
     * - start_date: Tanggal mulai
     * - end_date: Tanggal selesai/perkiraan
     * - cost: Biaya maintenance
     * - notes: Catatan
     */
    public function createMaintenance(array $data): AssetActivity
    {
        // No status check needed - available is always present
        // Maintenance can coexist with allocation and reservation

        // Get tenant_id from asset or fallback to company_id (this project uses company_id)
        // If tenant_id is null, use company_id as the tenant identifier
        $tenantId = $this->tenant_id ?? $this->company_id ?? auth()->user()?->tenant_id ?? auth()->user()?->company_id;
        $companyId = $this->company_id ?? auth()->user()?->company_id;

        // Use the new AssetActivity method with specific fields
        $activity = AssetActivity::createMaintenance([
            'asset_id' => $this->id,
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'maintenance_type' => $data['maintenance_type'] ?? 'preventive',
            'technician' => $data['technician'] ?? null,
            'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'description' => $data['description'] ?? 'Maintenance dijadwalkan',
        ]);

        return $activity;
    }

    /**
     * Complete maintenance - removes ONLY the maintenance status.
     * Preserves all other active statuses (available, allocated, reserved).
     */
    public function completeMaintenance(): void
    {
        if ($this->current_maintenance_id) {
            $activity = $this->activities()->find($this->current_maintenance_id);
            if ($activity) {
                $activity->complete();
            }
        }

        // Only clear current_maintenance_id - do NOT reset status to 'available'
        // 'available' is always present, other statuses are preserved
        $this->update([
            'current_maintenance_id' => null,
        ]);
    }

    // =====================================================
    // LEGACY HELPERS - For backward compatibility
    // These methods are deprecated but kept for compatibility
    // =====================================================

    /**
     * @deprecated Use createAllocation() instead
     */
    public function assignTo(User $user, string $notes = null): AssetActivity
    {
        return $this->createAllocation([
            'to_user_id' => $user->id,
            'notes' => $notes,
        ]);
    }

    /**
     * @deprecated Use completeAllocation() instead
     */
    public function unassign(): bool
    {
        $this->completeAllocation();
        return true;
    }

    /**
     * @deprecated Use createMaintenance() instead
     */
    public function markAsMaintenance(): bool
    {
        $this->createMaintenance([]);
        return true;
    }

    /**
     * @deprecated Use completeMaintenance() instead
     */
    public function markAsAvailable(): bool
    {
        // Only allow if currently in maintenance
        if ($this->status === self::STATUS_MAINTENANCE) {
            $this->completeMaintenance();
        }
        return true;
    }

    /**
     * @deprecated Use base status directly
     */
    public function markAsLost(): bool
    {
        return (bool) $this->update(['status' => self::STATUS_LOST]);
    }

    /**
     * @deprecated Use base status directly
     */
    public function markAsStolen(): bool
    {
        return (bool) $this->update(['status' => self::STATUS_STOLEN]);
    }

    /**
     * @deprecated Use base status directly
     */
    public function markAsRetired(): bool
    {
        return (bool) $this->update(['status' => self::STATUS_RETIRED]);
    }

    /**
     * Calculate current depreciation value.
     */
    public function calculateCurrentValue(): float
    {
        if ($this->depreciation_method === self::DEPRECIATION_NONE || !$this->expected_lifespan_months) {
            return $this->purchase_cost;
        }

        if (!$this->purchase_date) {
            return $this->purchase_cost;
        }

        $monthsElapsed = $this->purchase_date->diffInMonths(now());
        $monthsElapsed = min($monthsElapsed, $this->expected_lifespan_months);

        if ($this->depreciation_method === self::DEPRECIATION_STRAIGHT_LINE) {
            $monthlyDepreciation = ($this->purchase_cost - $this->salvage_value) / $this->expected_lifespan_months;
            $totalDepreciation = $monthlyDepreciation * $monthsElapsed;
            $currentValue = $this->purchase_cost - $totalDepreciation;
        } else {
            // Declining balance (double declining)
            $rate = (2 / $this->expected_lifespan_months);
            $currentValue = $this->purchase_cost * pow((1 - $rate), $monthsElapsed / 12);
        }

        return max($this->salvage_value, $currentValue);
    }

    /**
     * Generate next asset number.
     */
    public static function generateNumber(): string
    {
        $year = date('Y');

        // Use lock to prevent race condition
        return \DB::transaction(function () use ($year) {
            // Get the highest sequence number for this year (including soft-deleted)
            $lastAsset = static::withTrashed()
                ->where('asset_number', 'like', "AST-{$year}-%")
                ->selectRaw("MAX(CAST(SUBSTRING(asset_number, -4) AS UNSIGNED)) as max_seq")
                ->first();

            $newSequence = ($lastAsset->max_seq ?? 0) + 1;

            return sprintf('AST-%s-%04d', $year, $newSequence);
        });
    }

    public static function getStatuses(): array
    {
        // Multi-status system: only 4 canonical statuses
        // Available is always present, others are derived from activities
        return [
            self::STATUS_AVAILABLE => 'Tersedia',
            self::STATUS_ALLOCATED => 'Dialokasikan',
            self::STATUS_RESERVED => 'Direservasi',
            self::STATUS_MAINTENANCE => 'Maintenance',
            // Legacy - deprecated but kept for backward compatibility
            // self::STATUS_RETIRED => 'Ditarik',
            // self::STATUS_LOST => 'Hilang',
            // self::STATUS_STOLEN => 'Dicuri',
        ];
    }

    /**
     * Get multi-status with icons.
     */
    public static function getMultiStatuses(): array
    {
        return [
            self::STATUS_AVAILABLE => ['label' => 'Tersedia', 'icon' => 'fa-check-circle', 'color' => 'green'],
            self::STATUS_ALLOCATED => ['label' => 'Dialokasikan', 'icon' => 'fa-box', 'color' => 'blue'],
            self::STATUS_RESERVED => ['label' => 'Direservasi', 'icon' => 'fa-calendar-check', 'color' => 'purple'],
            self::STATUS_MAINTENANCE => ['label' => 'Maintenance', 'icon' => 'fa-wrench', 'color' => 'amber'],
        ];
    }

    /**
     * Get base statuses with icons.
     * @deprecated Use getMultiStatuses() instead
     */
    public static function getBaseStatuses(): array
    {
        return self::getMultiStatuses();
    }

    /**
     * Get activity types with icons.
     */
    public static function getActivityTypes(): array
    {
        return AssetActivity::TYPE_LABELS;
    }

    /**
     * Get activity types with icons for UI display.
     */
    public static function getActivityTypesWithIcons(): array
    {
        return [
            'allocation' => ['label' => 'Alokasi', 'icon' => '📦', 'color' => 'blue'],
            'reservation' => ['label' => 'Reservasi', 'icon' => '📍', 'color' => 'purple'],
        ];
    }

    // =====================================================
    // QR CODE HELPERS
    // =====================================================

    /**
     * Get QR identifier, generate if not exists.
     */
    public function getQrIdentifier(): string
    {
        if (!$this->qr_identifier) {
            $this->update(['qr_identifier' => 'AST-' . $this->uuid]);
        }
        return $this->qr_identifier;
    }

    /**
     * Get QR data for scanning.
     */
    public function getQrData(): array
    {
        return [
            'type' => 'asset',
            'id' => $this->id,
            'uuid' => $this->uuid,
            'identifier' => $this->getQrIdentifier(),
            'name' => $this->name,
        ];
    }

    /**
     * Calculate maintenance end date based on start date and duration.
     */
    public function calculateMaintenanceEndDate(): ?\Carbon\Carbon
    {
        if (!$this->maintenance_start_date || !$this->maintenance_duration_days) {
            return null;
        }
        return $this->maintenance_start_date->addDays($this->maintenance_duration_days);
    }

    /**
     * Check if maintenance is overdue.
     */
    public function getIsMaintenanceOverdueAttribute(): bool
    {
        if ($this->status !== self::STATUS_MAINTENANCE) {
            return false;
        }
        return $this->maintenance_end_date && $this->maintenance_end_date->isPast();
    }

    /**
     * Scope to find by QR identifier.
     */
    public function scopeByQrIdentifier($query, string $identifier)
    {
        return $query->where('qr_identifier', $identifier);
    }

    // =====================================================
    // AUTO NAME GENERATION
    // =====================================================

    /**
     * Generate automatic asset name based on category type and relevant fields.
     * Uses company scoped sequence to avoid duplicates.
     *
     * @param int|null $companyId
     * @param int|null $tenantId
     * @param string $categoryType (fisik|akses)
     * @param string|null $accessType (lisensi|ipam|upass)
     * @param array $data Asset data including product_name, license_name, etc.
     * @return string Generated name in format "{Base Name} - 001"
     */
    public static function generateAutoName(
        ?int $companyId,
        ?int $tenantId,
        string $categoryType,
        ?string $accessType,
        array $data
    ): string {
        // Determine base name based on category type
        $baseName = self::determineBaseName($categoryType, $accessType, $data);

        // Get the next sequence number (company scoped)
        $sequence = self::getNextSequence($companyId, $baseName);

        return $baseName . ' - ' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Determine the base name for asset name generation.
     */
    protected static function determineBaseName(
        string $categoryType,
        ?string $accessType,
        array $data
    ): string {
        if ($categoryType === 'fisik') {
            // For physical assets, use product_name as base name
            $productName = $data['product_name'] ?? null;
            if (!empty($productName)) {
                return trim($productName);
            }
            // Fallback to category name if available
            if (!empty($data['category_name'])) {
                return trim($data['category_name']);
            }
            return 'Asset Fisik';
        }

        if ($categoryType === 'akses') {
            return self::determineAccessBaseName($accessType, $data);
        }

        // Default fallback
        if (!empty($data['category_name'])) {
            return trim($data['category_name']);
        }

        return 'Asset';
    }

    /**
     * Determine base name for access category based on access type.
     */
    protected static function determineAccessBaseName(?string $accessType, array $data): string
    {
        switch ($accessType) {
            case 'lisensi':
                // Use license_name or product_name as base name
                $licenseName = $data['license_name'] ?? $data['product_name'] ?? null;
                if (!empty($licenseName)) {
                    return trim($licenseName);
                }
                return 'Lisensi';

            case 'ipam':
                // Use hostname, IP address, or category name
                $hostname = $data['ipam_hostname'] ?? null;
                if (!empty($hostname)) {
                    return trim($hostname);
                }
                $ipAddress = $data['ipam_ip_address'] ?? null;
                if (!empty($ipAddress)) {
                    return 'IP ' . trim($ipAddress);
                }
                return 'IPAM';

            case 'upass':
                // Use product_name or category name as base name
                $productName = $data['product_name'] ?? null;
                if (!empty($productName)) {
                    return trim($productName);
                }
                return 'Upass';

            default:
                // Fallback to category name
                if (!empty($data['category_name'])) {
                    return trim($data['category_name']);
                }
                return 'Akses';
        }
    }

    /**
     * Get the next sequence number for a given base name within company scope.
     * Uses database transaction with lock to prevent race conditions.
     *
     * @param int|null $companyId
     * @param string $baseName
     * @return int
     */
    protected static function getNextSequence(?int $companyId, string $baseName): int
    {
        return \DB::transaction(function () use ($companyId, $baseName) {
            // Find existing assets with the same base name prefix
            // We match assets where name starts with "{baseName} - "
            $pattern = $baseName . ' - ';

            $query = static::withTrashed()
                ->where('name', 'like', $pattern . '%');

            // Scope by company_id if available
            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $lastAsset = $query
                ->selectRaw("MAX(CAST(SUBSTRING(name, " . (strlen($pattern) + 1) . ") AS UNSIGNED)) as max_seq")
                ->first();

            $lastSequence = $lastAsset->max_seq ?? 0;

            return $lastSequence + 1;
        });
    }
}
