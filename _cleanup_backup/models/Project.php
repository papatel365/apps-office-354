<?php

namespace App\Models;

use App\Core\Traits\BelongsToCompany;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasActivityLog;
use App\Core\Traits\HasAuditLog;
use App\Modules\System\Models\User;
use App\Traits\NotifiableActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Project extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasAuditLog;
    use HasActivityLog;
    use NotifiableActivity;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'company_id',
        'project_number',
        'name',
        'description',
        'user_id',
        'status',
        'priority',
        'start_date',
        'deadline',
        'completion_date',
        'progress_percent',
        'billing_type',
        'budget',
        'estimated_hours',
        'cost',
        'currency_code',
        'notification_settings',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'deadline' => 'date',
            'completion_date' => 'date',
            'progress_percent' => 'integer',
            'budget' => 'decimal:4',
            'estimated_hours' => 'decimal:2',
            'cost' => 'decimal:4',
            'notification_settings' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const BILLING_FIXED = 'fixed';
    const BILLING_HOURLY = 'hourly';
    const BILLING_TASK_BASED = 'task_based';

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Client relationship removed - Client model no longer exists

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('target_date', 'asc');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(ProjectTag::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class)->orderBy('created_at', 'desc');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeOnHold($query)
    {
        return $query->where('status', self::STATUS_ON_HOLD);
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('deadline', '<=', now()->addDays($days))
            ->where('deadline', '>=', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: Filter projects visible to a user based on CRM permissions.
     *
     * LOGIC:
     * - If user has Superadmin/Admin/Director role → see all projects in company
     * - If user has Global scope (scope_global = true) → see all projects in company
     * - If user has Own scope (scope_own = true, scope_global = false) → see:
     *   - Projects where user is the CREATOR (created_by)
     *   - OR Projects where user is a MEMBER (in project_members table)
     * - No scope → no access (return empty)
     *
     * @param Builder $query
     * @param mixed $user User model or user ID
     * @return Builder
     */
    public function scopeVisibleTo($query, $user)
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        $userId = is_object($user) ? $user->id : $user;

        // Check if user has Director/Admin role
        if (self::isUserDirectorOrAdmin($user)) {
            return $query->where('company_id', $user->company_id);
        }

        // Use UserPermissionService to check scope
        $permService = \App\Services\Permission\UserPermissionService::forUser($user);

        // Check global scope
        if ($permService->isGlobalScope('projects')) {
            return $query->where('company_id', $user->company_id);
        }

        // Check own scope - see projects where user is creator OR member
        if ($permService->isOwnScope('projects')) {
            return $query->where('company_id', $user->company_id)
                ->where(function ($q) use ($userId) {
                    $q->where('created_by', $userId)
                      ->orWhereHas('members', fn($q2) => $q2->where('user_id', $userId));
                });
        }

        // No scope = no access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Check if user is Director or Admin.
     */
    public static function isUserDirectorOrAdmin($user): bool
    {
        if (!$user) {
            return false;
        }

        $companyRole = $user->company_role ?? null;
        $userType = $user->user_type ?? null;

        if (in_array($companyRole, ['director', 'admin']) || in_array($userType, ['director', 'admin'])) {
            return true;
        }

        return $user->hasRole(['director', 'admin']);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getFormattedBudgetAttribute(): string
    {
        return number_format($this->budget, 2);
    }

    public function getFormattedEstimatedHoursAttribute(): string
    {
        return number_format($this->estimated_hours ?? 0, 2);
    }

    public function getFormattedCostAttribute(): string
    {
        return number_format($this->cost, 2);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline && $this->deadline->isPast() && !$this->is_completed;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getDaysUntilDeadlineAttribute(): ?int
    {
        if (!$this->deadline) {
            return null;
        }

        return now()->diffInDays($this->deadline, false);
    }

    public function getTagsListAttribute(): string
    {
        return $this->tags->pluck('name')->implode(', ');
    }

    public function getStatusBadgeAttribute(): array
    {
        $badges = [
            'not_started' => ['type' => 'secondary', 'text' => 'Belum Dimulai'],
            'in_progress' => ['type' => 'primary', 'text' => 'Sedang Berlangsung'],
            'on_hold' => ['type' => 'warning', 'text' => 'Ditunda'],
            'cancelled' => ['type' => 'danger', 'text' => 'Dibatalkan'],
            'completed' => ['type' => 'success', 'text' => 'Selesai'],
        ];

        return $badges[$this->status] ?? ['type' => 'secondary', 'text' => ucfirst($this->status)];
    }

    public function getPriorityBadgeAttribute(): array
    {
        $badges = [
            'low' => ['type' => 'secondary', 'text' => 'Rendah'],
            'medium' => ['type' => 'primary', 'text' => 'Sedang'],
            'high' => ['type' => 'danger', 'text' => 'Tinggi'],
            'urgent' => ['type' => 'danger', 'text' => 'Urgent'],
        ];

        return $badges[$this->priority] ?? ['type' => 'secondary', 'text' => ucfirst($this->priority)];
    }

    // =====================================================
    // MUTATORS
    // =====================================================

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = ucfirst(trim($value));
    }

    // =====================================================
    // HELPERS
    // =====================================================

    public function complete(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completion_date' => now(),
            'progress_percent' => 100,
        ]);
    }

    /**
     * Update project progress from tasks and milestones.
     * Considers both direct tasks and milestone tasks.
     */
    public function updateProgress(): void
    {
        $totalTasks = 0;
        $completedTasks = 0;

        // Direct tasks (not assigned to any milestone)
        $directTasks = $this->tasks()->whereNull('milestone_id')->get();
        $totalTasks += $directTasks->count();
        $completedTasks += $directTasks->where('status', 'completed')->count();

        // Tasks from milestones
        $milestoneTasks = $this->tasks()->whereNotNull('milestone_id')->get();
        $totalTasks += $milestoneTasks->count();
        $completedTasks += $milestoneTasks->where('status', 'completed')->count();

        // Calculate progress
        if ($totalTasks === 0) {
            $progress = 0;
        } else {
            $progress = round(($completedTasks / $totalTasks) * 100);
        }

        // Auto-complete project if all tasks are done
        if ($totalTasks > 0 && $completedTasks === $totalTasks && $this->status !== self::STATUS_COMPLETED) {
            $this->update([
                'progress_percent' => $progress,
                'status' => self::STATUS_COMPLETED,
                'completion_date' => now(),
            ]);
        } else {
            $this->update(['progress_percent' => $progress]);
        }

        // Update milestone auto-progress for all milestones
        foreach ($this->milestones as $milestone) {
            $milestone->calculateAutoProgress();
        }
    }

    /**
     * Generate next project number.
     */
    public static function generateNumber(): string
    {
        $year = date('Y');

        return \DB::transaction(function () use ($year) {
            // Use lock to prevent race condition when multiple users create projects simultaneously
            $lastProject = static::where('project_number', 'like', "PRJ-{$year}-%")
                ->lockForUpdate()
                ->orderBy('project_number', 'desc')
                ->first();

            $sequence = 1;

            if ($lastProject) {
                // Extract sequence from project_number (e.g., "PRJ-2026-0001" -> extract "0001" and add 1)
                $parts = explode('-', $lastProject->project_number);
                $sequence = isset($parts[2]) ? ((int) $parts[2]) + 1 : 1;
            }

            return sprintf('PRJ-%s-%04d', $year, $sequence);
        });
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_NOT_STARTED => 'Belum Dimulai',
            self::STATUS_IN_PROGRESS => 'Sedang Berlangsung',
            self::STATUS_ON_HOLD => 'Ditunda',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_COMPLETED => 'Selesai',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Rendah',
            self::PRIORITY_MEDIUM => 'Sedang',
            self::PRIORITY_HIGH => 'Tinggi',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    // =====================================================
    // SOFT DELETE & FORCE DELETE
    // =====================================================

    /**
     * Perform a soft delete (default behavior).
     * The project will be moved to trash.
     */
    public function trash(): bool
    {
        return $this->delete();
    }

    /**
     * Restore a soft-deleted project from trash.
     */
    public function restore(): bool
    {
        return $this->restore();
    }

    /**
     * Permanently delete the project and all its related data.
     * This action CANNOT be undone.
     *
     * Deletes in order:
     * 1. Tasks (with all related data: assignees, followers, comments, attachments, checklists, activities)
     * 2. Project Members
     * 3. Project Milestones
     * 4. Project Notes
     * 5. Project Tags
     * 6. Project Attachments (morphed)
     * 7. Project Comments (morphed)
     * 8. Company Notifications related to this project
     * 9. Activity logs related to this project
     * 10. Audit logs related to this project
     * 11. Finally, the project itself
     */
    public function forceDeleteWithRelations(): bool
    {
        return DB::transaction(function () {
            $projectId = $this->id;
            $projectName = $this->name;

            Log::info("Force deleting project {$projectId}: {$projectName}");

            // 1. Delete Tasks and all their related data
            $tasks = Task::with([
                'assignees',
                'followers',
                'comments',
                'attachments',
                'checklists',
                'activities',
            ])->where('project_id', $projectId)->get();

            foreach ($tasks as $task) {
                // Delete task assignees (pivot)
                $task->assignees()->detach();

                // Delete task followers (pivot)
                $task->followers()->detach();

                // Delete task comments (morphed)
                $task->comments()->delete();

                // Delete task attachments (morphed)
                $task->attachments()->delete();

                // Delete task checklists
                $task->checklists()->delete();

                // Delete task activities
                $task->activities()->delete();

                // Delete the task itself (with soft delete)
                $task->forceDelete();
            }

            Log::info("Deleted " . $tasks->count() . " tasks for project {$projectId}");

            // 2. Delete Project Members
            $membersCount = $this->members()->count();
            $this->members()->delete();
            Log::info("Deleted {$membersCount} project members for project {$projectId}");

            // 3. Delete Project Milestones (cascades to milestone tasks via foreign key or manual delete)
            $milestones = ProjectMilestone::where('project_id', $projectId)->get();
            foreach ($milestones as $milestone) {
                // Delete milestone tasks first
                Task::where('milestone_id', $milestone->id)->delete();
                $milestone->delete();
            }
            Log::info("Deleted " . $milestones->count() . " milestones for project {$projectId}");

            // 4. Delete Project Notes
            $notesCount = $this->notes()->count();
            $this->notes()->delete();
            Log::info("Deleted {$notesCount} project notes for project {$projectId}");

            // 5. Delete Project Tags
            $tagsCount = $this->tags()->count();
            $this->tags()->delete();
            Log::info("Deleted {$tagsCount} project tags for project {$projectId}");

            // 6. Delete Project Attachments (morphed)
            $attachmentsCount = $this->attachments()->count();
            $this->attachments()->delete();
            Log::info("Deleted {$attachmentsCount} project attachments for project {$projectId}");

            // 7. Delete Project Comments (morphed)
            $commentsCount = $this->comments()->count();
            $this->comments()->delete();
            Log::info("Deleted {$commentsCount} project comments for project {$projectId}");

            // 8. Delete Company Notifications related to this project
            $notificationsDeleted = DB::table('company_notifications')
                ->where('notifiable_type', self::class)
                ->where('notifiable_id', $projectId)
                ->delete();
            Log::info("Deleted {$notificationsDeleted} company notifications for project {$projectId}");

            // 9. Delete Activity logs related to this project
            // Get the UUID for activity log lookup
            $activityLogsDeleted = DB::table('activity_logs')
                ->where('subject_type', self::class)
                ->where('subject_id', $projectId)
                ->delete();
            Log::info("Deleted {$activityLogsDeleted} activity logs for project {$projectId}");

            // Also delete activities for related tasks
            $taskUuids = $tasks->pluck('uuid')->toArray();
            if (!empty($taskUuids)) {
                $taskActivitiesDeleted = DB::table('activity_logs')
                    ->where('subject_type', Task::class)
                    ->whereIn('subject_id', $taskUuids)
                    ->delete();
                Log::info("Deleted {$taskActivitiesDeleted} task activity logs for project {$projectId}");
            }

            // 10. Delete Audit logs related to this project
            $auditLogsDeleted = DB::table('audit_logs')
                ->where('auditable_type', self::class)
                ->where('auditable_id', $projectId)
                ->delete();
            Log::info("Deleted {$auditLogsDeleted} audit logs for project {$projectId}");

            // 11. Finally, permanently delete the project
            $this->forceDelete();

            Log::info("Project {$projectId} ({$projectName}) permanently deleted with all related data");

            return true;
        });
    }

    /**
     * Get trashed projects (soft deleted).
     */
    public static function getTrashed()
    {
        return static::onlyTrashed()->with('creator');
    }

    /**
     * Check if project is in trash.
     */
    public function isInTrash(): bool
    {
        return $this->trashed();
    }

    /**
     * Get count of related records for display.
     */
    public function getRelatedCounts(): array
    {
        return [
            'tasks' => $this->tasks()->count(),
            'members' => $this->members()->count(),
            'milestones' => $this->milestones()->count(),
            'notes' => $this->notes()->count(),
            'tags' => $this->tags()->count(),
            'attachments' => $this->attachments()->count(),
            'comments' => $this->comments()->count(),
        ];
    }
}
