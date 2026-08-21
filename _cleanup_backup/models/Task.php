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

class Task extends Model
{
    use HasFactory;
    use SoftDeletes;

    // =====================================================
    // HELPER METHODS FOR ROLE CHECK
    // =====================================================

    /**
     * Check if user is Director or Admin.
     * Checks both company_role field AND Spatie roles.
     */
    public static function isUserDirectorOrAdmin($user): bool
    {
        if (!$user) {
            return false;
        }

        // Check company_role field (used in CRM)
        $companyRole = $user->company_role ?? null;
        $userType = $user->user_type ?? null;

        // Director/Admin if company_role or user_type is director/admin
        if (in_array($companyRole, ['director', 'admin']) || in_array($userType, ['director', 'admin'])) {
            return true;
        }

        // Also check Spatie roles
        return $user->hasRole(['director', 'admin']);
    }

    /**
     * Check if user is Director.
     */
    public static function isUserDirector($user): bool
    {
        if (!$user) {
            return false;
        }

        $companyRole = $user->company_role ?? null;
        $userType = $user->user_type ?? null;

        if (in_array($companyRole, ['director']) || in_array($userType, ['director'])) {
            return true;
        }

        return $user->hasRole(['director']);
    }

    /**
     * Check if user is Admin.
     */
    public static function isUserAdmin($user): bool
    {
        if (!$user) {
            return false;
        }

        $companyRole = $user->company_role ?? null;
        $userType = $user->user_type ?? null;

        if (in_array($companyRole, ['admin']) || in_array($userType, ['admin'])) {
            return true;
        }

        return $user->hasRole(['admin']);
    }

    /**
     * Check if user is Manager.
     */
    public static function isUserManager($user): bool
    {
        if (!$user) {
            return false;
        }

        $companyRole = $user->company_role ?? null;
        $userType = $user->user_type ?? null;

        if (in_array($companyRole, ['manager']) || in_array($userType, ['manager'])) {
            return true;
        }

        return $user->hasRole(['manager']);
    }
    use BelongsToTenant;
    use BelongsToCompany;
    use HasAuditLog;
    use HasActivityLog;
    use NotifiableActivity;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'company_id',
        'task_number',
        'project_id',
        'milestone_id',
        'parent_id',
        'name',
        'description',
        'tags',
        'priority',
        'status',
        'billable',
        'start_date',
        'due_date',
        'duration_days',
        'progress',
        'completed_at',
        'estimated_hours',
        'actual_hours',
        'hourly_rate',
        'milestone',
        'visibility',
        'recurring_type',
        'recurring_interval',
        'recurring_custom_days',
        'milestone_start_date',
        'milestone_due_date',
        'milestone_sort_order',
        'assigned_by',
        'created_by',
        'updated_by',
        'require_photo',
    ];

    protected function casts(): array
    {
        return [
            'billable' => 'boolean',
            'require_photo' => 'boolean',
            'start_date' => 'date',
            'due_date' => 'date',
            'milestone_start_date' => 'date',
            'milestone_due_date' => 'date',
            'milestone_sort_order' => 'integer',
            'completed_at' => 'datetime',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'duration_days' => 'integer',
            'progress' => 'integer',
            'recurring_interval' => 'integer',
            'recurring_custom_days' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS - STATUS WORKFLOW
    // =====================================================

    const STATUS_TO_DO = 'to_do';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_WAITING_APPROVAL = 'waiting_approval';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const VISIBILITY_INTERNAL = 'internal';
    const VISIBILITY_PROJECT = 'project';
    const VISIBILITY_CLIENT = 'client';

    // Status labels (Indonesian)
    const STATUS_LABELS = [
        self::STATUS_TO_DO => 'To Do',
        self::STATUS_IN_PROGRESS => 'Sedang Dikerjakan',
        self::STATUS_WAITING_APPROVAL => 'Menunggu Approval',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    // Status icons (Heroicons) - Professional icons instead of emojis
    const STATUS_ICONS = [
        self::STATUS_TO_DO => 'heroicon-o-document-text',
        self::STATUS_IN_PROGRESS => 'heroicon-o-arrow-right-circle',
        self::STATUS_WAITING_APPROVAL => 'heroicon-o-clock',
        self::STATUS_COMPLETED => 'heroicon-o-check-circle',
        self::STATUS_CANCELLED => 'heroicon-o-x-circle',
    ];

    // Status colors for UI
    const STATUS_COLORS = [
        self::STATUS_TO_DO => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'border' => 'border-gray-300', 'ring' => 'ring-gray-400'],
        self::STATUS_IN_PROGRESS => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'border' => 'border-blue-300', 'ring' => 'ring-blue-400'],
        self::STATUS_WAITING_APPROVAL => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'border' => 'border-amber-300', 'ring' => 'ring-amber-400'],
        self::STATUS_COMPLETED => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'border' => 'border-green-300', 'ring' => 'ring-green-400'],
        self::STATUS_CANCELLED => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'border' => 'border-red-300', 'ring' => 'ring-red-400'],
    ];

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assignees(): HasMany
    {
        return $this->hasMany(TaskAssignee::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function subTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    // Activity Log - tracks all changes to this task
    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->orderBy('created_at', 'desc');
    }

    // Approval history - tracks all approval/rejection attempts
    public function approvals(): HasMany
    {
        return $this->hasMany(TaskApproval::class)->orderBy('created_at', 'asc');
    }

    // Get latest approval (successful approval)
    public function latestApproval(): HasMany
    {
        return $this->approvals()->where('action', TaskApproval::ACTION_APPROVE)->latest();
    }

    // Get rejection count
    public function getRejectionCountAttribute(): int
    {
        return $this->approvals()->where('action', TaskApproval::ACTION_REJECT)->count();
    }

    // Photos/Evidence relationship
    public function photos(): HasMany
    {
        return $this->hasMany(TaskPhoto::class)->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    // Work Updates relationship
    public function workUpdates(): HasMany
    {
        return $this->hasMany(WorkUpdate::class)->orderBy('created_at', 'desc');
    }

    // Checklists relationship
    public function checklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class)->ordered();
    }

    // Followers relationship
    public function followers(): HasMany
    {
        return $this->hasMany(TaskFollower::class);
    }

    // Check if task requires photo evidence
    public function requiresPhoto(): bool
    {
        return $this->require_photo;
    }

    // Check if task has photo evidence
    public function hasEvidence(): bool
    {
        return $this->photos()->exists();
    }

    // Check if user can submit for approval (requires evidence if require_photo is true)
    public function canSubmitForApproval(): bool
    {
        if ($this->require_photo && !$this->hasEvidence()) {
            return false;
        }
        return true;
    }

    // Get validation message for missing evidence
    public function getMissingEvidenceMessage(): ?string
    {
        if ($this->require_photo && !$this->hasEvidence()) {
            return 'Task ini memerlukan bukti foto. Mohonunggah foto terlebih dahulu.';
        }
        return null;
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeToDo($query)
    {
        return $query->where('status', self::STATUS_TO_DO);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_TO_DO);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeWaitingApproval($query)
    {
        return $query->where('status', self::STATUS_WAITING_APPROVAL);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('due_date', '<=', now()->addDays($days))
            ->where('due_date', '>=', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByAssignee($query, int $userId)
    {
        return $query->whereHas('assignees', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function scopeRootTasks($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByMilestone($query, int $milestoneId)
    {
        return $query->where('milestone_id', $milestoneId);
    }

    public function scopeWithoutMilestone($query)
    {
        return $query->whereNull('milestone_id');
    }

    /**
     * Scope: Filter tasks visible to a user based on CRM permissions.
     *
     * LOGIC:
     * - If user has Superadmin/Admin/Director role → see all tasks in company
     * - If user has Global scope (scope_global = true) → see all tasks in company
     * - If user has Own scope (scope_own = true, scope_global = false) → see:
     *   - Tasks where user is the CREATOR (created_by)
     *   - OR Tasks where user is an ASSIGNEE (in task_assignees table)
     *   - OR Tasks where user is a FOLLOWER (in task_followers table)
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
        if ($permService->isGlobalScope('tasks')) {
            return $query->where('company_id', $user->company_id);
        }

        // Check own scope - see tasks where user is creator OR assignee OR follower
        if ($permService->isOwnScope('tasks')) {
            return $query->where('company_id', $user->company_id)
                ->where(function ($q) use ($userId) {
                    $q->where('created_by', $userId)
                      ->orWhereHas('assignees', fn($q2) => $q2->where('user_id', $userId))
                      ->orWhereHas('followers', fn($q3) => $q3->where('user_id', $userId));
                });
        }

        // No scope = no access
        return $query->whereRaw('1 = 0');
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->is_completed;
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    public function getDaysRemainingAttribute(): ?int
    {
        return $this->days_until_due;
    }

    public function getProgressAttribute(): float
    {
        if (!$this->subTasks->count()) {
            return $this->is_completed ? 100 : 0;
        }

        $completed = $this->subTasks->where('status', self::STATUS_COMPLETED)->count();
        return round(($completed / $this->subTasks->count()) * 100);
    }

    public function getAssignedUsersAttribute(): \Illuminate\Support\Collection
    {
        return $this->assignees->map(function ($assignee) {
            return $assignee->user;
        })->filter();
    }

    public function getFormattedEstimatedHoursAttribute(): string
    {
        return number_format($this->estimated_hours, 2);
    }

    public function getFormattedActualHoursAttribute(): string
    {
        return number_format($this->actual_hours, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::getPriorities()[$this->priority] ?? ucfirst($this->priority);
    }

    public function getStatusBadgeAttribute(): array
    {
        $colors = self::STATUS_COLORS[$this->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'border' => 'border-gray-300', 'ring' => 'ring-gray-400'];

        return [
            'type' => $this->status,
            'label' => $this->status_label,
            'icon' => self::STATUS_ICONS[$this->status] ?? 'heroicon-o-circle',
            'bg' => $colors['bg'],
            'text' => $colors['text'],
            'border' => $colors['border'],
            'ring' => $colors['ring'] ?? 'ring-gray-400',
        ];
    }

    public function getPriorityBadgeAttribute(): array
    {
        $badges = [
            'low' => ['type' => 'gray', 'text' => 'Rendah'],
            'medium' => ['type' => 'blue', 'text' => 'Sedang'],
            'high' => ['type' => 'orange', 'text' => 'Tinggi'],
            'urgent' => ['type' => 'red', 'text' => 'Urgent'],
        ];

        return $badges[$this->priority] ?? ['type' => 'secondary', 'text' => ucfirst($this->priority)];
    }

    public function getTagsArrayAttribute(): array
    {
        if (empty($this->tags)) {
            return [];
        }
        return array_map('trim', explode(',', $this->tags));
    }

    public function getRecurringTypeLabelAttribute(): ?string
    {
        if (empty($this->recurring_type)) {
            return null;
        }

        $labels = [
            'weekly' => 'Minggu',
            'biweekly' => '2 Minggu',
            'monthly' => '1 Bulan',
            'quarterly' => '3 Bulan',
            'biannually' => '6 Bulan',
            'yearly' => '12 Bulan',
            'custom' => 'Kustom',
        ];

        return $labels[$this->recurring_type] ?? ucfirst($this->recurring_type);
    }

    public function getIsRecurringAttribute(): bool
    {
        return !empty($this->recurring_type);
    }

    public function getChecklistProgressAttribute(): array
    {
        $total = $this->checklists->count();
        $checked = $this->checklists->where('is_checked', true)->count();

        return [
            'total' => $total,
            'checked' => $checked,
            'percent' => $total > 0 ? round(($checked / $total) * 100) : 0,
        ];
    }

    // =====================================================
    // PERMISSION HELPERS - ENTERPRISE PROJECT MANAGEMENT
    // =====================================================

    /**
     * ROLE DEFINITIONS:
     * - Director/Admin: Full access to all task operations
     * - Manager: Can edit task details (progress, notes), submit for approval, cannot delete or approve
     * - Staff: Can work on assigned tasks, cannot delete or approve
     */

    /**
     * Check if user can VIEW this task.
     *
     * RULES:
     * - Admin, Director, and Company Admin → can view ALL tasks in their company/tenant
     * - Others → can ONLY view tasks where they are in Assignees
     *
     * NOTE: Tenant isolation (company_id check) is handled separately in controllers/middleware.
     * This method only handles role-based visibility within the user's tenant.
     */
    public function canBeViewedBy($user): bool
    {
        // Director, Admin, and Company Admin can view all tasks within their tenant
        if (self::isUserDirectorOrAdmin($user)) {
            return true;
        }

        // All other users (Staff, Member, etc.) can ONLY view tasks where they are in Assignees
        // They cannot see tasks just because they are project members
        return $this->assignees()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Check if user can VIEW task structure details (name, project, assignee, priority, etc.)
     * Same rules as canBeViewedBy
     */
    public function canViewStructure($user): bool
    {
        return $this->canBeViewedBy($user);
    }

    /**
     * Check if user can EDIT task STRUCTURE (name, project, assignee, priority, milestone)
     * Director/Admin: Yes
     * Manager: NO - cannot change structure
     * Staff: NO - cannot change structure
     */
    public function canEditStructure($user): bool
    {
        if ($this->is_completed || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        return self::isUserDirectorOrAdmin($user);
    }

    /**
     * Check if user can EDIT task WORK (progress, status notes, working notes)
     * Director/Admin: Yes
     * Manager: Yes
     * Staff: Yes (if assigned)
     */
    public function canEditWork($user): bool
    {
        if ($this->status === self::STATUS_COMPLETED || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        // Director and Admin can edit work
        if (self::isUserDirectorOrAdmin($user)) {
            return true;
        }

        // Manager can edit work
        if (self::isUserManager($user)) {
            return true;
        }

        // Staff can edit if assigned
        return $this->assignees()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Check if task can be started.
     * Anyone with work access can start.
     */
    public function canBeStartedBy($user): bool
    {
        if ($this->status !== self::STATUS_TO_DO) {
            return false;
        }

        return $this->canEditWork($user);
    }

    /**
     * Check if task can be submitted for approval.
     * Manager, Staff, Admin, Director can submit.
     * Must have required photos if require_photo is true.
     */
    public function canBeSubmittedForApprovalBy($user): bool
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            return false;
        }

        if (!$this->canEditWork($user)) {
            return false;
        }

        // Check if photo is required and has evidence
        if ($this->require_photo && $this->photos()->count() === 0) {
            return false;
        }

        return true;
    }

    /**
     * Get validation message for photo requirement.
     */
    public function getPhotoRequirementMessage(): ?string
    {
        if ($this->require_photo && $this->photos()->count() === 0) {
            return 'Task ini memerlukan minimal 1 bukti foto sebelum dapat diajukan untuk approval.';
        }
        return null;
    }

    /**
     * Check if user can APPROVE this task.
     * Only Director and Admin.
     */
    public function canApprove($user): bool
    {
        if ($this->status !== self::STATUS_WAITING_APPROVAL) {
            return false;
        }

        return self::isUserDirectorOrAdmin($user);
    }

    /**
     * Check if user can REJECT this task.
     * Only Director and Admin.
     */
    public function canReject($user): bool
    {
        if ($this->status !== self::STATUS_WAITING_APPROVAL) {
            return false;
        }

        return self::isUserDirectorOrAdmin($user);
    }

    /**
     * Check if user can REOPEN this task (from Completed back to In Progress).
     * Only Director and Admin.
     * Completed is FINAL unless explicitly reopened.
     */
    public function canReopen($user): bool
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return false;
        }

        return self::isUserDirectorOrAdmin($user);
    }

    /**
     * Check if user can DELETE this task.
     * Only Director and Admin.
     */
    public function canDelete($user): bool
    {
        return self::isUserDirectorOrAdmin($user);
    }

    /**
     * Check if user can RESTORE this task (from soft delete).
     * Only Director and Admin.
     */
    public function canRestore($user): bool
    {
        return self::isUserDirectorOrAdmin($user);
    }

    /**
     * Check if user can VIEW this task in Recycle Bin.
     * Only Director and Admin.
     */
    public function canViewInRecycleBin($user): bool
    {
        return self::isUserDirectorOrAdmin($user);
    }

    /**
     * Check if user can ADD WORK UPDATE to this task.
     * Assignees and Director/Admin can add work updates.
     */
    public function canAddWorkUpdate($user): bool
    {
        // Director/Admin can always add work updates
        if (self::isUserDirectorOrAdmin($user)) {
            return true;
        }

        // Check if user is an assignee
        return $this->assignees()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * ACCESSOR: Check if current user can DELETE this task.
     * Only Director and Admin can delete.
     * Used in views for conditional rendering.
     */
    public function getCanDeleteAttribute(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        return self::isUserDirectorOrAdmin($user);
    }

    /**
     * ACCESSOR: Check if current user can EDIT this task (work).
     * Used in views for conditional rendering.
     */
    public function getCanEditWorkAttribute(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        return $this->canEditWork($user);
    }

    /**
     * ACCESSOR: Check if current user can START this task.
     * Used in views for conditional rendering.
     */
    public function getCanBeStartedByAttribute(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        return $this->canBeStartedBy($user);
    }

    /**
     * ACCESSOR: Check if current user can APPROVE this task.
     * Used in views for conditional rendering.
     */
    public function getCanApproveAttribute(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        return $this->canApprove($user);
    }

    /**
     * Get available next statuses for this task based on current status.
     */
    public function getAvailableNextStatuses($user): array
    {
        $nextStatuses = [];

        switch ($this->status) {
            case self::STATUS_TO_DO:
                $nextStatuses = [
                    self::STATUS_IN_PROGRESS => [
                        'label' => 'Sedang Dikerjakan',
                        'icon' => 'heroicon-o-arrow-right-circle',
                        'color' => 'blue',
                        'action' => 'start',
                        'can_perform' => $this->canBeStartedBy($user),
                    ],
                    self::STATUS_CANCELLED => [
                        'label' => 'Dibatalkan',
                        'icon' => 'heroicon-o-x-circle',
                        'color' => 'red',
                        'action' => 'cancel',
                        'can_perform' => self::isUserDirectorOrAdmin($user),
                    ],
                ];
                break;

            case self::STATUS_IN_PROGRESS:
                $nextStatuses = [
                    self::STATUS_WAITING_APPROVAL => [
                        'label' => 'Ajukan Approval',
                        'icon' => 'heroicon-o-paper-airplane',
                        'color' => 'amber',
                        'action' => 'submit_approval',
                        'can_perform' => $this->canBeSubmittedForApprovalBy($user),
                    ],
                    self::STATUS_TO_DO => [
                        'label' => 'Kembalikan ke To Do',
                        'icon' => 'heroicon-o-arrow-uturn-left',
                        'color' => 'gray',
                        'action' => 'revert_to_do',
                        'can_perform' => self::isUserDirectorOrAdmin($user) || self::isUserManager($user),
                    ],
                    self::STATUS_CANCELLED => [
                        'label' => 'Dibatalkan',
                        'icon' => 'heroicon-o-x-circle',
                        'color' => 'red',
                        'action' => 'cancel',
                        'can_perform' => self::isUserDirectorOrAdmin($user),
                    ],
                ];
                break;

            case self::STATUS_WAITING_APPROVAL:
                $nextStatuses = [
                    self::STATUS_COMPLETED => [
                        'label' => 'Setujui (Completed)',
                        'icon' => 'heroicon-o-check-circle',
                        'color' => 'green',
                        'action' => 'approve',
                        'can_perform' => $this->canApprove($user),
                    ],
                    self::STATUS_IN_PROGRESS => [
                        'label' => 'Tolak (Kembalikan)',
                        'icon' => 'heroicon-o-x-mark',
                        'color' => 'orange',
                        'action' => 'reject',
                        'can_perform' => $this->canReject($user),
                    ],
                ];
                break;

            case self::STATUS_COMPLETED:
                // Completed is FINAL - only Director/Admin can reopen
                $nextStatuses = [
                    self::STATUS_IN_PROGRESS => [
                        'label' => 'Buka Ulang (Reopen)',
                        'icon' => 'heroicon-o-arrow-path',
                        'color' => 'purple',
                        'action' => 'reopen',
                        'can_perform' => $this->canReopen($user),
                    ],
                ];
                break;
        }

        // Filter out statuses user cannot perform
        return array_filter($nextStatuses, fn($status) => $status['can_perform']);
    }

    // =====================================================
    // MUTATORS
    // =====================================================

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = ucfirst(trim($value));
    }

    // =====================================================
    // HELPERS - STATUS TRANSITIONS
    // =====================================================

    /**
     * Start task - change status to In Progress.
     * Can be done by anyone with work access.
     */
    public function start($user = null): bool
    {
        if ($this->status !== self::STATUS_TO_DO) {
            return false;
        }

        $oldStatus = $this->status;

        $updated = $this->update([
            'status' => self::STATUS_IN_PROGRESS,
        ]);

        if ($updated && $user) {
            TaskActivity::logStatusChanged($this, $user, $oldStatus, self::STATUS_IN_PROGRESS, 'Task dimulai');
        }

        return $updated;
    }

    /**
     * Mark task as waiting for approval.
     * Can be done by Manager, Staff, Admin, Director.
     */
    public function markAsWaitingApproval($user = null): bool
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            return false;
        }

        $oldStatus = $this->status;

        $updated = $this->update([
            'status' => self::STATUS_WAITING_APPROVAL,
        ]);

        if ($updated && $user) {
            TaskActivity::logStatusChanged($this, $user, $oldStatus, self::STATUS_WAITING_APPROVAL, 'Diajukan untuk approval');
        }

        return $updated;
    }

    /**
     * Approve task - mark as completed.
     * Can ONLY be done by Director and Admin.
     * Records approval in TaskApproval history.
     */
    public function approve($approver): bool
    {
        if ($this->status !== self::STATUS_WAITING_APPROVAL) {
            return false;
        }

        $oldStatus = $this->status;

        $updated = $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress' => 100,
        ]);

        if ($updated) {
            // Log activity
            TaskActivity::logApproved($this, $approver);

            // Record approval in approval history
            TaskApproval::recordApproval($this, $approver);
        }

        return $updated;
    }

    /**
     * Reject task - return to in progress.
     * Can ONLY be done by Director and Admin.
     * Records rejection in TaskApproval history.
     */
    public function reject($rejector, string $reason): bool
    {
        if ($this->status !== self::STATUS_WAITING_APPROVAL) {
            return false;
        }

        $oldStatus = $this->status;
        $version = TaskApproval::getNextVersion($this);

        $updated = $this->update([
            'status' => self::STATUS_IN_PROGRESS,
        ]);

        if ($updated) {
            // Log activity
            TaskActivity::logRejected($this, $rejector, $reason, $version);

            // Record rejection in approval history
            TaskApproval::recordRejection($this, $rejector, $reason);
        }

        return $updated;
    }

    /**
     * Reopen task - return from Completed to In Progress.
     * Can ONLY be done by Director and Admin.
     * Completed is FINAL unless explicitly reopened.
     */
    public function reopen($user, ?string $reason = null): bool
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return false;
        }

        $oldStatus = $this->status;

        $updated = $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'completed_at' => null,
        ]);

        if ($updated) {
            // Log activity
            TaskActivity::logReopened($this, $user, $reason);

            // Record reopen in approval history
            TaskApproval::recordReopen($this, $user, $reason);
        }

        return $updated;
    }

    /**
     * Cancel task.
     * Can only be done by Director and Admin.
     */
    public function cancel($user = null): bool
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            return false;
        }

        $oldStatus = $this->status;

        $updated = $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);

        if ($updated && $user) {
            TaskActivity::logStatusChanged($this, $user, $oldStatus, self::STATUS_CANCELLED, 'Task dibatalkan');
        }

        return $updated;
    }

    /**
     * Complete task directly (bypass approval workflow).
     * Can ONLY be done by Director and Admin.
     */
    public function complete($user): bool
    {
        if (!self::isUserDirectorOrAdmin($user)) {
            return false;
        }

        if ($this->status === self::STATUS_COMPLETED) {
            return false;
        }

        $oldStatus = $this->status;

        $updated = $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress' => 100,
        ]);

        if ($updated) {
            TaskActivity::logStatusChanged($this, $user, $oldStatus, self::STATUS_COMPLETED, 'Diselesaikan langsung (bypass approval)');
            TaskApproval::recordApproval($this, $user, 'Diselesaikan langsung');
        }

        return $updated;
    }

    /**
     * Revert task back to To Do.
     */
    public function revertToDo($user = null): bool
    {
        if (!in_array($this->status, [self::STATUS_IN_PROGRESS])) {
            return false;
        }

        $oldStatus = $this->status;

        $updated = $this->update([
            'status' => self::STATUS_TO_DO,
        ]);

        if ($updated && $user) {
            TaskActivity::logStatusChanged($this, $user, $oldStatus, self::STATUS_TO_DO, 'Dikembalikan ke To Do');
        }

        return $updated;
    }

    /**
     * Assign user to task with activity logging.
     */
    public function assignTo($user, string $role = null, string $jobDescription = null, $assigner = null): TaskAssignee
    {
        $existing = $this->assignees()->where('user_id', $user->id)->first();

        $assignee = $this->assignees()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'tenant_id' => $this->tenant_id,
                'assigned_at' => now(),
                'role' => $role,
                'job_description' => $jobDescription,
            ]
        );

        // Log if this is a new assignment
        if (!$existing) {
            TaskActivity::logAssigned($this, $assigner ?? auth()->user(), $user, $role);
        }

        return $assignee;
    }

    /**
     * Unassign user from task with activity logging.
     */
    public function unassign($user, $unassigner = null): bool
    {
        $deleted = $this->assignees()->where('user_id', $user->id)->delete();

        if ($deleted) {
            TaskActivity::create([
                'uuid' => \Str::uuid(),
                'tenant_id' => $this->tenant_id,
                'task_id' => $this->id,
                'user_id' => $unassigner?->id ?? auth()->id(),
                'activity_type' => TaskActivity::TYPE_UNASSIGNED,
                'old_value' => $user->id,
                'metadata' => [
                    'unassigned_user_id' => $user->id,
                    'unassigned_user_name' => $user->name,
                ],
                'note' => "Menghapus {$user->name} dari task",
            ]);
        }

        return $deleted;
    }

    /**
     * Generate next task number.
     * Uses DB transaction with row locking to prevent race conditions.
     * Includes soft-deleted records to avoid sequence gaps.
     */
    public static function generateNumber(): string
    {
        $year = date('Y');

        return \DB::transaction(function () use ($year) {
            // Use lock to prevent race condition when multiple users create tasks simultaneously
            // IMPORTANT: Include soft-deleted records to avoid sequence gaps
            $lastTask = static::withTrashed()
                ->where('task_number', 'like', "TSK-{$year}-%")
                ->lockForUpdate()
                ->orderBy('task_number', 'desc')
                ->first();

            $sequence = 1;

            if ($lastTask) {
                // Extract sequence from task_number (e.g., "TSK-2026-0001" -> extract "0001" and add 1)
                $parts = explode('-', $lastTask->task_number);
                $sequence = isset($parts[2]) ? ((int) $parts[2]) + 1 : 1;
            }

            return sprintf('TSK-%s-%04d', $year, $sequence);
        });
    }

    public static function getStatuses(): array
    {
        return self::STATUS_LABELS;
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

    public static function getRoles(): array
    {
        return [
            'admin' => 'Admin',
            'director' => 'Director',
            'manager' => 'Manager',
            'technician' => 'Teknisi',
            'nms' => 'NMS',
            'devops' => 'DevOps',
            'support' => 'Support',
        ];
    }

    public static function getRecurringTypes(): array
    {
        return [
            'weekly' => 'Minggu',
            'biweekly' => '2 Minggu',
            'monthly' => '1 Bulan',
            'quarterly' => '3 Bulan',
            'biannually' => '6 Bulan',
            'yearly' => '12 Bulan',
            'custom' => 'Kustom',
        ];
    }
}
