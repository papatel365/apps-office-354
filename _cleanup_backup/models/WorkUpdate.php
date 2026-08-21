<?php

namespace App\Models;

use App\Core\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkUpdate extends Model
{
    use HasFactory;
    use HasAuditLog;

    // Boot method to auto-generate UUID
    protected static function booted(): void
    {
        static::creating(function (WorkUpdate $workUpdate) {
            if (empty($workUpdate->uuid)) {
                $workUpdate->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'tenant_id',
        'task_id',
        'user_id',
        'completed_work',
        'in_progress_work',
        'notes',
        'progress',
        'progress_manual',
        'photo_count',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'progress_manual' => 'boolean',
            'photo_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\System\Models\User::class, 'user_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(TaskPhoto::class, 'work_update_id');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeForTask($query, int $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getHasCompletedWorkAttribute(): bool
    {
        return !empty($this->completed_work);
    }

    public function getHasInProgressWorkAttribute(): bool
    {
        return !empty($this->in_progress_work);
    }

    public function getHasNotesAttribute(): bool
    {
        return !empty($this->notes);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('d M Y, H:i');
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // =====================================================
    // HELPERS
    // =====================================================

    /**
     * Get work update details for display.
     */
    public function getDetailsAttribute(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'task_id' => $this->task_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'completed_work' => $this->completed_work,
            'in_progress_work' => $this->in_progress_work,
            'notes' => $this->notes,
            'progress' => $this->progress,
            'photo_count' => $this->photo_count,
            'formatted_date' => $this->formatted_date,
            'time_ago' => $this->time_ago,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
