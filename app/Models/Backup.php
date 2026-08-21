<?php

namespace App\Models;

use App\Core\Traits\BelongsToCompany;
use App\Core\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use HasActivityLog;

    protected $table = 'backups';

    protected $fillable = [
        'uuid',
        'company_id',
        'backup_type',
        'filename',
        'filesize',
        'checksum',
        'disk',
        'path',
        'status',
        'is_scheduled',
        'schedule_type',
        'started_at',
        'finished_at',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'filesize' => 'integer',
        'is_scheduled' => 'boolean',
    ];

    // Backup type constants
    const TYPE_DATABASE = 'database';
    const TYPE_FILE = 'file';
    const TYPE_FULL = 'full';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_RESTORING = 'restoring';
    const STATUS_RESTORED = 'restored';

    // Schedule type constants
    const SCHEDULE_MANUAL = 'manual';
    const SCHEDULE_DAILY = 'daily';
    const SCHEDULE_WEEKLY = 'weekly';
    const SCHEDULE_MONTHLY = 'monthly';

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    public function scopeManual($query)
    {
        return $query->where('is_scheduled', false);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('backup_type', $type);
    }

    // Accessors
    public function getFormattedFilesizeAttribute(): string
    {
        $bytes = $this->filesize;

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }

        return round($bytes / (1024 * 1024 * 1024), 1) . ' GB';
    }

    public function getDurationInSecondsAttribute(): ?int
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        return $this->finished_at->diffInSeconds($this->started_at);
    }

    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->duration_in_seconds;

        if ($seconds === null) {
            return '-';
        }

        if ($seconds < 60) {
            return $seconds . ' detik';
        }

        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . ' menit ' . $remainingSeconds . ' detik';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . ' jam ' . $minutes . ' menit';
    }

    // Status badge color
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-gray-100 text-gray-800',
            self::STATUS_IN_PROGRESS => 'bg-blue-100 text-blue-800',
            self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            self::STATUS_FAILED => 'bg-red-100 text-red-800',
            self::STATUS_RESTORING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_RESTORED => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    // Type badge color
    public function getTypeBadgeClassAttribute(): string
    {
        return match($this->backup_type) {
            self::TYPE_DATABASE => 'bg-indigo-100 text-indigo-800',
            self::TYPE_FILE => 'bg-cyan-100 text-cyan-800',
            self::TYPE_FULL => 'bg-violet-100 text-violet-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    // File path helpers
    public function getFilePathAttribute(): string
    {
        return $this->path ?? 'backups/' . $this->backup_type . '/' . $this->filename;
    }

    public function getFullPathAttribute(): string
    {
        return storage_path('app/' . $this->file_path);
    }

    // Check if file exists
    public function getFileExistsAttribute(): bool
    {
        return \Illuminate\Support\Facades\Storage::disk($this->disk)->exists($this->file_path);
    }

    // Generate UUID before creating
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Backup $backup) {
            if (empty($backup->uuid)) {
                $backup->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }
}
