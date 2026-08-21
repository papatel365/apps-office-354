<?php

namespace App\Models;

use App\Core\Traits\HasAuditLog;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TaskActivity extends Model
{
    use HasFactory;
    use HasAuditLog;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'task_id',
        'user_id',
        'activity_type',
        'old_value',
        'new_value',
        'metadata',
        'version',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // =====================================================
    // HELPER: Get tenant_id with fallback
    // =====================================================

    /**
     * Get tenant_id with fallback to current tenant context.
     * Handles tasks created with null tenant_id.
     *
     * Priority:
     * 1. Task's own tenant_id
     * 2. User's tenant_id (most reliable for current operation)
     * 3. TenantService current tenant
     * 4. Company-tenant relationship (if company belongs to a tenant)
     */
    protected static function getTenantId(Task $task, $user = null): ?int
    {
        // 1. First check if task has a valid tenant_id
        if (!empty($task->tenant_id)) {
            return (int) $task->tenant_id;
        }

        // 2. Fallback to user's tenant_id (most reliable for current operation)
        if ($user && !empty($user->tenant_id)) {
            return (int) $user->tenant_id;
        }

        // 3. Fallback to TenantService current tenant
        if (function_exists('app') && app()->bound(\App\Services\TenantService::class)) {
            $tenantId = app(\App\Services\TenantService::class)->getCurrentTenantId();
            if ($tenantId) {
                return (int) $tenantId;
            }
        }

        // 4. Try to get tenant from user's company (if company has a tenant relationship)
        if ($user && !empty($user->company_id)) {
            // Try to find a tenant associated with this company
            // Check if Company model has a tenant relationship
            if (method_exists($user, 'company') && $user->company) {
                // If company has a tenant_id column
                if (!empty($user->company->tenant_id)) {
                    return (int) $user->company->tenant_id;
                }
            }
        }

        return null;
    }

    /**
     * Safely get tenant_id with logging for debugging.
     * Returns null if no valid tenant found instead of failing FK constraint.
     */
    protected static function safeGetTenantId(Task $task, $user = null): ?int
    {
        $tenantId = self::getTenantId($task, $user);

        if (!$tenantId) {
            \Log::warning('TaskActivity: No valid tenant_id found', [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'task_tenant_id' => $task->tenant_id,
                'user_id' => $user?->id,
                'user_tenant_id' => $user?->tenant_id,
            ]);
        }

        return $tenantId;
    }

    // =====================================================
    // CONSTANTS - Activity Types
    // =====================================================

    const TYPE_CREATED = 'created';
    const TYPE_UPDATED = 'updated';
    const TYPE_DELETED = 'deleted';
    const TYPE_RESTORED = 'restored';
    const TYPE_STATUS_CHANGED = 'status_changed';
    const TYPE_ASSIGNED = 'assigned';
    const TYPE_UNASSIGNED = 'unassigned';
    const TYPE_COMMENT_ADDED = 'comment_added';
    const TYPE_PHOTO_UPLOADED = 'photo_uploaded';
    const TYPE_PHOTO_UPDATED = 'photo_updated';
    const TYPE_PHOTO_DELETED = 'photo_deleted';
    const TYPE_PHOTO_RESTORED = 'photo_restored';
    const TYPE_APPROVED = 'approved';
    const TYPE_REJECTED = 'rejected';
    const TYPE_REOPENED = 'reopened';
    const TYPE_DEADLINE_CHANGED = 'deadline_changed';
    const TYPE_PRIORITY_CHANGED = 'priority_changed';
    const TYPE_PROGRESS_UPDATED = 'progress_updated';
    const TYPE_MILESTONE_CHANGED = 'milestone_changed';

    // Activity type labels
    const TYPE_LABELS = [
        self::TYPE_CREATED => 'Task dibuat',
        self::TYPE_UPDATED => 'Task diperbarui',
        self::TYPE_DELETED => 'Task dihapus',
        self::TYPE_RESTORED => 'Task dipulihkan',
        self::TYPE_STATUS_CHANGED => 'Status berubah',
        self::TYPE_ASSIGNED => 'Assignee ditambahkan',
        self::TYPE_UNASSIGNED => 'Assignee dihapus',
        self::TYPE_COMMENT_ADDED => 'Komentar ditambahkan',
        self::TYPE_PHOTO_UPLOADED => 'Foto diunggah',
        self::TYPE_PHOTO_UPDATED => 'Foto diperbarui',
        self::TYPE_PHOTO_DELETED => 'Foto dihapus',
        self::TYPE_PHOTO_RESTORED => 'Foto dipulihkan',
        self::TYPE_APPROVED => 'Task disetujui',
        self::TYPE_REJECTED => 'Task ditolak',
        self::TYPE_REOPENED => 'Task dibuka ulang',
        self::TYPE_DEADLINE_CHANGED => 'Deadline diubah',
        self::TYPE_PRIORITY_CHANGED => 'Priority diubah',
        self::TYPE_PROGRESS_UPDATED => 'Progress diperbarui',
        self::TYPE_MILESTONE_CHANGED => 'Milestone diubah',
    ];

    // Icon names (Heroicons)
    const TYPE_ICONS = [
        self::TYPE_CREATED => 'heroicon-o-plus-circle',
        self::TYPE_UPDATED => 'heroicon-o-pencil',
        self::TYPE_DELETED => 'heroicon-o-trash',
        self::TYPE_RESTORED => 'heroicon-o-arrow-uturn-left',
        self::TYPE_STATUS_CHANGED => 'heroicon-o-arrows-right-left',
        self::TYPE_ASSIGNED => 'heroicon-o-user-plus',
        self::TYPE_UNASSIGNED => 'heroicon-o-user-minus',
        self::TYPE_COMMENT_ADDED => 'heroicon-o-chat-bubble-left-right',
        self::TYPE_PHOTO_UPLOADED => 'heroicon-o-photo',
        self::TYPE_PHOTO_UPDATED => 'heroicon-o-camera',
        self::TYPE_PHOTO_DELETED => 'heroicon-o-photo',
        self::TYPE_PHOTO_RESTORED => 'heroicon-o-photo',
        self::TYPE_APPROVED => 'heroicon-o-check-circle',
        self::TYPE_REJECTED => 'heroicon-o-x-circle',
        self::TYPE_REOPENED => 'heroicon-o-arrow-path',
        self::TYPE_DEADLINE_CHANGED => 'heroicon-o-calendar',
        self::TYPE_PRIORITY_CHANGED => 'heroicon-o-flag',
        self::TYPE_PROGRESS_UPDATED => 'heroicon-o-bars-3',
        self::TYPE_MILESTONE_CHANGED => 'heroicon-o-folder',
    ];

    // Color classes for activity type
    const TYPE_COLORS = [
        self::TYPE_CREATED => 'text-green-600 bg-green-50',
        self::TYPE_UPDATED => 'text-blue-600 bg-blue-50',
        self::TYPE_DELETED => 'text-red-600 bg-red-50',
        self::TYPE_RESTORED => 'text-purple-600 bg-purple-50',
        self::TYPE_STATUS_CHANGED => 'text-indigo-600 bg-indigo-50',
        self::TYPE_ASSIGNED => 'text-cyan-600 bg-cyan-50',
        self::TYPE_UNASSIGNED => 'text-gray-600 bg-gray-50',
        self::TYPE_COMMENT_ADDED => 'text-yellow-600 bg-yellow-50',
        self::TYPE_PHOTO_UPLOADED => 'text-pink-600 bg-pink-50',
        self::TYPE_PHOTO_UPDATED => 'text-orange-600 bg-orange-50',
        self::TYPE_PHOTO_DELETED => 'text-red-600 bg-red-50',
        self::TYPE_PHOTO_RESTORED => 'text-purple-600 bg-purple-50',
        self::TYPE_APPROVED => 'text-green-600 bg-green-50',
        self::TYPE_REJECTED => 'text-red-600 bg-red-50',
        self::TYPE_REOPENED => 'text-orange-600 bg-orange-50',
        self::TYPE_DEADLINE_CHANGED => 'text-amber-600 bg-amber-50',
        self::TYPE_PRIORITY_CHANGED => 'text-red-600 bg-red-50',
        self::TYPE_PROGRESS_UPDATED => 'text-blue-600 bg-blue-50',
        self::TYPE_MILESTONE_CHANGED => 'text-violet-600 bg-violet-50',
    ];

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

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->activity_type] ?? ucfirst($this->activity_type);
    }

    public function getIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->activity_type] ?? 'heroicon-o-circle';
    }

    public function getColorClassAttribute(): string
    {
        return self::TYPE_COLORS[$this->activity_type] ?? 'text-gray-600 bg-gray-50';
    }

    public function getDescriptionAttribute(): string
    {
        $description = $this->type_label;

        if ($this->user) {
            $description .= ' oleh ' . $this->user->name;
        }

        if ($this->old_value && $this->new_value) {
            $description .= " ({$this->old_value} → {$this->new_value})";
        }

        return $description;
    }

    // =====================================================
    // HELPERS
    // =====================================================

    /**
     * Log task creation
     */
    public static function logCreated(Task $task, $user): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null; // Skip logging if no valid tenant
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_CREATED,
            'new_value' => $task->status,
            'note' => 'Task dibuat: ' . $task->name,
        ]);
    }

    /**
     * Log status change
     */
    public static function logStatusChanged(Task $task, $user, string $oldStatus, string $newStatus, ?string $note = null): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_STATUS_CHANGED,
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
            'note' => $note,
        ]);
    }

    /**
     * Log field change
     */
    public static function logFieldChanged(Task $task, $user, string $field, $oldValue, $newValue): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        $type = match($field) {
            'priority' => self::TYPE_PRIORITY_CHANGED,
            'due_date' => self::TYPE_DEADLINE_CHANGED,
            'progress' => self::TYPE_PROGRESS_UPDATED,
            default => self::TYPE_UPDATED,
        };

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => $type,
            'old_value' => $field . ':' . $oldValue,
            'new_value' => $field . ':' . $newValue,
            'metadata' => [
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ],
        ]);
    }

    /**
     * Log assignment
     */
    public static function logAssigned(Task $task, $user, User $assignedUser, ?string $role = null): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_ASSIGNED,
            'new_value' => $assignedUser->id,
            'metadata' => [
                'assigned_user_id' => $assignedUser->id,
                'assigned_user_name' => $assignedUser->name,
                'role' => $role,
            ],
            'note' => "Menambahkan {$assignedUser->name}" . ($role ? " sebagai {$role}" : ''),
        ]);
    }

    /**
     * Log photo upload with detailed metadata
     */
    public static function logPhotoUploaded(Task $task, $user, int $photoCount, ?array $captions = null, ?string $workStage = null): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        $note = "Mengunggah {$photoCount} foto";
        if ($captions && count($captions) > 0) {
            $note .= ": \"" . implode('", "', $captions) . "\"";
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_PHOTO_UPLOADED,
            'metadata' => [
                'photo_count' => $photoCount,
                'captions' => $captions,
                'work_stage' => $workStage,
            ],
            'note' => $note,
        ]);
    }

    /**
     * Log photo metadata update
     */
    public static function logPhotoUpdated(Task $task, $user, TaskPhoto $photo, array $changes = []): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        $note = "Memperbarui metadata foto";
        if (isset($changes['old_caption']) && isset($changes['new_caption'])) {
            if ($changes['old_caption'] !== $changes['new_caption']) {
                $note = "Mengubah caption foto: \"{$changes['old_caption']}\" → \"{$changes['new_caption']}\"";
            }
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_PHOTO_UPDATED,
            'metadata' => [
                'photo_id' => $photo->id,
                'changes' => $changes,
            ],
            'note' => $note,
        ]);
    }

    /**
     * Log photo deletion
     */
    public static function logPhotoDeleted(Task $task, $user, string $photoName, ?string $photoCaption = null): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        $note = "Menghapus foto";
        if ($photoCaption) {
            $note = "Menghapus foto \"{$photoCaption}\"";
        } elseif ($photoName) {
            $note = "Menghapus foto \"{$photoName}\"";
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_PHOTO_DELETED,
            'metadata' => [
                'photo_name' => $photoName,
                'photo_caption' => $photoCaption,
            ],
            'note' => $note,
        ]);
    }

    /**
     * Log photo restoration
     */
    public static function logPhotoRestored(Task $task, $user, string $photoName): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_PHOTO_RESTORED,
            'metadata' => [
                'photo_name' => $photoName,
            ],
            'note' => "Memulihkan foto \"{$photoName}\"",
        ]);
    }

    /**
     * Log comment
     */
    public static function logCommentAdded(Task $task, $user, string $commentPreview): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_COMMENT_ADDED,
            'note' => Str::limit($commentPreview, 100),
        ]);
    }

    /**
     * Log approval
     */
    public static function logApproved(Task $task, $user, ?string $notes = null): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_APPROVED,
            'note' => $notes ?? 'Task disetujui',
        ]);
    }

    /**
     * Log rejection
     */
    public static function logRejected(Task $task, $user, string $reason, int $version): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_REJECTED,
            'version' => $version,
            'metadata' => [
                'reason' => $reason,
                'version' => $version,
            ],
            'note' => "Tolak #{$version}: {$reason}",
        ]);
    }

    /**
     * Log reopen
     */
    public static function logReopened(Task $task, $user, ?string $reason = null): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_REOPENED,
            'note' => $reason ?? 'Task dibuka ulang',
        ]);
    }

    /**
     * Log deletion
     */
    public static function logDeleted(Task $task, $user): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            \Log::warning('TaskActivity::logDeleted skipped - no valid tenant_id', [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'task_tenant_id' => $task->tenant_id,
                'user_id' => $user?->id,
                'user_tenant_id' => $user?->tenant_id,
            ]);
            return null;
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_DELETED,
            'metadata' => [
                'task_name' => $task->name,
                'task_number' => $task->task_number,
            ],
            'note' => 'Task dihapus',
        ]);
    }

    /**
     * Log restoration
     */
    public static function logRestored(Task $task, $user): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_RESTORED,
            'note' => 'Task dipulihkan dari recycle bin',
        ]);
    }

    /**
     * Log milestone change (move task to/from milestone)
     */
    public static function logMilestoneChanged(Task $task, $user, ?int $oldMilestoneId, ?int $newMilestoneId): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        $oldMilestoneName = null;
        $newMilestoneName = null;

        if ($oldMilestoneId) {
            $oldMilestone = \App\Models\ProjectMilestone::find($oldMilestoneId);
            $oldMilestoneName = $oldMilestone?->name ?? 'Milestone #' . $oldMilestoneId;
        }

        if ($newMilestoneId) {
            $newMilestone = \App\Models\ProjectMilestone::find($newMilestoneId);
            $newMilestoneName = $newMilestone?->name ?? 'Milestone #' . $newMilestoneId;
        }

        $note = 'Memindahkan task';
        if ($oldMilestoneName && $newMilestoneName) {
            $note = "Memindahkan task dari \"{$oldMilestoneName}\" ke \"{$newMilestoneName}\"";
        } elseif ($oldMilestoneName) {
            $note = "Mengeluarkan task dari \"{$oldMilestoneName}\"";
        } elseif ($newMilestoneName) {
            $note = "Memindahkan task ke \"{$newMilestoneName}\"";
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_MILESTONE_CHANGED,
            'old_value' => $oldMilestoneId ? 'milestone:' . $oldMilestoneId : null,
            'new_value' => $newMilestoneId ? 'milestone:' . $newMilestoneId : null,
            'metadata' => [
                'old_milestone_id' => $oldMilestoneId,
                'old_milestone_name' => $oldMilestoneName,
                'new_milestone_id' => $newMilestoneId,
                'new_milestone_name' => $newMilestoneName,
            ],
            'note' => $note,
        ]);
    }

    /**
     * Log dates change (start_date, due_date)
     */
    public static function logDatesChanged(Task $task, $user, ?string $oldStartDate, ?string $oldDueDate, ?string $newStartDate, ?string $newDueDate): ?self
    {
        $tenantId = self::safeGetTenantId($task, $user);
        if (!$tenantId) {
            return null;
        }

        $note = 'Mengubah tanggal task';
        if ($oldStartDate && $newStartDate) {
            $note = "Mengubah tanggal dari {$oldStartDate} menjadi {$newStartDate}";
        } elseif ($newStartDate && !$oldStartDate) {
            $note = "Mengatur tanggal mulai menjadi {$newStartDate}";
        }

        if ($oldDueDate !== $newDueDate) {
            if ($oldDueDate && $newDueDate) {
                $note .= ", deadline dari {$oldDueDate} menjadi {$newDueDate}";
            } elseif ($newDueDate && !$oldDueDate) {
                $note .= ", mengatur deadline menjadi {$newDueDate}";
            } elseif (!$newDueDate && $oldDueDate) {
                $note .= ", menghapus deadline";
            }
        }

        return self::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenantId,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'activity_type' => self::TYPE_STATUS_CHANGED,
            'old_value' => json_encode(['start_date' => $oldStartDate, 'due_date' => $oldDueDate]),
            'new_value' => json_encode(['start_date' => $newStartDate, 'due_date' => $newDueDate]),
            'metadata' => [
                'old_start_date' => $oldStartDate,
                'old_due_date' => $oldDueDate,
                'new_start_date' => $newStartDate,
                'new_due_date' => $newDueDate,
            ],
            'note' => $note,
        ]);
    }
}
