<?php

namespace App\Models;

use App\Core\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class TaskPhoto extends Model
{
    use HasFactory;
    use HasAuditLog;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'task_id',
        'work_update_id',
        'original_filename',
        'file_path',
        'thumbnail_path',
        'disk',
        'file_size',
        'mime_type',
        'is_evidence',
        'caption',
        'description',
        'work_stage',
        'sort_order',
        'uploaded_by',
        'deleted_by',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_evidence' => 'boolean',
            'file_size' => 'integer',
            'sort_order' => 'integer',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // =====================================================
    // WORK STAGE CONSTANTS
    // =====================================================

    const STAGE_PERSIAPAN = 'persiapan';
    const STAGE_SEBELUM = 'sebelum';
    const STAGE_PROSES = 'proses';
    const STAGE_SESUDAH = 'sesudah';
    const STAGE_TESTING = 'testing';
    const STAGE_SERAH_TERIMA = 'serah_terima';

    const WORK_STAGES = [
        self::STAGE_PERSIAPAN => 'Persiapan',
        self::STAGE_SEBELUM => 'Sebelum Pengerjaan',
        self::STAGE_PROSES => 'Proses Pengerjaan',
        self::STAGE_SESUDAH => 'Sesudah Pengerjaan',
        self::STAGE_TESTING => 'Testing & Validasi',
        self::STAGE_SERAH_TERIMA => 'Serah Terima',
    ];

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function workUpdate(): BelongsTo
    {
        return $this->belongsTo(WorkUpdate::class, 'work_update_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\System\Models\User::class, 'uploaded_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\System\Models\User::class, 'deleted_by');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeEvidence($query)
    {
        return $query->where('is_evidence', true);
    }

    public function scopeByWorkStage($query, string $stage)
    {
        return $query->where('work_stage', $stage);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            return Storage::disk($this->disk)->url($this->thumbnail_path);
        }
        return $this->url;
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFormattedWorkStageAttribute(): ?string
    {
        return self::WORK_STAGES[$this->work_stage] ?? null;
    }

    public function getWorkStageLabelAttribute(): ?string
    {
        return $this->formatted_work_stage;
    }

    public function getIsImageAttribute(): bool
    {
        return in_array($this->mime_type, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ]);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->caption) {
            return $this->caption;
        }
        return $this->original_filename;
    }

    // =====================================================
    // HELPERS
    // =====================================================

    /**
     * Delete photo files from storage.
     */
    public function deleteFiles(): bool
    {
        $deleted = false;

        if ($this->file_path && Storage::disk($this->disk)->exists($this->file_path)) {
            Storage::disk($this->disk)->delete($this->file_path);
            $deleted = true;
        }

        if ($this->thumbnail_path && Storage::disk($this->disk)->exists($this->thumbnail_path)) {
            Storage::disk($this->disk)->delete($this->thumbnail_path);
        }

        return $deleted;
    }

    /**
     * Boot method to handle soft delete with user tracking.
     */
    protected static function booted(): void
    {
        static::deleting(function ($photo) {
            // Store who deleted this photo
            $photo->deleted_by = auth()->id();
            $photo->saveQuietly();
        });

        static::deleted(function ($photo) {
            // Delete files from storage when permanently deleted
            if ($photo->isForceDeleting()) {
                $photo->deleteFiles();
            }
        });

        static::restored(function ($photo) {
            $photo->deleted_by = null;
            $photo->saveQuietly();
        });
    }

    /**
     * Update photo metadata.
     */
    public function updateMetadata(array $data): bool
    {
        $allowed = ['caption', 'description', 'work_stage', 'sort_order'];

        $update = array_intersect_key($data, array_flip($allowed));

        if (empty($update)) {
            return false;
        }

        return $this->update($update);
    }

    /**
     * Get photo details for display.
     */
    public function getDetailsAttribute(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'caption' => $this->caption,
            'description' => $this->description,
            'work_stage' => $this->work_stage,
            'work_stage_label' => $this->formatted_work_stage,
            'original_filename' => $this->original_filename,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'file_size' => $this->formatted_file_size,
            'mime_type' => $this->mime_type,
            'is_image' => $this->is_image,
            'uploader' => $this->uploader?->name,
            'uploader_id' => $this->uploaded_by,
            'created_at' => $this->created_at->toIso8601String(),
            'formatted_date' => $this->created_at->format('d M Y, H:i'),
        ];
    }
}
