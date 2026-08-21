<?php

namespace App\Models;

use App\Core\Traits\HasAuditLog;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskApproval extends Model
{
    use HasFactory;
    use HasAuditLog;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'task_id',
        'user_id',
        'version',
        'action',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';
    const ACTION_REOPEN = 'reopen';

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
    // ACCESSORS
    // =====================================================

    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            self::ACTION_APPROVE => 'Disetujui',
            self::ACTION_REJECT => 'Ditolak',
            self::ACTION_REOPEN => 'Dibuka Ulang',
            default => ucfirst($this->action),
        };
    }

    public function getIsApprovalAttribute(): bool
    {
        return $this->action === self::ACTION_APPROVE;
    }

    public function getIsRejectionAttribute(): bool
    {
        return $this->action === self::ACTION_REJECT;
    }

    // =====================================================
    // HELPERS
    // =====================================================

    /**
     * Get the next version number for a task
     */
    public static function getNextVersion(Task $task): int
    {
        $lastVersion = self::where('task_id', $task->id)
            ->max('version');

        return ($lastVersion ?? 0) + 1;
    }

    /**
     * Record an approval
     */
    public static function recordApproval(Task $task, User $user, ?string $notes = null): self
    {
        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'version' => self::getNextVersion($task),
            'action' => self::ACTION_APPROVE,
            'notes' => $notes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Record a rejection
     */
    public static function recordRejection(Task $task, User $user, string $reason): self
    {
        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'version' => self::getNextVersion($task),
            'action' => self::ACTION_REJECT,
            'notes' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Record a reopen
     */
    public static function recordReopen(Task $task, User $user, ?string $reason = null): self
    {
        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'version' => self::getNextVersion($task),
            'action' => self::ACTION_REOPEN,
            'notes' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get all approvals for a task
     */
    public static function getApprovalHistory(Task $task): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('task_id', $task->id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
