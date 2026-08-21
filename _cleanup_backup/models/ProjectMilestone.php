<?php

namespace App\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ProjectMilestone extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;
    use HasAuditLog;

    // Boot method to auto-generate UUID
    protected static function booted(): void
    {
        static::creating(function (ProjectMilestone $milestone) {
            if (empty($milestone->uuid)) {
                $milestone->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'tenant_id',
        'company_id',
        'project_id',
        'milestone_number',
        'name',
        'description',
        'start_date',
        'target_date',
        'completed_date',
        'status',
        'visibility',
        'color',
        'pic_id',
        'manual_progress',
        'auto_progress',
        'total_tasks',
        'completed_tasks',
        'documentation',
        'notes',
        'visible_to_users',
        'visible_to_divisions',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_date' => 'date',
            'completed_date' => 'date',
            'manual_progress' => 'integer',
            'auto_progress' => 'integer',
            'total_tasks' => 'integer',
            'completed_tasks' => 'integer',
            'sort_order' => 'integer',
            'documentation' => 'array',
            'visible_to_users' => 'array',
            'visible_to_divisions' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    // Status - Professional Project Management
    const STATUS_NOT_STARTED = 'not_started';   // Belum Dimulai
    const STATUS_IN_PROGRESS = 'in_progress';   // Berjalan
    const STATUS_COMPLETED = 'completed';        // Selesai
    const STATUS_CANCELLED = 'cancelled';       // Dibatalkan
    const STATUS_OVERDUE = 'overdue';           // Terlambat (computed)

    // Aliases for backward compatibility
    const STATUS_PENDING = 'not_started';
    const STATUS_ACTIVE = 'in_progress';

    // Visibility
    const VISIBILITY_PROJECT = 'project'; // Visible to all project members
    const VISIBILITY_SELECTED = 'selected'; // Visible only to selected users/divisions
    const VISIBILITY_PRIVATE = 'private'; // Only creator and assigned users

    // Milestone Colors (for UI)
    const COLORS = [
        'blue' => '#3b82f6',
        'green' => '#10b981',
        'purple' => '#8b5cf6',
        'red' => '#ef4444',
        'orange' => '#f97316',
        'yellow' => '#eab308',
        'pink' => '#ec4899',
        'cyan' => '#06b6d4',
    ];

    /**
     * Get status label in Indonesian.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_NOT_STARTED => 'Belum Dimulai',
            self::STATUS_IN_PROGRESS => 'Berjalan',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_OVERDUE => 'Terlambat',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    /**
     * Check if milestone is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->target_date &&
               $this->target_date->isPast() &&
               !$this->isCompleted() &&
               $this->status !== self::STATUS_CANCELLED;
    }

    /**
     * Check if milestone is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get effective status (considering overdue).
     */
    public function getEffectiveStatus(): string
    {
        if ($this->isOverdue()) {
            return self::STATUS_OVERDUE;
        }
        return $this->status;
    }

    /**
     * Get status badge for UI.
     */
    public function getStatusBadge(): array
    {
        $status = $this->getEffectiveStatus();

        $badges = [
            'not_started' => [
                'type' => 'gray',
                'bg' => 'bg-gray-100',
                'text' => 'text-gray-700',
                'label' => 'Belum Dimulai',
                'icon' => 'fa-clock',
                'color' => '#6b7280'
            ],
            'in_progress' => [
                'type' => 'blue',
                'bg' => 'bg-blue-100',
                'text' => 'text-blue-700',
                'label' => 'Berjalan',
                'icon' => 'fa-play',
                'color' => '#3b82f6'
            ],
            'completed' => [
                'type' => 'green',
                'bg' => 'bg-green-100',
                'text' => 'text-green-700',
                'label' => 'Selesai',
                'icon' => 'fa-check-circle',
                'color' => '#10b981'
            ],
            'cancelled' => [
                'type' => 'red',
                'bg' => 'bg-red-100',
                'text' => 'text-red-700',
                'label' => 'Dibatalkan',
                'icon' => 'fa-times-circle',
                'color' => '#ef4444'
            ],
            'overdue' => [
                'type' => 'orange',
                'bg' => 'bg-orange-100',
                'text' => 'text-orange-700',
                'label' => 'Terlambat',
                'icon' => 'fa-exclamation-triangle',
                'color' => '#f97316'
            ],
        ];

        return $badges[$status] ?? $badges['not_started'];
    }

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'milestone_id')->orderBy('milestone_sort_order');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeNotStarted($query)
    {
        return $query->where('status', self::STATUS_NOT_STARTED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope for overdue milestones (based on target_date).
     */
    public function scopeOverdue($query)
    {
        return $query->where('target_date', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope for active milestones (not completed or cancelled).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeVisibleTo($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // Project visibility - visible to all project members
            $q->where('visibility', self::VISIBILITY_PROJECT)
                ->orWhere('created_by', $user->id)
                // Selected visibility
                ->orWhere(function ($q2) use ($user) {
                    $q2->where('visibility', self::VISIBILITY_SELECTED)
                        ->where(function ($q3) use ($user) {
                            $q3->whereJsonContains('visible_to_users', $user->id)
                                ->orWhereJsonContains('visible_to_divisions', $user->division_id);
                        });
                });
        });
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getStatusBadgeAttribute(): array
    {
        return $this->getStatusBadge();
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->isOverdue();
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->isCompleted();
    }

    public function getProgressAttribute(): int
    {
        // Use auto_progress if tasks exist, otherwise manual_progress
        if ($this->total_tasks > 0) {
            return $this->auto_progress ?? 0;
        }
        return $this->manual_progress ?? 0;
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->target_date) {
            return null;
        }
        return now()->diffInDays($this->target_date, false);
    }

    public function getDaysRemainingAttribute(): ?int
    {
        $days = $this->days_until_due;
        return $days !== null && $days > 0 ? $days : null;
    }

    public function getFormattedProgressAttribute(): string
    {
        return $this->progress . '%';
    }

    public function getDocumentationPhotosAttribute(): array
    {
        if (!$this->documentation || !isset($this->documentation['photos']) || !is_array($this->documentation['photos'])) {
            return [];
        }

        return array_map(function ($photo) {
            return Storage::disk('public')->url($photo);
        }, $this->documentation['photos']);
    }

    public function getTaskStatsAttribute(): array
    {
        return [
            'total' => $this->total_tasks,
            'completed' => $this->completed_tasks,
            'in_progress' => $this->tasks()->where('status', Task::STATUS_IN_PROGRESS)->count(),
            'pending' => $this->tasks()->whereIn('status', [Task::STATUS_TO_DO, Task::STATUS_IN_PROGRESS])->count(),
            'waiting_approval' => $this->tasks()->where('status', Task::STATUS_WAITING_APPROVAL)->count(),
            'overdue' => $this->tasks()->overdue()->count(),
        ];
    }

    public function getVisibilityLabelAttribute(): string
    {
        return match($this->visibility) {
            self::VISIBILITY_PROJECT => 'Semua Member',
            self::VISIBILITY_SELECTED => 'Tertentu',
            self::VISIBILITY_PRIVATE => 'Privat',
            default => 'Project',
        };
    }

    // =====================================================
    // HELPERS
    // =====================================================

    /**
     * Start milestone - change status to In Progress
     */
    public function start(): bool
    {
        if ($this->status !== self::STATUS_NOT_STARTED) {
            return false;
        }

        return $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    public function markInProgress(): bool
    {
        if ($this->status === self::STATUS_NOT_STARTED) {
            return $this->update(['status' => self::STATUS_IN_PROGRESS]);
        }
        return false;
    }

    /**
     * Mark as not started.
     */
    public function markNotStarted(): bool
    {
        return $this->update([
            'status' => self::STATUS_NOT_STARTED,
            'completed_date' => null,
        ]);
    }

    /**
     * Complete milestone - marks as completed
     */
    public function complete(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_date' => now(),
            'manual_progress' => 100,
            'auto_progress' => 100,
        ]);
    }

    /**
     * Cancel milestone
     */
    public function cancel(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Update progress manually
     */
    public function updateProgress(int $progress): bool
    {
        $progress = min(100, max(0, $progress));

        $data = ['manual_progress' => $progress];

        if ($progress >= 100 && $this->status !== self::STATUS_COMPLETED) {
            $data['status'] = self::STATUS_COMPLETED;
            $data['completed_date'] = now();
        } elseif ($progress > 0 && $this->status === self::STATUS_NOT_STARTED) {
            $data['status'] = self::STATUS_IN_PROGRESS;
        }

        return $this->update($data);
    }

    /**
     * Calculate and update auto progress from tasks.
     * Also handles automatic status changes:
     * - Auto-complete when all tasks are done
     * - Auto-start when any task is in progress
     * - Auto-overdue detection
     */
    public function calculateAutoProgress(): bool
    {
        $tasks = $this->tasks()->get();

        $total = $tasks->count();
        $completed = $tasks->where('status', Task::STATUS_COMPLETED)->count();

        // Calculate auto progress
        $autoProgress = $total > 0 ? round(($completed / $total) * 100) : 0;

        $data = [
            'auto_progress' => $autoProgress,
            'total_tasks' => $total,
            'completed_tasks' => $completed,
        ];

        // Auto-complete milestone if ALL tasks are done
        if ($total > 0 && $completed === $total && $this->status !== self::STATUS_COMPLETED) {
            $data['status'] = self::STATUS_COMPLETED;
            $data['completed_date'] = now();
            $data['manual_progress'] = 100;
        }

        // Auto-start if there are tasks and some are in progress
        if ($total > 0 && $completed > 0 && $this->status === self::STATUS_NOT_STARTED) {
            $data['status'] = self::STATUS_IN_PROGRESS;
        }

        // Auto-mark as in progress if there are tasks but none completed yet
        if ($total > 0 && $completed === 0 && $this->status === self::STATUS_NOT_STARTED) {
            // Check if any task is in progress
            $inProgress = $tasks->where('status', Task::STATUS_IN_PROGRESS)->count();
            if ($inProgress > 0) {
                $data['status'] = self::STATUS_IN_PROGRESS;
            }
        }

        return $this->update($data);
    }

    /**
     * Quick complete - shortcut when manually marking complete.
     */
    public function quickComplete(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_date' => now(),
            'manual_progress' => 100,
            'auto_progress' => 100,
            'total_tasks' => $this->total_tasks ?: 0,
            'completed_tasks' => $this->total_tasks ?: 0,
        ]);
    }

    /**
     * Quick restart - mark as not started and clear completion.
     */
    public function quickRestart(): bool
    {
        return $this->update([
            'status' => self::STATUS_NOT_STARTED,
            'completed_date' => null,
            'manual_progress' => 0,
        ]);
    }

    /**
     * Add documentation photos
     */
    public function addDocumentation(array $photos, ?string $notes = null): bool
    {
        $doc = $this->documentation ?? [];

        // Add new photos
        foreach ($photos as $photo) {
            $doc['photos'][] = $photo;
        }

        // Add notes
        if ($notes) {
            $doc['notes'] = $doc['notes'] ?? [];
            $doc['notes'][] = [
                'text' => $notes,
                'added_at' => now()->toISOString(),
                'added_by' => auth()->id(),
            ];
        }

        return $this->update(['documentation' => $doc]);
    }

    /**
     * Set visibility for milestone
     */
    public function setVisibility(string $visibility, ?array $userIds = null, ?array $divisionIds = null): bool
    {
        $data = ['visibility' => $visibility];

        if ($visibility === self::VISIBILITY_SELECTED) {
            $data['visible_to_users'] = $userIds ?? [];
            $data['visible_to_divisions'] = $divisionIds ?? [];
        }

        return $this->update($data);
    }

    /**
     * Check if milestone is visible to user
     */
    public function isVisibleTo(User $user): bool
    {
        // Creator always sees their milestones
        if ($this->created_by === $user->id) {
            return true;
        }

        switch ($this->visibility) {
            case self::VISIBILITY_PROJECT:
                // Check if user is a project member
                return $this->project->members()->where('user_id', $user->id)->exists();

            case self::VISIBILITY_SELECTED:
                // Check user ID
                if ($this->visible_to_users && in_array($user->id, $this->visible_to_users)) {
                    return true;
                }
                // Check division
                if ($user->division_id && $this->visible_to_divisions && in_array($user->division_id, $this->visible_to_divisions)) {
                    return true;
                }
                return false;

            case self::VISIBILITY_PRIVATE:
                // Only creator sees private milestones
                return $this->created_by === $user->id;

            default:
                return true;
        }
    }

    /**
     * Generate next milestone number.
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        return \DB::transaction(function () use ($year, $month) {
            // Use lock to prevent race condition when multiple users create milestones simultaneously
            $lastMilestone = static::where('milestone_number', 'like', "MS-{$year}{$month}-%")
                ->lockForUpdate()
                ->orderBy('milestone_number', 'desc')
                ->first();

            $sequence = 1;

            if ($lastMilestone) {
                // Extract sequence from milestone_number (e.g., "MS-202607-0001" -> extract "0001" and add 1)
                $parts = explode('-', $lastMilestone->milestone_number);
                $sequence = isset($parts[2]) ? ((int) $parts[2]) + 1 : 1;
            }

            return sprintf('MS-%s%s-%04d', $year, $month, $sequence);
        });
    }

    /**
     * Get next sort order for project
     */
    public static function getNextSortOrder(int $projectId): int
    {
        $max = static::where('project_id', $projectId)->max('sort_order');
        return ($max ?? 0) + 1;
    }

    /**
     * Get summary array for API/JS responses.
     * Handles null values gracefully.
     */
    public function toSummaryArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'milestone_number' => $this->milestone_number,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'progress' => $this->progress,
            'total_tasks' => $this->total_tasks ?? 0,
            'completed_tasks' => $this->completed_tasks ?? 0,
            'start_date' => $this->start_date?->toDateString(),
            'target_date' => $this->target_date?->toDateString(),
            'completed_date' => $this->completed_date?->toDateString(),
            'is_overdue' => $this->isOverdue(),
            'days_remaining' => $this->days_remaining,
            'color' => $this->color ?? 'blue',
            'color_hex' => self::COLORS[$this->color] ?? self::COLORS['blue'],
            'pic_id' => $this->pic_id,
            'creator' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
