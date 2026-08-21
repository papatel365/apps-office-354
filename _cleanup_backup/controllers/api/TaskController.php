<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Modules\System\Models\User;
use App\Traits\CrmPermissionQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use CrmPermissionQueries;

    /**
     * Check if current user is Director or Admin.
     * Uses User model's isDirectorOrAdmin() method for consistency.
     */
    protected function isDirectorOrAdmin(): bool
    {
        return $this->user()?->isDirectorOrAdmin() ?? false;
    }

    /**
     * Check if user can view a specific task.
     * Uses ProjectTaskPolicy for consistent permission logic.
     */
    protected function canViewTask(Task $task): bool
    {
        return \Illuminate\Support\Facades\Gate::allows('view', $task);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Task::query()
            ->with('project', 'assignees.user')
            ->when($request->project_id, fn($q) => $q->byProject($request->project_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->byPriority($request->priority));

        // Apply unified permission filter using CrmPermissionQueries trait
        // Handles: Superadmin/Global scope → all tasks, Own scope → creator OR assignee
        $query = $this->applyTaskPermissionFilter($query);

        $tasks = $query->orderBy('due_date', 'asc')->paginate($request->per_page ?? 20);

        return $this->paginated($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:to_do,in_progress,waiting_approval,completed,cancelled',
            'due_date' => 'nullable|date',
            'assignee_ids' => 'nullable|array',
        ]);

        // SECURITY: Validate all assignees have required roles (Director or Admin)
        if ($request->assignee_ids) {
            foreach ($request->assignee_ids as $userId) {
                $user = User::find($userId);
                if (!$user || !$user->canBeAssignee()) {
                    return $this->error('User yang dipilih tidak memiliki hak sebagai Assignee.', 403);
                }
            }
        }

        $task = Task::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'task_number' => Task::generateNumber(),
            'created_by' => $this->user()->id,
            'assigned_by' => $this->user()->id,
            'status' => Task::STATUS_TO_DO,
            ...$request->validated(),
        ]);

        if ($request->assignee_ids) {
            foreach ($request->assignee_ids as $userId) {
                $task->assignTo(User::find($userId));
            }
        }

        return $this->success($task->load('assignees.user'), 'Task created', 201);
    }

    public function show(Task $task): JsonResponse
    {
        if (!$this->canViewTask($task)) {
            return $this->error('Anda tidak memiliki akses ke task ini.', 403);
        }

        $task->load('project', 'assignees.user', 'comments.user');

        // Include available next statuses
        $availableStatuses = $task->getAvailableNextStatuses($this->user());

        return $this->success([
            'task' => $task,
            'available_statuses' => $availableStatuses,
            'can_approve' => $task->canBeApprovedBy($this->user()),
            'can_complete' => $task->canBeCompletedBy($this->user()),
            'can_delete' => $task->canBeDeletedBy($this->user()),
            'can_edit' => $task->canBeEditedBy($this->user()),
        ]);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        if (!$task->canBeEditedBy($this->user())) {
            return $this->error('Anda tidak memiliki akses untuk mengedit task ini.', 403);
        }

        $task->update($request->validated());

        return $this->success($task, 'Task updated');
    }

    /**
     * Delete task.
     *
     * PERMISSION: Only Director and Admin can delete tasks.
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        if (!$this->isDirectorOrAdmin()) {
            return $this->error('Anda tidak memiliki izin untuk menghapus tugas ini. Hanya Director dan Admin yang dapat menghapus.', 403);
        }

        $task->delete();

        return $this->success(null, 'Task deleted');
    }

    /**
     * Start task - change status to In Progress.
     */
    public function start(Task $task): JsonResponse
    {
        if (!$task->canBeStartedBy($this->user())) {
            return $this->error('Task tidak dapat dimulai dari status saat ini.', 422);
        }

        $task->start();

        return $this->success($task, 'Task started');
    }

    /**
     * Submit task for approval - change status to Waiting Approval.
     */
    public function submitForApproval(Task $task): JsonResponse
    {
        if (!$task->canBeSubmittedForApprovalBy($this->user())) {
            return $this->error('Task tidak dapat diajukan untuk approval dari status saat ini.', 422);
        }

        $task->markAsWaitingApproval();

        // Add system comment
        $task->comments()->create([
            'tenant_id' => $this->tenantId(),
            'user_id' => $this->user()->id,
            'content' => 'Task diajukan untuk approval.',
            'is_internal' => true,
        ]);

        return $this->success($task, 'Task submitted for approval');
    }

    /**
     * Approve task - change status to Completed.
     *
     * PERMISSION: Only Director and Admin can approve.
     */
    public function approve(Request $request, Task $task): JsonResponse
    {
        if (!$task->canBeApprovedBy($this->user())) {
            return $this->error('Anda tidak memiliki izin untuk menyetujui tugas ini. Hanya Director dan Admin.', 403);
        }

        if (!$task->canBeApprovedBy($this->user())) {
            return $this->error('Task tidak dapat disetujui dari status saat ini.', 422);
        }

        $task->approve($this->user());

        // Add approval comment
        $task->comments()->create([
            'tenant_id' => $this->tenantId(),
            'user_id' => $this->user()->id,
            'content' => 'Task disetujui dan ditandai selesai.',
            'is_internal' => true,
        ]);

        // Update project progress
        if ($task->project_id) {
            $task->project->updateProgress();
        }

        return $this->success($task, 'Task approved and completed');
    }

    /**
     * Reject task - return to In Progress.
     *
     * PERMISSION: Only Director and Admin can reject.
     * REJECTION REASON IS REQUIRED.
     */
    public function reject(Request $request, Task $task): JsonResponse
    {
        if (!$task->canBeApprovedBy($this->user())) {
            return $this->error('Anda tidak memiliki izin untuk menolak tugas ini. Hanya Director dan Admin.', 403);
        }

        if ($task->status !== Task::STATUS_WAITING_APPROVAL) {
            return $this->error('Task tidak dapat ditolak dari status saat ini.', 422);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $task->reject($this->user(), $request->rejection_reason);

        // Add rejection comment
        $task->comments()->create([
            'tenant_id' => $this->tenantId(),
            'user_id' => $this->user()->id,
            'content' => "Task ditolak. Alasan: {$request->rejection_reason}",
            'is_internal' => false,
        ]);

        return $this->success($task, 'Task rejected');
    }

    /**
     * Complete task directly.
     *
     * PERMISSION: Only Director and Admin can complete directly.
     */
    public function complete(Task $task): JsonResponse
    {
        if (!$this->isDirectorOrAdmin()) {
            return $this->error('Anda tidak memiliki izin untuk menyelesaikan tugas ini langsung. Gunakan approval workflow.', 403);
        }

        if ($task->status === Task::STATUS_COMPLETED) {
            return $this->error('Task sudah selesai.', 422);
        }

        $task->complete();

        // Update project progress
        if ($task->project_id) {
            $task->project->updateProgress();
        }

        return $this->success($task, 'Task completed');
    }

    /**
     * Cancel task.
     *
     * PERMISSION: Only Director and Admin can cancel.
     */
    public function cancel(Task $task): JsonResponse
    {
        if (!$this->isDirectorOrAdmin()) {
            return $this->error('Anda tidak memiliki izin untuk membatalkan tugas ini.', 403);
        }

        if (in_array($task->status, [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED])) {
            return $this->error('Task tidak dapat dibatalkan.', 422);
        }

        $task->cancel();

        return $this->success($task, 'Task cancelled');
    }
}
