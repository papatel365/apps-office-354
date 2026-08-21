<?php

namespace App\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;
    use HasAuditLog;

    /**
     * File allowed MIME types for sales documents
     */
    const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * Maximum file size in kilobytes (10MB)
     */
    const MAX_FILE_SIZE = 10240;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'attachable_type',
        'attachable_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'disk',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the signed URL for downloading this attachment
     * Uses temporary signed URL that expires in 5 minutes
     */
    public function getSignedUrlAttribute(): string
    {
        if ($this->disk === 'public') {
            return url('storage/' . $this->file_path);
        }

        // Private disk - generate signed URL
        return Storage::disk($this->disk)->temporaryUrl(
            $this->file_path,
            now()->addMinutes(5)
        );
    }

    /**
     * Get preview URL (for images/PDFs inline)
     */
    public function getPreviewUrl(): string
    {
        if ($this->disk === 'public') {
            return url('storage/' . $this->file_path);
        }

        return Storage::disk($this->disk)->temporaryUrl(
            $this->file_path,
            now()->addMinutes(5)
        );
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\System\Models\User::class, 'uploaded_by');
    }

    /**
     * Get the parent attachable model
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeForTenant($query, ?int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Check if current user can access this attachment
     */
    public function canBeAccessedBy(int $tenantId): bool
    {
        // Only check tenant_id as that's the only multi-tenant identifier
        return $this->tenant_id === $tenantId;
    }

    /**
     * Validate MIME type
     */
    public static function isAllowedMimeType(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_MIME_TYPES);
    }

    /**
     * Get file size in human readable format
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file is an image
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->file_type ?? '', 'image/');
    }

    /**
     * Check if file is a PDF
     */
    public function getIsPdfAttribute(): bool
    {
        return $this->file_type === 'application/pdf';
    }

    /**
     * Get display icon class
     */
    public function getIconAttribute(): string
    {
        $ext = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));

        return match($ext) {
            'pdf' => 'fa-file-pdf text-red-500',
            'doc', 'docx' => 'fa-file-word text-blue-500',
            'xls', 'xlsx' => 'fa-file-excel text-green-500',
            'png', 'jpg', 'jpeg', 'webp', 'gif' => 'fa-file-image text-purple-500',
            'zip', 'rar' => 'fa-file-archive text-yellow-500',
            default => 'fa-file text-gray-500',
        };
    }
}
