<?php

namespace App\Models;

use App\Core\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class AssetPhoto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLog;

    protected $fillable = [
        'uuid',
        'asset_id',
        'file_path',
        'thumbnail_path',
        'original_filename',
        'file_size',
        'mime_type',
        'is_cover',
        'sort_order',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_cover' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    // Image MIME types
    const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    // Max file size: 10MB
    const MAX_FILE_SIZE = 10 * 1024 * 1024;

    // Allowed extensions
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeImages($query)
    {
        return $query->whereIn('mime_type', self::IMAGE_MIME_TYPES);
    }

    public function scopeCovers($query)
    {
        return $query->where('is_cover', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    public function scopeForAsset($query, int $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    /**
     * Get the full URL to the photo.
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }
        return null;
    }

    /**
     * Get the URL to the thumbnail.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }
        return $this->url;
    }

    /**
     * Check if this is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return in_array($this->mime_type, self::IMAGE_MIME_TYPES);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file extension.
     */
    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->original_filename, PATHINFO_EXTENSION));
    }

    // =====================================================
    // MUTATORS
    // =====================================================

    /**
     * Set is_cover and unset other covers for this asset.
     */
    public function setIsCoverAttribute(bool $value): void
    {
        if ($value) {
            // Unset all other covers for this asset
            self::where('asset_id', $this->asset_id)
                ->where('id', '!=', $this->id ?? 0)
                ->update(['is_cover' => false]);
        }

        $this->attributes['is_cover'] = $value;
    }

    // =====================================================
    // HELPERS
    // =====================================================

    /**
     * Delete the photo files from storage.
     */
    public function deleteFiles(): bool
    {
        $deleted = false;

        // Delete main image
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
            $deleted = true;
        }

        // Delete thumbnail
        if ($this->thumbnail_path && Storage::disk('public')->exists($this->thumbnail_path)) {
            Storage::disk('public')->delete($this->thumbnail_path);
            $deleted = true;
        }

        return $deleted;
    }

    /**
     * Override delete to also delete files.
     */
    public function delete(): ?bool
    {
        $this->deleteFiles();
        return parent::delete();
    }

    /**
     * Set as cover photo.
     */
    public function setAsCover(): void
    {
        $this->update(['is_cover' => true]);
    }

    /**
     * Generate next sort order for asset.
     */
    public static function getNextSortOrder(int $assetId): int
    {
        $max = self::where('asset_id', $assetId)->max('sort_order');
        return ($max ?? 0) + 1;
    }
}
