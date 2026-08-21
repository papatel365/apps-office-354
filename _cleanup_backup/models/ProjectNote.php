<?php

namespace App\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectNote extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;
    use HasAuditLog;

    // Boot method to auto-generate UUID
    protected static function booted(): void
    {
        static::creating(function (ProjectNote $note) {
            if (empty($note->uuid)) {
                $note->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'tenant_id',
        'company_id',
        'project_id',
        'user_id',
        'title',
        'content',
        'color',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
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

    public function getTagsArrayAttribute(): array
    {
        return $this->tags ?? [];
    }

    public function getColorClassAttribute(): string
    {
        $colorMap = [
            '#3B82F6' => 'blue',
            '#EF4444' => 'red',
            '#10B981' => 'green',
            '#F59E0B' => 'yellow',
            '#8B5CF6' => 'purple',
            '#EC4899' => 'pink',
            '#06B6D4' => 'cyan',
            '#F97316' => 'orange',
        ];

        return $colorMap[$this->color] ?? 'blue';
    }

    // =====================================================
    // HELPERS
    // =====================================================

    public function getExcerpt(int $length = 100): string
    {
        $text = strip_tags($this->content);
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }

    public function isOwner(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}
