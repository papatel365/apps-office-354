<?php

namespace App\Models;

use App\Core\Traits\HasAuditLog;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskComment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLog;

    // Boot method to register model events
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate UUID when creating a new comment
        static::creating(function (TaskComment $comment) {
            if (empty($comment->uuid)) {
                $comment->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'tenant_id',
        'task_id',
        'user_id',
        'content',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
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
        return $this->belongsTo(User::class);
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeExternal($query)
    {
        return $query->where('is_internal', false);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getAuthorNameAttribute(): string
    {
        return $this->user?->name ?? 'Unknown';
    }
}
