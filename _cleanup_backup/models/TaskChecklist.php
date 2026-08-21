<?php

namespace App\Models;

use App\Core\Traits\BelongsToTenant;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskChecklist extends Model
{
    use HasFactory;
    use BelongsToTenant;

    // Boot method to auto-generate UUID
    protected static function booted(): void
    {
        static::creating(function (TaskChecklist $checklist) {
            if (empty($checklist->uuid)) {
                $checklist->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'tenant_id',
        'task_id',
        'user_id',
        'content',
        'is_checked',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_checked' => 'boolean',
            'sort_order' => 'integer',
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
        return $this->belongsTo(User::class);
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeChecked($query)
    {
        return $query->where('is_checked', true);
    }

    public function scopeUnchecked($query)
    {
        return $query->where('is_checked', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }
}
