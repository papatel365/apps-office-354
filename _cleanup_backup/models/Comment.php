<?php

namespace App\Models;

use App\Core\Traits\HasAuditLog;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLog;

    // Boot method to auto-generate UUID
    protected static function booted(): void
    {
        static::creating(function (Comment $comment) {
            if (empty($comment->uuid)) {
                $comment->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'tenant_id',
        'commentable_type',
        'commentable_id',
        'user_id',
        'content',
        'parent_id',
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

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function attachments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
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

    public function scopeForCommentable($query, string $type, int $id)
    {
        return $query->where('commentable_type', $type)
            ->where('commentable_id', $id);
    }

    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getAuthorNameAttribute(): string
    {
        return $this->user?->name ?? 'Unknown';
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // =====================================================
    // HELPERS
    // =====================================================

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }
}
