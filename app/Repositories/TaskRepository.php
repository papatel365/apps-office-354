<?php

namespace App\Repositories;

use App\Models\Task;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TaskRepository extends BaseRepository
{
    protected Task $model;

    public function __construct(Task $model)
    {
        $this->model = $model;
    }

    /**
     * Get paginated tasks with filters.
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 20)
    {
        $query = $this->model->with(['project', 'assignees.user']);

        if (!empty($filters['project_id'])) {
            $query->byProject($filters['project_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        if (!empty($filters['user_id'])) {
            $query->byAssignee($filters['user_id']);
        }

        if (!empty($filters['my_tasks'])) {
            $query->byAssignee(auth()->id());
        }

        return $query->orderBy('due_date')->orderBy('priority', 'desc')->paginate($perPage);
    }

    /**
     * Assign user to task.
     */
    public function assignUser(Task $task, int $userId): Task
    {
        $user = User::findOrFail($userId);
        $task->assignTo($user);
        return $task->fresh()->load('assignees.user');
    }

    /**
     * Unassign user from task.
     */
    public function unassignUser(Task $task, int $userId): Task
    {
        $user = User::findOrFail($userId);
        $task->unassign($user);
        return $task->fresh()->load('assignees.user');
    }

    /**
     * Complete task.
     */
    public function complete(Task $task): Task
    {
        $task->complete();

        if ($task->project_id) {
            $task->project->updateProgress();
        }

        return $task->fresh()->load('assignees.user');
    }

    /**
     * Get my tasks summary.
     */
    public function myTasksSummary(): array
    {
        $userId = auth()->id();

        return [
            'pending' => $this->model->byAssignee($userId)->pending()->count(),
            'in_progress' => $this->model->byAssignee($userId)->inProgress()->count(),
            'due_today' => $this->model->byAssignee($userId)->whereDate('due_date', today())->count(),
            'overdue' => $this->model->byAssignee($userId)->overdue()->count(),
        ];
    }

    /**
     * Get tasks by status.
     */
    public function byStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get overdue tasks.
     */
    public function overdue(): Collection
    {
        return $this->model->overdue()->get();
    }

    /**
     * Get root tasks only (no parent).
     */
    public function rootTasks(): Collection
    {
        return $this->model->rootTasks()->get();
    }
}
