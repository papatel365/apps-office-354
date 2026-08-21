<?php

namespace App\Models;

use App\Core\Traits\BelongsToCompany;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;

class AssetCategory extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasAuditLog;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'description',
        'category_type',
        'access_type',
        'depreciation_method',
        'default_lifespan_months',
        'color',
        'icon',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_lifespan_months' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    const DEPRECIATION_NONE = 'none';
    const DEPRECIATION_STRAIGHT_LINE = 'straight_line';
    const DEPRECIATION_DECLINING_BALANCE = 'declining_balance';

    // Category Types
    const CATEGORY_TYPE_FISIK = 'fisik';
    const CATEGORY_TYPE_AKSES = 'akses';

    // Access Types (for Akses category)
    const ACCESS_TYPE_LISENSI = 'lisensi';
    const ACCESS_TYPE_IPAM = 'ipam';
    const ACCESS_TYPE_UPASS = 'upass';

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AssetCategory::class, 'parent_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'category_id');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeFisik($query)
    {
        return $query->where('category_type', self::CATEGORY_TYPE_FISIK);
    }

    public function scopeAkses($query)
    {
        return $query->where('category_type', self::CATEGORY_TYPE_AKSES);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getFormattedColorAttribute(): string
    {
        return $this->color ?? '#6B7280';
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

    public function getCategoryTypeLabelAttribute(): string
    {
        return match($this->category_type) {
            self::CATEGORY_TYPE_FISIK => 'Fisik',
            self::CATEGORY_TYPE_AKSES => 'Akses',
            default => ucfirst($this->category_type ?? ''),
        };
    }

    public function getAccessTypeLabelAttribute(): ?string
    {
        if (!$this->access_type) {
            return null;
        }

        return match($this->access_type) {
            self::ACCESS_TYPE_LISENSI => 'Lisensi',
            self::ACCESS_TYPE_IPAM => 'IPAM',
            self::ACCESS_TYPE_UPASS => 'Upass',
            default => ucfirst($this->access_type),
        };
    }

    public function getIsFisikAttribute(): bool
    {
        return $this->category_type === self::CATEGORY_TYPE_FISIK;
    }

    public function getIsAksesAttribute(): bool
    {
        return $this->category_type === self::CATEGORY_TYPE_AKSES;
    }

    // =====================================================
    // MUTATORS
    // =====================================================

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = ucfirst(strtolower(trim($value)));
    }

    // =====================================================
    // QR CODE HELPERS
    // =====================================================

    /**
     * Get QR identifier for category.
     */
    public function getQrIdentifier(): string
    {
        return 'CAT-' . $this->uuid;
    }

    /**
     * Get QR data for scanning.
     */
    public function getQrData(): array
    {
        return [
            'type' => 'category',
            'id' => $this->id,
            'uuid' => $this->uuid,
            'identifier' => $this->getQrIdentifier(),
            'name' => $this->name,
            'asset_count' => $this->assets()->count(),
        ];
    }

    /**
     * Get all assets in this category (including children).
     */
    public function getAllAssets(): \Illuminate\Database\Eloquent\Collection
    {
        $categoryIds = $this->getAllChildren()->pluck('id')->toArray();
        $categoryIds[] = $this->id;

        return Asset::whereIn('category_id', $categoryIds)->with('category')->get();
    }

    // =====================================================
    // HELPERS
    // =====================================================

    /**
     * Get all children recursively.
     */
    public function getAllChildren(): \Illuminate\Support\Collection
    {
        $children = $this->children;

        foreach ($this->children as $child) {
            $children = $children->merge($child->getAllChildren());
        }

        return $children;
    }

    /**
     * Get category tree for dropdown.
     */
    public static function getTree(): \Illuminate\Support\Collection
    {
        return static::root()
            ->active()
            ->with('children')
            ->orderBy('sort_order')
            ->get();
    }

    public static function getDepreciationMethods(): array
    {
        return [
            self::DEPRECIATION_NONE => 'Tidak Ada',
            self::DEPRECIATION_STRAIGHT_LINE => 'Garis Lurus',
            self::DEPRECIATION_DECLINING_BALANCE => 'Saldo Menurun',
        ];
    }

    public static function getCategoryTypes(): array
    {
        return [
            self::CATEGORY_TYPE_FISIK => 'Fisik',
            self::CATEGORY_TYPE_AKSES => 'Akses',
        ];
    }

    public static function getAccessTypes(): array
    {
        return [
            self::ACCESS_TYPE_LISENSI => 'Lisensi',
            self::ACCESS_TYPE_IPAM => 'IPAM',
            self::ACCESS_TYPE_UPASS => 'Upass',
        ];
    }

    public static function getAccessTypesForSelect(): array
    {
        return [
            '' => 'Pilih Jenis Akses',
            self::ACCESS_TYPE_LISENSI => 'Lisensi',
            self::ACCESS_TYPE_IPAM => 'IPAM',
            self::ACCESS_TYPE_UPASS => 'Upass',
        ];
    }
}
